<?php

namespace App\Http\Livewire\Partner;

use App\Http\Livewire\Concerns\WithListFilters;
use App\Http\Livewire\Partner\Concerns\{AuthorizesPartnerAccess, PartnerListToolkit};
use App\Models\{File, Partial};
use App\Services\Files\FileStorageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Livewire\{Component, WithPagination};

/**
 * Obras parciais informadas (lista de controle do portal da parceira: docs/PADRAO_LISTAS_OPERACIONAIS.md §10).
 * Escopo por empresa vem de `applyPartnerCompanyScope`; o filtro Empresa só reduz esse escopo.
 */
class PartialList extends Component
{
    use AuthorizesPartnerAccess;
    use PartnerListToolkit;
    use WithListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /**
     * Situação da parcial, na mesma ordem de precedência de `partialStatus()`: rejeitada, paga, em medição, em fiscalização, avaliação.
     * Fonte única para o filtro e os cartões; `partialStatus()` desenha o selo da linha com a mesma regra.
     */
    private const STATUS_SQL = "CASE
        WHEN COALESCE(partials.deny, 0) = 1 THEN 'rejected'
        WHEN COALESCE(partials.payment, 0) = 1 AND COALESCE(partials.allow, 0) = 1 THEN 'paid'
        WHEN COALESCE(partials.supervision, 0) = 1 AND COALESCE(partials.payment, 0) = 0 THEN 'measuring'
        WHEN COALESCE(partials.allow, 0) = 1 AND COALESCE(partials.supervision, 0) = 0 THEN 'supervising'
        ELSE 'evaluation'
    END";

    /** Chave => [rótulo, tom]. */
    private const STATUSES = [
        'evaluation'  => ['Avaliação', 'amber'],
        'supervising' => ['Em fiscalização', 'blue'],
        'measuring'   => ['Em medição', 'teal'],
        'paid'        => ['Pago', 'green'],
        'rejected'    => ['Rejeitado', 'red'],
    ];

    public $search = '';

    public $perPage = 50;

    public $dt_in = '';

    public $dt_out = '';

    protected $queryString = [
        'search'  => ['except' => ''],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
        'dt_in'   => ['except' => '', 'as' => 'in'],
        'dt_out'  => ['except' => '', 'as' => 'out'],
        'filters' => ['except' => [], 'as' => 'f'],
    ];

    public function mount(): void
    {
        $this->perPage = in_array((int) $this->perPage, [25, 50, 100, 250, 500], true) ? (int) $this->perPage : 50;
        $this->filters = $this->normalizeFilters($this->filters);
    }

    protected function listFilterDefinitions(): array
    {
        return [
            'company' => ['label' => 'Empreiteira'],
            'status'  => ['label' => 'Situação'],
        ];
    }

    protected function listFilterOptions(): array
    {
        return [
            'company' => $this->companyFilterOptions(),
            'status'  => collect(self::STATUSES)->map(fn ($meta, $key) => ['v' => $key, 'l' => $meta[0]])->values()->all(),
        ];
    }

    public function pesquisar(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDtIn(): void
    {
        $this->resetPage();
    }

    public function updatedDtOut(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array((int) $this->perPage, [25, 50, 100, 250, 500], true) ? (int) $this->perPage : 50;
        $this->resetPage();
    }

    /** Cartões-resumo clicam como filtro rápido de situação (um por vez; clicar de novo desmarca). */
    public function quickStatus(string $status): void
    {
        $current = $this->filterValues('status');

        $this->applyListFilters(array_merge($this->filters, ['status' => $current === [$status] ? [] : [$status]]));
    }

    /** "Limpar filtros": busca, datas e filtros. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->search = '';
        $this->dt_in  = '';
        $this->dt_out = '';
        $this->clearListFilters();
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {
            $storage = app(FileStorageService::class);

            if ($storage->exists($file)) {
                return $storage->download($file, $file->file_name);
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ARQUIVO INEXISTENTE!',
                'timer'    => 5000,
            ]);
        }
    }

    /** Parciais no escopo de permissão da parceira, com busca, datas e Empresa (base da lista e dos cartões). */
    private function baseQuery(): Builder
    {
        $query = Partial::query();

        $this->applyPartnerCompanyScope($query);
        $this->applyPartnerBranchScopeToNoteRelation($query);
        $this->applyCompanyFilter($query);

        if (trim((string) $this->search) !== '') {
            $search = '%' . trim($this->search) . '%';

            $query->where(function ($q) use ($search) {
                $q->whereRelation('Note', 'note', 'like', $search)
                    ->orWhereRelation('Note.Orders', 'ordem', 'like', $search);
            });
        }

        if ($this->dt_in) {
            $query->whereDate('created_at', '>=', $this->dt_in);
        }

        if ($this->dt_out) {
            $query->whereDate('created_at', '<=', $this->dt_out);
        }

        return $query;
    }

    public function getListsProperty()
    {
        $query = $this->baseQuery();

        if ($status = $this->filterValues('status')) {
            $query->whereRaw('(' . self::STATUS_SQL . ') IN (' . implode(',', array_fill(0, count($status), '?')) . ')', $status);
        }

        return $query
            ->with(['Note.Files.service', 'orders'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /** Contagem por situação numa só consulta agrupada (no recorte atual, sem o filtro de situação). Cache de 60 s. */
    private function counts(): array
    {
        $key = 'partner-partial-counts:' . auth()->id() . ':' . md5(json_encode([$this->search, $this->dt_in, $this->dt_out, $this->filterValues('company')]));

        $rows = Cache::remember($key, 60, fn () => $this->baseQuery()
            ->toBase()
            ->selectRaw(self::STATUS_SQL . ' as st, COUNT(*) as total')
            ->groupBy('st')
            ->pluck('total', 'st')
            ->all());

        return collect(array_keys(self::STATUSES))->mapWithKeys(fn ($key) => [$key => (int) ($rows[$key] ?? 0)])->all();
    }

    /** Situação da linha (selo): mesma regra do filtro (`STATUS_SQL`). */
    public function partialStatus(Partial $partial): array
    {
        $key = match (true) {
            (bool) $partial->deny                       => 'rejected',
            $partial->payment && $partial->allow        => 'paid',
            $partial->supervision && !$partial->payment => 'measuring',
            $partial->allow && !$partial->supervision   => 'supervising',
            default                                     => 'evaluation',
        };

        return ['key' => $key, 'status' => self::STATUSES[$key][0], 'tone' => self::STATUSES[$key][1]];
    }

    public function render()
    {
        $lists = $this->lists;

        // O componente de arquivos lê `$file->note`/`$file->service`: reaproveita a nota já carregada em vez de consultar por arquivo.
        $files = $lists->getCollection()->flatMap(function ($partial) {
            $partial->Note?->Files->each(fn ($file) => $file->setRelation('note', $partial->Note));

            return $partial->Note?->Files ?? collect();
        });

        return view('livewire.partner.partial-list', [
            'lists'       => $lists,
            'counts'      => $this->counts(),
            'statuses'    => self::STATUSES,
            'tacitFiles'  => $this->tacitFileIdsFor($files->pluck('id')),
            'hasCriteria' => $this->hasListFilters() || trim((string) $this->search) !== '' || $this->dt_in || $this->dt_out,
        ]);
    }
}
