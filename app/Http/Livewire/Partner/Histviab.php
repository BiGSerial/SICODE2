<?php

namespace App\Http\Livewire\Partner;

use App\Exports\Viability\HistoricReport;
use App\Helpers\TextFormatter;
use App\Models\City;
use App\Models\{File, Viability};
use App\Services\Files\FileStorageService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Livewire\{Component, WithPagination};
use ZipArchive;

class Histviab extends Component
{
    use \App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
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
        $this->authorizePartnerAccess('viability.export');

        return (new HistoricReport($this->lists->orderBy('sended_at')->get()))->download(date('YmdHis-') . 'HistViabExport.xlsx');
    }

    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function downloadFile($id)
    {
        $this->authorizePartnerAccess('viability.view_files');

        if ($file = File::find($id)) {

            $storage = app(FileStorageService::class);

            if ($storage->exists($file)) {
                return $storage->download($file, $file->file_name);
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
            $this->multinotas    = [];
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
        $this->authorizePartnerAccess('viability.view_files');

        if (count($this->files_selected)) {
            $files = File::find($this->files_selected);

            if ($files) {
                $zipFile = 'Arquivos-Lote-' . hash('crc32', time()) . '.zip';
                $zip     = new ZipArchive();
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                $storage    = app(FileStorageService::class);
                $tempCopies = [];

                foreach ($files as $file) {
                    $tempCopy = $storage->temporaryLocalCopy($file);

                    if (!$tempCopy) {
                        continue;
                    }

                    if (!$storage->matchesStoredChecksum($file, $tempCopy)) {
                        $zip->close();

                        foreach (array_merge($tempCopies, [$tempCopy]) as $copy) {
                            if (is_file($copy)) {
                                @unlink($copy);
                            }
                        }

                        if (file_exists($zipFile)) {
                            @unlink($zipFile);
                        }

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'error',
                            'title'    => 'Checksum divergente!',
                            'html'     => 'O arquivo ' . e($file->original_name ?: $file->file_name) . ' não confere com o hash gravado no servidor.',
                            'timer'    => 5000,
                        ]);

                        return;
                    }

                    $zip->addFile($tempCopy, $file->file_name . '.' . $file->ext);
                    $tempCopies[] = $tempCopy;
                }

                $zip->close();

                foreach ($tempCopies as $tempCopy) {
                    if (is_file($tempCopy)) {
                        @unlink($tempCopy);
                    }
                }

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
        $this->date_in  = "";
        $this->date_out = "";
        $this->dateBy   = 'sended_at';
        $this->search   = '';
    }

    public function updatedMonth()
    {
        if ($this->month) {
            $this->date_in  = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
            $this->date_out = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');
        } else {
            $this->date_in  = '';
            $this->date_out = '';
        }
    }

    public function getListsProperty()
    {

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) {
                session()->start();
            }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Viability::Query();
        // ->where('completed', true)
        // ->where('approved', true)
        // ->where('hired', true);

        $this->applyPartnerCompanyScope($query);

        $this->applyPartnerBranchScopeToNoteRelation($query);

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
