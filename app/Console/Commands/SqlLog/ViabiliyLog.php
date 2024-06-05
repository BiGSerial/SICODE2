<?php

namespace App\Console\Commands\SqlLog;

use App\Custom\Viabilitiesstatus;
use App\Models\SicodeSql\ViabilityLog;
use App\Models\Viability;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class ViabiliyLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:log_viability {--days=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Viability Log to SQL Server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (env('APP_QA') || env('APP_ENV') == 'local') {
            $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> NOT IS PRODUCTION SERVER, ABORTING PROPAGATION LOG</>');
        }

        $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> Verifing Viabilitys.... </>');

        $days = $this->option('days');

        $progressBar = new ProgressBar($this->output, Viability::whereDate('updated_at', '>=', Carbon::now()->subDays($days))->count());
        $progressBar->setFormat(' <bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s% %message%');
        $progressBar->setMessage('Inserting in bulk');


        $viabilities = Viability::whereDate('updated_at', '>=', Carbon::now()->subDays($days))
            ->with('Order', 'User', 'Company', 'Engineer')
            ->chunk(1000, function ($chunk) use ($progressBar) {
                foreach ($chunk as $viability) {
                    $check = ViabilityLog::updateOrCreate(
                        ['viability_id' => $viability->id],
                        [
                            'hired_by' => $viability->User->name,
                            'company_hiring' => $viability->User->Employee->Contract ? $viability->User->Employee->Contract->company->name : '---',
                            'responsible' => $viability->Engineer ? $viability->Engineer->name : 'DESCONHECIDO',
                            'company_responsible' => $viability->Engineer->Employee->Contract ? $viability->Engineer->Employee->Contract->company->name : '---',
                            'viability_by' => $viability->Form ? $viability->Form->responsible : 'DESCONHECIDO',
                            'company_viability' => $viability->Company->name,
                            'note' => $viability->Order->Note->note,
                            'order' => $viability->Order->ordem,
                            'status' => Viabilitiesstatus::status($viability->status)->status,
                            'completed' => $viability->completed,
                            'approved' => $viability->approved,
                            'rejected' => $viability->rejected,
                            'tacit' => $viability->tacit,
                            'hired' => $viability->hired,
                            'completed_at' => $viability->completed_at,
                            'returned_at' => $viability->returned_at,
                            'hired_at' => $viability->hired_at,
                            'tacit_at' => $viability->tacit_at,
                        ]
                    );

                    if ($check->wasRecentlyCreated) {
                        $msg = "<bg=green;fg=white;options=bold> CREATED </><bg=blue;fg=white;options=bold> {$viability->Order->Note->note} </>";
                    } else {
                        $msg = "<bg=yellow;fg=white;options=bold> UPDATED </><bg=blue;fg=white;options=bold> {$viability->Order->Note->note} </>";
                    }

                    $progressBar->setMessage($msg);
                    $progressBar->advance();
                }
            });

        $progressBar->finish();

        if ($viabilities->isEmpty()) {
            $this->info("<bg=green;fg=white;options=bold> DONE </><fg=yellow;options=bold> NO REGISTERS FOUNDED");
        } else {
            $this->info("<bg=blue;fg=white;options=bold> INFO </><fg=white;options=bold> WE HAVE FOUND {$viabilities->count()} REGISTERS AREN'T IN VIABILITY LOG");
        }

        $this->info('<bg=green;fg=white> DONE </>');
    }

}
