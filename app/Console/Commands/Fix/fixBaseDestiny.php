<?php

namespace App\Console\Commands\Fix;

use App\Models\Edp_depc\BaseOV;
use App\Models\Note;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

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

        $statsToFix = [];
        $ovToFix = [];

        $status = BaseOv::select('numStat')->distinct()->get()->pluck('numStat')->toArray();

        foreach ($status as $stat) {
            $count_o = BaseOV::where('numStat', $stat)->count();
            $count_n = Note::where('nstats', $stat)->count();
            $this->info('Status: ' . $stat . ' - BaseOV: ' . $count_o . ' - Note: ' . $count_n);



            if (($count_o < $count_n) && $stat < 98) {

                $statsToFix[] = $stat;
            }
        }

        if (count($statsToFix) > 0) {
            foreach ($statsToFix as $stat) {
                Note::where('nstats', $stat)->where('type_note', 2)->chunk(500, function ($notes) use (&$ovToFix, $stat) {
                    $origins = BaseOV::where('numStat', $stat)->where('ultimoStatus', 1)->get();

                    if ($origins->count() < 500) {
                        $originIds = $origins->pluck('OV')->toArray();
                        $notesToFix = $notes->filter(function ($note) use ($originIds) {
                            return !in_array($note->note, $originIds);
                        });

                        foreach ($notesToFix as $note) {
                            $ovToFix[] = $note;
                        }
                    }
                });
            }
        }


        if (count($ovToFix) > 0) {
            dd($ovToFix);
        } else {
            $this->info('Nothing to fix');
        }

    }

}
