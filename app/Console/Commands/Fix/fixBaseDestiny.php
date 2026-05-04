<?php

namespace App\Console\Commands\Fix;

use App\Models\Edp_depc\BaseOV;
use App\Models\Note;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class fixBaseDestiny extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:fix_destinyBase {--status=} {--ov=}';

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
        $status = $this->option('status');
        $ov = $this->option('ov');

        if ($status) {
            // Do something if 'status' exists
            $origins = BaseOV::where('numStat', $status)->where('ultimoStatus', 1)->get();
            $destinys = Note::where('nstats', $status)->where('type_note', 2)->get();

            $originValues = $origins->pluck('OV')->toArray();
            $destinyValues = $destinys->pluck('note')->toArray();

            if (count($originValues) > count($destinyValues)) {
                $missingInDestiny = array_diff($originValues, $destinyValues);
                $this->info("Values in origins but not in destinys: " . implode(', ', $missingInDestiny));
            } else {
                $missingInOrigin = array_diff($destinyValues, $originValues);
                $this->info("Values in destinys but not in origins: " . implode(', ', $missingInOrigin));
            }
        }

        if ($ov) {


            $this->info("Updating OV: {$ov}");

            $origin = BaseOV::where('OV', $ov)->where('ultimoStatus', 1)->first();

            if (!$origin) {
                $this->info('OV não encontrada na base de origem. Impossível atualizar informação.');
                return;
            }


            $destiny = Note::where('note', $ov)->where('type_note', 2)->first();

            try {
                $destiny->update([
                    'created_by'    => $origin->criadoPor,
                    'dt_created'    => "{$origin->dtCriacao} {$origin->hrCriacao}",
                    'dt_status'     => $origin->dhStat,
                    'user'          => $origin->usuario,
                    'value'         => $origin->valorLiq,
                    'currency'      => $origin->moeda,
                    'eq_venda'      => $origin->eqVenda,
                    'numPedido'     => $origin->numPedido,
                    'client'        => $origin->emissorOV,
                    'group1'        => $origin->grpCliente1,
                    'group2'        => $origin->grpCliente2,
                    'group3'        => $origin->grpCliente3,
                    'group4'        => $origin->grpCliente4,
                    'group5'        => $origin->grpCliente5,
                    'pze'           => $origin->PzE,
                    'num_material'  => $origin->numMaterial,
                    'material'      => $origin->material,
                    'nexp'          => $origin->numExp,
                    'lexp'          => $origin->localExp ?? $destiny->lexp,
                    'pep'           => $origin->PEP,
                    'nstats'        => $origin->numStat,
                    'status'        => $origin->status,
                    'days'          => $origin->dias,
                    'transaction'   => $origin->transicao,
                    'validar_prazo' => $origin->considerarPrazo,
                    'rubrica'       => $origin->rubrica,
                    'pze_tratado'   => $origin->PzETratado,
                    'days_stat'     => $origin->diasNoStatus,
                    'pze_parecer'   => $origin->parecerPrazo,
                    'days_left'     => $origin->diasPVencimento,
                    'type_note'     => 2,
                ]);

                $this->info("OV: {$ov} updated successfully.");
            } catch (\Throwable $th) {
                $this->error("Error updating OV: {$ov}");
                $this->error($th->getMessage());
            }
        }
    }

}
