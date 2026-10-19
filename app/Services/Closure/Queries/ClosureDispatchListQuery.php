<?php

namespace App\Services\Closure\Queries;

use App\Models\{City, Company, Order, User};
use App\Models\Closure\ClosureOrderState;
use App\Services\Closure\{ClosureEligibleOrders, ClosureMetaClassifier};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\{LengthAwarePaginator, Paginator};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Listagem do Despacho de Encerramento: uma linha por ORDEM (docs/encerramento/REFORMULACAO_ENCERRAMENTO.md §5.1).
 * Joins/subselects únicos (sem N+1); o tipo P/M/F vem de ClosureMetaClassifier::withMetaType.
 */
class ClosureDispatchListQuery
{
    public const NO_STATE = 'NONE';

    private ClosureMetaClassifier $classifier;

    private ClosureEligibleOrders $eligible;

    public function __construct(?ClosureMetaClassifier $classifier = null, ?ClosureEligibleOrders $eligible = null)
    {
        $this->classifier = $classifier ?? new ClosureMetaClassifier();
        $this->eligible   = $eligible ?? new ClosureEligibleOrders($this->classifier);
    }

    /**
     * Lista ordenada (ordem, id) das Ordens do conjunto que passam nos filtros de SQL (busca, status, município,
     * responsável) e no de empresa do informe (mapa em memória). Tipo e "só não despachadas" são aplicados em memória por `overview()`, porque o
     * tipo vem do conjunto cacheado (ClosureEligibleOrders).
     *
     * @return array<int, array{id: int, state_id: ?int}>
     */
    private function baseRows(array $filters, array $set): array
    {
        if ($set === []) {
            return [];
        }

        $query = Order::query()
            ->select('orders.id')
            ->selectRaw('s.id as state_id')
            ->join('notes', 'notes.id', '=', 'orders.note_id')
            ->leftJoin('closure_order_states as s', 's.order_id', '=', 'orders.id')
            ->whereIn('orders.id', array_keys($set));

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $term = '%' . $search . '%';
            $query->where(fn (Builder $q) => $q->where('orders.ordem', 'like', $term)->orWhere('notes.note', 'like', $term));
        }

        $status = $filters['status'] ?? null;

        if ($status === self::NO_STATE) {
            $query->whereNull('s.id');
        } elseif ($status) {
            $query->where('s.operational_status', $status);
        }

        if (!empty($filters['cityId'])) {
            $query->where('notes.nexp', $filters['cityId']);
        }

        if (!empty($filters['assigneeId'])) {
            $query->where('s.assigned_user_id', $filters['assigneeId']);
        }

        $rows = $query->orderBy('orders.ordem')->orderBy('orders.id')->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'state_id' => $row->state_id ? (int) $row->state_id : null])->all();

        // Empresa = a da relação com o informe (mapa cacheado em ClosureEligibleOrders), não a de quem está com a Ordem.
        if (!empty($filters['companyId'])) {
            $inform = $this->eligible->informCompanies();
            $wanted = (string) $filters['companyId'];
            $rows   = array_values(array_filter($rows, fn ($row) => ($inform[$row['id']] ?? '') === $wanted));
        }

        return $rows;
    }

    /**
     * Contagens (cards) e página numa só passada: 1 query leve sobre `orders.id IN (conjunto)` + 1 da página.
     *
     * @param array{search?: string, types?: array<int, string>, status?: ?string, companyId?: ?string, cityId?: ?string, assigneeId?: ?string, onlyUndispatched?: bool, competence?: ?string, sort?: ?string, dir?: ?string} $filters
     * @return array{counts: array{total: int, P: int, M: int, F: int, undispatched: int}, competences: array<string, int>, orders: LengthAwarePaginator}
     */
    public function overview(array $filters, int $perPage, ?int $page = null): array
    {
        $set  = $this->eligible->all();
        $base = $this->baseRows($filters, $set);

        $counts      = ['total' => 0, 'P' => 0, 'M' => 0, 'F' => 0, 'undispatched' => 0];
        $competences = [];

        foreach ($base as $row) {
            $counts['total']++;
            $comp = $set[$row['id']]['competence'] ?? null;

            if ($comp !== null) {
                $competences[$comp] = ($competences[$comp] ?? 0) + 1;
            }

            $counts['undispatched'] += $row['state_id'] === null ? 1 : 0;
            $type = $set[$row['id']]['type'] ?? null;

            if ($type !== null) {
                $counts[$type]++;
            }
        }

        $rows = $this->applyTypeFilters($base, $set, $filters);

        ksort($competences);

        return ['counts' => $counts, 'competences' => $competences, 'orders' => $this->pageOf($rows, $set, $perPage, $page)];
    }

    /** Ids (ordenados) das Ordens que passam em todos os filtros; usado por "selecionar todas/página". */
    public function filteredIds(array $filters, ?int $limit = null): array
    {
        $set  = $this->eligible->all();
        $rows = $this->applyTypeFilters($this->baseRows($filters, $set), $set, $filters);
        $ids  = array_column($rows, 'id');

        return $limit !== null ? array_slice($ids, 0, $limit) : $ids;
    }

    /**
     * Dados mínimos por Ordem para o modal montar a lista no cliente (1 query; tipo/competência vêm do conjunto cacheado).
     *
     * @param array<int, int> $ids
     * @return array<int, array{id: int, ordem: ?string, note: ?string, type: ?string, comp: string, company: ?string}>
     */
    public function briefs(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $set    = $this->eligible->all();
        $inform = $this->eligible->informCompanies();
        $orders = Order::query()->join('notes', 'notes.id', '=', 'orders.note_id')
            ->whereIn('orders.id', $ids)->get(['orders.id', 'orders.ordem', 'notes.note as note_number'])->keyBy('id');

        return collect($ids)->filter(fn ($id) => $orders->has($id))->map(fn ($id) => [
            'id'      => (int) $id,
            'ordem'   => $orders[$id]->ordem,
            'note'    => $orders[$id]->note_number,
            'type'    => $set[$id]['type'] ?? null,
            'comp'    => ClosureMetaClassifier::competenceLabel($set[$id]['competence'] ?? null),
            'company' => ($inform[$id] ?? '') ?: null,
        ])->values()->all();
    }

    /** Ids da página informada (mesma ordenação/filtros da lista). */
    public function pageIds(array $filters, int $perPage, int $page): array
    {
        return array_slice($this->filteredIds($filters), max(0, ($page - 1) * $perPage), $perPage);
    }

    /**
     * Contagens para os cards-resumo, respeitando os filtros, exceto tipo e "só não despachadas"
     * (os próprios cards são esses filtros).
     *
     * @return array{total: int, P: int, M: int, F: int, undispatched: int}
     */
    public function counts(array $filters = []): array
    {
        unset($filters['types'], $filters['onlyUndispatched']);

        return $this->overview($filters, 1, 1)['counts'];
    }

    public function paginate(array $filters, int $perPage, ?int $page = null): LengthAwarePaginator
    {
        return $this->overview($filters, $perPage, $page)['orders'];
    }

    /** @param array<int, array{id: int, state_id: ?int}> $rows */
    private function applyTypeFilters(array $rows, array $set, array $filters): array
    {
        $types = array_values(array_filter(array_map(fn ($t) => ClosureMetaClassifier::letter($t), (array) ($filters['types'] ?? []))));

        if ($types !== []) {
            $rows = array_filter($rows, fn ($row) => in_array($set[$row['id']]['type'] ?? null, $types, true));
        }

        if (!empty($filters['onlyUndispatched'])) {
            $rows = array_filter($rows, fn ($row) => $row['state_id'] === null);
        }

        $competence = $filters['competence'] ?? null;

        if ($competence) {
            $rows = array_filter($rows, fn ($row) => ($set[$row['id']]['competence'] ?? null) === $competence);
        }

        $rows = array_values($rows);

        // Ordenação por competência (YYYY-MM compara como texto; sem competência vai ao fim). usort é estável: empate mantém (ordem, id).
        if (($filters['sort'] ?? null) === 'competence') {
            $sign = ($filters['dir'] ?? 'asc') === 'desc' ? -1 : 1;
            usort($rows, function ($a, $b) use ($set, $sign) {
                $x = $set[$a['id']]['competence'] ?? null;
                $y = $set[$b['id']]['competence'] ?? null;

                if ($x === $y) {
                    return 0;
                }

                return $x === null ? 1 : ($y === null ? -1 : $sign * strcmp($x, $y));
            });
        }

        return $rows;
    }

    /** @param array<int, array{id: int, state_id: ?int}> $rows */
    private function pageOf(array $rows, array $set, int $perPage, ?int $page): LengthAwarePaginator
    {
        $page    = max(1, (int) ($page ?: Paginator::resolveCurrentPage()));
        $ids     = array_column(array_slice($rows, ($page - 1) * $perPage, $perPage), 'id');
        $records = $ids === [] ? collect() : $this->pageQuery($ids)->get();

        $inform = $records->isEmpty() ? [] : $this->eligible->informCompanies();
        $names  = Company::withTrashed()->whereIn('id', array_values(array_unique(array_filter(array_map(fn ($r) => $inform[$r->id] ?? '', $records->all())))))->pluck('name', 'id');

        foreach ($records as $record) {
            $companyId = ($inform[$record->id] ?? '') ?: null;
            $record->setAttribute('inform_company_id', $companyId);
            $record->setAttribute('inform_company_name', $companyId ? $names->get($companyId) : null);
            $record->setAttribute('meta_type', $set[$record->id]['type'] ?? null);
            $record->setAttribute('competence', $set[$record->id]['competence'] ?? null);
        }

        return new LengthAwarePaginator($records, count($rows), $perPage, $page, [
            'path'     => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }

    /** Colunas pesadas (subselects) só para as linhas da página. */
    private function pageQuery(array $ids): Builder
    {
        return Order::query()
            ->select('orders.*')
            ->join('notes', 'notes.id', '=', 'orders.note_id')
            ->leftJoin('closure_order_states as s', 's.order_id', '=', 'orders.id')
            ->selectRaw('notes.note as note_number, notes.nexp as city_code')
            ->selectRaw('s.id as state_id, s.operational_status, s.priority as is_priority, s.assigned_user_id, s.current_holder_company_id')
            ->selectRaw('(select c.municipio from cities c where c.rdMunicipio = notes.nexp limit 1) as city_name')
            ->selectRaw('(select co.name from companies co where co.id = s.current_holder_company_id limit 1) as holder_company_name')
            ->selectRaw('(select u.name from users u where u.id = s.assigned_user_id limit 1) as assignee_name')
            ->selectRaw("(select max(o.fimReal) from operations o where o.order_id = orders.id and o.operacao = '0020' and o.status like 'CONF%' and o.fimReal is not null) as fim_real_op20")
            ->selectRaw('(select count(*) from operations o where o.order_id = orders.id) as ops_total')
            ->selectRaw("(select count(*) from operations o where o.order_id = orders.id and o.status like 'CONF%') as ops_conf")
            ->selectRaw('(select max(op.partial_id) from order_partial op where op.order_id = orders.id) as partial_id')
            ->whereIn('orders.id', $ids)
            ->orderBy('orders.ordem')->orderBy('orders.id');
    }

    /**
     * Prévia de elegibilidade por Ordem (espelha as recusas de ClosureDispatchService + conflito de vínculo).
     * Sem empresa escolhida, só as recusas independentes do destino são avaliadas.
     *
     * @param array<int, int|string> $orderIds
     * @return array<int, array{id: int, ordem: ?string, note: ?string, type: ?string, comp: string, eligible: bool, reason: ?string}>
     */
    public function eligibility(array $orderIds, ?Company $company, ?User $assignee): array
    {
        $orders = Order::query()->with(['Note:id,note,canceled', 'ClosureTarget'])
            ->whereIn('id', array_map('intval', $orderIds))->orderBy('ordem')->get();
        $class  = $this->classifier->classify($orders);
        $states = ClosureOrderState::query()->with('Production')->whereIn('order_id', $orders->pluck('id'))->get()->keyBy('order_id');
        $rows   = [];

        foreach ($orders as $order) {
            $type   = $class->get($order->id)['type'] ?? null;
            $state  = $states->get($order->id);
            $reason = null;

            if ($order->canceled) {
                $reason = 'Ordem cancelada.';
            } elseif ($order->Note?->canceled) {
                $reason = 'Nota/OV cancelada.';
            } elseif (str_starts_with((string) $order->statusSist, 'ENTE') || str_starts_with((string) $order->statusSist, 'ENCE')) {
                $reason = 'Ordem já encerrada no SAP.';
            } elseif (!$order->ClosureTarget && !$type) {
                $reason = 'Ordem fora da meta de Encerramento.';
            } elseif ($state && $company) {
                $production = $state->Production;
                $same       = $production
                    && (string) $production->company_id === (string) $company->id
                    && (string) $production->user_id === (string) $assignee?->id;
                $reason = $same ? null : 'Ordem já vinculada a outro despacho.';
            }

            $rows[] = [
                'id'       => $order->id,
                'ordem'    => $order->ordem,
                'note'     => $order->Note?->note,
                'type'     => $type,
                'comp'     => ClosureMetaClassifier::competenceLabel($class->get($order->id)['competence'] ?? null),
                'eligible' => $reason === null,
                'reason'   => $reason,
            ];
        }

        return $rows;
    }

    /** @return Collection<int, Company> empresas que têm informe/parcial em alguma Ordem do conjunto (filtro "Empresa") */
    public function companies(): Collection
    {
        $ids = array_values(array_unique(array_filter($this->eligible->informCompanies())));

        return $ids === [] ? new Collection() : Company::withTrashed()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Municípios das Ordens do conjunto (cacheado com o mesmo TTL do conjunto).
     *
     * @return Collection<int, object{rdMunicipio: string, municipio: string}>
     */
    public function cities(): Collection
    {
        $rows = Cache::remember(ClosureEligibleOrders::citiesCacheKey(), ClosureEligibleOrders::TTL, function () {
            $codes = [];

            foreach (array_chunk(array_keys($this->eligible->all()), 1000) as $chunk) {
                $codes = array_merge($codes, Order::query()->join('notes', 'notes.id', '=', 'orders.note_id')
                    ->whereIn('orders.id', $chunk)->whereNotNull('notes.nexp')->distinct()->pluck('notes.nexp')->all());
            }

            return City::query()->whereNotNull('municipio')->whereIn('rdMunicipio', array_values(array_unique($codes)))
                ->orderBy('municipio')->get(['rdMunicipio', 'municipio'])
                ->map(fn ($city) => ['rdMunicipio' => $city->rdMunicipio, 'municipio' => $city->municipio])->all();
        });

        return collect($rows)->map(fn (array $city) => (object) $city);
    }

    /** @return Collection<int, User> responsáveis que já têm Ordens de Encerramento */
    public function assignees(): Collection
    {
        return User::query()
            ->whereIn('id', fn ($sub) => $sub->select('assigned_user_id')->from('closure_order_states')->whereNotNull('assigned_user_id'))
            ->orderBy('name')->get(['id', 'name']);
    }
}
