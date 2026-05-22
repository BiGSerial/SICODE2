<?php

namespace App\Http\Livewire\Partner;

use App\Models\Edp_depc\City;
use App\Models\{File, Note};
use Illuminate\Support\Facades\{Crypt, Storage};
use Livewire\{Component, WithPagination};
use ZipArchive;

class Hiredviability extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $cities;

    public $files_selected = [];

    public $search;

    // Filters
    private $filter_group = 'partner';

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



    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        }
    }

    public function openForms($id)
    {
        if ($id) {

            return redirect()->route('forms.viability', ['id' => Crypt::encrypt($id)]);
        }
    }

    public function downloadZip()
    {
        if (count($this->files_selected)) {
            $files = File::find($this->files_selected);

            if ($files) {
                $zipFile = 'Arquivos-Lote-' . hash('crc32', time()) . '.zip';
                $zip     = new ZipArchive();
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                foreach ($files as $file) {
                    $content = Storage::get($file->path);
                    $zip->addFromString($file->file_name . '.' . $file->ext, $content);
                }

                $zip->close();

                $this->files_selected = [];

                return response()->download($zipFile)->deleteFileAfterSend(true);
            }
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum Arquivo foi selecionado para Download',
                'timer'    => 5000,
            ]);

            return;
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

        $query = Note::Query();

        $query->whereRelation('Viabilities', function ($q) {
            $q->where('tacit', false)
                ->where('canceled', false)
                ->where('hired', true)
                ->where('completed', false);

            if (!Auth()->User()->superadm) {

                if (isset(Auth()->User()->Employee->Contract->Company->id)) {
                    $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
                } else {
                    $q->where('company_id', null);
                }
            }

        })
            ->with(['Viabilities' => function ($query) {
                $query->where('tacit', false)
                ->where('canceled', false)
                ->where('hired', true)
                ->where('completed', false);
            }, 'Files']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->Where('note', 'like', "%$this->search%")
                    ->orWhereRelation('Orders', 'ordem', 'like', "%$this->search%");
            });
        }

        if (isset($this->filter['rubrica'])) {

            $query->whereIn('rubrica', $this->filter['rubrica']);
        }

        if (isset($this->filter['city'])) {

            $query->whereIn('lexp', $this->filter['city']);
        }

        return $query->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.partner.hiredviability', [
            'lists'  => $this->lists,
            'cities' => $this->cities,
        ]);
    }
}
