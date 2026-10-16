<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Enum\ClosureOperationalStatus as Status;
use App\Models\Closure\ClosurePriorityRequest;
use App\Models\{Company, Note, User};
use App\Services\Closure\Analytics\ClosureDispatcherAnalytics;
use App\Services\Closure\{ClosureOrderWorkflowException, ClosureOrderWorkflowService, ClosurePriorityException, ClosurePriorityService};
use Livewire\Component;

class Dashboard extends Component
{
    public const TYPE_CHART_ID = 'closure_dash_users';

    public const STATUS_CHART_ID = 'closure_dash_status';

    public string $filterType = '';

    public ?string $filterCompany = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public int $staleDays = 7;

    /** Célula clicada na matriz: user (id|'none'|null), type, status. */
    public array $cell = ['user' => null, 'type' => null, 'status' => null];

    public ?int $transferNoteId = null;

    public ?string $transferUserId = null;

    public string $transferReason = '';

    public array $transferReceipt = [];

    public ?string $feedbackMessage = null;

    public string $feedbackType = 'success';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['filterType', 'filterCompany', 'dateFrom', 'dateTo'], true)) {
            $this->clearCell();
        }
    }

    public function selectCell(?string $user, ?string $type, ?string $status): void
    {
        $this->cell = [
            'user'   => $user === '' ? null : $user,
            'type'   => $type === '' ? null : $type,
            'status' => $status === '' ? null : $status,
        ];
    }

    public function clearCell(): void
    {
        $this->cell = ['user' => null, 'type' => null, 'status' => null];
    }

    public function clearFilters(): void
    {
        $this->filterType    = '';
        $this->filterCompany = null;
        $this->dateFrom      = null;
        $this->dateTo        = null;
        $this->clearCell();
    }

    public function decidePriority(int $requestId, string $decision, ?string $decisionNote = null): void
    {
        $request = ClosurePriorityRequest::query()->find($requestId);

        if (!$request) {
            $this->feedback('Pedido de prioridade não encontrado.', 'warning');

            return;
        }

        try {
            $service = app(ClosurePriorityService::class);

            if ($decision === 'accept') {
                $service->accept($request, auth()->user(), $decisionNote);
                $this->feedback('Pedido aceito: a prioridade foi aplicada às Ordens abertas da obra.');
            } else {
                $service->decline($request, auth()->user(), $decisionNote);
                $this->feedback('Pedido recusado.');
            }
        } catch (ClosurePriorityException $e) {
            $this->feedback($e->getMessage(), 'warning');
        }
    }

    public function prioritizeNote(int $noteId, bool $priority = true): void
    {
        $note = Note::query()->find($noteId);

        if (!$note) {
            $this->feedback('Obra não encontrada.', 'warning');

            return;
        }

        try {
            $service = app(ClosurePriorityService::class);

            if (!$priority) {
                $count = $service->clear($note, auth()->user());
                $this->feedback("Prioridade removida de {$count} Ordem(ns) da obra.");

                return;
            }

            $pending = ClosurePriorityRequest::query()
                ->where('note_id', $note->id)
                ->where('status', ClosurePriorityRequest::STATUS_PENDING)
                ->first();

            if ($pending) {
                $service->accept($pending, auth()->user(), 'Priorizada diretamente pelo despachante.');
                $this->feedback('Obra priorizada (pedido pendente aceito).');

                return;
            }

            $request = ClosurePriorityRequest::query()->create([
                'note_id'      => $note->id,
                'requested_by' => auth()->id(),
                'reason'       => 'Priorizada diretamente pelo despachante.',
                'status'       => ClosurePriorityRequest::STATUS_PENDING,
            ]);

            $service->accept($request, auth()->user(), 'Priorizada diretamente pelo despachante.');
            $this->feedback('Obra priorizada: Ordens abertas marcadas com prioridade.');
        } catch (ClosurePriorityException $e) {
            $this->feedback($e->getMessage(), 'warning');
        }
    }

    public function openTransfer(int $noteId): void
    {
        $this->transferNoteId  = $noteId;
        $this->transferUserId  = null;
        $this->transferReason  = '';
        $this->transferReceipt = [];
    }

    public function cancelTransfer(): void
    {
        $this->transferNoteId = null;
    }

    public function transfer(): void
    {
        if (!$this->transferNoteId || !$this->transferUserId || trim($this->transferReason) === '') {
            $this->feedback('Informe o usuário de destino e o motivo da transferência.', 'warning');

            return;
        }

        $target = User::query()
            ->whereKey($this->transferUserId)
            ->where(fn ($q) => $q->where('closure_operator', true)->orWhere('closure_manager', true)->orWhere('admin', true)->orWhere('superadm', true))
            ->first();
        $company = $target?->company_id ? Company::query()->find($target->company_id) : null;

        if (!$target || !$company) {
            $this->feedback('O usuário de destino precisa ser operador do Encerramento e ter empresa.', 'warning');

            return;
        }

        $workflow = app(ClosureOrderWorkflowService::class);
        $batch    = (string) \Illuminate\Support\Str::uuid();
        $receipt  = ['note_id' => $this->transferNoteId, 'target' => $target->name, 'company' => $company->name, 'reason' => trim($this->transferReason), 'moved' => [], 'skipped' => [], 'at' => now()->format('d/m/Y H:i')];

        foreach ($this->analytics()->transferableStates($this->transferNoteId) as $state) {
            try {
                $workflow->transfer($state, $company, $target, auth()->user(), $this->transferReason, $batch);
                $receipt['moved'][] = $state->Order->ordem;
            } catch (ClosureOrderWorkflowException $e) {
                $receipt['skipped'][] = ['ordem' => $state->Order->ordem, 'reason' => $e->getMessage()];
            }
        }

        $this->transferReceipt = $receipt;
        $this->transferNoteId  = null;
        $this->transferReason  = '';
        $this->feedback(count($receipt['moved']) . ' Ordem(ns) transferida(s) para ' . $target->name . '.', $receipt['moved'] === [] ? 'warning' : 'success');
    }

    public function dismissReceipt(): void
    {
        $this->transferReceipt = [];
    }

    public function render()
    {
        $analytics = $this->analytics();
        $matrix    = $analytics->matrix($this->filters());
        $summary   = $analytics->summary($this->filters(), $matrix);
        $charts    = $this->charts($matrix);

        $this->dispatchChartUpdates($charts, $matrix['totals']['total']);

        return view('livewire.closure.dispatch.dashboard', [
            'matrix'        => $matrix,
            'summary'       => $summary,
            'statuses'      => ClosureDispatcherAnalytics::MATRIX_STATUSES,
            'interaction'   => $analytics->partnerInteraction(),
            'priorities'    => $analytics->pendingPriorityRequests(),
            'critical'      => $analytics->criticalWorks(max(1, $this->staleDays)),
            'orders'        => $analytics->orders($this->filters(), $this->cell),
            'charts'        => $charts,
            'companies'     => Company::query()->orderBy('name')->get(['id', 'name']),
            'transferUsers' => $this->transferNoteId
                ? User::query()->whereNotNull('company_id')
                    ->where(fn ($q) => $q->where('closure_operator', true)->orWhere('closure_manager', true))
                    ->orderBy('name')->get(['id', 'name'])
                : collect(),
            'transferStates' => $this->transferNoteId ? $analytics->transferableStates($this->transferNoteId) : collect(),
            'userChartId'    => self::TYPE_CHART_ID,
            'statusChartId'  => self::STATUS_CHART_ID,
        ]);
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return [
            'type'       => $this->filterType ?: null,
            'company_id' => $this->filterCompany ?: null,
            'from'       => $this->dateFrom ?: null,
            'to'         => $this->dateTo ?: null,
        ];
    }

    /**
     * Gráficos Chart.js. Quando não há dados o payload mantém datasets vazios na
     * renderização inicial e a view mostra um overlay; as atualizações via evento só
     * ocorrem quando há dados (o último estado do gráfico nunca é limpo).
     *
     * @param array{rows: array<int, array<string, mixed>>, totals: array<string, mixed>} $matrix
     * @return array{users: array<string, mixed>, status: array<string, mixed>}
     */
    private function charts(array $matrix): array
    {
        $rows   = collect($matrix['rows']);
        $colors = ['P' => '#E32C2C', 'M' => '#0F8A77', 'F' => '#263CC8', 'N' => '#94A3B8'];

        $datasets = [];

        foreach (['P' => 'Passivo', 'M' => 'Meta atual', 'F' => 'Meta futura', 'N' => 'Sem tipo'] as $type => $label) {
            $datasets[] = [
                'label'           => $label,
                'data'            => $rows->map(fn ($r) => $r['types'][$type])->all(),
                'backgroundColor' => $colors[$type],
                'borderColor'     => $colors[$type],
                'borderRadius'    => 4,
                'stack'           => 'tipo',
            ];
        }

        $users = [
            'type'    => 'bar',
            'data'    => ['labels' => $rows->pluck('name')->all(), 'datasets' => $datasets],
            'options' => [
                'indexAxis'           => 'y',
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => [
                    'legend'     => ['display' => true, 'position' => 'top', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]],
                    'datalabels' => ['display' => '__VALUE_LABEL_NONZERO__', 'color' => '#fff', 'font' => ['weight' => '700', 'size' => 10]],
                ],
                'scales' => [
                    'x' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0], 'grid' => ['color' => 'rgba(148, 163, 184, .18)']],
                    'y' => ['stacked' => true, 'grid' => ['display' => false]],
                ],
            ],
        ];

        $statusValues = array_map(fn ($s) => $matrix['totals']['status'][$s], ClosureDispatcherAnalytics::MATRIX_STATUSES);
        $palette      = ['#263CC8', '#A8B1E9', '#212E3E', '#F7D200', '#E32C2C', '#0F8A77', '#225E66', '#0CD3F8', '#91AFB3'];

        $status = [
            'type' => 'doughnut',
            'data' => [
                'labels'   => array_map(fn ($s) => Status::labelFor($s), ClosureDispatcherAnalytics::MATRIX_STATUSES),
                'datasets' => [['label' => 'Ordens', 'data' => $statusValues, 'backgroundColor' => $palette, 'borderColor' => '#fff', 'borderWidth' => 2]],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'cutout'              => '58%',
                'plugins'             => [
                    'legend'     => ['display' => true, 'position' => 'right', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]],
                    'datalabels' => ['display' => '__VALUE_LABEL_NONZERO__', 'color' => '#fff', 'font' => ['weight' => '700', 'size' => 10]],
                ],
            ],
        ];

        return ['users' => $users, 'status' => $status];
    }

    /** @param array{users: array<string, mixed>, status: array<string, mixed>} $charts */
    private function dispatchChartUpdates(array $charts, int $total): void
    {
        if ($total === 0) {
            return; // estado vazio: overlay na view; mantém o último estado do gráfico.
        }

        $this->dispatchBrowserEvent('grafico-atualizar-' . self::TYPE_CHART_ID, $charts['users']);
        $this->dispatchBrowserEvent('grafico-atualizar-' . self::STATUS_CHART_ID, $charts['status']);
    }

    private function analytics(): ClosureDispatcherAnalytics
    {
        return app(ClosureDispatcherAnalytics::class);
    }

    private function feedback(string $message, string $type = 'success'): void
    {
        $this->feedbackMessage = $message;
        $this->feedbackType    = $type;
    }
}
