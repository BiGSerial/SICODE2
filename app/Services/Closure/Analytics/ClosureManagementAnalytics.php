<?php

namespace App\Services\Closure\Analytics;

use App\Models\Closure\{ClosureInternalReturn, ClosurePartnerSignal};
use App\Models\Company;
use App\Services\Closure\ClosureArea;
use App\Services\Closure\Queries\ClosureInboxQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Dados do painel GERENCIAL do Encerramento (tela e Excel leem daqui). Responde:
 *  - principais problemas (pendências por motivo, RI por motivo, depósitos bloqueados);
 *  - problemas por parceira;
 *  - problemas por atividade (Parceira, Medição, Fiscalização e Desenho/RI);
 *  - comparador mensal: melhoramos ou pioramos? (6 meses terminando no mês de `to`).
 *
 * Regras (documentadas em docs/encerramento/PAINEL_GERENCIAL.md):
 *  - mês de cada número = data do próprio fato (abertura, resolução, resposta, envio do RI, bloqueio do depósito, evento da Ordem);
 *  - indicador tem direção ("mais é melhor" ou "menos é melhor"); variação dentro de ±5% é "estável";
 *  - tempos em dias corridos (SQL em segundos / 86400); média só de quem tem as duas datas;
 *  - pendência sem `destination_area` é da Parceira (padrão histórico); RI é a atividade Desenho.
 */
class ClosureManagementAnalytics
{
    public const MONTHS = 6;

    public const STABLE_PERCENT = 5.0;

    public const AREA_DESIGN = 'DESIGN';

    /** Cache curto: o painel é lido por poucas pessoas e os números mudam devagar. */
    public const CACHE_SECONDS = 120;

    /** key => [rótulo, unidade n|days, melhor up|down|neutral, explicação] */
    public const INDICATORS = [
        'dispatched'      => ['Ordens despachadas', 'n', 'neutral', 'Ordens enviadas aos executores (evento Despachada).'],
        'finished'        => ['Ordens finalizadas', 'n', 'up', 'Ordens que o executor finalizou (evento Finalizada).'],
        'closed'          => ['Encerradas no SAP', 'n', 'up', 'Ordens que o SAP encerrou (evento Encerrada no SAP).'],
        'issues_opened'   => ['Pendências abertas', 'n', 'down', 'Pendências abertas pelo Encerramento (parceira, Medição ou Fiscalização).'],
        'issues_resolved' => ['Pendências resolvidas', 'n', 'up', 'Pendências concluídas pelo Encerramento.'],
        'returns'         => ['Devoluções para correção', 'n', 'down', 'Vezes em que o Encerramento devolveu um retorno por estar errado ou incompleto (retrabalho).'],
        'response_days'   => ['Resposta média (dias)', 'days', 'down', 'Dias entre enviar a pendência e receber o retorno, das respondidas no mês.'],
        'ri_sent'         => ['RI enviados ao Desenho', 'n', 'down', 'Retornos internos enviados ao Desenho (obra travada).'],
        'ri_days'         => ['Retorno médio do RI (dias)', 'days', 'down', 'Dias entre enviar o RI e o Desenho devolver, dos devolvidos no mês.'],
        'deposits'        => ['Depósitos bloqueados', 'n', 'down', 'Bloqueios de depósito criados no mês.'],
    ];

    public function __construct(private readonly ClosureInboxQuery $inbox)
    {
    }

    /**
     * @param array{from?: ?string, to?: ?string} $filters
     *
     * @return array{from: Carbon, to: Carbon}
     */
    public function normalize(array $filters): array
    {
        $from = $this->date($filters['from'] ?? null)?->startOfDay() ?? now()->startOfMonth();
        $to   = $this->date($filters['to'] ?? null)?->endOfDay() ?? now()->endOfMonth();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return ['from' => $from, 'to' => $to];
    }

    /**
     * @param array{from?: ?string, to?: ?string} $filters
     *
     * @return array<string, mixed>
     */
    public function snapshot(array $filters = []): array
    {
        $f   = $this->normalize($filters);
        $key = 'closure.management.v1.' . $f['from']->toDateString() . '.' . $f['to']->toDateString();

        return Cache::remember($key, self::CACHE_SECONDS, function () use ($f) {
            $comparator = $this->comparator($f['to']);
            $partners   = $this->partners($f['from'], $f['to']);
            $activities = $this->activities($f['from'], $f['to']);
            $problems   = $this->problems($f['from'], $f['to']);

            return [
                'filters'    => $f,
                'updated_at' => now(),
                'summary'    => $this->summary($f['from'], $f['to'], $comparator),
                'comparator' => $comparator,
                'problems'   => $problems,
                'partners'   => $partners,
                'activities' => $activities,
            ];
        });
    }

    /** Veredito de um indicador entre dois valores: melhorou, piorou, estável (ou variou, quando não há direção). */
    public static function verdict(string $key, float|int|null $previous, float|int|null $current): array
    {
        $better = self::INDICATORS[$key][2];

        if ($previous === null || $current === null) {
            return ['key' => 'na', 'label' => 'Sem base', 'tone' => 'muted', 'delta' => null, 'percent' => null];
        }

        if ($previous == 0 && $current == 0) {
            return ['key' => 'na', 'label' => 'Sem movimento', 'tone' => 'muted', 'delta' => null, 'percent' => null];
        }

        $delta   = $current - $previous;
        $percent = $previous > 0 ? $delta / $previous * 100 : ($current > 0 ? 100.0 : 0.0);

        if ($delta == 0 || abs($percent) < self::STABLE_PERCENT) {
            return ['key' => 'stable', 'label' => 'Estável', 'tone' => 'muted', 'delta' => $delta, 'percent' => $percent];
        }

        if ($better === 'neutral') {
            return ['key' => 'changed', 'label' => $delta > 0 ? 'Subiu' : 'Caiu', 'tone' => 'blue', 'delta' => $delta, 'percent' => $percent];
        }

        $improved = ($better === 'up') === ($delta > 0);

        return ['key' => $improved ? 'better' : 'worse', 'label' => $improved ? 'Melhorou' : 'Piorou', 'tone' => $improved ? 'green' : 'red', 'delta' => $delta, 'percent' => $percent];
    }

    /**
     * Comparador mensal: seis meses terminando no mês de `$to`; o último contra o anterior dá o veredito de cada indicador.
     *
     * @return array<string, mixed>
     */
    public function comparator(Carbon $to): array
    {
        $end   = $to->copy()->startOfMonth();
        $start = $end->copy()->subMonths(self::MONTHS - 1);
        $range = [$start->copy()->startOfDay(), $end->copy()->endOfMonth()];

        $months = [];

        for ($i = 0; $i < self::MONTHS; $i++) {
            $month    = $start->copy()->addMonths($i);
            $months[] = ['key' => $month->format('Y-m'), 'label' => $month->locale('pt_BR')->translatedFormat('M/y')];
        }

        $series = array_fill_keys(array_keys(self::INDICATORS), array_fill_keys(array_column($months, 'key'), null));

        $fill = function (string $indicator, $rows, string $column, bool $zeroFill = true) use (&$series) {
            foreach ($series[$indicator] as $ym => $_) {
                $row                     = $rows[$ym] ?? null;
                $series[$indicator][$ym] = $row !== null ? $row->{$column} : ($zeroFill ? 0 : null);
            }
        };

        $events = fn (string $type) => DB::table('closure_order_events')->where('type', $type)->whereBetween('created_at', $range)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, count(*) as c")->groupBy('ym')->get()->keyBy('ym');

        $fill('dispatched', $events('DISPATCHED'), 'c');
        $fill('finished', $events('FINISHED'), 'c');
        $fill('closed', $events('SAP_CLOSED'), 'c');

        $fill('issues_opened', DB::table('closure_issues')->whereBetween('opened_at', $range)->selectRaw("DATE_FORMAT(opened_at, '%Y-%m') as ym, count(*) as c")->groupBy('ym')->get()->keyBy('ym'), 'c');
        $fill('issues_resolved', DB::table('closure_issues')->whereBetween('resolved_at', $range)->selectRaw("DATE_FORMAT(resolved_at, '%Y-%m') as ym, count(*) as c")->groupBy('ym')->get()->keyBy('ym'), 'c');
        $fill('returns', DB::table('closure_issue_events')->where('type', 'RETURNED_FOR_CORRECTION')->whereBetween('created_at', $range)->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, count(*) as c")->groupBy('ym')->get()->keyBy('ym'), 'c');

        $response = DB::table('closure_issues')->whereBetween('responded_at', $range)->whereNotNull('requested_at')
            ->selectRaw("DATE_FORMAT(responded_at, '%Y-%m') as ym, avg(timestampdiff(SECOND, requested_at, responded_at)) / 86400 as d")->groupBy('ym')->get()->keyBy('ym');
        $fill('response_days', $response, 'd', false);

        $fill('ri_sent', DB::table('closure_internal_returns')->whereBetween('sent_at', $range)->selectRaw("DATE_FORMAT(sent_at, '%Y-%m') as ym, count(*) as c")->groupBy('ym')->get()->keyBy('ym'), 'c');

        $riDays = DB::table('closure_internal_returns')->where('status', ClosureInternalReturn::STATUS_RETURNED)->whereBetween('returned_at', $range)
            ->selectRaw("DATE_FORMAT(returned_at, '%Y-%m') as ym, avg(timestampdiff(SECOND, sent_at, returned_at)) / 86400 as d")->groupBy('ym')->get()->keyBy('ym');
        $fill('ri_days', $riDays, 'd', false);

        $fill('deposits', DB::table('closure_partner_signals')->where('type', ClosurePartnerSignal::TYPE_DEPOSIT_BLOCKED)->whereBetween('created_at', $range)->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, count(*) as c")->groupBy('ym')->get()->keyBy('ym'), 'c');

        $lastKey = $months[self::MONTHS - 1]['key'];
        $prevKey = $months[self::MONTHS - 2]['key'];
        $rows    = [];
        $count   = ['better' => 0, 'worse' => 0, 'stable' => 0];

        foreach (self::INDICATORS as $key => [$label, $unit, $better, $hint]) {
            $values  = array_map(fn ($v) => $v === null ? null : ($unit === 'days' ? round((float) $v, 1) : (int) $v), array_values($series[$key]));
            $verdict = self::verdict($key, $series[$key][$prevKey], $series[$key][$lastKey]);

            if (isset($count[$verdict['key']])) {
                $count[$verdict['key']]++;
            }

            $rows[] = ['key' => $key, 'label' => $label, 'unit' => $unit, 'better' => $better, 'hint' => $hint, 'values' => $values, 'verdict' => $verdict];
        }

        $judged = $count['better'] + $count['worse'] + $count['stable'];

        return [
            'months'    => $months,
            'rows'      => $rows,
            'count'     => $count,
            'judged'    => $judged,
            'reference' => $end->copy()->locale('pt_BR')->translatedFormat('F/Y'),
            'previous'  => $end->copy()->subMonth()->locale('pt_BR')->translatedFormat('F/Y'),
            'partial'   => $end->isSameMonth(now()),
            'overall'   => $count['worse'] > $count['better'] ? 'worse' : ($count['better'] > $count['worse'] ? 'better' : 'stable'),
            'series'    => array_column($rows, 'values', 'key'),
        ];
    }

    /**
     * Principais problemas do período: motivos de pendência, motivos de RI e depósitos bloqueados, numa lista só (do maior para o menor).
     *
     * @return array{ranking: array<int, array<string, mixed>>, issue_reasons: array<int, array<string, mixed>>, ri_reasons: array<int, array<string, mixed>>, deposits: array<int, array<string, mixed>>, total: int}
     */
    public function problems(Carbon $from, Carbon $to): array
    {
        $issueReasons = DB::table('closure_issues as i')
            ->join('closure_issue_categories as c', 'c.id', '=', 'i.closure_issue_category_id')
            ->leftJoin('closure_issue_subcategories as s', 's.id', '=', 'i.closure_issue_subcategory_id')
            ->whereBetween('i.opened_at', [$from, $to])
            ->selectRaw(
                "c.name as category, s.name as subcategory, coalesce(i.destination_area, 'PARTNER') as area, count(*) as total,"
                . " sum(i.status <> 'RESOLVED') as open_now,"
                . ' avg(case when i.requested_at is not null and i.responded_at is not null then timestampdiff(SECOND, i.requested_at, i.responded_at) end) / 86400 as avg_days'
            )
            ->groupBy('category', 'subcategory', 'area')->orderByDesc('total')->get()
            ->map(fn ($r) => [
                'label'    => $r->category . ($r->subcategory ? ' · ' . $r->subcategory : ''),
                'category' => $r->category,
                'area'     => $r->area,
                'source'   => 'Pendência · ' . self::areaLabel($r->area),
                'total'    => (int) $r->total,
                'open'     => (int) $r->open_now,
                'avg_days' => $r->avg_days !== null ? round((float) $r->avg_days, 1) : null,
            ])->all();

        $riReasons = DB::table('closure_internal_returns as r')
            ->leftJoin('reclaims as rc', 'rc.id', '=', 'r.reclaim_id')
            ->leftJoin('subcategories as sc', 'sc.id', '=', 'rc.subcategory_id')
            ->whereBetween('r.sent_at', [$from, $to])
            ->selectRaw(
                "coalesce(sc.name, rc.category, 'Sem motivo informado') as reason, count(*) as total, sum(r.status = 'SENT') as open_now,"
                . " avg(case when r.status = 'RETURNED' then timestampdiff(SECOND, r.sent_at, r.returned_at) end) / 86400 as avg_days"
            )
            ->groupBy('reason')->orderByDesc('total')->get()
            ->map(fn ($r) => [
                'label'    => $r->reason,
                'source'   => 'RI · Desenho',
                'total'    => (int) $r->total,
                'open'     => (int) $r->open_now,
                'avg_days' => $r->avg_days !== null ? round((float) $r->avg_days, 1) : null,
            ])->all();

        $deposits = DB::table('closure_partner_signals')
            ->where('type', ClosurePartnerSignal::TYPE_DEPOSIT_BLOCKED)->whereBetween('created_at', [$from, $to])
            ->selectRaw("coalesce(deposit_label, deposit_code, center, 'Depósito') as label, count(*) as total, sum(status = 'ACTIVE') as open_now, coalesce(sum(affected_count), 0) as affected")
            ->groupBy('label')->orderByDesc('total')->get()
            ->map(fn ($r) => [
                'label'    => 'Depósito ' . $r->label,
                'source'   => 'Depósito bloqueado',
                'total'    => (int) $r->total,
                'open'     => (int) $r->open_now,
                'affected' => (int) $r->affected,
                'avg_days' => null,
            ])->all();

        $ranking = collect($issueReasons)->merge($riReasons)->merge($deposits)->sortByDesc('total')->values()->all();

        return [
            'ranking'       => $ranking,
            'issue_reasons' => $issueReasons,
            'ri_reasons'    => $riReasons,
            'deposits'      => $deposits,
            'total'         => (int) array_sum(array_column($ranking, 'total')),
        ];
    }

    /**
     * Problemas por parceira no período (pendências abertas no período + situação de agora).
     *
     * @return array<int, array<string, mixed>> da que mais recebeu para a que menos recebeu
     */
    public function partners(Carbon $from, Carbon $to): array
    {
        $received = DB::table('closure_issues')->whereNotNull('destination_company_id')->whereBetween('opened_at', [$from, $to])
            ->selectRaw('destination_company_id as company_id, count(*) as total')->groupBy('company_id')->pluck('total', 'company_id');

        $now = DB::table('closure_issues')->whereNotNull('destination_company_id')
            ->selectRaw("destination_company_id as company_id, sum(status <> 'RESOLVED') as open_now, sum(status in ('REQUESTED','RETURNED')) as awaiting")
            ->groupBy('company_id')->get()->keyBy('company_id');

        $responded = DB::table('closure_issues')->whereNotNull('destination_company_id')->whereNotNull('requested_at')->whereBetween('responded_at', [$from, $to])
            ->selectRaw('destination_company_id as company_id, count(*) as total, avg(timestampdiff(SECOND, requested_at, responded_at)) / 86400 as avg_days')
            ->groupBy('company_id')->get()->keyBy('company_id');

        $returns = DB::table('closure_issue_events as e')->join('closure_issues as i', 'i.id', '=', 'e.closure_issue_id')
            ->where('e.type', 'RETURNED_FOR_CORRECTION')->whereNotNull('i.destination_company_id')->whereBetween('e.created_at', [$from, $to])
            ->selectRaw('i.destination_company_id as company_id, count(*) as total')->groupBy('company_id')->pluck('total', 'company_id');

        $topReason = DB::table('closure_issues as i')->join('closure_issue_categories as c', 'c.id', '=', 'i.closure_issue_category_id')
            ->whereNotNull('i.destination_company_id')->whereBetween('i.opened_at', [$from, $to])
            ->selectRaw('i.destination_company_id as company_id, c.name as category, count(*) as total')->groupBy('company_id', 'category')->get()
            ->groupBy('company_id')->map(fn ($g) => $g->sortByDesc('total')->first());

        $deposits = DB::table('closure_partner_signals')->where('type', ClosurePartnerSignal::TYPE_DEPOSIT_BLOCKED)->whereBetween('created_at', [$from, $to])
            ->selectRaw('company_id, count(*) as total')->groupBy('company_id')->pluck('total', 'company_id');

        $ids   = collect([$received, $now, $responded, $returns, $deposits])->flatMap(fn ($c) => $c->keys())->unique()->filter()->values();
        $names = Company::query()->withTrashed()->whereIn('id', $ids->all())->pluck('name', 'id');

        return $ids->map(function ($id) use ($received, $now, $responded, $returns, $topReason, $deposits, $names) {
            $answered = (int) ($responded[$id]->total ?? 0);
            $back     = (int) ($returns[$id] ?? 0);

            return [
                'company_id' => (string) $id,
                'name'       => $names[$id] ?? 'Empresa removida',
                'received'   => (int) ($received[$id] ?? 0),
                'open'       => (int) ($now[$id]->open_now ?? 0),
                'awaiting'   => (int) ($now[$id]->awaiting ?? 0),
                'answered'   => $answered,
                'avg_days'   => isset($responded[$id]) && $responded[$id]->avg_days !== null ? round((float) $responded[$id]->avg_days, 1) : null,
                'returns'    => $back,
                'rework'     => $answered > 0 ? round($back / $answered * 100, 1) : null,
                'top_reason' => $topReason[$id]->category ?? null,
                'deposits'   => (int) ($deposits[$id] ?? 0),
            ];
        })->sortByDesc(fn ($p) => [$p['received'], $p['awaiting']])->values()->all();
    }

    /**
     * Problemas por atividade: Parceira, Medição, Fiscalização (pendências por `destination_area`) e Desenho (RI).
     * `received_prev` é o período imediatamente anterior, de mesma duração, para a variação.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activities(Carbon $from, Carbon $to): array
    {
        $days     = max(1, (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1);
        $prevTo   = $from->copy()->subSecond();
        $prevFrom = $from->copy()->subDays($days)->startOfDay();

        $area = "coalesce(destination_area, 'PARTNER')";

        $received     = fn (Carbon $a, Carbon $b) => DB::table('closure_issues')->whereBetween('opened_at', [$a, $b])->selectRaw("{$area} as a, count(*) as c")->groupBy('a')->pluck('c', 'a');
        $receivedNow  = $received($from, $to);
        $receivedPrev = $received($prevFrom, $prevTo);

        $resolved = DB::table('closure_issues')->whereBetween('resolved_at', [$from, $to])->selectRaw("{$area} as a, count(*) as c")->groupBy('a')->pluck('c', 'a');

        $now = DB::table('closure_issues')->selectRaw("{$area} as a, sum(status <> 'RESOLVED') as open_now, sum(status in ('REQUESTED','RETURNED')) as awaiting")->groupBy('a')->get()->keyBy('a');

        $answered = DB::table('closure_issues')->whereNotNull('requested_at')->whereBetween('responded_at', [$from, $to])
            ->selectRaw("{$area} as a, count(*) as c, avg(timestampdiff(SECOND, requested_at, responded_at)) / 86400 as d")->groupBy('a')->get()->keyBy('a');

        $returns = DB::table('closure_issue_events as e')->join('closure_issues as i', 'i.id', '=', 'e.closure_issue_id')
            ->where('e.type', 'RETURNED_FOR_CORRECTION')->whereBetween('e.created_at', [$from, $to])
            ->selectRaw("coalesce(i.destination_area, 'PARTNER') as a, count(*) as c")->groupBy('a')->pluck('c', 'a');

        $ri = fn (Carbon $a, Carbon $b) => DB::table('closure_internal_returns')->whereBetween('sent_at', [$a, $b])->count();

        $riNow = DB::table('closure_internal_returns')->selectRaw("sum(status = 'SENT') as open_now")->first();
        $riAvg = DB::table('closure_internal_returns')->where('status', ClosureInternalReturn::STATUS_RETURNED)->whereBetween('returned_at', [$from, $to])
            ->selectRaw('count(*) as c, avg(timestampdiff(SECOND, sent_at, returned_at)) / 86400 as d')->first();
        $riResolved = (int) ($riAvg->c ?? 0);
        $riReturns  = DB::table('closure_internal_returns')->whereBetween('sent_at', [$from, $to])->where('cycle', '>', 1)->count();

        $rows = [];

        foreach ([ClosureArea::PARTNER, ClosureArea::MEASUREMENT, ClosureArea::SUPERVISION] as $code) {
            $boxOrders = ClosureArea::hasInbox($code) ? array_sum($this->inbox->accompanyingCounts($code)) : null;
            $answers   = (int) ($answered[$code]->c ?? 0);
            $back      = (int) ($returns[$code] ?? 0);

            $rows[] = [
                'code'          => $code,
                'name'          => self::areaLabel($code),
                'received'      => (int) ($receivedNow[$code] ?? 0),
                'received_prev' => (int) ($receivedPrev[$code] ?? 0),
                'resolved'      => (int) ($resolved[$code] ?? 0),
                'open'          => (int) ($now[$code]->open_now ?? 0),
                'awaiting'      => (int) ($now[$code]->awaiting ?? 0),
                'answered'      => $answers,
                'avg_days'      => isset($answered[$code]) && $answered[$code]->d !== null ? round((float) $answered[$code]->d, 1) : null,
                'returns'       => $back,
                'rework'        => $answers > 0 ? round($back / $answers * 100, 1) : null,
                'in_box'        => $boxOrders,
                'unit'          => 'pendências',
            ];
        }

        $rows[] = [
            'code'          => self::AREA_DESIGN,
            'name'          => self::areaLabel(self::AREA_DESIGN),
            'received'      => $ri($from, $to),
            'received_prev' => $ri($prevFrom, $prevTo),
            'resolved'      => $riResolved,
            'open'          => (int) ($riNow->open_now ?? 0),
            'awaiting'      => (int) ($riNow->open_now ?? 0),
            'answered'      => $riResolved,
            'avg_days'      => $riAvg && $riAvg->d !== null ? round((float) $riAvg->d, 1) : null,
            'returns'       => $riReturns,
            'rework'        => $riResolved > 0 ? round($riReturns / $riResolved * 100, 1) : null,
            'in_box'        => null,
            'unit'          => 'RI',
        ];

        return $rows;
    }

    public static function areaLabel(string $code): string
    {
        return match ($code) {
            ClosureArea::PARTNER     => 'Parceiras',
            ClosureArea::MEASUREMENT => 'Medição',
            ClosureArea::SUPERVISION => 'Fiscalização',
            self::AREA_DESIGN        => 'Desenho (RI)',
            default                  => ClosureArea::label($code),
        };
    }

    /**
     * Cartões do painel executivo (tudo derivado do comparador e das pendências de agora).
     *
     * @param array<string, mixed> $comparator
     *
     * @return array<string, mixed>
     */
    private function summary(Carbon $from, Carbon $to, array $comparator): array
    {
        $open = DB::table('closure_issues')->selectRaw("sum(status <> 'RESOLVED') as open_now, sum(status in ('REQUESTED','RETURNED')) as awaiting")->first();

        $answered = DB::table('closure_issues')->whereNotNull('requested_at')->whereBetween('responded_at', [$from, $to])
            ->selectRaw('count(*) as c, avg(timestampdiff(SECOND, requested_at, responded_at)) / 86400 as d')->first();

        $returns = (int) DB::table('closure_issue_events')->where('type', 'RETURNED_FOR_CORRECTION')->whereBetween('created_at', [$from, $to])->count();

        $ri       = (int) DB::table('closure_internal_returns')->where('status', ClosureInternalReturn::STATUS_SENT)->count();
        $deposits = (int) DB::table('closure_partner_signals')->where('type', ClosurePartnerSignal::TYPE_DEPOSIT_BLOCKED)->where('status', ClosurePartnerSignal::STATUS_ACTIVE)->count();
        $count    = (int) ($answered->c ?? 0);

        return [
            'open_issues'  => (int) ($open->open_now ?? 0),
            'awaiting'     => (int) ($open->awaiting ?? 0),
            'avg_response' => $answered && $answered->d !== null ? round((float) $answered->d, 1) : null,
            'answered'     => $count,
            'returns'      => $returns,
            'rework'       => $count > 0 ? round($returns / $count * 100, 1) : null,
            'ri_open'      => $ri,
            'deposits'     => $deposits,
            'verdicts'     => collect($comparator['rows'])->mapWithKeys(fn ($r) => [$r['key'] => $r['verdict']])->all(),
        ];
    }

    private function date(?string $value): ?Carbon
    {
        try {
            return filled($value) ? Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
