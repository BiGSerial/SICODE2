<?php

namespace App\Http\Livewire\Partner;

use App\Exports\parner\exportExcel;
use App\Models\Edp_depc\City;
use App\Models\{File, Note};
use Illuminate\Support\Facades\{Crypt, Storage};
use Livewire\{Component, WithPagination};
use ZipArchive;

class Todoviability extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $cities;

    public $files_selected = [];
    public $inActivity = [];

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

    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function putInActvity($id)
    {

        if ($id) {

            foreach ((Note::find($id))->Viabilities as $viab) {
                $viab->update(['inActivity' => !$viab->inActivity]);
            }
        }

    }

    public function export_excel()
    {

        return (new  exportExcel($this->lists->get()->sortBy(function ($note) {
            // Acessar a primeira 'Viability' e o campo 'sended_at'
            return $note->Viabilities->first()->sended_at ?? null;
        })))->download(date('YmdHis-') . 'exportViabilityParner.xlsx');
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
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];

        }

        $query = Note::Query();

        $query->whereRelation('Viabilities', function ($q) {
            $q->where('canceled', false)
                ->where('completed', false)
                ->where('tacit', false);
            if (!Auth()->User()->superadm) {

                $companyId = auth()->user()->Employee->Contract->Company->id ?? null;

                if ($companyId) {
                    $q->where('company_id', $companyId);
                } else {
                    $q->where('company_id', null);
                }

            }

        })->with(['Viabilities' => function ($query) {
            $query->where('tacit', false)
            ->where('canceled', false)

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

        return $query;
    }

    public function inActivityUpdade()
    {
        return $this->inActivity = Note::whereRelation('Viabilities', function ($q) {
            $q->where('canceled', false)
                ->where('inActivity', true)
                ->where('completed', false)
                ->where('tacit', false);

            if (!Auth()->User()->superadm) {

                $companyId = auth()->user()->Employee->Contract->Company->id ?? null;
                if ($companyId) {
                    $q->where('company_id', $companyId);
                } else {
                    $q->where('company_id', null);
                }
            }
        })->get()->pluck('id')->toArray();
    }

    public function render()
    {
        $this->inActivityUpdade();

        return view('livewire.partner.todoviability', [
            'lists'  => $this->lists->paginate($this->perPage),
            'cities' => $this->cities,
        ]);
    }
}
