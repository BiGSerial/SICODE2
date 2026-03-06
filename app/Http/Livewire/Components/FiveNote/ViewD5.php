<?php

namespace App\Http\Livewire\Components\FiveNote;

use App\Models\EvidenceFile;
use App\Models\FiveNote;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ViewD5 extends Component
{
    public $five;
    public $hasEvidence = false;
    public array $trackingMeta = [];
    public array $eventTimeline = [];
    protected ?string $fiscalizationServiceId = null;
    protected ?string $paymentServiceId = null;

    public $origin = 'EMPREITEIRA';

    protected $listeners = [
        'getInfoResponse',
        'hasEvidence',
        'evidenceSaved',
        'samuca158012Encerrar' => 'toSave',
    ];

    protected $rules = [
        'five.name' => 'required|string|max:255',
    ];

    public function getInfoResponse(FiveNote $five)
    {
        $this->resolveServiceIds();
        $this->five = $five->load([
            'note:id,note,rubrica',
            'note.Productions:id,note_id,service_id,user_id,company_id,created_at,att_at,completed,completed_at,status',
            'note.Productions.User:id,name',
            'note.Productions.Company:id,name',
            'company:id,name',
            'productions:id,service_id,user_id,company_id,created_at,att_at,completed,completed_at,status',
            'productions.User:id,name',
            'productions.Company:id,name',
            'evidenceFiles',
            'comments',
        ]);

        try {
            $this->five->load([
                'timelineEvents' => fn ($q) => $q
                    ->with(['actor:id,name', 'owner:id,name'])
                    ->orderBy('occurred_at', 'asc')
                    ->orderBy('id', 'asc'),
            ]);
        } catch (\Throwable $e) {
            $this->five->setRelation('timelineEvents', collect());
        }

        $this->trackingMeta = $this->buildTrackingMeta($this->five);
        $this->eventTimeline = $this->buildEventTimeline($this->five);

        if ($this->five) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'finishFiveModal',
            ]);
        }
    }

    public function dowloadFile(EvidenceFile $file)
    {
        // dd(Storage::fileExists('public/'.$file->path));

        if (Storage::fileExists('public/'.$file->path)) {
            return Storage::download('public/'.$file->path);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ARQUIVO INEXISTENTE!',
                'timer'    => 5000,
            ]);

            return;
        }
    }

    public function deleteFile(EvidenceFile $file)
    {
        if ($file) {
            $file->delete();
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Arquivo removido com sucesso!',
            ]);
            $this->emit('refreshComponent');
        }
    }

    public function hasEvidence(bool $has)
    {
        $this->hasEvidence = $has;
    }

    public function finishD5()
    {
        $this->validate();

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'ENCERRAR D5',
            'msg'           => "Você tem certeza que deseja encerrar o D5 {$this->five->note_d5}?",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Continue!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'samuca158012Encerrar',
            // 'chave'         => '',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma D5 foi encerrada.',
        ]);

    }

    public function toSave(): void
    {
        $this->emitTo('files.evidence.upload-evidence', 'saveEvidences');
    }

    public function evidenceSaved()
    {
        $this->finish();
    }


    public function finish()
    {
        DB::beginTransaction();

        try {
            $this->five->is_completed = true;
            $this->five->completed_at = now();
            $this->five->save();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'OPERAÇÃO CONCLUIDA',
                'html'     => 'A operação de encerramento do D5 foi concluída com sucesso.',
                'timer'    => 5000,
            ]);

            DB::commit();

            $this->clearAll();

        } catch (\Throwable $th) {
            DB::rollBack();

            if ($files = $this->five->EvidenceFiles()->where('origin', $this->origin)->get()) {
                foreach ($files as $f) {
                    $f->delete();
                }
            }

            $this->dispatchBrowserEvent('swal', [
                 'position' => 'center',
                 'icon'     => 'error',
                 'title'    => 'OPERAÇÃO FALHOU',
                 'html'     => 'A operação de encerramento do D5 falhou.',
                 'timer'    => 5000,
             ]);
        }
    }

    public function clearAll()
    {
        $this->five = null;
        $this->trackingMeta = [];
        $this->eventTimeline = [];
        $this->emitTo('files.evidence.upload-evidence', 'cancelEvidences');
        $this->resetErrorBag();
        $this->dispatchBrowserEvent('hideModal');
        $this->emitUp('refresh_component');

    }

    protected function buildEventTimeline(FiveNote $fiveNote): array
    {
        $events = $fiveNote->timelineEvents ?? collect();
        if ($events->isEmpty()) {
            return [];
        }

        return $events->map(function ($event) {
            return [
                'id' => $event->id,
                'when' => $event->occurred_at,
                'event' => $this->eventLabel((string) $event->event_type),
                'stage' => $this->stageLabel((string) ($event->to_stage ?? '')),
                'owner' => $event->owner?->name,
                'actor' => $event->actor?->name,
                'inferred' => (bool) $event->inferred,
                'icon' => $this->eventIcon((string) $event->event_type),
            ];
        })->all();
    }

    protected function eventIcon(string $eventType): string
    {
        return match ($eventType) {
            'd5_created' => 'ri-add-circle-line',
            'd5_created_from_supervision' => 'ri-inbox-archive-line',
            'd5_payment_updated' => 'ri-bank-card-line',
            'd5_released_to_partner' => 'ri-send-plane-2-line',
            'd5_partner_completed', 'd5_partner_recompleted' => 'ri-building-line',
            'd5_sent_to_supervision_queue', 'd5_supervision_assigned', 'd5_supervision_approved' => 'ri-shield-check-line',
            'd5_user_assigned' => 'ri-user-add-line',
            'd5_user_unassigned' => 'ri-user-unfollow-line',
            'd5_user_changed' => 'ri-user-settings-line',
            'd5_returned_with_pending' => 'ri-arrow-go-back-line',
            'd5_sent_to_payment_archive' => 'ri-folder-transfer-line',
            'd5_archived' => 'ri-checkbox-circle-line',
            default => 'ri-time-line',
        };
    }

    protected function eventLabel(string $eventType): string
    {
        return match ($eventType) {
            'd5_created' => 'D5 criada',
            'd5_created_from_supervision' => 'D5 Solicitada',
            'd5_payment_updated' => 'Pagamento atualizou',
            'd5_released_to_partner' => 'Liberada para empreiteira',
            'd5_partner_completed' => 'Empreiteira concluiu',
            'd5_partner_recompleted' => 'Empreiteira concluiu novamente',
            'd5_sent_to_supervision_queue' => 'Enviada para fila da fiscalização',
            'd5_supervision_assigned' => 'Fiscalização atribuída',
            'd5_user_assigned' => 'Usuário atribuído',
            'd5_user_unassigned' => 'Usuário desatribuído',
            'd5_user_changed' => 'Usuário alterado',
            'd5_returned_with_pending' => 'Retornada com pendência',
            'd5_supervision_approved' => 'Fiscalização aprovou',
            'd5_sent_to_payment_archive' => 'Enviada para arquivamento',
            'd5_archived' => 'D5 arquivada',
            default => $eventType,
        };
    }

    protected function stageLabel(string $stage): string
    {
        return match ($stage) {
            'created' => 'Criada',
            'payment_review' => 'Em pagamento',
            'released_to_partner' => 'Com empreiteira',
            'partner_done' => 'Concluída pela empreiteira',
            'supervision_queue' => 'Fila fiscalização',
            'supervision_assigned' => 'Fiscal atribuído',
            'returned_to_partner' => 'Devolvida à empreiteira',
            'supervision_approved' => 'Aprovada na fiscalização',
            'payment_archive_queue' => 'Fila arquivamento',
            'archived' => 'Arquivada',
            default => $stage ?: '---',
        };
    }

    protected function buildTrackingMeta(FiveNote $fiveNote): array
    {
        $activity = $this->resolveActivity($fiveNote);
        $assignee = $this->resolveAssignee($fiveNote, $activity['key']);

        $timeline = [
            [
                'label' => 'Despacho',
                'at' => $fiveNote->dispatch_at,
                'wait_days' => $this->waitDays($fiveNote->dispatch_at, $fiveNote->completed_at),
                'icon' => 'ri-send-plane-2-line',
            ],
            [
                'label' => 'Retorno empreiteira',
                'at' => $fiveNote->completed_at,
                'wait_days' => $this->waitDays($fiveNote->completed_at, $fiveNote->supervisioned_at),
                'icon' => 'ri-building-line',
            ],
            [
                'label' => 'Fiscalizacao',
                'at' => $fiveNote->supervisioned_at,
                'wait_days' => $this->waitDays($fiveNote->supervisioned_at, $fiveNote->payed_at),
                'icon' => 'ri-shield-check-line',
            ],
            [
                'label' => 'Pagamento',
                'at' => $fiveNote->payed_at,
                'wait_days' => $this->waitDays(
                    $fiveNote->payed_at,
                    $fiveNote->is_archived ? ($fiveNote->updated_at ?? now()) : null
                ),
                'icon' => 'ri-bank-card-line',
            ],
            [
                'label' => 'Finalizado',
                'at' => $fiveNote->is_archived ? ($fiveNote->updated_at ?? now()) : null,
                'wait_days' => null,
                'icon' => 'ri-checkbox-circle-line',
            ],
        ];

        return [
            'activity' => $activity,
            'assignee' => $assignee,
            'timeline' => $timeline,
        ];
    }

    protected function resolveActivity(FiveNote $fiveNote): array
    {
        if ($fiveNote->is_archived) {
            return ['key' => 'finalizado', 'label' => 'Finalizado', 'color' => 'text-bg-success'];
        }

        if ((is_null($fiveNote->note_d5) || trim((string) $fiveNote->note_d5) === '') && !$fiveNote->visible_partner) {
            return ['key' => 'aguardando_geracao_d5', 'label' => 'Aguardando Geracao de D5', 'color' => 'text-bg-secondary'];
        }

        if ($fiveNote->is_supervisioned) {
            return ['key' => 'aguardando_pagamento', 'label' => 'Aguardando Pagamento', 'color' => 'text-bg-primary'];
        }

        if ($fiveNote->is_completed) {
            return ['key' => 'aguardando_fiscalizacao', 'label' => 'Aguardando Fiscalizacao', 'color' => 'text-bg-warning'];
        }

        return ['key' => 'aguardando_fornecedor', 'label' => 'Aguardando Fornecedor', 'color' => 'text-bg-danger'];
    }

    protected function resolveAssignee(FiveNote $fiveNote, string $activityKey): array
    {
        if (!in_array($activityKey, ['aguardando_fiscalizacao', 'aguardando_pagamento', 'aguardando_geracao_d5'], true)) {
            return ['name' => null, 'company' => null, 'has_assignee' => false];
        }

        $targetServiceId = $activityKey === 'aguardando_fiscalizacao'
            ? $this->fiscalizationServiceId
            : $this->paymentServiceId;

        if (!$targetServiceId) {
            return ['name' => null, 'company' => null, 'has_assignee' => false];
        }

        $productions = $fiveNote->note?->Productions ?? collect();
        $partnerReturnAt = $fiveNote->completed_at;
        $strictPartnerWindow = $activityKey === 'aguardando_fiscalizacao' && (bool) $partnerReturnAt;

        $openForService = $productions
            ->where('service_id', $targetServiceId)
            ->where('completed', false);

        if ($partnerReturnAt) {
            $openForService = $openForService->filter(function ($production) use ($partnerReturnAt) {
                $mark = $production->att_at ?: $production->created_at;
                return $mark && $mark->greaterThanOrEqualTo($partnerReturnAt);
            });
        }

        $candidate = $openForService
            ->sortByDesc(function ($production) {
                return $production->att_at ?: $production->created_at;
            })
            ->first();

        if (!$candidate) {
            $candidate = $productions
                ->where('service_id', $targetServiceId)
                ->where('completed', false)
                ->sortByDesc(function ($production) {
                    return $production->att_at ?: $production->created_at;
                })
                ->first();
        }

        if ($strictPartnerWindow && !$candidate) {
            return ['name' => null, 'company' => null, 'has_assignee' => false];
        }

        return [
            'name' => $candidate?->User?->name,
            'company' => $candidate?->Company?->name,
            'has_assignee' => (bool) $candidate?->user_id,
        ];
    }

    protected function waitDays($from, $to): ?int
    {
        if (!$from) {
            return null;
        }

        $start = $from instanceof Carbon ? $from : Carbon::parse($from);
        $end = $to ? ($to instanceof Carbon ? $to : Carbon::parse($to)) : now();

        return $start->diffInDays($end);
    }

    protected function resolveServiceIds(): void
    {
        if ($this->fiscalizationServiceId && $this->paymentServiceId) {
            return;
        }

        $this->fiscalizationServiceId = Service::whereIn('service', ['Fiscalizacao', 'Fiscalização'])->value('uuid');
        $this->paymentServiceId = Service::where('service', 'Pagamento')->value('uuid');
    }

    public function render()
    {
        return view('livewire.components.five-note.view-d5');
    }
}
