<?php

namespace App\Console\Commands\Ads;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Edp_depc\BaseCosts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class FillMissingTacitOrderServiceCost extends Command
{
    use ShowsProgress;

    protected $signature = 'ads:fill-missing-service-cost
        {--dry : Simula a execução sem gravar no banco}
        {--chunk=500 : Tamanho do lote para processamento}';

    protected $description = 'Preenche orders.service_cost apenas para ordens sem valor vinculadas a ADS tácitas existentes.';

    public function handle(): int
    {
        try {
            $dryRun = (bool) $this->option('dry');
            $chunkSize = max(100, (int) $this->option('chunk'));

            $query = DB::table('orders as o')
                ->select(['o.id', 'o.ordem'])
                ->whereNull('o.service_cost')
                ->where('o.canceled', false)
                ->where('o.statusSist', 'not like', 'CANC%')
                ->where('o.statusSist', 'not like', 'ENT%')
                ->where('o.statusSist', 'not like', 'ENC%')
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('order_work_report as owr')
                        ->join('work_reports as wr', 'wr.id', '=', 'owr.work_report_id')
                        ->join('adsforms as af', 'af.work_report_id', '=', 'owr.work_report_id')
                        ->whereColumn('owr.order_id', 'o.id')
                        ->where('wr.rejected', false)
                        ->where('wr.canceled', false)
                        ->where('af.tacit', true);
                })
                ->orderBy('o.id');

            $total = (clone $query)->count();
            $this->info("Ordens alvo (sem service_cost e com ADS tácita): {$total}");

            if ($total === 0) {
                $this->info('Nada para atualizar.');
                return self::SUCCESS;
            }

            $cache = [];
            $updated = 0;
            $withoutCost = 0;

            $bar = $this->createProgressBar($total);
            $bar->start();

            $query->chunkById($chunkSize, function ($rows) use (&$cache, &$updated, &$withoutCost, $dryRun, $bar) {
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
                        DB::table('orders')
                            ->where('id', $row->id)
                            ->update([
                                'service_cost' => $cost,
                                'updated_at' => now(),
                            ]);
                    }

                    $updated++;
                    $bar->advance();
                }
            }, 'o.id', 'id');

            $bar->finish();
            $this->newLine(2);

            $this->info('Execução concluída.');
            $this->line('Modo: ' . ($dryRun ? 'DRY RUN (sem gravação)' : 'ATUALIZAÇÃO REAL'));
            $this->line("Ordens processadas: {$updated}");
            $this->line("Ordens sem custo encontrado (gravadas com 0.00): {$withoutCost}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
