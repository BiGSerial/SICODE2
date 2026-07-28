<?php

namespace App\Http\Livewire\Engineers;

use App\Jobs\Engineers\ExportPartialHistoryJob;
use App\Services\Engineers\PartialHistoryService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class PartialHist extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search;
    public $bulk_search;
    public $company_id;
    public $status;
    public $perPage = 100;
    public $selectedRow;


    public $dt_in;
    public $dt_out;
    public $month;

    // Filters
    private $filter_group = 'partial';
    private $filters;

    protected $queryString = [
        'search' => ['except' => ''],
        'company_id' => ['except' => '', 'as' => 'company'],
        'status' => ['except' => ''],
        'dt_in' => ['except' => '', 'as' => 'in'],
        'dt_out' => ['except' => '', 'as' => 'out'],
    ];

    protected $listeners = [
        'refresh' => '$refresh',
        'refresh_list' => '$refresh'
    ];

    public function pesquisar()
    {
        $this->resetPage();
    }

    public function applyBulkSearch()
    {
        $this->bulk_search = $this->normalizeBulkSearch($this->bulk_search);
        $this->resetPage();
        $this->dispatchBrowserEvent('hide-bulk-search-modal');
    }

    public function clearBulkSearch()
    {
        $this->bulk_search = '';
        $this->resetPage();
    }

    public function clearLocalFilters()
    {
        $this->reset(['search', 'bulk_search', 'company_id', 'status', 'dt_in', 'dt_out', 'month']);
        $this->resetPage();
    }

    public function updatedCompanyId()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedMonth()
    {
        $this->dt_in = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
        $this->dt_out = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');

        $this->resetPage();
    }


    public function getListsProperty()
    {
        return app(PartialHistoryService::class)
            ->query($this->currentFilters())
            ->paginate($this->perPage);
    }

    /**
     * @return array<string,mixed>
     */
    private function currentFilters(): array
    {

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filters = $_SESSION['filter'][$this->filter_group];
        }

        return [
            'search' => $this->search,
            'bulk_search' => $this->bulk_search,
            'company_id' => $this->company_id,
            'status' => $this->status,
            'dt_in' => $this->dt_in,
            'dt_out' => $this->dt_out,
            'rubrica' => $this->filters['rubrica'] ?? '',
        ];
    }

    public function exportToExcel()
    {
        ExportPartialHistoryJob::dispatch($this->currentFilters(), auth()->id());

        $this->dispatchBrowserEvent('notify', [
            'type' => 'success',
            'message' => 'Exportação enviada para a fila. Você receberá uma notificação quando o arquivo estiver pronto.',
        ]);
    }

    public function partialStatus($partial): array
    {
        $status = [
            'status' => '',
            'color' => '',
        ];

        if ($partial) {
            if ($partial->deny) {
                $status = [
                    'status' => 'REJEITADO',
                    'color' => 'text-bg-danger',
                ];
            } elseif ($partial->payment && $partial->allow) {
                $status = [
                    'status' => 'PAGO',
                    'color' => 'text-bg-success',
                ];
            } elseif ($partial->supervision && !$partial->payment) {
                $status = [
                    'status' => 'EM PAGAMENTO',
                    'color' => 'text-bg-info',
                ];
            } elseif ($partial->allow && !$partial->supervision) {
                $status = [
                    'status' => 'EM FISCALIZAÇÃO',
                    'color' => 'text-bg-info',
                ];
            } else {
                $status = [
                    'status' => 'AVALIAÇÃO',
                    'color' => 'text-bg-warning',
                ];
            }
        }

        return $status;
    }

    public function rejectedStage($partial): ?string
    {
        if (!$partial?->deny) {
            return null;
        }

        if ($partial->payment || $partial->payment_at) {
            return 'payment';
        }

        if ($partial->supervision || $partial->supervision_at) {
            return 'supervision';
        }

        return 'approval';
    }

    public function rejectedStageInfo($partial, string $stage): array
    {
        if ($this->rejectedStage($partial) !== $stage) {
            return [
                'active' => false,
                'date' => null,
                'user' => null,
            ];
        }

        $date = match ($stage) {
            'approval' => $partial->decision_at,
            'supervision' => $partial->supervision_at,
            'payment' => $partial->payment_at ?: $partial->supervision_at,
            default => null,
        } ?: ($partial->complete ? $partial->updated_at : null) ?: $partial->updated_at;

        $user = match ($stage) {
            'approval' => $partial->engineer,
            'supervision' => $partial->supervisor,
            'payment' => $partial->payer,
            default => null,
        };

        return [
            'active' => true,
            'date' => $date,
            'user' => $user?->name,
        ];
    }

    private function normalizeBulkSearch(?string $value): string
    {
        return collect(preg_split('/[\s,;]+/', (string) $value) ?: [])
            ->map(fn($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->implode(PHP_EOL);
    }

    public function getBulkSearchCountProperty(): int
    {
        return collect(preg_split('/[\s,;]+/', trim((string) $this->bulk_search)) ?: [])
            ->filter()
            ->count();
    }

    public function render()
    {
        $service = app(PartialHistoryService::class);

        return view('livewire.engineers.partial-hist', [
            'lists' => $this->lists,
            'companies' => $service->companyOptions(),
            'statuses' => [
                PartialHistoryService::STATUS_SUPERVISION => 'EM FISCALIZAÇÃO',
                PartialHistoryService::STATUS_PAYMENT => 'EM PAGAMENTO',
                PartialHistoryService::STATUS_PAID => 'PAGO',
                PartialHistoryService::STATUS_REJECTED => 'REJEITADO',
            ],
            'bulkSearchCount' => $this->bulkSearchCount,
        ]);
    }
}
