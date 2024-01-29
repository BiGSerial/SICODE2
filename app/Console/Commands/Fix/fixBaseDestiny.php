<?php

namespace App\Console\Commands\Fix;

use App\Models\Edp_depc\BaseOV;
use App\Models\Note;
use Illuminate\Console\Command;

class fixBaseDestiny extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:fix_destinyBase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify about extra register in Destiny.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $statusInfo = Note::select('nstats', 'type_note')->where('type_note', 2)->orderBy('nstats')->get()->pluck('nstats')->unique()->toArray();
        $extraNotes['status'] = [];

        $this->info('<bg=blue;fg=white> INFO </> INIT COMPARING DBs ORIGIN WITH DESTINY...');
        foreach ($statusInfo as $status) {


            $originsCount = BaseOV::where('numStat', $status)->where('ultimoStatus', 1)->count();
            $destiniesCount = Note::where('nstats', $status)->where('type_note', 2)->count();

            if ($originsCount < $destiniesCount) {

                $this->info('<bg=red;fg=yellow> FAIL </> <fg=yellow;options=underscore;options=bold> INTEGRITY ERROR IN STATUS ' . $status . ' ORIGIN: ' . $originsCount . ' DESTINY: ' . $destiniesCount . ' </>');

                $extraNotes['status'][] = $status;

                Note::where('nstats', $status)->where('type_note', 2)->chunk(1000, function ($notes) use (&$extraNotes, &$status) {
                    $chkOrigins = BaseOV::whereIn('OV', $notes->pluck('note')->toArray())->where('ultimoStatus', 1)->get();

                    if ($chkOrigins->count() < $notes->count()) {

                        $this->comment('<bg=yellow;fg=black> ANALYSIS </> GETTING DIFFERENCES IN STATUS ' . $status);

                        $chkOvs = $chkOrigins->pluck('OV')->toArray();
                        $chkNotes = $notes->pluck('note')->toArray();

                        $extraNotes['status'][]['notes'][] = array_diff($chkNotes, $chkOvs);
                    }
                });

            } else {
                $this->info('<bg=green;fg=white> DONE </> <fg=white;options=bold> INTEGRITY OK IN STATUS </> <fg=yellow;options=bold>' . $status . ' </>');
            }
        }

        $this->info('<bg=blue;fg=white> *** FINISHED ***</>  FIX BASE CHECK HAS FINISHED.');

        print_r($extraNotes);
    }
}
