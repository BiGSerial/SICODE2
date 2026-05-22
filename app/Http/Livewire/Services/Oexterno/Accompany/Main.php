<?php

namespace App\Http\Livewire\Services\Oexterno\Accompany;

use App\Custom\RuleBuilder;
use App\Exports\oexterno\ProtocolsList;
use App\Helpers\TextFormatter;
use App\Models\{Bancoupdate, File, Note, Notetimeline, Production, Service, User};
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Query\Expression;
use Livewire\{Component, WithPagination};
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class Main extends Component
{
    use WithPagination;
    use Exportable;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 200;

    public $search;

    public $advanceSearch;

    public $multisearch = [];

    public $rubrica_s = [];

    public $rubrica_l;

    public $note;

    public $last_update;

    public $protocolar = true;

    public $waiting;

    public $typeNote = "";

    // Filters
    private $filter_group = 'oexterno';

    public $column = 'dt_created';
    public $direction = 'asc';


    private $filter;

    protected $listeners = [
        'refresh_list'      => '$refresh',
        'refresh_service'   => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
         'refresh_All_Filter' => 'cleanAll',
    ];

    protected $queryString = [
        'typeNote' => ['except' => '', 'as' => 'tipo'],
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];


    public function navigateTo($note)
    {
        return redirect()->to(
            route('services.protocolNote', [
                'service' => $this->service->uuid,
                'note'    => $note,
            ])
        );
    }

    public function setColumn($column)
    {
        if ($this->column === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->column = $column;
            $this->direction = 'asc';
        }
    }

    public function updatedSearch()
    {
        if ($this->search = trim($this->search)) {

            $this->multisearch = [];
            $this->resetPage();
        }
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->multisearch = $this->formatTextToArray($this->advanceSearch);
            $this->search = '';
            $this->advanceSearch = '';
            $this->resetPage();
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function cleanAll()
    {
        $this->search = '';
        $this->advanceSearch = '';
        $this->multisearch = [];
        $this->resetPage();
        $this->dispatchBrowserEvent('hideModal');
    }

    public function exportToExcel()
    {
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Download em andamento...',
            'timer'    => 2000,
        ]);

        return Excel::download(new ProtocolsList($this->notes), date('Ymd_his').'_protocols.xlsx');
    }

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filtro']['rubrica']) && $_SESSION['filtro']['rubrica']) {
            $this->rubrica_s = $_SESSION['filtro']['rubrica'];
        }
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    // public function downloadZip()
    // {
    //     if (count($this->files_selected)) {
    //         $files = File::find($this->files_selected);

    //         if ($files) {
    //             $zipFile = 'Arquivos-Lote-' . hash('crc32', time()) . '.zip';
    //             $zip     = new ZipArchive();
    //             $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    //             foreach ($files as $file) {
    //                 $content = Storage::get($file->path);
    //                 $zip->addFromString($file->file_name . '.' . $file->ext, $content);
    //             }

    //             $zip->close();

    //             $this->files_selected = [];

    //             return response()->download($zipFile)->deleteFileAfterSend(true);
    //         }
    //     } else {
    //         $this->dispatchBrowserEvent('swal', [
    //             'position' => 'center',
    //             'icon'     => 'warning',
    //             'title'    => 'Nenhum Arquivo foi selecionado para Download',
    //             'timer'    => 5000,
    //         ]);

    //         return;
    //     }
    // }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {



            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        }
    }




    public function getNotesProperty()
    {
        if (!session()->isStarted()) {
            session()->start();
        }
        $this->filter = session("filter.{$this->filter_group}", []);

        $query = Note::query()->excludeCanceledFullDone();



        $query->where(function ($q) {
            $q->where('nstats', 11)
                ->where('type_note', 2);
        });

        $query->when(trim($this->search), function ($q, $s) {



            $wildcard = str_contains($s, '*') || str_contains($s, '%')
                ? str_replace('*', '%', $s)
                : $s;

            if (str_contains($wildcard, '%')) {
                $type = 'like';
            } else {
                $type = '=';
            }

            return $q->where(function ($query) use ($wildcard, $type) {
                $query->where('note', $type, $wildcard)
                    ->orWhere('material', $type, $wildcard)
                    ->orWhere('numPedido', $type, $wildcard)
                    ->orWhere('group2', $type, $wildcard)
                    ->orWhereHas('Externals.Protocols', function ($q) use ($wildcard, $type) {
                        $q->where('protocol', $type, $wildcard);
                    });
            });
        });

        $query->when($this->multisearch, function ($q) {
            $q->where(function ($query) {
                $query->whereIn('note', $this->multisearch)
                    ->orWhereIn('material', $this->multisearch)
                    ->orWhereIn('numPedido', $this->multisearch)
                    ->orWhereIn('group2', $this->multisearch)
                    ->orWhereHas('Externals.Protocols', function ($q) {
                        $q->whereIn('protocol', $this->multisearch);
                    });
            });
        });

        if ($this->typeNote) {
            $query->where('type_note', $this->typeNote);
        }

        if (isset($this->filter['rubrica'])) {
            $query->whereIn('rubrica', $this->filter['rubrica']);
        }

        if (isset($this->filter['city'])) {
            $query->whereIn('lexp', $this->filter['city']);
        }


        if (isset($this->filter['entities']) && count($this->filter['entities'])) {


            $query->whereHas('externals', function ($q) {
                $q->whereIn('entity_id', $this->filter['entities']);
            });
        }

        $query->with('externals.protocols', 'externals.comments')
            ->orderBy($this->column, $this->direction);

        return $query;
    }


    public function render()
    {


        return view('livewire.services.oexterno.accompany.main', [
            'total'  => $this->notes->get(),
            'lists'  => $this->notes->paginate($this->perPage),
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }
}
