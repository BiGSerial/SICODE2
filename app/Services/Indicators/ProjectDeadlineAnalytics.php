<?php

namespace App\Services\Indicators;

use App\Models\OvPhaseDuration;
use App\Services\Notes\OvStatusHistoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dados da tela e do Excel "Prazo de Projetos": aderência por fase (levantamento 27, desenho 28), por empresa e a lista completa.
 * Tela e Excel leem daqui, para os números nunca divergirem. Regras em docs/reports/PRAZO_DE_PROJETOS.md.
 */
class ProjectDeadlineAnalytics
{
    public const PHASE_LABELS = [
        OvPhaseDuration::SURVEY => 'Levantamento',
        OvPhaseDuration::DESIGN => 'Desenho',
    ];

    /** Máximo de linhas da lista completa (o Excel avisa quando corta). */
    public const DETAIL_LIMIT = 50000;

    public function __construct(private readonly OvAdherenceService $adherence)
    {
    }

    /**
     * @param  array{from?:?string,to?:?string,company?:?string}  $filters
     * @return array{from:Carbon,to:Carbon,company:?string}
     */
    public function normalize(array $filters, ?Carbon $now = null): array
    {
        $now ??= now();
        $floor = Carbon::parse(OvStatusHistoryService::FLOOR)->startOfDay();

        $from = !empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : $floor;
        $to   = !empty($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : $now->copy()->endOfDay();

        $from = $from->lt($floor) ? $floor : $from;
        $to   = $to->lt($from) ? $from->copy()->endOfDay() : $to;

        return ['from' => $from, 'to' => $to, 'company' => ($filters['company'] ?? '') !== '' ? $filters['company'] : null];
    }

    /**
     * @param  array{from?:?string,to?:?string,company?:?string}  $filters
     * @return array<string,mixed>
     */
    public function report(array $filters = [], bool $withDetail = true): array
    {
        $f          = $this->normalize($filters);
        $companyIds = $f['company'] ? [$f['company']] : null;

        $phases = [];

        foreach (self::PHASE_LABELS as $phase => $label) {
            $months = $this->adherence->monthly($phase, $companyIds, $f['from']->toDateTimeString(), $f['to']->toDateTimeString());
            $total  = array_sum(array_column($months, 'total'));
            $bands  = array_map(fn ($i) => array_sum(array_map(fn ($m) => $m['bands'][$i], $months)), [0, 1, 2, 3]);

            $phases[$phase] = [
                'phase'   => $phase,
                'label'   => $label,
                'limit'   => OvAdherenceService::BANDS[$phase][0],
                'labels'  => self::bandLabels($phase),
                'months'  => $months,
                'summary' => [
                    'total'     => $total,
                    'bands'     => $bands,
                    'adherence' => $total ? round($bands[0] / $total * 100, 1) : 0.0,
                    'avg_days'  => $total
                        ? round(array_sum(array_map(fn ($m) => $m['avg_days'] * $m['total'], $months)) / $total, 2)
                        : 0.0,
                ],
            ];
        }

        $report = [
            'filters'      => $f,
            'generated_at' => now(),
            'phases'       => $phases,
            'companies'    => $this->companies($f),
            'detail'       => [],
            'truncated'    => false,
        ];

        if ($withDetail) {
            $detail              = $this->detail($f);
            $report['truncated'] = count($detail) > self::DETAIL_LIMIT;
            $report['detail']    = array_slice($detail, 0, self::DETAIL_LIMIT);
        }

        return $report;
    }

    /** @return array<int,string> */
    public static function bandLabels(int $phase): array
    {
        [$a, $b, $c] = OvAdherenceService::BANDS[$phase];

        return ["0 a {$a} dias", ($a + 1) . " a {$b} dias", ($b + 1) . " a {$c} dias", "Acima de {$c} dias"];
    }

    public static function bandFor(int $phase, float $days): string
    {
        [$a, $b, $c] = OvAdherenceService::BANDS[$phase];
        $labels      = self::bandLabels($phase);

        return match (true) {
            $days <= $a => $labels[0],
            $days <= $b => $labels[1],
            $days <= $c => $labels[2],
            default     => $labels[3],
        };
    }

    /**
     * Uma linha por empresa executora, com as duas fases.
     *
     * @param  array{from:Carbon,to:Carbon,company:?string}  $f
     * @return array<int,array<string,mixed>>
     */
    private function companies(array $f): array
    {
        $rows = DB::table('ov_phase_durations as d')
            ->leftJoin('companies as c', 'c.id', '=', 'd.company_id')
            ->whereBetween('d.ended_at', [$f['from'], $f['to']])
            ->when($f['company'], fn ($q) => $q->where('d.company_id', $f['company']))
            ->groupBy('d.company_id', 'c.name', 'd.phase')
            ->selectRaw('d.company_id, COALESCE(c.name, ?) as company, d.phase, COUNT(*) as total, AVG(d.days) as avg_days', ['Sem empresa'])
            ->selectRaw('SUM(d.days <= CASE WHEN d.phase = ? THEN ? ELSE ? END) as adherent', [
                OvPhaseDuration::SURVEY,
                OvAdherenceService::BANDS[OvPhaseDuration::SURVEY][0],
                OvAdherenceService::BANDS[OvPhaseDuration::DESIGN][0],
            ])
            ->get();

        $byCompany = [];

        foreach ($rows as $r) {
            $key                        = $r->company_id ?? '-';
            $byCompany[$key]['name']    = $r->company;
            $byCompany[$key][$r->phase] = [
                'total'     => (int) $r->total,
                'adherence' => $r->total ? round($r->adherent / $r->total * 100, 1) : 0.0,
                'avg_days'  => round((float) $r->avg_days, 2),
            ];
        }

        usort($byCompany, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return array_values($byCompany);
    }

    /**
     * Lista completa das fases concluídas no período, da mais recente para a mais antiga (traz 1 a mais que o limite para sinalizar corte).
     *
     * @param  array{from:Carbon,to:Carbon,company:?string}  $f
     * @return array<int,array<string,mixed>>
     */
    private function detail(array $f): array
    {
        return DB::table('ov_phase_durations as d')
            ->join('notes as n', 'n.id', '=', 'd.note_id')
            ->leftJoin('companies as c', 'c.id', '=', 'd.company_id')
            ->whereBetween('d.ended_at', [$f['from'], $f['to']])
            ->when($f['company'], fn ($q) => $q->where('d.company_id', $f['company']))
            ->orderByDesc('d.ended_at')
            ->orderBy('d.id')
            ->limit(self::DETAIL_LIMIT + 1)
            ->get(['n.note', 'd.phase', 'c.name as company', 'd.started_at', 'd.ended_at', 'd.days'])
            ->map(fn ($r) => [
                'note'       => $r->note,
                'phase'      => $r->phase,
                'company'    => $r->company,
                'started_at' => Carbon::parse($r->started_at),
                'ended_at'   => Carbon::parse($r->ended_at),
                'days'       => (float) $r->days,
                'band'       => self::bandFor((int) $r->phase, (float) $r->days),
                'on_time'    => (float) $r->days <= OvAdherenceService::BANDS[(int) $r->phase][0],
            ])
            ->all();
    }
}
