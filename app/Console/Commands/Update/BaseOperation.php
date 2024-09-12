<?php

namespace App\Console\Commands\Update;

use App\Custom\RegistroJson;
use App\Models\Edp_depc\{BaseOperation as Edp_depcBaseOperation, City};
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
        $totalRecords = Order::Where('statusSist', 'Not Like', 'ENT%')->Where('statusSist', 'Not Like', 'ENC%')->count();
        $cities       = City::get();

        $log = new RegistroJson('upd_baseOperation', $this->option());
        $log->setTotal($totalRecords);

        $progressBar = new ProgressBar($this->output, $totalRecords * 6, 0.2);

        $progressBar->setFormat("<bg=blue;fg=white>UPDATE OPERATION: %current%/%max% </><fg=white;options=bold> [%cloop%/%tloop%][C: %ctd%/U: %upd%/NF: %nf%]</> <fg=green> [%bar%] </><fg=white;options=bold> %percent%%</> <bg=red;options=bold> %elapsed:6s%/%estimated:-6s% </>\n <bg=blue;fg=white>READING: </> %message%");
        // $progressBar->setFormat('%current%/%max% [%cloop%/%tloop%][C: %ctd%/U: %upd%/NF: %nf%] [%bar%] %percent%% %elapsed:6s%/%estimated:-6s% %message%');
        $progressBar->setMessage('Inserting in bulk');

        $progressBar->display();

        $progressBar->start();

        $chunkSize = 500;

        $count['upd']   = 0;
        $count['ctd']   = 0;
        $count['tloop'] = round($totalRecords / $chunkSize, 0);
        $count['cloop'] = 0;
        $count['nf']    = 0;

        Order::Where('statusSist', 'Not Like', 'ENT%')->Where('statusSist', 'Not Like', 'ENC%')->chunk($chunkSize, function ($orders) use (&$progressBar, &$count, &$log) {

            $originOrders = $orders->pluck('ordem')->unique();
            $operations   = Edp_depcBaseOperation::whereIn('ordem', $originOrders)->get();


            $count['cloop']++;

            foreach ($operations as $operation) {

                $order = $orders->where('ordem', $operation->ordem)->first();

                // if (!$order) {

                //     $order = Order::whereRelation('Operations', 'ordem', $origin->ordem)->whereRelation('Operations', 'operacao', $origin->operacao)->first();

                //     if (!$order) {
                //         // Nota não encontrada, cria-se um uma nova numeração caso o tamanho da numeração seja menor que 4 digitos.
                //         $count['nf']++;

                //         if (strlen((string)(int)$origin->ovNota) < 4) {
                //             $rangeNum = 90000000;
                //             $newNote = 'C'.(string)($rangeNum + Note::Where('note', 'like', 'C9%')->count());
                //         } else {
                //             $newNote = (string)(int)$origin->ovNota;
                //         }

                //         $note = new Note();
                //         $note->note = $newNote;
                //         $note->created_by = 'SICODE';
                //         $note->numPedido = $origin->descricao;
                //         $note->centerjob = $origin->cenTrab;
                //         $note->nexp = $cities->where('gpm', $origin->gpm)->first() ? $cities->where('gpm', $origin->gpm)->first()->rdMunicipio : null;
                //         $note->lexp = $cities->where('gpm', $origin->gpm)->first() ? $cities->where('gpm', $origin->gpm)->first()->municipio : null;
                //         $note->nstats = '101';
                //         $note->save();

                //     }
                // }

                if ($order) {
                    try {
                        $operation = $order->Operations()->updateOrCreate(
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

                        if ($operation->wasRecentlyCreated) {

                            $count['ctd']++;
                        } else {

                            $count['upd']++;
                        }
                    } catch (\Throwable $th) {
                        $log->setErrorMessage($th->getMessage());
                    }
                } else {
                    $count['nf']++;
                }

                $progressBar->setMessage($operation->id, 'message');
                $progressBar->setMessage($count['nf'], 'nf');
                $progressBar->setMessage($count['cloop'], 'cloop');
                $progressBar->setMessage($count['tloop'], 'tloop');
                $progressBar->setMessage($count['upd'], 'upd');
                $progressBar->setMessage($count['ctd'], 'ctd');
                $progressBar->advance();
            }
        });

        $log->setCreated($count['ctd']);
        $log->setUpdated($count['upd']);
        $log->setNoteUpdated($count['nf']);
        $log->save();

        unset($count);

        $progressBar->finish();
    }
}
