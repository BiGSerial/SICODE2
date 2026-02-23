<?php

namespace App\Http\Livewire\Reports;

use App\Jobs\Reports\ExportReturnWorkReportsJob;
use App\Models\Company;
use App\Models\ReturnWork;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReturnWorkReports extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public int $perPage = 50;
    public ?string $dt_in = null;
    public ?string $dt_out = null;
    public ?string $search = null;

    /** @var array<int> */
    public array $companyIds = [];

    /** @var array<string> */
    public array $serviceIds = [];

    /** @var array<string> */
    public array $categoryValues = [];

    public $companies;
    public $services;
    public $categories;

    protected $queryString = [
        'dt_in' => ['except' => '', 'as' => 'dtin'],
        'dt_out' => ['except' => '', 'as' => 'dtout'],
        'search' => ['except' => '', 'as' => 'q'],
        'companyIds' => ['except' => [], 'as' => 'company'],
        'serviceIds' => ['except' => [], 'as' => 'service'],
        'categoryValues' => ['except' => [], 'as' => 'category'],
        'perPage' => ['except' => 50, 'as' => 'pp'],
    ];

    public function mount(): void
    {
        $this->dt_in = $this->dt_in ?: now()->startOfMonth()->format('Y-m-d');
        $this->dt_out = $this->dt_out ?: now()->format('Y-m-d');

        if ($this->dt_out && Carbon::parse($this->dt_out)->greaterThan(now())) {
            $this->dt_out = now()->format('Y-m-d');
        }

        $this->companies = Company::query()
            ->join('work_reports as wr', 'wr.company_id', '=', 'companies.id')
            ->join('return_works as rw', 'rw.work_report_id', '=', 'wr.id')
            ->select('companies.id', 'companies.name')
            ->distinct()
            ->orderBy('companies.name')
            ->get();
        $this->services = DB::table('return_works as rw')
            ->leftJoin('services as s', 's.uuid', '=', 'rw.service_id')
            ->selectRaw('rw.service_id as uuid, COALESCE(NULLIF(s.service, ""), CONCAT("Serviço removido (", rw.service_id, ")")) as service')
            ->whereNotNull('rw.service_id')
            ->distinct()
            ->orderBy('service')
            ->get();

        $this->categories = ReturnWork::query()
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    public function updating($name): void
    {
        if ($name !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'companyIds',
            'serviceIds',
            'categoryValues',
        ]);

        $this->dt_in = now()->startOfMonth()->format('Y-m-d');
        $this->dt_out = now()->format('Y-m-d');
        $this->perPage = 50;
        $this->resetPage();
    }

    public function exportToExcel(): void
    {
        $params = [
            'dt_in' => $this->dt_in,
            'dt_out' => $this->dt_out,
            'search' => $this->search,
            'companyIds' => $this->companyIds,
            'serviceIds' => $this->serviceIds,
            'categoryValues' => $this->categoryValues,
        ];

        ExportReturnWorkReportsJob::dispatch($params, (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Exportação iniciada',
            'html' => "<div class='card'><div class='card-body'>
                <p>Seu arquivo está sendo gerado.</p>
                <p class='mb-0'><strong>Quando concluir, o link aparecerá na sua Central de Notificações.</strong></p>
            </div></div>",
            'timer' => 5000,
        ]);
    }

    public function getRowsProperty()
    {
        $start = Carbon::parse($this->dt_in ?: now()->startOfMonth())->startOfDay();
        $end = Carbon::parse($this->dt_out ?: now())->endOfDay();

        if ($end->greaterThan(now())) {
            $end = now()->endOfDay();
        }

        return ReturnWork::query()
            ->with([
                'Service:uuid,service',
                'User:id,name',
                'Workreport:id,note_id,company_id,user_id,informer,created_at',
                'Workreport.Note:id,note',
                'Workreport.Company:id,name',
                'Workreport.User:id,name,company_id',
                'Workreport.User.Company:id,name',
                'Workreport.User.Employee:id,user_id,contract_id',
                'Workreport.User.Employee.Contract:id,company_id',
                'Workreport.User.Employee.Contract.company:id,name',
            ])
            ->whereBetween('created_at', [$start, $end])
            ->when(!empty($this->categoryValues), fn ($q) => $q->whereIn('category', $this->categoryValues))
            ->when(!empty($this->serviceIds), fn ($q) => $q->whereIn('service_id', $this->serviceIds))
            ->when(!empty($this->companyIds), function ($q) {
                $q->whereHas('Workreport', function ($wr) {
                    $wr->whereIn('company_id', $this->companyIds);
                });
            })
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($sq) use ($term) {
                    $sq->whereHas('Workreport.Note', fn ($n) => $n->where('note', 'like', $term))
                        ->orWhereHas('Workreport.Orders', fn ($o) => $o->where('ordem', 'like', $term));
                });
            })
            ->orderByDesc('created_at');
    }

    public function render()
    {
        return view('livewire.reports.return-work-reports', [
            'rows' => $this->rows->paginate($this->perPage),
        ]);
    }
}
