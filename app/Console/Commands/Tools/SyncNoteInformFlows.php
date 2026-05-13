<?php

namespace App\Console\Commands\Tools;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncNoteInformFlows extends Command
{
    protected $signature = 'sicode:sync-note-inform-flows
        {--note_id= : Processa apenas uma nota}
        {--flow_type= : Filtra por tipo (partial|final)}
        {--chunk=500 : Tamanho do lote}
        {--dry : Simula sem gravar}';

    protected $description = 'Gera/atualiza registros consolidados em note_inform_flows.';

    public function handle(): int
    {
        try {
            $dryRun = (bool) $this->option('dry');
            $chunkSize = max(100, (int) $this->option('chunk'));
            $noteId = $this->option('note_id') ? (int) $this->option('note_id') : null;
            $flowType = $this->normalizeFlowType($this->option('flow_type'));

            if ($flowType === null && $this->option('flow_type')) {
                $this->error("Opcao --flow_type invalida. Use 'partial' ou 'final'.");
                return self::FAILURE;
            }

            $partialCount = 0;
            $finalCount = 0;

            if ($flowType === null || $flowType === 'partial') {
                $partialCount = $this->syncPartials($chunkSize, $noteId, $dryRun);
            }

            if ($flowType === null || $flowType === 'final') {
                $finalCount = $this->syncFinals($chunkSize, $noteId, $dryRun);
            }

            $this->newLine();
            $this->info('Sincronizacao concluida.');
            $this->line('Modo: ' . ($dryRun ? 'DRY RUN' : 'GRAVACAO REAL'));
            $this->line("Registros partial processados: {$partialCount}");
            $this->line("Registros final processados: {$finalCount}");
            $this->line('Total processado: ' . ($partialCount + $finalCount));

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function syncPartials(int $chunkSize, ?int $noteId, bool $dryRun): int
    {
        $query = DB::table('partials as p')
            ->join('notes as n', 'n.id', '=', 'p.note_id')
            ->leftJoin('users as fiscal', 'fiscal.id', '=', 'p.supervision_id')
            ->leftJoinSub(
                DB::table('order_partial')->selectRaw('partial_id, MIN(order_id) as order_id')->groupBy('partial_id'),
                'op1',
                'op1.partial_id',
                '=',
                'p.id'
            )
            ->leftJoin('orders as o', 'o.id', '=', 'op1.order_id')
            ->select([
                'p.id',
                'p.note_id',
                'p.company_id',
                'p.decision_at',
                'p.supervision_id',
                'p.supervision_at',
                'p.payment',
                'p.payment_at',
                'p.complete',
                'p.deny',
                'p.created_at',
                'p.updated_at',
                'n.note as note_number',
                'n.numPedido as ovi',
                'fiscal.name as fiscal_user_name',
                'o.id as order_id',
                'o.ordem as order_number',
            ])
            ->orderBy('p.id');

        if ($noteId) {
            $query->where('p.note_id', $noteId);
        }

        $total = (clone $query)->count();
        $this->info("Partials alvo: {$total}");

        if ($total === 0) {
            return 0;
        }

        $processed = 0;

        $query->chunkById($chunkSize, function (Collection $rows) use (&$processed, $dryRun) {
            $now = now();
            $noteContexts = $this->loadNoteContexts($rows->pluck('note_id')->all());
            $adsByNote = $this->loadLatestAdsByNote($rows->pluck('note_id')->all(), true);
            $upserts = [];

            foreach ($rows as $row) {
                $context = $noteContexts[$row->note_id] ?? ['service_id' => null];
                $ads = $adsByNote[$row->note_id] ?? null;
                $stage = $this->resolvePartialStage($row);

                $upserts[] = [
                    'note_id' => $row->note_id,
                    'flow_type' => 'partial',
                    'partial_id' => $row->id,
                    'work_report_id' => null,
                    'flow_key' => "partial:{$row->id}",
                    'company_id' => $row->company_id,
                    'service_id' => $context['service_id'],
                    'note_number' => $row->note_number,
                    'ovi' => $row->ovi,
                    'order_id' => $row->order_id,
                    'order_number' => $row->order_number,
                    'informed_at' => $row->created_at,
                    'inform_type' => 'partial',
                    'is_validated_by_publication' => false,
                    'publication_validated_at' => null,
                    'has_ads' => true,
                    'ads_form_id' => $ads->id ?? null,
                    'ads_sent_at' => $row->created_at,
                    'ads_type' => $ads ? ($ads->tacit ? 'tacit' : 'manual') : 'not_sent',
                    'ads_is_tacit' => (bool) ($ads->tacit ?? false),
                    'fiscalization_entered_at' => $row->decision_at,
                    'fiscalization_type' => 'partial',
                    'fiscal_assigned_at' => $row->decision_at,
                    'fiscal_user_id' => $row->supervision_id,
                    'fiscal_user_name' => $row->fiscal_user_name,
                    'fiscalization_completed_at' => $row->supervision_at,
                    'fiscalization_closed_in_sicode' => (bool) $row->complete,
                    'fiscalization_closed_in_sicode_at' => $row->complete ? ($row->payment_at ?? $row->supervision_at) : null,
                    'fiscalization_closed_in_sap' => false,
                    'fiscalization_closed_in_sap_at' => null,
                    'has_d5' => false,
                    'five_note_id' => null,
                    'five_note_number' => null,
                    'five_note_created_at' => null,
                    'measurement_entered_at' => $row->payment_at,
                    'measurement_type' => 'partial',
                    'measurement_completed_at' => $row->payment_at,
                    'measurement_exited_at' => $row->complete ? $row->payment_at : null,
                    'ads_production_id' => null,
                    'fiscalization_production_id' => null,
                    'measurement_production_id' => null,
                    'final_cycle_started_at' => null,
                    'final_cycle_ended_at' => null,
                    'current_stage' => $stage,
                    'blocking_reason' => $this->blockingReasonFromStage($stage),
                    'active' => true,
                    'source_created_at' => $row->created_at,
                    'source_updated_at' => $row->updated_at,
                    'calculated_at' => $now,
                    'resolver_payload' => json_encode([
                        'source' => 'partials',
                        'rule_version' => 1,
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!$dryRun && !empty($upserts)) {
                DB::table('note_inform_flows')->upsert(
                    $upserts,
                    ['flow_key'],
                    $this->upsertColumns()
                );
            }

            $processed += count($upserts);
            $this->line("Partial lote processado: " . count($upserts));
        }, 'p.id', 'id');

        return $processed;
    }

    private function syncFinals(int $chunkSize, ?int $noteId, bool $dryRun): int
    {
        $query = DB::table('work_reports as wr')
            ->join('notes as n', 'n.id', '=', 'wr.note_id')
            ->leftJoin('adsforms as af', 'af.work_report_id', '=', 'wr.id')
            ->leftJoin('five_notes as fn', 'fn.note_id', '=', 'wr.note_id')
            ->leftJoinSub(
                DB::table('order_work_report')->selectRaw('work_report_id, MIN(order_id) as order_id')->groupBy('work_report_id'),
                'owr1',
                'owr1.work_report_id',
                '=',
                'wr.id'
            )
            ->leftJoin('orders as o', 'o.id', '=', 'owr1.order_id')
            ->where('wr.canceled', false)
            ->select([
                'wr.id',
                'wr.note_id',
                'wr.company_id',
                'wr.informed_at',
                'wr.acceptance_accepted',
                'wr.acceptance_at',
                'wr.created_at',
                'wr.updated_at',
                'af.id as ads_form_id',
                'af.tacit',
                'af.tacit_delivered_at',
                'af.created_at as ads_created_at',
                'fn.id as five_note_id',
                'fn.note_d5 as five_note_number',
                'fn.created_at as five_note_created_at',
                'n.note as note_number',
                'n.numPedido as ovi',
                'o.id as order_id',
                'o.ordem as order_number',
            ])
            ->orderBy('wr.id');

        if ($noteId) {
            $query->where('wr.note_id', $noteId);
        }

        $total = (clone $query)->count();
        $this->info("Work reports finais alvo: {$total}");

        if ($total === 0) {
            return 0;
        }

        $processed = 0;

        $query->chunkById($chunkSize, function (Collection $rows) use (&$processed, $dryRun) {
            $now = now();
            $noteIds = $rows->pluck('note_id')->all();
            $workReportIds = $rows->pluck('id')->all();
            $noteContexts = $this->loadNoteContexts($noteIds);
            $nextCycleEnd = $this->loadNextFinalInformedAtByWorkReport($noteIds);
            [$fiscalProdByWorkReport, $paymentProdByWorkReport] = $this->loadProductionsByServiceForFinals($workReportIds);
            $sapByWorkReport = $this->loadSapOperationStatusByWorkReportsFromNotes($rows);
            $upserts = [];

            foreach ($rows as $row) {
                $context = $noteContexts[$row->note_id] ?? ['service_id' => null];
                $fiscalProd = $fiscalProdByWorkReport[$row->id] ?? null;
                $paymentProd = $paymentProdByWorkReport[$row->id] ?? null;
                $sap = $sapByWorkReport[$row->id] ?? [
                    'op30_all_conf' => false,
                    'op30_done_at' => null,
                    'op30_text' => 'Nao',
                    'op50_all_conf' => false,
                    'op50_done_at' => null,
                    'op50_text' => 'Nao',
                ];
                $stage = $this->resolveFinalStage($row);
                $informedAt = $row->informed_at ?? $row->created_at;

                $upserts[] = [
                    'note_id' => $row->note_id,
                    'flow_type' => 'final',
                    'partial_id' => null,
                    'work_report_id' => $row->id,
                    'flow_key' => "work_report:{$row->id}",
                    'company_id' => $row->company_id,
                    'service_id' => $context['service_id'],
                    'note_number' => $row->note_number,
                    'ovi' => $row->ovi,
                    'order_id' => $row->order_id,
                    'order_number' => $row->order_number,
                    'informed_at' => $informedAt,
                    'inform_type' => 'final',
                    'is_validated_by_publication' => (bool) $row->acceptance_accepted,
                    'publication_validated_at' => $row->acceptance_at,
                    'has_ads' => $row->ads_form_id !== null,
                    'ads_form_id' => $row->ads_form_id,
                    'ads_sent_at' => $row->tacit_delivered_at ?? $row->ads_created_at,
                    'ads_type' => $row->ads_form_id ? ($row->tacit ? 'tacit' : 'manual') : 'not_sent',
                    'ads_is_tacit' => (bool) $row->tacit,
                    'fiscalization_entered_at' => $fiscalProd->att_at ?? null,
                    'fiscalization_type' => 'final',
                    'fiscal_assigned_at' => $fiscalProd->att_at ?? null,
                    'fiscal_user_id' => $fiscalProd->user_id ?? null,
                    'fiscal_user_name' => $fiscalProd->user_name ?? null,
                    'fiscalization_completed_at' => $fiscalProd->completed_at ?? null,
                    'fiscalization_closed_in_sicode' => (bool) ($fiscalProd->completed ?? false),
                    'fiscalization_closed_in_sicode_at' => $fiscalProd->completed_at ?? null,
                    'fiscalization_closed_in_sap' => (bool) $sap['op30_all_conf'],
                    'fiscalization_closed_in_sap_at' => $sap['op30_done_at'],
                    'baixa_fiscal_status' => $sap['op30_text'],
                    'has_d5' => $row->five_note_id !== null,
                    'five_note_id' => $row->five_note_id,
                    'five_note_number' => $row->five_note_number,
                    'five_note_created_at' => $row->five_note_created_at,
                    'measurement_entered_at' => $paymentProd->att_at ?? null,
                    'measurement_type' => 'final',
                    'measurement_completed_at' => $sap['op50_all_conf'] ? ($sap['op50_done_at'] ?? $paymentProd->completed_at ?? null) : ($paymentProd->completed_at ?? null),
                    'measurement_exited_at' => $sap['op50_all_conf'] ? ($sap['op50_done_at'] ?? $paymentProd->confirmed_at ?? null) : ($paymentProd->confirmed_at ?? null),
                    'baixa_measurement_status' => $sap['op50_text'],
                    'ads_production_id' => $paymentProd->id ?? null,
                    'fiscalization_production_id' => $fiscalProd->id ?? null,
                    'measurement_production_id' => $paymentProd->id ?? null,
                    'final_cycle_started_at' => $informedAt,
                    'final_cycle_ended_at' => $nextCycleEnd[$row->id] ?? null,
                    'current_stage' => $stage,
                    'blocking_reason' => $this->blockingReasonFromStage($stage),
                    'active' => true,
                    'source_created_at' => $row->created_at,
                    'source_updated_at' => $row->updated_at,
                    'calculated_at' => $now,
                    'resolver_payload' => json_encode([
                        'source' => 'work_reports',
                        'rule_version' => 1,
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!$dryRun && !empty($upserts)) {
                DB::table('note_inform_flows')->upsert(
                    $upserts,
                    ['flow_key'],
                    $this->upsertColumns()
                );
            }

            $processed += count($upserts);
            $this->line("Final lote processado: " . count($upserts));
        }, 'wr.id', 'id');

        return $processed;
    }

    private function loadNoteContexts(array $noteIds): array
    {
        $rows = DB::table('productions as p')
            ->joinSub(
                DB::table('productions')
                    ->selectRaw('note_id, MAX(id) as max_id')
                    ->whereIn('note_id', $noteIds)
                    ->groupBy('note_id'),
                'lp',
                function ($join) {
                    $join->on('lp.max_id', '=', 'p.id');
                }
            )
            ->select(['p.note_id', 'p.service_id'])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->note_id] = [
                'service_id' => $row->service_id,
            ];
        }

        return $map;
    }

    private function loadLatestAdsByNote(array $noteIds, bool $partial): array
    {
        if (empty($noteIds)) {
            return [];
        }

        $rows = DB::table('adsforms as af')
            ->joinSub(
                DB::table('adsforms')
                    ->selectRaw('note_id, MAX(id) as max_id')
                    ->whereIn('note_id', $noteIds)
                    ->where('partial', $partial)
                    ->groupBy('note_id'),
                'laf',
                function ($join) {
                    $join->on('laf.max_id', '=', 'af.id');
                }
            )
            ->select([
                'af.id',
                'af.note_id',
                'af.tacit',
                'af.tacit_delivered_at',
                'af.created_at',
            ])
            ->get();

        return $rows->keyBy('note_id')->all();
    }

    private function loadNextFinalInformedAtByWorkReport(array $noteIds): array
    {
        if (empty($noteIds)) {
            return [];
        }

        $rows = DB::table('work_reports')
            ->whereIn('note_id', $noteIds)
            ->where('canceled', false)
            ->select(['id', 'note_id', 'informed_at', 'created_at'])
            ->orderBy('note_id')
            ->orderByRaw('COALESCE(informed_at, created_at)')
            ->orderBy('id')
            ->get()
            ->groupBy('note_id');

        $nextByWr = [];
        foreach ($rows as $group) {
            $list = $group->values();
            $lastIndex = $list->count() - 1;
            for ($i = 0; $i <= $lastIndex; $i++) {
                $current = $list[$i];
                $next = $i < $lastIndex ? $list[$i + 1] : null;
                $nextByWr[$current->id] = $next ? ($next->informed_at ?? $next->created_at) : null;
            }
        }

        return $nextByWr;
    }

    private function loadProductionsByServiceForFinals(array $workReportIds): array
    {
        if (empty($workReportIds)) {
            return [[], []];
        }

        $rows = DB::table('work_reports as wr')
            ->join('productions as prod', 'prod.note_id', '=', 'wr.note_id')
            ->join('services as srv', 'srv.uuid', '=', 'prod.service_id')
            ->leftJoin('users as usr', 'usr.id', '=', 'prod.user_id')
            ->whereIn('wr.id', $workReportIds)
            ->where('wr.canceled', false)
            ->whereNotNull('prod.att_at')
            ->whereRaw('prod.att_at > COALESCE(wr.informed_at, wr.created_at)')
            ->where(function ($q) {
                $q->whereNull('prod.partial')->orWhere('prod.partial', false);
            })
            ->whereIn('srv.service', ['Fiscalizacao', 'Fiscalização', 'Pagamento'])
            ->select([
                'wr.id as work_report_id',
                'prod.id',
                'srv.service',
                'prod.att_at',
                'prod.user_id',
                'usr.name as user_name',
                'prod.completed',
                'prod.completed_at',
                'prod.confirmed_at',
            ])
            ->orderBy('wr.id')
            ->orderBy('prod.att_at')
            ->orderBy('prod.id')
            ->get()
            ->groupBy('work_report_id');

        $fiscalMap = [];
        $paymentMap = [];
        foreach ($rows as $workReportId => $items) {
            $fiscal = $items->first(function ($item) {
                return in_array($item->service, ['Fiscalizacao', 'Fiscalização'], true);
            });
            $payment = $items->first(function ($item) {
                return $item->service === 'Pagamento';
            });

            if ($fiscal) {
                $fiscalMap[$workReportId] = $fiscal;
            }

            if ($payment) {
                $paymentMap[$workReportId] = $payment;
            }
        }

        return [$fiscalMap, $paymentMap];
    }

    private function upsertColumns(): array
    {
        return [
            'note_id',
            'flow_type',
            'partial_id',
            'work_report_id',
            'company_id',
            'service_id',
            'note_number',
            'ovi',
            'order_id',
            'order_number',
            'informed_at',
            'inform_type',
            'is_validated_by_publication',
            'publication_validated_at',
            'has_ads',
            'ads_form_id',
            'ads_sent_at',
            'ads_type',
            'ads_is_tacit',
            'fiscalization_entered_at',
            'fiscalization_type',
            'fiscal_assigned_at',
            'fiscal_user_id',
            'fiscal_user_name',
            'fiscalization_completed_at',
            'fiscalization_closed_in_sicode',
            'fiscalization_closed_in_sicode_at',
            'fiscalization_closed_in_sap',
            'fiscalization_closed_in_sap_at',
            'baixa_fiscal_status',
            'has_d5',
            'five_note_id',
            'five_note_number',
            'five_note_created_at',
            'measurement_entered_at',
            'measurement_type',
            'measurement_completed_at',
            'measurement_exited_at',
            'baixa_measurement_status',
            'ads_production_id',
            'fiscalization_production_id',
            'measurement_production_id',
            'final_cycle_started_at',
            'final_cycle_ended_at',
            'current_stage',
            'blocking_reason',
            'active',
            'source_created_at',
            'source_updated_at',
            'calculated_at',
            'resolver_payload',
            'updated_at',
        ];
    }

    private function normalizeFlowType(?string $flowType): ?string
    {
        if ($flowType === null || trim($flowType) === '') {
            return null;
        }

        $normalized = strtolower(trim($flowType));
        if (in_array($normalized, ['partial', 'final'], true)) {
            return $normalized;
        }

        return null;
    }

    private function resolvePartialStage(object $row): string
    {
        if (!$row->decision_at) {
            return 'waiting_fiscalization_entry';
        }

        if ((bool) $row->deny && $row->supervision_at) {
            return 'rejected_fiscalization';
        }

        if (!$row->payment_at) {
            return 'waiting_measurement_entry';
        }

        if (!(bool) $row->complete) {
            return 'waiting_measurement_exit';
        }

        return 'completed';
    }

    private function resolveFinalStage(object $row): string
    {
        if (!$row->ads_form_id) {
            return 'waiting_ads';
        }

        if (!$row->five_note_id) {
            return 'waiting_d5';
        }

        return 'completed';
    }

    private function blockingReasonFromStage(string $stage): ?string
    {
        return match ($stage) {
            'waiting_ads' => 'ADS ainda nao registrada.',
            'waiting_fiscalization_entry' => 'Fluxo ainda nao entrou em fiscalizacao.',
            'rejected_fiscalization' => 'Fiscalizacao rejeitada.',
            'waiting_measurement_entry' => 'Fluxo ainda nao entrou em medicao/pagamento.',
            'waiting_measurement_exit' => 'Fluxo entrou em medicao, mas ainda nao foi finalizado.',
            'waiting_d5' => 'D5/FiveNote ainda nao vinculada.',
            default => null,
        };
    }

    private function loadSapOperationStatusByWorkReportsFromNotes(Collection $finalRows): array
    {
        if ($finalRows->isEmpty()) {
            return [];
        }

        $workReportToNote = $finalRows
            ->pluck('note_id', 'id')
            ->map(fn ($noteId) => (int) $noteId)
            ->all();

        $noteIds = array_values(array_unique(array_values($workReportToNote)));

        $rows = DB::table('orders as o')
            ->leftJoin('operations as op', 'op.order_id', '=', 'o.id')
            ->whereIn('o.note_id', $noteIds)
            ->select([
                'o.note_id',
                'o.id as order_id',
                DB::raw("MAX(CASE WHEN op.operacao = '0030' AND op.status LIKE 'CONF%' THEN 1 ELSE 0 END) as has_30_conf"),
                DB::raw("MAX(CASE WHEN op.operacao = '0050' AND op.status LIKE 'CONF%' THEN 1 ELSE 0 END) as has_50_conf"),
                DB::raw("MAX(CASE WHEN op.operacao = '0030' AND op.status LIKE 'CNPA%' THEN 1 ELSE 0 END) as has_30_cnpa"),
                DB::raw("MAX(CASE WHEN op.operacao = '0050' AND op.status LIKE 'CNPA%' THEN 1 ELSE 0 END) as has_50_cnpa"),
                DB::raw("MAX(CASE WHEN op.operacao = '0030' AND op.status LIKE 'CONF%' THEN op.fimReal ELSE NULL END) as op30_done_at"),
                DB::raw("MAX(CASE WHEN op.operacao = '0050' AND op.status LIKE 'CONF%' THEN op.fimReal ELSE NULL END) as op50_done_at"),
            ])
            ->groupBy('o.note_id', 'o.id')
            ->get()
            ->groupBy('note_id');

        $map = [];

        $noteResult = [];
        foreach ($rows as $noteId => $items) {
            $ordersCount = $items->count();
            $orders30Conf = $items->where('has_30_conf', 1)->count();
            $orders50Conf = $items->where('has_50_conf', 1)->count();
            $orders30Cnpa = $items->where('has_30_cnpa', 1)->count();
            $orders50Cnpa = $items->where('has_50_cnpa', 1)->count();

            $op30AllConf = $ordersCount > 0 && $orders30Conf === $ordersCount;
            $op50AllConf = $ordersCount > 0 && $orders50Conf === $ordersCount;

            $op30DoneAt = $op30AllConf
                ? $items->pluck('op30_done_at')->filter()->max()
                : null;

            $op50DoneAt = $op50AllConf
                ? $items->pluck('op50_done_at')->filter()->max()
                : null;

            $op30AnyConf = $orders30Conf > 0;
            $op50AnyConf = $orders50Conf > 0;
            $op30AnyCnpa = $orders30Cnpa > 0;
            $op50AnyCnpa = $orders50Cnpa > 0;

            $op30Text = $this->resolveBaixaTextStatus($op30AllConf, $op30AnyConf, $op30AnyCnpa);
            $op50Text = $this->resolveBaixaTextStatus($op50AllConf, $op50AnyConf, $op50AnyCnpa);

            $noteResult[(int) $noteId] = [
                'op30_all_conf' => $op30AllConf,
                'op30_done_at' => $op30DoneAt,
                'op30_text' => $op30Text,
                'op50_all_conf' => $op50AllConf,
                'op50_done_at' => $op50DoneAt,
                'op50_text' => $op50Text,
            ];
        }

        foreach ($workReportToNote as $workReportId => $noteId) {
            $map[(int) $workReportId] = $noteResult[$noteId] ?? [
                'op30_all_conf' => false,
                'op30_done_at' => null,
                'op30_text' => 'Nao',
                'op50_all_conf' => false,
                'op50_done_at' => null,
                'op50_text' => 'Nao',
            ];
        }

        return $map;
    }

    private function resolveBaixaTextStatus(bool $allConf, bool $anyConf, bool $anyCnpa): string
    {
        if ($allConf) {
            return 'Baixado';
        }

        if ($anyConf && $anyCnpa) {
            return 'Divergente';
        }

        if ($anyCnpa) {
            return 'Parcial';
        }

        return 'Nao';
    }
}
