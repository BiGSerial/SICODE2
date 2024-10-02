<?php

namespace App\Console\Commands\Update;

use App\Custom\RegistroJson;
use App\Models\Edp_depc\{BaseOperation as Edp_depcBaseOperation, City};
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
        $totalRecords = Order::where('statusSist', 'Not Like', 'ENT%')
                             ->where('statusSist', 'Not Like', 'ENC%')
                             ->count();

        $log = new RegistroJson('upd_baseOperation', $this->option());
        $log->setTotal($totalRecords);

        $progressBar = new ProgressBar($this->output, $totalRecords);
        $progressBar->setFormat("<bg=blue;fg=white>UPDATE OPERATION: %current%/%max% </><fg=white;options=bold> [Loop: %cloop%/%tloop%][C: %ctd%/U: %upd%/NF: %nf%]</> <fg=green> [%bar%] </><fg=white;options=bold> %percent%%</> <bg=red;options=bold> %elapsed:6s%/%estimated:-6s% </>\n<bg=blue;fg=white>READING: </> %message%");
        $progressBar->setMessage('Processing');
        $progressBar->start();

        $chunkSize = 500;

        $count = [
            'nf'    => 0,
            'tloop' => (int) ceil($totalRecords / $chunkSize),
            'cloop' => 0,
            'ctd'   => 0,
            'upd'   => 0,
        ];

        Order::where('statusSist', 'Not Like', 'ENT%')
            ->where('statusSist', 'Not Like', 'ENC%')
            ->chunk($chunkSize, function ($orders) use (&$progressBar, &$count, &$log) {

                $count['cloop']++;

                // Map orders by 'ordem' for efficient lookup
                $ordersByOrdem = $orders->keyBy(function ($item) {
                    return trim($item->ordem);
                });

                // Get unique 'ordem' values
                $originOrders = $ordersByOrdem->keys();

                // Fetch operations for these 'ordem' values
                $operations = Edp_depcBaseOperation::whereIn('ordem', $originOrders)->get();

                // Group operations by 'ordem'
                $operationsByOrdem = $operations->groupBy(function ($item) {
                    return trim($item->ordem);
                });

                foreach ($orders as $order) {

                    $trimmedOrdem = trim($order->ordem);

                    if ($operationsByOrdem->has($trimmedOrdem)) {

                        $orderOperations = $operationsByOrdem->get($trimmedOrdem);

                        $upsertData = [];
                        $uniqueKeys = [];

                        foreach ($orderOperations as $operation) {

                            $upsertData[] = [
                                'order_id'         => $order->id,
                                'operacao'         => $operation->operacao,
                                'descOperacao'     => $operation->descOperacao,
                                'inicioPlanejado'  => $operation->inicioPlanejado,
                                'fimPlanejado'     => $operation->fimPlanejado,
                                'inicioReal'       => $operation->inicioReal,
                                'fimReal'          => $operation->fimReal,
                                'status'           => $operation->status,
                                'notaOv'           => $operation->notaOv,
                                'cenPlan'          => $operation->cenPlan,
                                'cenTrab'          => $operation->cenTrab,
                                'txtCenTrab'       => $operation->txtCenTrab,
                            ];

                            // Collect unique keys for existing records check
                            $uniqueKeys[] = [
                                'order_id' => $order->id,
                                'operacao' => $operation->operacao,
                            ];
                        }

                        // Fetch existing operations for these unique keys
                        $existingOperations = Operation::where('order_id', $order->id)
                            ->whereIn('operacao', collect($uniqueKeys)->pluck('operacao'))
                            ->get()
                            ->keyBy(function ($item) {
                                return $item->order_id . '-' . $item->operacao;
                            });

                        // Determine created and updated counts
                        foreach ($upsertData as $data) {
                            $key = $data['order_id'] . '-' . $data['operacao'];
                            if ($existingOperations->has($key)) {
                                $count['upd']++;
                            } else {
                                $count['ctd']++;
                            }
                        }

                        $uniqueBy = ['order_id', 'operacao'];
                        $updateColumns = [
                            'descOperacao', 'inicioPlanejado', 'fimPlanejado', 'inicioReal', 'fimReal',
                            'status', 'notaOv', 'cenPlan', 'cenTrab', 'txtCenTrab'
                        ];


                        dd($upsertData);

                        try {
                            Operation::upsert($upsertData, $uniqueBy, $updateColumns);
                        } catch (\Throwable $th) {
                            $log->setErrorMessage($th->getMessage());
                        }

                    } else {
                        $count['nf']++;
                    }

                    // Update progress bar
                    $progressBar->setMessage('Order ID: ' . $order->id, 'message');
                    $progressBar->setMessage($count['nf'], 'nf');
                    $progressBar->setMessage($count['ctd'], 'ctd');
                    $progressBar->setMessage($count['upd'], 'upd');
                    $progressBar->setMessage($count['cloop'], 'cloop');
                    $progressBar->setMessage($count['tloop'], 'tloop');

                    $progressBar->advance();
                }

            });

        $log->setCreated($count['ctd']);
        $log->setUpdated($count['upd']);
        $log->setNoteUpdated($count['nf']);
    }

}
