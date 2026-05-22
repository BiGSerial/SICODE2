<?php

namespace App\Http\Livewire\Engineers;

use App\Exports\Workreports\HistListExport;
use App\Helpers\TextFormatter;
use App\Models\Edp_depc\City;
use App\Models\File;
use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Workedlist extends Component
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
    public $adsOnly = false;


    // search by date
    public $month;
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


    public function updatedMonth()
    {
        $this->date_in = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
        $this->date_out = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');
    }

    public function updatedDateIn()
    {
        $this->month = Carbon::parse($this->date_in)->format('Y-m');
    }

    public function buscarMulti()
    {
        $this->search = '';
        $this->multiSearch = $this->formatTextToArray($this->advanceSearch);
        $this->dispatchBrowserEvent('hideModal');
        $this->advanceSearch = '';
    }


    public function export_excel()
    {

        $export = new HistListExport($this->lists->pluck('id')->toArray());


        return Excel::download($export, 'Informe_Conclusao_historico_'.date('YmdHis').'.xlsx');
    }

    public function mount()
    {
        $this->cities = City::orderBy('cidade')->get();

        // $this->month = Carbon::now()->format('Y-m');
        // $this->date_in = Carbon::now()->startOfMonth()->format('Y-m-d');
        // $this->date_out = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function cleanAll()
    {
        $this->search = '';
        $this->advanceSearch = '';
        $this->multiSearch = [];
        $this->date_in = '';
        $this->date_out = '';
        $this->month = '';
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

        $query = WorkReport::query()->active();


        $query->where('rejected', false);


        if (!auth()->user()->superadm) {

            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->where(function ($q) {
                    $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
                });
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }


        if (($this->date_in || $this->date_out)) {

            if ($this->date_in && !$this->date_out) {
                $query->whereDate('informed_at', '>=', $this->date_in);
            }

            if (!$this->date_in && $this->date_out) {
                $query->whereDate('informed_at', '<=', $this->date_out);
            }

            if ($this->date_in && $this->date_out) {
                $query->whereBetween('informed_at', [$this->date_in, $this->date_out]);
            }
        }

        if ($this->search) {

            $this->advanceSearch = '';
            $this->multiSearch = [];

            $query->where(function ($q) {
                $q->WhereRelation('Note', 'note', 'like', "%$this->search%")
                    ->orWhereRelation('Orders', 'ordem', 'like', "%$this->search%");
            });
        }

        if ($this->multiSearch) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('note', $this->multiSearch)
                    ->orWhereRelation('Orders', function ($q) {
                        $q->whereIn('ordem', $this->multiSearch);
                    });
            });
        }

        if ($this->adsOnly) {
            $query->where(function ($q) {
                $q->whereHas('Note.OldAds')
                    ->orWhereHas('Note.Adsform');
            });
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



        return $query;
    }

    public function render()
    {
        return view('livewire.engineers.workedlist', [
            'lists' => $this->lists->orderBy('created_at', 'DESC')->paginate($this->perPage)
        ]);
    }
}
