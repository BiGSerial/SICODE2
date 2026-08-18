<?php

namespace App\Http\Livewire\Partner;

use App\Jobs\Partner\ExportDeclaredEquipmentJob;
use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Services\Partner\DeclaredEquipmentQueryService;

class WorkEquipment extends Component
{
    use AuthorizesPartnerAccess;
    use WithPagination;

    private const EXCEL_EXPORT_LIMIT = 10000;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;
    public int $excelExportLimit = self::EXCEL_EXPORT_LIMIT;

    public $search;
    public $advancedSearch;
    public $multipleSearch;
    public $equipType;
    public $moviment;
    public $companySelected;
    public $date_in;
    public $date_out;
    public $month;


    private $filter_group = 'equipment';



    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
        'equipType' => ['except' => '', 'as' => 'tipo'],
        'moviment' => ['except' => '', 'as' => 'movimento'],
        'companySelected' => ['except' => '', 'as' => 'empresa'],
        'date_in' => ['except' => '', 'as' => 'data_inicial'],
        'date_out' => ['except' => '', 'as' => 'data_final'],
        'month' => ['except' => '', 'as' => 'mes'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'refresh_filter' => 'refreshFilters',

    ];

    public function updatedSearch()
    {
        $this->gotoPage(1);
    }

    public function updatedMonth()
    {
        $this->gotoPage(1);

        if ($this->month) {
            $this->date_in = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
            $this->date_out = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');
        } else {
            $this->date_in = null;
            $this->date_out = null;
        }
    }

    public function updated($propertyName)
    {
        $paginationSensitive = [
            'search',
            'perPage',
            'equipType',
            'moviment',
            'companySelected',
            'date_in',
            'date_out',
        ];

        if (in_array($propertyName, $paginationSensitive, true)) {
            $this->resetPage();
        }
    }

    public function refreshFilters(...$payload): void
    {
        $this->resetPage();
    }

    public function exportFile(string $format): void
    {
        $this->authorizePartnerAccess('conclusion_reports.export');

        $format = $format === ExportDeclaredEquipmentJob::FORMAT_XLSX
            ? ExportDeclaredEquipmentJob::FORMAT_XLSX
            : ExportDeclaredEquipmentJob::FORMAT_CSV;

        $total = $this->lists->count();

        if ($format === ExportDeclaredEquipmentJob::FORMAT_XLSX && $total > self::EXCEL_EXPORT_LIMIT) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Volume alto para Excel',
                'html' => "Esta consulta tem {$total} registros. Para mais de " . self::EXCEL_EXPORT_LIMIT . ' registros, use CSV.',
                'timer' => 6000,
            ]);

            return;
        }

        ExportDeclaredEquipmentJob::dispatch($this->exportParams(), (string) auth()->id(), $format);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Exportacao em andamento',
            'html' => 'Voce sera notificado quando o arquivo estiver pronto.',
            'timer' => 4000,
        ]);
    }


    public function getListsProperty()
    {
        return app(DeclaredEquipmentQueryService::class)->build($this->exportParams(), auth()->user());
    }

    public function cleanAll()
    {
        $this->reset([
            'search',
            'advancedSearch',
            'multipleSearch',
            'equipType',
            'moviment',
            'companySelected',
            'date_in',
            'date_out',
            'month'
        ]);
    }

    private function exportParams(): array
    {
        return [
            'search' => $this->search,
            'equipType' => $this->equipType,
            'moviment' => $this->moviment,
            'companySelected' => $this->companySelected,
            'date_in' => $this->date_in,
            'date_out' => $this->date_out,
            'filters' => $this->loadFilters(),
        ];
    }

    private function loadFilters(): array
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        $sessionFilters = session('filter.' . $this->filter_group);
        if (is_array($sessionFilters)) {
            return $sessionFilters;
        }

        if (isset($_SESSION['filter'][$this->filter_group]) && is_array($_SESSION['filter'][$this->filter_group])) {
            return $_SESSION['filter'][$this->filter_group];
        }

        return [];
    }


    public function render()
    {
        return view('livewire.partner.work-equipment', [
            'equipments' => $this->lists->paginate($this->perPage),
        ]);
    }
}
