<?php

namespace App\Services\Reports\TeamPanel;

use App\Custom\Notestatus;
use App\Services\Home\MyPanel\MyPanelAnalytics as Panel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Dados do "Painel da equipe" (Serviços → Relatórios, gerência) e do seu Excel: a mesma leitura do "Meu painel", mas sobre a equipe toda.
 * Tudo o que a tela mostra é calculado aqui e nunca na view. As regras de tempo são as do Meu painel (`MyPanelAnalytics`):
 * aberta conta desde a ATT (pós-obra: desde o informe da Nota); sem prazo/SLA; RI (`d5`) só com "Incluir RI".
 *
 * Filtros: company (uuid|null), service (uuid|null), user (uuid|null), from/to (datas), include_ri (bool).
 * Média da equipe = notas por pessoa em cada dia trabalhado, dentro do filtro aplicado (empresa/atividade/pessoa).
 */
final class TeamPanelAnalytics
{
    public const NO_OWNER = 'Sem responsável';

    /** Pessoas sem nenhuma conclusão no período só entram na lista se produziram (ou têm abertas) nestes últimos meses. */
    public const ACTIVE_MONTHS = 6;

    /** Ordem em que a tela carrega as partes (rápidas primeiro; a série mensal por último). */
    public const PARTS = ['open', 'daily', 'production', 'monthly'];

    /** Cache de cada parte por filtro e dia (segundos): trocar de filtro e voltar, ou reabrir a tela, não recalcula. */
    public const CACHE_TTL = 120;

    /** Listas dos filtros (empresas, atividades, pessoas). */
    public const FILTER_CACHE_TTL = 600;

    /** Teto da lista de concluídas no Excel (a aba avisa quando corta). */
    public const COMPLETED_LIMIT = 50000;

    public function __construct(private readonly Panel $base)
    {
    }

    /** Empresas que já produziram (inclui rejeitadas: só assim o DISTINCT lê o índice, 1 s → 0 s): alimenta o filtro. As listas dos filtros mudam pouco e varrem a base: cache de 10 min. */
    public function companies(): Collection
    {
        return Cache::remember('team-panel:filter:companies', self::FILTER_CACHE_TTL, fn () => DB::table('companies as c')
            ->whereIn('c.id', DB::table('productions')->distinct()->pluck('company_id'))
            ->orderBy('c.name')
            ->get(['c.id', 'c.name']));
    }

    /** Atividades (serviços) com produção, opcionalmente dentro de uma empresa. */
    public function activities(?string $company = null): Collection
    {
        return Cache::remember('team-panel:filter:activities:' . ($company ?? 'all'), self::FILTER_CACHE_TTL, fn () => DB::table('services as s')
            ->whereIn('s.uuid', DB::table('productions')->distinct()->when($company, fn ($q) => $q->where('company_id', $company))->pluck('service_id'))
            ->orderBy('s.service')
            ->get(['s.uuid', 's.service']));
    }

    /** Pessoas com produção, opcionalmente dentro da empresa/atividade escolhida (cascata do filtro). */
    public function people(?string $company = null, ?string $service = null): Collection
    {
        return Cache::remember('team-panel:filter:people:' . ($company ?? 'all') . ':' . ($service ?? 'all'), self::FILTER_CACHE_TTL, fn () => DB::table('users as u')
            ->whereIn('u.id', DB::table('productions')
                ->distinct()
                ->whereNotNull('user_id')
                ->when($company, fn ($q) => $q->where('company_id', $company))
                ->when($service, fn ($q) => $q->where('service_id', $service))
                ->pluck('user_id'))
            ->orderBy('u.name')
            ->get(['u.id', 'u.name']));
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function normalize(array $filters, ?Carbon $now = null): array
    {
        return $this->base->normalize($filters, $now) + [
            'company' => !empty($filters['company']) ? (string) $filters['company'] : null,
            'user'    => !empty($filters['user']) ? (string) $filters['user'] : null,
        ];
    }

    /**
     * Tudo o que a tela precisa, de uma vez (Excel, testes e recálculo por filtro). Sem cache por padrão.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function snapshot(array $filters, ?Carbon $now = null, bool $cache = false): array
    {
        $now ??= now();

        return $this->assemble($this->normalize($filters, $now), $now, self::PARTS, $cache);
    }

    /**
     * Junta só as partes pedidas sobre o esqueleto vazio (`placeholder`): a tela entrega o painel aos poucos, na ordem de PARTS.
     *
     * - open:       abertas, fila, carga por pessoa/empresa, situação, idades
     * - daily:      recebidas/concluídas/pessoas por dia (também alimenta os mini-gráficos dos cartões)
     * - production: totais e comparação, pessoas, empresas, atividades, etapas
     * - monthly:    12 meses por grupo (a mais pesada)
     *
     * @param array<string, mixed>    $f normalizado (`normalize`)
     * @param array<int, string>      $parts
     *
     * @return array<string, mixed>
     */
    public function assemble(array $f, Carbon $now, array $parts, bool $cache = false): array
    {
        $data = $this->placeholder($f, $now);

        foreach ($parts as $name) {
            $chunk   = $this->part($name, $f, $now, $cache);
            $summary = array_merge($data['summary'], $chunk['summary'] ?? []);
            $data    = array_replace($data, $chunk);

            $data['summary'] = $summary;
        }

        return $data;
    }

    /** Uma parte do painel, com cache curto (por filtro e dia) quando a tela pede. */
    public function part(string $name, array $f, Carbon $now, bool $cache = false): array
    {
        $compute = fn () => match ($name) {
            'open'       => $this->openPart($f, $now),
            'daily'      => ['daily' => $this->dailySeries($f)],
            'production' => $this->productionPart($f, $now, $cache),
            'monthly'    => ['monthly' => $this->monthlySeries($f)],
        };

        if (!$cache) {
            return $compute();
        }

        $key = 'team-panel:' . md5(json_encode([$name, $f['from']->toDateString(), $f['to']->toDateString(), $f['company'], $f['service'], $f['user'], $f['include_ri'], $now->toDateString()]));

        return Cache::remember($key, self::CACHE_TTL, $compute);
    }

    /** Forma completa e vazia do snapshot: a tela desenha o que já chegou e o esqueleto no resto. */
    public function placeholder(array $f, Carbon $now): array
    {
        return [
            'generated_at' => $now,
            'filters'      => $f,
            'open'         => [],
            'summary'      => [
                'open'        => 0, 'open_people' => 0, 'unassigned' => 0, 'open_services' => 0, 'running' => 0, 'paused' => 0, 'oldest' => null,
                'average'     => null, 'over_30' => 0, 'ri' => 0, 'done' => 0, 'done_postes' => 0, 'done_prev' => 0, 'postes_prev' => 0,
                'done_delta'  => null, 'postes_delta' => null, 'today' => 0, 'today_postes' => 0, 'people' => 0, 'people_prev' => 0,
                'idle_people' => 0, 'per_person' => null,
            ],
            'next'         => null,
            'open_by'      => [],
            'open_by_user' => [],
            'open_by_co'   => [],
            'status'       => [],
            'age_buckets'  => ['labels' => [], 'values' => []],
            'users'        => [],
            'companies'    => [],
            'activities'   => [],
            'stages'       => $this->base->stageRow((object) ['avg_total' => null, 'avg_queue' => null, 'avg_exec' => null, 'avg_stopped' => null]),
            'team_per_day' => null,
            'daily'        => ['labels' => [], 'dates' => [], 'received' => [], 'completed' => [], 'postes' => [], 'people' => []],
            'monthly'      => ['labels' => [], 'months' => [], 'series' => array_fill_keys(array_keys(Panel::GROUP_LABELS), []), 'postes' => [], 'people' => []],
        ];
    }

    /** @return array<string, mixed> */
    private function openPart(array $f, Carbon $now): array
    {
        $open  = $this->openRows($f, $now);
        $ages  = collect($open)->pluck('age_days')->filter(fn ($d) => $d !== null);
        $owned = collect($open)->whereNotNull('user_id');

        return [
            'open'         => $open,
            'next'         => $open[0] ?? null,
            'open_by'      => $this->base->openByActivity($open),
            'open_by_user' => $this->openByOwner($open, 'user_id', 'user'),
            'open_by_co'   => $this->openByOwner($open, 'company_id', 'company'),
            'status'       => $this->base->openByStatus($open),
            'age_buckets'  => $this->base->ageBuckets($open),
            'summary'      => [
                'open'          => count($open),
                'open_people'   => $owned->pluck('user_id')->unique()->count(),
                'unassigned'    => collect($open)->whereNull('user_id')->count(),
                'open_services' => collect($open)->pluck('service_id')->unique()->count(),
                'running'       => collect($open)->where('status_code', 3)->count(),
                'paused'        => collect($open)->where('status_code', 4)->count(),
                'oldest'        => $ages->max(),
                'average'       => $ages->isEmpty() ? null : round($ages->avg(), 1),
                'over_30'       => $ages->filter(fn (int $d) => $d > 30)->count(),
                'ri'            => collect($open)->where('ri', true)->count(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function productionPart(array $f, Carbon $now, bool $cache): array
    {
        $open     = $this->part('open', $f, $now, $cache)['open'];
        $totals   = $this->completedTotals($f, $f['from'], $f['to']);
        $previous = $this->completedTotals($f, $f['prev_from'], $f['prev_to']);
        $today    = $this->completedToday($f, $now);
        $users    = $this->userRows($f, $open, $now);
        $teamDay  = $this->perPersonDay($totals);

        return [
            'users'        => $users,
            'companies'    => $this->companyRows($f, $open, $teamDay),
            'activities'   => $this->activityRows($f, $teamDay),
            'stages'       => $this->base->stageRow($totals),
            'team_per_day' => $teamDay,
            'summary'      => [
                'done'         => (int) $totals->notas,
                'done_postes'  => (int) $totals->postes,
                'done_prev'    => (int) $previous->notas,
                'postes_prev'  => (int) $previous->postes,
                'done_delta'   => $this->delta((int) $totals->notas, (int) $previous->notas),
                'postes_delta' => $this->delta((int) $totals->postes, (int) $previous->postes),
                'today'        => $today['notas'],
                'today_postes' => $today['postes'],
                'people'       => (int) $totals->pessoas,
                'people_prev'  => (int) $previous->pessoas,
                'idle_people'  => collect($users)->where('idle', true)->count(),
                'per_person'   => $teamDay,
            ],
        ];
    }

    /**
     * Snapshot + listas completas para o Excel (a tela só resume).
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function report(array $filters, ?Carbon $now = null): array
    {
        $snapshot                    = $this->snapshot($filters, $now);
        $snapshot['completed']       = $this->completedRows($snapshot['filters']);
        $snapshot['completed_limit'] = self::COMPLETED_LIMIT;
        $snapshot['matrix']          = $this->userActivityMatrix($snapshot['filters']);

        return $snapshot;
    }

    // ───────────────────────────── Em aberto ─────────────────────────────

    /**
     * Atividades abertas da equipe, da mais antiga para a mais recente.
     *
     * @return array<int, array<string, mixed>>
     */
    private function openRows(array $f, Carbon $now): array
    {
        $informedSql = Panel::informedSql();

        $rows = $this->scope(DB::table('productions as p'), $f)
            ->join('services as s', 's.uuid', '=', 'p.service_id')
            ->join('notes as n', 'n.id', '=', 'p.note_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('companies as c', 'c.id', '=', 'p.company_id')
            ->where('p.completed', false)
            ->selectRaw("p.id, p.note_id, n.note, s.uuid as service_id, s.service, s.folder, s.project, s.construction, s.post_construction,
                p.user_id, u.name as user_name, p.company_id, c.name as company_name,
                p.status, p.d5, p.priority, p.att_at, p.dispatch_at, {$informedSql} as informed_at")
            ->get();

        $today = $now->copy()->startOfDay();

        return $rows
            ->map(function ($r) use ($today) {
                $informed = $r->informed_at ? Carbon::parse($r->informed_at) : null;
                $att      = $r->att_at ? Carbon::parse($r->att_at) : null;
                $dispatch = $r->dispatch_at ? Carbon::parse($r->dispatch_at) : null;
                $since    = $informed ?? $att ?? $dispatch;
                $status   = Notestatus::status($r->status);

                return [
                    'production_id' => (int) $r->id,
                    'note'          => (string) $r->note,
                    'service_id'    => $r->service_id,
                    'service'       => $r->service,
                    'folder'        => $r->folder,
                    'group'         => Panel::group($r),
                    'user_id'       => $r->user_id,
                    'user'          => $r->user_name ?: self::NO_OWNER,
                    'company_id'    => $r->company_id,
                    'company'       => $r->company_name ?: '—',
                    'status_code'   => (int) $r->status,
                    'status'        => $status->status,
                    'status_color'  => $status->color,
                    'ri'            => (bool) $r->d5,
                    'priority'      => (bool) $r->priority,
                    'att_at'        => $att,
                    'informed_at'   => $informed,
                    'since'         => $since,
                    'basis'         => $informed ? 'informe' : ($att ? 'ATT' : ($dispatch ? 'despacho' : null)),
                    'age_days'      => $since ? max(0, (int) $since->copy()->startOfDay()->diffInDays($today)) : null,
                ];
            })
            ->sort(fn (array $a, array $b) => [$b['age_days'] ?? -1, $a['note']] <=> [$a['age_days'] ?? -1, $b['note']])
            ->values()
            ->all();
    }

    /**
     * Abertas por pessoa ou por empresa: quantidade, mais antiga, idade média e as faixas usadas no gráfico de carga.
     *
     * @param array<int, array<string, mixed>> $open
     *
     * @return array<int, array<string, mixed>>
     */
    private function openByOwner(array $open, string $idKey, string $nameKey): array
    {
        return collect($open)
            ->groupBy(fn (array $r) => $r[$idKey] ?? '—')
            ->map(function (Collection $items) use ($idKey, $nameKey) {
                $ages = $items->pluck('age_days')->filter(fn ($d) => $d !== null);
                $in   = fn (int $from, int $to) => $ages->filter(fn (int $d) => $d >= $from && $d <= $to)->count();

                return [
                    'id'      => $items->first()[$idKey],
                    'name'    => $items->first()[$nameKey],
                    'total'   => $items->count(),
                    'ri'      => $items->where('ri', true)->count(),
                    'running' => $items->where('status_code', 3)->count(),
                    'paused'  => $items->where('status_code', 4)->count(),
                    'oldest'  => $ages->max(),
                    'average' => $ages->isEmpty() ? null : round($ages->avg(), 1),
                    'b_new'   => $in(0, 15),
                    'b_mid'   => $in(16, 30),
                    'b_old'   => $in(31, PHP_INT_MAX),
                ];
            })
            ->sortByDesc(fn (array $row) => [$row['total'], $row['oldest'] ?? -1])
            ->values()
            ->all();
    }

    // ───────────────────────────── Concluídas ─────────────────────────────

    /** Filtros comuns (empresa, atividade, pessoa, RI, não rejeitada). */
    private function scope($query, array $f)
    {
        return $query
            ->where('p.rejected', false)
            ->when(!$f['include_ri'], fn ($q) => $q->where('p.d5', false))
            ->when($f['service'], fn ($q, $v) => $q->where('p.service_id', $v))
            ->when($f['company'], fn ($q, $v) => $q->where('p.company_id', $v))
            ->when($f['user'], fn ($q, $v) => $q->where('p.user_id', $v));
    }

    private function completedQuery(array $f, Carbon $from, Carbon $to)
    {
        return $this->scope(DB::table('productions as p'), $f)
            ->join('services as s', 's.uuid', '=', 'p.service_id')
            ->where('p.completed', true)
            ->whereBetween('p.completed_at', [$from, $to]);
    }

    /** Agregados do Meu painel + pessoas distintas e "pessoa-dias" (base da média por pessoa por dia trabalhado). */
    private function aggregateSql(): string
    {
        return Panel::aggregateSql() . ",
            COUNT(DISTINCT p.user_id) as pessoas,
            COUNT(DISTINCT CONCAT(COALESCE(p.user_id, ''), DATE(p.completed_at))) as pessoa_dias";
    }

    private function completedTotals(array $f, Carbon $from, Carbon $to): object
    {
        $row = $this->completedQuery($f, $from, $to)->selectRaw($this->aggregateSql())->first();

        return $row ?? (object) ['notas' => 0, 'postes' => 0, 'dias' => 0, 'pessoas' => 0, 'pessoa_dias' => 0, 'avg_total' => null, 'avg_queue' => null, 'avg_exec' => null, 'avg_stopped' => null];
    }

    /** Notas por pessoa em cada dia trabalhado (a referência da equipe). */
    private function perPersonDay(object $totals): ?float
    {
        return $totals->pessoa_dias > 0 ? round($totals->notas / $totals->pessoa_dias, 2) : null;
    }

    /** @return array{notas: int, postes: int} */
    private function completedToday(array $f, Carbon $now): array
    {
        $row = $this->completedQuery($f, $now->copy()->startOfDay(), $now->copy()->endOfDay())
            ->selectRaw('COUNT(*) as notas, COALESCE(SUM(p.postes_u), 0) as postes')
            ->first();

        return ['notas' => (int) ($row->notas ?? 0), 'postes' => (int) ($row->postes ?? 0)];
    }

    /**
     * Uma linha por pessoa: produção do período, comparação com a equipe e o que ainda tem aberto. Quem produziu (ou tem abertas)
     * nos últimos meses e não concluiu nada no período aparece com 0 — é a resposta para "quem está parado?".
     *
     * @param array<int, array<string, mixed>> $open
     *
     * @return array<int, array<string, mixed>>
     */
    private function userRows(array $f, array $open, Carbon $now): array
    {
        $done = $this->completedQuery($f, $f['from'], $f['to'])
            ->whereNotNull('p.user_id')
            ->selectRaw($this->aggregateSql() . ', p.user_id, MAX(p.completed_at) as last_done')
            ->groupBy('p.user_id')
            ->get()
            ->keyBy('user_id');

        $totalDone = max(1, (int) $done->sum('notas'));
        $teamDay   = $done->sum('dias') > 0 ? $done->sum('notas') / $done->sum('dias') : null;
        $openBy    = collect($this->openByOwner($open, 'user_id', 'user'))->filter(fn ($r) => $r['id'] !== null)->keyBy('id');

        // Pessoas que não concluíram no período mas estão ativas (abertas ou produção recente).
        $recent = $this->scope(DB::table('productions as p'), $f)
            ->whereNotNull('p.user_id')
            ->where(fn ($q) => $q->where('p.completed', false)->orWhere('p.completed_at', '>=', $f['to']->copy()->subMonths(self::ACTIVE_MONTHS)))
            ->distinct()
            ->pluck('p.user_id');

        $ids   = $done->keys()->merge($openBy->keys())->merge($recent)->unique()->values();
        $names = DB::table('users as u')
            ->leftJoin('companies as c', 'c.id', '=', 'u.company_id')
            ->whereIn('u.id', $ids)
            ->get(['u.id', 'u.name', 'c.name as company'])
            ->keyBy('id');

        return $ids->map(function (string $id) use ($done, $openBy, $names, $totalDone, $teamDay) {
            $d      = $done->get($id);
            $o      = $openBy->get($id);
            $notas  = (int) ($d->notas ?? 0);
            $dias   = (int) ($d->dias ?? 0);
            $perDay = $dias > 0 ? $notas / $dias : null;

            return [
                'user_id'      => $id,
                'name'         => $names->get($id)->name ?? 'Usuário removido',
                'company'      => $names->get($id)->company ?? '—',
                'notas'        => $notas,
                'postes'       => (int) ($d->postes ?? 0),
                'dias'         => $dias,
                'share'        => round($notas / $totalDone * 100, 1),
                'per_day'      => $perDay !== null ? round($perDay, 1) : null,
                'team_per_day' => $teamDay !== null ? round($teamDay, 1) : null,
                'delta'        => $perDay !== null && $teamDay ? round(($perDay - $teamDay) / $teamDay * 100, 1) : null,
                'avg_total'    => isset($d->avg_total) ? (float) $d->avg_total : null,
                'avg_stopped'  => isset($d->avg_stopped) ? (float) $d->avg_stopped : null,
                'last_done'    => isset($d->last_done) ? Carbon::parse($d->last_done) : null,
                'open'         => (int) ($o['total'] ?? 0),
                'open_ri'      => (int) ($o['ri'] ?? 0),
                'open_oldest'  => $o['oldest'] ?? null,
                'open_average' => $o['average'] ?? null,
                'idle'         => $notas === 0,
            ];
        })
            ->sort(fn (array $a, array $b) => [$b['notas'], $b['open'], $a['name']] <=> [$a['notas'], $a['open'], $b['name']])
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $open
     *
     * @return array<int, array<string, mixed>>
     */
    private function companyRows(array $f, array $open, ?float $teamDay): array
    {
        $done = $this->completedQuery($f, $f['from'], $f['to'])
            ->leftJoin('companies as c', 'c.id', '=', 'p.company_id')
            ->selectRaw($this->aggregateSql() . ', p.company_id, c.name as company')
            ->groupBy('p.company_id', 'c.name')
            ->get();

        $openBy = collect($this->openByOwner($open, 'company_id', 'company'))->keyBy('id');
        $total  = max(1, (int) $done->sum('notas'));
        $rows   = $done->keyBy('company_id');

        $ids = $rows->keys()->merge($openBy->keys())->unique()->filter()->values();

        return $ids->map(function ($id) use ($rows, $openBy, $total, $teamDay) {
            $d      = $rows->get($id);
            $o      = $openBy->get($id);
            $notas  = (int) ($d->notas ?? 0);
            $perDay = ($d->pessoa_dias ?? 0) > 0 ? $notas / $d->pessoa_dias : null;

            return [
                'company_id'   => $id,
                'name'         => $d->company ?? $o['name'] ?? '—',
                'pessoas'      => (int) ($d->pessoas ?? 0),
                'notas'        => $notas,
                'postes'       => (int) ($d->postes ?? 0),
                'share'        => round($notas / $total * 100, 1),
                'per_day'      => $perDay !== null ? round($perDay, 1) : null,
                'delta'        => $perDay !== null && $teamDay ? round(($perDay - $teamDay) / $teamDay * 100, 1) : null,
                'avg_total'    => isset($d->avg_total) ? (float) $d->avg_total : null,
                'open'         => (int) ($o['total'] ?? 0),
                'open_oldest'  => $o['oldest'] ?? null,
                'open_average' => $o['average'] ?? null,
            ];
        })
            ->sort(fn (array $a, array $b) => [$b['notas'], $b['open']] <=> [$a['notas'], $a['open']])
            ->values()
            ->all();
    }

    /**
     * Produtividade por atividade, comparada com a média da equipe naquela atividade (notas por pessoa/dia trabalhado).
     *
     * @return array<int, array<string, mixed>>
     */
    private function activityRows(array $f, ?float $teamDay): array
    {
        $rows = $this->completedQuery($f, $f['from'], $f['to'])
            ->selectRaw('s.uuid as service_id, s.service, s.folder, s.project, s.construction, s.post_construction, ' . $this->aggregateSql())
            ->groupBy('s.uuid', 's.service', 's.folder', 's.project', 's.construction', 's.post_construction')
            ->orderByDesc('notas')
            ->get();
        $total = max(1, (int) $rows->sum('notas'));

        return $rows->map(function ($r) use ($total, $teamDay) {
            $perDay = $r->pessoa_dias > 0 ? $r->notas / $r->pessoa_dias : null;

            return [
                'service_id'   => $r->service_id,
                'service'      => $r->service,
                'group'        => Panel::group($r),
                'notas'        => (int) $r->notas,
                'postes'       => (int) $r->postes,
                'pessoas'      => (int) $r->pessoas,
                'share'        => round($r->notas / $total * 100, 1),
                'per_day'      => $perDay !== null ? round($perDay, 1) : null,
                'team_per_day' => $teamDay !== null ? round($teamDay, 1) : null,
                'delta'        => $perDay !== null && $teamDay ? round(($perDay - $teamDay) / $teamDay * 100, 1) : null,
                'avg_total'    => $r->avg_total !== null ? (float) $r->avg_total : null,
                'avg_queue'    => $r->avg_queue !== null ? (float) $r->avg_queue : null,
                'avg_exec'     => $r->avg_exec !== null ? (float) $r->avg_exec : null,
                'avg_stopped'  => $r->avg_stopped !== null ? (float) $r->avg_stopped : null,
            ];
        })->values()->all();
    }

    /**
     * Notas concluídas por pessoa e atividade (matriz do Excel).
     *
     * @return array{services: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    private function userActivityMatrix(array $f): array
    {
        $cells = $this->completedQuery($f, $f['from'], $f['to'])
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->selectRaw('COALESCE(u.name, ?) as person, s.service, COUNT(*) as n', [self::NO_OWNER])
            ->groupBy('person', 's.service')
            ->get();

        $services = $cells->groupBy('service')->map->sum('n')->sortDesc()->keys()->all();

        $rows = $cells->groupBy('person')->map(fn (Collection $items, string $person) => [
            'person' => $person,
            'total'  => (int) $items->sum('n'),
            'cells'  => $items->pluck('n', 'service')->map(fn ($n) => (int) $n)->all(),
        ])->sortByDesc('total')->values()->all();

        return ['services' => $services, 'rows' => $rows];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function completedRows(array $f): array
    {
        $informedSql = Panel::informedSql();
        $since       = Panel::sinceSql();

        return $this->completedQuery($f, $f['from'], $f['to'])
            ->join('notes as n', 'n.id', '=', 'p.note_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('companies as c', 'c.id', '=', 'p.company_id')
            ->leftJoin('analises as a', 'a.production_id', '=', 'p.id')
            ->selectRaw("p.id, n.note, s.service, s.folder, s.project, s.construction, s.post_construction, u.name as user_name, c.name as company_name,
                p.d5, p.postes_u, p.status, p.dispatch_at, p.att_at, p.completed_at, p.stopped, a.conclusion, {$informedSql} as informed_at,
                CASE WHEN {$since} IS NOT NULL THEN GREATEST(TIMESTAMPDIFF(SECOND, {$since}, p.completed_at), 0) END as total_seconds")
            ->orderBy('p.completed_at')
            ->orderBy('p.id')
            ->limit(self::COMPLETED_LIMIT)
            ->get()
            ->map(fn ($r) => [
                'production_id' => (int) $r->id,
                'note'          => (string) $r->note,
                'service'       => $r->service,
                'group'         => Panel::group($r),
                'user'          => $r->user_name ?: self::NO_OWNER,
                'company'       => $r->company_name ?: '—',
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

    private function delta(int|float $current, int|float $previous): ?float
    {
        return $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null;
    }

    // ───────────────────────────── Séries ─────────────────────────────

    /**
     * Recebidas (ATT), concluídas, postes e pessoas que produziram, por dia.
     *
     * @return array<string, mixed>
     */
    private function dailySeries(array $f): array
    {
        $received = $this->scope(DB::table('productions as p'), $f)
            ->whereBetween('p.att_at', [$f['from'], $f['to']])
            ->selectRaw('DATE(p.att_at) as d, COUNT(*) as n')
            ->groupBy('d')
            ->pluck('n', 'd');

        $done = $this->completedQuery($f, $f['from'], $f['to'])
            ->selectRaw('DATE(p.completed_at) as d, COUNT(*) as n, COALESCE(SUM(p.postes_u), 0) as pst, COUNT(DISTINCT p.user_id) as pessoas')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $labels = $dates = $in = $out = $postes = $people = [];

        for ($day = $f['from']->copy()->startOfDay(); $day->lte($f['to']); $day->addDay()) {
            $key      = $day->toDateString();
            $dates[]  = $key;
            $labels[] = $day->format('d/m');
            $in[]     = (int) $received->get($key, 0);
            $out[]    = (int) ($done->get($key)->n ?? 0);
            $postes[] = (int) ($done->get($key)->pst ?? 0);
            $people[] = (int) ($done->get($key)->pessoas ?? 0);
        }

        return ['labels' => $labels, 'dates' => $dates, 'received' => $in, 'completed' => $out, 'postes' => $postes, 'people' => $people];
    }

    /**
     * Notas concluídas por mês e grupo (Projeto/Construção/Pós-obra), nos 12 meses até o fim do período.
     *
     * @return array<string, mixed>
     */
    private function monthlySeries(array $f): array
    {
        $end   = $f['to']->copy()->endOfMonth();
        $start = $f['to']->copy()->subMonths(11)->startOfMonth();

        // Agrupa por mês e atividade e só então aplica os grupos (sem juntar `services` linha a linha: 12 meses são muitas linhas).
        $flags = DB::table('services')->get(['uuid', 'project', 'construction', 'post_construction'])->keyBy('uuid');
        $rows  = $this->scope(DB::table('productions as p'), $f)
            ->where('p.completed', true)
            ->whereBetween('p.completed_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(p.completed_at, '%Y-%m') as ym, p.service_id, COUNT(*) as n,
                COALESCE(SUM(p.postes_u), 0) as postes")
            ->groupBy('ym', 'p.service_id')
            ->get();

        $people = DB::table('productions as p')
            ->where('p.rejected', false)->where('p.completed', true)
            ->when(!$f['include_ri'], fn ($q) => $q->where('p.d5', false))
            ->when($f['service'], fn ($q, $v) => $q->where('p.service_id', $v))
            ->when($f['company'], fn ($q, $v) => $q->where('p.company_id', $v))
            ->when($f['user'], fn ($q, $v) => $q->where('p.user_id', $v))
            ->whereBetween('p.completed_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(p.completed_at, '%Y-%m') as ym, COUNT(DISTINCT p.user_id) as pessoas")
            ->groupBy('ym')
            ->pluck('pessoas', 'ym');

        $byMonth = [];

        foreach ($rows as $r) {
            $svc = $flags->get($r->service_id);
            $m   = &$byMonth[$r->ym];
            $m ??= ['project' => 0, 'construction' => 0, 'post_construction' => 0, 'other' => 0, 'postes' => 0];

            $m['postes'] += (int) $r->postes;
            $m['project'] += $svc && $svc->project ? (int) $r->n : 0;
            $m['construction'] += $svc && $svc->construction ? (int) $r->n : 0;
            $m['post_construction'] += $svc && $svc->post_construction ? (int) $r->n : 0;
            $m['other'] += !$svc || (!$svc->project && !$svc->construction && !$svc->post_construction) ? (int) $r->n : 0;
            unset($m);
        }

        $rows = collect($byMonth)->map(fn (array $v) => (object) $v);

        $labels = $months = $postes = $peopleSeries = [];
        $series = array_fill_keys(array_keys(Panel::GROUP_LABELS), []);

        for ($month = $start->copy(); $month->lte($end); $month->addMonth()) {
            $key            = $month->format('Y-m');
            $months[]       = $key;
            $labels[]       = $month->translatedFormat('M/y');
            $postes[]       = (int) ($rows->get($key)->postes ?? 0);
            $peopleSeries[] = (int) ($people->get($key) ?? 0);

            foreach (array_keys(Panel::GROUP_LABELS) as $group) {
                $series[$group][] = (int) ($rows->get($key)->{$group} ?? 0);
            }
        }

        return ['labels' => $labels, 'months' => $months, 'series' => $series, 'postes' => $postes, 'people' => $peopleSeries];
    }
}
