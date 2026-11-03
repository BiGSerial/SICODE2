<?php

namespace App\Http\Livewire\Engineers\Ads;

use App\Http\Livewire\Concerns\WithListFilters;
use App\Jobs\Engineers\ExportAdsSituationJob;
use App\Models\AdsNonWorkingDayAdjustment;
use App\Models\{Company, WorkReport};
use App\Services\Ads\AdsDeadlinePolicy;
use App\Services\Engineers\AdsSituationService;
use Livewire\{Component, WithPagination};

class Status extends Component
{
    use WithListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    private const SCOPES = ['atual', 'passivo'];

    public int $perPage = 25;

    public string $statusFilter = 'atual';

    public string $detailStatusFilter = 'all';

    public ?string $date_in = null;

    public ?string $date_out = null;

    public ?string $search = null;

    public array $rowFineData = [];

    /** @var array<int, string> ids dos informes marcados (checkboxes `data-ops-row`). */
    public array $selected = [];

    public ?string $adjustmentDate = null;

    public ?string $adjustmentReason = null;

    protected $queryString = [
        'statusFilter'       => ['except' => 'atual', 'as' => 'status'],
        'detailStatusFilter' => ['except' => 'all', 'as' => 'detail'],
        'date_in'            => ['except' => '', 'as' => 'din'],
        'date_out'           => ['except' => '', 'as' => 'dout'],
        'search'             => ['except' => '', 'as' => 'q'],
        'filters'            => ['except' => [], 'as' => 'f'],
        'perPage'            => ['except' => 25, 'as' => 'pp'],
    ];

    public function mount(): void
    {
        $this->date_in  = $this->date_in ?: now()->startOfMonth()->format('Y-m-d');
        $this->date_out = $this->date_out ?: now()->format('Y-m-d');

        // Links antigos usavam `disabled` (tela vazia até escolher): hoje o padrão é o escopo Atual.
        if (!in_array($this->statusFilter, self::SCOPES, true)) {
            $this->statusFilter = 'atual';
        }

        $this->filters = $this->normalizeFilters($this->filters);
    }

    protected function listFilterDefinitions(): array
    {
        return [
            'company' => ['label' => 'Empreiteira'],
        ];
    }

    protected function listFilterOptions(): array
    {
        return [
            'company' => $this->rememberFilterOptions('company', fn () => Company::query()
                ->whereIn('id', WorkReport::query()->where('rejected', false)->select('company_id'))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($company) => ['v' => (string) $company->id, 'l' => (string) $company->name])
                ->all()),
        ];
    }

    /** Qualquer critério novo volta à página 1 e zera a seleção; digitar o abono (modal) não conta. */
    public function updated($name): void
    {
        if (in_array($name, ['statusFilter', 'detailStatusFilter', 'date_in', 'date_out', 'search', 'perPage'], true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function updatedStatusFilter(): void
    {
        if (!in_array($this->statusFilter, self::SCOPES, true)) {
            $this->statusFilter = 'atual';
        }

        if ($this->statusFilter !== 'atual') {
            $this->detailStatusFilter = 'all';
        }
    }

    public function hasCriteria(): bool
    {
        return $this->hasListFilters() || filled($this->search) || $this->detailStatusFilter !== 'all';
    }

    public function clearAllCriteria(): void
    {
        $this->detailStatusFilter = 'all';
        $this->search             = null;
        $this->date_in            = now()->startOfMonth()->format('Y-m-d');
        $this->date_out           = now()->format('Y-m-d');
        $this->clearListFilters();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function getRowsProperty()
    {
        return app(AdsSituationService::class)
            ->paginate($this->filters(), $this->perPage);
    }

    public function refreshFine(int $workReportId): void
    {
        $result = app(AdsSituationService::class)->refreshSingleWorkReportFine($workReportId);

        if (!$result) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Registro não encontrado.',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->rowFineData[$workReportId] = $result;
    }

    public function addNonWorkingDayAdjustment(): void
    {
        $ids = collect($this->selected)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Selecione pelo menos um informe.',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->validate([
            'adjustmentDate'   => ['required', 'date'],
            'adjustmentReason' => ['required', 'string', 'min:10'],
        ], [
            'adjustmentDate.required'   => 'Informe a data abonada.',
            'adjustmentReason.required' => 'Informe a justificativa.',
            'adjustmentReason.min'      => 'A justificativa deve ter pelo menos 10 caracteres.',
        ]);

        foreach ($ids as $workReportId) {
            AdsNonWorkingDayAdjustment::query()->updateOrCreate(
                [
                    'work_report_id' => $workReportId,
                    'date'           => $this->adjustmentDate,
                ],
                [
                    'reason'     => trim((string) $this->adjustmentReason),
                    'created_by' => auth()->id(),
                ]
            );

            if ($workReport = WorkReport::query()->find($workReportId)) {
                app(AdsDeadlinePolicy::class)->syncWorkReport($workReport);
            }
        }

        $this->rowFineData      = [];
        $this->selected         = [];
        $this->adjustmentDate   = null;
        $this->adjustmentReason = null;

        $this->dispatchBrowserEvent('ads-abono-done');
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Dia abonado como não útil para os registros selecionados.',
            'timer'    => 3000,
        ]);
    }

    public function setDetailStatusFilter(string $status): void
    {
        if ($this->statusFilter !== 'atual') {
            return;
        }

        $this->detailStatusFilter = $this->detailStatusFilter === $status ? 'all' : $status;
        $this->selected           = [];
        $this->resetPage();
    }

    public function exportReport(): void
    {
        ExportAdsSituationJob::dispatch($this->filters(), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Exportação iniciada',
            'html'     => "<div class='card'><div class='card-body'>
                <p>Seu arquivo está sendo gerado.</p>
                <p class='mb-0'><strong>Você será notificado quando o download estiver disponível.</strong></p>
            </div></div>",
            'timer' => 5000,
        ]);
    }

    /** Mesmos critérios da tela e do Excel (`ExportAdsSituationJob`). Datas invertidas são trocadas em vez de zerar a lista. */
    private function filters(): array
    {
        [$dateIn, $dateOut] = $this->date_in && $this->date_out && $this->date_in > $this->date_out
            ? [$this->date_out, $this->date_in]
            : [$this->date_in, $this->date_out];

        return [
            'statusFilter'       => $this->statusFilter,
            'detailStatusFilter' => $this->detailStatusFilter,
            'date_in'            => $dateIn,
            'date_out'           => $dateOut,
            'search'             => $this->search,
            'companyIds'         => $this->filterValues('company'),
        ];
    }

    public function render()
    {
        return view('livewire.engineers.ads.status', [
            'rows'    => $this->rows,
            'summary' => app(AdsSituationService::class)->summarize($this->filters()),
        ]);
    }
}
