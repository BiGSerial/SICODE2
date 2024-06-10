<?php

namespace App\Http\Livewire\Partner;

use App\Models\Edp_depc\City;
use App\Models\{File, Note};
use Illuminate\Support\Facades\{Crypt, Storage};
use Livewire\{Component, WithPagination};
use ZipArchive;

class Histviab extends Component
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

    public function updatedPerPage()
    {
        $this->gotoPage(1);
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
            session_start();
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        // dd($_SESSION['filter']);

        $query = Note::Query();

        $query->whereRelation('Viabilities', function ($q) {

            $q->where('completed', true);

            if (!Auth()->User()->superadm) {

                $companyId = auth()->user()->Employee->Contract->Company->id ?? null;

                if ($companyId) {
                    $q->where('company_id', $companyId);
                } else {
                    $q->where('company_id', null);
                }
            }

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

            $q->orderBy('sended_at');
        })
            ->with(['Viabilities' => function ($query) {
                $query->where('completed', true)
                    ->orderBy('sended_at')
                    ->with('Order', 'Form', 'Comments.User');
            }, 'Files']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->Where('note', 'like', "%$this->search%")
                    ->orWhereRelation('Orders', 'ordem', 'like', "%$this->search%");
            });
        }

        if (isset($this->filter['city'])) {

            $query->whereIn('lexp', $this->filter['city']);
        }

        return $query->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.partner.histviab', [
            'lists'  => $this->lists,
            'cities' => $this->cities,
        ]);
    }
}
