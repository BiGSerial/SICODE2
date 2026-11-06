<?php

namespace App\Services\Reports\Production;

use App\Models\{Company, Production, Service, User};
use App\Support\SicodeRules;
use Carbon\{Carbon, CarbonInterface};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\{Arr, Collection};
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Dados do "Relatório de Produção" (Serviços → Relatórios). A tela, a lista paginada e o Excel leem daqui, com o mesmo filtro,
 * para os números nunca divergirem. Regras de cálculo em docs/reports/RELATORIO_PRODUCAO.md.
 *
 * Filtros (`normalize`): from/to (Y-m-d), services[], company, user, conclusions[], doe/ma/confirmed ('' | 'sim' | 'nao'),
 * include_open, include_ri, search, multisearch[] e scope (ids de empresa visíveis para usuário de contrato; null = todas).
 */
class ProductionReportAnalytics
{
    /** Partes entregues em sequência pela tela (a lista abre na hora; indicadores e gráficos chegam em seguida). Cada parte é uma varredura. */
    public const PARTS = ['summary', 'breakdown', 'conclusions'];

    /** Indicadores e gráficos da tela só para períodos de até 93 dias (a lista e o Excel cobrem qualquer período): cada parte varre o período. */
    public const SCREEN_MAX_DAYS = 93;

    public const TRI = ['sim', 'nao'];

    /** Postes/ativos de uma linha: postes × (EO + projeto inicial), ou só os postes quando não há EO/projeto. */
    private const POSTES = 'COALESCE(productions.postes_u, 0) * CASE WHEN COALESCE(productions.eo, 0) + COALESCE(productions.iproject, 0) <> 0 THEN COALESCE(productions.eo, 0) + COALESCE(productions.iproject, 0) ELSE 1 END';

    public const NO_CONCLUSION = 'Sem conclusão';

    /**
     * @param array<string, mixed> $f
     *
     * @return array<string, mixed>
     */
    public function normalize(array $f, ?CarbonInterface $now = null): array
    {
        $now  = $now ?? now();
        $from = !empty($f['from']) ? Carbon::parse($f['from'])->startOfDay() : null;
        $to   = !empty($f['to']) ? Carbon::parse($f['to'])->endOfDay() : null;

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $tri = fn (mixed $v) => in_array($v, self::TRI, true) ? $v : '';

        return [
            'from'         => $from,
            'to'           => $to,
            'services'     => array_values(array_filter((array) ($f['services'] ?? []))),
            'company'      => ($f['company'] ?? null) ?: null,
            'user'         => ($f['user'] ?? null) ?: null,
            'conclusions'  => array_values(array_filter((array) ($f['conclusions'] ?? []), 'is_string')),
            'doe'          => $tri($f['doe'] ?? ''),
            'ma'           => $tri($f['ma'] ?? ''),
            'confirmed'    => $tri($f['confirmed'] ?? ''),
            'include_open' => (bool) ($f['include_open'] ?? false),
            'include_ri'   => (bool) ($f['include_ri'] ?? false),
            'search'       => trim((string) ($f['search'] ?? '')),
            'multisearch'  => array_values(array_filter((array) ($f['multisearch'] ?? []))),
            'scope'        => array_key_exists('scope', $f) ? $f['scope'] : null,
            'days'         => $from && $to ? (int) $from->diffInDays($to) + 1 : null,
            'now'          => $now,
        ];
    }

    /** Empresas que o usuário enxerga (`null` = todas). */
    public function scopeFor(User $user): ?array
    {
        return $user->contract ? SicodeRules::visibleCompanyIdsFor($user) : null;
    }

    /**
     * Base de toda leitura: produções do filtro, com a Nota (`n`) e a Análise (`a`) ligadas para filtrar e agrupar no banco.
     *
     * @param array<string, mixed> $f resultado de normalize()
     */
    public function query(array $f): Builder
    {
        $q = Production::query()->where('productions.rejected', false);

        if ($f['scope'] !== null) {
            $q->whereIn('productions.company_id', $f['scope'] ?: ['0']);
        }

        if (!$f['include_ri']) {
            $q->where('productions.d5', false);
        }

        // Concluídas dentro do período; com "incluir em aberto" entram também todas as ainda abertas.
        $q->where(function ($w) use ($f) {
            $w->where(function ($done) use ($f) {
                $done->where('productions.completed', true);

                if ($f['from']) {
                    $done->where('productions.completed_at', '>=', $f['from']);
                }

                if ($f['to']) {
                    $done->where('productions.completed_at', '<=', $f['to']);
                }
            });

            if ($f['include_open']) {
                $w->orWhere('productions.completed', false);
            }
        });

        $f['services'] && $q->whereIn('productions.service_id', $f['services']);
        $f['company'] && $q->where('productions.company_id', $f['company']);
        $f['user'] && $q->where('productions.user_id', $f['user']);
        $f['conclusions'] && $this->whereConclusion($this->joinAnalise($q), $f['conclusions']);
        $f['doe'] !== '' && $this->joinNote($q);

        foreach (['doe' => 'n.doe', 'ma' => 'productions.ma', 'confirmed' => 'productions.confirmed'] as $key => $column) {
            if ($f[$key] === 'sim') {
                $q->where($column, true);
            } elseif ($f[$key] === 'nao') {
                $q->where(fn ($w) => $w->where($column, false)->orWhereNull($column));
            }
        }

        $this->search($q, $f);

        return $q;
    }

    /**
     * Lista paginada da tela: do mais novo para o mais antigo. A ordem (conclusão, depois id) é a do índice (completed, completed_at):
     * as abertas, sem data de fim, vêm por último (NULL é o menor valor), das mais recentes para as mais antigas. Nota e Análise vêm por
     * carregamento em lote, não por JOIN.
     */
    public function listQuery(array $f): Builder
    {
        return $this->query($f)
            ->select('productions.*')
            ->with(['User:id,name', 'Company:id,name', 'Service:uuid,service', 'Note:id,note,doe,ma,group2,group5,material', 'Analise:id,production_id,conclusion'])
            ->orderByDesc('productions.completed_at')
            ->orderByDesc('productions.id');
    }

    /** Lista no layout anterior (59 colunas): mesmo filtro, eager loads do mapeamento e ordem da conclusão mais antiga para a mais nova. */
    public function legacyLayoutQuery(array $f): Builder
    {
        return $this->query($f)
            ->select('productions.*')
            ->with([
                'Dispatcher:id,name,company_id',
                'Dispatcher.Company:id,name',
                'Att:id,name',
                'Att.Company:id,name',
                'User:id,name',
                'Company:id,name',
                'Service:uuid,service',
                'Note:id,note,material,group2,group5,lexp,postes,nexp,doe,rubrica,type_note',
                'Note.RamalForm:id,note_id,created_at',
                'Note.WorkForm' => fn ($q) => $q->with(['Orders:id', 'Adsform:id,created_at']),
                'Analise',
                'Reclaim:id,category',
            ])
            ->orderBy('productions.completed_at');
    }

    /** Os indicadores e gráficos cabem na tela só em períodos com início, fim e até SCREEN_MAX_DAYS dias. */
    public function screenAllowed(array $f): bool
    {
        return $f['days'] !== null && $f['days'] <= self::SCREEN_MAX_DAYS;
    }

    /**
     * @param array<string, mixed> $f
     * @param array<int, string>   $parts
     *
     * @return array<string, mixed>
     */
    public function assemble(array $f, array $parts = self::PARTS, bool $cache = false): array
    {
        $key = 'prod-report:' . md5(json_encode([$this->cacheKey($f), $parts]));
        $run = fn () => $this->compute($f, $parts);

        return $cache ? Cache::remember($key, now()->addMinutes(2), $run) : $run();
    }

    /** Conjunto completo (todas as partes, listas sem corte), usado pelo Excel. */
    public function report(array $f): array
    {
        $f = $this->normalize($f);

        return $this->compute($f, self::PARTS) + ['filters' => $f, 'generated_at' => now()];
    }

    /** @return array<int, array{value: string, label: string}> conclusões que existem na base (para o filtro). */
    public function conclusions(): array
    {
        return Cache::remember('prod-report:conclusions', now()->addMinutes(10), fn () => DB::table('analises')
            ->whereNotNull('conclusion')->whereNotIn('conclusion', ['', '0'])
            ->distinct()->orderBy('conclusion')->pluck('conclusion')
            ->map(fn (string $c) => ['value' => $c, 'label' => $c])->all());
    }

    public function services(): Collection
    {
        return Cache::remember('prod-report:services', now()->addMinutes(10), fn () => Service::query()
            ->whereIn('uuid', Production::query()->select('service_id')->distinct())
            ->orderBy('service')->get(['uuid', 'service']));
    }

    public function companies(?array $scope = null): Collection
    {
        return Company::query()
            ->when($scope !== null, fn ($q) => $q->whereIn('id', $scope ?: ['0']))
            ->whereIn('id', Cache::remember('prod-report:company-ids', now()->addMinutes(10), fn () => Production::query()->select('company_id')->distinct()->pluck('company_id')->all()))
            ->orderBy('name')->get(['id', 'name']);
    }

    /** Pessoas que já produziram, no recorte de empresa. */
    public function people(?string $company, ?array $scope = null): Collection
    {
        return Cache::remember('prod-report:people:' . md5(json_encode([$company, $scope])), now()->addMinutes(10), fn () => User::query()
            ->whereIn('id', Production::query()->select('user_id')->distinct()
                ->when($company, fn ($q) => $q->where('company_id', $company))
                ->when($scope !== null, fn ($q) => $q->whereIn('company_id', $scope ?: ['0'])))
            ->orderBy('name')->get(['id', 'name']));
    }

    public static function duration(?float $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        $seconds = (int) round($seconds);
        $days    = intdiv($seconds, 86400);
        $hours   = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $days > 0 ? "{$days}d {$hours}h" : ($hours > 0 ? "{$hours}h {$minutes}min" : "{$minutes}min");
    }

    /** Postes/ativos de uma linha já carregada (mesma regra do SQL de POSTES). */
    public static function postes(Production $p): int
    {
        $extra = (int) $p->eo + (int) $p->iproject;

        return (int) $p->postes_u * ($extra !== 0 ? $extra : 1);
    }

    /**
     * @param array<string, mixed> $f
     * @param array<int, string>   $parts
     *
     * @return array<string, mixed>
     */
    private function compute(array $f, array $parts): array
    {
        $out = ['summary' => [], 'flags' => [], 'series' => ['labels' => [], 'done' => [], 'doe' => [], 'ma' => [], 'unit' => 'day'], 'conclusions' => [], 'services' => [], 'companies' => [], 'users' => []];

        if (in_array('summary', $parts, true)) {
            [$out['summary'], $out['flags'], $out['series']] = $this->overview($f);
        }

        if (in_array('breakdown', $parts, true)) {
            [$out['services'], $out['companies'], $out['users'], $people] = $this->breakdown($f);
            $out['people']                                                = $people;
        }

        if (in_array('conclusions', $parts, true)) {
            $out['conclusions'] = $this->conclusionRows($f);
        }

        if (isset($out['people']) && $out['summary']) {
            $out['summary']['people'] = $out['people'];
        }

        return $out;
    }

    /**
     * Uma única varredura (com a Nota ligada) agrupada por dia/mês de conclusão: de lá saem os indicadores, o cruzamento exclusivo
     * órgão externo × meio ambiente e a série do gráfico. As linhas em aberto (sem data de conclusão) caem no grupo sem data.
     *
     * @return array{0: array<string, int|float|null>, 1: array<string, int>, 2: array<string, mixed>}
     */
    private function overview(array $f): array
    {
        $daily = $f['from'] && $f['to'] && $f['days'] <= self::SCREEN_MAX_DAYS;
        $fmt   = $daily ? '%Y-%m-%d' : '%Y-%m';
        $done  = 'productions.completed = 1 AND productions.att_at IS NOT NULL AND productions.completed_at >= productions.att_at';

        $rows = $this->joinNote($this->query($f))->toBase()->selectRaw(
            "DATE_FORMAT(productions.completed_at, '{$fmt}') AS b,
             COUNT(*) AS total,
             SUM(productions.completed = 1) AS done,
             SUM(productions.completed = 0) AS open,
             SUM(productions.confirmed = 1) AS counted,
             SUM(productions.d5 = 1) AS ri,
             SUM(n.doe = 1) AS doe,
             SUM(productions.ma = 1) AS ma,
             SUM(" . self::POSTES . ") AS postes,
             SUM(n.doe = 1 AND COALESCE(productions.ma, 0) = 0) AS doe_only,
             SUM(COALESCE(n.doe, 0) = 0 AND productions.ma = 1) AS ma_only,
             SUM(n.doe = 1 AND productions.ma = 1) AS both_flags,
             SUM(COALESCE(n.doe, 0) = 0 AND COALESCE(productions.ma, 0) = 0) AS none_flags,
             SUM(CASE WHEN {$done} THEN TIMESTAMPDIFF(SECOND, productions.att_at, productions.completed_at) END) AS timed_sum,
             SUM(CASE WHEN {$done} THEN 1 END) AS timed_n,
             SUM(CASE WHEN productions.stopped > 0 THEN productions.stopped END) AS stopped_sum,
             SUM(productions.stopped > 0) AS stopped_n"
        )->groupBy('b')->get();

        $sum = fn (string $col) => (int) $rows->sum(fn ($r) => (int) $r->{$col});
        $out = [
            'total'       => $sum('total'), 'done' => $sum('done'), 'open' => $sum('open'), 'counted' => $sum('counted'), 'ri' => $sum('ri'),
            'doe'         => $sum('doe'), 'ma' => $sum('ma'), 'postes' => $sum('postes'),
            'people'      => 0,
            'avg_total'   => $sum('timed_n') ? $sum('timed_sum') / $sum('timed_n') : null,
            'avg_stopped' => $sum('stopped_n') ? $sum('stopped_sum') / $sum('stopped_n') : null,
        ];
        $out['pct_doe']     = $out['total'] ? round($out['doe'] / $out['total'] * 100, 1) : 0.0;
        $out['pct_ma']      = $out['total'] ? round($out['ma'] / $out['total'] * 100, 1) : 0.0;
        $out['pct_counted'] = $out['done'] ? round($out['counted'] / $out['done'] * 100, 1) : 0.0;

        $flags = ['doe_only' => $sum('doe_only'), 'ma_only' => $sum('ma_only'), 'both' => $sum('both_flags'), 'none' => $sum('none_flags')];

        return [$out, $flags, $this->series($f, $rows->whereNotNull('b')->keyBy('b'), $daily)];
    }

    /**
     * @param Collection<string, object> $byBucket grupos por dia/mês de conclusão
     *
     * @return array{labels: array<int, string>, done: array<int, int>, doe: array<int, int>, ma: array<int, int>, unit: string}
     */
    private function series(array $f, Collection $byBucket, bool $daily): array
    {
        $keys = $byBucket->keys()->sort()->values();
        $from = $f['from'] ?: ($keys->isNotEmpty() ? Carbon::parse($keys->first() . ($daily ? '' : '-01')) : null);
        $to   = $f['to'] ?: ($keys->isNotEmpty() ? Carbon::parse($keys->last() . ($daily ? '' : '-01')) : null);

        $series = ['labels' => [], 'done' => [], 'doe' => [], 'ma' => [], 'unit' => $daily ? 'day' : 'month'];

        if (!$from || !$to) {
            return $series;
        }

        $cursor = $daily ? $from->copy()->startOfDay() : $from->copy()->startOfMonth();
        $last   = $daily ? $to->copy()->startOfDay() : $to->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $row                = $byBucket->get($cursor->format($daily ? 'Y-m-d' : 'Y-m'));
            $series['labels'][] = $cursor->format($daily ? 'd/m' : 'm/Y');
            $series['done'][]   = (int) ($row->done ?? 0);
            $series['doe'][]    = (int) ($row->doe ?? 0);
            $series['ma'][]     = (int) ($row->ma ?? 0);
            $daily ? $cursor->addDay() : $cursor->addMonthNoOverflow();
        }

        return $series;
    }

    /**
     * Uma varredura (sem JOIN) agrupada por atividade × empresa × pessoa; os três rankings e a contagem de pessoas saem desses grupos em PHP.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>, 2: array<int, array<string, mixed>>, 3: int}
     */
    private function breakdown(array $f): array
    {
        $rows = $this->query($f)->toBase()
            ->selectRaw('productions.service_id AS s, productions.company_id AS c, productions.user_id AS u, COUNT(*) AS total, SUM(productions.completed = 1) AS done,
                SUM(productions.completed = 0) AS open, SUM(productions.ma = 1) AS ma, SUM(' . self::POSTES . ') AS postes')
            ->groupBy('productions.service_id', 'productions.company_id', 'productions.user_id')->get();

        $rollup = function (string $key) use ($rows): Collection {
            return $rows->groupBy($key)->map(fn (Collection $g, $id) => [
                'id' => $id, 'total' => (int) $g->sum('total'), 'done' => (int) $g->sum('done'), 'open' => (int) $g->sum('open'), 'ma' => (int) $g->sum('ma'), 'postes' => (int) $g->sum('postes'),
            ])->sortByDesc('total')->values();
        };

        $services  = $rollup('s');
        $companies = $rollup('c');
        $users     = $rollup('u');

        $serviceNames = Service::query()->whereIn('uuid', $services->pluck('id'))->pluck('service', 'uuid');
        $companyNames = Company::withTrashed()->whereIn('id', $companies->pluck('id'))->pluck('name', 'id');
        $userNames    = User::withTrashed()->whereIn('id', $users->pluck('id'))->pluck('name', 'id');

        return [
            $services->map(fn (array $r) => $r + ['service' => $serviceNames[$r['id']] ?? 'Atividade removida'])->all(),
            $companies->map(fn (array $r) => $r + ['name' => $companyNames[$r['id']] ?? 'Desconhecida'])->all(),
            $users->map(fn (array $r) => $r + ['name' => $userNames[$r['id']] ?? 'Desconhecido'])->all(),
            $users->whereNotNull('id')->where('id', '!=', '')->count(),
        ];
    }

    /** @return array<int, array{conclusion: string, raw: ?string, total: int, ma: int, pct: float}> */
    private function conclusionRows(array $f): array
    {
        $rows = $this->joinAnalise($this->query($f))->toBase()
            ->selectRaw('a.conclusion AS conclusion, COUNT(*) AS total, SUM(productions.ma = 1) AS ma')
            ->groupBy('a.conclusion')->orderByDesc('total')->get();
        $sum = max(1, (int) $rows->sum('total'));

        return $rows->map(fn ($r) => [
            'conclusion' => $r->conclusion !== null && $r->conclusion !== '' ? $r->conclusion : self::NO_CONCLUSION,
            'raw'        => $r->conclusion,
            'total'      => (int) $r->total,
            'ma'         => (int) $r->ma,
            'pct'        => round($r->total / $sum * 100, 1),
        ])->all();
    }

    private function joinNote(Builder $q): Builder
    {
        return $this->joined($q, 'notes as n') ? $q : $q->leftJoin('notes as n', 'n.id', '=', 'productions.note_id');
    }

    private function joinAnalise(Builder $q): Builder
    {
        return $this->joined($q, 'analises as a') ? $q : $q->leftJoin('analises as a', 'a.production_id', '=', 'productions.id');
    }

    private function joined(Builder $q, string $table): bool
    {
        return collect($q->getQuery()->joins ?? [])->contains(fn ($join) => $join->table === $table);
    }

    /** Conclusão "Sem conclusão" casa com a ausência de análise ou de texto. */
    private function whereConclusion(Builder $q, array $conclusions): void
    {
        $none = in_array(self::NO_CONCLUSION, $conclusions, true);
        $real = array_values(array_diff($conclusions, [self::NO_CONCLUSION]));

        $q->where(function ($w) use ($none, $real) {
            $real && $w->whereIn('a.conclusion', $real);
            $none && $w->orWhereNull('a.conclusion')->orWhere('a.conclusion', '');
        });
    }

    /** Busca por Nota, Ordem, Material, Grupo 2 e Grupo 5; `*`/`%` viram curinga; a busca múltipla é por lista exata de Notas, Materiais ou Ordens. */
    private function search(Builder $q, array $f): void
    {
        if ($f['multisearch']) {
            $terms = $f['multisearch'];

            $q->where(fn ($w) => $w->whereIn('productions.note_id', fn ($s) => $s->select('id')->from('notes')->whereIn('note', $terms)->orWhereIn('material', $terms))
                ->orWhereIn('productions.note_id', fn ($s) => $s->select('note_id')->from('orders')->whereIn('ordem', $terms)));

            return;
        }

        if ($f['search'] === '') {
            return;
        }

        $term = str_replace('*', '%', $f['search']);
        $op   = str_contains($term, '%') ? 'like' : '=';

        $q->where(fn ($w) => $w->whereIn('productions.note_id', fn ($s) => $s->select('id')->from('notes')
            ->where(fn ($n) => $n->where('note', $op, $term)->orWhere('material', $op, $term)->orWhere('group2', $op, $term)->orWhere('group5', $op, $term)))
            ->orWhereIn('productions.note_id', fn ($s) => $s->select('note_id')->from('orders')->where('ordem', $op, $term)));
    }

    private function cacheKey(array $f): array
    {
        return Arr::except(array_map(fn ($v) => $v instanceof CarbonInterface ? $v->toDateString() : $v, $f), ['now']) + ['day' => $f['now']->toDateString()];
    }
}
