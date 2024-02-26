<?php

namespace App\Console\Commands\Update;

use App\Models\Edp_depc\BaseOperation as Edp_depcBaseOperation;
use App\Models\Edp_depc\BaseOrder;
use App\Models\Edp_depc\City;
use App\Models\Operation;
use App\Models\Order;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class BaseOperation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_baseOperation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Base Operation from SQLSERVER.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $totalRecords = Edp_depcBaseOperation::count();
        $cities = City::get();

        $progressBar = new ProgressBar($this->output, $totalRecords, 0.2);

        $progressBar->setFormat('<bg=blue;fg=white>UPDATE OPERATION: %current%/%max% </><fg=white;options=bold> [%cloop%/%tloop%][C: %ctd%/U: %upd%/NF: %nf%]</> <fg=green> [%bar%] </><fg=white;options=bold> %percent%%</> <bg=red;options=bold> %elapsed:6s%/%estimated:-6s% </> %message%');
        // $progressBar->setFormat('%current%/%max% [%cloop%/%tloop%][C: %ctd%/U: %upd%/NF: %nf%] [%bar%] %percent%% %elapsed:6s%/%estimated:-6s% %message%');
        $progressBar->setMessage('Inserting in bulk');

        $progressBar->start();

        $chunkSize = 10000;

        $count['upd'] = 0;
        $count['ctd'] = 0;
        $count['tloop'] = round($totalRecords / $chunkSize, 0);
        $count['cloop'] = 0;
        $count['nf'] = 0;

        Edp_depcBaseOperation::chunk($chunkSize, function ($origins) use (&$progressBar, &$count, $cities) {
            $originOrders = $origins->pluck('ordem')->unique();
            $orders = Order::whereIn('ordem', $originOrders)->get();

            if (count($originOrders) > $orders->count()) {
                $this->info('<bg=yellow;fg=black> INFO </> <fg=white;options=bold> DESTINY`s BASE HAVE NOTES/OV NOT FOUNDED...</>');
            }

            $count['cloop']++;

            foreach ($origins as $origin) {

                $order = $orders->where('ordem', $origin->ordem)->first();

                if (!$order) {

                    $order = Order::whereRelation('Operations', 'ordem', $origin->ordem)->whereRelation('Operations', 'operacao', $origin->operacao)->first();

                    // if (!$order) {
                    //     // Nota não encontrada, cria-se um uma nova numeração caso o tamanho da numeração seja menor que 4 digitos.
                    //     $count['nf']++;

                    //     if (strlen((string)(int)$origin->ovNota) < 4) {
                    //         $rangeNum = 90000000;
                    //         $newNote = 'C'.(string)($rangeNum + Note::Where('note', 'like', 'C9%')->count());
                    //     } else {
                    //         $newNote = (string)(int)$origin->ovNota;
                    //     }

                    //     $note = new Note();
                    //     $note->note = $newNote;
                    //     $note->created_by = 'SICODE';
                    //     $note->numPedido = $origin->descricao;
                    //     $note->centerjob = $origin->cenTrab;
                    //     $note->nexp = $cities->where('gpm', $origin->gpm)->first() ? $cities->where('gpm', $origin->gpm)->first()->rdMunicipio : null;
                    //     $note->lexp = $cities->where('gpm', $origin->gpm)->first() ? $cities->where('gpm', $origin->gpm)->first()->municipio : null;
                    //     $note->nstats = '101';
                    //     $note->save();

                    // }
                }

                if ($order) {
                    $operation = $order->Operations()->updateOrCreate(
                        [ 'operacao' => $origin->operacao],
                        [
                            'descOperacao' => $origin->descOperacao,
                            'inicioPlanejado' => $origin->inicioPlanejado,
                            'fimPlanejado' => $origin->fimPlanejado,
                            'inicioReal' => $origin->inicioReal,
                            'fimReal' => $origin->fimReal,
                            'status' => $origin->status,
                            'notaOv' => $origin->notaOv,
                            'cenPlan' => $origin->cenPlan,
                            'cenTrab' => $origin->cenTrab,
                            'txtCenTrab' => $origin->txtCenTrab,
                        ]
                    );

                    if ($operation->wasRecentlyCreated) {

                        $count['ctd']++;

                    } else {

                        $count['upd']++;

                    }

                } else {
                    $count['nf']++;
                }

                $progressBar->setMessage($origin->id, 'message');
                $progressBar->setMessage($count['nf'], 'nf');
                $progressBar->setMessage($count['cloop'], 'cloop');
                $progressBar->setMessage($count['tloop'], 'tloop');
                $progressBar->setMessage($count['upd'], 'upd');
                $progressBar->setMessage($count['ctd'], 'ctd');
                $progressBar->advance();
            }

            unset($origins);
            unset($orders);

        });

        unset($count);

        $progressBar->finish();
    }
}
