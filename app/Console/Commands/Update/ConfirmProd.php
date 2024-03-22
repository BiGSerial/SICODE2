<?php

namespace App\Console\Commands\Update;

use App\Models\Edp_depc\BaseEP;
use App\Models\Edp_depc\BaseOV;
use App\Models\Manualconfirm;
use App\Models\Notify;
use App\Models\Production;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class ConfirmProd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:confirm_prod';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Status Change Note with Production';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $prazo = 20;
        $this->info("CHECKING PRODS COMPLETEDS FROM BASE ... ");

        $productions = Production::where('completed', true)->where('noinconsistency', false)->where('confirmed', false)->with('Note', 'Service', 'User')->get();

        // $progressBar = new ProgressBar($this->output);

        if ($productions->count()) {
            // $progressBar->setFormat('%current%/%max% [%bar%] %percent%% %elapsed:6s%/%estimated:-6s% %message%');

            // $progressBar->start($productions->count());

            $this->info("INITIALIZING UPDATE... " . $productions->count());
            // $progressBar->setMessage('Verifing...');

            foreach ($productions as $production) {

                // Confirma Nota/OV por expiração de prazo de verificação.
                if (Carbon::parse($production->completed_at)->diffInDays(Carbon::now()) >= 1) {
                    $production->update(['confirmed' => true, 'confirmed_at' => date('Y-m-d H:i:s')]);

                    if ($production->User->bypassprod) {
                        $production->update(['noinconsistency' => true]);
                    }

                    $this->info('<bg=blue;fg=white> DONE </> <fg=white;options=bold> NOTE/OV CONFIRMED BY EXPIRATION </> <fg=yellow;options=bold>'.$production->Note->note.' </>');
                } elseif (Carbon::parse($production->completed_at)->diffInDays(Carbon::now()) >= 5) {
                    $production->update(['conf_manual' => true]);
                }


                if ($production->Note->type_note == 2) {

                    $verificar = BaseOV::where('OV', $production->Note->note)
                    ->where(function ($q) use ($production) {
                        return $q->where('transicao', 'LIKE', $production->status_note.' para%')
                                ->orWhere('transicao', 'LIKE', $production->Service->status.' para%');
                    })
                    ->orderBy('dhStat', 'DESC')
                    ->get();



                    if ($verificar->count()) {

                        $ok = false;

                        $this->info('<bg=blue;fg=white> COMPARING </> <fg=yellow;options=bold>'.$verificar->count().' FOUNDERED REGISTERS </>');


                        foreach ($verificar as $verificando) {

                            $completedAt = Carbon::parse($production->completed_at);
                            $dhStat = Carbon::parse($verificando->dhStat);
                            $diferencaEmDias = $completedAt->diffInDays($dhStat);


                            if (($diferencaEmDias >= -2 && $diferencaEmDias <= 2)) {
                                $ok = true;
                            }

                        }

                        if ($ok || $production->User->bypassprod) {

                            $production->update(['noinconsistency' => true]);

                            if (!$production->confirmed) {
                                $production->update(['confirmed' => true, 'confirmed_at' => date('Y-m-d H:i:s')]);
                            }
                            $this->info('<bg=green;fg=white> DONE </> <fg=white;options=bold> OV CONFIRMED </> <fg=yellow;options=bold>'.$production->Note->note.' </>');
                        }


                    }

                    if ($production->Note->type_note == 1) {

                        $verificar = BaseEP::where('nota', $production->Note->note)->first();

                        if ($production->User->bypassprod || ($verificar && ($verificar->statusUsuario && $production->status_note)) || (($verificar && $production->centroTrab) && ($production->centroTrab != $verificar->cenTrabResp))) {

                            $production->update(['noinconsistency' => true]);

                            if (!$production->confirmed) {

                                $production->update(['confirmed' => true, 'confirmed_at' => date('Y-m-d H:i:s')]);
                            }

                            $this->info('<bg=green;fg=white> DONE </> <fg=white;options=bold> NOTE CONFIRMED </> <fg=yellow;options=bold>'.$production->Note->note.' </>');
                        }
                    }


                }


            }



            $this->info("FINISHED CHECK... ".Production::where('completed', true)->where('confirmed', false)->with('Note', 'Service')->count());
        }
    }
}
