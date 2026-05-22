<?php

namespace App\Http\Livewire\Partner;

use App\Exports\parner\exportExcel;
use App\Exports\Viability\HistoricReport;
use App\Helpers\TextFormatter;
use App\Models\Edp_depc\City;
use App\Models\{File, Note, Viability};
use Carbon\Carbon;
use Illuminate\Support\Facades\{Crypt, Storage};
use Livewire\{Component, WithPagination};
use ZipArchive;

class Histviab extends Component
{
    use WithPagination;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $cities;

    public $files_selected = [];

    public $search;

    public $typeNote = '';
    public $advanceSearch = '';
    public $multinotas = [];

    // search by date
    public $date_in;
    public $date_out;
    public $month;
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

        return (new HistoricReport($this->lists->orderBy('sended_at')->get()))->download(date('YmdHis-') . 'HistViabExport.xlsx');
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

    public function buscarMultinotas()
    {
        if ($this->advanceSearch) {
            $this->search = '';
            $this->gotoPage(1);

            $this->multinotas = $this->formatTextToArray($this->advanceSearch);
            if (count($this->multinotas)) {
                $this->advanceSearch = '';
                $this->dispatchBrowserEvent('hideModal');
            }
        }
    }

    public function updatedSearch()
    {
        if (trim($this->search)) {
            $this->gotoPage(1);
            $this->multinotas = [];
            $this->advanceSearch = '';
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

    public function updatedMonth()
    {
        if ($this->month) {
            $this->date_in = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
            $this->date_out =  Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');
        } else {
            $this->date_in = '';
            $this->date_out = '';
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

        if ($this->multinotas) {
            $query->where(function ($q) {
                $q->whereRelation('Note', function ($q) {
                    $q->whereIn('note', $this->multinotas);
                })
                    ->orWhereRelation('Note.Orders', function ($q) {
                        $q->whereIn('ordem', $this->multinotas);
                    });
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
        return view('livewire.partner.histviab', [
            'lists'  => $this->lists->paginate($this->perPage),
            'cities' => $this->cities,
        ]);
    }
}
