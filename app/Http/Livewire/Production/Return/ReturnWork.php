<?php

namespace App\Http\Livewire\Production\Return;

use App\Models\Production;
use App\Models\ReturnWork as ModelsReturnWork;
use App\Models\WorkReport;
use App\Models\WorkReportFlowProduction;
use App\Services\WorkReports\WorkReportCurrentStatusRefresher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReturnWork extends Component
{
    public ?Production $production = null;
    public ?ModelsReturnWork $returnWork = null;
    public array $selectedWorkReportIds = [];
    public array $returnableWorkReports = [];
    private bool $hasDirectWorkReportLinks = false;

    protected $listeners = [
        'toReturn',
        'close',
        'confirm_return_work' => 'save',
    ];

    protected $rules = [
        'returnWork.category' => 'required|string',
        'returnWork.text_obs' => 'required|string|min:6',
        'selectedWorkReportIds' => 'required|array|min:1',
    ];

    protected function messages()
    {
        return [
            'returnWork.category.required' => 'É obrigatório selecionar a categotria.',
            'returnWork.text_obs.required' => 'É obrigatório detalhar o motivo do retorno.',
            'returnWork.text_obs.min' => 'Texto do detalhamento muito curto.',
            'selectedWorkReportIds.required' => 'Selecione ao menos um informe para devolver.',
            'selectedWorkReportIds.min' => 'Selecione ao menos um informe para devolver.',
        ];
    }

    public function toReturn(Production $production)
    {
        $this->production = $production->loadMissing([
            'Note',
            'WorkReportFlowProductions.WorkReport.Company',
            'WorkReportFlowProductions.WorkReport.Orders',
        ]);

        $workReports = $this->returnableWorkReportsForProduction();

        if ($workReports->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORME INEXISTENTE',
                'html'     => 'O INFORME pode ter sido removido da base, não está vinculado a esta produção ou já foi devolvido. Comunique ao responsável este problema.'
            ]);

            return;
        }

        $this->returnWork = new ModelsReturnWork();
        $this->returnableWorkReports = $workReports
            ->map(fn (WorkReport $workReport) => $this->workReportOption($workReport))
            ->values()
            ->all();
        $this->selectedWorkReportIds = $workReports->count() === 1
            ? [(string) $workReports->first()->id]
            : [];

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'returnWorkform',
        ]);
    }

    public function toSave()
    {
        $this->validate();

        $selectedLabels = collect($this->returnableWorkReports)
            ->whereIn('id', array_map('strval', $this->selectedWorkReportIds))
            ->map(function (array $workReport) {
                $scopes = collect($workReport['scopes'])->pluck('label')->implode(', ');

                return "#{$workReport['id']}" . ($scopes ? " ({$scopes})" : '');
            })
            ->implode('<br>');

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'DEVOLVER INFORME',
            'msg'   => "<p><strong>VOCÊ ESTÁ PRESTES A DEVOLVER O INFORME: </strong></p>
                <p class='mb-0 py-0'>OBRA: <strong>{$this->production->Note->note}</strong> </p>
                <p class='mt-0 py-0'>INFORME(S): <strong>{$selectedLabels}</strong></p>

                <p>Deseja continuar?</p> ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Devolva!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_return_work',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum informe foi devolvido.',
        ]);
    }

    public function save()
    {
        $this->validate();

        try {
            $selectedIds = collect($this->selectedWorkReportIds)
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            DB::transaction(function () use ($selectedIds) {
                $workReports = $this->returnableWorkReportsForProduction()
                    ->whereIn('id', $selectedIds)
                    ->values();

                if ($workReports->isEmpty()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'selectedWorkReportIds' => 'Nenhum informe válido foi selecionado para devolução.',
                    ]);
                }

                foreach ($workReports as $workReport) {
                    $workReport->update([
                        'rejected' => true,
                        'informed_at' => null,
                    ]);

                    ModelsReturnWork::query()->create([
                        'work_report_id' => $workReport->id,
                        'service_id' => $this->production->service_id,
                        'user_id' => Auth()->User()->id,
                        'category' => $this->returnWork->category,
                        'text_obs' => $this->returnWork->text_obs,
                    ]);

                    $workReport->update([
                        'retry' => $workReport->Returnwork()->count(),
                    ]);

                    $this->reverseCurrentLinksForWorkReport($workReport);
                    app(WorkReportCurrentStatusRefresher::class)->refresh($workReport->id);
                }

                if ($this->shouldDeleteProductionAfterReturn()) {
                    WorkReportFlowProduction::query()
                        ->where('production_id', $this->production->id)
                        ->delete();

                    $this->production->delete();
                }
            });

            $this->emitUp('refresh_list');
            $this->close();

        } catch (\Illuminate\Validation\ValidationException $e) {

            $text = "";

            foreach ($e->errors() as $error) {
                $text .= $error[0]."<br>";
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'CAMPOS OBRIGATÓRIO',
                'html'     => $text,
            ]);

            return;
        }
    }

    public function close()
    {
        $this->production = null;
        $this->returnWork = null;
        $this->selectedWorkReportIds = [];
        $this->returnableWorkReports = [];
        $this->hasDirectWorkReportLinks = false;
        $this->dispatchBrowserEvent('hideModal');
    }

    private function returnableWorkReportsForProduction(): Collection
    {
        if (!$this->production) {
            return collect();
        }

        $links = WorkReportFlowProduction::query()
            ->with(['WorkReport.Company', 'WorkReport.Orders', 'WorkReport.Note'])
            ->where('production_id', $this->production->id)
            ->where('is_current', true)
            ->whereHas('WorkReport', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('canceled', false)
                        ->orWhereNull('canceled');
                })->where(function ($subQuery) {
                    $subQuery->where('rejected', false)
                        ->orWhereNull('rejected');
                });
            })
            ->orderByRaw("CASE final_scope WHEN 'network' THEN 1 WHEN 'connection' THEN 2 ELSE 3 END")
            ->get();

        $this->hasDirectWorkReportLinks = $links->isNotEmpty();

        if ($links->isNotEmpty()) {
            return $links
                ->pluck('WorkReport')
                ->filter()
                ->unique('id')
                ->values();
        }

        return WorkReport::query()
            ->with(['Company', 'Orders', 'Note'])
            ->where('note_id', $this->production->note_id)
            ->where(function ($query) {
                $query->where('canceled', false)
                    ->orWhereNull('canceled');
            })
            ->where(function ($query) {
                $query->where('rejected', false)
                    ->orWhereNull('rejected');
            })
            ->orderByRaw('COALESCE(informed_at, created_at) DESC')
            ->orderByDesc('id')
            ->get();
    }

    private function workReportOption(WorkReport $workReport): array
    {
        return [
            'id' => (string) $workReport->id,
            'company' => $workReport->Company?->name ?? '---',
            'date' => optional($workReport->informed_at ?? $workReport->created_at)->format('d/m/Y H:i') ?? '---',
            'orders' => $workReport->Orders->pluck('ordem')->filter()->values()->all(),
            'scopes' => $workReport->finalScopeBadges(),
        ];
    }

    private function reverseCurrentLinksForWorkReport(WorkReport $workReport): void
    {
        WorkReportFlowProduction::query()
            ->where('production_id', $this->production->id)
            ->where('work_report_id', $workReport->id)
            ->where('is_current', true)
            ->update([
                'is_current' => false,
                'reversed_at' => now(),
                'reversed_by' => Auth()->id(),
                'reverse_reason' => 'return_work_report',
            ]);
    }

    private function shouldDeleteProductionAfterReturn(): bool
    {
        if ($this->hasDirectWorkReportLinks) {
            return !WorkReportFlowProduction::query()
                ->where('production_id', $this->production->id)
                ->where('is_current', true)
                ->exists();
        }

        return !WorkReport::query()
            ->where('note_id', $this->production->note_id)
            ->where(function ($query) {
                $query->where('canceled', false)
                    ->orWhereNull('canceled');
            })
            ->where(function ($query) {
                $query->where('rejected', false)
                    ->orWhereNull('rejected');
            })
            ->exists();
    }

    public function render()
    {
        return view('livewire.production.return.return-work');
    }
}
