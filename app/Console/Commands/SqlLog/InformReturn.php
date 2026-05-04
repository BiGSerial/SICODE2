<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\ReturnWork;
use App\Models\SicodeSql\InformLog;
use App\Models\SicodeSql\LogReturnInform;
use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Console\Command;

class InformReturn extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:log_InformReturn {--days=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send LOG Return Informs to SQL SERVER';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (env('APP_QA') || env('APP_ENV') == 'local') {
            $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> NOT INFORMS SERVER, ABORTING PROPAGATION LOG</>');
            return; // Adicionando um retorno aqui para abortar a execução
        }

        $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> Verifying Return Informs.... </>');

        $days = $this->option('days');

        $returnWorkReports = ReturnWork::whereDate('updated_at', '>=', Carbon::now()->subDays($days))->count();
        $progressBar = $this->createProgressBar($returnWorkReports);
        $progressBar->setFormat(' <bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s% %message%');
        $progressBar->setMessage('Inserting in bulk');

        if ($returnWorkReports) {
            ReturnWork::whereDate('updated_at', '>=', Carbon::now()->subDays($days))->chunk(500, function ($chunk) use ($progressBar) {
                foreach ($chunk as $inform) {

                    $check = LogReturnInform::updateOrCreate(
                        [
                            'return_inform_id' => $inform->id,
                            'inform_id' => $inform->work_report_id,
                        ],
                        [
                            'service' => $inform->Service->service ? $inform->Service->service : null,
                            'usuario' => $inform->User->name ? $inform->User->name : null,
                            'category' => $inform->category,
                            'text_obs' => $inform->text_obs,
                            'returned_at' => $inform->created_at,
                        ]
                    );


                    if ($check->wasRecentlyCreated) {
                        $msg = "<bg=green;fg=white;options=bold> CREATED </><bg=blue;fg=white;options=bold></>";
                    } else {
                        $msg = "<bg=yellow;fg=white;options=bold> UPDATED </><bg=blue;fg=white;options=bold></>";
                    }

                    $progressBar->setMessage($msg);
                    $progressBar->advance();
                }
            });
        }

        $progressBar->finish();

        if (!$returnWorkReports) {
            $this->info("<bg=green;fg=white;options=bold> DONE </><fg=yellow;options=bold> NO REGISTERS FOUND");
        } else {
            $this->info("<bg=blue;fg=white;options=bold> INFO </><fg=white;options=bold> WE HAVE FOUND {$returnWorkReports} REGISTERS THAT AREN'T IN INFORMS LOG");
        }

        $this->info('<bg=green;fg=white> DONE </>');
    }
}
