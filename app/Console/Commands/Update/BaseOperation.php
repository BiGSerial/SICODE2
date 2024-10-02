<?php

namespace App\Console\Commands\Update;

use App\Custom\RegistroJson;
use App\Models\Edp_depc\{BaseOperation as Edp_depcBaseOperation, City};
use App\Models\Operation;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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

        // $this->removeDuplicate();




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

                        foreach ($orderOperations as $operation) {

                            $data = [
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

                            $uniqueAttributes = [
                                'order_id' => $data['order_id'],
                                'operacao' => $data['operacao'],
                            ];

                            $updateAttributes = $data;
                            unset($updateAttributes['order_id'], $updateAttributes['operacao']);

                            try {
                                $operationModel = Operation::updateOrCreate($uniqueAttributes, $updateAttributes);
                                if ($operationModel->wasRecentlyCreated) {
                                    $count['ctd']++;
                                } else {
                                    $count['upd']++;
                                }
                            } catch (\Throwable $th) {
                                $log->setErrorMessage($th->getMessage());
                            }
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
        $log->save();

        $progressBar->finish();
    }


    public function removeDuplicate()
    {

        // Step 1: Find duplicates based on order_id and operacao
        $duplicates = DB::table('operations')
            ->select('order_id', 'operacao', DB::raw('COUNT(*) as count'))
            ->groupBy('order_id', 'operacao')
            ->having('count', '>', 1)
            ->get();

        $totalDuplicates = $duplicates->count();
        echo "Found {$totalDuplicates} sets of duplicates.\n";

        if ($totalDuplicates === 0) {
            echo "No duplicates found. Exiting.\n";
            exit;
        }

        $counter = 0;

        foreach ($duplicates as $duplicate) {
            // Fetch all duplicate records
            $records = Operation::where('order_id', $duplicate->order_id)
                ->where('operacao', $duplicate->operacao)
                ->orderBy('id', 'desc') // Assuming the highest ID is the most recent
                ->get();

            // Keep the first record and delete the rest
            $recordsToDelete = $records->slice(1); // All except the first record
            $idsToDelete = $recordsToDelete->pluck('id')->toArray();

            // Delete the duplicate records
            Operation::whereIn('id', $idsToDelete)->delete();

            $counter++;
            echo "Processed duplicate group {$counter} of {$totalDuplicates}.\n";
        }

        echo "Duplicate removal complete.\n";

    }


}
