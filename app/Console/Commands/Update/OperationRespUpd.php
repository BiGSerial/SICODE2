<?php

namespace App\Console\Commands\Update;

use App\Models\Edp_depc\BaseOperationResp;
use App\Models\OperationResp;
use App\Models\Order;
use Illuminate\Console\Command;
use Carbon\Carbon;

class OperationRespUpd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:operation-resp-upd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $operation = BaseOperationResp::where("operacao", "0040")
        ->where('confFinal', '!=', 'X')
        // ->where('fimLancado', '>=', Carbon::now()->subDays(7))
        ->orderBy('fimLancado', 'desc')
        ->count();


        $progressbar = $this->output->createProgressBar($operation);

        $progressbar->setFormat('%current%/%max% [ins: %ins% |upd: %upd% |nf: %nf% |err: %err%] [%bar%] %elapsed%/%estimated% | %memory%');

        $count['ins'] = 0;
        $count['upd'] = 0;
        $count['nf'] = 0;
        $count['err'] = 0;

        if ($operation > 0) {

            $progressbar->start();

            BaseOperationResp::where("operacao", "0040")->where('confFinal', '!=', 'X')->orderBy('fimLancado', 'asc')->chunk(500, function ($operations) use (&$count, &$progressbar) {

                $orders = Order::whereIn('ordem', $operations->pluck('ordem')->unique()->toArray())->get();

                foreach ($operations as $operation) {

                    $order = $orders->where('ordem', $operation->ordem)->first();

                    if ($order) {
                        try {
                            $chk = OperationResp::updateOrCreate(
                                ['order_id' => $order->id,
                                 'operacao' => $operation->operacao,
                                 'confFinal' => $operation->confFinal,
                                 ],
                                [
                                     'note_id' => $order->note_id,
                                     'fimReal' => $operation->fimReal,
                                     'fimLancado' => $operation->fimLancado,
                                     'cenTrab' => $operation->cenTrab,
                                     'txtCenTrab' => $operation->txtCenTrab,
                                     'matriculaResp' => $operation->matriculaResp,
                                     'nomeResp' => $operation->nomeResp,
                                 ]
                            );

                            if ($chk->wasRecentlyCreated) {
                                $count['ins']++;
                            } else {
                                $count['upd']++;
                            }

                        } catch (\Throwable $th) {
                            $count['err']++;
                            dd($th->getMessage());
                        }
                    } else {
                        $count['nf']++;
                    }

                    $progressbar->setMessage($count['ins'], 'ins');
                    $progressbar->setMessage($count['upd'], 'upd');
                    $progressbar->setMessage($count['nf'], 'nf');
                    $progressbar->setMessage($count['err'], 'err');
                    $progressbar->advance();
                }
            });


        }

        $progressbar->finish();

    }
}
