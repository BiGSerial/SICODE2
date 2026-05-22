<?php

namespace App\Http\Livewire\Reports;

use App\Helpers\TextFormatter;
use App\Models\Edp_depc\City;
use App\Models\File;
use App\Models\WorkReport;
use App\Jobs\Reports\ExportWorkreportsJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Workreports extends Component
{
    use WithPagination;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $cities;

    public $files_selected = [];

    public $search;
    public $advanceSearch;

    public $multiSearch = [];

    // search by date
    public $date_in;
    public $date_out;
    public $dateBy = 'first_informed';

    // Filters
    private $filter_group = 'reports_worklist';

    private $filter;

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    public function mount()
    {
        $this->cities = City::orderBy('cidade')->get();
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->goToPage(1);
            $this->multiSearch = $this->formatTextToArray($this->advanceSearch);
            $this->dispatchBrowserEvent('hideModal');
        } else {
            $this->multiSearch = [];
        }
    }

    public function cleanAll()
    {
        $this->search = '';
        $this->advanceSearch = '';
        $this->multiSearch = [];
        $this->date_in = '';
        $this->date_out = '';
        $this->dateBy = 'first_informed';
    }

    public function updated($propertyName)
    {
        $paginationSensitive = [
            'search',
            'advanceSearch',
            'date_in',
            'date_out',
            'dateBy',
            'perPage',
        ];

        if (in_array($propertyName, $paginationSensitive, true)) {
            $this->resetPage();
        }
    }

    public function exportReport(): void
    {
        $filters = $this->loadFilters();

        $params = [
            'date_in' => $this->date_in,
            'date_out' => $this->date_out,
            'dateBy' => $this->dateBy,
            'search' => $this->search,
            'multiSearch' => $this->multiSearch,
            'filters' => $filters,
        ];

        ExportWorkreportsJob::dispatch($params, (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'EXPORTACAO EM ANDAMENTO',
            'html' => "<div class='card'><div class='card-body'><p>Seu arquivo esta sendo gerado.</p><p class='fw-bold'>Voce sera notificado quando estiver pronto.</p></div></div>",
            'timer' => 5000,
        ]);
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        }
    }

    public function getListsProperty()
    {
        $this->filter = $this->loadFilters();

        $query = WorkReport::query()->active();


        // $query->where('company_id', Auth()->User()->Employee->Contract->company->id);


        if ($this->date_in || $this->date_out) {
            $dateColumn = $this->resolveDateColumn();

            if ($this->date_in) {
                $query->whereDate($dateColumn, '>=', $this->date_in);
            }

            if ($this->date_out) {
                $query->whereDate($dateColumn, '<=', $this->date_out);
            }
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->WhereRelation('Note', 'note', 'like', "%$this->search%")
                    ->orWhereRelation('Orders', 'ordem', 'like', "%$this->search%");
            });
        }

        if ($this->multiSearch) {
            $query->where(function ($q) {
                $q->WhereRelation('Note', function ($sq) {
                    $sq->whereIn('note', $this->multiSearch);
                })->orWhereRelation('Orders', function ($sq) {
                    $sq->whereIn('ordem', $this->multiSearch);
                });
            });
        }

        if (isset($this->filter['company'])) {
            $query->whereIn('company_id', $this->filter['company']);
        }

        if (isset($this->filter['city'])) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('lexp', $this->filter['city']);
            });
        }

        if (isset($this->filter['rubrica'])) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('rubrica', $this->filter['rubrica']);
            });
        }

        $query->orderBy('created_at', 'DESC');

        return $query;
    }

    private function loadFilters(): array
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }
        
        return $_SESSION['filter'][$this->filter_group] ?? [];
    }

    private function resolveDateColumn()
    {
        if ($this->dateBy === 'informed_at' || $this->dateBy === 'created_at') {
            return $this->dateBy;
        }

        return DB::raw('COALESCE(informed_at, created_at)');
    }

    public function render()
    {
        return view('livewire.reports.workreports', [
            'lists' => $this->lists->paginate($this->perPage)
        ]);
    }
}
