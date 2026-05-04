<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\User;
use App\Models\Viability;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Throwable;

class TacitLogic extends Command
{
    use ShowsProgress;

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
        $log = null;

        try {
            $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();

        // dd($sevenDaysAgo, $sevenDaysAgo->copy()->subDays(-10));


            $viabilitiesToUpdate = Viability::where('sended_at', '<', $sevenDaysAgo)
                ->where('tacit', false)
                ->where('rejected', false)
                ->where('approved', false)
                ->get();

            $log = new RegistroJson('check_tacit', $this->options());
            $log->setTotal($viabilitiesToUpdate->count());
            $updatedCount = 0;

            $progressBar = $this->createProgressBar($viabilitiesToUpdate->count());
            $progressBar->setFormat('<bg=blue;fg=white;options=bold> %current%/%max% </><fg=white;options=bold> <fg=green;options=bold> [%bar%] </> %percent%%');



            $progressBar->start();

            // return;

            foreach ($viabilitiesToUpdate as $viability) {



                $adjustedSevenDaysAgo = $sevenDaysAgo->copy()->subDays($viability->Days->sum('days'))->startOfDay();
                $totalDays = $viability->Days->sum('days') + 7;

                if ($viability->sended_at < $adjustedSevenDaysAgo) {

                    $viability->update([
                        'tacit' => true,
                        'tacit_at' => date('Y-m-d H:i:s'),
                        'completed' => $viability->hired ? true : false,
                        'completed_at' => $viability->hired ? date('Y-m-d H:i:s') : null,
                        'status' => $viability->hired ? 9 : 15,
                        'approved' => true,
                    ]);
                    $updatedCount++;

                    $viability->Comments()->create([
                        'user_id' => User::first()->id,
                        'message' => '>> OBRA LIBERADA PARA CONTRATAÇÃO TÁCITA DEVIDO EXPIRAÇÃO DO PRAZO ESTIPULADO DE RETORNO DA PARCEIRA. (System) <<',
                    ]);

                }

                $progressBar->advance();
            }

            $progressBar->finish();

            // Fix Tacit
            $fixTacits = Viability::Where('tacit', true)->where(function ($q) {
                $q->where('status', '!=', 9)
                ->orWhere('status', '!=', 15);
            })->get();

            if ($fixTacits->isNotEmpty()) {
                foreach ($fixTacits as $fix) {
                    $fix->update([
                        'tacit' => $fix->returned_at ? false : true,
                        'completed' => $fix->hired ? true : false,
                        'completed_at' => $fix->hired ? Carbon::parse($fix->sended_at)->addDays(7) : null,
                        'status' => $fix->hired ? 9 : ($fix->returned_at ? 14 : 15),
                        'approved' => true,
                        'rejected' => false,
                    ]);
                    $updatedCount++;
                }
            }


            // Corrigir Status Contratados e Viavéis
            $fixHireds = Viability::where('hired', true)->where('approved', true)->where('completed', false)->get();

            if ($fixHireds) {
                foreach ($fixHireds as $fixHired) {
                    $fixHired->update([
                        'completed' => true,
                        'completed_at' => date('Y-m-d H:i:s'),
                        'status' => 9,
                    ]);
                    $updatedCount++;
                }
            }

            $log->setUpdated($updatedCount);
            $log->save();

            return self::SUCCESS;
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            return self::FAILURE;
        }

    }

}
