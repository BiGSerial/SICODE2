<?php

namespace App\Http\Livewire\Protests\Dispatch\Actions;

use App\Models\MedProtest;
use App\Models\ProtestJob;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Enum\ProtestJobStatus;

class ViewProtestJob extends Component
{
    public ?string $jobId = null;

    // UI/UX state
    public int $tabIndex = 0;

    // Propriedade necessária para corrigir o erro "Public property [$messageTarget] not found"
    // Define o padrão como 'med' já que removemos a opção de 'job'
    public string $messageTarget = 'med';

    // Objetos carregados
    public ?ProtestJob $job = null;
    public $protest = null;
    public $medProtest = null;

    public ?string $result = null;
    public array $resultOptions = [];
    public bool $showConfirmCard = false;

    // Exibição
    public array $outcome = [];
    public array $timeline = [];
    public array $commentsByOrigin = [
        'job'     => [],
        'med'     => [],
        'protest' => [],
    ];

    // Envio de mensagem
    public string $newMessage = '';
    public bool   $restrict = false;

    // Confirmações (SweetAlert)
    protected $listeners = [
        'refresh'            => '$refresh',
        'open'               => 'open',
        'confirmEscalate'    => 'doEscalate',
        'confirmReopen'      => 'doReopen',
        'confirmCancel'      => 'doCancel',
        'confirmConfirm'     => 'doConfirm',
    ];

    /** Abre e popula o modal deste componente */
    public function open(string $jobId): void
    {
        $this->resetState();
        $this->jobId = $jobId;

        $this->job = ProtestJob::query()
            ->with([
                'protest.City',
                'protest.Comments.User',
                'protest.Notes',
                'medProtest.Comments.User',
                'medProtest.EvidenceFiles',
                'medProtest.Notes',
                'creator',
                'owner',
                'closer',
                'events.actor',
                'Comments.User',
            ])
            ->findOrFail($jobId);

        $this->protest    = $this->job->protest;
        $this->medProtest = $this->job->medProtest;
        $this->outcome    = $this->job->outcome ?? [];

        $this->result = $this->medProtest?->result;
        $this->resultOptions = MedProtest::resultOptions();

        // Listas por origem (desc)
        // 'job' deixado vazio propositalmente para não exibir mensagens do ProtestJob na aba, conforme solicitado
        $this->commentsByOrigin['job']     = [];
        $this->commentsByOrigin['med']     = $this->medProtest?->Comments?->sortByDesc('created_at')->values()->toArray() ?? [];
        $this->commentsByOrigin['protest'] = $this->protest?->Comments?->sortByDesc('created_at')->values()->toArray() ?? [];

        $this->timeline = $this->buildTimeline();

        // Mostrar modal (Bridge no Blade)
        $this->dispatchBrowserEvent('protestjob-view:show');
    }

    public function close(): void
    {
        $this->dispatchBrowserEvent('protestjob-view:hide');
        $this->resetState();
    }

    public function sendMessage(): void
    {
        $this->validate([
            'newMessage'    => 'required|string|min:2',
        ], [], ['newMessage' => 'mensagem']);

        // Trava de segurança: garante que só envia se houver MedProtest
        if (!$this->medProtest) {
            $this->dispatchBrowserEvent('toast', ['type' => 'warning', 'msg' => 'Este ProtestJob não possui MedProtest associada.']);
            return;
        }

        try {
            // Cria o comentário diretamente na MedProtest
            $this->medProtest->Comments()->create([
                'user_id'   => optional(auth()->user())->id,
                'message'   => $this->newMessage,
                'restrict'  => $this->restrict,
                'granted'   => false,
                'dismissed' => false,
            ]);

            // Recarregar os dados para atualizar a lista
            $this->open($this->jobId);

            $this->newMessage = '';
            $this->restrict   = false;

            $this->dispatchBrowserEvent('toast', ['type' => 'success', 'msg' => 'Mensagem enviada.']);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('toast', ['type' => 'error', 'msg' => 'Erro ao enviar: ' . $e->getMessage()]);
        }
    }

    /* ===================== CONFIRMAÇÕES (SweetAlert) ===================== */

    public function askEscalate(): void
    {
        if (!$this->job) {
            return;
        }

        $level = ($this->job->escalation_level ?? 0) + 1;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Reescalar atividade?',
            'msg'           => "Isso marcará o job como escalonado (Nível {$level}) e registrará o evento.",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Reescalar!',
            'btnCanceltxt'  => 'Não, Cancelar',
            'action'        => 'confirmEscalate',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma alteração realizada.',
        ]);
    }

    public function askReopen(): void
    {
        if (!$this->job) {
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Reabrir atividade?',
            'msg'           => 'Isso irá reabrir o ProtestJob, removendo carimbos de fechamento quando aplicável.',
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Reabrir!',
            'btnCanceltxt'  => 'Não, Cancelar',
            'action'        => 'confirmReopen',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma alteração realizada.',
        ]);
    }

    public function askConfirm(): void
    {
        if (!$this->job || $this->job->status->value !== 'done' || $this->job->confirmed) {
            return;
        }

        $this->result = MedProtest::normalizeResult($this->result ?? $this->medProtest?->result);
        $this->showConfirmCard = true;
        $this->resetErrorBag('result');
    }

    public function cancelConfirmCard(): void
    {
        $this->showConfirmCard = false;
        $this->resetErrorBag('result');
    }

    public function askCancel(): void
    {
        if (!$this->job) {
            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Cancelar atividade?',
            'msg'           => 'Isso irá cancelar o ProtestJob. Esta ação será registrada na linha do tempo.',
            'icon'          => 'error',
            'btnOktxt'      => 'Sim, Cancelar!',
            'btnCanceltxt'  => 'Não, Voltar',
            'action'        => 'confirmCancel',
            'cancel_titulo' => 'Operação abortada',
            'cancel_msg'    => 'Nenhuma mudança realizada.',
        ]);
    }

    /* ===================== AÇÕES CONFIRMADAS ===================== */

    public function doEscalate(): void
    {
        if (!$this->job) {
            return;
        }

        try {
            DB::transaction(function () {
                $newLevel = ($this->job->escalation_level ?? 0) + 1;

                $this->job->update([
                    'escalated_at'    => now(),
                    'escalation_level' => $newLevel,
                ]);

                // Evento específico de escalonamento
                $this->job->events()->create([
                    'type'        => 'escalated',
                    'actor_id'    => optional(auth()->user())->id,
                    'meta'        => ['level' => $newLevel],
                    'occurred_at' => now(),
                ]);
            });

            $this->open($this->jobId);
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Atividade reescalonada com sucesso!',
            ]);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'error',
                'menssage' => 'Falha ao reescalar: ' . $e->getMessage(),
            ]);
        }
    }

    public function doReopen(): void
    {
        if (!$this->job) {
            return;
        }

        try {
            // Usa método do modelo
            $this->job->reopen('Reaberto via Controlador por ' . Auth()->user()->name);
            $this->open($this->jobId);

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Atividade reaberta.',
            ]);

            $this->emitUp('refresh');
            $this->emitSelf('refresh');

        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Falha ao reabrir: ' . $e->getMessage(),
            ]);
        }
    }

    public function doConfirm(?string $result = null): void
    {
        if (!$this->job) {
            return;
        }

        try {
            // Usa metodo do modelo
            if ($result !== null) {
                $this->result = $result;
            }

            $this->validate([
                'result' => 'required|in:' . implode(',', MedProtest::resultOptions()),
            ]);

            $this->job->confirmJob(null, $this->result);
            $this->showConfirmCard = false;
            $this->open($this->jobId);

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Atividade confirmada.',
            ]);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Falha ao confirmar: ' . $e->getMessage(),
            ]);
        }
    }

    public function doCancel(): void
    {
        if (!$this->job) {
            return;
        }

        try {
            // Usa método do modelo
            $this->job->cancel('Cancelado via modal');
            $this->open($this->jobId);

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Atividade cancelada.',
            ]);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Falha ao cancelar: ' . $e->getMessage(),
            ]);
        }
    }

    /* ===================== CÁLCULOS DE PERMISSÃO (getters) ===================== */

    public function getCanEscalateProperty(): bool
    {
        if (!$this->job) {
            return false;
        }
        if ($this->job->escalated_at) {
            return false;
        }

        $imminent = $this->job->sla_due_at && $this->job->sla_due_at->lessThanOrEqualTo(now()->addMinutes(30));
        return (bool)($this->job->sla_breached_at) || $imminent;
    }

    public function getCanReopenProperty(): bool
    {
        if (!$this->job) {
            return false;
        }

        // Pode reabrir se estiver encerrado (DONE) ou cancelado
        return in_array($this->job->status, [
            ProtestJobStatus::DONE,
            ProtestJobStatus::CANCELED,
        ], true);
    }

    public function getCanCancelProperty(): bool
    {
        if (!$this->job) {
            return false;
        }

        return in_array($this->job->status, [
            ProtestJobStatus::OPENED,
            ProtestJobStatus::ASSIGNED,
            ProtestJobStatus::IN_PROGRESS,
            ProtestJobStatus::WAITING,
            ProtestJobStatus::REOPENED,
        ], true);
    }

    /* ===================== TIMELINE ===================== */

    protected function humanStatus(?string $val): string
    {
        if (!$val) {
            return '—';
        }
        return ucfirst(str_replace('_', ' ', strtolower($val)));
    }

    protected function mapEventToCard(\App\Models\ProtestJobEvent $e): array
    {
        $type = $e->type;
        $meta = $e->meta ?? [];
        $actor = $e->actor?->name ?? 'Sistema';
        $when  = ($e->occurred_at ?? $e->created_at)?->format('d/m/Y H:i');

        // defaults
        $card = [
            'variant'  => 'secondary',
            'icon'     => 'bi-clock-history',
            'title'    => ucfirst(str_replace('_', ' ', $type)),
            'subtitle' => "por {$actor} • {$when}",
            'chips'    => [],
            'lines'    => [],
            'raw'      => $meta,
        ];

        switch ($type) {
            case 'status_changed':
                $from = $this->humanStatus($meta['from'] ?? null);
                $to   = $this->humanStatus($meta['to']   ?? null);
                $card['variant'] = 'primary';
                $card['icon']    = 'bi-arrow-right-circle-fill';
                $card['title']   = 'Status alterado';
                $card['chips'][] = $from;
                $card['chips'][] = '→';
                $card['chips'][] = $to;
                break;

            case 'updated':
                $card['variant'] = 'info';
                $card['icon']    = 'bi-pencil-square';
                $card['title']   = 'Atualização';
                if (!empty($meta['changes'])) {
                    $parts = is_string($meta['changes']) ? explode('|', $meta['changes']) : (array)$meta['changes'];
                    foreach ($parts as $p) {
                        $p = trim($p);
                        if ($p !== '') {
                            $card['chips'][] = $p;
                        }
                    }
                }
                break;

            case 'reassigned':
                $card['variant'] = 'warning';
                $card['icon']    = 'bi-person-arrows';
                $card['title']   = 'Reatribuído';
                $card['lines'][] = ['label' => 'De',   'value' => $meta['from_owner'] ?? '—'];
                $card['lines'][] = ['label' => 'Para', 'value' => $meta['to_owner']   ?? '—'];
                break;

            case 'sla_warning':
                $card['variant'] = 'warning';
                $card['icon']    = 'bi-exclamation-triangle-fill';
                $card['title']   = 'Alerta de SLA';
                if (!empty($meta['code'])) {
                    $card['chips'][] = "code: {$meta['code']}";
                }
                if (!empty($meta['reason'])) {
                    $card['lines'][] = $meta['reason'];
                }
                break;

            case 'sla_breached':
                $card['variant'] = 'danger';
                $card['icon']    = 'bi-alarm-exclamation';
                $card['title']   = 'SLA Estourado';
                if (!empty($meta['reason'])) {
                    $card['lines'][] = $meta['reason'];
                }
                break;

            case 'escalated':
                $lvl = $meta['level'] ?? null;
                $card['variant'] = 'danger';
                $card['icon']    = 'bi-arrow-up-right-circle-fill';
                $card['title']   = 'Atividade reescalonada';
                if ($lvl) {
                    $card['chips'][] = "Nível {$lvl}";
                }
                break;

            default:
                // genérico
                break;
        }

        return $card;
    }

    protected function buildTimeline(): array
    {
        $items = [];

        // Eventos -> cards
        foreach (
            $this->job->events()
                ->with('actor')
                ->orderByDesc('occurred_at')
                ->orderByDesc('created_at')
                ->get() as $e
        ) {
            $items[] = [
                'kind' => 'event',
                'card' => $this->mapEventToCard($e),
                'at'   => $e->occurred_at ?? $e->created_at,
            ];
        }

        // Comentários
        $pushComments = function ($list, string $origin) use (&$items) {
            foreach ($list ?? [] as $c) {
                $items[] = [
                    'kind'     => 'comment',
                    'at'       => $c->created_at ?? now(),
                    'who'      => $c->user?->name ?? '—',
                    'text'     => $c->message ?? '',
                    'origin'   => $origin,
                    'restrict' => (bool) ($c->restrict ?? false),
                ];
            }
        };

        // Mantive no timeline histórico, mas pode ser removido se desejar limpar também a timeline.
        // Se quiser remover da timeline, comente a linha abaixo:
        $pushComments($this->job->Comments, 'ProtestJob');

        $pushComments($this->medProtest?->Comments, 'MedProtest');
        $pushComments($this->protest?->Comments, 'Protest');

        // Ordena por data
        usort($items, fn ($a, $b) => ($b['at'] <=> $a['at']));

        return $items;
    }

    protected function resetState(): void
    {
        $this->reset([
            'jobId','job','protest','medProtest',
            'outcome','timeline','commentsByOrigin',
            'newMessage','restrict',
            'result','resultOptions','showConfirmCard',
        ]);
        $this->restrict = false;
        $this->messageTarget = 'med';
    }

    public function render()
    {
        return view('livewire.protests.dispatch.actions.view-protest-job');
    }
}
