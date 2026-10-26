<?php

namespace App\Services\Closure\Queries;

use App\Models\Closure\{ClosureIssue, ClosureOrderState};
use App\Models\{Company, User};
use App\Services\Closure\{ClosureEligibleOrders, ClosureStatusUserRouteService};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Consultas das caixas de Medição e Fiscalização.
 *  - acompanhando (visão): TODAS as Ordens elegíveis do Encerramento (despachadas ou não, a mesma base do Despacho) cujo status usuário
 *    ATUAL (orders.statusUser) está mapeado para a área. Consulta ao vivo: a Ordem sai da caixa sozinha quando o status muda.
 *  - despachadas (ação): pendências (closure_issues) abertas para a área.
 */
class ClosureInboxQuery
{
    /** Situações em que a pendência ainda pede algo da área de destino. */
    public const OPEN_ISSUE_STATUSES = [ClosureIssue::STATUS_OPEN, ClosureIssue::STATUS_REQUESTED, ClosureIssue::STATUS_RETURNED];

    public function __construct(
        private ClosureStatusUserRouteService $routes,
        private ClosureDispatchListQuery $list,
        private ClosureEligibleOrders $eligible,
    ) {
    }

    /**
     * Filtros da lista do Despacho para a caixa: os status escolhidos (sempre dentro dos mapeados) ou todos os mapeados.
     * Sem status mapeado a caixa fica vazia (nunca "tudo").
     *
     * @param array{search?: string, prefixes?: array<int, string>, type?: string, bulk?: array<int, string>, holderUserId?: ?string, companyId?: ?string} $filters
     *
     * @return array<string, mixed>
     */
    public function listFilters(string $area, array $filters = []): array
    {
        $mapped   = $this->routes->prefixesFor($area);
        $selected = array_values(array_intersect($mapped, array_map(
            fn ($p) => mb_strtoupper(ClosureEligibleOrders::statusUserPrefix((string) $p)),
            (array) ($filters['prefixes'] ?? []),
        )));

        $type = $filters['type'] ?? 'all';

        return [
            // Lista vazia no filtro do Despacho significaria "sem restrição"; sem mapa, um prefixo impossível mantém a caixa vazia.
            'statusUsers' => $mapped === [] ? ['__SEM_STATUS_MAPEADO__'] : ($selected !== [] ? $selected : $mapped),
            'search'      => (string) ($filters['search'] ?? ''),
            'bulk'        => array_values(array_filter($filters['bulk'] ?? [])),
            'types'       => in_array($type, ['P', 'M', 'F'], true) ? [$type] : [],
            'assigneeId'  => ($filters['holderUserId'] ?? null) ?: null,
            'companyId'   => ($filters['companyId'] ?? null) ?: null,
        ];
    }

    /** Página de Ordens (mesma linha da lista do Despacho: valor da obra, tipo, competência, posse...). */
    public function accompanying(string $area, array $filters, int $perPage, ?int $page = null): LengthAwarePaginator
    {
        return $this->list->paginate($this->listFilters($area, $filters), $perPage, $page);
    }

    /** @return array<int, int> ids de todas as Ordens da caixa com os filtros (busca em massa e exportação) */
    public function accompanyingIds(string $area, array $filters): array
    {
        return $this->list->filteredIds($this->listFilters($area, $filters));
    }

    /**
     * Quantas Ordens elegíveis há em cada status mapeado (cartões-filtro), sem os demais filtros.
     *
     * @return array<string, int> prefixo => quantidade (todos os mapeados, inclusive 0)
     */
    public function accompanyingCounts(string $area): array
    {
        $mapped = $this->routes->prefixesFor($area);
        $counts = array_fill_keys($mapped, 0);

        if ($mapped === []) {
            return $counts;
        }

        $facets = $this->eligible->facets()['orders'];

        foreach (array_keys($this->eligible->all()) as $id) {
            $status = (string) ($facets[$id]['s'] ?? '');

            if ($status !== '' && isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    /**
     * Opções dos filtros Responsável (quem tem as Ordens da caixa) e Empresa (do informe), sem aplicar esses dois filtros.
     *
     * @return array{users: \Illuminate\Support\Collection, companies: \Illuminate\Support\Collection}
     */
    public function filterOptions(string $area, array $filters = []): array
    {
        $ids = $this->list->filteredIds($this->listFilters($area, ['holderUserId' => null, 'companyId' => null] + $filters));

        $userIds = $ids === [] ? [] : ClosureOrderState::query()->whereIn('order_id', $ids)->whereNotNull('assigned_user_id')->distinct()->pluck('assigned_user_id')->all();

        $informCompanies = $this->eligible->informCompanies();
        $companyIds      = array_values(array_unique(array_filter(array_map(fn ($id) => $informCompanies[$id] ?? '', $ids))));

        return [
            'users'     => $userIds === [] ? collect() : User::query()->whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']),
            'companies' => $companyIds === [] ? collect() : Company::withTrashed()->whereIn('id', $companyIds)->orderBy('name')->get(['id', 'name']),
        ];
    }

    /** Pendências da área (todas as situações; o filtro de situação é da tela). */
    public function dispatched(string $area, ?string $status = null): Builder
    {
        $query = ClosureIssue::query()
            ->where('destination_area', $area)
            ->with(['Order.Note', 'Category', 'Subcategory']);

        if ($status === 'open') {
            $query->whereIn('status', self::OPEN_ISSUE_STATUSES);
        } elseif ($status === 'done') {
            $query->whereNotIn('status', self::OPEN_ISSUE_STATUSES);
        }

        return $query->orderByDesc('requested_at')->orderByDesc('id');
    }
}
