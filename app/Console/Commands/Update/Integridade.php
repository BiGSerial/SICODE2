<?php

namespace App\Console\Commands\Update;

use App\Models\Bancoupdate;
use App\Models\Edp_depc\BaseOV;
use App\Models\Note;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Calculation\LookupRef\Offset;
use Symfony\Component\Process\Process;

class Integridade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:chk_integridade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check integrity of database with states information';

    /**
     * Execute the console command.
     */
    public function handle()
    {


        $tries = 0;

        retry:

       // Executar o comando de limpar terminal
       if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
           // Se for Windows
           system('cls');
       } else {
           // Se for Unix (Linux, macOS)
           system('clear');
       }


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

        foreach ($origins as $origin) {

            $destiny = Note::Where('nstats', $origin->numStat)->where('type_note', 2)->count();


            if ($destiny && $origin->count != $destiny) {
                $this->info('<bg=red;fg=yellow> FAIL </> <fg=yellow;options=underscore;options=bold> INTEGRITY ERROR IN STATUS '.$origin->numStat.' ORIGIN: '. $origin->count .' DESTINY: '.$destiny.' </>');
                if ($origin->numStat < 98) {
                    $error++;
                } elseif ($tries <= 1) {
                    $error++;
                } elseif ($tries > 1) {
                    $this->comment('<bg=yellow;fg=black> WARNING </> IGNORING ERROR STATS EXISTS IN '.$origin->numStat);
                }
            } elseif ($destiny && $origin->count == $destiny) {
                $this->info('<bg=green;fg=white> DONE </> <fg=white;options=bold> INTEGRITY OK IN STATUS </> <fg=yellow;options=bold>'.$origin->numStat.' </>');
            } else {
                if ($origin->numStat < 98) {
                    $error++;
                } elseif ($tries <= 1) {
                    $error++;
                } elseif ($tries > 1) {
                    $this->comment('<bg=yellow;fg=black> WRNG </> IGNORING ERROR STATS EXISTS IN '.$origin->numStat);
                } else {
                    $this->comment('<bg=yellow;fg=black> WRNG </> INTEGRITY ERROR STATS NOT EXISTS IN '.$origin->numStat);
                }

            }
        }

        if ($error && $tries <= 3) {

            $days = 0;

            if ($tries === 1) {
                $days = 30;
            } elseif ($tries === 2) {
                $days = 90;
            } elseif ($tries === 3) {
                $this->call('sicode:upd_baseov', ['--full' => true]);
            }

            if ($days) {
                $this->call('sicode:upd_baseov', ['--days' => $days]);
            } else {
                $this->call('sicode:upd_baseov');
            }

            $tries++;
            goto retry;

        } elseif ($error) {
            $this->info('<bg=blue;fg=white> INFO </>  WE FOUNDED PROBLEMS WITH A INTEGRITY OF INFORMATION IN DATABASE, WE WILL TRY AGAIN LATER.');

            // Registra atualizações
            Bancoupdate::Create([
                'last_update' => date('Y-m-d H:i:s'),
                'error' => 1,
                'inserts' => -1,
                'updates' => -1
            ]);

        } else {
            $this->info('<bg=green;fg=white> DONE </> HASN´T INTEGRITY PROBLEMS WITH DATABASE INFORMATION.');

            $this->call('sicode:confirm-manual');

            $this->call('sicode:confirm_prod');

        }

        if (env('APP_ENV') === 'production') {
            $this->call('sicode:expurgo_sql_prod');
            $this->call('sicode:upd_wpa');
        }

    }
}
