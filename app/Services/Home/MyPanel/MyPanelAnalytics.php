<?php

namespace App\Services\Home\MyPanel;

use App\Custom\Notestatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Dados do "Meu painel" (home do usuário) e do seu Excel: tudo o que a tela mostra, calculado aqui e nunca na view.
 *
 * Regras de tempo (sem prazo/SLA: o painel só mede e ordena, do maior para o menor):
 *  - Atividade em aberto: tempo desde a data de ATT (atribuição) — `productions.att_at`, ou o despacho se ainda não houver ATT.
 *  - Pós-obra com informe (Fiscalização, Medição/Pagamento e Publicação): o tempo parte do `informed_at` do informe da Nota.
 *  - RI (`d5`) só entra quando o filtro "Incluir RI" está ligado.
 *
 * Filtros: service (uuid|null), from/to (datas), include_ri (bool). O período anterior tem a mesma duração e termina no dia anterior.
 */
final class MyPanelAnalytics
{
    /** Atividades cujo tempo parte da data do informe associado à produção. */
    public const INFORM_FOLDERS = ['fiscalizacao', 'pagamento', 'publicacao'];

    public const GROUP_LABELS = ['project' => 'Projeto', 'construction' => 'Construção', 'post_construction' => 'Pós-obra', 'other' => 'Outras'];

    /** Médias da equipe não dependem do usuário e varrem a base toda: ficam em cache por este tempo (segundos). */
    public const TEAM_CACHE_TTL = 600;

    /** Tamanho máximo do período (dias) para o gráfico diário não explodir. */
    public const MAX_DAYS = 366;

    /** Atividades (serviços) em que o usuário já produziu: alimenta o filtro. */
    public function activities(string $userId): Collection
    {
        return DB::table('services as s')
            ->whereIn('s.uuid', DB::table('productions')->where('user_id', $userId)->where('rejected', false)->select('service_id'))
            ->orderBy('s.service')
            ->get(['s.uuid', 's.service']);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{service: ?string, from: Carbon, to: Carbon, days: int, prev_from: Carbon, prev_to: Carbon, include_ri: bool}
     */
    public function normalize(array $filters, ?Carbon $now = null): array
    {
        $now ??= now();

        $to   = $this->date($filters['to'] ?? null, $now)->endOfDay();
        $from = $this->date($filters['from'] ?? null, $to->copy()->subDays(29))->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        if ($from->diffInDays($to) >= self::MAX_DAYS) {
            $from = $to->copy()->subDays(self::MAX_DAYS - 1)->startOfDay();
        }

        $days     = $from->diffInDays($to->copy()->startOfDay()) + 1;
        $prevTo   = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        return [
            'service'    => !empty($filters['service']) ? (string) $filters['service'] : null,
            'from'       => $from,
            'to'         => $to,
            'days'       => $days,
            'prev_from'  => $prevFrom,
            'prev_to'    => $prevTo,
            'include_ri' => (bool) ($filters['include_ri'] ?? false),
        ];
    }

    /**
     * Tudo o que a tela precisa.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function snapshot(string $userId, array $filters, ?Carbon $now = null): array
    {
        $now ??= now();
        $f = $this->normalize($filters, $now);

        $open      = $this->openRows($userId, $f, $now);
        $byService = $this->completedByActivity($userId, $f, $f['from'], $f['to']);
        $team      = $this->teamAverages($f, $byService->pluck('service_id')->all());
        $totals    = $this->completedTotals($userId, $f, $f['from'], $f['to']);
        $previous  = $this->completedTotals($userId, $f, $f['prev_from'], $f['prev_to']);

        $activities = $this->activityRows($byService, $team);

        return [
            'generated_at' => $now,
            'filters'      => $f,
            'open'         => $open,
            'summary'      => $this->summary($open, $totals, $previous, $this->completedToday($userId, $f, $now)),
            'next'         => $open[0] ?? null,
            'open_by'      => $this->openByActivity($open),
            'status'       => $this->openByStatus($open),
            'age_buckets'  => $this->ageBuckets($open),
            'activities'   => $activities,
            'stages'       => $this->stageRow($totals),
            'daily'        => $this->dailySeries($userId, $f),
            'week'         => $this->weekSeries($userId, $f, $now),
            'monthly'      => $this->monthlySeries($userId, $f),
        ];
    }

    /**
     * Snapshot + a lista completa das concluídas do período (só para o Excel; a tela não carrega isso).
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function report(string $userId, array $filters, ?Carbon $now = null): array
    {
        $snapshot              = $this->snapshot($userId, $filters, $now);
        $snapshot['completed'] = $this->completedRows($userId, $snapshot['filters']);

        return $snapshot;
    }

    /** Duração legível em dias/horas a partir de segundos ("2,5 d", "6 h", "—"). */
    public static function duration(?float $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        $days = $seconds / 86400;

        if ($days >= 1) {
            return rtrim(rtrim(number_format($days, 1, ',', '.'), '0'), ',') . ' d';
        }

        $hours = $seconds / 3600;

        return $hours >= 1 ? number_format($hours, 0, ',', '.') . ' h' : max(1, (int) round($seconds / 60)) . ' min';
    }

    // ───────────────────────────── Em aberto ─────────────────────────────

    /**
     * Atividades abertas do usuário, da mais antiga para a mais recente.
     *
     * @return array<int, array<string, mixed>>
     */
    private function openRows(string $userId, array $f, Carbon $now): array
    {
        $informedSql = self::informedSql();

        $rows = DB::table('productions as p')
            ->join('services as s', 's.uuid', '=', 'p.service_id')
            ->join('notes as n', 'n.id', '=', 'p.note_id')
            ->where('p.user_id', $userId)
            ->where('p.completed', false)
            ->where('p.rejected', false)
            ->when(!$f['include_ri'], fn ($q) => $q->where('p.d5', false))
            ->when($f['service'], fn ($q, $service) => $q->where('p.service_id', $service))
            ->selectRaw("p.id, p.note_id, n.note, s.uuid as service_id, s.service, s.folder, s.project, s.construction, s.post_construction,
                p.status, p.d5, p.priority, p.att_at, p.dispatch_at, {$informedSql} as informed_at")
            ->get();

        $today = $now->copy()->startOfDay();

        return $rows
            ->map(function ($r) use ($today) {
                $informed = $r->informed_at ? Carbon::parse($r->informed_at) : null;
                $att      = $r->att_at ? Carbon::parse($r->att_at) : null;
                $dispatch = $r->dispatch_at ? Carbon::parse($r->dispatch_at) : null;
                $since    = $informed ?? $att ?? $dispatch;
                $basis    = $informed ? 'informe' : ($att ? 'ATT' : ($dispatch ? 'despacho' : null));
                $status   = Notestatus::status($r->status);

                return [
                    'production_id' => (int) $r->id,
                    'note'          => (string) $r->note,
                    'service_id'    => $r->service_id,
                    'service'       => $r->service,
                    'folder'        => $r->folder,
                    'group'         => $this->group($r),
                    'status_code'   => (int) $r->status,
                    'status'        => $status->status,
                    'status_color'  => $status->color,
                    'ri'            => (bool) $r->d5,
                    'priority'      => (bool) $r->priority,
                    'att_at'        => $att,
                    'informed_at'   => $informed,
                    'since'         => $since,
                    'basis'         => $basis,
                    'age_days'      => $since ? max(0, (int) $since->copy()->startOfDay()->diffInDays($today)) : null,
                ];
            })
            ->sort(fn (array $a, array $b) => [$b['age_days'] ?? -1, $a['note']] <=> [$a['age_days'] ?? -1, $b['note']])
            ->values()
            ->all();
    }

    /**
     * Abertas por atividade: quantidade, mais antiga e idade média.
     *
     * @param array<int, array<string, mixed>> $open
     *
     * @return array<int, array<string, mixed>>
     */
    public function openByActivity(array $open): array
    {
        return collect($open)
            ->groupBy('service_id')
            ->map(function (Collection $items) {
                $ages = $items->pluck('age_days')->filter(fn ($d) => $d !== null);

                return [
                    'service_id' => $items->first()['service_id'],
                    'service'    => $items->first()['service'],
                    'group'      => $items->first()['group'],
                    'total'      => $items->count(),
                    'ri'         => $items->where('ri', true)->count(),
                    'oldest'     => $ages->max(),
                    'average'    => $ages->isEmpty() ? null : round($ages->avg(), 1),
                ];
            })
            ->sortByDesc(fn (array $row) => [$row['oldest'] ?? -1, $row['total']])
            ->values()
            ->all();
    }

    /**
     * Faixas de idade das abertas (só para leitura do volume, sem prazo): quantas há em cada faixa de dias.
     *
     * @param array<int, array<string, mixed>> $open
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function ageBuckets(array $open): array
    {
        $ranges = ['0-7 dias' => [0, 7], '8-15 dias' => [8, 15], '16-30 dias' => [16, 30], '31-60 dias' => [31, 60], '61+ dias' => [61, PHP_INT_MAX]];
        $ages   = collect($open)->pluck('age_days')->filter(fn ($d) => $d !== null);

        return [
            'labels' => array_keys($ranges),
            'values' => array_map(fn (array $r) => $ages->filter(fn (int $d) => $d >= $r[0] && $d <= $r[1])->count(), array_values($ranges)),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $open
     *
     * @return array<int, array{label: string, color: string, total: int}>
     */
    public function openByStatus(array $open): array
    {
        return collect($open)
            ->groupBy('status')
            ->map(fn (Collection $items, string $label) => ['label' => $label, 'color' => $items->first()['status_color'], 'total' => $items->count()])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    // ───────────────────────────── Concluídas ─────────────────────────────

    private function completedQuery(string $userId, array $f, Carbon $from, Carbon $to)
    {
        return DB::table('productions as p')
            ->join('services as s', 's.uuid', '=', 'p.service_id')
            ->where('p.user_id', $userId)
            ->where('p.completed', true)
            ->where('p.rejected', false)
            ->when(!$f['include_ri'], fn ($q) => $q->where('p.d5', false))
            ->when($f['service'], fn ($q, $service) => $q->where('p.service_id', $service))
            ->whereBetween('p.completed_at', [$from, $to]);
    }

    /** Agregados de uma conclusão: volume e tempos médios (em segundos). */
    public static function aggregateSql(): string
    {
        $since = self::sinceSql();

        return "COUNT(*) as notas,
            COALESCE(SUM(p.postes_u), 0) as postes,
            COUNT(DISTINCT DATE(p.completed_at)) as dias,
            AVG(CASE WHEN {$since} IS NOT NULL THEN GREATEST(TIMESTAMPDIFF(SECOND, {$since}, p.completed_at), 0) END) as avg_total,
            AVG(CASE WHEN p.dispatch_at IS NOT NULL AND p.att_at IS NOT NULL THEN GREATEST(TIMESTAMPDIFF(SECOND, p.dispatch_at, p.att_at), 0) END) as avg_queue,
            AVG(CASE WHEN p.att_at IS NOT NULL THEN GREATEST(TIMESTAMPDIFF(SECOND, p.att_at, p.completed_at) - COALESCE(p.stopped, 0), 0) END) as avg_exec,
            AVG(COALESCE(p.stopped, 0)) as avg_stopped";
    }

    private function completedByActivity(string $userId, array $f, Carbon $from, Carbon $to): Collection
    {
        return $this->completedQuery($userId, $f, $from, $to)
            ->selectRaw('s.uuid as service_id, s.service, s.folder, s.project, s.construction, s.post_construction, ' . $this->aggregateSql())
            ->groupBy('s.uuid', 's.service', 's.folder', 's.project', 's.construction', 's.post_construction')
            ->orderByDesc('notas')
            ->get();
    }

    private function completedTotals(string $userId, array $f, Carbon $from, Carbon $to): object
    {
        $row = $this->completedQuery($userId, $f, $from, $to)->selectRaw($this->aggregateSql())->first();

        return $row ?? (object) ['notas' => 0, 'postes' => 0, 'dias' => 0, 'avg_total' => null, 'avg_queue' => null, 'avg_exec' => null, 'avg_stopped' => null];
    }

    /** @return array{notas: int, postes: int} */
    private function completedToday(string $userId, array $f, Carbon $now): array
    {
        $row = $this->completedQuery($userId, $f, $now->copy()->startOfDay(), $now->copy()->endOfDay())
            ->selectRaw('COUNT(*) as notas, COALESCE(SUM(p.postes_u), 0) as postes')
            ->first();

        return ['notas' => (int) ($row->notas ?? 0), 'postes' => (int) ($row->postes ?? 0)];
    }

    /**
     * Média da equipe por atividade: notas/postes por pessoa por dia trabalhado, no mesmo período.
     *
     * @param array<int, string> $serviceIds
     */
    private function teamAverages(array $f, array $serviceIds): Collection
    {
        if ($serviceIds === []) {
            return collect();
        }

        $key = 'my-panel:team:' . md5(json_encode([$f['from']->toDateString(), $f['to']->toDateString(), $f['include_ri'], $serviceIds]));

        return collect(Cache::remember($key, self::TEAM_CACHE_TTL, function () use ($f, $serviceIds) {
            $perDay = DB::table('productions as p')
                ->where('p.completed', true)
                ->where('p.rejected', false)
                ->when(!$f['include_ri'], fn ($q) => $q->where('p.d5', false))
                ->whereIn('p.service_id', $serviceIds)
                ->whereBetween('p.completed_at', [$f['from'], $f['to']])
                ->whereNotNull('p.user_id')
                ->selectRaw('p.service_id, p.user_id, DATE(p.completed_at) as d, COUNT(*) as n, COALESCE(SUM(p.postes_u), 0) as pst')
                ->groupBy('p.service_id', 'p.user_id', 'd');

            return DB::query()->fromSub($perDay, 't')
                ->selectRaw('service_id, AVG(n) as avg_notas, AVG(pst) as avg_postes, COUNT(DISTINCT user_id) as pessoas')
                ->groupBy('service_id')
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        }))->map(fn (array $r) => (object) $r)->keyBy('service_id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function activityRows(Collection $byService, Collection $team): array
    {
        $totalNotas = max(1, (int) $byService->sum('notas'));

        return $byService->map(function ($r) use ($team, $totalNotas) {
            $avg     = $team->get($r->service_id);
            $perDay  = $r->dias > 0 ? $r->notas / $r->dias : 0;
            $teamDay = $avg ? (float) $avg->avg_notas : null;

            return [
                'service_id'   => $r->service_id,
                'service'      => $r->service,
                'group'        => $this->group($r),
                'notas'        => (int) $r->notas,
                'postes'       => (int) $r->postes,
                'dias'         => (int) $r->dias,
                'share'        => round($r->notas / $totalNotas * 100, 1),
                'per_day'      => round($perDay, 1),
                'team_per_day' => $teamDay !== null ? round($teamDay, 1) : null,
                'team_people'  => $avg ? (int) $avg->pessoas : null,
                'delta'        => $teamDay ? round(($perDay - $teamDay) / $teamDay * 100, 1) : null,
                'avg_total'    => $r->avg_total !== null ? (float) $r->avg_total : null,
                'avg_queue'    => $r->avg_queue !== null ? (float) $r->avg_queue : null,
                'avg_exec'     => $r->avg_exec !== null ? (float) $r->avg_exec : null,
                'avg_stopped'  => $r->avg_stopped !== null ? (float) $r->avg_stopped : null,
            ];
        })->values()->all();
    }

    /**
     * Tempo médio por etapa das concluídas do período.
     *
     * @return array<int, array{key: string, label: string, seconds: ?float}>
     */
    public function stageRow(object $totals): array
    {
        return [
            ['key' => 'queue', 'label' => 'Na fila (despacho até a ATT)', 'seconds' => $totals->avg_queue !== null ? (float) $totals->avg_queue : null],
            ['key' => 'exec', 'label' => 'Em execução (sem as pausas)', 'seconds' => $totals->avg_exec !== null ? (float) $totals->avg_exec : null],
            ['key' => 'stopped', 'label' => 'Parada (pausas)', 'seconds' => $totals->avg_stopped !== null ? (float) $totals->avg_stopped : null],
            ['key' => 'total', 'label' => 'Do início até concluir', 'seconds' => $totals->avg_total !== null ? (float) $totals->avg_total : null],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function completedRows(string $userId, array $f): array
    {
        $informedSql = self::informedSql();
        $since       = $this->sinceSql();

        return $this->completedQuery($userId, $f, $f['from'], $f['to'])
            ->join('notes as n', 'n.id', '=', 'p.note_id')
            ->leftJoin('analises as a', 'a.production_id', '=', 'p.id')
            ->selectRaw("p.id, n.note, s.service, s.folder, s.project, s.construction, s.post_construction, p.d5, p.postes_u, p.status,
                p.dispatch_at, p.att_at, p.completed_at, p.stopped, a.conclusion, {$informedSql} as informed_at,
                CASE WHEN {$since} IS NOT NULL THEN GREATEST(TIMESTAMPDIFF(SECOND, {$since}, p.completed_at), 0) END as total_seconds")
            ->orderBy('p.completed_at')
            ->orderBy('p.id')
            ->get()
            ->map(fn ($r) => [
                'production_id' => (int) $r->id,
                'note'          => (string) $r->note,
                'service'       => $r->service,
                'group'         => $this->group($r),
                'ri'            => (bool) $r->d5,
                'dispatch_at'   => $r->dispatch_at ? Carbon::parse($r->dispatch_at) : null,
                'att_at'        => $r->att_at ? Carbon::parse($r->att_at) : null,
                'informed_at'   => $r->informed_at ? Carbon::parse($r->informed_at) : null,
                'completed_at'  => Carbon::parse($r->completed_at),
                'total_seconds' => $r->total_seconds !== null ? (float) $r->total_seconds : null,
                'stopped'       => $r->stopped !== null ? (float) $r->stopped : null,
                'postes'        => (int) $r->postes_u,
                'status'        => Notestatus::status($r->status)->status,
                'conclusion'    => $r->conclusion,
            ])
            ->all();
    }

    // ───────────────────────────── KPIs ─────────────────────────────

    /**
     * @param array<int, array<string, mixed>> $open
     *
     * @return array<string, mixed>
     */
    private function summary(array $open, object $totals, object $previous, array $today): array
    {
        $ages    = collect($open)->pluck('age_days')->filter(fn ($d) => $d !== null);
        $running = collect($open)->where('status_code', 3)->count();
        $paused  = collect($open)->where('status_code', 4)->count();

        return [
            'open'          => count($open),
            'open_services' => collect($open)->pluck('service_id')->unique()->count(),
            'running'       => $running,
            'paused'        => $paused,
            'oldest'        => $ages->max(),
            'average'       => $ages->isEmpty() ? null : round($ages->avg(), 1),
            'ri'            => collect($open)->where('ri', true)->count(),
            'done'          => (int) $totals->notas,
            'done_postes'   => (int) $totals->postes,
            'done_prev'     => (int) $previous->notas,
            'postes_prev'   => (int) $previous->postes,
            'done_delta'    => $this->delta((int) $totals->notas, (int) $previous->notas),
            'postes_delta'  => $this->delta((int) $totals->postes, (int) $previous->postes),
            'today'         => $today['notas'],
            'today_postes'  => $today['postes'],
        ];
    }

    private function delta(int|float $current, int|float $previous): ?float
    {
        return $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null;
    }

    // ───────────────────────────── Séries ─────────────────────────────

    /**
     * Recebidas (ATT) e concluídas por dia, no período.
     *
     * @return array<string, mixed>
     */
    private function dailySeries(string $userId, array $f): array
    {
        $received = DB::table('productions as p')
            ->where('p.user_id', $userId)
            ->where('p.rejected', false)
            ->when(!$f['include_ri'], fn ($q) => $q->where('p.d5', false))
            ->when($f['service'], fn ($q, $service) => $q->where('p.service_id', $service))
            ->whereBetween('p.att_at', [$f['from'], $f['to']])
            ->selectRaw('DATE(p.att_at) as d, COUNT(*) as n')
            ->groupBy('d')
            ->pluck('n', 'd');

        $done = $this->completedQuery($userId, $f, $f['from'], $f['to'])
            ->selectRaw('DATE(p.completed_at) as d, COUNT(*) as n, COALESCE(SUM(p.postes_u), 0) as pst')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $labels = $dates = $in = $out = $postes = [];

        for ($day = $f['from']->copy()->startOfDay(); $day->lte($f['to']); $day->addDay()) {
            $key      = $day->toDateString();
            $dates[]  = $key;
            $labels[] = $day->format('d/m');
            $in[]     = (int) $received->get($key, 0);
            $out[]    = (int) ($done->get($key)->n ?? 0);
            $postes[] = (int) ($done->get($key)->pst ?? 0);
        }

        return ['labels' => $labels, 'dates' => $dates, 'received' => $in, 'completed' => $out, 'postes' => $postes];
    }

    /**
     * Concluídas nos últimos 7 dias e média da equipe por pessoa por dia (referência, não meta fixa).
     *
     * @return array<string, mixed>
     */
    private function weekSeries(string $userId, array $f, Carbon $now): array
    {
        $to   = $now->copy()->endOfDay();
        $from = $now->copy()->subDays(6)->startOfDay();

        $done = $this->completedQuery($userId, $f, $from, $to)
            ->selectRaw('DATE(p.completed_at) as d, COUNT(*) as n')
            ->groupBy('d')
            ->pluck('n', 'd');

        $key  = 'my-panel:week:' . md5(json_encode([$from->toDateString(), $f['include_ri'], $f['service']]));
        $team = Cache::remember($key, self::TEAM_CACHE_TTL, function () use ($f, $from, $to) {
            $perDay = DB::table('productions as p')
                ->where('p.completed', true)
                ->where('p.rejected', false)
                ->when(!$f['include_ri'], fn ($q) => $q->where('p.d5', false))
                ->when($f['service'], fn ($q, $service) => $q->where('p.service_id', $service))
                ->whereNotNull('p.user_id')
                ->whereBetween('p.completed_at', [$from, $to])
                ->selectRaw('p.user_id, DATE(p.completed_at) as d, COUNT(*) as n')
                ->groupBy('p.user_id', 'd');

            $avg = DB::query()->fromSub($perDay, 't')->selectRaw('AVG(n) as avg_n')->value('avg_n');

            return $avg !== null ? (float) $avg : -1.0;
        });
        $team = $team >= 0 ? $team : null;

        $labels = $values = [];

        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $labels[] = $day->translatedFormat('D d');
            $values[] = (int) $done->get($day->toDateString(), 0);
        }

        return ['labels' => $labels, 'completed' => $values, 'team' => $team !== null ? round((float) $team, 1) : null, 'total' => array_sum($values)];
    }

    /**
     * Notas concluídas por mês e grupo (Projeto/Construção/Pós-obra), nos 12 meses até o fim do período.
     *
     * @return array<string, mixed>
     */
    private function monthlySeries(string $userId, array $f): array
    {
        $end   = $f['to']->copy()->endOfMonth();
        $start = $f['to']->copy()->subMonths(11)->startOfMonth();

        $rows = $this->completedQuery($userId, $f, $start, $end)
            ->selectRaw("DATE_FORMAT(p.completed_at, '%Y-%m') as ym,
                SUM(CASE WHEN s.project = 1 THEN 1 ELSE 0 END) as project,
                SUM(CASE WHEN s.construction = 1 THEN 1 ELSE 0 END) as construction,
                SUM(CASE WHEN s.post_construction = 1 THEN 1 ELSE 0 END) as post_construction,
                SUM(CASE WHEN s.project = 0 AND s.construction = 0 AND s.post_construction = 0 THEN 1 ELSE 0 END) as other,
                COALESCE(SUM(p.postes_u), 0) as postes")
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        $labels = $months = [];
        $series = array_fill_keys(array_keys(self::GROUP_LABELS), []);
        $postes = [];

        for ($month = $start->copy(); $month->lte($end); $month->addMonth()) {
            $key      = $month->format('Y-m');
            $months[] = $key;
            $labels[] = $month->translatedFormat('M/y');
            $postes[] = (int) ($rows->get($key)->postes ?? 0);

            foreach (array_keys(self::GROUP_LABELS) as $group) {
                $series[$group][] = (int) ($rows->get($key)->{$group} ?? 0);
            }
        }

        return ['labels' => $labels, 'months' => $months, 'series' => $series, 'postes' => $postes];
    }

    // ───────────────────────────── Apoio ─────────────────────────────

    /** Subconsulta do informe (data mais recente, não cancelado) — só avaliada nas atividades pós-obra. */
    public static function informedSql(): string
    {
        $folders = "'" . implode("','", self::INFORM_FOLDERS) . "'";

        return "CASE WHEN s.folder IN ({$folders}) THEN (SELECT MAX(w.informed_at) FROM work_reports w WHERE w.note_id = p.note_id AND w.canceled = 0) END";
    }

    /** Onde o tempo começa: informe (pós-obra) → ATT → despacho. */
    public static function sinceSql(): string
    {
        return 'COALESCE(' . self::informedSql() . ', p.att_at, p.dispatch_at)';
    }

    public static function group(object $row): string
    {
        return match (true) {
            (bool) $row->post_construction => 'post_construction',
            (bool) $row->construction      => 'construction',
            (bool) $row->project           => 'project',
            default                        => 'other',
        };
    }

    private function date(mixed $value, Carbon $fallback): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : $fallback->copy();
        } catch (\Throwable) {
            return $fallback->copy();
        }
    }
}
