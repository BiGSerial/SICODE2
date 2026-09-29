<?php

namespace App\Services\Reports;

use App\Models\FiveNote;
use App\Models\Holiday;
use App\Models\Partial;
use App\Models\Production;
use App\Models\WorkReport;
use App\Models\WorkReportFlowProduction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Relatório "Processo de medição – Pós Obra".
 *
 * Para cada informe (Final, Parcial, D5) mede, em dias úteis, cada etapa:
 *   1. Despacho Fiscal   : data do informe        -> dispatch_at da production de Fiscalização  (prazo 2)
 *   2. Fiscalização      : dispatch_at -> completed_at da production de Fiscalização              (prazo 3)
 *   3. Medição/Pagamento : dispatch_at -> completed_at da production de Pagamento/Medição         (prazo 3)
 *   4. Total             : data do informe        -> completed_at do Pagamento/Medição           (prazo 8)
 */
class PostWorkProcessReportService
{
    public const TYPE_FINAL = 'final';
    public const TYPE_PARTIAL = 'partial';
    public const TYPE_D5 = 'd5';

    public const LIMIT_FISCAL_DISPATCH = 2;
    public const LIMIT_FISCALIZATION = 3;
    public const LIMIT_MEASUREMENT = 3;
    public const LIMIT_TOTAL = 8;

    /** @var array<string, true>|null */
    private ?array $holidays = null;

    /**
     * @param array{type?: string, from?: ?string, to?: ?string, company_id?: ?string, only_late?: bool|string, search?: ?string} $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(array $filters): Collection
    {
        $type = $filters['type'] ?? 'all';

        $rows = collect();

        if (in_array($type, ['all', self::TYPE_FINAL], true)) {
            $rows = $rows->concat($this->finalRows($filters));
        }

        if (in_array($type, ['all', self::TYPE_PARTIAL], true)) {
            $rows = $rows->concat($this->partialRows($filters));
        }

        if (in_array($type, ['all', self::TYPE_D5], true)) {
            $rows = $rows->concat($this->d5Rows($filters));
        }

        if (filter_var($filters['only_late'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $rows = $rows->filter(fn (array $row) => $row['any_late']);
        }

        return $rows->sortByDesc('start_at_raw')->values();
    }

    /**
     * @return array{total: int, late: int, on_time: int, by_type: array<string, int>}
     */
    public function summarize(Collection $rows): array
    {
        return [
            'total'   => $rows->count(),
            'late'    => $rows->where('any_late', true)->count(),
            'on_time' => $rows->where('any_late', false)->count(),
            'by_type' => $rows->countBy('type')->all(),
        ];
    }

    // ---------------------------------------------------------------------
    // Informe Final
    // ---------------------------------------------------------------------

    private function finalRows(array $filters): Collection
    {
        $query = WorkReport::query()
            ->with([
                'Note:id,note',
                'Company:id,name',
                'User:id,name',
                'Adsform:id,work_report_id,amount,created_at',
                'FlowProductions' => fn ($q) => $q->where('is_current', true)
                    ->with(['Production' => fn ($p) => $p->with($this->productionRelations())]),
            ])
            ->whereNotNull('informed_at')
            ->where(fn ($q) => $q->whereNull('canceled')->orWhere('canceled', false));

        $this->applyRange($query, 'informed_at', $filters);
        $this->applyCompany($query, $filters);
        $this->applySearch($query, $filters);

        return $query->get()->map(function (WorkReport $report) {
            $flows = $report->FlowProductions;

            $fiscal = $this->pickLatest(
                $flows->where('stage', WorkReportFlowProduction::STAGE_FISCALIZATION)->pluck('Production')->filter()
            );
            $payment = $this->pickLatest(
                $flows->where('stage', WorkReportFlowProduction::STAGE_PAYMENT)->pluck('Production')->filter()
            );

            return $this->buildRow(self::TYPE_FINAL, $report->informed_at, $fiscal, $payment, [
                'note'          => $report->Note?->note,
                'company'       => $report->Company?->name,
                'informer'      => $report->informer ?? $report->User?->name,
                'ads_at'        => $report->Adsform?->created_at,
                'ads_amount'    => $report->Adsform?->amount,
                'has_ads'       => $report->Adsform !== null,
                'informed_at'   => $report->informed_at,
            ]);
        });
    }

    // ---------------------------------------------------------------------
    // Informe Parcial
    // ---------------------------------------------------------------------

    private function partialRows(array $filters): Collection
    {
        $query = Partial::query()
            ->with([
                'Note:id,note',
                'company:id,name',
                'user:id,name',
                'engineer:id,name',
                'supervisor:id,name',
                'payer:id,name',
                'productions' => fn ($q) => $q->with($this->productionRelations()),
            ]);

        $this->applyRange($query, 'created_at', $filters);
        $this->applyCompany($query, $filters);
        $this->applySearch($query, $filters);

        return $query->get()->map(function (Partial $partial) {
            [$fiscal, $payment] = $this->pickStages($partial->productions, $partial->created_at);

            return $this->buildRow(self::TYPE_PARTIAL, $partial->created_at, $fiscal, $payment, [
                'note'           => $partial->Note?->note,
                'company'        => $partial->company?->name,
                'informer'       => $partial->user?->name,
                'allow'          => $partial->allow,
                'decision_at'    => $partial->decision_at,
                'engineer'       => $partial->engineer?->name,
                'supervision'    => $partial->supervision,
                'supervision_at' => $partial->supervision_at,
                'supervisor'     => $partial->supervisor?->name,
                'payment'        => $partial->payment,
                'payment_at'     => $partial->payment_at,
                'payer'          => $partial->payer?->name,
                'value'          => $partial->value,
            ]);
        });
    }

    // ---------------------------------------------------------------------
    // Informe D5
    // ---------------------------------------------------------------------

    private function d5Rows(array $filters): Collection
    {
        $query = FiveNote::query()
            ->with([
                'note:id,note',
                'company:id,name',
                'productions' => fn ($q) => $q->with($this->productionRelations()),
            ])
            ->whereNotNull('completed_at');

        $this->applyRange($query, 'completed_at', $filters);
        $this->applyCompany($query, $filters);

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(fn ($q) => $q->where('note_d5', 'like', "%{$search}%")
                ->orWhereHas('note', fn ($n) => $n->where('note', 'like', "%{$search}%")));
        }

        return $query->get()->map(function (FiveNote $five) {
            // completed_at pode mudar se o D5 for rejeitado e refeito: só valem as productions posteriores.
            [$fiscal, $payment] = $this->pickStages($five->productions, $five->completed_at);

            return $this->buildRow(self::TYPE_D5, $five->completed_at, $fiscal, $payment, [
                'note'           => $five->note?->note,
                'note_d5'        => $five->note_d5,
                'company'        => $five->company?->name,
                'informer'       => $five->name,
                'supervision'    => $five->is_supervisioned,
                'supervision_at' => $five->supervisioned_at,
                'payment'        => $five->is_payed,
                'payment_at'     => $five->payed_at,
            ]);
        });
    }

    // ---------------------------------------------------------------------
    // Núcleo
    // ---------------------------------------------------------------------

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function buildRow(string $type, ?Carbon $start, ?Production $fiscal, ?Production $payment, array $extra): array
    {
        $stages = [
            'fiscal_dispatch' => $this->stage($start, $fiscal?->dispatch_at, self::LIMIT_FISCAL_DISPATCH),
            'fiscalization'   => $this->stage($fiscal?->dispatch_at, $fiscal?->completed_at, self::LIMIT_FISCALIZATION),
            'measurement'     => $this->stage($payment?->dispatch_at, $payment?->completed_at, self::LIMIT_MEASUREMENT),
            'total'           => $this->stage($start, $payment?->completed_at, self::LIMIT_TOTAL),
        ];

        return array_merge([
            'type'          => $type,
            'type_label'    => ['final' => 'Informe Final', 'partial' => 'Informe Parcial', 'd5' => 'Informe D5'][$type],
            'start_at'      => $start,
            'start_at_raw'  => $start?->timestamp ?? 0,
            'fiscal'        => $this->productionInfo($fiscal),
            'payment'       => $this->productionInfo($payment),
            'stages'        => $stages,
            'any_late'      => collect($stages)->contains(fn (array $s) => $s['late']),
        ], $extra);
    }

    /**
     * Uma etapa: dias úteis entre $from e $to (ou até agora se ainda aberta) contra o prazo.
     *
     * @return array{from: ?Carbon, to: ?Carbon, limit: int, days: ?int, late_days: int, late: bool, status: string}
     */
    private function stage(?Carbon $from, ?Carbon $to, int $limit): array
    {
        if (!$from) {
            return ['from' => null, 'to' => $to, 'limit' => $limit, 'days' => null, 'late_days' => 0, 'late' => false, 'status' => 'pending'];
        }

        $closed = $to !== null;
        $days = $this->businessDaysBetween($from, $to ?? now());
        $lateDays = max(0, $days - $limit);

        return [
            'from'      => $from,
            'to'        => $to,
            'limit'     => $limit,
            'days'      => $days,
            'late_days' => $lateDays,
            'late'      => $lateDays > 0,
            'status'    => match (true) {
                $closed && $lateDays > 0 => 'late',
                $closed => 'on_time',
                $lateDays > 0 => 'open_late',
                default => 'open',
            },
        ];
    }

    /**
     * Dias úteis decorridos: conta cada dia útil após o dia de $from até o dia de $to (inclusive).
     */
    public function businessDaysBetween(Carbon $from, Carbon $to): int
    {
        $cursor = $from->copy()->startOfDay()->addDay();
        $end = $to->copy()->startOfDay();
        $days = 0;

        while ($cursor->lte($end)) {
            if ($this->isBusinessDay($cursor)) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }

    private function isBusinessDay(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        if ($this->holidays === null) {
            $state = strtoupper((string) config('sicode.ruleset', 'es'));

            $this->holidays = Holiday::query()
                ->where('state', $state)
                ->pluck('date')
                ->mapWithKeys(fn ($d) => [Carbon::parse($d)->toDateString() => true])
                ->all();
        }

        return !isset($this->holidays[$date->toDateString()]);
    }

    /**
     * Separa as productions em Fiscalização e Medição/Pagamento, considerando só as posteriores à data do informe.
     *
     * @return array{0: ?Production, 1: ?Production}
     */
    private function pickStages(Collection $productions, ?Carbon $since): array
    {
        $valid = $productions->filter(function (Production $p) use ($since) {
            $at = $p->dispatch_at ?? $p->created_at;

            return !$since || ($at && $at->gte($since));
        });

        $fiscal = $this->pickLatest($valid->filter(fn (Production $p) => $this->serviceKind($p) === 'fiscal'));
        $payment = $this->pickLatest($valid->filter(fn (Production $p) => $this->serviceKind($p) === 'payment'));

        return [$fiscal, $payment];
    }

    private function serviceKind(Production $production): ?string
    {
        $name = Str::of((string) $production->Service?->service)->ascii()->lower()->squish()->toString();

        return match (true) {
            Str::contains($name, 'fiscalizacao') => 'fiscal',
            Str::contains($name, ['pagamento', 'medicao']) => 'payment',
            default => null,
        };
    }

    /** Em caso de rejeição/redespacho vale a production mais recente. */
    private function pickLatest(Collection $productions): ?Production
    {
        return $productions
            ->sortByDesc(fn (Production $p) => ($p->dispatch_at ?? $p->created_at)?->timestamp . str_pad((string) $p->id, 12, '0', STR_PAD_LEFT))
            ->first();
    }

    /** @return array<string, mixed>|null */
    private function productionInfo(?Production $production): ?array
    {
        if (!$production) {
            return null;
        }

        return [
            'id'           => $production->id,
            'dispatch_at'  => $production->dispatch_at,
            'att_at'       => $production->att_at,
            'completed_at' => $production->completed_at,
            'user'         => $production->User?->name,
            'company'      => $production->User?->Company?->name,
            'att_user'     => $production->Att?->name,
            'dispatcher'   => $production->Dispatcher?->name,
        ];
    }

    /** @return array<int|string, mixed> */
    private function productionRelations(): array
    {
        return [
            'Service:id,uuid,service',
            'User:id,name,company_id',
            'User.Company:id,name',
            'Att:id,name',
            'Dispatcher:id,name',
        ];
    }

    private function applyRange($query, string $column, array $filters): void
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $query->when($from, fn ($q) => $q->where($column, '>=', Carbon::parse($from)->startOfDay()))
            ->when($to, fn ($q) => $q->where($column, '<=', Carbon::parse($to)->endOfDay()));
    }

    private function applyCompany($query, array $filters): void
    {
        $company = $filters['company_id'] ?? null;

        $query->when($company, fn ($q) => $q->where('company_id', $company));
    }

    private function applySearch($query, array $filters): void
    {
        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->whereHas('Note', fn ($n) => $n->where('note', 'like', "%{$search}%"));
        }
    }
}
