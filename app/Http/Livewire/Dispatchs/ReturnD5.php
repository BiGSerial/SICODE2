<?php

namespace App\Http\Livewire\Dispatchs;

use App\Http\Livewire\Concerns\WithListFilters;
use App\Jobs\Dispatchs\ExportReturnD5Job;
use App\Models\{City, File, Note, Service, User};
use App\Services\Files\FileStorageService;
use App\Services\Reclaims\{ReclaimRowPresenter, ReturnD5Query};
use Livewire\{Component, WithPagination};

/**
 * Lista de Retorno Interno (RI) do Despacho. Lista operacional: padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md.
 * O componente guarda o estado da tela (busca, origem, atalho, filtros, seleção) e as ações; a consulta é do ReturnD5Query (a mesma do Excel)
 * e a linha pronta é do ReclaimRowPresenter.
 */
class ReturnD5 extends Component
{
    use WithListFilters;
    use WithPagination;

    public const PAGE_SIZES = [25, 50, 100, 250, 500];

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $service;

    public $search = '';

    /** Origem do retorno (`ReturnD5Query::ORIGINS`); vazio = todas. */
    public $origin = '';

    /** Atalho "Sem atribuição": só retornos que ainda não têm atividade. */
    public $notAtt = false;

    /** Usuário escolhido na Ocupação (menu lateral). */
    public $filterUser = '';

    /** @var array<int, string> ids marcados (caixas `wire:model.defer`; a contagem é do navegador) */
    public $selected = [];

    public $sortField = 'created_at';

    public $sortDirection = 'asc';

    protected $queryString = [
        'search'        => ['except' => '', 'as' => 'busca'],
        'perPage'       => ['as' => 'pagina'],
        'origin'        => ['except' => '', 'as' => 'origem'],
        'filterUser'    => ['except' => ''],
        'sortField'     => ['except' => 'created_at', 'as' => 'ordenar'],
        'sortDirection' => ['except' => 'asc'],
        'notAtt'        => ['except' => false],
        'filters'       => ['except' => [], 'as' => 'f'],
    ];

    protected $listeners = [
        'refresh_list',
        'refreshComponent' => '$refresh',
        'cleanAll'         => 'closeall',
        'filterUser'       => 'filterUser',
    ];

    public function mount($service): void
    {
        $this->service = Service::where('uuid', $service)->firstOrFail();

        $this->perPage = in_array((int) $this->perPage, self::PAGE_SIZES, true) ? (int) $this->perPage : 50;
        $this->origin  = array_key_exists((string) $this->origin, ReturnD5Query::ORIGINS) ? $this->origin : '';
        $this->filters = $this->normalizeFilters($this->filters);
    }

    // ---------- Estado da tela ----------

    public function updated($name): void
    {
        // A seleção (`selected`, caixas `wire:model.defer`) chega como atualização antes da ação em lote: não pode ser zerada aqui.
        if ($name === 'page' || str_starts_with((string) $name, 'selected')) {
            return;
        }

        $this->resetPage();
        $this->selected = [];
    }

    public function setNotAtt(): void
    {
        $this->notAtt   = !$this->notAtt;
        $this->selected = [];
        $this->resetPage();
    }

    public function sortBy($field): void
    {
        if (!in_array($field, ReturnD5Query::SORTABLE, true)) {
            return;
        }

        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField     = $field;
        $this->resetPage();
    }

    public function filterUser($user_id): void
    {
        $this->filterUser = $user_id;
        $this->selected   = [];
        $this->resetPage();
    }

    public function cleanUser(): void
    {
        $this->filterUser = '';
        $this->selected   = [];
        $this->resetPage();
    }

    /** "Limpar tudo": filtros, busca, origem, atalho e usuário. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->search     = '';
        $this->origin     = '';
        $this->notAtt     = false;
        $this->filterUser = '';
        $this->filters    = [];

        $this->afterListFiltersChanged();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function refresh_list(): void
    {
        $this->selected = [];
        $this->resetPage();
    }

    /** Chamado pelos modais de atribuição (`cleanAll`) depois de agir. */
    public function closeall(): void
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->selected = [];
        $this->resetPage();
        $this->emit('refresh_list');
    }

    // ---------- Ações ----------

    public function massAssign(): void
    {
        if (!$this->selected) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'SEM OBRAS SELECIONADAS',
                'html'     => 'Verifique a seleção das obras e tente novamente.',
                'timer'    => 5000,
            ]);

            return;
        }

        $this->emitTo('dispatchs.common.return-in-mass', 'goOpenMassAtt', $this->selected);
    }

    /** Excel por Job, nos mesmos filtros da tela; `$onlySelected` leva só as linhas marcadas. */
    public function exportToExcel(bool $onlySelected = false): void
    {
        if ($onlySelected && !$this->selected) {
            return;
        }

        ExportReturnD5Job::dispatch(
            $this->criteria() + ['service_uuid' => $this->service->uuid, 'ids' => $onlySelected ? array_map('intval', $this->selected) : []],
            auth()->id()
        )
            ->onConnection((string) config('queue.channels.exports.connection', 'database'))
            ->onQueue((string) config('queue.channels.exports.queue', 'exports'));

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>A lista de retornos internos está sendo gerada em Excel" . ($onlySelected ? ' (só as linhas selecionadas)' : ', nos filtros atuais') . ". Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {
            $storage = app(FileStorageService::class);

            if ($storage->exists($file)) {
                return $storage->download($file, $file->file_name);
            }
        }
    }

    // ---------- Filtros (WithListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterOptions(): array
    {
        $cities = $this->rememberFilterOptions('cities', fn () => City::query()
            ->whereNotNull('rdMunicipio')
            ->orderBy('municipio')
            ->get(['rdMunicipio', 'municipio', 'regiao', 'regional'])
            ->map(fn ($c) => [
                'v' => (string) $c->rdMunicipio,
                'l' => (string) ($c->municipio ?: $c->rdMunicipio),
                'p' => ['region' => (string) $c->regiao, 'regional' => (string) $c->regional],
            ])->unique('v')->values()->all());

        return [
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => Note::query()
                ->whereIn('id', \App\Models\Reclaim::query()->where('completed', false)->where('service_id', $this->service->uuid)->select('note_id'))
                ->whereNotNull('rubrica')->where('rubrica', '!=', '')
                ->distinct()->orderBy('rubrica')->pluck('rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
            'region' => collect($cities)->pluck('p.region')->filter()->unique()->sort()->values()
                ->map(fn ($r) => ['v' => $r, 'l' => $r])->all(),
            'regional' => collect($cities)->map(fn ($c) => $c['p'])->filter(fn ($p) => $p['regional'] !== '')
                ->unique(fn ($p) => $p['region'] . '|' . $p['regional'])->sortBy('regional')->values()
                ->map(fn ($p) => ['v' => $p['regional'], 'l' => $p['regional'], 'p' => ['region' => $p['region']]])->all(),
            'city' => $cities,
        ];
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . ':' . $this->service->uuid;
    }

    // ---------- Consulta ----------

    /** @return array<string, mixed> critérios no formato do ReturnD5Query (tela e Excel) */
    private function criteria(): array
    {
        return [
            'search'    => $this->search,
            'filters'   => $this->filters,
            'origin'    => $this->origin,
            'notAtt'    => (bool) $this->notAtt,
            'user'      => $this->filterUser,
            'sort'      => $this->sortField,
            'direction' => $this->sortDirection,
        ];
    }

    public function render()
    {
        $query = app(ReturnD5Query::class);
        $lists = $query->query($this->service->uuid, $this->criteria())->paginate((int) $this->perPage);
        $now   = now();

        return view('livewire.dispatchs.return-d5', [
            'lists'       => $lists,
            'rows'        => collect($lists->items())->map(fn ($reclaim) => ReclaimRowPresenter::listRow($reclaim, $now)),
            'hasCriteria' => $this->hasListFilters() || filled($this->search) || $this->origin !== '' || $this->notAtt || filled($this->filterUser),
            'userName'    => filled($this->filterUser) ? User::whereKey($this->filterUser)->value('name') : null,
            'origins'     => ReturnD5Query::ORIGINS,
            'tacitFiles'  => $query->tacitFileIds($lists->getCollection()->flatMap(fn ($r) => $r->Note?->Files?->pluck('id') ?? [])),
        ]);
    }
}
