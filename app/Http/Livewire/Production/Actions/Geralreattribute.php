<?php

namespace App\Http\Livewire\Production\Actions;

use App\Models\{Notetimeline, Production, ProjectReviewDraft};
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Geralreattribute extends Component
{
    public ?Production $production;

    public $chave;

    protected $listeners = [
        'confirm_reatt' => 'confirm_reatt',
    ];

    public function mount($production, $chave)
    {
        $this->production = $production;
        $this->chave      = $chave;
    }

    public function ask_reatt()
    {
        $context = $this->getProjectReviewReturnContext();
        $note = $this->production->load('Note')->Note->note ?? '---';
        $user = $this->production->load('User')->User->name ?? '---';

        $extraWarning = '';
        $confirmText = 'Sim, Re-atribua!';

        if ($context['isDrawingInPendingReview']) {
            if ($context['hasDraft']) {
                $extraWarning = '<br><br><div class="alert alert-warning text-start mb-0">'
                    . '<strong>Atenção:</strong> existe rascunho do analista nesta análise de projeto. '
                    . 'A análise não será removida automaticamente.</div>';
                $confirmText = 'Sim, continuar mesmo assim';
            } elseif ($context['analystStarted']) {
                $extraWarning = '<br><br><div class="alert alert-warning text-start mb-0">'
                    . '<strong>Atenção:</strong> o analista já iniciou a análise (itens/comentários/anotações). '
                    . 'A análise não será removida automaticamente.</div>';
                $confirmText = 'Sim, continuar mesmo assim';
            } elseif ($context['pendingCycleId']) {
                $extraWarning = '<br><br><div class="alert alert-info text-start mb-0">'
                    . 'Análise de projeto pendente e sem interação do analista: ela será removida antes da reatribuição.</div>';
            }
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Re-atribuir',
            'msg'           => "Você deseja reatribuir a Nota/Ov <strong>{$note}</strong> para <strong>{$user}</strong>?{$extraWarning}",
            'icon'          => 'question',
            'btnOktxt'      => $confirmText,
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_reatt',
            'chave'         => $this->chave,
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Nota/OV foi reatribuída.',
        ]);

    }

    public function confirm_reatt($chave)
    {
        if ($this->chave === $chave) {
            $analysisRemoved = false;
            $updated = false;

            DB::transaction(function () use (&$analysisRemoved, &$updated) {
                $this->production->refresh();
                $context = $this->getProjectReviewReturnContext();

                if (
                    $context['isDrawingInPendingReview']
                    && $context['pendingCycleId']
                    && !$context['hasDraft']
                    && !$context['analystStarted']
                ) {
                    $pendingCycle = $context['pendingCycle'];
                    $pendingCycle->Orders()->delete();
                    $pendingCycle->Findings()->delete();
                    $pendingCycle->Messages()->delete();

                    ProjectReviewDraft::query()
                        ->where('production_id', $this->production->id)
                        ->where('cycle_id', $pendingCycle->id)
                        ->delete();

                    $pendingCycle->delete();
                    $analysisRemoved = true;
                }

                $updated = $this->production->update([
                    'status' => 2,
                    'completed' => false,
                    'confirmed' => false,
                    'completed_at' => null,
                    'confirmed_at' => null,
                ]);
            });

            if ($updated) {
                $this->emit('refresh_list');

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => $analysisRemoved
                        ? 'Nota/Ov reatribuída e análise pendente removida!'
                        : 'Nota/Ov reatribuída com sucesso!',
                    'html'     => $analysisRemoved
                        ? 'A análise de projeto pendente (sem interação do analista) foi removida antes do retorno.'
                        : null,
                    'timer'    => 2800,
                ]);

                $production = $this->production;

                if ($production) {
                    Notetimeline::Create([
                        'note_id'      => $production->id,
                        'service_id'   => $production->service_id,
                        'user_id'      => Auth()->User()->id,
                        'info'         => 'A nota foi reatribuída.',
                        'status'       => 26,
                        'productionId' => $production->id,
                    ]);
                }

            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Ocorreu um erro ao tentar re-atribuir a nota/ov.',
                    'timer'    => 5000,
                ]);
            }
        }

        $this->emitUp('update_list');
    }

    public function render()
    {
        return view('livewire.production.actions.geralreattribute');
    }

    private function getProjectReviewReturnContext(): array
    {
        $production = $this->production->loadMissing([
            'Service:id,uuid,service',
            'ProjectReviewCycles' => function ($q) {
                $q->where('decision', 'PENDING')
                    ->latest('round_number')
                    ->withCount(['Findings', 'Messages'])
                    ->limit(1);
            },
        ]);

        $serviceName = mb_strtolower((string) ($production->Service->service ?? ''));
        $isDrawingService = str_contains($serviceName, 'desenho');
        $isPendingReview = (int) ($production->status ?? 0) === Production::STATUS_IN_PROJECT_REVIEW;

        $pendingCycle = $production->ProjectReviewCycles->first();
        $pendingCycleId = $pendingCycle?->id;

        $hasDraft = false;
        if ($pendingCycleId) {
            $hasDraft = ProjectReviewDraft::query()
                ->where('production_id', $production->id)
                ->where('cycle_id', $pendingCycleId)
                ->exists();
        }

        $analystStarted = false;
        if ($pendingCycle) {
            $analystStarted =
                (int) ($pendingCycle->findings_count ?? 0) > 0
                || (int) ($pendingCycle->messages_count ?? 0) > 0
                || !empty($pendingCycle->analyst_note)
                || !is_null($pendingCycle->decided_at)
                || (string) ($pendingCycle->decision ?? 'PENDING') !== 'PENDING';
        }

        return [
            'isDrawingInPendingReview' => $isDrawingService && $isPendingReview,
            'pendingCycle' => $pendingCycle,
            'pendingCycleId' => $pendingCycleId,
            'hasDraft' => $hasDraft,
            'analystStarted' => $analystStarted,
        ];
    }
}
