<?php

namespace App\Console\Commands\Orders;

use App\Custom\RegistroJson;
use App\Models\Edp_depc\BaseCosts;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class FillOrderServiceCost extends Command
{
    protected $signature = 'orders:fill-service-cost
        {--cutoff= : Data de corte (YYYY-MM-DD)}
        {--scope=all : Escopo [all|viability|tacit]}
        {--dry : Simula a execução sem gravar no banco}
        {--recalculate : Recalcula também ordens com service_cost já preenchido}
        {--chunk=500 : Tamanho do lote}';

    protected $description = 'Preenche orders.service_cost em escopo geral, com data de corte opcional.';

    public function handle(): int
    {
        $registro = null;

        try {
            $dryRun = (bool) $this->option('dry');
            $recalculate = (bool) $this->option('recalculate');
            $chunkSize = max(100, (int) $this->option('chunk'));
            $scope = strtolower(trim((string) $this->option('scope')));
            $cutoffInput = $this->option('cutoff');
            $cutoff = $cutoffInput ? Carbon::parse((string) $cutoffInput)->endOfDay() : null;

            $registro = new RegistroJson('orders:fill-service-cost', [
                'cutoff' => $cutoff ? $cutoff->toDateString() : null,
                'scope' => $scope,
                'dry' => $dryRun,
                'recalculate' => $recalculate,
                'chunk' => $chunkSize,
            ]);

            if (!in_array($scope, ['all', 'viability', 'tacit'], true)) {
                if ($registro) {
                    $registro->setErrorMessage('Opção inválida em --scope.');
                    $registro->fail('Opção inválida em --scope. Use: all, viability ou tacit.');
                }
                $this->error('Opção inválida em --scope. Use: all, viability ou tacit.');
                return self::FAILURE;
            }

            $query = DB::table('orders as o')
                ->select(['o.id', 'o.ordem', 'o.service_cost'])
                ->when(!$recalculate, function ($q) {
                    $q->whereNull('o.service_cost');
                })
                ->when($scope === 'tacit', function ($q) use ($cutoff) {
                    $q->whereExists(function ($sub) use ($cutoff) {
                        $sub->select(DB::raw(1))
                            ->from('order_work_report as owr')
                            ->join('adsforms as af', 'af.work_report_id', '=', 'owr.work_report_id')
                            ->whereColumn('owr.order_id', 'o.id')
                            ->where('af.tacit', true);

                        if ($cutoff) {
                            $sub->where('af.created_at', '<=', $cutoff);
                        }
                    });
                })
                ->when($scope === 'viability', function ($q) use ($cutoff) {
                    $q->whereExists(function ($sub) use ($cutoff) {
                        $sub->select(DB::raw(1))
                            ->from('order_viability as ov')
                            ->join('viabilities as v', 'v.id', '=', 'ov.viability_id')
                            ->whereColumn('ov.order_id', 'o.id');

                        if ($cutoff) {
                            $sub->whereNotNull('v.sended_at')
                                ->where('v.sended_at', '<=', $cutoff);
                        }
                    });
                })
                ->when($scope === 'all', function ($q) use ($cutoff) {
                    $q->where(function ($all) use ($cutoff) {
                        $all->whereExists(function ($sub) use ($cutoff) {
                            $sub->select(DB::raw(1))
                                ->from('order_viability as ov')
                                ->join('viabilities as v', 'v.id', '=', 'ov.viability_id')
                                ->whereColumn('ov.order_id', 'o.id');

                            if ($cutoff) {
                                $sub->whereNotNull('v.sended_at')
                                    ->where('v.sended_at', '<=', $cutoff);
                            }
                        })
                        ->orWhereExists(function ($sub) use ($cutoff) {
                            $sub->select(DB::raw(1))
                                ->from('order_work_report as owr')
                                ->join('adsforms as af', 'af.work_report_id', '=', 'owr.work_report_id')
                                ->whereColumn('owr.order_id', 'o.id')
                                ->where('af.tacit', true);

                            if ($cutoff) {
                                $sub->where('af.created_at', '<=', $cutoff);
                            }
                        });
                    });
                })
                ->orderBy('o.id');

            $total = (clone $query)->count();
            $this->info("Ordens alvo: {$total}");
            if ($registro) {
                $registro->setTotal($total);
            }

            if ($total === 0) {
                if ($registro) {
                    $registro->setUpdated(0);
                    $registro->save();
                }
                $this->info('Nada para atualizar.');
                return self::SUCCESS;
            }

            $cache = [];
            $processed = 0;
            $updated = 0;
            $withoutCost = 0;

            $bar = new ProgressBar($this->output, $total);
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
            $this->line('Escopo: ' . $scope);
            $this->line('Corte: ' . ($cutoff ? $cutoff->format('Y-m-d') : 'sem corte'));
            $this->line('Modo de cálculo: ' . ($recalculate ? 'RECALCULATE (atualiza todos)' : 'DEFAULT (apenas service_cost nulo)'));
            $this->line("Ordens processadas: {$processed}");
            $this->line("Ordens atualizadas: {$updated}");
            $this->line("Ordens sem custo encontrado (0.00): {$withoutCost}");
            if ($registro) {
                $registro->setUpdated($updated);
                $registro->save();
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            if ($registro) {
                $registro->setErrorMessage($e->getMessage());
                $registro->fail($e->getMessage());
            }
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
