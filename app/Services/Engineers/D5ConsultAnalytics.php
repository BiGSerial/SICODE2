<?php

namespace App\Services\Engineers;

use App\Models\Company;
use App\Services\D5\D5StageResolver;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Números da "Consulta geral D5" (Engenharia). Recebe a mesma query filtrada da lista (sem o filtro de etapa), para os
 * cartões e gráficos nunca divergirem da tabela. Regras de cálculo em docs/engineers/CONSULTA_GERAL_D5.md.
 */
class D5ConsultAnalytics
{
    /** Passou disto (dias parada na etapa) = "Atenção"; passou de LATE_DAYS = "Atrasada". Mesma régua da tabela. */
    public const WARN_DAYS = 3;

    public const LATE_DAYS = 5;

    public const CACHE_SECONDS = 120;

    /** Meses no gráfico de fluxo (o atual e os anteriores). */
    public const FLOW_MONTHS = 6;

    /** Primeira faixa (índice) que já conta como atrasada: a partir de LATE_DAYS + 1 dias. */
    private const FIRST_LATE_BUCKET = 2;

    /** Faixas de dias na etapa: limite superior (inclusive) de cada uma; a última é aberta. */
    public const BUCKETS = [
        ['label' => '0-3 dias', 'max' => 3],
        ['label' => '4-5 dias', 'max' => 5],
        ['label' => '6-10 dias', 'max' => 10],
        ['label' => '11-20 dias', 'max' => 20],
        ['label' => '21-60 dias', 'max' => 60],
        ['label' => '61-180 dias', 'max' => 180],
        ['label' => '181+ dias', 'max' => null],
    ];

    /** Etapas "em andamento" (a D5 ainda espera alguém), na ordem do fluxo. */
    public const OPEN_STAGES = [
        D5StageResolver::SUPPLIER,
        D5StageResolver::FISCALIZATION,
        D5StageResolver::PAYMENT,
    ];

    public const STAGE_LABELS = [
        D5StageResolver::SUPPLIER      => 'Fornecedor',
        D5StageResolver::FISCALIZATION => 'Fiscalização',
        D5StageResolver::PAYMENT       => 'Medição',
        D5StageResolver::FINALIZED     => 'Finalizado',
    ];

    /** Desde quando a D5 está parada em cada etapa (com a mesma queda de data da tabela). */
    private const STAGE_START = [
        D5StageResolver::SUPPLIER      => 'dispatch_at',
        D5StageResolver::FISCALIZATION => 'COALESCE(completed_at, supervisioned_at, dispatch_at)',
        D5StageResolver::PAYMENT       => 'COALESCE(supervisioned_at, completed_at, dispatch_at, created_at)',
    ];

    /**
     * @param Builder $filtered query da lista com todos os filtros, exceto a etapa
     *
     * @return array<string, mixed>
     */
    public function snapshot(Builder $filtered): array
    {
        $key = 'd5-consult:v2:' . md5($filtered->toSql() . json_encode($filtered->getBindings()));

        return Cache::remember($key, self::CACHE_SECONDS, fn () => $this->compute($filtered));
    }

    /** @return array<string, mixed> */
    private function compute(Builder $filtered): array
    {
        $stages    = [];
        $companies = [];
        $reasons   = [];

        foreach (self::OPEN_STAGES as $stage) {
            $stages[$stage] = $this->stageSummary($filtered, $stage);

            foreach ($this->grouped($filtered, $stage, 'company_id') as $row) {
                $companies = $this->mergeGroup($companies, (string) ($row->k ?? ''), $row);
            }

            foreach ($this->grouped($filtered, $stage, 'reason') as $row) {
                $reasons = $this->mergeGroup($reasons, trim((string) ($row->k ?? '')) ?: 'Sem motivo informado', $row);
            }
        }

        $finalized = $this->finalized($filtered);
        $open      = array_sum(array_column($stages, 'total'));
        $late      = array_sum(array_column($stages, 'late'));
        $warn      = array_sum(array_column($stages, 'warn'));
        $ages      = array_filter(array_column($stages, 'oldest'), fn ($v) => $v !== null);

        $weighted = 0;

        foreach ($stages as $s) {
            $weighted += ($s['average'] ?? 0) * $s['total'];
        }

        return [
            'summary' => [
                'total'       => (clone $filtered)->reorder()->count(),
                'open'        => $open,
                'late'        => $late,
                'warn'        => $warn,
                'on_time'     => max(0, $open - $late - $warn),
                'oldest'      => $ages ? max($ages) : null,
                'average'     => $open > 0 ? round($weighted / $open, 1) : null,
                'finalized'   => $finalized,
                'return_days' => $this->averageReturnDays($filtered),
            ],
            'stages'      => $stages,
            'age_buckets' => $this->ageBuckets($stages),
            'by_company'  => $this->companies($companies),
            'by_reason'   => $this->reasons($reasons),
            'flow'        => $this->flow($filtered),
        ];
    }

    /** @return array{key: string, label: string, total: int, oldest: ?int, average: ?float, late: int, warn: int, buckets: array<int, int>} */
    private function stageSummary(Builder $filtered, string $stage): array
    {
        $query = (clone $filtered)->reorder();
        D5StageResolver::applyFilter($query, $stage);

        $age    = $this->ageSql($stage);
        $row    = $query->selectRaw('COUNT(*) as n, MAX(' . $age . ') as oldest, AVG(' . $age . ') as average, ' . $this->bucketSql($age))->first();
        $counts = [];

        foreach (array_keys(self::BUCKETS) as $i) {
            $counts[] = (int) ($row->{'b' . $i} ?? 0);
        }

        return [
            'key'     => $stage,
            'label'   => self::STAGE_LABELS[$stage],
            'total'   => (int) ($row->n ?? 0),
            'oldest'  => $row?->oldest !== null ? (int) $row->oldest : null,
            'average' => $row?->average !== null ? round((float) $row->average, 1) : null,
            'warn'    => $counts[1],
            'late'    => array_sum(array_slice($counts, self::FIRST_LATE_BUCKET)),
            'buckets' => $counts,
        ];
    }

    /** @return \Illuminate\Support\Collection<int, object{k: mixed, n: int, late: int, oldest: ?int}> */
    private function grouped(Builder $filtered, string $stage, string $column)
    {
        $query = (clone $filtered)->reorder();
        D5StageResolver::applyFilter($query, $stage);

        $age = $this->ageSql($stage);

        return $query
            ->selectRaw($column . ' as k, COUNT(*) as n, SUM(CASE WHEN ' . $age . ' > ' . self::LATE_DAYS . ' THEN 1 ELSE 0 END) as late, MAX(' . $age . ') as oldest')
            ->groupBy($column)
            ->get();
    }

    /**
     * @param array<string, array{total: int, late: int, oldest: ?int}> $acc
     *
     * @return array<string, array{total: int, late: int, oldest: ?int}>
     */
    private function mergeGroup(array $acc, string $key, object $row): array
    {
        $cur = $acc[$key] ?? ['total' => 0, 'late' => 0, 'oldest' => null];

        $acc[$key] = [
            'total'  => $cur['total'] + (int) $row->n,
            'late'   => $cur['late'] + (int) $row->late,
            'oldest' => max($cur['oldest'] ?? 0, (int) ($row->oldest ?? 0)),
        ];

        return $acc;
    }

    /** @param array<string, array{total: int, late: int, oldest: ?int}> $groups */
    private function companies(array $groups): array
    {
        $names = Company::withTrashed()->whereIn('id', array_filter(array_keys($groups)))->pluck('name', 'id');

        $rows = [];

        foreach ($groups as $id => $g) {
            $rows[] = ['id' => $id, 'label' => $names[$id] ?? 'Sem empreiteira'] + $g;
        }

        return $this->rank($rows);
    }

    /** @param array<string, array{total: int, late: int, oldest: ?int}> $groups */
    private function reasons(array $groups): array
    {
        $rows = [];

        foreach ($groups as $label => $g) {
            $rows[] = ['label' => $label] + $g;
        }

        return $this->rank($rows);
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function rank(array $rows): array
    {
        usort($rows, fn ($a, $b) => [$b['total'], $b['late']] <=> [$a['total'], $a['late']]);

        return $rows;
    }

    private function finalized(Builder $filtered): int
    {
        $query = (clone $filtered)->reorder();
        D5StageResolver::applyFilter($query, D5StageResolver::FINALIZED);

        return $query->count();
    }

    /** Tempo médio, em dias, entre o despacho e o retorno da empreiteira (só D5 que já retornaram). */
    private function averageReturnDays(Builder $filtered): ?float
    {
        $avg = (clone $filtered)->reorder()
            ->whereNotNull('dispatch_at')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, dispatch_at, completed_at)) / 24 as d')
            ->value('d');

        return $avg === null ? null : round((float) $avg, 1);
    }

    /**
     * Despachadas x retornadas pela empreiteira, por mês, nos últimos FLOW_MONTHS meses.
     *
     * @return array{labels: array<int, string>, sent: array<int, int>, returned: array<int, int>}
     */
    private function flow(Builder $filtered): array
    {
        $months = [];

        for ($i = self::FLOW_MONTHS - 1; $i >= 0; $i--) {
            $months[] = now()->startOfMonth()->subMonthsNoOverflow($i);
        }

        $from = $months[0]->copy()->startOfMonth();

        $series = fn (string $column) => (clone $filtered)->reorder()
            ->where($column, '>=', $from)
            ->selectRaw("DATE_FORMAT({$column}, '%Y-%m') as m, COUNT(*) as n")
            ->groupBy('m')
            ->get()
            ->pluck('n', 'm');

        $sent     = $series('dispatch_at');
        $returned = $series('completed_at');

        return [
            'labels'   => array_map(fn ($m) => $m->locale('pt_BR')->translatedFormat('M/y'), $months),
            'sent'     => array_map(fn ($m) => (int) ($sent[$m->format('Y-m')] ?? 0), $months),
            'returned' => array_map(fn ($m) => (int) ($returned[$m->format('Y-m')] ?? 0), $months),
        ];
    }

    /**
     * @param array<string, array{buckets: array<int, int>}> $stages
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function ageBuckets(array $stages): array
    {
        $values = array_fill(0, count(self::BUCKETS), 0);

        foreach ($stages as $s) {
            foreach ($s['buckets'] as $i => $n) {
                $values[$i] += $n;
            }
        }

        return ['labels' => array_column(self::BUCKETS, 'label'), 'values' => $values];
    }

    private function ageSql(string $stage): string
    {
        return 'TIMESTAMPDIFF(DAY, ' . self::STAGE_START[$stage] . ', NOW())';
    }

    private function bucketSql(string $age): string
    {
        $parts = [];
        $prev  = -1;

        foreach (self::BUCKETS as $i => $b) {
            $cond = $b['max'] === null
                ? "{$age} > {$prev}"
                : ($prev < 0 ? "{$age} <= {$b['max']}" : "{$age} > {$prev} AND {$age} <= {$b['max']}");

            $parts[] = "SUM(CASE WHEN {$cond} THEN 1 ELSE 0 END) as b{$i}";
            $prev    = $b['max'] ?? $prev;
        }

        return implode(', ', $parts);
    }
}
