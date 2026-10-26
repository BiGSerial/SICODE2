<?php

namespace App\Services\Closure\Queries;

use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, Order, User};
use App\Services\Closure\{ClosureEligibleOrders, ClosureMetaClassifier};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\{LengthAwarePaginator, Paginator};
use Illuminate\Support\Collection;

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

        $scoped = $this->scopedIds($filters, $set);

        if ($scoped === []) {
            return [];
        }

        $query = Order::query()
            ->select('orders.id')
            ->selectRaw('s.id as state_id')
            ->join('notes', 'notes.id', '=', 'orders.note_id')
            ->leftJoin('closure_order_states as s', 's.order_id', '=', 'orders.id')
            ->whereIn('orders.id', $scoped)
            // Ordem já encerrada no SAP (ENT%/ENC%) não é mais trabalho: fica fora das listas.
            ->where(fn (Builder $q) => $q->whereNull('orders.statusSist')->orWhere(fn (Builder $w) => $w->where('orders.statusSist', 'not like', 'ENT%')->where('orders.statusSist', 'not like', 'ENC%')));

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $term = '%' . $search . '%';
            $query->where(fn (Builder $q) => $q->where('orders.ordem', 'like', $term)->orWhere('notes.note', 'like', $term));
        }

        // Busca em massa: lista de Notas/Ordens coladas pelo usuário, correspondência exata.
        $bulk = array_values(array_filter(array_map('strval', (array) ($filters['bulk'] ?? []))));

        if ($bulk !== []) {
            $query->where(fn (Builder $q) => $q->whereIn('orders.ordem', $bulk)->orWhereIn('notes.note', $bulk));
        }

        $status = $filters['status'] ?? null;

        if ($status === self::NO_STATE) {
            $query->whereNull('s.id');
        } elseif ($status) {
            $query->where('s.operational_status', $status);
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
     * Ids do conjunto que passam nos filtros em memória (Região/Regional/Município e Status usuário), sobre as
     * facetas cacheadas. Sem esses filtros devolve o conjunto inteiro.
     *
     * @return array<int, int>
     */
    private function scopedIds(array $filters, array $set): array
    {
        $codes = $this->allowedCityCodes($filters);
        $su    = array_values(array_filter(array_map(fn ($v) => ClosureEligibleOrders::statusUserPrefix((string) $v), (array) ($filters['statusUsers'] ?? []))));

        if ($codes === null && $su === []) {
            return array_keys($set);
        }

        $codes  = $codes === null ? null : array_flip($codes);
        $orders = $this->eligible->facets()['orders'];
        $ids    = [];

        foreach (array_keys($set) as $id) {
            $facet = $orders[$id] ?? null;

            if ($codes !== null && ($facet === null || $facet['n'] === null || !isset($codes[$facet['n']]))) {
                continue;
            }

            if ($su !== [] && !$this->matchesStatusUser($facet['s'] ?? '', $su)) {
                continue;
            }

            $ids[] = $id;
        }

        return $ids;
    }

    /** Token a token: 'JAND INVE' casa 'JAND INVE' e 'JAND INVE BLOQ', não 'JAND INVEX'. */
    private function matchesStatusUser(string $statusUser, array $wanted): bool
    {
        foreach ($wanted as $prefix) {
            if ($statusUser === $prefix || str_starts_with($statusUser, $prefix . ' ')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Códigos de município (rdMunicipio) permitidos: os escolhidos; senão todos os de Região/Regional; null = sem restrição.
     *
     * @return ?array<int, string>
     */
    private function allowedCityCodes(array $filters): ?array
    {
        $cityCodes = $this->strings($filters['cityCodes'] ?? []);

        if ($cityCodes !== []) {
            return $cityCodes;
        }

        $regions   = $this->strings($filters['regions'] ?? []);
        $regionals = $this->strings($filters['regionals'] ?? []);

        if ($regions === [] && $regionals === []) {
            return null;
        }

        $codes = [];

        foreach ($this->eligible->facets()['cities'] as $code => $city) {
            if (($regions === [] || in_array($city['g'], $regions, true)) && ($regionals === [] || in_array($city['r'], $regionals, true))) {
                $codes[] = (string) $code;
            }
        }

        return $codes;
    }

    /** @return array<int, string> */
    private function strings($values): array
    {
        return array_values(array_unique(array_filter(array_map('strval', array_filter((array) $values, 'is_scalar')), fn ($v) => $v !== '')));
    }

    /**
     * Opções (com nº de Ordens do conjunto elegível) de Região, Regional, Município e Status usuário, para os
     * multi-dropdowns. Vêm das facetas cacheadas: nada de SQL com cache quente. Regional/Município carregam
     * região/regional para a cascata no cliente.
     *
     * @return array{regions: array, regionals: array, cities: array, statusUsers: array}
     */
    public function filterOptions(): array
    {
        $facets = $this->eligible->facets();
        $cities = [];
        $su     = [];

        foreach ($this->eligible->all() as $id => $_) {
            $facet = $facets['orders'][$id] ?? null;

            if ($facet === null) {
                continue;
            }

            if ($facet['n'] !== null && isset($facets['cities'][$facet['n']])) {
                $cities[$facet['n']] = ($cities[$facet['n']] ?? 0) + 1;
            }

            if ($facet['s'] !== '') {
                $su[$facet['s']] = ($su[$facet['s']] ?? 0) + 1;
            }
        }

        $regions   = [];
        $regionals = [];
        $cityOpts  = [];

        foreach ($cities as $code => $n) {
            $city                = $facets['cities'][$code];
            $regions[$city['g']] = ($regions[$city['g']] ?? 0) + $n;
            $key                 = $city['g'] . '|' . $city['r'];
            $regionals[$key]     = ['v' => $city['r'], 'g' => $city['g'], 'n' => ($regionals[$key]['n'] ?? 0) + $n];
            $cityOpts[]          = ['v' => (string) $code, 'l' => $city['m'], 'g' => $city['g'], 'r' => $city['r'], 'n' => $n];
        }

        ksort($regions);
        ksort($su);
        usort($cityOpts, fn ($a, $b) => strcmp($a['l'], $b['l']));
        $regionals = array_values($regionals);
        usort($regionals, fn ($a, $b) => strcmp($a['v'], $b['v']));

        return [
            'regions'     => array_map(fn ($v, $n) => ['v' => (string) $v, 'l' => (string) $v, 'n' => $n], array_keys($regions), $regions),
            'regionals'   => array_map(fn ($r) => $r + ['l' => $r['v']], $regionals),
            'cities'      => array_map(fn ($c) => $c + [], $cityOpts),
            'statusUsers' => array_map(fn ($v, $n) => ['v' => (string) $v, 'l' => (string) $v, 'n' => $n], array_keys($su), $su),
        ];
    }

    /**
     * Contagens (cards) e página numa só passada: 1 query leve sobre `orders.id IN (conjunto)` + 1 da página.
     *
     * @param array{search?: string, types?: array<int, string>, status?: ?string, companyId?: ?string, regions?: array, regionals?: array, cityCodes?: array, statusUsers?: array, assigneeId?: ?string, onlyUndispatched?: bool, competence?: ?string, sort?: ?string, dir?: ?string} $filters
     * @return array{counts: array{total: int, P: int, M: int, F: int, undispatched: int, selectable: int}, competences: array<string, int>, orders: LengthAwarePaginator}
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

        // Só Ordens ainda sem posse podem ser despachadas: a seleção em massa e o rótulo do botão contam apenas essas.
        $counts['selectable'] = count(array_filter($rows, fn ($row) => $row['state_id'] === null));

        ksort($competences);

        return ['counts' => $counts, 'competences' => $competences, 'orders' => $this->pageOf($rows, $set, $perPage, $page)];
    }

    /** Ids (ordenados) das Ordens que passam em todos os filtros; usado por "selecionar todas/página". */
    public function filteredIds(array $filters, ?int $limit = null, bool $undispatchedOnly = false): array
    {
        $set  = $this->eligible->all();
        $rows = $this->applyTypeFilters($this->baseRows($filters, $set), $set, $filters);

        if ($undispatchedOnly) {
            $rows = array_filter($rows, fn ($row) => $row['state_id'] === null);
        }

        $ids = array_column($rows, 'id');

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

    /**
     * Todas as linhas que passam nos filtros, já hidratadas como na lista (para exportação). Em blocos de 500 ids.
     *
     * @return Collection<int, Order>
     */
    public function exportRecords(array $filters): Collection
    {
        $set     = $this->eligible->all();
        $ids     = $this->filteredIds($filters);
        $records = new Collection();

        foreach (array_chunk($ids, 500) as $chunk) {
            $chunkRecords = $this->pageQuery($chunk)->get();
            $inform       = $this->eligible->informCompanies();
            $names        = Company::withTrashed()->whereIn('id', array_values(array_unique(array_filter(array_map(fn ($r) => $inform[$r->id] ?? '', $chunkRecords->all())))))->pluck('name', 'id');

            foreach ($chunkRecords as $record) {
                $companyId = ($inform[$record->id] ?? '') ?: null;
                $record->setAttribute('inform_company_name', $companyId ? $names->get($companyId) : null);
                $record->setAttribute('meta_type', $set[$record->id]['type'] ?? null);
                $record->setAttribute('competence', $set[$record->id]['competence'] ?? null);
            }

            $records = $records->concat($chunkRecords);
        }

        // Mesma ordem da lista (ids já vêm ordenados pelos filtros/ordenação).
        $position = array_flip($ids);

        return $records->sortBy(fn ($r) => $position[$r->id] ?? PHP_INT_MAX)->values();
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
            ->selectRaw('s.id as state_id, s.operational_status, s.priority as is_priority, s.assigned_user_id, s.current_holder_company_id, s.current_holder_user_id, s.entered_current_status_at')
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
            } elseif ($state) {
                $reason = \App\Services\Closure\ClosureDispatchService::alreadyDispatchedReason($order->ordem, $state);
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

    /** @return Collection<int, User> responsáveis que já têm Ordens de Encerramento */
    public function assignees(): Collection
    {
        return User::query()
            ->whereIn('id', fn ($sub) => $sub->select('assigned_user_id')->from('closure_order_states')->whereNotNull('assigned_user_id'))
            ->orderBy('name')->get(['id', 'name']);
    }
}
