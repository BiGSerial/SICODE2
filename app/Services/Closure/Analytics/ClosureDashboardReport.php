<?php

namespace App\Services\Closure\Analytics;

use App\Enum\ClosureOperationalStatus as Status;
use App\Models\Company;
use App\Services\Closure\{ClosureMetaClassifier, ClosureSignal};
use Carbon\Carbon;

/**
 * Dados do Excel do Painel analítico: tudo o que o painel mostra, em tabelas planas (uma por bloco da tela) e, em separado, as
 * atividades (Ordens) detalhadas por status. Respeita os filtros do painel (tipo, empresa, período) e, ao contrário da tela,
 * NÃO corta nada: todas as Ordens ativas, não só as 50 primeiras. Só dados — o visual do arquivo fica em App\Exports\Closure\Dashboard.
 */
final class ClosureDashboardReport
{
    public const TYPE_LABELS = ['P' => 'Passivo', 'M' => 'Meta atual', 'F' => 'Meta futura', 'N' => 'Sem tipo'];

    /** Rótulos curtos dos status (cabeçalho da matriz e nome das abas). */
    public const STATUS_SHORT = [
        'IN_PROGRESS'      => 'Em andamento',
        'ASSIGNED'         => 'Atribuída',
        'PAUSED'           => 'Pausa',
        'WAITING_PARTNER'  => 'Aguard. parceira',
        'DEPOSIT_BLOCKED'  => 'Dep. bloqueado',
        'DEPOSIT_RELEASED' => 'Dep. liberado',
        'FINISHED'         => 'Finalizado',
        'WAITING'          => 'Aguardando meta',
        'AVAILABLE'        => 'Disponível',
    ];

    public function __construct(private readonly ClosureDispatcherAnalytics $analytics)
    {
    }

    /**
     * @param array<string, mixed> $filters filtros do painel: type, company_id, from, to
     *
     * @return array<string, mixed>
     */
    public function build(array $filters, int $staleDays = 7, ?Carbon $now = null): array
    {
        $now ??= now();
        $matrix  = $this->analytics->matrix($filters);
        $summary = $this->analytics->summary($filters, $matrix);

        $orders      = $this->orderRows($filters, $now);
        $interaction = $this->analytics->partnerInteraction();
        $critical    = $this->analytics->criticalWorks(max(1, $staleDays), 1000);
        $priorities  = $this->analytics->pendingPriorityRequests();

        return [
            'generated_at'     => $now,
            'filters'          => $this->filterLabels($filters, $staleDays),
            'summary'          => $summary,
            'signals'          => $this->signalCounts($orders['all']),
            'critical_count'   => $critical->count(),
            'priorities_count' => $priorities->count(),
            'volume'           => $this->volumeRows($matrix),
            'status'           => $this->statusRows($matrix),
            'matrix'           => $this->matrixRows($matrix),
            'orders'           => $orders['all'],
            'orders_by_status' => $orders['by_status'],
            'partners'         => $this->partnerRows($interaction['partners']),
            'users'            => $this->userRows($interaction['users']),
            'critical'         => $this->criticalRows($critical, $staleDays),
            'priorities'       => $this->priorityRows($priorities, $now),
        ];
    }

    public static function statusLabel(string $code): string
    {
        return self::STATUS_SHORT[$code] ?? Status::labelFor($code);
    }

    /** @return array<string, string> rótulo => valor dos filtros aplicados */
    private function filterLabels(array $filters, int $staleDays): array
    {
        $company = !empty($filters['company_id']) ? (Company::withTrashed()->find($filters['company_id'])?->name ?? $filters['company_id']) : null;

        return [
            'Tipo'                           => !empty($filters['type']) ? (self::TYPE_LABELS[$filters['type']] ?? $filters['type']) : 'Todos',
            'Empresa (posse atual)'          => $company ?? 'Todas',
            'No status desde'                => !empty($filters['from']) ? Carbon::parse($filters['from'])->format('d/m/Y') : '—',
            'No status até'                  => !empty($filters['to']) ? Carbon::parse($filters['to'])->format('d/m/Y') : '—',
            'Obra crítica: parada há (dias)' => (string) max(1, $staleDays),
        ];
    }

    /**
     * Todas as Ordens ativas (com sinal por cor), e as mesmas separadas por status na ordem do painel.
     *
     * @return array{all: array<int, array<string, mixed>>, by_status: array<string, array<int, array<string, mixed>>>}
     */
    private function orderRows(array $filters, Carbon $now): array
    {
        $all = $this->analytics->orders($filters, [], PHP_INT_MAX)->map(function ($order) use ($now) {
            $signal  = ClosureSignal::resolve($order->status, $order->assigned_user_id, $order->current_holder_company_id, $order->current_holder_user_id, $order->entered_at, $order->statusSist, $now);
            $entered = $order->entered_at ? Carbon::parse($order->entered_at) : null;

            return [
                'priority'    => (bool) $order->priority,
                'type'        => ClosureMetaClassifier::letter($order->meta_type) ?? '—',
                'note'        => (string) $order->note_number,
                'ordem'       => (string) $order->ordem,
                'status_code' => $order->status,
                'status'      => self::statusLabel($order->status),
                'signal_key'  => $signal['key'] ?? null,
                'signal'      => $signal['label'] ?? '—',
                'responsible' => $order->responsible_name ?: 'Sem responsável (pilha)',
                'company'     => $order->company_name ?: '—',
                'since'       => $entered?->format('d/m/Y H:i'),
                'days'        => $entered ? (int) $entered->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()) : null,
            ];
        })->values()->all();

        $byStatus = [];

        foreach (ClosureDispatcherAnalytics::MATRIX_STATUSES as $code) {
            $rows = array_values(array_filter($all, fn (array $row) => $row['status_code'] === $code));

            if ($rows !== []) {
                $byStatus[$code] = $rows;
            }
        }

        return ['all' => $all, 'by_status' => $byStatus];
    }

    /**
     * @param array<int, array<string, mixed>> $orders
     *
     * @return array<string, int>
     */
    private function signalCounts(array $orders): array
    {
        $counts = [ClosureSignal::OVERDUE => 0, ClosureSignal::DONE => 0, ClosureSignal::ASSIGNED => 0, ClosureSignal::PILE => 0, 'none' => 0];

        foreach ($orders as $order) {
            $counts[$order['signal_key'] ?? 'none']++;
        }

        return $counts;
    }

    /** @return array{rows: array<int, array<int, mixed>>, total: array<int, mixed>} */
    private function volumeRows(array $matrix): array
    {
        $grand = max(1, (int) $matrix['totals']['total']);
        $rows  = collect($matrix['rows'])->map(fn (array $row) => [
            $row['name'], $row['types']['P'], $row['types']['M'], $row['types']['F'], $row['types']['N'], $row['total'], round($row['total'] / $grand * 100, 1),
        ])->all();

        $t = $matrix['totals'];

        return ['rows' => $rows, 'total' => ['Total geral', $t['types']['P'], $t['types']['M'], $t['types']['F'], $t['types']['N'], $t['total'], $t['total'] > 0 ? 100.0 : 0.0]];
    }

    /** @return array<int, array<int, mixed>> */
    private function statusRows(array $matrix): array
    {
        $grand = max(1, (int) $matrix['totals']['total']);

        return array_map(fn (string $code) => [
            self::statusLabel($code), $matrix['totals']['status'][$code], round($matrix['totals']['status'][$code] / $grand * 100, 1),
        ], ClosureDispatcherAnalytics::MATRIX_STATUSES);
    }

    /**
     * Matriz plana: uma linha por usuário × tipo, com a contagem por status; depois os totais por tipo e o total geral.
     *
     * @return array{rows: array<int, array<int, mixed>>, totals: array<int, array<int, mixed>>}
     */
    private function matrixRows(array $matrix): array
    {
        $line = function (string $name, string $type, array $row) {
            $cells = array_map(fn (string $status) => $row['cells'][$status][$type], ClosureDispatcherAnalytics::MATRIX_STATUSES);

            return array_merge([$name, self::TYPE_LABELS[$type]], $cells, [$row['types'][$type]]);
        };

        $rows = [];

        foreach ($matrix['rows'] as $row) {
            foreach (array_keys(self::TYPE_LABELS) as $type) {
                if ($row['types'][$type] > 0) {
                    $rows[] = $line($row['name'], $type, $row);
                }
            }
        }

        $totals = [];

        foreach (array_keys(self::TYPE_LABELS) as $type) {
            if ($matrix['totals']['types'][$type] > 0) {
                $totals[] = $line('Total ' . mb_strtolower(self::TYPE_LABELS[$type]), $type, $matrix['totals']);
            }
        }

        $grandCells = array_map(fn (string $status) => $matrix['totals']['status'][$status], ClosureDispatcherAnalytics::MATRIX_STATUSES);
        $totals[]   = array_merge(['Total geral', 'Todos'], $grandCells, [$matrix['totals']['total']]);

        return ['rows' => $rows, 'totals' => $totals];
    }

    /** @return array<int, array<int, mixed>> */
    private function partnerRows($partners): array
    {
        return $partners->map(fn (array $p) => [
            $p['name'], $p['open'], $p['awaiting'], self::days($p['avg_wait']), self::days($p['max_wait']), $p['returns'], $p['responded'], self::days($p['avg_response']),
        ])->values()->all();
    }

    /** @return array<int, array<int, mixed>> */
    private function userRows($users): array
    {
        return $users->map(fn (array $u) => [$u['name'], $u['sent'], $u['awaiting'], $u['responded'], self::days($u['avg_response'])])->values()->all();
    }

    /** @return array<int, array<int, mixed>> */
    private function criticalRows($critical, int $staleDays): array
    {
        return $critical->map(fn (array $c) => [
            $c['note'], $c['open_orders'], $c['passive_orders'], $c['stale_passive'], $c['oldest_days'], $c['stuck_issues'], $c['stuck_days'],
            $c['stuck_issues'] > 0 && $c['stale_passive'] > 0 ? 'Passivo parado e pendência parada' : ($c['stuck_issues'] > 0 ? 'Pendência parada com a parceira' : 'Passivo parado no status'),
        ])->values()->all();
    }

    /** @return array<int, array<int, mixed>> */
    private function priorityRows($priorities, Carbon $now): array
    {
        return $priorities->map(fn ($p) => [
            $p->Note?->note ?? '#' . $p->note_id,
            $p->RequestedBy?->name ?? '—',
            $p->reason,
            $p->created_at?->format('d/m/Y H:i'),
            $p->created_at ? (int) $p->created_at->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()) : null,
        ])->values()->all();
    }

    /** Segundos → dias com 1 casa (null mantém vazio). */
    private static function days(?int $seconds): ?float
    {
        return $seconds === null ? null : round($seconds / 86400, 1);
    }
}
