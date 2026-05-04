<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\SicodeSql\InformLog;
use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Console\Command;

class InformLogs extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:log_inform {--days=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send LOG Informs to SQL SERVER';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (env('APP_QA') || env('APP_ENV') == 'local') {
            $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> NOT INFORMS SERVER, ABORTING PROPAGATION LOG</>');
            return; // Adicionando um retorno aqui para abortar a execução
        }

        $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> Verifying Informs.... </>');

        $days = $this->option('days');

        $workReports = WorkReport::whereDate('updated_at', '>=', Carbon::now()->startOfDay()->subDays($days))->count();
        $progressBar = $this->createProgressBar($workReports);
        $progressBar->setFormat(' <bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s% %message%');
        $progressBar->setMessage('Inserting in bulk');



        if ($workReports) {
            WorkReport::whereDate('updated_at', '>=', Carbon::now()->startOfDay()->subDays($days))->chunk(500, function ($chunk) use ($progressBar) {
                foreach ($chunk as $inform) {

                    foreach ($inform->orders as $order) {

                        $check = InformLog::updateOrCreate(
                            [
                                'inform_id' => $inform->id,
                                'ordem'  => $order->ordem
                            ],
                            [
                                'note' => $inform->Note->note,
                                'company' => $inform->Company->name,
                                'user_name' => $inform->User->name,
                                'date' => $inform->date,
                                'equipment' => $inform->equipment,
                                'connection' => $inform->connection,
                                'changes' => $inform->changes,
                                'observation' => $inform->observation,
                                'damage' => $inform->damage,
                                'description' => $inform->description,
                                'team' => $inform->team,
                                'responsible' => $inform->responsible,
                                'approved' => $inform->approved,
                                'rejected' => $inform->rejected,
                                'retry' => $inform->retry,
                                'first_at' => $inform->created_at,
                                'informed_at' => $inform->informed_at,
                            ]
                        );
                    }


                    if ($check->wasRecentlyCreated) {
                        $msg = "<bg=green;fg=white;options=bold> CREATED </><bg=blue;fg=white;options=bold> {$inform->Note->note} </>";
                    } else {
                        $msg = "<bg=yellow;fg=white;options=bold> UPDATED </><bg=blue;fg=white;options=bold> {$inform->Note->note} </>";
                    }

                    $progressBar->setMessage($msg);
                    $progressBar->advance();
                }
            });
        }

        $progressBar->finish();

        if (!$workReports) {
            $this->info("<bg=green;fg=white;options=bold> DONE </><fg=yellow;options=bold> NO REGISTERS FOUND");
        } else {
            $this->info("<bg=blue;fg=white;options=bold> INFO </><fg=white;options=bold> WE HAVE FOUND {$workReports} REGISTERS THAT AREN'T IN INFORMS LOG");
        }

        $this->info('<bg=green;fg=white> DONE </>');
    }
}
