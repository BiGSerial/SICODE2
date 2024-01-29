<?php

namespace App\Console\Commands\Update;

use App\Models\Edp_depc\Wpaupdstatus;
use App\Models\Wpa;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class Wpaupd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_wpa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update WPA status from SQL Base';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $wpas = Wpaupdstatus::orderBy('production_id')->get();

        $progressBar = new ProgressBar($this->output, $wpas->count());

        $progressBar->setFormat('<bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s%');

        $progressBar->start();

        foreach ($wpas as $wpa) {
            if ($prod_wpa = Wpa::where('production_id', $wpa->production_id)->get()->last()) {
                $prod_wpa->update([
                    'sector' => $wpa->SectorId,
                    'stats' =>  $wpa->statusNota,
                    'execstats' =>  $wpa->statusExec,
                    'lat' => $wpa->Latitude,
                    'long' =>  $wpa->Longitude,
                    'issue_at' => $wpa->IssueDate,
                    'completed_at' => $wpa->ConclusionDate,
                ]);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
