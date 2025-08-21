<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Helpers\TextFormatter;
use App\Models\Edp_depc\City;
use App\Models\File;
use App\Models\Note;
use App\Models\Viability;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Histhiring extends Component
{
    use WithFileUploads;

    use TextFormatter;

    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $cities;

    public $files_selected = [];
    public $hasNoHired = false;

    public $search;
    public $advancedSearch;
    public $multipleSearch = [];


    // search by date
    public $date_in;
    public $date_out;
    public $dateBy = 'sended_at';

    // Filters
    private $filter_group = 'hiring_hist';

    private $filter;

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'update_list' => '$refresh',
        'refresh_list' => '$refresh',
    ];

    public function mount()
    {
        $this->cities = City::orderBy('cidade')->get();
    }

    public function buscarMulti()
    {
        if ($this->advancedSearch) {

            $this->multipleSearch = $this->formatTextToArray($this->advancedSearch);

            if (count($this->multipleSearch) > 0) {
                $this->search = null;
                $this->goToPage(1);
                $this->advancedSearch = null;

                $this->dispatchBrowserEvent('hideModal');
            }
        }
    }

    public function updatedSearch()
    {
        if (trim($this->search)) {
            $this->advancedSearch = null;
            $this->multipleSearch = [];
            $this->goToPage(1);
        }
    }

    public function downloadFile($id)
    {


        if ($file = File::find($id)) {

            // dd($file->file_name);

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
        $query = Viability::query();

        $query->where('hired', true);

        if ($this->dateBy && ($this->date_in || $this->date_out)) {

            if ($this->date_in && !$this->date_out) {
                $query->whereDate($this->dateBy, '>=', $this->date_in);
            }

            if (!$this->date_in && $this->date_out) {
                $query->whereDate($this->dateBy, '<=', $this->date_out);
            }

            if ($this->date_in && $this->date_out) {
                $query->whereBetween($this->dateBy, [$this->date_in, $this->date_out]);
            }
        }

        if ($this->hasNoHired) {
            $query->whereRelation('Orders.Operations', function ($q) {
                $q->where('operacao', '0010')->where('status', 'NOT LIKE', 'CONF%');
            });
        }

        if ($this->multipleSearch) {
            $multipleSearch = $this->multipleSearch; //Define a variável fora das closures
            $query->whereRElation('Note', function ($q) use ($multipleSearch) {
                $q->whereIn('note', $multipleSearch)
                  ->orWhereHas('orders', function ($q) use ($multipleSearch) {
                      $q->whereIn('ordem', $multipleSearch);
                  });
            });
        }

        $query->orderBy('sended_at', 'DESC');

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', 'like', '%' . $this->search . '%')
                    ->orWhereRelation('Note.Orders', 'ordem', 'like', '%' . $this->search . '%');
            });
        }

        if (isset($this->filter['city'])) {
            $query->whereIn('lexp', $this->filter['city']);
        }

        $query->with(['Company', 'User', 'Form', 'Comments.User', 'Files']);

        return $query->paginate($this->perPage);
    }





    public function render()
    {
        return view('livewire.construction.hiring.histhiring', [
            'lists' => $this->lists
        ]);
    }
}
