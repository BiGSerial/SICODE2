<?php

namespace App\Console\Commands\Orders;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\Edp_depc\BaseCosts;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class FillOrderServiceCost extends Command
{
    use ShowsProgress;

    protected $signature = 'orders:fill-service-cost
        {--cutoff= : Data de corte (YYYY-MM-DD) usando created_at das fontes}
        {--order= : Número da ordem para checagem pontual}
        {--include-partials : Inclui ordens vinculadas em order_partial/partials}
        {--only-viability : Considera apenas ordens vinculadas à viabilidade}
        {--check : Não atualiza; mostra apenas contagens de elegíveis e atualizáveis}
        {--check-only : Apenas consulta e exibe o custo calculado}
        {--dry : Simula sem gravar}
        {--recalculate : Recalcula também ordens com service_cost preenchido}
        {--write-zero : Permite gravar 0.00 quando não houver custo encontrado}
        {--chunk=500 : Tamanho do lote}';

    protected $description = 'Preenche orders.service_cost para ordens sem custo com vínculo em Viabilidade/WorkReport (e opcionalmente Partial), com corte por created_at.';

    public function handle(): int
    {
        $registro = null;

        try {
            $dryRun = (bool) $this->option('dry');
            $checkOnly = (bool) $this->option('check-only');
            $check = (bool) $this->option('check');
            $checkMode = $check || $checkOnly;
            $includePartials = (bool) $this->option('include-partials');
            $onlyViability = (bool) $this->option('only-viability');
            $recalculate = (bool) $this->option('recalculate');
            $writeZero = (bool) $this->option('write-zero');
            $chunkSize = max(100, (int) $this->option('chunk'));
            $orderNumber = trim((string) ($this->option('order') ?? ''));
            $cutoffInput = $this->option('cutoff');
            $cutoff = $cutoffInput ? Carbon::parse((string) $cutoffInput)->endOfDay() : null;

            if ($checkMode) {
                $dryRun = true;
            }

            $registro = new RegistroJson('orders:fill-service-cost', [
                'cutoff' => $cutoff ? $cutoff->toDateString() : null,
                'order' => $orderNumber !== '' ? $orderNumber : null,
                'include_partials' => $includePartials,
                'only_viability' => $onlyViability,
                'check' => $check,
                'check_only' => $checkOnly,
                'dry' => $dryRun,
                'recalculate' => $recalculate,
                'write_zero' => $writeZero,
                'chunk' => $chunkSize,
            ]);

            if ($checkMode && $orderNumber !== '') {
                return $this->checkSingleOrder($orderNumber, $cutoff, $includePartials, $onlyViability, $registro);
            }

            $query = $this->buildTargetOrdersQuery($cutoff, $orderNumber, $recalculate, $includePartials, $onlyViability);

            $total = (clone $query)->count();
            $this->info("Ordens alvo: {$total}");
            $registro?->setTotal($total);

            if ($total === 0) {
                $registro?->setUpdated(0);
                $registro?->save();
                $this->info('Nada para atualizar.');
                if ($orderNumber !== '') {
                    $this->warn("Ordem {$orderNumber} não encontrada no recorte informado.");
                }
                return self::SUCCESS;
            }

            if ($checkMode && $orderNumber === '') {
                [$updatableCount, $withoutCostCount] = $this->countUpdatableOrders($query, $chunkSize);

                $this->info('Checagem concluída.');
                $this->line("Ordens elegíveis: {$total}");
                $this->line("Ordens atualizáveis (com custo encontrado): {$updatableCount}");
                $this->line("Ordens sem custo encontrado: {$withoutCostCount}");

                $registro?->setUpdated($updatableCount);
                $registro?->setCreated($withoutCostCount);
                $registro?->save();

                return self::SUCCESS;
            }

            $cache = [];
            $processed = 0;
            $updated = 0;
            $withoutCost = 0;
            $skippedNoCost = 0;

            $bar = $this->createProgressBar($total);
            $bar->start();

            $query->chunkById($chunkSize, function ($rows) use (
                &$cache,
                &$processed,
                &$updated,
                &$withoutCost,
                &$skippedNoCost,
                $dryRun,
                $writeZero,
                $bar
            ) {
                $orderNumbers = $rows->pluck('ordem')->filter()->map(fn ($v) => trim((string) $v))->unique()->values()->all();
                $missing = array_values(array_diff($orderNumbers, array_keys($cache)));

                if (!empty($missing)) {
                    $loadedCosts = $this->baseCostsByOrderNumbers($missing);
                    foreach ($missing as $ordem) {
                        $cache[$ordem] = (float) ($loadedCosts[$ordem] ?? 0.0);
                    }
                }

                $pendingUpdates = [];

                foreach ($rows as $row) {
                    $ordem = trim((string) $row->ordem);
                    $cost = (float) ($cache[$ordem] ?? 0.0);

                    if ($cost <= 0) {
                        $withoutCost++;
                        if (!$writeZero) {
                            $skippedNoCost++;
                            $processed++;
                            $bar->advance();
                            continue;
                        }
                    }

                    $pendingUpdates[(int) $row->id] = $cost;

                    $processed++;
                    $bar->advance();
                }

                if (!$dryRun && !empty($pendingUpdates)) {
                    $updated += $this->bulkUpdateServiceCost($pendingUpdates);
                }
            }, 'o.id', 'id');

            $bar->finish();
            $this->newLine(2);

            $this->info('Execução concluída.');
            $this->line('Modo: ' . ($dryRun ? 'DRY RUN (sem gravação)' : 'ATUALIZAÇÃO REAL'));
            $this->line('Ordem: ' . ($orderNumber !== '' ? $orderNumber : 'todas'));
            $this->line('Corte (created_at): ' . ($cutoff ? $cutoff->format('Y-m-d') : 'sem corte'));
            $this->line('Escopo: ' . ($onlyViability ? 'SOMENTE VIABILIDADE' : 'VIABILIDADE OU WORKREPORT'));
            $this->line('Inclui partials: ' . ($includePartials ? 'SIM' : 'NÃO'));
            $this->line('Modo de cálculo: ' . ($recalculate ? 'RECALCULATE (atualiza todos)' : 'DEFAULT (apenas service_cost nulo)'));
            $this->line('Política para custo ausente: ' . ($writeZero ? 'GRAVA 0.00' : 'NÃO ATUALIZA (preserva valor atual)'));
            $this->line("Ordens processadas: {$processed}");
            $this->line("Ordens atualizadas: {$updated}");
            $this->line("Ordens sem custo encontrado (0.00): {$withoutCost}");
            $this->line("Ordens sem custo ignoradas: {$skippedNoCost}");

            $registro?->setUpdated($updated);
            $registro?->setCreated($skippedNoCost);
            $registro?->save();

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $registro?->setErrorMessage($e->getMessage());
            $registro?->fail($e->getMessage());
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function checkSingleOrder(string $orderNumber, ?Carbon $cutoff, bool $includePartials, bool $onlyViability, ?RegistroJson $registro): int
    {
        $order = DB::table('orders')
            ->select(['id', 'ordem', 'service_cost'])
            ->where('ordem', $orderNumber)
            ->first();

        $calculated = (float) ($this->baseCostsByOrderNumbers([$orderNumber])[$orderNumber] ?? 0.0);

        $inWorkReportScope = false;
        $inViabilityScope = false;
        $inPartialScope = false;
        if ($order) {
            if (!$onlyViability) {
                $inWorkReportScope = DB::table('order_work_report as owr')
                    ->join('work_reports as wr', 'wr.id', '=', 'owr.work_report_id')
                    ->where('owr.order_id', $order->id)
                    ->when($cutoff, fn ($q) => $q->where('wr.created_at', '<=', $cutoff))
                    ->exists();
            }

            $inViabilityScope = DB::table('order_viability as ov')
                ->join('viabilities as v', 'v.id', '=', 'ov.viability_id')
                ->where('ov.order_id', $order->id)
                ->when($cutoff, function ($q) use ($cutoff) {
                    $q->where('v.created_at', '<=', $cutoff);
                })
                ->exists();

            if ($includePartials) {
                $inPartialScope = DB::table('order_partial as op')
                    ->join('partials as p', 'p.id', '=', 'op.partial_id')
                    ->where('op.order_id', $order->id)
                    ->when($cutoff, fn ($q) => $q->where('p.created_at', '<=', $cutoff))
                    ->exists();
            }
        }

        $this->info("Cheque da ordem {$orderNumber}:");
        $this->line('Encontrada em orders: ' . ($order ? 'SIM' : 'NÃO'));
        $this->line('No recorte de WorkReports: ' . ($onlyViability ? 'NÃO APLICADO' : ($inWorkReportScope ? 'SIM' : 'NÃO')));
        $this->line('No recorte de Viabilidade: ' . ($inViabilityScope ? 'SIM' : 'NÃO'));
        $this->line('No recorte de Partials: ' . ($includePartials ? ($inPartialScope ? 'SIM' : 'NÃO') : 'NÃO APLICADO'));
        $this->line('service_cost atual: ' . ($order ? (string) $order->service_cost : 'NULL'));
        $this->line('custo calculado (base_costs): ' . number_format($calculated, 5, '.', ''));

        $registro?->setTotal(1);
        $registro?->setUpdated(0);
        $registro?->save();

        return self::SUCCESS;
    }

    private function buildTargetOrdersQuery(?Carbon $cutoff, string $orderNumber, bool $recalculate, bool $includePartials, bool $onlyViability): Builder
    {
        return DB::table('orders as o')
            ->select(['o.id', 'o.ordem', 'o.service_cost'])
            ->when(!$recalculate, function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNull('o.service_cost')
                        ->orWhere('o.service_cost', '=', 0);
                });
            })
            ->when($orderNumber !== '', fn ($q) => $q->where('o.ordem', $orderNumber))
            ->where(function ($scopeQuery) use ($cutoff, $includePartials, $onlyViability) {
                if ($onlyViability) {
                    $scopeQuery->whereExists(function ($sub) use ($cutoff) {
                        $sub->select(DB::raw(1))
                            ->from('order_viability as ov')
                            ->join('viabilities as v', 'v.id', '=', 'ov.viability_id')
                            ->whereColumn('ov.order_id', 'o.id');

                        if ($cutoff) {
                            $sub->where('v.created_at', '<=', $cutoff);
                        }
                    });
                } else {
                    $scopeQuery->whereExists(function ($sub) use ($cutoff) {
                        $sub->select(DB::raw(1))
                            ->from('order_work_report as owr')
                            ->join('work_reports as wr', 'wr.id', '=', 'owr.work_report_id')
                            ->whereColumn('owr.order_id', 'o.id');

                        if ($cutoff) {
                            $sub->where('wr.created_at', '<=', $cutoff);
                        }
                    })->orWhereExists(function ($sub) use ($cutoff) {
                        $sub->select(DB::raw(1))
                            ->from('order_viability as ov')
                            ->join('viabilities as v', 'v.id', '=', 'ov.viability_id')
                            ->whereColumn('ov.order_id', 'o.id');

                        if ($cutoff) {
                            $sub->where('v.created_at', '<=', $cutoff);
                        }
                    });
                }

                if ($includePartials) {
                    $scopeQuery->orWhereExists(function ($sub) use ($cutoff) {
                        $sub->select(DB::raw(1))
                            ->from('order_partial as op')
                            ->join('partials as p', 'p.id', '=', 'op.partial_id')
                            ->whereColumn('op.order_id', 'o.id');

                        if ($cutoff) {
                            $sub->where('p.created_at', '<=', $cutoff);
                        }
                    });
                }
            })
            ->orderBy('o.id');
    }

    private function baseCostsByOrderNumbers(array $orderNumbers): array
    {
        $normalized = collect($orderNumbers)
            ->map(fn ($ordem) => trim((string) $ordem))
            ->filter(fn ($ordem) => $ordem !== '')
            ->unique()
            ->values()
            ->all();

        if (empty($normalized)) {
            return [];
        }

        return BaseCosts::query()
            ->whereIn('ordem', $normalized)
            ->select('ordem', DB::raw('SUM(qtdNecessaria * preco) as base_cost'))
            ->groupBy('ordem')
            ->pluck('base_cost', 'ordem')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    private function countUpdatableOrders(Builder $query, int $chunkSize): array
    {
        $cache = [];
        $updatable = 0;
        $withoutCost = 0;

        $query->chunkById($chunkSize, function ($rows) use (&$cache, &$updatable, &$withoutCost) {
            $orderNumbers = $rows->pluck('ordem')->filter()->map(fn ($v) => trim((string) $v))->unique()->values()->all();
            $missing = array_values(array_diff($orderNumbers, array_keys($cache)));

            if (!empty($missing)) {
                $loadedCosts = $this->baseCostsByOrderNumbers($missing);
                foreach ($missing as $ordem) {
                    $cache[$ordem] = (float) ($loadedCosts[$ordem] ?? 0.0);
                }
            }

            foreach ($rows as $row) {
                $ordem = trim((string) $row->ordem);
                $cost = (float) ($cache[$ordem] ?? 0.0);
                if ($cost > 0) {
                    $updatable++;
                } else {
                    $withoutCost++;
                }
            }
        }, 'o.id', 'id');

        return [$updatable, $withoutCost];
    }

    private function bulkUpdateServiceCost(array $pendingUpdates): int
    {
        if (empty($pendingUpdates)) {
            return 0;
        }

        $orderIds = array_map('intval', array_keys($pendingUpdates));

        $eligibleIds = DB::table('orders')
            ->whereIn('id', $orderIds)
            ->where(function ($q) {
                $q->whereNull('service_cost')
                    ->orWhere('service_cost', 0);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($eligibleIds)) {
            return 0;
        }

        $eligibleLookup = array_flip($eligibleIds);
        $rows = [];
        $now = now();

        foreach ($pendingUpdates as $id => $cost) {
            $id = (int) $id;
            if (!isset($eligibleLookup[$id])) {
                continue;
            }

            $rows[] = [
                'id' => $id,
                'service_cost' => (float) $cost,
                'updated_at' => $now,
            ];
        }

        if (empty($rows)) {
            return 0;
        }

        DB::table('orders')->upsert($rows, ['id'], ['service_cost', 'updated_at']);

        return count($rows);
    }
}
