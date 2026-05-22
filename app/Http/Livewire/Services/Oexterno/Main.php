<?php

namespace App\Http\Livewire\Services\Oexterno;

use App\Custom\RuleBuilder;
use App\Exports\oexterno\ProtocolsList;
use App\Helpers\TextFormatter;
use App\Models\{Bancoupdate, File, Note, Notetimeline, Production, Service, User};
use Illuminate\Support\Facades\Storage;
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
    public $statusFilter = '';

    public $column = 'dt_created';
    public $direction = 'asc';

    // Filters
    private $filter_group = 'oexterno';

    private $filter;

    protected $listeners = [
        'refresh_list'      => '$refresh',
        'refresh_service'   => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
        'refresh_All_Filter' => 'cleanAll'
    ];

    protected $queryString = [
        'typeNote' => ['except' => '', 'as' => 'tipo'],
        'statusFilter' => ['except' => '', 'as' => 'status'],
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

    public function updatedSearch()
    {
        if ($this->search = trim($this->search)) {
            $this->multisearch = [];
            $this->resetPage();
        }
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


    public function mount($service)
    {
        $this->service = Service::query()
            ->select(['id', 'uuid', 'service', 'status'])
            ->where('uuid', $service)
            ->with('Status')
            ->first();
        $this->last_update = Note::query()
            ->orderByDesc('dt_status')
            ->value('dt_status');

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) { session()->start(); }
        }

        if (isset($_SESSION['filtro']['rubrica']) && $_SESSION['filtro']['rubrica']) {
            $this->rubrica_s = $_SESSION['filtro']['rubrica'];
        }
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

    public function to_accompany(Note $note)
    {
        $this->note = $note;

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Atribuir Tarefa',
            'msg'   => "
            Você deseja atribuir a NOTA/OV para você?</br></br>
            <div class='card card-light'>
            <div class='card-body'>
            <p><strong>NOTA/OV estará disponível em acompanhamento como
            sua tarefa e nenhum outro usuário poderá atribuir pra si.</p>
            </div>
            </div>
            ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Atribua!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_accompany',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum serviço foi atribuído.',

        ]);
    }

    public function add_to_accompany()
    {
        $user = User::with('Employee.Contract')->find(Auth()->User()->id);

        $check = Production::where('note_id', $this->note->id)->where(function ($q) {
            return $q->where('completed', false)
                ->orWhere('dt_note', $this->note->dt_status);
        })->with('User', 'Company', 'Service')->first();

        if ($check) {
            $name = $check->User?->name ?? ($check->Company ? "{$check->Company->name} (sem usuário atribuído)" : 'Desconhecido');

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOOPS! NOTA/OV TRATADA OU EM TRATAMENTO',
                'html'     => "<strong>{$this->note->note}</strong> foi ou está em Tratamento em {$check->Service->service} por <strong>{$name}</strong>",

            ]);

            return;
        }

        $production = Production::Create([
            'note_id'     => $this->note->id,
            'service_id'  => $this->service->uuid,
            'user_id'     => $user->id,
            'company_id'  => $user->Employee->Contract->company_id,
            'dispatch_by' => $user->id,
            'att_by'      => $user->id,
            'dt_note'     => $this->note->dt_status,
            'status_note' => $this->note->nstats,
            'dispatch_at' => date('Y-m-d H:i:s'),
            'att_at'      => date('Y-m-d H:i:s'),
            'status'      => 2,
            'dhstats'     => $this->note->dt_status,
        ]);

        if ($production) {

            Notetimeline::Create([
                'note_id'      => $this->note->id,
                'service_id'   => $production->service_id,
                'user_id'      => Auth()->User()->id,
                'info'         => "Usuário {$user->name} atribuiu a Nota/OV.",
                'status'       => 2,
                'productionId' => $production->id,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => "{$this->note->note} foi atribuído a você com sucesso.",
                'timer'    => 2500,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => "Erro ao tentar atribuir {$this->note->note}.",
                'timer'    => 2500,
            ]);
        }
    }



    public function getNotesProperty()
    {

        if (!session()->isStarted()) {
            session()->start();
        }
        $this->filter = session("filter.{$this->filter_group}", []);




        $query = Note::query()
            ->select([
                'id',
                'note',
                'material',
                'numPedido',
                'group2',
                'lexp',
                'rubrica',
                'nstats',
                'centerjob',
                'type_note',
                'dt_status',
                'dt_created',
            ])
            ->excludeCanceledFullDone();

        // RuleBuilder::applyRules($query, $this->service->Status);

        // if ($this->protocolar || $this->waiting) {
        //     // $query = Note::query()->excludeCanceledFullDone();
        //     $query->when($this->protocolar, function ($q) {
        //         return $q->where('nstats', 20);
        //     })->when($this->waiting, function ($q) {
        //         return $q->where('nstats', 11);
        //     });
        // }

        $allowedStatuses = $this->statusFilter ? [(int) $this->statusFilter] : [20, 11];
        $hasDirectSearch = filled(trim((string) $this->search));
        $hasMultiSearch = !empty($this->multisearch);
        $hasAnySearch = $hasDirectSearch || $hasMultiSearch;

        if (!$hasAnySearch) {
            $query->where(function ($q) use ($allowedStatuses) {
                $q->whereIn('nstats', $allowedStatuses)
                    ->orWhere(function ($qq) {
                        $qq->where('centerjob', 'ORGAOEXT')
                            ->where('type_note', 1);
                    });
            });

            // "Main/A Protocolar": fora das 4 caixas auxiliares.
            // As 4 listas trabalham com registros em externals com completed = false.
            $query->whereDoesntHave('Externals', function ($q) {
                $q->where('completed', false);
            });
        }

        $query->when($this->search, function ($q) {
            $wildcard = str_contains($this->search, '*') || str_contains($this->search, '%')
                ? str_replace('*', '%', $this->search)
                : $this->search;

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


        $query->with([
            'Files:id,note_id,service_id,file_name,path,ext',
            'Externals:id,note_id,entidade,status,completed,updated_at',
            'Externals.Protocols:id,external_id,protocol,created_at',
            'Externals.Comments:id,external_id,title,created_at,updated_at',
        ])->orderBy($this->column, $this->direction);


        return $query;

    }

    public function render()
    {
        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        return view('livewire.services.oexterno.main', [
            'lists'  => $this->notes->paginate($this->perPage),
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }

    public function resolveFolderLabel(Note $note): array
    {
        $hasExternal = $note->externals && $note->externals->isNotEmpty();
        $pendingExternal = $hasExternal
            && $note->externals->contains(fn ($external) => (bool) !$external->completed);

        if (!$pendingExternal) {
            return ['label' => 'A PROTOCOLAR', 'badge' => 'text-bg-success'];
        }

        $pendingStatuses = collect($note->externals)
            ->filter(fn ($external) => (bool) !$external->completed)
            ->pluck('status')
            ->filter()
            ->values();

        if ($pendingStatuses->contains('AGUARDANDO_PAGAMENTO')) {
            return ['label' => 'AGUARDANDO PAGAMENTO', 'badge' => 'text-bg-danger'];
        }

        if ($pendingStatuses->contains('AGUARDANDO_TAXA')) {
            return ['label' => 'AGUARDANDO TAXA', 'badge' => 'text-bg-info'];
        }

        if ($pendingStatuses->contains('AGUARDANDO_ORGAO')) {
            return ['label' => 'AGUARDANDO ORGAO EXTERNO', 'badge' => 'text-bg-warning'];
        }

        return ['label' => 'STATUS INDEFINIDO', 'badge' => 'text-bg-dark'];
    }
}
