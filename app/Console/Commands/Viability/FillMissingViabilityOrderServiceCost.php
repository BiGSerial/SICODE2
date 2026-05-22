<?php

namespace App\Console\Commands\Viability;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Edp_depc\BaseCosts;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class FillMissingViabilityOrderServiceCost extends Command
{
    use ShowsProgress;

    protected $signature = 'viability:fill-order-service-cost
        {--dry : Simula a execução sem gravar no banco}
        {--all : Atualiza todas as ordens vinculadas a viabilidade, mesmo com valor preenchido}
        {--date-from= : Data inicial (YYYY-MM-DD) com base em viabilities.sended_at}
        {--date-to= : Data final (YYYY-MM-DD) com base em viabilities.sended_at}
        {--chunk=500 : Tamanho do lote para processamento}';

    protected $description = 'Retrofit de orders.service_cost para ordens vinculadas em order_viability (base MOP).';

    public function handle(): int
    {
        try {
            $startedAt = now();
            $startedAtMicro = microtime(true);

            $dryRun = (bool) $this->option('dry');
            $updateAll = (bool) $this->option('all');
            $chunkSize = max(100, (int) $this->option('chunk'));
            $dateFromInput = $this->option('date-from');
            $dateToInput = $this->option('date-to');
            $dateFrom = $dateFromInput ? Carbon::parse((string) $dateFromInput)->startOfDay() : null;
            $dateTo = $dateToInput ? Carbon::parse((string) $dateToInput)->endOfDay() : null;

            if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
                $this->error('Período inválido: --date-from maior que --date-to.');
                return self::FAILURE;
            }

            $query = DB::table('orders as o')
                ->select(['o.id', 'o.ordem', 'o.service_cost'])
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('order_viability as ov')
                        ->join('viabilities as v', 'v.id', '=', 'ov.viability_id')
                        ->whereColumn('ov.order_id', 'o.id');
                })
                ->when(!$updateAll, function ($q) {
                    $q->whereNull('o.service_cost');
                })
                ->when($dateFrom || $dateTo, function ($q) use ($dateFrom, $dateTo) {
                    $q->whereExists(function ($sub) use ($dateFrom, $dateTo) {
                        $sub->select(DB::raw(1))
                            ->from('order_viability as ovf')
                            ->join('viabilities as vf', 'vf.id', '=', 'ovf.viability_id')
                            ->whereColumn('ovf.order_id', 'o.id')
                            ->whereNotNull('vf.sended_at');

                        if ($dateFrom) {
                            $sub->where('vf.sended_at', '>=', $dateFrom);
                        }

                        if ($dateTo) {
                            $sub->where('vf.sended_at', '<=', $dateTo);
                        }
                    });
                })
                ->orderBy('o.id');

            $total = (clone $query)->count();
            $this->info('Ordens alvo (vinculadas a viabilidade): ' . $total);

            if ($total === 0) {
                $this->info('Nada para atualizar.');
                return self::SUCCESS;
            }

            $cache = [];
            $processed = 0;
            $updated = 0;
            $withoutCost = 0;

            $bar = $this->createProgressBar($total);
            $bar->start();

            $query->chunkById($chunkSize, function ($rows) use (
                &$cache,
                &$processed,
                &$updated,
                &$withoutCost,
                $dryRun,
                $bar
            ) {
                $orderNumbers = $rows->pluck('ordem')->filter()->unique()->values()->all();
                $missing = array_values(array_diff($orderNumbers, array_keys($cache)));

                if (!empty($missing)) {
                    $loadedCosts = BaseCosts::query()
                        ->whereIn('ordem', $missing)
                        ->select('ordem', DB::raw('SUM(qtdNecessaria * preco) as base_cost'))
                        ->groupBy('ordem')
                        ->pluck('base_cost', 'ordem');

                    foreach ($missing as $ordem) {
                        $cache[$ordem] = round((float) ($loadedCosts[$ordem] ?? 0), 2);
                    }
                }

                foreach ($rows as $row) {
                    $cost = (float) ($cache[$row->ordem] ?? 0);

                    if ($cost <= 0) {
                        $withoutCost++;
                    }

                    if (!$dryRun) {
                        $changed = DB::table('orders')
                            ->where('id', $row->id)
                            ->update([
                                'service_cost' => $cost,
                                'updated_at' => now(),
                            ]);

                        if ($changed > 0) {
                            $updated++;
                        }
                    }

                    $processed++;
                    $bar->advance();
                }
            }, 'o.id', 'id');

            $bar->finish();
            $this->newLine(2);

            $this->info('Execução concluída.');
            $this->line('Modo: ' . ($dryRun ? 'DRY RUN (sem gravação)' : 'ATUALIZAÇÃO REAL'));
            $this->line('Escopo: ' . ($updateAll ? 'TODAS as ordens de viabilidade' : 'Apenas service_cost nulo'));
            $this->line('Período (viabilities.sended_at): ' . ($dateFrom ? $dateFrom->format('Y-m-d') : 'início') . ' até ' . ($dateTo ? $dateTo->format('Y-m-d') : 'fim'));
            $this->line("Ordens processadas: {$processed}");
            $this->line("Ordens atualizadas: {$updated}");
            $this->line("Ordens sem custo encontrado (0.00): {$withoutCost}");

            $elapsedSeconds = max(1, (int) round(microtime(true) - $startedAtMicro));
            $ordersPerSecond = $processed > 0 ? ($processed / max(1, $elapsedSeconds)) : 0.0;
            $this->line('Duração: ' . gmdate('H:i:s', $elapsedSeconds));
            $this->line('Taxa média: ' . number_format($ordersPerSecond, 2, ',', '.') . ' ordens/s');

            if ($dryRun && $processed > 0) {
                // Dry run não faz UPDATE; adiciona 25% de margem para estimar execução real.
                $estimatedRealSeconds = (int) ceil($elapsedSeconds * 1.25);
                $estimatedFinish = $startedAt->copy()->addSeconds($estimatedRealSeconds);

                $this->line('Previsão de término (execução real): '
                    . $estimatedFinish->format('Y-m-d H:i:s')
                    . ' (estimado em ~'
                    . gmdate('H:i:s', $estimatedRealSeconds)
                    . ')');
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
