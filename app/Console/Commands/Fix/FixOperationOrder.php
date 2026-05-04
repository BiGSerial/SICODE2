<?php

namespace App\Console\Commands\Fix;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Edp_depc\BaseOperation;
use App\Models\Order;
use Illuminate\Console\Command;

class FixOperationOrder extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:fix-operation-order {--op=0010}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recovery Missed Operation to Order';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $chunkSize = 8000;

        $op_count = BaseOperation::where('operacao', $this->option('op'))->count();



        $count['upd']   = 0;
        $count['ctd']   = 0;
        $count['tloop'] = round($op_count / $chunkSize, 0);
        $count['cloop'] = 0;
        $count['nf']    = 0;


        $this->info('TRY FIX MISSED OPERATION');
        BaseOperation::where('operacao', $this->option('op'))->chunk($chunkSize, function ($operations) use (&$count, &$progressBar) {
            $theOperations = $operations->pluck('ordem')->unique();

            $orders = Order::whereDoesntHave('Operations')->whereIn('ordem', $theOperations)->get();

            if($orders) {

                $progressBar2 = $this->createProgressBar($orders->count(), 0.2);

                foreach ($orders as $order) {

                    $operation = $operations->where('ordem', $order->ordem)->first();

                    if ($operation) {

                        $check = $order->Operations()->updateOrCreate(
                            ['operacao' => $operation->operacao],
                            [
                                'descOperacao'    => $operation->descOperacao,
                                'inicioPlanejado' => $operation->inicioPlanejado,
                                'fimPlanejado'    => $operation->fimPlanejado,
                                'inicioReal'      => $operation->inicioReal,
                                'fimReal'         => $operation->fimReal,
                                'status'          => $operation->status,
                                'notaOv'          => $operation->notaOv,
                                'cenPlan'         => $operation->cenPlan,
                                'cenTrab'         => $operation->cenTrab,
                                'txtCenTrab'      => $operation->txtCenTrab,
                            ]
                        );

                        if ($check->wasRecentlyCreated) {

                            $count['ctd']++;

                        } else {

                            $count['upd']++;

                        }
                    }


                    $progressBar2->advance();
                }

                $progressBar2->finish();


            } else {
                $count['nf']++;
            }




        });


    }
}
