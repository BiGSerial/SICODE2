<?php

namespace App\Console\Commands\Tools;

use App\Models\{FiveNote, Partial, WorkReportFlowProduction};
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Read-only diagnostic for the legacy Payment production model.
 *
 * The current schema does not persist a single, mandatory measurement context.
 * This command deliberately reports that ambiguity; it never infers or repairs
 * a relationship.
 */
class AuditPaymentProductionIntegrity extends Command
{
    protected $signature = 'sicode:audit-payment-production-integrity
                            {--json : Emit the findings as JSON}
                            {--limit=200 : Maximum rows shown for each finding}';

    protected $description = 'Audita, sem alterar dados, a integridade de Productions de Medição/Pagamento';

    public function handle(): int
    {
        $paymentServiceIds = $this->paymentServiceIds();

        if ($paymentServiceIds === []) {
            $this->warn('Nenhum serviço de Pagamento/Medição foi encontrado; nenhuma Production foi auditada.');

            return self::SUCCESS;
        }

        $findings = [
            'missing_work_report_context'     => $this->missingWorkReportContext($paymentServiceIds),
            'partial_context_cardinality'     => $this->partialContextCardinality($paymentServiceIds),
            'dfive_context_cardinality'       => $this->d5ContextCardinality($paymentServiceIds),
            'partial_note_mismatch'           => $this->partialNoteMismatch($paymentServiceIds),
            'work_report_note_mismatch'       => $this->workReportNoteMismatch($paymentServiceIds),
            'multiple_work_report_links'      => $this->multipleWorkReportLinks($paymentServiceIds),
            'd5_note_or_work_report_mismatch' => $this->d5Mismatch($paymentServiceIds),
            'duplicate_open_partial'          => $this->duplicateOpenPartial($paymentServiceIds),
            'duplicate_open_d5'               => $this->duplicateOpenD5($paymentServiceIds),
            'duplicate_historical_cycle_1'    => $this->duplicateHistoricalCycleOne($paymentServiceIds),
        ];

        $result = [
            'read_only'           => true,
            'payment_service_ids' => $paymentServiceIds,
            'open_definition'     => 'completed = false AND confirmed = false (convenção atualmente usada pelas filas; revisar antes de saneamento)',
            'findings'            => $findings,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('AUDITORIA SOMENTE LEITURA — nenhuma linha foi alterada.');
        $this->line('Serviços auditados: ' . implode(', ', $paymentServiceIds));
        $this->line('Production aberta: ' . $result['open_definition']);

        foreach ($findings as $name => $finding) {
            $this->newLine();
            $this->comment(Str::headline($name) . ': ' . $finding['total']);

            if ($finding['rows'] !== []) {
                $this->table(array_keys($finding['rows'][0]), $finding['rows']);
            }

            if ($finding['truncated']) {
                $this->warn('Resultado limitado a ' . $this->limit() . ' linhas; use --limit para ampliar.');
            }
        }

        return self::SUCCESS;
    }

    /** @return array<int, string> */
    private function paymentServiceIds(): array
    {
        return DB::table('services')
            ->select(['uuid', 'service'])
            ->get()
            ->filter(fn (object $service) => in_array(
                Str::lower(trim(Str::ascii((string) $service->service))),
                ['pagamento', 'medicao'],
                true,
            ))
            ->pluck('uuid')
            ->values()
            ->all();
    }

    private function missingWorkReportContext(array $serviceIds): array
    {
        return $this->finding(
            $this->paymentProductions($serviceIds, 'p')
                ->where(fn (Builder $query) => $query->whereNull('p.partial')->orWhere('p.partial', false))
                ->whereNotExists(function (Builder $query) {
                    $query->selectRaw('1')
                        ->from('work_report_flow_productions as wrfp')
                        ->whereColumn('wrfp.production_id', 'p.id')
                        ->where('wrfp.stage', WorkReportFlowProduction::STAGE_PAYMENT);
                })
                ->select(['p.id as production_id', 'p.note_id', 'p.user_id', 'p.status', 'p.completed', 'p.confirmed', 'p.dfive', 'p.created_at']),
        );
    }

    private function partialContextCardinality(array $serviceIds): array
    {
        return $this->finding(
            $this->paymentProductions($serviceIds, 'p')
                ->where('p.partial', true)
                ->leftJoin('productionables as pa', function ($join) {
                    $join->on('pa.production_id', '=', 'p.id')
                        ->where('pa.productionable_type', Partial::class);
                })
                ->groupBy(['p.id', 'p.note_id', 'p.user_id', 'p.status', 'p.completed', 'p.confirmed', 'p.created_at'])
                ->havingRaw('COUNT(pa.id) <> 1')
                ->selectRaw('p.id as production_id, p.note_id, p.user_id, p.status, p.completed, p.confirmed, p.created_at, COUNT(pa.id) as partial_pivot_count'),
        );
    }

    private function d5ContextCardinality(array $serviceIds): array
    {
        return $this->finding(
            $this->paymentProductions($serviceIds, 'p')
                ->where('p.dfive', true)
                ->leftJoin('productionables as pa', function ($join) {
                    $join->on('pa.production_id', '=', 'p.id')
                        ->where('pa.productionable_type', FiveNote::class);
                })
                ->groupBy(['p.id', 'p.note_id', 'p.user_id', 'p.status', 'p.completed', 'p.confirmed', 'p.created_at'])
                ->havingRaw('COUNT(pa.id) <> 1')
                ->selectRaw('p.id as production_id, p.note_id, p.user_id, p.status, p.completed, p.confirmed, p.created_at, COUNT(pa.id) as d5_pivot_count'),
        );
    }

    private function partialNoteMismatch(array $serviceIds): array
    {
        return $this->finding(
            $this->paymentProductions($serviceIds, 'p')
                ->join('productionables as pa', function ($join) {
                    $join->on('pa.production_id', '=', 'p.id')
                        ->where('pa.productionable_type', Partial::class);
                })
                ->join('partials as partials', 'partials.id', '=', 'pa.productionable_id')
                ->whereColumn('partials.note_id', '<>', 'p.note_id')
                ->select(['p.id as production_id', 'p.note_id as production_note_id', 'partials.id as partial_id', 'partials.note_id as partial_note_id', 'p.created_at']),
        );
    }

    private function workReportNoteMismatch(array $serviceIds): array
    {
        return $this->finding(
            $this->paymentProductions($serviceIds, 'p')
                ->join('work_report_flow_productions as wrfp', function ($join) {
                    $join->on('wrfp.production_id', '=', 'p.id')
                        ->where('wrfp.stage', WorkReportFlowProduction::STAGE_PAYMENT);
                })
                ->join('work_reports as wr', 'wr.id', '=', 'wrfp.work_report_id')
                ->whereColumn('wr.note_id', '<>', 'p.note_id')
                ->select(['p.id as production_id', 'p.note_id as production_note_id', 'wr.id as work_report_id', 'wr.note_id as work_report_note_id', 'wrfp.source', 'p.created_at']),
        );
    }

    private function multipleWorkReportLinks(array $serviceIds): array
    {
        $groups = $this->paymentProductions($serviceIds, 'p')
            ->join('work_report_flow_productions as wrfp', function ($join) {
                $join->on('wrfp.production_id', '=', 'p.id')
                    ->where('wrfp.stage', WorkReportFlowProduction::STAGE_PAYMENT);
            })
            ->groupBy('p.id')
            ->havingRaw('COUNT(DISTINCT wrfp.work_report_id) > 1')
            ->selectRaw('p.id as production_id, COUNT(DISTINCT wrfp.work_report_id) as work_report_count');

        return $this->groupFinding($groups, 'production_id', 'work_report_ids', $serviceIds);
    }

    private function d5Mismatch(array $serviceIds): array
    {
        return $this->finding(
            $this->paymentProductions($serviceIds, 'p')
                ->join('productionables as pa', function ($join) {
                    $join->on('pa.production_id', '=', 'p.id')
                        ->where('pa.productionable_type', FiveNote::class);
                })
                ->join('five_notes as fn', 'fn.id', '=', 'pa.productionable_id')
                ->leftJoin('work_report_flow_productions as wrfp', function ($join) {
                    $join->on('wrfp.production_id', '=', 'p.id')
                        ->where('wrfp.stage', WorkReportFlowProduction::STAGE_PAYMENT);
                })
                ->where(function (Builder $query) {
                    $query->whereColumn('fn.note_id', '<>', 'p.note_id')
                        ->orWhere(function (Builder $query) {
                            $query->whereNotNull('fn.work_report_id')
                                ->whereNotNull('wrfp.work_report_id')
                                ->whereColumn('fn.work_report_id', '<>', 'wrfp.work_report_id');
                        });
                })
                ->select(['p.id as production_id', 'p.note_id as production_note_id', 'fn.id as five_note_id', 'fn.note_id as five_note_note_id', 'fn.work_report_id as five_note_work_report_id', 'wrfp.work_report_id as production_work_report_id', 'p.created_at']),
        );
    }

    private function duplicateOpenPartial(array $serviceIds): array
    {
        $groups = DB::table('productions as p')
            ->join('productionables as pa', function ($join) {
                $join->on('pa.production_id', '=', 'p.id')
                    ->where('pa.productionable_type', Partial::class);
            })
            ->whereIn('p.service_id', $serviceIds)
            ->where('p.partial', true)
            ->where('p.completed', false)
            ->where('p.confirmed', false)
            ->groupBy('pa.productionable_id')
            ->havingRaw('COUNT(*) > 1')
            ->selectRaw('pa.productionable_id as partial_id, COUNT(*) as production_count');

        return $this->groupFinding($groups, 'partial_id', 'payment_production_ids', $serviceIds, true);
    }

    private function duplicateOpenD5(array $serviceIds): array
    {
        $groups = DB::table('productions as p')
            ->join('productionables as pa', function ($join) {
                $join->on('pa.production_id', '=', 'p.id')
                    ->where('pa.productionable_type', FiveNote::class);
            })
            ->whereIn('p.service_id', $serviceIds)
            ->where('p.dfive', true)
            ->where('p.completed', false)
            ->where('p.confirmed', false)
            ->groupBy('pa.productionable_id')
            ->havingRaw('COUNT(*) > 1')
            ->selectRaw('pa.productionable_id as five_note_id, COUNT(*) as production_count');

        return $this->groupFinding($groups, 'five_note_id', 'payment_production_ids', $serviceIds, true);
    }

    private function duplicateHistoricalCycleOne(array $serviceIds): array
    {
        $groups = DB::table('productions as p')
            ->join('work_report_flow_productions as wrfp', function ($join) {
                $join->on('wrfp.production_id', '=', 'p.id')
                    ->where('wrfp.stage', WorkReportFlowProduction::STAGE_PAYMENT);
            })
            ->whereIn('p.service_id', $serviceIds)
            ->where(fn (Builder $query) => $query->whereNull('p.partial')->orWhere('p.partial', false))
            ->groupBy('wrfp.work_report_id')
            ->havingRaw('COUNT(DISTINCT p.id) > 1')
            ->selectRaw('wrfp.work_report_id, COUNT(DISTINCT p.id) as production_count');

        return $this->groupFinding($groups, 'work_report_id', 'payment_production_ids', $serviceIds);
    }

    private function paymentProductions(array $serviceIds, string $alias): Builder
    {
        return DB::table("productions as {$alias}")->whereIn("{$alias}.service_id", $serviceIds);
    }

    private function finding(Builder $query): array
    {
        $total = DB::query()->fromSub($query, 'audit_finding')->count();
        $rows  = (clone $query)->limit($this->limit())->get()->map(fn (object $row) => (array) $row)->all();

        return ['total' => $total, 'truncated' => $total > count($rows), 'rows' => $rows];
    }

    private function groupFinding(Builder $groups, string $idColumn, string $idsColumn, array $serviceIds, bool $onlyOpen = false): array
    {
        $total         = DB::query()->fromSub($groups, 'audit_groups')->count();
        $groups        = (clone $groups)->limit($this->limit())->get();
        $ids           = $groups->pluck($idColumn)->all();
        $productionIds = [];

        if ($ids !== []) {
            // Fetch IDs separately to remain portable across MySQL and SQLite (no GROUP_CONCAT dependency).
            $pivotType = $idColumn === 'partial_id' ? Partial::class : ($idColumn === 'five_note_id' ? FiveNote::class : null);
            $query     = DB::table('productions as p')->whereIn('p.service_id', $serviceIds);

            if ($onlyOpen) {
                $query->where('p.completed', false)->where('p.confirmed', false);
            }

            if ($pivotType) {
                $query->join('productionables as pa', function ($join) use ($pivotType) {
                    $join->on('pa.production_id', '=', 'p.id')->where('pa.productionable_type', $pivotType);
                })->whereIn('pa.productionable_id', $ids)->select(['pa.productionable_id as context_id', 'p.id as production_id']);
            } elseif ($idColumn === 'work_report_id') {
                $query->join('work_report_flow_productions as wrfp', function ($join) {
                    $join->on('wrfp.production_id', '=', 'p.id')->where('wrfp.stage', WorkReportFlowProduction::STAGE_PAYMENT);
                })->whereIn('wrfp.work_report_id', $ids)->select(['wrfp.work_report_id as context_id', 'p.id as production_id']);
            } else {
                $query->join('work_report_flow_productions as wrfp', function ($join) {
                    $join->on('wrfp.production_id', '=', 'p.id')->where('wrfp.stage', WorkReportFlowProduction::STAGE_PAYMENT);
                })->whereIn('p.id', $ids)->select(['p.id as context_id', 'wrfp.work_report_id']);
            }

            foreach ($query->get() as $row) {
                $productionIds[$row->context_id][] = $row->production_id ?? $row->work_report_id;
            }
        }

        $rows = $groups->map(function (object $group) use ($idColumn, $idsColumn, $productionIds) {
            $row             = (array) $group;
            $row[$idsColumn] = $productionIds[$group->{$idColumn}] ?? [];

            return $row;
        })->all();

        return ['total' => $total, 'truncated' => $total > count($rows), 'rows' => $rows];
    }

    private function limit(): int
    {
        return max(1, (int) $this->option('limit'));
    }
}
