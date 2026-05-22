<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Production;
use App\Models\SicodeSql\WpasLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class WpasLogger extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:wpas_log {--days=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charger to SQLserver Wpas DD registers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (env('APP_QA') || env('APP_ENV') == 'local') {
            $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> NOT IS PRODUCTION SERVER, ABORTING PROPAGATION LOG</>');
        }

        $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> PREPARING WPAS NOTES INFORMATION </>');
        $wpas = Production::whereDate('updated_at', '>=', Carbon::now()->subDays($this->option('days')))->whereHas('Wpas')->With('Wpas')->get();
        $this->info("<bg=green;fg=white> DONE </> <fg=white;options=bold> {$wpas->count()} FOUNDED. </>");
        $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> PREPARING WPAS NOTES SQL </>');
        $sql = WpasLog::count();
        $this->info("<bg=green;fg=white> DONE </> <fg=white;options=bold> {$sql} Log Registers. </>");
        $progressBar = $this->createProgressBar($wpas->count());

        if ($wpas->count()) {

            $this->info('<bg=yellow;fg=white> RUN  </> <fg=white;options=bold> STARTING... </>');

            $progressBar->start($wpas->count());
            $progressBar->setFormat(' <bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s% %message%');

            foreach ($wpas as $wpa) {
                $chk = WpasLog::where('production_id', $wpa->id)->first();

                if ($chk) {
                    try {
                        $chk->update([
                            'production_id' => $wpa->id,
                            'note'          => $wpa->load('Note')->Note->note,
                            'dd'            => $wpa->Wpas->last()->dd,
                        ]);

                        // $this->info("<bg=yellow;fg=white> UPDT </> <fg=white;options=bold> {$chk->note} </>");
                    } catch (\Throwable $th) {
                        // $this->info("<bg=yellow;fg=white> FAIL </> <fg=white;options=bold> {$chk->note} </>");
                        echo $th->getMessage();
                    }
                } else {
                    try {
                        $chk = WpasLog::create([
                            'production_id' => $wpa->id,
                            'note'          => $wpa->load('Note')->Note->note,
                            'dd'            => $wpa->Wpas->last()->dd,
                        ]);
                        // $this->info("<bg=green;fg=white> UPDT </> <fg=white;options=bold> {$chk->note} </>");
                    } catch (\Throwable $th) {
                        // $note =  $wpa->load('Note')->Note->note;
                        // $this->info("<bg=yellow;fg=white> FAIL </> <fg=white;options=bold> {$note } </>");
                        echo $th->getMessage();
                    }
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();

        $this->info('<bg=green;fg=white> RUN  </> <fg=white;options=bold> COMPLETE. </>');
    }
}
