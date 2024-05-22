<?php

namespace App\Console\Commands\Update;

use App\Models\User;
use App\Models\Viability;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Symfony\Component\Console\Helper\ProgressBar;

class TacitLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:check_tacit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Date verification a Tacit condition of Notes/OV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $viabilitiesToUpdate = Viability::whereNull('returned_at')
            ->where('sended_at', '<=', $sevenDaysAgo)
            ->where('rejected', false)
            ->where('approved', false)
            ->where('completed', false)
            ->get();

        if ($viabilitiesToUpdate) {

            $progressBar = new ProgressBar($this->output, $viabilitiesToUpdate->count());
            $progressBar->setFormat('<bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%% %elapsed:6s%/%estimated:-6s%');

            $progressBar->start();

            foreach ($viabilitiesToUpdate as $viability) {

                $viability->update([
                    'tacit' => true,
                    'tacit_at' => Carbon::now(),
                    'completed' => $viability->hired ? true : false,
                    'completed_at' => $viability->hired ? Carbon::now() : null,
                    'status' => $viability->hired ? 9 : 15,
                    'approved' => true,
                    'status' => $viability->hired ? 9 : 14,
                ]);

                $viability->Comments()->create([
                    'user_id' => User::first()->id,
                    'message' => '>> OBRA LIBERADA PARA CONTRATAÇÃO TÁCITA DEVIDO EXPIRAÇÃO DO PRAZO ESTIPULADO DE RETORNO DA PARCEIRA. (Systema) <<',
                ]);

                $progressBar->advance();
            }

            $progressBar->finish();

            dump($viabilitiesToUpdate);

            return;
        }

    }

}
