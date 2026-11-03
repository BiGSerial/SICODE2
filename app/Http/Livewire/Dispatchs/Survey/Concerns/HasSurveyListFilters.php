<?php

namespace App\Http\Livewire\Dispatchs\Survey\Concerns;

use App\Custom\RuleBuilder;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\{City, Note};
use App\Services\Design\BlockEvaluator;
use App\Support\SicodeRules;
use Illuminate\Pagination\{LengthAwarePaginator, Paginator};
use Illuminate\Support\Facades\Cache;

/**
 * Peças compartilhadas pelas listas operacionais do Levantamento (`Main` e `PartnerMain`) no padrão de listas
 * operacionais (docs/PADRAO_LISTAS_OPERACIONAIS.md): filtros, consulta única (tela, Excel e seleção), memo do bloqueio por linha.
 * Usa as propriedades públicas do componente que a inclui (`service`, `search`, `multiSearch`, `note_type`, `not_assigned`...).
 */
trait HasSurveyListFilters
{
    use WithNoteListFilters;

    /** @var array<string, array> Memo de `rowDispatchState()` (zerado a cada render). */
    private array $dispatchStateMemo = [];

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

    protected function listFilterCacheScope(): string
    {
        return static::class . $this->service->uuid;
    }

    protected function listFilterOptions(): array
    {
        $statuses = $this->service->Status->where('exclusion', false)->pluck('value')->unique()->values()->all();

        return [
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => Note::query()
                ->when($statuses, fn ($q) => $q->whereIn('nstats', $statuses))
                ->whereNotNull('rubrica')->where('rubrica', '!=', '')
                ->distinct()->orderBy('rubrica')->pluck('rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
        ] + $this->locationFilterOptions();
    }

    /** Códigos de município (`notes.nexp`) dos filtros Região/Regional/Município; vazio sem filtro de localidade. */
    public function getBaseProperty(): array
    {
        $region = $this->filterValues('region');
        $local  = $this->filterValues('regional');
        $city   = $this->filterValues('city');

        if (!$region && !$local && !$city) {
            return [];
        }

        return City::query()
            ->when($region, fn ($q) => $q->whereIn('regiao', $region))
            ->when($local, fn ($q) => $q->whereIn('baseConstrucao', $local))
            ->when($city, fn ($q) => $q->whereIn('rdMunicipio', $city))
            ->pluck('rdMunicipio')->filter()->unique()->values()->all();
    }

    /** "Limpar tudo": filtros, busca (simples e em massa), tipo e atalho. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters             = [];
        $this->search              = '';
        $this->multiSearch         = [];
        $this->advanceSearch       = '';
        $this->note_type           = '';
        $this->not_assigned        = false;
        $this->bulkSearchAnyStatus = false;
        $this->afterListFiltersChanged();
    }

    public function updatedSearch(): void
    {
        if (filled($this->search)) {
            $this->multiSearch = [];
        }

        $this->resetPage();
    }

    public function updatedNoteType(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function filterStatus(): void
    {
        $this->not_assigned = !$this->not_assigned;
        $this->resetPage();
    }

    // ---------- Bloqueio por linha (memo) ----------

    public function needBlock(Note $note): array
    {
        return $this->rowDispatchState($note)['eval'];
    }

    /**
     * Avaliação de bloqueio + pilha da empresa de uma linha, feita UMA vez por requisição
     * (a tela, o "selecionar todos" e a verificação do mestre liam isso 3x por linha).
     *
     * @return array{eval: array, stack: ?\App\Models\Production, canDispatch: bool}
     */
    public function rowDispatchState(Note $note): array
    {
        return $this->dispatchStateMemo[$note->id] ??= (function () use ($note) {
            $eval  = app(BlockEvaluator::class)->evaluate($note, $this->service);
            $stack = SicodeRules::openCompanyStackProductionFor($note, Auth()->User(), $this->service->uuid);

            return [
                'eval'        => $eval,
                'stack'       => $stack,
                'canDispatch' => !$eval['block'] || (bool) ($eval['command'] ?? false) || (bool) $stack,
            ];
        })();
    }

    private function canDispatchNote(Note $note): bool
    {
        return $this->rowDispatchState($note)['canDispatch'];
    }

    // ---------- Seleção ----------

    /** Marca/desmarca as linhas da página que podem ser despachadas. */
    public function setSelectAll(): void
    {
        $page = $this->paginateLists()->getCollection();
        $set  = array_fill_keys(array_map('strval', $this->selected), true);

        foreach ($page as $note) {
            $id = (string) $note->id;

            if ($this->selectall) {
                if ($this->canDispatchNote($note)) {
                    $set[$id] = true;
                }
            } else {
                unset($set[$id]);
            }
        }

        $this->selected = array_values(array_keys($set));
    }

    /** `selectall` fica ligado quando TODAS as linhas despacháveis da página estão marcadas. */
    protected function recomputeSelectAllFor(iterable $items): void
    {
        $eligible = [];

        foreach ($items as $note) {
            if ($this->canDispatchNote($note)) {
                $eligible[] = (string) $note->id;
            }
        }

        $this->selectall = $eligible !== [] && array_diff($eligible, array_map('strval', $this->selected)) === [];
    }

    // ---------- Consulta única (tela, Excel e seleção) ----------

    /**
     * Página da lista. A contagem total (consulta cara: as regras de status de `RuleBuilder` varrem `notes`) fica em cache de 60 s
     * por consulta + usuário, então paginar, ordenar a seleção ou digitar não paga a contagem de novo.
     */
    protected function paginateLists(): LengthAwarePaginator
    {
        $query   = $this->lists;
        $page    = Paginator::resolveCurrentPage('page');
        $perPage = (int) $this->perPage;
        $key     = 'survey-list-total:' . md5($query->toSql() . json_encode($query->getBindings()) . Auth()->id());
        $total   = Cache::remember($key, 60, fn () => $query->toBase()->getCountForPagination());
        $items   = $total ? $query->forPage($page, $perPage)->get() : $query->getModel()->newCollection();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path'     => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    /** Relações lidas pela view, carregadas de uma vez (sem consulta por linha). */
    protected function listRelations(): array
    {
        return [
            'Productions' => fn ($q) => $q->where('service_id', $this->service->uuid)->with(['User:id,name', 'Company:id,name']),
            'Wpas',
        ];
    }

    public function getListsProperty()
    {
        $multi = !empty($this->multiSearch);
        $query = Note::query()->excludeCanceledFullDone();

        SicodeRules::applyContractDispatchMainVisibility(
            $query,
            Auth()->User(),
            $this->service->uuid,
            fn ($statusQuery) => $this->bulkSearchAnyStatus && $multi
                ? null
                : RuleBuilder::applyRules($statusQuery, $this->service->Status)
        );

        if (!$multi && filled($this->search)) {
            $like = '%' . trim($this->search) . '%';

            $query->where(function ($q) use ($like) {
                $q->where('note', 'like', $like)
                    ->orWhere('material', 'like', $like)
                    ->orWhere('numPedido', 'like', $like)
                    ->orWhere('group1', 'like', $like)
                    ->orWhere('group2', 'like', $like)
                    ->orWhere('group4', 'like', $like)
                    ->orWhere('group5', 'like', $like);
            });
        }

        if ($multi) {
            // Busca em massa procura pelas notas informadas, qualquer que seja o recorte de localidade.
            $query->whereIn('note', $this->multiSearch);
        } elseif ($base = $this->base) {
            $query->where(fn ($q) => $q->whereIn('nexp', $base)->orWhereNull('nexp'));
        }

        if ($rubrica = $this->filterValues('rubrica')) {
            $query->where(fn ($q) => $q->whereIn('rubrica', $rubrica)->orWhereNull('rubrica'));
        }

        if ($this->note_type) {
            $query->where(fn ($q) => $q->where('type_note', $this->note_type)->orWhereNull('type_note'));
        }

        if ($this->not_assigned) {
            $query->whereDoesntHave('Productions', fn ($sub) => $sub->where('service_id', $this->service->uuid)->where('confirmed', false));
        }

        return $query->with($this->listRelations())
            ->orderBy('is45', 'DESC')
            ->orderBy('type_note', 'DESC')
            ->orderBy('days_left');
    }
}
