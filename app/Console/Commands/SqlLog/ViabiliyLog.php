<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\Viabilitiesstatus;
use App\Http\Livewire\Construction\Hiring\Actions\Waitinghiring;
use App\Models\HiringWaiting;
use App\Models\SicodeSql\ViabilityLog;
use App\Models\Viability;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ViabiliyLog extends Command
{
    use ShowsProgress;

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

        $progressBar = $this->createProgressBar(Viability::whereDate('updated_at', '>=', Carbon::now()->subDays($days))->count());
        $progressBar->setFormat(' <bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s% %message%');
        $progressBar->setMessage('Inserting in bulk');


        Viability::whereDate('updated_at', '>=', Carbon::now()->subDays($days))
            ->with('Orders', 'User', 'Company', 'Engineer')
            ->chunk(500, function ($chunk) use ($progressBar) {
                foreach ($chunk as $viability) {

                    $sla_hiring = [
                        'ri_sended_at' => null,
                        'ri_finished_at' => null,
                        'ri_service' => null,
                        'ri_category' => null,
                    ];

                    $waiting_hiring = HiringWaiting::where('note_id', $viability->Note->id)->first();

                    if ($waiting_hiring) {



                        $sla_hiring['ri_sended_at'] =  $waiting_hiring->created_at ? Carbon::parse($waiting_hiring->created_at)->format('Y-m-d H:i:s') : null;
                        $sla_hiring['ri_category'] = $waiting_hiring->category ? $waiting_hiring->category : null;

                        if ($waiting_hiring->Reclaim) {

                            $sla_hiring['ri_finished_at'] = $waiting_hiring->Reclaim->completed_at ? $waiting_hiring->Reclaim->completed_at : null;
                            $sla_hiring['ri_service'] = $waiting_hiring->Reclaim->Service->service ? $waiting_hiring->Reclaim->Service->service : null;
                        }

                        $this->info('Waiting: ' . $viability->Note->note);
                    }

                    if ($viability->Orders->isNotEmpty()) {
                        foreach ($viability->Orders as $order) {
                            $check = ViabilityLog::updateOrCreate(
                                [
                                    'viability_id' => $viability->id,
                                    'order' => $order->ordem,
                                ],
                                [
                                    'hired_by' => $viability->User->name,
                                    'company_hiring' => $viability->User->Employee->Contract ? $viability->User->Employee->Contract->company->name : '---',
                                    'responsible' => $viability->Engineer ? $viability->Engineer->name : 'DESCONHECIDO',
                                    'company_responsible' => $viability->Engineer->Employee->Contract ? $viability->Engineer->Employee->Contract->company->name : '---',
                                    'viability_by' => $viability->Form ? $viability->Form->responsible : 'NÃO VIABILIZADO',
                                    'company_viability' => $viability->Company->name,
                                    'note' => $viability->Note->note,
                                    'status' => Viabilitiesstatus::status($viability->status)->status,
                                    'completed' => $viability->completed,
                                    'approved' => $viability->approved,
                                    'rejected' => $viability->rejected,
                                    'tacit' => $viability->tacit,
                                    'hired' => $viability->hired,
                                    'completed_at' => $viability->completed_at,
                                    'sended_at' => $viability->sended_at,
                                    'returned_at' => $viability->returned_at,
                                    'hired_at' => $viability->hired_at,
                                    'tacit_at' => $viability->tacit_at,
                                    'ri_sended_at' => $sla_hiring['ri_sended_at'],
                                    'ri_finished_at' => $sla_hiring['ri_finished_at'],
                                    'ri_service' => $sla_hiring['ri_service'],
                                    'ri_category' => $sla_hiring['ri_category'],
                                ]
                            );

                            if ($check->wasRecentlyCreated) {
                                $msg = "<bg=green;fg=white;options=bold> CREATED </><bg=blue;fg=white;options=bold> {$viability->Note->note} </>";
                            } else {
                                $msg = "<bg=yellow;fg=white;options=bold> UPDATED </><bg=blue;fg=white;options=bold> {$viability->Note->note} </>";
                            }
                        }
                    }





                    $progressBar->setMessage($msg);
                    $progressBar->advance();
                }
            });

        $progressBar->finish();

        // if (!$viabilities) {
        //     $this->info("<bg=green;fg=white;options=bold> DONE </><fg=yellow;options=bold> NO REGISTERS FOUNDED");
        // } else {
        //     $this->info("<bg=blue;fg=white;options=bold> INFO </><fg=white;options=bold> WE HAVE FOUND {$viabilities->count()} REGISTERS AREN'T IN VIABILITY LOG");
        // }

        $this->info('<bg=green;fg=white> DONE </>');
    }
}
