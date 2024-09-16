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
        $sts_repair = [];
        $lostNote = [];

        // Executar o comando de limpar terminal
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Se for Windows
            system('cls');
        } else {
            // Se for Unix (Linux, macOS)
            system('clear');
        }

        system('cls');

        $this->info('<bg=blue;fg=white> INFO </> <fg=white;options=bold>CHEKING INTEGRITY DB...</>');

        // $status = Service::orderBy('status')->get();

        $this->info('<bg=blue;fg=white> INFO </>  READING ORIGIN DB...');

        $origins = BaseOV::Where('ultimoStatus', 1)
            ->select('numStat', DB::raw('count(*) as count'))
            ->groupBy('numStat')
            ->get();
        $this->info('<bg=green;fg=white> DONE </> ORIGIN DB DONE...');

        $error = 0;

        $this->info('<bg=blue;fg=white> INFO </> INIT COMPARING DBs ORIGIN WITH DESTINY...');

        $destinies = Note::Where('type_note', 2)
            ->select('nstats', DB::raw('count(*) as count'))
            ->groupBy('nstats')
            ->get();

        if ($origins && $destinies) {
            $this->info('<bg=blue;fg=white> INFO </> COMPARING DBs ...');
            foreach ($origins as $origin) {
                if ($destiny = $destinies->where('nstats', $origin->numStat)->first()) {
                    if ($origin->count != $destiny->count) {
                        $sts_repair[] = [
                            'status' => $origin->numStat,
                            'origin' => $origin->count,
                            'destiny' => $destiny->count
                        ];
                    }
                }
            }

            if (count($sts_repair)) {
                $this->info('<bg=blue;fg=white> INFO </> FOUND STATUS INCINSISTENCY ... '. count($sts_repair));
                unset($destinies);
                unset($origins);

                foreach ($sts_repair as $repair) {

                    if ($repair['status'] < 98 && BaseOv::where('numStat', $repair['status'])->Where('ultimoStatus', 1)->count() < 500) {
                        $ovs = BaseOv::Where('ultimoStatus', 1)->where('numStat', intval($repair['status']))->get()->pluck('OV')->toArray();



                        if ($ovs) {
                            $notes = Note::Where('type_note', 2)->where('nstats', $repair['status'])->pluck('note')->toArray();

                            $diff_notes = array_diff($notes, $ovs);

                            foreach ($diff_notes as $note) {
                                $lostNote[] = $note;
                            }

                            $diff_notes = array_diff($ovs, $notes);

                            foreach ($diff_notes as $note) {
                                $lostNote[] = $note;
                            }


                        }



                        $this->info('<bg=blue;fg=white> INFO </> FINISH STATUS ...'.$repair['status']);
                    } else {
                        $total = $repair['origin'] - $repair['destiny'];

                        if ($total < 0) {
                            $total *= -1;

                            Note::Where('type_note', 2)->where('nstats', $repair['status'])->chunk(500, function($destinies) use ($repair){
                                $origins = BaseOv::Where('ultimoStatus', 1)->where('numStat', $repair['status'])->wehreIn('OV', $destinies->pluck('note'))->get();

                                if ($origins->count() != $destinies->count()) {
                                    # code...
                                }

                            });
                        }
                    }

                }
            }

            if ($lostNote) {
                $this->info('<bg=blue;fg=white> INFO </> SHOWING MISSES NOTES ...');
                foreach ($lostNote as $note) {
                    $this->info('<bg=blue;fg=white> NOTE </> ' . $note);
                }
            } else {
                $this->info('<bg=blue;fg=white> INFO </> NADA ENCONTRADO ...');
            }
        }
    }

}
