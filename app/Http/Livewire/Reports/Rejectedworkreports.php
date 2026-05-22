<?php

namespace App\Http\Livewire\Reports;

use App\Helpers\TextFormatter;
use App\Models\Edp_depc\City;
use App\Models\File;
use App\Models\WorkReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Rejectedworkreports extends Component
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
    // public $dateBy = 'sended_at';

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
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        // Iniciar a consulta de WorkReport
        $query = WorkReport::query()->active()
            ->where('rejected', true)
        ->whereDoesntHave('Note', function ($q) {
            $q->whereIn('nstats', [55])
            ->orWhere(function ($q) {
                $q->where('nstats', 99)
                  ->where('type_note', 1);
            });
        });

        // Filtros de data
        if ($this->date_in || $this->date_out) {
            if ($this->date_in && !$this->date_out) {
                $query->whereDate('created_at', '>=', $this->date_in);
            }

            if (!$this->date_in && $this->date_out) {
                $query->whereDate('created_at', '<=', $this->date_out);
            }

            if ($this->date_in && $this->date_out) {
                $query->whereBetween('created_at', [$this->date_in, $this->date_out]);
            }
        }

        // Filtro de busca
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', 'like', "%$this->search%")
                    ->orWhereRelation('Orders', 'ordem', 'like', "%$this->search%")
                    ->orWhereRelation('Returnwork', 'category', 'like', "%$this->search%")
                    ->orWhereRelation('Returnwork', 'text_obs', 'like', "%$this->search%");
            });
        }

        // Filtro de multiSearch
        if ($this->multiSearch) {
            $query->where(function ($q) {
                $q->whereRelation('Note', function ($sq) {
                    $sq->whereIn('note', $this->multiSearch);
                })->orWhereRelation('Orders', function ($sq) {
                    $sq->whereIn('ordem', $this->multiSearch);
                });
            });
        }

        // Filtros adicionais
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
        if (isset($this->filter['category'])) {
            $query->whereRelation('Returnwork', function ($q) {
                $q->whereIn('category', $this->filter['category']);
            });
        }

        // Join com Returnwork para ordenar pelo último registro da relação
        $subquery = DB::table('return_works')
            ->select('work_report_id', DB::raw('MAX(created_at) as max_created_at'))
            ->groupBy('work_report_id');

        $query->leftJoinSub($subquery, 'last_return_work', function ($join) {
            $join->on('work_reports.id', '=', 'last_return_work.work_report_id');
        })
        ->select('work_reports.*')
        ->orderBy('last_return_work.max_created_at', 'ASC');

        return $query; // Executa a consulta e retorna os resultados
    }



    public function render()
    {
        return view('livewire.reports.rejectedworkreports', [
            'lists' => $this->lists->paginate($this->perPage)
        ]);
    }
}
