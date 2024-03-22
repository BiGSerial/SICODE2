<?php

namespace App\Console\Commands\Fix;

use App\Models\Edp_depc\BaseOV;
use App\Models\Note;
use Illuminate\Console\Command;
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
        $output = new ConsoleOutput();

        $listStatus = BaseOV::select('numStat')
            ->orderBy('numStat')
            ->distinct()
            ->get()
            ->pluck('numStat')
            ->toArray();

        if (count($listStatus)) {

            $progressBar = new ProgressBar($output, count($listStatus));
            $progressBar->setFormat(" %current%/%max% [%bar%] %percent:3s%% %extra%\n %process% %message%");

            $toFix = [];
            $noteToFix = [];

            $progressBar->setMessage('<bg=blue;fg=white> INFO </> <fg=white;options=bold>CHECKING INTEGRITY DB...</>', 'process');
            $progressBar->setMessage("<fg=white;options=bold>[0/0]</>", 'extra');

            $progressBar->start();

            foreach ($listStatus as $status) {
                $origin = BaseOV::where('numStat', $status)->where('ultimoStatus', 1)->count();
                $destiny = Note::where('nstats', $status)->where('type_note', 2)->count();

                if ($origin != $destiny) {
                    $diff = $origin - $destiny;

                    $progressBar->setMessage("<bg=red;fg=white> FAIL </> <fg=white;options=bold>INCONSISTENCY... O: {$origin} => D: {$destiny} | DIFF: {$diff}</>", 'message');

                    $toFix[] = [
                        'status' => $status,
                        'origin' => $origin,
                        'destiny' => $destiny,
                        'diff' => $origin - $destiny,
                        'force' => false,
                    ];
                } else {
                    $progressBar->setMessage('<bg=green;fg=white> DONE </> <fg=white;options=bold>STATUS OK: </>'.$status);
                }

                $progressBar->advance();
            }

            if (count($toFix)) {
                $progressBar->setMessage('<bg=blue;fg=white> INFO </> <fg=white;options=bold>LOOKING FOR FIX: </>', 'process');
                $progressBar->start(count($toFix));

                foreach ($toFix as $fix) {
                    $conta_nota = 0;
                    $total = $fix['diff'] + count($noteToFix);

                    BaseOV::where('numStat', $fix['status'])
                        ->where('ultimoStatus', 1)
                        ->chunk(10000, function ($origin) use ($fix, &$noteToFix, &$progressBar, &$conta_nota, $total) {
                            $conta_nota += $origin->count();
                            $progressBar->setMessage("<fg=white;options=bold>STATUS </>".$fix['status'], 'message');

                            $diff = "";
                            $origin_c = $origin->pluck('OV')->toArray();
                            $destiny_c = Note::whereIn('note', $origin_c)->where('nstats', $fix['status'])->get()->pluck('note')->toArray();

                            if (count($origin_c) != count($destiny_c)) {

                                if (count($origin_c) > count($destiny_c)) {
                                    $diff = array_diff($destiny_c, $origin_c);
                                } else {
                                    $diff = array_diff($origin_c, $destiny_c);
                                }

                            }

                            if ($diff) {

                                if (is_array($diff)) {

                                    foreach ($diff as $differ) {
                                        $noteToFix[] = $differ;
                                    }

                                } else {

                                    $noteToFix[] = $diff;

                                }
                            }

                            $count = count($noteToFix);
                            $progressBar->setMessage("<fg=white;options=bold>[{$count}/{$total}][Count:{$conta_nota}/{$fix['origin']}]</>", 'extra');
                            $progressBar->display();

                            if ($count >= $total) {
                                return false;
                            }
                        });

                    $progressBar->advance();
                }
            }

            if (count($noteToFix)) {
                $progressBar->setMessage("<bg=red;fg=white> WORKING </><fg=white;options=bold> FIXING DATABASE WAITING FOR...</>", 'extra');
                $progressBar->setMessage("", 'message');
                $progressBar->display();

                $noteToFix = array_unique($noteToFix);

                $origins = BaseOV::whereIn('OV', $noteToFix)->where('ultimoStatus', 1)->get();

                $progressBar->start(count($noteToFix));

                if ($origins) {
                    foreach ($origins as $origin) {
                        $chk = Note::updateOrCreate(
                            ['note' => $origin->OV],
                            [
                                'created_by' => $origin->criadoPor,
                                'dt_created' => "{$origin->dtCriacao} {$origin->hrCriacao}",
                                'dt_status' => $origin->dhStat,
                                'user' => $origin->usuario,
                                'value' => $origin->valorLiq,
                                'currency' => $origin->moeda,
                                'eq_venda' => $origin->eqVenda,
                                'numPedido' => $origin->numPedido,
                                'client' => $origin->emissorOV,
                                'group1' => $origin->grpCliente1,
                                'group2' => $origin->grpCliente2,
                                'group3' => $origin->grpCliente3,
                                'group4' => $origin->grpCliente4,
                                'group5' => $origin->grpCliente5,
                                'pze' => $origin->PzE,
                                'num_material' => $origin->numMaterial,
                                'material' => $origin->material,
                                'nexp' => $origin->numExp,
                                'lexp' => $origin->localExp,
                                'pep' => $origin->PEP,
                                'nstats' => $origin->numStat,
                                'status' => $origin->status,
                                'days' => $origin->dias,
                                'transaction' => $origin->transicao,
                                'validar_prazo' => $origin->considerarPrazo,
                                'rubrica' => $origin->rubrica,
                                'pze_tratado' => $origin->PzETratado,
                                'days_stat' => $origin->diasNoStatus,
                                'pze_parecer' => $origin->parecerPrazo,
                                'days_left' => $origin->diasPVencimento,
                                'type_note' => 2,
                            ]
                        );

                        $progressBar->advance();
                    }
                }
            }

            $progressBar->finish();
        }
    }

}
