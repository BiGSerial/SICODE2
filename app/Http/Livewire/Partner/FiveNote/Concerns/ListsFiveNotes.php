<?php

namespace App\Http\Livewire\Partner\FiveNote\Concerns;

use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\WithListFilters;
use App\Http\Livewire\Partner\Concerns\{AuthorizesPartnerAccess, PartnerListToolkit};
use App\Jobs\ExportFiveNotesJob;
use App\Models\FiveNote;
use App\Traits\WildcardFormmater;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

/**
 * Base das listas de D5 do portal da parceira (em espera, rejeitadas e histórico): lista de controle, docs/PADRAO_LISTAS_OPERACIONAIS.md §10.
 * Cada tela só define o recorte (`baseQuery`), a ordenação e o tipo de exportação; busca, período, "Mostrar" (abas), filtro Empresa e Excel ficam aqui.
 */
trait ListsFiveNotes
{
    use AuthorizesPartnerAccess;
    use PartnerListToolkit;
    use TextFormatter;
    use WildcardFormmater;
    use WithListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 25;

    public $multiSearch = '';

    public $multipleSearch = [];

    public $search = '';

    public $month;

    public $startDate;

    public $endDate;

    protected $listeners = [
        'refresh_component' => '$refresh',
    ];

    /** Recorte da tela (visibilidade, concluída ou não, devolvida ou não), já com o escopo da empresa. */
    abstract protected function baseQuery(): Builder;

    abstract protected function exportKind(): string;

    public function mount(): void
    {
        $this->filters = $this->normalizeFilters($this->filters);
    }

    protected function listFilterDefinitions(): array
    {
        return count($this->companyOptions()) > 1 ? ['company' => ['label' => 'Empresa']] : [];
    }

    protected function listFilterOptions(): array
    {
        return count($this->companyOptions()) > 1 ? ['company' => $this->companyOptions()] : [];
    }

    private ?array $companyOptionsMemo = null;

    private function companyOptions(): array
    {
        return $this->companyOptionsMemo ??= $this->rememberFilterOptions('company', fn () => $this->companyFilterOptions());
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . ':' . auth()->id();
    }

    protected function listFilterCacheTtl(): int
    {
        return 60;
    }

    /** Todos os filtros menos "Mostrar" (metas atuais / passivas): é o recorte que as abas contam. */
    protected function filteredQuery(): Builder
    {
        $query = $this->baseQuery();

        $this->applyCompanyFilter($query);

        return $query
            ->when($this->search, function ($query) {
                $query->where(fn ($q) => $this->applyTerm($q, $this->formatWithWildcard($this->search)));
            })
            ->when($this->multipleSearch, function ($query) {
                $query->where(function ($q) {
                    foreach ($this->multipleSearch as $item) {
                        $this->applyTerm($q, $this->formatWithWildcard($item));
                    }
                });
            })
            ->when($this->startDate, fn ($query) => $query->whereDate('dispatch_at', '>=', $this->startDate))
            ->when($this->endDate, fn ($query) => $query->whereDate('dispatch_at', '<=', $this->endDate))
            ->when($this->month, fn ($query) => $query->whereMonth('dispatch_at', $this->month));
    }

    private function applyTerm(Builder $q, object $search): void
    {
        $q->orWhere('note_d5', $search->type, $search->search)
            ->orWhere('pep', $search->type, $search->search)
            ->orWhere('loc_install', $search->type, $search->search)
            ->orWhereRelation('Note', fn ($q) => $q->where('note', $search->type, $search->search))
            ->orWhereRelation('Note.Orders', fn ($q) => $q->where('ordem', $search->type, $search->search));
    }

    public function getFivesProperty()
    {
        return $this->filteredQuery()
            ->when($this->passiveFilter === 'current', fn ($q) => $q->where('isPassive', false))
            ->when($this->passiveFilter === 'passive', fn ($q) => $q->where('isPassive', true));
    }

    /** Contagem de cada aba ("Mostrar") no mesmo recorte da lista, numa consulta agrupada só. */
    protected function passiveCounts(): array
    {
        $row = $this->filteredQuery()->reorder()
            ->selectRaw('COALESCE(SUM(isPassive = 0), 0) as current_total, COALESCE(SUM(isPassive = 1), 0) as passive_total, COUNT(*) as all_total')
            ->first();

        return [
            'current' => (int) $row->current_total,
            'passive' => (int) $row->passive_total,
            'all'     => (int) $row->all_total,
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->multipleSearch = [];
        $this->multiSearch    = '';
    }

    public function updatedPassiveFilter(): void
    {
        $this->passiveFilter = in_array($this->passiveFilter, ['current', 'passive', 'all'], true) ? $this->passiveFilter : 'current';
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array((int) $this->perPage, [25, 50, 100], true) ? (int) $this->perPage : 25;
        $this->resetPage();
    }

    public function updatedMonth(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function multiSearch(): void
    {
        $this->resetPage();
        $this->search         = '';
        $this->multipleSearch = $this->formatTextToArray($this->multiSearch);
        $this->dispatchBrowserEvent('hideModal');
    }

    public function toClean(): void
    {
        $this->resetPage();
        $this->multipleSearch = [];
        $this->multiSearch    = '';
        $this->month          = '';
        $this->startDate      = '';
        $this->endDate        = '';
        $this->search         = '';
        $this->clearListFilters();
    }

    public function hasCriteria(): bool
    {
        return $this->hasListFilters()
            || filled($this->search) || $this->multipleSearch !== []
            || filled($this->month) || filled($this->startDate) || filled($this->endDate);
    }

    public function exportExcel(): void
    {
        $this->authorizePartnerAccess('d5_notes.export');

        $userId = auth()->id();

        if (!$userId) {
            return;
        }

        ExportFiveNotesJob::dispatch($this->exportPayload(), $userId, $this->exportKind());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO INICIADA',
            'text'     => 'Você receberá uma notificação com o link para download.',
            'timer'    => 5000,
        ]);
    }

    protected function exportPayload(): array
    {
        return [
            'search'               => $this->search,
            'multipleSearch'       => $this->multipleSearch,
            'month'                => $this->month,
            'startDate'            => $this->startDate,
            'endDate'              => $this->endDate,
            'passiveFilter'        => $this->passiveFilter,
            'partnerCompanyFilter' => $this->partnerCompanyFilter,
            'companyIds'           => $this->filterValues('company'),
        ];
    }

    /** Prazo de execução da D5 (dias desde o despacho): verde até 4, âmbar até 9, vermelho a partir de 10 (as faixas que a tela já usava). */
    protected function dispatchDays(FiveNote $five): ?int
    {
        return $five->dispatch_at ? (int) $five->dispatch_at->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;
    }
}
