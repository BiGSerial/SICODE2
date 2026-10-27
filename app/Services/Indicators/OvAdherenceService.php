<?php

namespace App\Services\Indicators;

use App\Models\OvPhaseDuration;
use App\Services\Notes\OvStatusHistoryService;
use Illuminate\Support\Facades\DB;

/**
 * Aderência de prazo por fase (levantamento 8 dias, desenho 6), agrupada pelo mês em que a fase termina.
 */
class OvAdherenceService
{
    /** Limites das faixas por fase: [aderência, intermediária, máxima]. */
    public const BANDS = [
        OvPhaseDuration::SURVEY => [8, 15, 30],
        OvPhaseDuration::DESIGN => [6, 15, 30],
    ];

    /**
     * @param  array<int>|null  $companyIds
     * @return array<int,array{month:string,total:int,bands:array<int,int>,adherence:float,avg_days:float}>
     */
    public function monthly(int $phase, ?array $companyIds = null, ?string $from = null, ?string $to = null): array
    {
        [$a, $b, $c] = self::BANDS[$phase];

        $rows = OvPhaseDuration::query()
            ->where('phase', $phase)
            ->where('ended_at', '>=', $from ?? OvStatusHistoryService::FLOOR)
            ->when($to, fn ($q) => $q->where('ended_at', '<=', $to))
            ->when($companyIds, fn ($q) => $q->whereIn('company_id', $companyIds))
            ->groupBy(DB::raw("DATE_FORMAT(ended_at, '%Y-%m')"))
            ->orderBy('month')
            ->selectRaw("DATE_FORMAT(ended_at, '%Y-%m') as month")
            ->selectRaw('COUNT(*) as total, AVG(days) as avg_days')
            ->selectRaw('SUM(days <= ?) as b1', [$a])
            ->selectRaw('SUM(days > ? AND days <= ?) as b2', [$a, $b])
            ->selectRaw('SUM(days > ? AND days <= ?) as b3', [$b, $c])
            ->selectRaw('SUM(days > ?) as b4', [$c])
            ->get();

        return $rows->map(fn ($r) => [
            'month'     => $r->month,
            'total'     => (int) $r->total,
            'bands'     => [(int) $r->b1, (int) $r->b2, (int) $r->b3, (int) $r->b4],
            'adherence' => $r->total ? round($r->b1 / $r->total * 100, 1) : 0.0,
            'avg_days'  => round((float) $r->avg_days, 2),
        ])->all();
    }
}
