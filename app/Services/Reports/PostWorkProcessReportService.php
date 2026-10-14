<?php

namespace App\Services\Reports;

use App\Models\{FiveNote, Holiday, Partial, Production, WorkReport, WorkReportFlowProduction};
use App\Support\SicodeRules;
use Illuminate\Support\{Carbon, Collection, Str};
use Illuminate\Support\Facades\DB;

/**
 * Relatório "Processo de medição – Pós Obra".
 *
 * Para cada informe (Final, Parcial, D5) mede, em dias úteis, cada etapa:
 *   1. Despacho Fiscal   : data do informe        -> dispatch_at da production de Fiscalização  (prazo 2)
 *   2. Fiscalização      : dispatch_at -> completed_at da production de Fiscalização              (prazo 3)
 *   3. Medição/Pagamento : dispatch_at -> completed_at da production de Pagamento/Medição         (prazo 3)
 *   4. Total             : data do informe        -> completed_at do Pagamento/Medição           (prazo 8)
 *
 * O intervalo entre o fim da Fiscalização e o despacho da Medição ("despacho da medição") não tem prazo
 * próprio: conta apenas dentro do Total e é exibido em `dispatch_gap`.
 *
 * Fontes por tipo (mesmos marcos do consolidado `note_inform_flows`, que alimenta o log SAP):
 *   - Final   : produções de fiscalização/medição do `note_inform_flows` (fallback: vínculos do informe,
 *               enquanto a linha do informe ainda não foi sincronizada).
 *   - Parcial : campos do próprio parcial. Aprovação do engenheiro (decision_at) = despacho da fiscalização,
 *               supervision_at = fim da fiscalização e despacho da medição, payment_at = fim da medição.
 *   - D5      : produções posteriores à conclusão da D5; is_supervisioned/is_payed (com a data) completam o que
 *               a produção não registrou.
 */
class PostWorkProcessReportService
{
    public const TYPE_FINAL   = 'final';
    public const TYPE_PARTIAL = 'partial';
    public const TYPE_D5      = 'd5';

    /** Etapas em que o informe pode estar "parado" (linhas da matriz de envelhecimento). */
    public const CURRENT_STAGES = [
        'await_fiscal_dispatch'      => 'Aguardando despacho fiscal',
        'in_fiscalization'           => 'Em fiscalização',
        'await_measurement_dispatch' => 'Aguardando despacho da medição',
        'in_measurement'             => 'Em medição/pagamento',
        'done'                       => 'Concluído',
    ];

    /** Colunas da matriz: 0..MATRIX_MAX_DAY-1 e a última agrupa "MATRIX_MAX_DAY ou mais". */
    public const MATRIX_MAX_DAY = 12;

    /** @var array<string, true>|null */
    private ?array $holidays = null;

    /**
     * @param array{type?: string, from?: ?string, to?: ?string, company_id?: ?string, only_late?: bool|string, only_open?: bool|string, search?: ?string} $filters
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

        // Somente em aberto: exclui informes cujo Pagamento/Medição já foi concluído.
        if (filter_var($filters['only_open'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $rows = $rows->filter(fn (array $row) => $row['current_stage'] !== 'done');
        }

        return $rows->sortByDesc('start_at_raw')->values();
    }

    /**
     * Concluído = Pagamento/Medição finalizado. A taxa de estouro considera só os concluídos: os em aberto
     * ainda podem estourar, então aparecem à parte (`open_late` = já estourados).
     *
     * @return array<string, mixed>
     */
    public function summarize(Collection $rows): array
    {
        $done = $rows->where('current_stage', 'done');
        $open = $rows->where('current_stage', '!=', 'done');

        $doneLate   = $done->where('any_late', true)->count();
        $doneOnTime = $done->count() - $doneLate;

        return [
            'total'          => $rows->count(),
            'late'           => $rows->where('any_late', true)->count(),
            'on_time'        => $rows->where('any_late', false)->count(),
            'done'           => $done->count(),
            'done_on_time'   => $doneOnTime,
            'done_late'      => $doneLate,
            'open'           => $open->count(),
            'open_late'      => $open->where('any_late', true)->count(),
            'open_on_time'   => $open->where('any_late', false)->count(),
            'late_rate_done' => $done->count() ? (int) round($doneLate / $done->count() * 100) : null,
            'with_issues'    => $rows->filter(fn (array $row) => !empty($row['issues']))->count(),
            'by_type'        => $rows->countBy('type')->all(),
        ];
    }

    /** Última sincronização do consolidado que alimenta os informes finais. */
    public function lastSyncedAt(): ?Carbon
    {
        $value = DB::table('note_inform_flows')->where('active', 1)->max('calculated_at');

        return $value ? Carbon::parse($value) : null;
    }

    // ---------------------------------------------------------------------
    // Informe Final
    // ---------------------------------------------------------------------

    private function finalRows(array $filters): Collection
    {
        $query = WorkReport::query()
            ->with([
                'Note:id,note,type_note',
                'Company:id,name',
                'User:id,name',
                'Orders:id,ordem',
                'Adsform:id,work_report_id,amount,created_at',
                'FlowProductions' => fn ($q) => $q->where('is_current', true)
                    ->with(['Production' => fn ($p) => $p->with($this->productionRelations())]),
            ])
            ->whereNotNull('informed_at')
            // Informe rejeitado sai do processo de medição (não é pendência de despacho).
            ->where(fn ($q) => $q->whereNull('rejected')->orWhere('rejected', false))
            ->where(fn ($q) => $q->whereNull('canceled')->orWhere('canceled', false));

        $this->applyRange($query, 'informed_at', $filters);
        $this->applyCompany($query, $filters);
        $this->applySearch($query, $filters);

        $reports = $query->get();
        $flows   = $this->loadFinalFlowMilestones($reports->pluck('id')->all());

        return $reports->map(function (WorkReport $report) use ($flows) {
            $links         = $report->FlowProductions;
            $linkedFiscal  = $this->pickLatest($links->where('stage', WorkReportFlowProduction::STAGE_FISCALIZATION)->pluck('Production')->filter());
            $linkedPayment = $this->pickLatest($links->where('stage', WorkReportFlowProduction::STAGE_PAYMENT)->pluck('Production')->filter());
            $syncIssues    = [];

            if ($flows->has($report->id)) {
                $fiscal  = $flows[$report->id]['fiscal'];
                $payment = $flows[$report->id]['payment'];
                $source  = 'note_inform_flows';

                // Consolidado defasado: o vínculo do informe existe, mas o consolidado ainda não o refletiu.
                if (!$fiscal && $linkedFiscal) {
                    $syncIssues[] = 'Consolidado sem fiscalização, mas há vínculo no informe (aguardando sincronização)';
                }

                if (!$payment && $linkedPayment) {
                    $syncIssues[] = 'Consolidado sem medição/pagamento, mas há vínculo no informe (aguardando sincronização)';
                }
            } else {
                // Informe ainda não sincronizado no consolidado: usa o vínculo do próprio informe.
                $fiscal  = $this->productionInfo($linkedFiscal);
                $payment = $this->productionInfo($linkedPayment);
                $source  = 'work_report_flow_productions';
            }

            $scopes = $report->finalScopeBadges();

            return $this->buildRow(self::TYPE_FINAL, $report->informed_at, $fiscal, $payment, [
                'id'          => $report->id,
                'scopes'      => collect($scopes)->map(fn ($b) => ['label' => $b['label'], 'class' => $b['class']])->all(),
                'orders'      => $report->Orders->pluck('ordem')->filter()->values()->all(),
                'note'        => $report->Note?->note,
                'company'     => $report->Company?->name,
                'informer'    => $report->informer ?? $report->User?->name,
                'ads_at'      => $report->Adsform?->created_at,
                'ads_amount'  => $report->Adsform?->amount,
                'has_ads'     => $report->Adsform !== null,
                'informed_at' => $report->informed_at,
                'data_source' => $source,
                'sync_issues' => $syncIssues,
            ]);
        });
    }

    /**
     * Marcos de fiscalização/medição de cada informe final segundo o consolidado `note_inform_flows`
     * (mesma origem do log SAP). Um informe pode ter uma linha por escopo: vale a produção mais recente.
     *
     * @param array<int, int> $workReportIds
     * @return Collection<int, array{fiscal: ?array<string, mixed>, payment: ?array<string, mixed>}>
     */
    private function loadFinalFlowMilestones(array $workReportIds): Collection
    {
        if (empty($workReportIds)) {
            return collect();
        }

        $flows = collect($workReportIds)->chunk(1000)->flatMap(
            fn ($ids) => DB::table('note_inform_flows')
                ->where('flow_type', 'final')
                ->where('active', 1)
                ->whereIn('work_report_id', $ids->all())
                ->get(['id', 'work_report_id', 'fiscalization_production_id', 'measurement_production_id', 'fiscalization_entered_at', 'measurement_entered_at'])
        )->groupBy('work_report_id');

        $productionIds = $flows->flatten(1)
            ->flatMap(fn ($flow) => [$flow->fiscalization_production_id, $flow->measurement_production_id])
            ->filter()->unique()->values();

        $productions = $productionIds->chunk(1000)->flatMap(
            fn ($ids) => Production::query()->with($this->productionRelations())->whereIn('id', $ids->all())->get()
        )->keyBy('id');

        $latestProduction = function (Collection $rows, string $idColumn, string $enteredColumn) use ($productions): ?array {
            $row = $rows->filter(fn ($flow) => $flow->{$idColumn} && $productions->has($flow->{$idColumn}))
                ->sortByDesc(fn ($flow) => ($flow->{$enteredColumn} ?? '') . str_pad((string) $flow->id, 12, '0', STR_PAD_LEFT))
                ->first();

            return $row ? $this->productionInfo($productions[$row->{$idColumn}]) : null;
        };

        return $flows->map(fn (Collection $rows) => [
            'fiscal'  => $latestProduction($rows, 'fiscalization_production_id', 'fiscalization_entered_at'),
            'payment' => $latestProduction($rows, 'measurement_production_id', 'measurement_entered_at'),
        ]);
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
                'orders:id,ordem',
                'productions' => fn ($q) => $q->with($this->productionRelations()),
            ])
            // Parcial rejeitado (deny) sai do processo de medição.
            ->where(fn ($q) => $q->whereNull('deny')->orWhere('deny', false));

        $this->applyRange($query, 'created_at', $filters);
        $this->applyCompany($query, $filters);
        $this->applySearch($query, $filters);

        return $query->get()->map(function (Partial $partial) {
            // O parcial não passa por despacho de produção: os marcos são os campos dele.
            $fiscal = $partial->decision_at || $partial->supervision_at ? [
                'id'           => null,
                'dispatch_at'  => $partial->decision_at,
                'att_at'       => null,
                'completed_at' => $partial->supervision_at,
                'user'         => $partial->supervisor?->name,
                'company'      => null,
                'att_user'     => null,
                'dispatcher'   => $partial->engineer?->name,
            ] : null;

            $payment = $partial->supervision_at || $partial->payment_at ? [
                'id'           => null,
                'dispatch_at'  => $partial->supervision_at,
                'att_at'       => null,
                'completed_at' => $partial->payment_at,
                'user'         => $partial->payer?->name,
                'company'      => null,
                'att_user'     => null,
                'dispatcher'   => $partial->supervisor?->name,
            ] : null;

            return $this->buildRow(self::TYPE_PARTIAL, $partial->created_at, $fiscal, $payment, [
                'id'               => $partial->id,
                'orders'           => $partial->orders->pluck('ordem')->filter()->values()->all(),
                'note'             => $partial->Note?->note,
                'company'          => $partial->company?->name,
                'informer'         => $partial->user?->name,
                'allow'            => $partial->allow,
                'decision_at'      => $partial->decision_at,
                'engineer'         => $partial->engineer?->name,
                'supervision_flag' => (bool) $partial->supervision,
                'supervision_at'   => $partial->supervision_at,
                'supervisor'       => $partial->supervisor?->name,
                'payment_flag'     => (bool) $partial->payment,
                'payment_at'       => $partial->payment_at,
                'payer'            => $partial->payer?->name,
                'value'            => $partial->value,
                'data_source'      => 'partials',
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

            // Supervisão/pagamento marcados na própria D5 completam o que a produção não registrou.
            $fiscal  = $this->completeFromFlag($fiscal, (bool) $five->is_supervisioned, $five->supervisioned_at);
            $payment = $this->completeFromFlag($payment, (bool) $five->is_payed, $five->payed_at);

            return $this->buildRow(self::TYPE_D5, $five->completed_at, $fiscal, $payment, [
                'id'               => $five->id,
                'note'             => $five->note?->note,
                'note_d5'          => $five->note_d5,
                'company'          => $five->company?->name,
                'informer'         => $five->name,
                'supervision_flag' => (bool) $five->is_supervisioned,
                'supervision_at'   => $five->supervisioned_at,
                'payment_flag'     => (bool) $five->is_payed,
                'payment_at'       => $five->payed_at,
                'data_source'      => 'productions',
            ]);
        });
    }

    /**
     * @param array<string, mixed>|null $milestone
     * @return array<string, mixed>|null
     */
    private function completeFromFlag(?array $milestone, bool $flagged, ?Carbon $at): ?array
    {
        if (!$flagged || !$at) {
            return $milestone;
        }

        if ($milestone === null) {
            return [
                'id'           => null,
                'dispatch_at'  => null,
                'att_at'       => null,
                'completed_at' => $at,
                'user'         => null,
                'company'      => null,
                'att_user'     => null,
                'dispatcher'   => null,
                'from_flag'    => true,
            ];
        }

        if ($milestone['completed_at'] === null) {
            $milestone['completed_at'] = $at;
            $milestone['from_flag']    = true;
        }

        return $milestone;
    }

    // ---------------------------------------------------------------------
    // Regras por região, matriz e filtros
    // ---------------------------------------------------------------------

    /** @return array{fiscal_dispatch: int, fiscalization: int, measurement: int, total: int} */
    public function limits(): array
    {
        return collect(['fiscal_dispatch', 'fiscalization', 'measurement', 'total'])
            ->mapWithKeys(fn (string $k) => [$k => SicodeRules::postWorkProcessLimit($k)])
            ->all();
    }

    /** @return array<string, string> */
    public function stageLabels(): array
    {
        return [
            'fiscal_dispatch' => 'Despacho Fiscal',
            'fiscalization'   => 'Fiscalização',
            'measurement'     => SicodeRules::postWorkProcessMeasurementLabel(),
            'total'           => 'Total',
        ];
    }

    /** Limite acumulado (dias úteis desde o informe) até o qual cada etapa "parada" ainda é aceitável. */
    public function cumulativeLimit(string $currentStage): int
    {
        $l = $this->limits();

        return match ($currentStage) {
            'await_fiscal_dispatch' => $l['fiscal_dispatch'],
            'in_fiscalization'      => $l['fiscal_dispatch'] + $l['fiscalization'],
            default                 => $l['total'],
        };
    }

    /**
     * Acumulador: quantos informes (Final + Parcial + D5 mesclados) há em cada etapa e idade em dias úteis.
     *
     * @return array{days: array<int, int>, rows: array<string, array<string, mixed>>, col_totals: array<int, int>, total: int}
     */
    public function matrix(Collection $rows): array
    {
        $days = range(0, self::MATRIX_MAX_DAY);
        $out  = [];

        foreach (self::CURRENT_STAGES as $key => $label) {
            $limit = $this->cumulativeLimit($key);
            $cells = [];

            foreach ($days as $d) {
                $cells[$d] = ['count' => 0, 'by_type' => [], 'status' => $d > $limit ? 'late' : ($d === $limit ? 'limit' : 'ok')];
            }
            $out[$key] = ['label' => $label, 'limit' => $limit, 'cells' => $cells, 'total' => 0];
        }

        foreach ($rows as $row) {
            $day  = min($row['age_days'], self::MATRIX_MAX_DAY);
            $cell = &$out[$row['current_stage']]['cells'][$day];
            $cell['count']++;
            $cell['by_type'][$row['type_label']] = ($cell['by_type'][$row['type_label']] ?? 0) + 1;
            unset($cell);
            $out[$row['current_stage']]['total']++;
        }

        $colTotals = [];

        foreach ($days as $d) {
            $colTotals[$d] = collect($out)->sum(fn ($r) => $r['cells'][$d]['count']);
        }

        return ['days' => $days, 'rows' => $out, 'col_totals' => $colTotals, 'total' => $rows->count()];
    }

    /** Filtra pela célula clicada na matriz (etapa atual + idade; a última coluna agrupa "N ou mais"). */
    public function filterByCell(Collection $rows, ?string $stage, ?int $day): Collection
    {
        if (!$stage) {
            return $rows;
        }

        return $rows->filter(function (array $row) use ($stage, $day) {
            if ($row['current_stage'] !== $stage) {
                return false;
            }

            if ($day === null) {
                return true;
            }

            return $day >= self::MATRIX_MAX_DAY ? $row['age_days'] >= self::MATRIX_MAX_DAY : $row['age_days'] === $day;
        })->values();
    }

    // ---------------------------------------------------------------------
    // Núcleo
    // ---------------------------------------------------------------------

    /**
     * @param array<string, mixed>|null $fiscal  marcos da fiscalização (dispatch_at, completed_at, ...)
     * @param array<string, mixed>|null $payment marcos da medição/pagamento
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function buildRow(string $type, ?Carbon $start, ?array $fiscal, ?array $payment, array $extra): array
    {
        $limits      = $this->limits();
        $extraIssues = $extra['sync_issues'] ?? [];
        unset($extra['sync_issues']);

        $fiscalDispatch   = $fiscal['dispatch_at'] ?? null;
        $fiscalCompleted  = $fiscal['completed_at'] ?? null;
        $paymentDispatch  = $payment['dispatch_at'] ?? null;
        $paymentCompleted = $payment['completed_at'] ?? null;

        $stages = [
            // Fiscalização existente sem data de despacho: etapa sem dados (não conta como atraso em aberto).
            'fiscal_dispatch' => $fiscal && !$fiscalDispatch
                ? $this->stage(null, null, $limits['fiscal_dispatch'])
                : $this->stage($start, $fiscalDispatch, $limits['fiscal_dispatch']),
            'fiscalization' => $this->stage($fiscalDispatch, $fiscalCompleted, $limits['fiscalization']),
            'measurement'   => $this->stage($paymentDispatch, $paymentCompleted, $limits['measurement']),
            'total'         => $this->stage($start, $paymentCompleted, $limits['total']),
        ];

        $labels     = $this->stageLabels();
        $lateStages = collect($labels)
            ->filter(fn ($label, $key) => $stages[$key]['late'])
            ->values()
            ->all();

        $currentStage = match (true) {
            $paymentCompleted !== null => 'done',
            $payment !== null          => 'in_measurement',
            $fiscalCompleted !== null  => 'await_measurement_dispatch',
            $fiscal !== null           => 'in_fiscalization',
            default                    => 'await_fiscal_dispatch',
        };

        return array_merge([
            'id'           => null,
            'scopes'       => [],
            'orders'       => [],
            'overall'      => $lateStages ? 'late' : ($stages['total']['to'] ? 'on_time' : 'open'),
            'late_stages'  => $lateStages,
            'type'         => $type,
            'type_label'   => ['final' => 'Informe Final', 'partial' => 'Informe Parcial', 'd5' => 'Informe D5'][$type],
            'start_at'     => $start,
            'start_at_raw' => $start?->timestamp ?? 0,
            'fiscal'       => $fiscal,
            'payment'      => $payment,
            'stages'       => $stages,
            // Intervalo entre o fim da fiscalização e o despacho da medição: sem prazo próprio.
            'dispatch_gap'  => $fiscalCompleted && (!$payment || $paymentDispatch) ? $this->stage($fiscalCompleted, $paymentDispatch, PHP_INT_MAX) : null,
            'any_late'      => collect($stages)->contains(fn (array $s) => $s['late']),
            'current_stage' => $currentStage,
            'age_days'      => $stages['total']['days'] ?? 0,
            'gantt'         => $start ? $this->gantt($start, $fiscal, $payment, $limits) : [],
            'issues'        => array_merge($this->detectIssues($type, $start, $fiscal, $payment, $limits), $extraIssues),
        ], $extra);
    }

    /**
     * Inconsistências de dados que distorcem os prazos. Aparecem na tela e na exportação para que a leitura
     * gerencial saiba quando um número não é confiável.
     *
     * @param array<string, mixed>|null $fiscal
     * @param array<string, mixed>|null $payment
     * @param array<string, int> $limits
     * @return array<int, string>
     */
    private function detectIssues(string $type, ?Carbon $start, ?array $fiscal, ?array $payment, array $limits): array
    {
        $issues = [];

        if ($fiscal && !$fiscal['dispatch_at'] && empty($fiscal['from_flag'])) {
            $issues[] = 'Fiscalização sem data de despacho';
        }

        if ($payment && !$payment['dispatch_at'] && empty($payment['from_flag'])) {
            $issues[] = 'Medição/Pagamento sem data de despacho';
        }

        if ($start && $fiscal && $fiscal['dispatch_at'] && $fiscal['dispatch_at']->lt($start)) {
            $issues[] = 'Despacho da fiscalização anterior à data do informe';
        }

        if ($fiscal && $fiscal['dispatch_at'] && $fiscal['completed_at'] && $fiscal['completed_at']->lt($fiscal['dispatch_at'])) {
            $issues[] = 'Fiscalização concluída antes do despacho';
        }

        if ($payment && $payment['dispatch_at'] && $payment['completed_at'] && $payment['completed_at']->lt($payment['dispatch_at'])) {
            $issues[] = 'Medição/Pagamento concluído antes do despacho';
        }

        if ($payment && !$fiscal) {
            $issues[] = 'Medição/Pagamento sem fiscalização';
        } elseif ($payment && $payment['completed_at'] && $fiscal && !$fiscal['completed_at']) {
            $issues[] = 'Medição/Pagamento concluído com fiscalização em aberto';
        }

        if (!$fiscal && $type !== self::TYPE_PARTIAL && $start && $this->businessDaysBetween($start, now()) > $limits['total']) {
            $issues[] = 'Sem fiscalização vinculada além do prazo total';
        }

        return $issues;
    }

    /**
     * Segmentos do Gantt em dias úteis desde o informe. Etapa em aberto vai até "agora".
     * `limit_end` marca onde o prazo da etapa acaba (o que passar disso é atraso).
     *
     * @param array<string, mixed>|null $fiscal
     * @param array<string, mixed>|null $payment
     * @return array<int, array{key: string, start: int, end: int, open: bool, limit_end: ?int}>
     */
    private function gantt(Carbon $start, ?array $fiscal, ?array $payment, array $limits): array
    {
        $at  = fn (?Carbon $d) => $d ? $this->businessDaysBetween($start, $d) : null;
        $now = $this->businessDaysBetween($start, now());

        $tfd = $at($fiscal['dispatch_at'] ?? null);
        $tfc = $at($fiscal['completed_at'] ?? null);
        $tpd = $at($payment['dispatch_at'] ?? null);
        $tpc = $at($payment['completed_at'] ?? null);

        $segments   = [];
        $segments[] = ['key' => 'fiscal_dispatch', 'start' => 0, 'end' => $tfd ?? $now, 'open' => $tfd === null, 'limit_end' => $limits['fiscal_dispatch']];

        if ($tfd !== null) {
            $segments[] = ['key' => 'fiscalization', 'start' => $tfd, 'end' => max($tfd, $tfc ?? $now), 'open' => $tfc === null, 'limit_end' => $tfd + $limits['fiscalization']];
        }

        if ($tfc !== null) {
            $segments[] = ['key' => 'measurement_dispatch', 'start' => $tfc, 'end' => max($tfc, $tpd ?? $now), 'open' => $tpd === null, 'limit_end' => null];
        }

        if ($tpd !== null) {
            $segments[] = ['key' => 'measurement', 'start' => $tpd, 'end' => max($tpd, $tpc ?? $now), 'open' => $tpc === null, 'limit_end' => $tpd + $limits['measurement']];
        }

        return $segments;
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

        $closed   = $to !== null;
        $days     = $this->businessDaysBetween($from, $to ?? now());
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
                $closed                  => 'on_time',
                $lateDays > 0            => 'open_late',
                default                  => 'open',
            },
        ];
    }

    /**
     * Dias úteis decorridos: conta cada dia útil após o dia de $from até o dia de $to (inclusive).
     */
    public function businessDaysBetween(Carbon $from, Carbon $to): int
    {
        $cursor = $from->copy()->startOfDay()->addDay();
        $end    = $to->copy()->startOfDay();
        $days   = 0;

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
     * @return array{0: ?array<string, mixed>, 1: ?array<string, mixed>}
     */
    private function pickStages(Collection $productions, ?Carbon $since): array
    {
        $valid = $productions->filter(function (Production $p) use ($since) {
            $at = $p->dispatch_at ?? $p->created_at;

            return !$since || ($at && $at->gte($since));
        });

        $fiscal  = $this->pickLatest($valid->filter(fn (Production $p) => $this->serviceKind($p) === 'fiscal'));
        $payment = $this->pickLatest($valid->filter(fn (Production $p) => $this->serviceKind($p) === 'payment'));

        return [$this->productionInfo($fiscal), $this->productionInfo($payment)];
    }

    private function serviceKind(Production $production): ?string
    {
        $name = Str::of((string) $production->Service?->service)->ascii()->lower()->squish()->toString();

        return match (true) {
            Str::contains($name, 'fiscalizacao')           => 'fiscal',
            Str::contains($name, ['pagamento', 'medicao']) => 'payment',
            default                                        => null,
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
        $to   = $filters['to'] ?? null;

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
