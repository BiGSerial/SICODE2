<?php

namespace App\Http\Livewire\Reports;

use App\Models\Edp_depc\City;
use App\Models\File;
use App\Models\WorkReport;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Workreports extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $cities;

    public $files_selected = [];

    public $search;

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

    public function cleanAll()
    {
        $this->search = '';
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
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = WorkReport::Query();


        // $query->where('company_id', Auth()->User()->Employee->Contract->company->id);


        if (($this->date_in || $this->date_out)) {

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

        if ($this->search) {
            $query->where(function ($q) {
                $q->WhereRelation('Note', 'note', 'like', "%$this->search%")
                    ->orWhereRelation('Orders', 'ordem', 'like', "%$this->search%");
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

    public function render()
    {
        return view('livewire.reports.workreports', [
            'lists' => $this->lists->paginate($this->perPage)
        ]);
    }
}
