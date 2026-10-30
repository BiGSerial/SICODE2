<?php

namespace App\Services\Engineers;

use App\Models\Company;
use App\Support\SicodeRules;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Dados do "Painel de informes" (Engenharia → Informes): tudo o que a tela e o Excel mostram sai daqui.
 *
 * Regras de cálculo (ver docs/engineers/PAINEL_DE_INFORMES.md):
 * - Universo = informes de conclusão de obra (work_reports) com data do informe (`informed_at`) no período, sem os cancelados.
 *   O informe devolvido e ainda sem novo envio (`rejected`) conta como recebido, mas não tem ADS a cobrar.
 * - Prazo e situação da ADS = a mesma regra da tela "Situação das ADS" (AdsSituationService): prazo de entrega a partir do informe
 *   (SicodeRules), ADS tácita pelo prazo próprio, atraso e multa pela AdsDeadlinePolicy.
 * - Devolução = linha em return_works (motivo da devolução). "Devolvido" é o informe que já teve ao menos uma devolução.
 * - Os indicadores e gráficos usam o recorte completo (período, empreiteira, busca, devolução, reinforme, situação atual);
 *   o filtro "Situação da ADS" vale para a lista e para a aba Registros do Excel.
 */
class InformDashboardAnalytics
{
    public const MAX_DAYS = 186;

    public const DAILY_MAX_DAYS = 93;

    public const TOP = 10;

    /** Situação da ADS do informe (chave => rótulo). Ordem fixa: é a dos gráficos. */
    public const BUCKETS = [
        'on_time'  => 'Entregue no prazo',
        'late'     => 'Entregue em atraso',
        'overdue'  => 'Vencida sem entrega',
        'open'     => 'Em aberto, no prazo',
        'legacy'   => 'Passivo (legado sem ADS)',
        'returned' => 'Devolvido, aguardando novo informe',
    ];

    private const CODE_TO_BUCKET = [
        'com_entrega'         => 'on_time',
        'entregue_atraso'     => 'late',
        'vencida_sem_entrega' => 'overdue',
        'no_prazo'            => 'open',
        'vencendo_3_dias'     => 'open',
        'a_informar'          => 'open',
        'passivo'             => 'legacy',
    ];

    public function __construct(private readonly AdsSituationService $ads)
    {
    }

    /** @return array<int, string>|null empresas visíveis ao usuário (null = todas) */
    public function scopeFor(\App\Models\User $user): ?array
    {
        return $user->contract ? SicodeRules::visibleCompanyIdsFor($user) : null;
    }

    /**
     * @param array<string, mixed> $f from, to ('Y-m-d'), company, search, returned, retry, status, ads, scope
     *
     * @return array<string, mixed>
     */
    public function normalize(array $f, ?Carbon $now = null): array
    {
        $now ??= now();

        $to   = $this->date($f['to'] ?? null, $now)->endOfDay();
        $from = $this->date($f['from'] ?? null, $now->copy()->startOfMonth())->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $truncated = $from->diffInDays($to) >= self::MAX_DAYS;

        if ($truncated) {
            $from = $to->copy()->subDays(self::MAX_DAYS - 1)->startOfDay();
        }

        $one = fn (string $key, array $allowed) => in_array($f[$key] ?? '', $allowed, true) ? (string) $f[$key] : '';

        return [
            'from'      => $from,
            'to'        => $to,
            'days'      => $from->diffInDays($to->copy()->startOfDay()) + 1,
            'truncated' => $truncated,
            'company'   => ($f['company'] ?? null) ?: null,
            'search'    => trim((string) ($f['search'] ?? '')),
            'returned'  => $one('returned', ['sim', 'nao', 'aguardando']),
            'retry'     => $one('retry', ['sim', 'nao']),
            'status'    => ($f['status'] ?? null) ?: null,
            'ads'       => $one('ads', array_keys(self::BUCKETS)),
            'scope'     => $f['scope'] ?? null,
        ];
    }

    /**
     * Linhas do recorte (sem o filtro de situação da ADS) e os números do painel, calculados juntos.
     *
     * @param array<string, mixed> $f normalize()
     *
     * @return array<string, mixed>
     */
    public function assemble(array $f, bool $cache = false): array
    {
        $key = 'inform-dash:' . md5(json_encode([$f['from']->toDateString(), $f['to']->toDateString(), $f['company'], $f['search'], $f['returned'], $f['retry'], $f['status'], $f['scope']]));
        $run = fn () => $this->compute($f);

        return $cache ? Cache::remember($key, now()->addMinutes(2), $run) : $run();
    }

    /** Conjunto completo para o Excel. */
    public function report(array $f): array
    {
        $f = $this->normalize($f);

        return $this->assemble($f) + ['filters' => $f, 'generated_at' => now()];
    }

    /** @return Collection<int, array<string, mixed>> linhas já filtradas pela situação da ADS, do informe mais novo para o mais antigo */
    public function listRows(array $snapshot, array $f): Collection
    {
        return collect($snapshot['rows'])->when($f['ads'] !== '', fn (Collection $rows) => $rows->where('bucket', $f['ads']))->values();
    }

    public function paginate(Collection $rows, int $page, int $perPage, string $path): LengthAwarePaginator
    {
        return new Paginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page, ['path' => $path]);
    }

    /** Empreiteiras (contrato de obra) para o filtro. */
    public function companies(?array $scope = null): Collection
    {
        return Company::query()
            ->whereNull('deleted_at')
            ->whereRelation('contracts', fn ($q) => $q->where('construction', true)->where('service', false))
            ->when($scope !== null, fn ($q) => $q->whereIn('id', $scope ?: ['0']))
            ->orderBy('name')->get(['id', 'name']);
    }

    /** @return array<string, string> chave => rótulo da situação atual do informe (esteira) */
    public function statuses(): array
    {
        return Cache::remember('inform-dash:statuses', now()->addMinutes(10), fn () => DB::table('work_reports')
            ->whereNotNull('current_status_key')->where('current_status_key', '<>', '')
            ->selectRaw('current_status_key as k, MAX(current_status_label) as l')
            ->groupBy('current_status_key')->orderBy('l')->pluck('l', 'k')->all());
    }

    public static function days(?float $days): string
    {
        return $days === null ? '—' : number_format($days, 1, ',', '.') . ' d';
    }

    /** @return array<string, mixed> */
    private function compute(array $f): array
    {
        $raw  = $this->query($f)->get();
        $rows = $this->enrich($raw);

        return ['rows' => $rows->all()] + $this->indicators($rows, $f) + ['reasons' => $this->reasons($rows->pluck('id'))];
    }

    private function query(array $f): Builder
    {
        $q = DB::table('work_reports as wr')
            ->join('notes as n', 'n.id', '=', 'wr.note_id')
            ->leftJoin('companies as c', 'c.id', '=', 'wr.company_id')
            ->leftJoin('adsforms as af', function ($join) {
                // Alguns informes têm mais de uma ADS: vale a mais recente, para o informe não aparecer duas vezes.
                $join->on('af.work_report_id', '=', 'wr.id')
                    ->whereRaw('af.id = (SELECT MAX(a2.id) FROM adsforms a2 WHERE a2.work_report_id = wr.id)');
            })
            ->where('wr.canceled', false)
            ->whereNotNull('wr.informed_at')
            ->whereBetween('wr.informed_at', [$f['from'], $f['to']])
            ->select([
                'wr.id as work_report_id',
                'wr.informed_at',
                'wr.company_id',
                'wr.rejected',
                'wr.retry',
                'wr.current_status_key',
                'wr.current_status_label',
                'n.note as note_number',
                DB::raw('COALESCE(c.name, "—") as company_name'),
                'af.id as adsform_id',
                'af.tacit',
                'af.tacit_due_at',
                'af.tacit_delivered_at',
                'af.created_at as ads_created_at',
                DB::raw('(SELECT ac.uf FROM andresscompanies ac WHERE ac.company_id = wr.company_id ORDER BY ac.id LIMIT 1) as state'),
                DB::raw('(SELECT COUNT(*) FROM return_works rw WHERE rw.work_report_id = wr.id) as returns_count'),
                DB::raw('(SELECT rw2.category FROM return_works rw2 WHERE rw2.work_report_id = wr.id ORDER BY rw2.id DESC LIMIT 1) as last_return'),
            ])
            ->orderByDesc('wr.informed_at')
            ->orderByDesc('wr.id');

        if ($f['scope'] !== null) {
            $q->whereIn('wr.company_id', $f['scope'] ?: ['0']);
        }

        if ($f['company']) {
            $q->where('wr.company_id', $f['company']);
        }

        if ($f['status']) {
            $q->where('wr.current_status_key', $f['status']);
        }

        if ($f['retry'] !== '') {
            $q->where('wr.retry', $f['retry'] === 'sim');
        }

        $hasReturn = fn ($sub) => $sub->selectRaw('1')->from('return_works as rwx')->whereColumn('rwx.work_report_id', 'wr.id');

        match ($f['returned']) {
            'sim'        => $q->where(fn ($w) => $w->where('wr.rejected', true)->orWhereExists($hasReturn)),
            'nao'        => $q->where('wr.rejected', false)->whereNotExists($hasReturn),
            'aguardando' => $q->where('wr.rejected', true),
            default      => null,
        };

        if ($f['search'] !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $f['search']) . '%';

            $q->where(function ($w) use ($like) {
                $w->where('n.note', 'like', $like)
                    ->orWhere('c.name', 'like', $like)
                    ->orWhereExists(fn ($sub) => $sub->selectRaw('1')
                        ->from('order_work_report as owr')
                        ->join('orders as o', 'o.id', '=', 'owr.order_id')
                        ->whereColumn('owr.work_report_id', 'wr.id')
                        ->where('o.ordem', 'like', $like));
            });
        }

        return $q;
    }

    /**
     * @param Collection<int, object> $raw
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function enrich(Collection $raw): Collection
    {
        $this->ads->preloadAdjustments($raw->where('rejected', false)->pluck('work_report_id'));
        $fmt = fn (?Carbon $d) => $d?->format('Y-m-d H:i:s');

        return $raw->map(function (object $r) use ($fmt) {
            $rejected = (bool) $r->rejected;
            $ads      = $rejected ? null : $this->ads->enrichRow($r);
            $bucket   = $rejected ? 'returned' : (self::CODE_TO_BUCKET[$ads['status_code']] ?? 'open');
            $informed = Carbon::parse($r->informed_at);
            $delivery = $ads && $ads['delivered_at'] ? max(0, $informed->floatDiffInDays($ads['delivered_at'], false)) : null;
            $late     = in_array($bucket, ['late', 'overdue'], true);

            return [
                'id'            => (int) $r->work_report_id,
                'note'          => (string) $r->note_number,
                'company_id'    => $r->company_id,
                'company'       => (string) $r->company_name,
                'informed_at'   => $fmt($informed),
                'due_at'        => $fmt($ads['due_at'] ?? null),
                'delivered_at'  => $fmt($ads['delivered_at'] ?? null),
                'bucket'        => $bucket,
                'status_code'   => $ads['status_code'] ?? null,
                'delay_days'    => $late ? (int) $ads['delay_days'] : 0,
                'penalty'       => $late ? (float) $ads['penalty_percentage'] : 0.0,
                'delivery_days' => $delivery,
                'tacit'         => (bool) $r->tacit,
                'retry'         => (bool) $r->retry,
                'rejected'      => $rejected,
                'returns'       => (int) $r->returns_count,
                'last_return'   => $r->last_return,
                'status_key'    => $r->current_status_key,
                'status_label'  => $r->current_status_label ?: 'Sem situação',
                'days_to_due'   => $ads['days_to_due'] ?? null,
                'expiring'      => ($ads['status_code'] ?? null) === 'vencendo_3_dias',
            ];
        });
    }

    /**
     * @param Collection<int, array<string, mixed>> $rows
     *
     * @return array<string, mixed>
     */
    private function indicators(Collection $rows, array $f): array
    {
        $count   = fn (string $bucket) => $rows->where('bucket', $bucket)->count();
        $buckets = array_combine(array_keys(self::BUCKETS), array_map($count, array_keys(self::BUCKETS)));

        $dueBase   = $buckets['on_time'] + $buckets['late'] + $buckets['overdue'];
        $lateRows  = $rows->whereIn('bucket', ['late', 'overdue']);
        $delivered = $rows->whereNotNull('delivery_days');
        $returned  = $rows->filter(fn (array $r) => $r['returns'] > 0 || $r['rejected']);
        $tacit     = $rows->where('tacit', true);
        $pct       = fn (int|float $a, int|float $b) => $b > 0 ? round($a / $b * 100, 1) : null;

        $summary = [
            'total'         => $rows->count(),
            'returned'      => $returned->count(),
            'waiting_new'   => $buckets['returned'],
            'pct_returned'  => $pct($returned->count(), $rows->count()),
            'retries'       => $rows->where('retry', true)->count(),
            'buckets'       => $buckets,
            'due_base'      => $dueBase,
            'pct_on_time'   => $pct($buckets['on_time'], $dueBase),
            'expiring'      => $rows->where('expiring', true)->count(),
            'avg_delivery'  => $delivered->isNotEmpty() ? round($delivered->avg('delivery_days'), 1) : null,
            'avg_late'      => $lateRows->isNotEmpty() ? round($lateRows->avg('delay_days'), 1) : null,
            'avg_penalty'   => $lateRows->isNotEmpty() ? round($lateRows->avg('penalty'), 1) : null,
            'max_late'      => (int) $lateRows->max('delay_days'),
            'tacit'         => $tacit->count(),
            'tacit_pending' => $tacit->filter(fn (array $r) => $r['delivered_at'] === null && $r['bucket'] !== 'returned')->count(),
        ];

        return [
            'summary'   => $summary,
            'series'    => $this->series($rows, $f),
            'companies' => $this->byCompany($rows),
            'statuses'  => $rows->groupBy('status_label')
                ->map(fn (Collection $g, string $label) => ['label' => $label, 'key' => $g->first()['status_key'], 'total' => $g->count()])
                ->sortByDesc('total')->values()->all(),
        ];
    }

    /**
     * Série do período: por dia (até DAILY_MAX_DAYS) ou por mês. Recebidos, devolvidos e com ADS em atraso ou vencida.
     *
     * @param Collection<int, array<string, mixed>> $rows
     *
     * @return array<string, mixed>
     */
    private function series(Collection $rows, array $f): array
    {
        $daily  = $f['days'] <= self::DAILY_MAX_DAYS;
        $format = $daily ? 'Y-m-d' : 'Y-m';
        $slots  = [];

        for ($d = $f['from']->copy(); $d->lte($f['to']); $daily ? $d->addDay() : $d->addMonthNoOverflow()->startOfMonth()) {
            $slots[$d->format($format)] = ['label' => $d->format($daily ? 'd/m' : 'm/Y'), 'received' => 0, 'returned' => 0, 'late' => 0, 'on_time' => 0, 'open' => 0];
        }

        foreach ($rows as $r) {
            $key = substr($r['informed_at'], 0, $daily ? 10 : 7);

            if (!isset($slots[$key])) {
                continue;
            }

            $slots[$key]['received']++;
            $slots[$key]['returned'] += ($r['returns'] > 0 || $r['rejected']) ? 1 : 0;
            $slots[$key]['late'] += in_array($r['bucket'], ['late', 'overdue'], true) ? 1 : 0;
            $slots[$key]['on_time'] += $r['bucket'] === 'on_time' ? 1 : 0;
            $slots[$key]['open'] += $r['bucket'] === 'open' ? 1 : 0;
        }

        $slots = array_values($slots);

        return [
            'unit'     => $daily ? 'day' : 'month',
            'labels'   => array_column($slots, 'label'),
            'received' => array_column($slots, 'received'),
            'returned' => array_column($slots, 'returned'),
            'late'     => array_column($slots, 'late'),
            'on_time'  => array_column($slots, 'on_time'),
            'open'     => array_column($slots, 'open'),
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function byCompany(Collection $rows): array
    {
        return $rows->groupBy('company')->map(function (Collection $g, string $name) {
            $on   = $g->where('bucket', 'on_time')->count();
            $late = $g->where('bucket', 'late')->count();
            $over = $g->where('bucket', 'overdue')->count();
            $base = $on + $late + $over;
            $bad  = $g->whereIn('bucket', ['late', 'overdue']);
            $ret  = $g->filter(fn (array $r) => $r['returns'] > 0 || $r['rejected'])->count();

            return [
                'name'         => $name,
                'total'        => $g->count(),
                'returned'     => $ret,
                'pct_returned' => $g->count() ? round($ret / $g->count() * 100, 1) : null,
                'on_time'      => $on,
                'late'         => $late,
                'overdue'      => $over,
                'open'         => $g->where('bucket', 'open')->count(),
                'pct_on_time'  => $base ? round($on / $base * 100, 1) : null,
                'avg_late'     => $bad->isNotEmpty() ? round($bad->avg('delay_days'), 1) : null,
            ];
        })->sortByDesc('total')->values()->all();
    }

    /**
     * Motivos das devoluções dos informes do recorte.
     *
     * @param Collection<int, int> $ids
     *
     * @return array<int, array{reason: string, total: int, pct: float}>
     */
    private function reasons(Collection $ids): array
    {
        $totals = [];

        foreach ($ids->chunk(2000) as $chunk) {
            DB::table('return_works')->whereIn('work_report_id', $chunk->all())
                ->selectRaw('category, COUNT(*) as n')->groupBy('category')->get()
                ->each(function ($row) use (&$totals) {
                    $totals[$row->category] = ($totals[$row->category] ?? 0) + (int) $row->n;
                });
        }

        arsort($totals);
        $sum = array_sum($totals);

        return collect($totals)->map(fn (int $n, string $reason) => ['reason' => $reason, 'total' => $n, 'pct' => $sum ? round($n / $sum * 100, 1) : 0.0])->values()->all();
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
