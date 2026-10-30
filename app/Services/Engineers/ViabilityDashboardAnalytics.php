<?php

namespace App\Services\Engineers;

use App\Models\{Company, Form, User, Viability};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Dados do painel de Viabilidade da Engenharia (tela e Excel leem daqui).
 *
 * Regras (ver docs/engineers/PAINEL_VIABILIDADE.md):
 * - Visão: superadm vê tudo; os demais veem as viabilidades dos usuários sob a sua gestão (`engineer_id` em `visibleUserIdsForWork`),
 *   o mesmo recorte das listas e dos contadores do menu.
 * - Em viabilidade (agora) = não cancelada, não concluída, não tácita, não rejeitada e ainda com o engenheiro (`visible_partner = 0`);
 *   é o mesmo número do contador do menu. Idade = dias corridos desde o envio (`sended_at`); acima de 7 dias = fora do prazo base.
 * - Fechamento = `COALESCE(returned_at, completed_at)` de viabilidade concluída. Realizada: não tácita, ou tácita com justificativa
 *   concedida. Não realizada: tácita sem justificativa, ou com justificativa indeferida (multa estimada de 1% do valor).
 * - Entradas = `sended_at` no período. Rejeições = formulários rejeitados (`forms.rejected`) criados no período, por motivo.
 * - SLA = média de dias entre o envio e o retorno das concluídas não tácitas que têm `returned_at`.
 */
class ViabilityDashboardAnalytics
{
    public const MAX_DAYS = 366;

    public const LATE_DAYS = 7;

    public const PENALTY_RATE = 0.01;

    public const AGE_RANGES = ['0-3 dias' => [0, 3], '4-7 dias' => [4, 7], '8-15 dias' => [8, 15], '16-30 dias' => [16, 30], '31+ dias' => [31, PHP_INT_MAX]];

    public const OLDEST_LIMIT = 10;

    private const CLOSE = 'COALESCE(viabilities.returned_at, viabilities.completed_at)';

    /**
     * @param array<string, mixed> $filters from, to ('Y-m-d'), company (uuid)
     *
     * @return array{from: Carbon, to: Carbon, days: int, company: ?string}
     */
    public function normalize(array $filters, ?Carbon $now = null): array
    {
        $now ??= now();

        $to   = $this->date($filters['to'] ?? null, $now)->endOfDay();
        $from = $this->date($filters['from'] ?? null, $now->copy()->startOfMonth())->startOfDay();

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

        $pile    = $this->pileRows($user, $f, $now);
        $period  = $this->periodTotals($user, $f);
        $reasons = $this->rejectionReasons($user, $f);
        $daily   = $this->dailySeries($user, $f);

        return [
            'generated_at' => $now,
            'filters'      => $f,
            'pile'         => $pile,
            'oldest'       => array_slice($pile, 0, self::OLDEST_LIMIT),
            'summary'      => $this->summary($pile, $period, $reasons),
            'age_buckets'  => $this->ageBuckets($pile),
            'by_engineer'  => $this->pileBy($pile, 'engineer', 'Sem engenheiro'),
            'by_company'   => $this->byCompany($user, $f, $pile),
            'reasons'      => $reasons,
            'daily'        => $daily,
            'options'      => ['companies' => $this->companyOptions($user)],
        ];
    }

    /**
     * Snapshot + a lista completa da pilha (já completa no snapshot) — separado para o Excel poder crescer sem mexer na tela.
     *
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function report(User $user, array $filters, ?Carbon $now = null): array
    {
        return $this->snapshot($user, $filters, $now);
    }

    /** @return array<int, array{id: string, name: string}> */
    public function companyOptions(User $user): array
    {
        return Cache::remember('viab-dash:companies:' . $user->getKey(), 600, function () use ($user) {
            $ids = $this->scope($user, null)->whereNotNull('viabilities.company_id')->distinct()->pluck('viabilities.company_id');

            return Company::withTrashed()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['id' => (string) $c->id, 'name' => (string) $c->name])->all();
        });
    }

    /** Recorte de visão + empresa escolhida. */
    private function scope(User $user, ?string $company): Builder
    {
        return Viability::query()
            ->when(!$user->superadm, fn (Builder $q) => $q->whereIn('viabilities.engineer_id', $user->visibleUserIdsForWork()))
            ->when($company, fn (Builder $q, string $c) => $q->where('viabilities.company_id', $c));
    }

    /** Viabilidades concluídas cujo fechamento cai no período. */
    private function closed(User $user, array $f): Builder
    {
        return $this->scope($user, $f['company'])
            ->where('viabilities.completed', true)
            ->whereBetween(DB::raw(self::CLOSE), [$f['from'], $f['to']]);
    }

    private function realized(Builder $closed): Builder
    {
        return $closed->where(function (Builder $q) {
            $q->where('viabilities.tacit', false)
                ->orWhere(fn (Builder $t) => $t->where('viabilities.tacit', true)->whereHas('Justification', fn (Builder $j) => $j->where('granted', true)));
        });
    }

    private function notRealized(Builder $closed): Builder
    {
        return $closed->where('viabilities.tacit', true)->where(function (Builder $q) {
            $q->whereDoesntHave('Justification')->orWhereHas('Justification', fn (Builder $j) => $j->where('dismissed', true));
        });
    }

    /**
     * @param array<string, mixed> $f
     *
     * @return array<int, array<string, mixed>> da mais antiga para a mais recente
     */
    private function pileRows(User $user, array $f, Carbon $now): array
    {
        return $this->scope($user, $f['company'])
            ->where('viabilities.canceled', false)
            ->where('viabilities.completed', false)
            ->where('viabilities.tacit', false)
            ->where('viabilities.rejected', false)
            ->where('viabilities.visible_partner', false)
            ->with(['Note:id,note,rubrica,lexp', 'Company:id,name', 'Engineer:id,name'])
            ->orderBy('viabilities.sended_at')
            ->get(['viabilities.id', 'viabilities.note_id', 'viabilities.company_id', 'viabilities.engineer_id', 'viabilities.sended_at', 'viabilities.value'])
            ->map(function (Viability $v) use ($now) {
                $age = $v->sended_at ? (int) $v->sended_at->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()) : 0;

                return [
                    'id'         => $v->id,
                    'note'       => (string) ($v->Note?->note ?? '—'),
                    'rubrica'    => $v->Note?->rubrica,
                    'city'       => $v->Note?->lexp,
                    'company_id' => $v->company_id,
                    'company'    => $v->Company?->name,
                    'engineer'   => $v->Engineer?->name,
                    'value'      => (float) $v->value,
                    'sended_at'  => $v->sended_at,
                    'age_days'   => $age,
                    'late'       => $age > self::LATE_DAYS,
                ];
            })->all();
    }

    /**
     * @param array<string, mixed> $f
     *
     * @return array<string, mixed>
     */
    private function periodTotals(User $user, array $f): array
    {
        $realized    = $this->realized($this->closed($user, $f));
        $notRealized = $this->notRealized($this->closed($user, $f));

        $sla = $this->closed($user, $f)->where('viabilities.tacit', false)->whereNotNull('viabilities.returned_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, viabilities.sended_at, viabilities.returned_at)) as hours, COUNT(*) as n')
            ->toBase()->first();

        $notRealizedValue = (float) (clone $notRealized)->sum('viabilities.value');

        return [
            'entered'            => $this->scope($user, $f['company'])->whereBetween('viabilities.sended_at', [$f['from'], $f['to']])->count(),
            'realized'           => (clone $realized)->count(),
            'realized_value'     => (float) (clone $realized)->sum('viabilities.value'),
            'not_realized'       => (clone $notRealized)->count(),
            'not_realized_value' => $notRealizedValue,
            'penalty'            => round($notRealizedValue * self::PENALTY_RATE, 2),
            'sla_days'           => $sla && $sla->hours !== null ? round((float) $sla->hours / 24, 1) : null,
            'sla_count'          => (int) ($sla->n ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $f
     *
     * @return array<int, array{label: string, total: int}>
     */
    private function rejectionReasons(User $user, array $f): array
    {
        return Form::query()
            ->where('forms.rejected', true)
            ->whereBetween('forms.created_at', [$f['from'], $f['to']])
            ->whereHas('Viability', fn (Builder $v) => $v
                ->when(!$user->superadm, fn (Builder $q) => $q->whereIn('viabilities.engineer_id', $user->visibleUserIdsForWork()))
                ->when($f['company'], fn (Builder $q, string $c) => $q->where('viabilities.company_id', $c)))
            ->selectRaw('forms.reason as reason, COUNT(*) as total')
            ->groupBy('forms.reason')
            ->orderByDesc('total')
            ->toBase()
            ->get()
            ->map(fn ($r) => ['label' => trim((string) $r->reason) !== '' ? trim((string) $r->reason) : 'Sem motivo informado', 'total' => (int) $r->total])
            ->all();
    }

    /**
     * @param array<string, mixed> $f
     *
     * @return array{labels: array<int, string>, dates: array<int, string>, entered: array<int, int>, closed: array<int, int>}
     */
    private function dailySeries(User $user, array $f): array
    {
        $entered = $this->scope($user, $f['company'])->whereBetween('viabilities.sended_at', [$f['from'], $f['to']])
            ->selectRaw('DATE(viabilities.sended_at) as d, COUNT(*) as n')->groupBy('d')->toBase()->pluck('n', 'd');

        $closed = $this->closed($user, $f)
            ->selectRaw('DATE(' . self::CLOSE . ') as d, COUNT(*) as n')->groupBy('d')->toBase()->pluck('n', 'd');

        $dates = $labels = $in = $out = [];

        for ($day = $f['from']->copy()->startOfDay(); $day->lte($f['to']); $day->addDay()) {
            $key      = $day->toDateString();
            $dates[]  = $key;
            $labels[] = $day->format('d/m');
            $in[]     = (int) $entered->get($key, 0);
            $out[]    = (int) $closed->get($key, 0);
        }

        return ['labels' => $labels, 'dates' => $dates, 'entered' => $in, 'closed' => $out];
    }

    /**
     * Produtividade por empresa: entradas e rejeições no período, fechamentos no período e a pilha de agora.
     *
     * @param array<string, mixed>             $f
     * @param array<int, array<string, mixed>> $pile
     *
     * @return array<int, array<string, mixed>>
     */
    private function byCompany(User $user, array $f, array $pile): array
    {
        $entered = $this->scope($user, $f['company'])->whereBetween('viabilities.sended_at', [$f['from'], $f['to']])
            ->selectRaw('viabilities.company_id as c, COUNT(*) as n')->groupBy('c')->toBase()->pluck('n', 'c');

        $realized = $this->realized($this->closed($user, $f))
            ->selectRaw('viabilities.company_id as c, COUNT(*) as n, COALESCE(SUM(viabilities.value), 0) as v')->groupBy('c')->toBase()->get()->keyBy('c');

        $not = $this->notRealized($this->closed($user, $f))
            ->selectRaw('viabilities.company_id as c, COUNT(*) as n, COALESCE(SUM(viabilities.value), 0) as v')->groupBy('c')->toBase()->get()->keyBy('c');

        $rejected = Form::query()
            ->join('viabilities', 'viabilities.id', '=', 'forms.viability_id')
            ->where('forms.rejected', true)
            ->whereBetween('forms.created_at', [$f['from'], $f['to']])
            ->when(!$user->superadm, fn (Builder $q) => $q->whereIn('viabilities.engineer_id', $user->visibleUserIdsForWork()))
            ->when($f['company'], fn (Builder $q, string $c) => $q->where('viabilities.company_id', $c))
            ->selectRaw('viabilities.company_id as c, COUNT(*) as n')->groupBy('c')->toBase()->pluck('n', 'c');

        $sla = $this->closed($user, $f)->where('viabilities.tacit', false)->whereNotNull('viabilities.returned_at')
            ->selectRaw('viabilities.company_id as c, AVG(TIMESTAMPDIFF(HOUR, viabilities.sended_at, viabilities.returned_at)) as h')->groupBy('c')->toBase()->pluck('h', 'c');

        $piled = collect($pile)->groupBy('company_id')->map->count();

        $ids   = collect([$entered, $realized, $not, $rejected])->flatMap(fn ($c) => $c->keys())->unique()->filter()->values();
        $names = Company::withTrashed()->whereIn('id', $ids)->pluck('name', 'id');

        return $ids->map(function ($id) use ($entered, $realized, $not, $rejected, $sla, $piled, $names) {
            $r    = (int) ($realized->get($id)->n ?? 0);
            $n    = (int) ($not->get($id)->n ?? 0);
            $name = $names[$id] ?? 'Sem empresa';

            return [
                'label'          => $name,
                'entered'        => (int) $entered->get($id, 0),
                'realized'       => $r,
                'not_realized'   => $n,
                'rejected'       => (int) $rejected->get($id, 0),
                'in_progress'    => (int) $piled->get($id, 0),
                'rate'           => $r + $n > 0 ? round($r / ($r + $n) * 100, 1) : null,
                'sla_days'       => $sla->get($id) !== null ? round((float) $sla->get($id) / 24, 1) : null,
                'realized_value' => (float) ($realized->get($id)->v ?? 0),
            ];
        })->sortByDesc('entered')->values()->all();
    }

    /**
     * @param array<int, array<string, mixed>> $pile
     * @param array<string, mixed>             $period
     * @param array<int, array<string, mixed>> $reasons
     *
     * @return array<string, mixed>
     */
    private function summary(array $pile, array $period, array $reasons): array
    {
        $rows = collect($pile);
        $ages = $rows->pluck('age_days');

        return $period + [
            'open'        => $rows->count(),
            'open_late'   => $rows->where('late', true)->count(),
            'open_value'  => (float) $rows->sum('value'),
            'oldest'      => $ages->isEmpty() ? null : (int) $ages->max(),
            'average'     => $ages->isEmpty() ? null : round((float) $ages->avg(), 1),
            'rejected'    => (int) collect($reasons)->sum('total'),
            'closed'      => $period['realized'] + $period['not_realized'],
            'realization' => $period['realized'] + $period['not_realized'] > 0
                ? round($period['realized'] / ($period['realized'] + $period['not_realized']) * 100, 1)
                : null,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $pile
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function ageBuckets(array $pile): array
    {
        $ages = collect($pile)->pluck('age_days');

        return [
            'labels' => array_keys(self::AGE_RANGES),
            'values' => array_map(fn (array $r) => $ages->filter(fn (int $d) => $d >= $r[0] && $d <= $r[1])->count(), array_values(self::AGE_RANGES)),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $pile
     *
     * @return array<int, array{label: string, total: int, late: int, oldest: int}>
     */
    private function pileBy(array $pile, string $key, string $fallback): array
    {
        return collect($pile)
            ->groupBy(fn (array $r) => $r[$key] ?: $fallback)
            ->map(fn (Collection $items, string $label) => [
                'label'  => $label,
                'total'  => $items->count(),
                'late'   => $items->where('late', true)->count(),
                'oldest' => (int) $items->max('age_days'),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
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
