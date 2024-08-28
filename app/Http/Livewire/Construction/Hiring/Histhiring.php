<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\Edp_depc\City;
use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Histhiring extends Component
{
    use WithFileUploads;

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
        $query = Note::query();

        $query->whereRelation('Viabilities', function ($q) {
            $q->where('hired', true);

            if ($this->dateBy && ($this->date_in || $this->date_out)) {

                if ($this->date_in && !$this->date_out) {
                    $q->whereDate($this->dateBy, '>=', $this->date_in);
                }

                if (!$this->date_in && $this->date_out) {
                    $q->whereDate($this->dateBy, '<=', $this->date_out);
                }

                if ($this->date_in && $this->date_out) {
                    $q->whereBetween($this->dateBy, [$this->date_in, $this->date_out]);
                }
            }

            $q->orderBy('sended_at', 'DESC');
        });

        if ($this->search) {
            $query->where(function ($q) {
                $q->Where('note', 'like', "%$this->search%")
                    ->orWhereRelation('Orders', 'ordem', 'like', "%$this->search%");
            });
        }

        if (isset($this->filter['city'])) {

            $query->whereIn('lexp', $this->filter['city']);
        }

        $query->with(['Viabilities' => function ($query) {
            $query->where('hired', true)
                ->with('Company', 'User', 'Form', 'Comments.User');
        }, 'Files']);


        return $query->paginate($this->perPage);
    }





    public function render()
    {
        return view('livewire.construction.hiring.histhiring', [
            'lists' => $this->lists
        ]);
    }
}
