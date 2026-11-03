<?php

namespace App\Services\Dispatch\Dashboard;

use App\Custom\RuleBuilder;
use App\Models\{Note, Service};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Dados do Dashboard de atividade (docs/dashboards/DASHBOARD_ATIVIDADE.md). A tela, os gráficos e o Excel leem SÓ daqui,
 * então nunca divergem. A "pilha" é a mesma da lista de despacho da atividade (`RuleBuilder` + `excludeCanceledFullDone`).
 *
 * Regras de cálculo:
 *  - Pilha: notas que a atividade enxerga para despacho; "em atribuição" = tem Production aberta (`confirmed = 0`) na atividade.
 *  - Tempo no status: dias desde `notes.dt_status` (30+ agrupa o resto). Prazo real: `30 - notes.days_left` (dias consumidos).
 *  - Prazo vencido: `notes.days_left < 0`. Cor do prazo: vermelho com menos de 6 dias restantes, âmbar até 9, verde com 10 ou mais.
 *  - Por colaborador: Productions abertas com usuário, idade desde ATT (ou despacho/criação): até 4, 5 a 8, mais de 8 dias.
 *  - Aderência: Productions concluídas no período (sem RI/D5), dias entre `dt_note` e a conclusão, por mês de conclusão e
 *    empresa (SATEL x própria/EDP), faixas 0–limite, limite+1–15, 16–30, 30+. Só OV.
 */
class ActivityDashboardAnalytics
{
    public const CACHE_TTL = 60;

    /** Acima disto a atividade não tem pilha própria (as regras de status deixam passar quase todas as notas): os blocos da pilha não se aplicam. */
    public const PILE_MAX = 50000;

    public const USER_LIMIT = 12;

    public const CRITICAL_LIMIT = 15;

    public const AGE_BUCKETS = ['Até 4 dias', '5 a 8 dias', 'Mais de 8 dias'];

    public const DEADLINE_SERIES = ['Na pilha', 'SATEL', 'Própria (EDP)', 'Outras empresas'];

    /** Filtros com valor padrão e validação (tela, URL e Excel passam por aqui). @return array{type: string, tracking: bool, from: Carbon, to: Carbon, limit: int} */
    public function normalize(array $filters): array
    {
        $type = (string) ($filters['type'] ?? '2');

        try {
            $from = Carbon::parse($filters['from'] ?? now()->startOfMonth())->startOfDay();
            $to   = Carbon::parse($filters['to'] ?? now()->endOfMonth())->startOfDay();
        } catch (\Throwable) {
            $from = now()->startOfMonth()->startOfDay();
            $to   = now()->endOfMonth()->startOfDay();
        }

        if ($to->lt($from)) {
            $to = $from->copy();
        }

        return [
            'type'     => in_array($type, ['1', '2', ''], true) ? $type : '2',
            'tracking' => (bool) ($filters['tracking'] ?? false),
            'from'     => $from,
            'to'       => $to,
            'limit'    => (string) ($filters['limit'] ?? '8') === '6' ? 6 : 8,
        ];
    }

    /** Quem calcula o dashboard da atividade: Pagamento, Publicação e Fiscalização têm pilha própria (por informe), as demais usam as regras de status. */
    public static function for(Service $service): self
    {
        return ReportPileDashboardAnalytics::supports($service) ? app(ReportPileDashboardAnalytics::class) : app(self::class);
    }

    /** Textos e regras que a tela e os gráficos mostram (a pilha por regras de status é o padrão). @return array<string, mixed> */
    public function profile(?Service $service = null): array
    {
        return [
            'key'      => 'status',
            'unit'     => 'notas',
            'time'     => ['avg' => 'Média no status', 'title' => 'Tempo no status', 'sub' => 'Quantas notas há por dias de permanência, em atribuição e na pilha', 'axis' => 'Dias no status', 'column' => 'No status', 'basis' => 'Dias desde a data do status'],
            'deadline' => ['title' => 'Prazo real', 'sub' => 'Dias consumidos do prazo; quanto maior, mais crítico. Por quem está com a nota', 'axis' => 'Dias consumidos do prazo', 'rule' => 'Dias consumidos do prazo de 30. Vermelho: menos de 6 dias restantes; âmbar: até 9; verde: 10 ou mais.'],
            'pile'     => 'as notas que a lista de despacho da atividade enxerga (regras de status).',
        ];
    }

    /** @return array<string, mixed> */
    public function snapshot(Service $service, array $filters): array
    {
        $f   = $this->normalize($filters);
        $key = 'dispatch-dashboard:' . md5($service->uuid . '|' . $f['type'] . '|' . (int) $f['tracking'] . '|' . $f['from']->toDateString() . '|' . $f['to']->toDateString() . '|' . $f['limit']);

        return Cache::remember($key, self::CACHE_TTL, fn () => $this->build($service, $f));
    }

    /** Snapshot sem cache + a pilha crítica inteira (Excel). @return array<string, mixed> */
    public function report(Service $service, array $filters): array
    {
        $f = $this->normalize($filters);

        $built = $this->build($service, $f, null);

        return $built + ['critical_all' => $built['critical'], 'generated_at' => now(), 'service' => $service];
    }

    /** @return array<string, mixed> */
    protected function build(Service $service, array $f, ?int $criticalLimit = self::CRITICAL_LIMIT): array
    {
        // O teste de tamanho varre a pilha: guardado por mais tempo que o resto (o tamanho de uma pilha não muda em minutos).
        $tooLarge = Cache::remember('dispatch-dashboard:large:' . md5($service->uuid . '|' . $f['type'] . '|' . (int) $f['tracking']), 600, fn () => $this->isTooLarge($service, $f));
        $agg      = $tooLarge ? $this->emptyPile() : $this->aggregatePile($service, $f, $criticalLimit);

        return [
            'summary'    => $agg['summary'],
            'status_age' => $agg['status_age'],
            'deadline'   => $agg['deadline'],
            'users'      => $agg['users'],
            'critical'   => $agg['critical'],
            'bands'      => $this->bands($service, $f),
            'filters'    => $f,
            'profile'    => $this->profile($service),
            'too_large'  => $tooLarge,
            'updated_at' => now(),
        ];
    }

    /** Pilhas por regra de status podem passar de centenas de milhares; as que têm repositório próprio nunca chegam perto (sobrescrevem). */
    protected function isTooLarge(Service $service, array $f): bool
    {
        return $this->stack($service, $f)->select('notes.id')->offset(self::PILE_MAX)->limit(1)->exists();
    }

    /** @return array<string, mixed> */
    protected function emptyPile(): array
    {
        return [
            'summary'    => ['total' => 0, 'assigned' => 0, 'unassigned' => 0, 'overdue' => 0, 'avg_status_days' => 0.0, 'oldest_status_days' => 0],
            'status_age' => $this->statusAgeSeries([]),
            'deadline'   => $this->deadlineSeries([]),
            'users'      => $this->userSeries([]),
            'critical'   => [],
        ];
    }

    protected function stack(Service $service, array $f): Builder
    {
        $query = Note::query()->excludeCanceledFullDone();

        RuleBuilder::applyRules($query, $service->Status);

        if ($f['type'] !== '') {
            $query->where('type_note', (int) $f['type']);
        }

        if (!$f['tracking']) {
            $query->whereRaw("LOWER(TRIM(COALESCE(notes.rubrica, ''))) <> ?", ['acompanhamento']);
        }

        return $query;
    }

    /**
     * Resumo, tempo no status e prazo real saem de UMA consulta agrupada sobre a pilha (a consulta de regras em `notes` é a parte
     * cara, e a pilha de algumas atividades passa de 600 mil notas). Colaboradores e pilha crítica leem só o que precisam.
     *
     * @return array{summary: array<string, mixed>, status_age: array<string, mixed>, deadline: array<string, mixed>, users: array<string, mixed>, critical: array<int, array<string, mixed>>}
     */
    protected function aggregatePile(Service $service, array $f, ?int $criticalLimit): array
    {
        $statusDays = 'CASE WHEN notes.dt_status IS NULL THEN NULL WHEN DATEDIFF(CURDATE(), notes.dt_status) < 0 THEN 0 ELSE DATEDIFF(CURDATE(), notes.dt_status) END';
        $assigned   = 'EXISTS (SELECT 1 FROM productions p WHERE p.note_id = notes.id AND p.service_id = ? AND p.confirmed = 0)';
        $company    = "CASE WHEN {$assigned} THEN COALESCE((SELECT LOWER(companies.name) FROM productions p LEFT JOIN companies ON companies.id = p.company_id WHERE p.note_id = notes.id AND p.service_id = ? AND p.confirmed = 0 ORDER BY p.created_at DESC LIMIT 1), '') ELSE NULL END";

        $rows = $this->stack($service, $f)
            ->selectRaw(
                "CASE WHEN notes.dt_status IS NULL THEN 'Sem data' WHEN {$statusDays} >= 30 THEN '30+' ELSE CAST({$statusDays} AS CHAR) END as age_bucket,
                CASE WHEN notes.days_left IS NULL THEN 'Sem prazo' WHEN (30 - notes.days_left) < 0 THEN '0' ELSE CAST((30 - notes.days_left) AS CHAR) END as deadline_bucket,
                ({$assigned}) as is_assigned,
                {$company} as company,
                SUM(CASE WHEN notes.days_left < 0 THEN 1 ELSE 0 END) as overdue,
                SUM({$statusDays}) as days_sum,
                SUM(CASE WHEN notes.dt_status IS NULL THEN 0 ELSE 1 END) as days_n,
                MAX({$statusDays}) as days_max,
                COUNT(*) as total",
                [$service->uuid, $service->uuid, $service->uuid]
            )
            ->groupBy('age_bucket', 'deadline_bucket', 'is_assigned', 'company')
            ->get();

        $total = $assignedTotal = $overdue = $sum = $count = $oldest = 0;
        $age   = [];
        $dead  = [];

        foreach ($rows as $r) {
            $n          = (int) $r->total;
            $isAssigned = (int) $r->is_assigned === 1;

            $total += $n;
            $assignedTotal += $isAssigned ? $n : 0;
            $overdue += (int) $r->overdue;
            $sum += (int) $r->days_sum;
            $count += (int) $r->days_n;
            $oldest = max($oldest, (int) $r->days_max);

            $age[$r->age_bucket][$isAssigned ? 1 : 0] = ($age[$r->age_bucket][$isAssigned ? 1 : 0] ?? 0) + $n;

            $series = match (true) {
                !$isAssigned                                => 'Na pilha',
                str_contains((string) $r->company, 'satel') => 'SATEL',
                str_contains((string) $r->company, 'edp')   => 'Própria (EDP)',
                default                                     => 'Outras empresas',
            };

            $dead[$r->deadline_bucket][$series] = ($dead[$r->deadline_bucket][$series] ?? 0) + $n;
        }

        return [
            'summary' => [
                'total'              => $total,
                'assigned'           => $assignedTotal,
                'unassigned'         => max(0, $total - $assignedTotal),
                'overdue'            => $overdue,
                'avg_status_days'    => $count ? round($sum / $count, 1) : 0.0,
                'oldest_status_days' => $oldest,
            ],
            'status_age' => $this->statusAgeSeries($age),
            'deadline'   => $this->deadlineSeries($dead),
            'users'      => $this->userSeries($this->userCounts($service, $f)),
            'critical'   => $this->critical($service, $f, $criticalLimit),
        ];
    }

    /**
     * Quem está com a pilha e há quanto tempo. Parte das Productions abertas (poucas) e só confirma, por id, que a nota está na pilha.
     *
     * @return array<string, array<string, int>> usuário => faixa => total
     */
    protected function userCounts(Service $service, array $f): array
    {
        $open = DB::table('productions as p')
            ->join('users', 'users.id', '=', 'p.user_id')
            ->where('p.service_id', $service->uuid)
            ->where('p.confirmed', false)
            ->get(['p.note_id', 'p.user_id', 'users.name as user_name', 'p.att_at', 'p.dispatch_at', 'p.created_at']);

        $inPile = [];

        foreach ($open->pluck('note_id')->unique()->chunk(2000) as $ids) {
            foreach ($this->stack($service, $f)->whereIn('notes.id', $ids->all())->pluck('notes.id') as $id) {
                $inPile[(int) $id] = true;
            }
        }

        $today  = now()->startOfDay();
        $counts = [];

        foreach ($open->filter(fn ($p) => isset($inPile[(int) $p->note_id]))->unique(fn ($p) => $p->note_id . '|' . $p->user_id) as $p) {
            $since = $p->att_at ?: ($p->dispatch_at ?: $p->created_at);
            $days  = $since ? max(0, (int) Carbon::parse($since)->startOfDay()->diffInDays($today)) : 0;
            $band  = $days < 5 ? self::AGE_BUCKETS[0] : ($days <= 8 ? self::AGE_BUCKETS[1] : self::AGE_BUCKETS[2]);

            $counts[$p->user_name][$band] = ($counts[$p->user_name][$band] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Pilha crítica: prazo mais consumido primeiro, sem prazo por último, depois o status mais antigo. `$limit = null` traz a pilha inteira
     * (Excel, em fila). Quem está com cada nota vem de uma consulta às Productions abertas só dessas notas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function critical(Service $service, array $f, ?int $limit): array
    {
        $query = $this->stack($service, $f)
            ->select('notes.id', 'notes.note', 'notes.type_note', 'notes.rubrica', 'notes.lexp', 'notes.days_left', 'notes.dt_status')
            ->orderByRaw('CASE WHEN notes.days_left IS NULL THEN 1 ELSE 0 END ASC')
            ->orderByRaw('GREATEST(30 - notes.days_left, 0) DESC')
            ->orderBy('notes.dt_status');

        $notes = $limit ? $query->limit($limit)->get() : $query->get();
        $open  = DB::table('productions as p')
            ->leftJoin('users', 'users.id', '=', 'p.user_id')
            ->where('p.service_id', $service->uuid)
            ->where('p.confirmed', false)
            ->whereIn('p.note_id', $notes->pluck('id')->all())
            ->orderByDesc('p.created_at')
            ->get(['p.note_id', 'users.name as user_name'])
            ->groupBy('note_id');
        $today = now()->startOfDay();

        return $notes->map(function ($n) use ($open, $today) {
            $daysLeft = $n->days_left === null ? null : (int) $n->days_left;
            $since    = $n->dt_status ? Carbon::parse($n->dt_status) : null;

            return [
                'id'             => (int) $n->id,
                'note'           => (string) $n->note,
                'kind'           => (int) $n->type_note === 2 ? 'OV' : 'Nota',
                'rubrica'        => $n->rubrica,
                'city'           => $n->lexp,
                'days_left'      => $daysLeft,
                'deadline_used'  => $daysLeft === null ? null : max(0, 30 - $daysLeft),
                'deadline_label' => $daysLeft === null ? '—' : max(0, 30 - $daysLeft) . ' ' . (max(0, 30 - $daysLeft) === 1 ? 'dia' : 'dias'),
                'deadline_tone'  => $daysLeft === null ? 'muted' : ($daysLeft < 6 ? 'red' : ($daysLeft < 10 ? 'amber' : 'green')),
                'status_days'    => $since ? max(0, (int) $since->copy()->startOfDay()->diffInDays($today)) : null,
                'status_at'      => $since,
                'assigned'       => $open->has($n->id),
                'assigned_user'  => $open->get($n->id)?->first()->user_name,
            ];
        })->all();
    }

    /** @param array<string, array<int, int>> $counts rótulo => [0 => na pilha, 1 => em atribuição] */
    protected function statusAgeSeries(array $counts): array
    {
        $order  = array_merge(array_map('strval', range(0, 29)), ['30+', 'Sem data']);
        $labels = array_values(array_filter($order, fn ($l) => isset($counts[$l])));

        return [
            'labels'     => $labels,
            'assigned'   => array_map(fn ($l) => $counts[$l][1] ?? 0, $labels),
            'unassigned' => array_map(fn ($l) => $counts[$l][0] ?? 0, $labels),
        ];
    }

    /** @param array<string, array<string, int>> $counts */
    protected function deadlineSeries(array $counts): array
    {
        $numeric = array_map('intval', array_filter(array_keys($counts), 'is_numeric'));
        $max     = max(30, $numeric ? max($numeric) : 30);
        $order   = array_map('strval', range($max, 0));
        $order   = isset($counts['Sem prazo']) ? array_merge($order, ['Sem prazo']) : $order;
        $labels  = array_values(array_filter($order, fn ($l) => isset($counts[$l])));
        $series  = [];

        foreach (self::DEADLINE_SERIES as $name) {
            $series[$name] = array_map(fn ($l) => $counts[$l][$name] ?? 0, $labels);
        }

        return ['labels' => $labels, 'series' => $series];
    }

    /** @param array<string, array<string, int>> $counts usuário => faixa => total */
    protected function userSeries(array $counts): array
    {
        $users = collect($counts)->map(fn ($b) => ['total' => array_sum($b), 'buckets' => $b])->sortByDesc('total')->take(self::USER_LIMIT);

        return [
            'labels'  => $users->keys()->values()->all(),
            'buckets' => collect(self::AGE_BUCKETS)->mapWithKeys(fn ($b) => [$b => $users->map(fn ($u) => (int) ($u['buckets'][$b] ?? 0))->values()->all()])->all(),
        ];
    }

    /**
     * Aderência: conclusões por faixa de dias, por mês de conclusão, SATEL x própria. Só OV.
     *
     * @return array{limit: int, band_labels: array<int, string>, groups: array<string, array{months: array<int, array{label: string, total: int, pct: string}>, series: array<int, array<int, int>>}>}
     */
    protected function bands(Service $service, array $f): array
    {
        $limit = $f['limit'];
        $to    = $f['to']->copy()->endOfDay();

        $rows = DB::table('productions as p')
            ->join('notes', 'notes.id', '=', 'p.note_id')
            ->leftJoin('companies', 'companies.id', '=', 'p.company_id')
            ->where('p.service_id', $service->uuid)
            ->where('p.completed', 1)
            ->where(fn ($q) => $q->whereNull('p.returned')->orWhere('p.returned', 0))
            ->where(fn ($q) => $q->whereNull('p.d5')->orWhere('p.d5', 0))
            ->where(fn ($q) => $q->whereNull('p.dfive')->orWhere('p.dfive', 0))
            ->where('notes.type_note', 2)
            ->whereNotNull('p.dt_note')
            ->whereNotNull('p.completed_at')
            ->whereBetween('p.completed_at', [$f['from'], $to])
            ->selectRaw(
                "DATE_FORMAT(p.completed_at, '%Y-%m') as ym, LOWER(COALESCE(companies.name, '')) as company,
                CASE WHEN GREATEST(DATEDIFF(p.completed_at, p.dt_note), 0) <= ? THEN 0
                     WHEN GREATEST(DATEDIFF(p.completed_at, p.dt_note), 0) <= 15 THEN 1
                     WHEN GREATEST(DATEDIFF(p.completed_at, p.dt_note), 0) <= 30 THEN 2 ELSE 3 END as band,
                COUNT(*) as total",
                [$limit]
            )
            ->groupBy('ym', 'company', 'band')
            ->get();

        $months = [];

        for ($cursor = $f['from']->copy()->startOfMonth(); $cursor->lte($to); $cursor->addMonth()) {
            $months[$cursor->format('Y-m')] = $cursor->copy();
        }

        $counts = ['satel' => [], 'own' => []];

        foreach ($rows as $row) {
            $group = str_contains($row->company, 'satel') ? 'satel' : (str_contains($row->company, 'edp') ? 'own' : null);

            if ($group) {
                $counts[$group][$row->ym][(int) $row->band] = ($counts[$group][$row->ym][(int) $row->band] ?? 0) + (int) $row->total;
            }
        }

        $groups = [];

        foreach ($counts as $group => $data) {
            $labels = [];
            $series = array_fill(0, 4, []);

            foreach ($months as $ym => $date) {
                $c     = array_map(fn ($b) => $data[$ym][$b] ?? 0, range(0, 3));
                $total = array_sum($c);

                $labels[] = ['label' => $date->locale('pt_BR')->translatedFormat('M/y'), 'total' => $total, 'pct' => $total > 0 ? number_format($c[0] / $total * 100, 1, ',', '.') . '%' : '—'];

                foreach ($c as $band => $count) {
                    $series[$band][] = $count;
                }
            }

            $groups[$group] = ['months' => $labels, 'series' => $series];
        }

        return ['limit' => $limit, 'band_labels' => ["0 a {$limit} dias", ($limit + 1) . ' a 15 dias', '16 a 30 dias', 'Acima de 30'], 'groups' => $groups];
    }
}
