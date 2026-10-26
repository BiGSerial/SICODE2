<?php

namespace App\Services\Closure\Analytics;

use App\Enum\ClosureOperationalStatus as Status;
use App\Models\Closure\{ClosureIssue, ClosureOrderState, ClosurePriorityRequest};
use App\Models\{Company, User};
use App\Services\Closure\ClosureMetaClassifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consultas agregadas do painel analítico do despachante do Encerramento.
 *
 * Unidade = Ordem (closure_order_states). "Usuário" = responsável (assigned_user_id),
 * que é preservado enquanto a Ordem aguarda a parceira. Tipo P/M/F pelo
 * ClosureMetaClassifier (congelado em closed_meta_type quando existir).
 * Todas as consultas são agregadas em SQL (sem N+1).
 *
 * Filtros aceitos: type (P/M/F), company_id (empresa que detém a Ordem agora),
 * from/to (YYYY-MM-DD, sobre entered_current_status_at).
 */
class ClosureDispatcherAnalytics
{
    /** Colunas de status da matriz, na ordem de exibição. */
    public const MATRIX_STATUSES = [
        'IN_PROGRESS',
        'ASSIGNED',
        'PAUSED',
        'WAITING_PARTNER',
        'DEPOSIT_BLOCKED',
        'DEPOSIT_RELEASED',
        'FINISHED',
        'WAITING',
        'AVAILABLE',
    ];

    public const NO_USER = 'none';

    public function __construct(private readonly ClosureMetaClassifier $classifier)
    {
    }

    /**
     * Matriz usuário × tipo × status.
     *
     * @param array<string, mixed> $filters
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, mixed>}
     */
    public function matrix(array $filters = []): array
    {
        [$sql, $bindings] = $this->classifier->metaTypeSql();

        $raw = $this->activeBase($filters)
            ->selectRaw(
                "closure_order_states.assigned_user_id as u_id, ({$sql}) as m_type, closure_order_states.operational_status as st_code, count(*) as total",
                $bindings
            )
            ->groupBy('u_id', 'm_type', 'st_code')
            ->get();

        $names = User::query()->withTrashed()
            ->whereIn('id', $raw->pluck('u_id')->filter()->unique()->all())
            ->pluck('name', 'id');

        $rows   = [];
        $totals = $this->emptyRow('Total', null);

        foreach ($raw as $item) {
            $key = $item->u_id ?? self::NO_USER;

            $rows[$key] ??= $this->emptyRow($item->u_id ? ($names[$item->u_id] ?? 'Usuário removido') : 'Sem responsável (pilha)', $item->u_id);

            $this->accumulate($rows[$key], $item->m_type, $item->st_code, (int) $item->total);
            $this->accumulate($totals, $item->m_type, $item->st_code, (int) $item->total);
        }

        uasort($rows, fn ($a, $b) => [$b['total'], $a['name']] <=> [$a['total'], $b['name']]);

        return ['rows' => array_values($rows), 'totals' => $totals];
    }

    /**
     * Cartões de resumo. O % de meta ignora tipo/período (é do ciclo corrente),
     * mas respeita a empresa.
     *
     * @param array<string, mixed> $filters
     * @param array{rows: array<int, array<string, mixed>>, totals: array<string, mixed>}|null $matrix
     * @return array<string, mixed>
     */
    public function summary(array $filters = [], ?array $matrix = null): array
    {
        $totals = ($matrix ?? $this->matrix($filters))['totals'];

        $closedMeta = ClosureOrderState::query()
            ->where('operational_status', Status::CLOSED->value)
            ->where('closed_meta_type', ClosureMetaClassifier::TYPE_META)
            ->where('closed_meta_period', ClosureMetaClassifier::periodFor())
            ->when($filters['company_id'] ?? null, fn ($q, $company) => $q->where('current_holder_company_id', $company))
            ->count();

        $openMeta = $this->activeBase(array_diff_key($filters, ['type' => 1, 'from' => 1, 'to' => 1]))
            ->tap(fn ($q) => $this->classifier->whereMetaType($q, ClosureMetaClassifier::TYPE_META))
            ->count();

        $metaTotal = $openMeta + $closedMeta;

        return [
            'total'           => $totals['total'],
            'types'           => $totals['types'],
            'meta_closed'     => $closedMeta,
            'meta_total'      => $metaTotal,
            'meta_percent'    => $metaTotal > 0 ? round($closedMeta / $metaTotal * 100, 1) : 0.0,
            'waiting_partner' => $totals['status']['WAITING_PARTNER'] ?? 0,
            'paused'          => $totals['status']['PAUSED'] ?? 0,
            'finished'        => $totals['status']['FINISHED'] ?? 0,
            'waiting'         => $totals['status']['WAITING'] ?? 0,
        ];
    }

    /**
     * Interação usuário × parceira a partir de closure_issues (+ events para devoluções).
     * Tempos em segundos.
     *
     * @return array{partners: Collection<int, array<string, mixed>>, users: Collection<int, array<string, mixed>>}
     */
    public function partnerInteraction(): array
    {
        $now = now()->toDateTimeString();

        // Desde quando a parceira está com a bola: solicitação (REQUESTED) ou última devolução (RETURNED).
        $since = "case closure_issues.status when 'REQUESTED' then closure_issues.requested_at"
            . " when 'RETURNED' then (select max(e.created_at) from closure_issue_events e where e.closure_issue_id = closure_issues.id and e.type = 'RETURNED_FOR_CORRECTION')"
            . ' else null end';

        $awaiting = "closure_issues.status in ('REQUESTED','RETURNED')";
        $waitSecs = "case when {$awaiting} then timestampdiff(SECOND, {$since}, ?) end";
        $respSecs = 'case when closure_issues.requested_at is not null and closure_issues.responded_at is not null'
            . ' then timestampdiff(SECOND, closure_issues.requested_at, closure_issues.responded_at) end';

        $partners = ClosureIssue::query()
            ->selectRaw(
                'closure_issues.destination_company_id as company_id,'
                . " sum(closure_issues.status <> 'RESOLVED') as open_count,"
                . " sum({$awaiting}) as awaiting_count,"
                . " avg({$waitSecs}) as avg_wait, max({$waitSecs}) as max_wait,"
                . ' sum(closure_issues.cycle - 1) as returns_count,'
                . ' sum(closure_issues.responded_at is not null) as responded_count,'
                . " avg({$respSecs}) as avg_response",
                [$now, $now]
            )
            ->whereNotNull('closure_issues.destination_company_id')
            ->groupBy('closure_issues.destination_company_id')
            ->get();

        $companyNames = Company::query()->withTrashed()->whereIn('id', $partners->pluck('company_id')->all())->pluck('name', 'id');

        $partnerRows = $partners->map(fn ($p) => [
            'company_id'   => $p->company_id,
            'name'         => $companyNames[$p->company_id] ?? 'Empresa removida',
            'open'         => (int) $p->open_count,
            'awaiting'     => (int) $p->awaiting_count,
            'avg_wait'     => $p->avg_wait !== null ? (int) round($p->avg_wait) : null,
            'max_wait'     => $p->max_wait !== null ? (int) $p->max_wait : null,
            'returns'      => (int) $p->returns_count,
            'responded'    => (int) $p->responded_count,
            'avg_response' => $p->avg_response !== null ? (int) round($p->avg_response) : null,
        ])->sortByDesc('awaiting')->values();

        $users = ClosureIssue::query()
            ->selectRaw(
                'closure_issues.opened_by as user_id,'
                . ' sum(closure_issues.requested_at is not null) as sent_count,'
                . " sum({$awaiting}) as awaiting_count,"
                . ' sum(closure_issues.responded_at is not null) as responded_count,'
                . " avg({$respSecs}) as avg_response"
            )
            ->whereNotNull('closure_issues.opened_by')
            ->groupBy('closure_issues.opened_by')
            ->get();

        $userNames = User::query()->withTrashed()->whereIn('id', $users->pluck('user_id')->all())->pluck('name', 'id');

        $userRows = $users->map(fn ($u) => [
            'user_id'      => $u->user_id,
            'name'         => $userNames[$u->user_id] ?? 'Usuário removido',
            'sent'         => (int) $u->sent_count,
            'awaiting'     => (int) $u->awaiting_count,
            'responded'    => (int) $u->responded_count,
            'avg_response' => $u->avg_response !== null ? (int) round($u->avg_response) : null,
        ])->sortByDesc('sent')->values();

        return ['partners' => $partnerRows, 'users' => $userRows];
    }

    /** @return Collection<int, ClosurePriorityRequest> */
    public function pendingPriorityRequests(): Collection
    {
        return ClosurePriorityRequest::query()
            ->with(['Note:id,note', 'RequestedBy:id,name'])
            ->where('status', ClosurePriorityRequest::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Obras (Notas) críticas: Ordens P abertas há mais de $days dias no status atual
     * e/ou pendência parada com a parceira há mais de $days dias.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function criticalWorks(int $days = 7, int $limit = 20): Collection
    {
        $cutoff = now()->subDays($days);

        [$sql, $bindings] = $this->classifier->metaTypeSql();

        $inner = $this->activeBase([])
            ->join('notes', 'notes.id', '=', 'orders.note_id')
            ->selectRaw(
                "orders.note_id, notes.note as note_number, closure_order_states.entered_current_status_at as entered_at, ({$sql}) as meta_type",
                $bindings
            );

        $stale = DB::query()
            ->fromSub($inner, 'x')
            ->selectRaw(
                'x.note_id, x.note_number, count(*) as open_orders,'
                . " sum(x.meta_type = 'P') as passive_orders,"
                . " sum(x.meta_type = 'P' and x.entered_at < ?) as stale_passive,"
                . " max(case when x.meta_type = 'P' then timestampdiff(DAY, x.entered_at, ?) end) as oldest_days",
                [$cutoff->toDateTimeString(), now()->toDateTimeString()]
            )
            ->groupBy('x.note_id', 'x.note_number')
            ->get()
            ->keyBy('note_id');

        $since = "case closure_issues.status when 'REQUESTED' then closure_issues.requested_at"
            . " when 'RETURNED' then (select max(e.created_at) from closure_issue_events e where e.closure_issue_id = closure_issues.id and e.type = 'RETURNED_FOR_CORRECTION')"
            . ' end';

        $stuck = ClosureIssue::query()
            ->join('orders', 'orders.id', '=', 'closure_issues.order_id')
            ->whereIn('closure_issues.status', [ClosureIssue::STATUS_REQUESTED, ClosureIssue::STATUS_RETURNED])
            ->selectRaw(
                "orders.note_id, count(*) as stuck_issues, max(timestampdiff(DAY, {$since}, ?)) as stuck_days",
                [now()->toDateTimeString()]
            )
            ->whereRaw("({$since}) < ?", [$cutoff->toDateTimeString()])
            ->groupBy('orders.note_id')
            ->get()
            ->keyBy('note_id');

        $noteNumbers = $stale->map(fn ($s) => $s->note_number);

        $stuckOnly = $stuck->keys()->diff($stale->keys());

        if ($stuckOnly->isNotEmpty()) {
            $noteNumbers = $noteNumbers->union(DB::table('notes')->whereIn('id', $stuckOnly->all())->pluck('note', 'id'));
        }

        return $noteNumbers->keys()
            ->map(function ($noteId) use ($stale, $stuck, $noteNumbers) {
                $s = $stale->get($noteId);
                $k = $stuck->get($noteId);

                return [
                    'note_id'        => (int) $noteId,
                    'note'           => $noteNumbers[$noteId],
                    'open_orders'    => (int) ($s->open_orders ?? 0),
                    'passive_orders' => (int) ($s->passive_orders ?? 0),
                    'stale_passive'  => (int) ($s->stale_passive ?? 0),
                    'oldest_days'    => $s?->oldest_days !== null ? (int) $s->oldest_days : null,
                    'stuck_issues'   => (int) ($k->stuck_issues ?? 0),
                    'stuck_days'     => $k?->stuck_days !== null ? (int) $k->stuck_days : null,
                ];
            })
            ->filter(fn ($row) => $row['stale_passive'] > 0 || $row['stuck_issues'] > 0)
            ->sortByDesc(fn ($row) => max($row['oldest_days'] ?? 0, $row['stuck_days'] ?? 0))
            ->take($limit)
            ->values();
    }

    /**
     * Ordens da célula clicada na matriz.
     *
     * @param array<string, mixed> $filters
     * @param array{user?: string|null, type?: string|null, status?: string|null} $cell
     * @return Collection<int, object>
     */
    public function orders(array $filters = [], array $cell = [], int $limit = 50): Collection
    {
        [$sql, $bindings] = $this->classifier->metaTypeSql();

        $query = $this->activeBase($filters)
            ->join('notes', 'notes.id', '=', 'orders.note_id')
            ->leftJoin('users as responsible', 'responsible.id', '=', 'closure_order_states.assigned_user_id')
            ->leftJoin('companies as holder', 'holder.id', '=', 'closure_order_states.current_holder_company_id')
            ->selectRaw(
                'closure_order_states.id as state_id, closure_order_states.priority, closure_order_states.operational_status as status,'
                . ' closure_order_states.entered_current_status_at as entered_at, closure_order_states.assigned_user_id,'
                . ' closure_order_states.current_holder_company_id, closure_order_states.current_holder_user_id, orders.statusSist,'
                . ' orders.ordem, orders.note_id, notes.note as note_number,'
                . " responsible.name as responsible_name, holder.name as company_name, ({$sql}) as meta_type",
                $bindings
            );

        if (($cell['user'] ?? null) !== null && $cell['user'] !== '') {
            $cell['user'] === self::NO_USER
                ? $query->whereNull('closure_order_states.assigned_user_id')
                : $query->where('closure_order_states.assigned_user_id', $cell['user']);
        }

        if (!empty($cell['status'])) {
            $query->where('closure_order_states.operational_status', $cell['status']);
        }

        if (!empty($cell['type'])) {
            $this->classifier->whereMetaType($query, $cell['type']);
        }

        return $query->orderByDesc('closure_order_states.priority')
            ->orderBy('closure_order_states.entered_current_status_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Estados de Ordens abertas e transferíveis de uma Nota (exclui as aguardando parceira,
     * que estão com a posse do sistema por uma pendência).
     *
     * @return Collection<int, ClosureOrderState>
     */
    public function transferableStates(int $noteId): Collection
    {
        return ClosureOrderState::query()
            ->whereHas('Order', fn ($q) => $q->where('note_id', $noteId))
            ->whereIn('operational_status', [
                Status::AVAILABLE->value,
                Status::ASSIGNED->value,
                Status::IN_PROGRESS->value,
                Status::DEPOSIT_BLOCKED->value,
                Status::DEPOSIT_RELEASED->value,
                Status::PAUSED->value,
            ])
            ->with('Order:id,ordem,note_id')
            ->orderBy('id')
            ->get();
    }

    /** Ordens ativas = nem encerradas nem canceladas (inclui FINISHED/WAITING). */
    private function activeBase(array $filters): Builder
    {
        return ClosureOrderState::query()
            ->join('orders', 'orders.id', '=', 'closure_order_states.order_id')
            ->whereNotIn('closure_order_states.operational_status', [Status::CLOSED->value, Status::CANCELLED->value])
            ->when($filters['company_id'] ?? null, fn (Builder $q, $company) => $q->where('closure_order_states.current_holder_company_id', $company))
            ->when($filters['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('closure_order_states.entered_current_status_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to) => $q->whereDate('closure_order_states.entered_current_status_at', '<=', $to))
            ->when($filters['type'] ?? null, fn (Builder $q, $type) => $this->classifier->whereMetaType($q, $type));
    }

    /** @return array<string, mixed> */
    private function emptyRow(string $name, ?string $userId): array
    {
        $types  = ['P' => 0, 'M' => 0, 'F' => 0, 'N' => 0];
        $status = array_fill_keys(self::MATRIX_STATUSES, 0);

        return [
            'user_id' => $userId,
            'name'    => $name,
            'total'   => 0,
            'types'   => $types,
            'status'  => $status,
            // células tipo × status: cells[status][P|M|F|N]
            'cells' => array_fill_keys(self::MATRIX_STATUSES, $types),
        ];
    }

    /** @param array<string, mixed> $row */
    private function accumulate(array &$row, ?string $type, string $status, int $count): void
    {
        $type = ClosureMetaClassifier::letter($type) ?? 'N';

        $row['total'] += $count;
        $row['types'][$type] += $count;

        if (isset($row['status'][$status])) {
            $row['status'][$status] += $count;
            $row['cells'][$status][$type] += $count;
        }
    }
}
