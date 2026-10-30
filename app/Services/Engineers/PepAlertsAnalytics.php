<?php

namespace App\Services\Engineers;

use App\Models\{Partial, PepBalanceAlert, User, WorkReport};
use App\Services\Partner\PepBudgetWatchService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Dados da tela "Informes sem saldo no PEP": tudo o que a tela e o Excel mostram sai daqui.
 *
 * Regras de cálculo (ver docs/engineers/INFORMES_SEM_SALDO_PEP.md):
 * - A pilha (pendentes) é sempre a situação de agora: itens `open` cujo informe não foi removido, na região/empresa do engenheiro.
 * - Período (De/Até) vale para entradas (created_at), tratados (handled_at) e histórico.
 * - Valor = ADS (parcial: `partials.value`; final: `adsforms.amount`); informe final sem ADS não soma valor.
 * - Idade = dias corridos desde a entrada na lista.
 */
class PepAlertsAnalytics
{
    public const MAX_DAYS = 120;

    public const HISTORY_LIMIT = 200;

    public const AGE_RANGES = ['0-2 dias' => [0, 2], '3-7 dias' => [3, 7], '8-15 dias' => [8, 15], '16+ dias' => [16, PHP_INT_MAX]];

    public function __construct(private readonly PepBudgetWatchService $watch)
    {
    }

    /**
     * @param array<string, mixed> $filters from, to ('Y-m-d'), company (uuid), region
     *
     * @return array{from: Carbon, to: Carbon, days: int, company: ?string, region: ?string}
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

        return [
            'from'    => $from,
            'to'      => $to,
            'days'    => $from->diffInDays($to->copy()->startOfDay()) + 1,
            'company' => !empty($filters['company']) ? (string) $filters['company'] : null,
            'region'  => !empty($filters['region']) ? (string) $filters['region'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function snapshot(User $user, array $filters, ?Carbon $now = null): array
    {
        $now ??= now();
        $f = $this->normalize($filters, $now);

        $open    = $this->openRows($user, $f, $now);
        $handled = $this->handledRows($user, $f, self::HISTORY_LIMIT);
        $flow    = $this->flowCounts($user, $f);

        return [
            'generated_at' => $now,
            'filters'      => $f,
            'open'         => $open,
            'history'      => $handled['rows'],
            'summary'      => $this->summary($open, $flow),
            'by_region'    => $this->groupOpen($open, 'region', 'Sem região'),
            'by_company'   => $this->groupOpen($open, 'company', 'Sem empresa'),
            'by_reason'    => $this->byReason($open),
            'by_type'      => $this->byType($open),
            'age_buckets'  => $this->ageBuckets($open),
            'daily'        => $this->dailySeries($f, $flow),
            'options'      => $this->filterOptions($user),
        ];
    }

    /**
     * Snapshot + o histórico completo do período (só para o Excel).
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function report(User $user, array $filters, ?Carbon $now = null): array
    {
        $report            = $this->snapshot($user, $filters, $now);
        $report['history'] = $this->handledRows($user, $report['filters'], null)['rows'];

        return $report;
    }

    /** @return array{regions: array<int, string>, companies: array<int, array{id: string, name: string}>} */
    public function filterOptions(User $user): array
    {
        $alerts = $this->watch->scopeVisibleTo(PepBalanceAlert::query(), $user);

        return [
            'regions'   => (clone $alerts)->whereNotNull('region')->distinct()->orderBy('region')->pluck('region')->all(),
            'companies' => \App\Models\Company::withTrashed()
                ->whereIn('id', (clone $alerts)->whereNotNull('company_id')->distinct()->pluck('company_id'))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($c) => ['id' => (string) $c->id, 'name' => (string) $c->name])
                ->all(),
        ];
    }

    /** @param array<string, mixed> $f */
    private function applyFilters(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['company'], fn (Builder $q, string $company) => $q->where('company_id', $company))
            ->when($f['region'], fn (Builder $q, string $region) => $q->where('region', $region));
    }

    /**
     * @param array<string, mixed> $f
     *
     * @return array<int, array<string, mixed>> da mais antiga para a mais recente
     */
    private function openRows(User $user, array $f, Carbon $now): array
    {
        $query  = $this->watch->scopeVisibleTo($this->watch->openQuery(), $user);
        $alerts = $this->applyFilters($query, $f)->with(['Note:id,note', 'Company'])->orderBy('created_at')->get();

        return $this->rows($alerts, $now)->all();
    }

    /**
     * @param array<string, mixed> $f
     *
     * @return array{rows: array<int, array<string, mixed>>}
     */
    private function handledRows(User $user, array $f, ?int $limit): array
    {
        $query = $this->watch->scopeVisibleTo(PepBalanceAlert::query()->handled(), $user);
        $query = $this->applyFilters($query, $f)
            ->whereBetween('handled_at', [$f['from'], $f['to']])
            ->with(['Note:id,note', 'Company', 'Handler:id,name'])
            ->orderByDesc('handled_at');

        if ($limit) {
            $query->limit($limit);
        }

        return ['rows' => $this->rows($query->get(), now())->all()];
    }

    /**
     * @param Collection<int, PepBalanceAlert> $alerts
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function rows(Collection $alerts, Carbon $now): Collection
    {
        $partials = Partial::query()
            ->whereIn('id', $alerts->where('informe_type', PepBalanceAlert::TYPE_PARTIAL)->pluck('informe_id'))
            ->get()
            ->keyBy('id');

        $workReports = WorkReport::query()
            ->with('Adsform:id,work_report_id,amount')
            ->whereIn('id', $alerts->where('informe_type', PepBalanceAlert::TYPE_WORK_REPORT)->pluck('informe_id'))
            ->get(['id', 'current_status_label', 'current_status_class', 'canceled', 'rejected'])
            ->keyBy('id');

        return $alerts->map(function (PepBalanceAlert $a) use ($partials, $workReports, $now) {
            $partial         = $a->isPartial();
            $informe         = $partial ? $partials->get($a->informe_id) : $workReports->get($a->informe_id);
            $value           = $partial ? (float) ($informe?->value ?? 0) : (float) ($informe?->Adsform?->amount ?? 0);
            [$label, $color] = $partial ? $this->partialStatus($informe) : $this->workReportStatus($informe);
            $end             = $a->handled_at ?? $now;
            $autoAt          = $a->covered_since?->copy()->addHours(PepBudgetWatchService::AUTO_RESOLVE_HOURS);

            return [
                'id'            => $a->id,
                'informe_id'    => (int) $a->informe_id,
                'note'          => (string) ($a->Note?->note ?? '—'),
                'type'          => $partial ? 'partial' : 'final',
                'type_label'    => $partial ? 'Parcial' : 'Final',
                'scope_label'   => $this->scopeLabel($a->scope),
                'region'        => $a->region,
                'company'       => $a->Company?->name,
                'value'         => $value > 0 ? $value : null,
                'status'        => $label,
                'status_color'  => $color,
                'reason'        => $a->reason,
                'reason_label'  => $a->reasonLabel(),
                'created_at'    => $a->created_at,
                'age_days'      => (int) $a->created_at->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()),
                'covered_since' => $a->covered_since,
                'auto_at'       => $autoAt,
                'handled_at'    => $a->handled_at,
                'handled_via'   => $a->handled_via,
                'handler'       => $a->handled_via === PepBalanceAlert::VIA_AUTO ? 'Saída automática' : $a->Handler?->name,
                'notes'         => $a->handled_notes,
            ];
        });
    }

    /** @return array{0: string, 1: string} */
    private function partialStatus(?Partial $partial): array
    {
        return match (true) {
            $partial === null                           => ['Removido', 'secondary'],
            (bool) $partial->deny                       => ['Rejeitada', 'danger'],
            $partial->payment && $partial->allow        => ['Paga', 'success'],
            $partial->supervision && !$partial->payment => ['Em medição', 'info'],
            (bool) $partial->allow                      => ['Em fiscalização', 'primary'],
            default                                     => ['Aguardando aprovação', 'warning'],
        };
    }

    /** @return array{0: string, 1: string} */
    private function workReportStatus(?WorkReport $report): array
    {
        if (!$report) {
            return ['Removido', 'secondary'];
        }

        $color = str_starts_with((string) $report->current_status_class, 'text-bg-')
            ? substr((string) $report->current_status_class, 8)
            : 'secondary';

        return [$report->current_status_label ?: '—', $color];
    }

    private function scopeLabel(?string $scope): string
    {
        return match ($scope) {
            'network'    => 'Rede',
            'connection' => 'Ligação',
            'general', null, '' => 'Geral',
            default => ucfirst($scope),
        };
    }

    /**
     * Entradas e tratados por dia no período (as duas séries do gráfico e dos mini-gráficos).
     *
     * @param array<string, mixed> $f
     *
     * @return array{entered: Collection, handled: Collection, auto: Collection}
     */
    private function flowCounts(User $user, array $f): array
    {
        $base = fn () => $this->applyFilters($this->watch->scopeVisibleTo(PepBalanceAlert::query(), $user), $f);

        return [
            'entered' => $base()->whereBetween('created_at', [$f['from'], $f['to']])
                ->selectRaw('DATE(created_at) as d, COUNT(*) as n')->groupBy('d')->pluck('n', 'd'),
            'handled' => $base()->handled()->whereBetween('handled_at', [$f['from'], $f['to']])
                ->selectRaw('DATE(handled_at) as d, COUNT(*) as n')->groupBy('d')->pluck('n', 'd'),
            'auto' => $base()->handled()->where('handled_via', PepBalanceAlert::VIA_AUTO)->whereBetween('handled_at', [$f['from'], $f['to']])
                ->selectRaw('COUNT(*) as n')->pluck('n'),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $open
     * @param array<string, mixed>             $flow
     *
     * @return array<string, mixed>
     */
    private function summary(array $open, array $flow): array
    {
        $rows  = collect($open);
        $ages  = $rows->pluck('age_days');
        $enter = (int) $flow['entered']->sum();
        $done  = (int) $flow['handled']->sum();
        $auto  = (int) ($flow['auto']->first() ?? 0);

        return [
            'open'         => $rows->count(),
            'open_value'   => (float) $rows->sum('value'),
            'with_value'   => $rows->whereNotNull('value')->count(),
            'no_balance'   => $rows->where('reason', PepBalanceAlert::REASON_NO_BALANCE)->count(),
            'ads_exceeds'  => $rows->where('reason', PepBalanceAlert::REASON_ADS_EXCEEDS)->count(),
            'covered'      => $rows->whereNotNull('covered_since')->count(),
            'oldest'       => $ages->isEmpty() ? null : (int) $ages->max(),
            'average'      => $ages->isEmpty() ? null : round((float) $ages->avg(), 1),
            'entered'      => $enter,
            'handled'      => $done,
            'handled_auto' => $auto,
            'handled_eng'  => max(0, $done - $auto),
        ];
    }

    /**
     * Ranking da pilha por região ou empresa (quantidade e valor da ADS).
     *
     * @param array<int, array<string, mixed>> $open
     *
     * @return array<int, array{label: string, total: int, value: float, oldest: int}>
     */
    private function groupOpen(array $open, string $key, string $fallback): array
    {
        return collect($open)
            ->groupBy(fn (array $r) => $r[$key] ?: $fallback)
            ->map(fn (Collection $items, string $label) => [
                'label'  => $label,
                'total'  => $items->count(),
                'value'  => (float) $items->sum('value'),
                'oldest' => (int) $items->max('age_days'),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $open
     *
     * @return array<int, array{key: string, label: string, color: string, total: int}>
     */
    private function byReason(array $open): array
    {
        $rows = collect($open);

        return [
            ['key' => PepBalanceAlert::REASON_NO_BALANCE, 'label' => 'Sem saldo no PEP', 'color' => 'danger', 'total' => $rows->where('reason', PepBalanceAlert::REASON_NO_BALANCE)->count()],
            ['key' => PepBalanceAlert::REASON_ADS_EXCEEDS, 'label' => 'ADS acima do saldo', 'color' => 'warning', 'total' => $rows->where('reason', PepBalanceAlert::REASON_ADS_EXCEEDS)->count()],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $open
     *
     * @return array<int, array{label: string, total: int}>
     */
    private function byType(array $open): array
    {
        $rows = collect($open);

        return [
            ['label' => 'Parcial', 'total' => $rows->where('type', 'partial')->count()],
            ['label' => 'Final', 'total' => $rows->where('type', 'final')->count()],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $open
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function ageBuckets(array $open): array
    {
        $ages = collect($open)->pluck('age_days');

        return [
            'labels' => array_keys(self::AGE_RANGES),
            'values' => array_map(fn (array $r) => $ages->filter(fn (int $d) => $d >= $r[0] && $d <= $r[1])->count(), array_values(self::AGE_RANGES)),
        ];
    }

    /**
     * @param array<string, mixed> $f
     * @param array<string, mixed> $flow
     *
     * @return array{labels: array<int, string>, dates: array<int, string>, entered: array<int, int>, handled: array<int, int>}
     */
    private function dailySeries(array $f, array $flow): array
    {
        $dates = $labels = $in = $out = [];

        for ($day = $f['from']->copy()->startOfDay(); $day->lte($f['to']); $day->addDay()) {
            $key      = $day->toDateString();
            $dates[]  = $key;
            $labels[] = $day->format('d/m');
            $in[]     = (int) $flow['entered']->get($key, 0);
            $out[]    = (int) $flow['handled']->get($key, 0);
        }

        return ['labels' => $labels, 'dates' => $dates, 'entered' => $in, 'handled' => $out];
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
