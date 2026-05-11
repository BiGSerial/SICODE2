<?php

namespace App\Http\Livewire\Services\Oexterno;

use App\Models\External;
use App\Models\File;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\WildcardFormatter;

class ListTax extends Component
{
    use WithPagination;
    use WildcardFormatter;

    protected $paginationTheme = 'bootstrap';

    public $service;
    public $perPage = 50;

    public $search   = '';
    public $status   = null;
    public $types    = [];
    public $dateFrom = null;
    public $dateTo   = null;
    public $entities = [];
    public $rubricas = [];

    protected $queryString = [
        'search'   => ['except' => ''],
        'status'   => ['except' => null],
        'types'    => ['except' => []],
        'entities' => ['except' => []],
        'rubricas' => ['except' => []],
        'dateFrom' => ['except' => null, 'as' => 'de'],
        'dateTo'   => ['except' => null, 'as' => 'ate'],
        'perPage'  => ['except' => 50],
    ];

    public function updated($name, $value)
    {
        if (in_array($name, ['search','status','types','entities','rubricas','dateFrom','dateTo','perPage'])) {
            $this->resetPage();
        }
    }

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function cleanFilters()
    {
        $this->search   = '';
        $this->status   = null;
        $this->types    = [];
        $this->dateFrom = null;
        $this->dateTo   = null;
        $this->entities = [];
        $this->rubricas = [];
        $this->resetPage();
    }

    public function redirectTo($note)
    {
        if (!$this->service || !$note) {
            $this->dispatchBrowserEvent('toast', [
                'title' => 'Erro de navegação',
                'message' => 'Não foi possível navegar para a nota especificada.',
                'type' => 'error'
            ]);
            return;
        }

        return redirect()->to(
            route('services.protocolNote', [
                'service' => $this->service->uuid,
                'note'    => $note,
            ])
        );
    }

    public function downloadFile(File $file)
    {
        if ($file && Storage::exists($file->path)) {
            return Storage::download($file->path);
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'ARQUIVO NÃO ENCONTRADO!',
            'timer'    => 5000,
        ]);
    }

    public function baseQuery()
    {
        return External::query()
            ->select([
                'externals.id',
                'externals.note_id',
                'externals.entity_id',
                'externals.user_id',
                'externals.status',
                'externals.completed',
                'externals.created_at',
                'externals.updated_at',
            ])
            ->selectSub(
                DB::table('external_comments')
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('external_id', 'externals.id'),
                'last_comment_at'
            )
            ->where(function ($w) {
                $w->whereIn('status', ['AGUARDANDO_TAXA']);
            })
            ->where('completed', false)
            ->with([
                'Note:id,note,lexp,centerjob,nstats,rubrica',
                'Note.Files:id,note_id,service_id,file_name,path,ext',
                'Entity:id,entity_type_id,name,nick',
                'Entity.Type:id,name',
                'User:id,name',
                'User.Company:id,name',
            ]);
    }

    public function getQueryProperty()
    {
        $q = $this->baseQuery();

        // ---- Mapa de filtros (onde procurar) ----
        $map = [
            // SEARCH: defina colunas locais e relações/colunas
            'search' => [
                'value'  => $this->search,
                'filter' => new \App\Support\Filters\MultiRelatedTextSearchSimple(
                    relations: [
                        'Note'        => ['note','lexp'],
                        'Entity'      => ['name','nick'],
                        'Entity.Type' => ['name'],
                        'User'        => ['name'],
                    ],
                    localCols: [
                        'externals.status',      // exemplo: buscar também em status local

                    ],
                    minLen: 2
                ),
            ],

            // STATUS: igualdade simples na coluna totalmente qualificada
            'status' => [
                'value'  => $this->status,
                'filter' => new \App\Support\Filters\InArray('externals.status'),
            ],

                // TYPES: lista (IN) por entity_type_id
            'entities' => [
                    'value'  => $this->entities,
                    'filter' => new \App\Support\Filters\InArray('entity', 'id'),
            ],
            'types' => [
                    'value'  => $this->types,
                    'filter' => new \App\Support\Filters\InArray('entity', 'entity_type_id'),
            ],
            'rubricas' => [
                    'value'  => $this->rubricas,
                    'filter' => new \App\Support\Filters\InArray('note', 'rubrica'),
            ],

            // DATA: faixa em externals.created_at
            'date_range' => [
                'value'  => ['from' => $this->dateFrom, 'to' => $this->dateTo],
                'filter' => new \App\Support\Filters\DateRange('externals.created_at'),
        ],
        ];

        // ---- Aplica todos os filtros em ordem ----
        $q = \App\Support\Filters\QueryFilterApplier::apply($q, $map);

        // ---- Ordenação final (como você já fazia) ----
        $q->orderByRaw('last_comment_at IS NULL, last_comment_at ASC')
          ->orderBy('externals.id');

        return $q;
    }

    public function getListsProperty()
    {
        return $this->query->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.services.oexterno.list-tax', [
            'lists' => $this->lists,
        ]);
    }
}
