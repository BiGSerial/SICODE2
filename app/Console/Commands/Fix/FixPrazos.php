<?php

namespace App\Console\Commands\Fix;

use App\Models\Edp_depc\BaseOV;
use App\Models\Note;
use Carbon\Carbon;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

class FixPrazos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:fix-prazos {--full} {--chk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tries fix days left general days notes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        system('clear');

        if (!$this->option('chk')) {
            $limit = 5;

            for ($i = 0; $i <= $limit ; $i++) {

                $Total = Note::when(!$this->option('full'), function ($q) {
                    return $q->where('nstats', '<', 98)->where('updated_at', '<', Carbon::today()->subHours(8));
                }, function ($q) {

                    return $q->where('nstats', '<', 98);
                })
                ->where('type_note', 2)
                ->count();

                if ($Total) {
                    $this->update_base($Total);
                } else {
                    $this->info("<bg=red;fg=yellow> FAIL </> <fg=yellow;options=underscore;options=bold> NO REGISTER ARE OUTDATED! </>");
                    break;
                }

                if ($this->option('full')) {

                    break;
                }
            }
        } else {
            $this->chk_prazos();
        }


    }

    public function update_base($total)
    {
        $progressBar = new ProgressBar($this->output, $total);

        $progressBar->setFormat('<bg=blue;fg=white>%current%/%max% </> [%tins%][E: %err% / U: %upd% / NE: %ne% / D: %dif%] [%bar%] %percent%% %elapsed:6s%/%estimated:-6s% %message%');

        $notes = [];
        $count = 0;
        $upd = 0;
        $err = 0;
        $ne = 0;
        $dif = 0;
        $lastId = 0;

        while ($destinos = Note::where('id', '>', $lastId)->when(!$this->option('full'), function ($q) {
            return $q->where('nstats', '<', 98)->where('updated_at', '<', Carbon::today()->subHours(2));
        }, function ($q) {
            return $q->where('nstats', '<', 98);
        })->where('type_note', 2)->orderBy('id')->take(1000)->get()) {

            $count++;
            $origens = BaseOV::where('ultimoStatus', 1)->whereIn('OV', $destinos->pluck('note'))->get();

            if ($destinos->last()) {
                $lastId = $destinos->last()->id;
            }

            if ($origens->count() != $destinos->count()) {

                if ($origens->count() > $destinos->count()) {
                    $notes[] = ($destinos->pluck('note'))->diff($origens->pluck('OV'));
                } else {
                    $notes[] = ($origens->pluck('OV'))->diff($destinos->pluck('note'));
                }
            }

            if ($origens->count()) {
                foreach ($origens as $origem) {

                    $update = $destinos->where('note', $origem->OV)->first();



                    if ($update) {

                        if ($update->nstats != $origem->numStat) {
                            $dif++;
                        }

                        DB::beginTransaction();

                        try {

                            $update->update([
                                'created_by' => $origem->criadoPor,
                                'dt_created' =>  "{$origem->dtCriacao} {$origem->hrCriacao}",
                                'dt_status' => $origem->dhStat,
                                'user' => $origem->usuario,
                                'value' => $origem->valorLiq,
                                'currency' => $origem->moeda,
                                'eq_venda' => $origem->eqVenda,
                                'numPedido' => $origem->numPedido,
                                'client' => $origem->emissorOV,
                                'group1' => $origem->grpCliente1,
                                'group2' => $origem->grpCliente2,
                                'group3' => $origem->grpCliente3,
                                'group4' => $origem->grpCliente4,
                                'group5' => $origem->grpCliente5,
                                'pze' => $origem->PzE,
                                'num_material' => $origem->numMaterial,
                                'material' => $origem->material,
                                'nexp' => $origem->numExp,
                                'lexp' => $origem->localExp,
                                'pep' => $origem->PEP,
                                'nstats' => $origem->numStat,
                                'status' => $origem->status,
                                'days' => $origem->dias,
                                'transaction' => $origem->transicao,
                                'validar_prazo' => $origem->considerarPrazo,
                                'rubrica' => $origem->rubrica,
                                'pze_tratado' => $origem->PzETratado,
                                'days_stat' => $origem->diasNoStatus,
                                'pze_parecer' => $origem->parecerPrazo,
                                'days_left' => $origem->diasPVencimento,
                            ]);


                            DB::commit();

                            $upd++;

                        } catch (\Exception $e) {
                            $err++;

                            DB::rollBack();
                            $this->error("Erro durante a atualização: {$e->getMessage()}");
                        }

                    } else {
                        $ne++;
                    }

                    $progressBar->setMessage($dif, 'dif');
                    $progressBar->setMessage($count, 'tins');
                    $progressBar->setMessage($err, 'err');
                    $progressBar->setMessage($upd, 'upd');
                    $progressBar->setMessage($ne, 'ne');
                    $progressBar->advance();
                }
            }
        }

        $progressBar->finish();

        $filePath = base_path('registroUpdate.json');

        if (!file_exists($filePath)) {

            $registroUpdate[] = [
                'tarefa' => 'Fix-Prazos',
                'options' => $this->option(),
                'total' => '',
                'updated' => $upd,
                'created' => '',
                'notupdated' => $ne,
                'erros' => '',
                'date' => date('Y-m-d H:i:s')
            ];



        } else {

            $registroUpdate = json_decode(file_get_contents($filePath), true);

            $registroUpdate[] = [
                'tarefa' => 'Fix-Prazos',
                'options' => $this->option(),
                'total' => '',
                'updated' => $upd,
                'created' => '',
                'notupdated' => $ne,
                'erros' => '',
                'date' => date('Y-m-d H:i:s')
            ];


        }

        $registroUpdate = array_filter($registroUpdate, function ($item) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $item['date']);
            return $date && $date->diff(new DateTime())->days <= 15;
        });

        file_put_contents($filePath, json_encode($registroUpdate));



    }

    public function chk_prazos()
    {
        $notes = Note::where('type_note', 2);
        $progressBar = new ProgressBar($this->output, $notes->count());

        $progressBar->setFormat('<bg=blue;fg=white>%current%/%max% </> [%tins%][E: %err% / NE: %ne%] [%bar%] %percent%% %elapsed:6s%/%estimated:-6s%');

        $count = 0;
        $err = 0;
        $ne = 0;
        $lastId = 0;

        $progressBar->start();

        while ($destinos = $notes->where('id', '>', $lastId)->orderBy('id')->take(1000)->get()) {
            $count++;
            $origens = BaseOV::where('ultimoStatus', 1)->whereIn('OV', $destinos->pluck('note'))->get();

            if ($destinos->last()) {
                $lastId = $destinos->last()->id;
            }

            if ($origens->count()) {
                foreach ($origens as $origem) {
                    $chk = $destinos->where('note', $origem->OV)->first();

                    if ($chk->days_left == $origem->diasPVencimento && $chk->pze_parecer == $origem->parecerPrazo) {
                        $ne++;
                    } else {
                        $err++;
                    }

                    $progressBar->setMessage($count, 'tins');
                    $progressBar->setMessage($err, 'err');
                    $progressBar->setMessage($ne, 'ne');
                    $progressBar->advance();
                }
            }


        }

        $progressBar->finish();


    }
}
