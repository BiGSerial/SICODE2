<?php

namespace App\Console\Commands\SqlLog;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Notetimeline;
use App\Models\SicodeSql\Movnote;
use Carbon\Carbon;
use Illuminate\Console\Command;

class StopNoteLog extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:notestop_log {--days=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charger to SQLServer Stopped Notes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (env('APP_QA') || env('APP_ENV') == 'local') {
            $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> NOT IS PRODUCTION SERVER, ABORTING PROPAGATION LOG</>');
        }
        
        $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> PREPARING STOP NOTES INFORMATION </>');
        $stops = Notetimeline::whereDate('updated_at', '>=', Carbon::now()->subDays($this->option('days')))->where('status', 4)->get();
        $this->info("<bg=green;fg=white> DONE </> <fg=white;options=bold> {$stops->count()} FOUNDED. </>");
        $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold> PREPARING STOP NOTES SQL </>');
        $sql = Movnote::count();
        $this->info("<bg=green;fg=white> DONE </> <fg=white;options=bold> {$sql} Log Registers. </>");
        $progressBar = $this->createProgressBar($stops->count());

        if ($stops->count()) {

            $this->info('<bg=yellow;fg=white> RUN  </> <fg=white;options=bold> STARTING... </>');

            $progressBar->start($stops->count());
            $progressBar->setFormat(' <bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s% %message%');

            foreach ($stops as $stop) {
                $chk = Movnote::where('move_id', $stop->id)->first();

                if ($chk) {
                    try {
                        $chk->update([
                            'move_id'        => $stop->id,
                            'production_id'  => $stop->production_id,
                            'note'           => $stop->load('Note')->Note->note,
                            'service'        => $stop->load('Service')->Service->service,
                            'user'           => $stop->load('User')->User->name,
                            'company'        => $stop->load('Production.Company')->Production->Company->name,
                            'status'         => $stop->status,
                            'note_status'    => $stop->load('Production')->Production->status_note,
                            'info'           => $stop->info,
                            'stopped_at'     => $stop->created_at,
                            'stopped_return' => $stop->return_stop,
                        ]);

                        // $this->info("<bg=yellow;fg=white> UPDT </> <fg=white;options=bold> {$chk->note} </>");
                    } catch (\Throwable $th) {
                        // $this->info("<bg=yellow;fg=white> FAIL </> <fg=white;options=bold> {$chk->note} </>");
                        echo $th->getMessage();
                    }
                } else {
                    try {
                        $chk = Movnote::create([
                            'move_id'        => $stop->id,
                            'production_id'  => $stop->production_id,
                            'note'           => $stop->load('Note')->Note->note,
                            'service'        => $stop->load('Service')->Service->service,
                            'user'           => $stop->load('User')->User->name,
                            'company'        => $stop->load('Production.Company')->Production->Company->name,
                            'status'         => $stop->status,
                            'note_status'    => $stop->load('Production')->Production->status_note,
                            'info'           => $stop->info,
                            'stopped_at'     => $stop->created_at,
                            'stopped_return' => $stop->return_stop,
                        ]);
                        // $this->info("<bg=green;fg=white> UPDT </> <fg=white;options=bold> {$chk->note} </>");
                    } catch (\Throwable $th) {
                        // $note =  $stop->load('Note')->Note->note;
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
