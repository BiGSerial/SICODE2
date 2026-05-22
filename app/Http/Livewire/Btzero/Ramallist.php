<?php

namespace App\Http\Livewire\Btzero;

use App\Exports\SMC\Smcexport;
use App\Exports\SMC\SmcListExport;
use App\Models\Edp_depc\City;
use App\Models\File;
use App\Models\RamalReport; // Ensure this model exists in your application
use App\Models\User;
use App\Models\WorkReport;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Ramallist extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $cities;

    public $files_selected = [];

    public $search;
    public $informer;
    public $informers;

    // search by date
    public $date_in;
    public $date_out;
    // public $dateBy = 'sended_at';

    // Filters
    private $filter_group = 'partner_forms';

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
        $this->informers = User::whereIn(
            'id',
            RamalReport::select('user_id')->distinct()->pluck('user_id')
        )->orderBy('name')->get();

    }

    public function export_excel()
    {
        return (new SmcListExport($this->getListsProperty()->with('Note.WorkForm')))->download(date('ymd-his').'-SMC_HistoricList.xlsx');
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
    }

    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = RamalReport::Query();


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

        if ($this->informer) {
            $query->where('user_id', $this->informer);
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
        return view('livewire.btzero.ramallist', [
            'lists' => $this->lists->paginate($this->perPage)
        ]);
    }
}
