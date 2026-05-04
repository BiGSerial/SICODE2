<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\Edp_depc\BaseCosts;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BaseCostsUpd extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_costs_mot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Residual MOT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $log = null;

        try {
            // Obtém todas as ordens únicas
            $ordens = BaseCosts::select('ordem')->distinct()->pluck('ordem')->toArray();

            $log = new RegistroJson('upd_costs_mot', $this->option());
            $log->setTotal(count($ordens));
            $updated = 0;

            if (count($ordens) > 0) {
                $progressBar = $this->createProgressBar(count($ordens));
                $progressBar->start();

                // Processa as ordens em chunks de 500
                Order::whereIn('ordem', $ordens)->chunk(500, function ($orders) use ($progressBar, &$log, &$updated) {
                    // Obtém todas as ordens atuais do chunk
                    $orderOrdens = $orders->pluck('ordem')->toArray();

                    // Calcula os custos para as ordens atuais do chunk
                    $costs = BaseCosts::whereIn('ordem', $orderOrdens)
                        ->select('ordem', DB::raw('SUM((qtdNecessaria - qtdRetirada) * preco) AS MOAberta'))
                        ->groupBy('ordem')
                        ->get();

                    // Atualiza cada ordem com o custo calculado
                    foreach ($costs as $cost) {
                        $order = $orders->where('ordem', $cost->ordem)->first();

                        if ($order) {
                            try {
                                $order->moaberto = $cost->MOAberta;
                                $order->save();
                                $updated++;
                            } catch (Throwable $th) {
                                $log->setErrorMessage($th->getMessage());
                            }
                        }

                        $progressBar->advance();
                    }
                });

                $progressBar->finish();
                $this->info('Atualização de ordens concluída!');
            }

            $log->setUpdated($updated);
            $log->save();
            return self::SUCCESS;
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            return self::FAILURE;
        }
    }
}
