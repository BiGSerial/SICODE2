<?php

namespace App\Http\Livewire\Responsible;

use App\Exports\parner\exportExcel;
use App\Exports\Viability\HistoricReport;
use App\Models\City;
use App\Models\File;
use App\Models\Viability;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class ViabHist extends Component
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
    public $dateBy = 'sended_at';

    // Filters
    private $filter_group = 'partner_hist';

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

    public function export_excel()
    {
        return (new HistoricReport($this->lists->orderBy('sended_at', 'DESC')->get()))->download(date('YmdHis-') . 'HistViabExport.xlsx');
    }

    public function updatedPerPage()
    {
        $this->gotoPage(1);
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



    public function cleanAll()
    {
        $this->date_in = "";
        $this->date_out = "";
        $this->dateBy = 'sended_at';
        $this->search = '';
    }

    public function getListsProperty()
    {

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }


        $query = Viability::Query();
        // ->where('completed', true)
        // ->where('approved', true)
        // ->where('hired', true);

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


        if ($this->search) {
            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', trim($this->search))
                    ->orWhereRelation('Note.Orders', 'ordem', trim($this->search));
            });
        }

        if ($this->date_in || $this->date_out) {
            $query->where(function ($q) {
                if ($this->date_in && !$this->date_out) {

                    $q->where($this->dateBy, '>=', $this->date_in);

                } elseif (!$this->date_in && $this->date_out) {

                    $q->where($this->dateBy, '<=', $this->date_out);

                } elseif ($this->date_in && $this->date_out) {

                    $q->whereBetween($this->dateBy, [$this->date_in, $this->date_out]);
                }
            });
        }



        return $query->orderBy('completed_at', 'DESC');
    }

    public function render()
    {
        return view('livewire.responsible.viab-hist', [
            'lists'  => $this->lists->paginate($this->perPage),
            'cities' => $this->cities,
        ]);
    }
}
