<?php

namespace App\Http\Livewire\Services\Oexterno;

use App\Exports\oexterno\ProtocolsList;
use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\WithListFilters;
use App\Models\{Bancoupdate, City, Entity, EntityType, File, Note, Notetimeline, Production, Service, User};
use App\Services\Files\FileStorageService;
use Illuminate\Support\Facades\Cache;
use Livewire\{Component, WithPagination};
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class Main extends Component
{
    use WithPagination;
    use Exportable;
    use TextFormatter;
    use WithListFilters;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 200;

    public $search;

    public $advanceSearch;

    public $multisearch = [];

    public $note;

    public $last_update;

    public $protocolar = true;

    public $waiting;

    public $typeNote = "";

    public $statusFilter = '';

    public $column = 'dt_created';

    public $direction = 'asc';

    protected $listeners = [
        'refresh_list'       => '$refresh',
        'refresh_service'    => '$refresh',
        'getCopy'            => 'copy',
        'confirm_accompany'  => 'add_to_accompany',
        'refresh_All_Filter' => 'cleanAll',
    ];

    protected $queryString = [
        'typeNote'     => ['except' => '', 'as' => 'tipo'],
        'statusFilter' => ['except' => '', 'as' => 'status'],
        'search'       => ['except' => '', 'as' => 'buscar'],
        'page'         => ['except' => 1, 'as' => 'p'],
        'perPage'      => ['as' => 'pp'],
        'filters'      => ['except' => [], 'as' => 'f'],
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
            $this->column    = $column;
            $this->direction = 'asc';
        }
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->multisearch   = $this->formatTextToArray($this->advanceSearch);
            $this->search        = '';
            $this->advanceSearch = '';
            $this->resetPage();
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function cleanAll()
    {
        $this->search        = '';
        $this->advanceSearch = '';
        $this->multisearch   = [];
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
        $this->last_update = Cache::remember('ops-last-note-status-date', 60, fn () => Note::query()->max('dt_status'));
    }

    // ---------- Filtros (WithListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'entityType' => ['label' => 'Tipo de entidade'],
            'entity'     => ['label' => 'Entidade', 'depends' => ['entityType']],
            'rubrica'    => ['label' => 'Rubrica'],
            'region'     => ['label' => 'Região'],
            'regional'   => ['label' => 'Regional', 'depends' => ['region']],
            'city'       => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterOptions(): array
    {
        $cities = $this->rememberFilterOptions('cities', fn () => City::query()
            ->whereNotNull('rdMunicipio')
            ->orderBy('municipio')
            ->get(['rdMunicipio', 'municipio', 'regiao', 'baseConstrucao'])
            ->map(fn ($c) => [
                'v' => (string) $c->rdMunicipio,
                'l' => (string) ($c->municipio ?: $c->rdMunicipio),
                'p' => ['region' => (string) $c->regiao, 'regional' => (string) $c->baseConstrucao],
            ])->unique('v')->values()->all());

        return [
            'entityType' => $this->rememberFilterOptions('entityType', fn () => EntityType::query()
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn ($t) => ['v' => (string) $t->id, 'l' => (string) $t->name])->all()),
            'entity' => $this->rememberFilterOptions('entity', fn () => Entity::query()
                ->orderBy('name')->get(['id', 'name', 'entity_type_id'])
                ->map(fn ($e) => ['v' => (string) $e->id, 'l' => (string) $e->name, 'p' => ['entityType' => (string) $e->entity_type_id]])->all()),
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => Note::query()
                ->whereNotNull('rubrica')->where('rubrica', '!=', '')
                ->distinct()->orderBy('rubrica')->pluck('rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
            'region' => collect($cities)->pluck('p.region')->filter()->unique()->sort()->values()
                ->map(fn ($r) => ['v' => $r, 'l' => $r])->all(),
            'regional' => collect($cities)->map(fn ($c) => $c['p'])->filter(fn ($p) => $p['regional'] !== '')
                ->unique('regional')->sortBy('regional')->values()
                ->map(fn ($p) => ['v' => $p['regional'], 'l' => $p['regional'], 'p' => ['region' => $p['region']]])->all(),
            'city' => $cities,
        ];
    }

    public function hasCriteria(): bool
    {
        return $this->hasListFilters() || filled(trim((string) $this->search)) || !empty($this->multisearch)
            || $this->typeNote !== '' || $this->statusFilter !== '';
    }

    /** "Limpar tudo": filtros, busca (simples e em massa), tipo e status. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters       = [];
        $this->search        = '';
        $this->advanceSearch = '';
        $this->multisearch   = [];
        $this->typeNote      = '';
        $this->statusFilter  = '';
        $this->afterListFiltersChanged();
    }

    public function updatedTypeNote(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /** Códigos de município (rdMunicipio) escolhidos por Região, Regional e/ou Município. */
    private function cityCodes(): array
    {
        $cities    = $this->filterValues('city');
        $regions   = $this->filterValues('region');
        $regionals = $this->filterValues('regional');

        if (!$cities && !$regions && !$regionals) {
            return [];
        }

        return City::query()
            ->when($cities, fn ($q) => $q->whereIn('rdMunicipio', $cities))
            ->when($regions, fn ($q) => $q->whereIn('regiao', $regions))
            ->when($regionals, fn ($q) => $q->whereIn('baseConstrucao', $regionals))
            ->pluck('rdMunicipio')->filter()->unique()->values()->all();
    }

    public function exportToExcel()
    {
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Download em andamento...',
            'timer'    => 2000,
        ]);

        return Excel::download(new ProtocolsList($this->notes), date('Ymd_his') . '_protocols.xlsx');
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

            $storage = app(FileStorageService::class);

            if ($storage->exists($file)) {
                return $storage->download($file, $file->file_name);
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
        $user = User::find(Auth()->User()->id);

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
            'company_id'  => $user->company_id,
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
                'note_id'       => $this->note->id,
                'service_id'    => $production->service_id,
                'user_id'       => Auth()->User()->id,
                'info'          => "Usuário {$user->name} atribuiu a Nota/OV.",
                'status'        => 2,
                'production_id' => $production->id,
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
            ->excludeCanceledFullDone()
            ->whereDoesntHave('ExternalOrganReleases', function ($releaseQuery) {
                $releaseQuery->whereNull('released_at');
            });

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
        $hasMultiSearch  = !empty($this->multisearch);
        $hasAnySearch    = $hasDirectSearch || $hasMultiSearch;

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

        if ($rubricas = $this->filterValues('rubrica')) {
            $query->whereIn('rubrica', $rubricas);
        }

        if ($this->filterValues('city') || $this->filterValues('region') || $this->filterValues('regional')) {
            // Município da nota = código SAP (nexp) das cidades escolhidas; sem nenhuma correspondente, a lista fica vazia.
            $codes = $this->cityCodes();
            $codes ? $query->whereIn('nexp', $codes) : $query->whereRaw('1 = 0');
        }

        // Entidade: as marcadas, ou todas do(s) tipo(s) de entidade marcado(s) (era o filtro "Tipos de Entidade" que repassava ao de Entidades).
        $entityIds = $this->filterValues('entity');

        if (!$entityIds && ($types = $this->filterValues('entityType'))) {
            $entityIds = Entity::query()->whereIn('entity_type_id', $types)->pluck('id')->map(fn ($id) => (string) $id)->all() ?: ['0'];
        }

        if ($entityIds) {
            $query->whereHas('externals', function ($q) use ($entityIds) {
                $q->whereIn('entity_id', $entityIds);
            });
        }

        $query->with([
            'Files:id,note_id,service_id,file_name,path,ext',
            'Files.Note:id,note',
            'Files.Service:id,uuid,service',
            'Externals:id,note_id,entidade,status,completed,updated_at',
            'Externals.Protocols:id,external_id,protocol,created_at',
            'Externals.Comments:id,external_id,title,created_at,updated_at',
        ])->orderBy($this->column, $this->direction);

        return $query;

    }

    public function render()
    {
        $lists = $this->notes->paginate($this->perPage);

        return view('livewire.services.oexterno.main', [
            'lists'     => $lists,
            'rows'      => $lists->getCollection()->map(fn (Note $note) => $this->rowFor($note))->values(),
            'updatedAt' => Cache::remember('ops-bancoupdate-exists', 60, fn () => Bancoupdate::query()->exists()) ? $this->last_update : null,
        ]);
    }

    /** A Nota vira a linha da tela: totais de protocolo, tons de idade e pasta atual já prontos (a view só desenha). */
    private function rowFor(Note $note): array
    {
        $externals = $note->Externals;
        $last      = $externals->last();
        $lastProto = $last?->protocols?->last();
        $total     = $externals->count();
        $done      = $externals->where('completed', true)->count();

        $statusDays = $note->dt_status?->copy()->startOfDay()->diffInDays() ?? 0;
        $totalDays  = $note->dt_created ? $note->dt_created->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;

        return [
            'id'          => $note->id,
            'note'        => $note->note,
            'files'       => $note->Files,
            'proto'       => ['done' => $done, 'total' => $total, 'tone' => $total === 0 ? 'muted' : ($done === $total ? 'green' : 'red')],
            'lastProto'   => $lastProto?->protocol,
            'lastProtoAt' => $lastProto?->created_at,
            'entity'      => $last?->entidade,
            'rubrica'     => $note->rubrica,
            'group2'      => $note->group2,
            'city'        => $note->lexp,
            'order'       => $note->numPedido,
            'status'      => $note->nstats,
            'centerjob'   => $note->centerjob,
            'folder'      => $this->resolveFolderLabel($note),
            'statusAt'    => $note->dt_status,
            'statusDays'  => $statusDays,
            'statusTone'  => $statusDays > 120 ? 'red' : ($statusDays <= 60 ? 'green' : 'amber'),
            'createdAt'   => $note->dt_created,
            'totalDays'   => $totalDays,
            'totalTone'   => $totalDays === null ? 'muted' : ($totalDays > 30 ? 'red' : ($totalDays < 27 ? 'green' : 'amber')),
        ];
    }

    public function resolveFolderLabel(Note $note): array
    {
        $hasExternal     = $note->Externals && $note->Externals->isNotEmpty();
        $pendingExternal = $hasExternal
            && $note->Externals->contains(fn ($external) => (bool) !$external->completed);

        if (!$pendingExternal) {
            return ['label' => 'A PROTOCOLAR', 'tone' => 'green'];
        }

        $pendingStatuses = collect($note->Externals)
            ->filter(fn ($external) => (bool) !$external->completed)
            ->pluck('status')
            ->filter()
            ->values();

        if ($pendingStatuses->contains('AGUARDANDO_PAGAMENTO')) {
            return ['label' => 'AGUARDANDO PAGAMENTO', 'tone' => 'red'];
        }

        if ($pendingStatuses->contains('AGUARDANDO_TAXA')) {
            return ['label' => 'AGUARDANDO TAXA', 'tone' => 'teal'];
        }

        if ($pendingStatuses->contains('AGUARDANDO_ORGAO')) {
            return ['label' => 'AGUARDANDO ORGAO EXTERNO', 'tone' => 'amber'];
        }

        return ['label' => 'STATUS INDEFINIDO', 'tone' => 'muted'];
    }
}
