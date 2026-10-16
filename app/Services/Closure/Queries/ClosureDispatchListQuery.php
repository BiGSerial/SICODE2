<?php

namespace App\Services\Closure\Queries;

use App\Models\{City, Company, Order, User};
use App\Models\Closure\ClosureOrderState;
use App\Services\Closure\ClosureMetaClassifier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Listagem do Despacho de Encerramento: uma linha por ORDEM (docs/encerramento/REFORMULACAO_ENCERRAMENTO.md §5.1).
 * Joins/subselects únicos (sem N+1); o tipo P/M/F vem de ClosureMetaClassifier::withMetaType.
 */
class ClosureDispatchListQuery
{
    public const NO_STATE = 'NONE';

    private ClosureMetaClassifier $classifier;

    public function __construct(?ClosureMetaClassifier $classifier = null)
    {
        $this->classifier = $classifier ?? new ClosureMetaClassifier();
    }

    /**
     * @param array{search?: string, types?: array<int, string>, status?: ?string, companyId?: ?string, cityId?: ?string, assigneeId?: ?string, onlyUndispatched?: bool} $filters
     */
    public function query(array $filters = []): Builder
    {
        $query = Order::query()
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
            ->selectRaw('(select max(op.partial_id) from order_partial op where op.order_id = orders.id) as partial_id');

        $this->classifier->withMetaType($query);

        $query->where(function (Builder $scope) {
            $scope->where(fn (Builder $eligible) => $this->classifier->scopeEligible($eligible))
                ->orWhereNotNull('s.id')
                ->orWhereExists(fn ($sub) => $sub->selectRaw('1')->from('closure_targets as t')->whereColumn('t.order_id', 'orders.id'));
        });

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $term = '%' . $search . '%';
            $query->where(fn (Builder $q) => $q->where('orders.ordem', 'like', $term)->orWhere('notes.note', 'like', $term));
        }

        $types = array_values(array_filter(array_map(fn ($t) => ClosureMetaClassifier::letter($t), (array) ($filters['types'] ?? []))));

        if ($types !== []) {
            $this->classifier->whereMetaType($query, $types);
        }

        $status = $filters['status'] ?? null;

        if ($status === self::NO_STATE) {
            $query->whereNull('s.id');
        } elseif ($status) {
            $query->where('s.operational_status', $status);
        }

        if (!empty($filters['companyId'])) {
            $query->where('s.current_holder_company_id', $filters['companyId']);
        }

        if (!empty($filters['cityId'])) {
            $query->where('notes.nexp', $filters['cityId']);
        }

        if (!empty($filters['assigneeId'])) {
            $query->where('s.assigned_user_id', $filters['assigneeId']);
        }

        if (!empty($filters['onlyUndispatched'])) {
            $query->whereNull('s.id');
        }

        return $query->orderBy('orders.ordem')->orderBy('orders.id');
    }

    public function paginate(array $filters, int $perPage, ?int $page = null): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Prévia de elegibilidade por Ordem (espelha as recusas de ClosureDispatchService + conflito de vínculo).
     *
     * @param array<int, int|string> $orderIds
     * @return array<int, array{id: int, ordem: ?string, note: ?string, type: ?string, eligible: bool, reason: ?string}>
     */
    public function eligibility(array $orderIds, Company $company, ?User $assignee): array
    {
        $orders = Order::query()->with(['Note:id,note,canceled', 'ClosureTarget'])
            ->whereIn('id', array_map('intval', $orderIds))->orderBy('ordem')->get();
        $types  = $this->classifier->typesFor($orders);
        $states = ClosureOrderState::query()->with('Production')->whereIn('order_id', $orders->pluck('id'))->get()->keyBy('order_id');
        $rows   = [];

        foreach ($orders as $order) {
            $type   = $types->get($order->id);
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
                'eligible' => $reason === null,
                'reason'   => $reason,
            ];
        }

        return $rows;
    }

    /** @return Collection<int, Company> */
    public function companies(): Collection
    {
        return Company::query()->orderBy('name')->get(['id', 'name']);
    }

    /** @return Collection<int, City> */
    public function cities(): Collection
    {
        return City::query()->whereNotNull('municipio')
            ->whereIn('rdMunicipio', fn ($sub) => $sub->select('notes.nexp')->from('notes')
                ->join('orders', 'orders.note_id', '=', 'notes.id')->whereNotNull('notes.nexp')->distinct())
            ->orderBy('municipio')->get(['rdMunicipio', 'municipio']);
    }

    /** @return Collection<int, User> responsáveis que já têm Ordens de Encerramento */
    public function assignees(): Collection
    {
        return User::query()
            ->whereIn('id', fn ($sub) => $sub->select('assigned_user_id')->from('closure_order_states')->whereNotNull('assigned_user_id'))
            ->orderBy('name')->get(['id', 'name']);
    }
}
