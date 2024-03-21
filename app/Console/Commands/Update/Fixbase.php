<?php

namespace App\Console\Commands\Update;

use App\Models\Edp_depc\BaseOV;
use App\Models\{HistoricNote, Note};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

class Fixbase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:fixbase_ov {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Try Fix Base OV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('INIT TRY FIX BD');
        $this->info('CHECKIN SIZE REGISTERS ORIGINS WITH DESTINY...');
        $origin         = BaseOV::where('ultimoStatus', 1)->count();
        $destiny        = Note::where('type_note', 2)->count();
        $allDifferences = new Collection();
        $progressBar    = new ProgressBar($this->output);

        $this->comment('ORIGIN DATABASE HAS LOSED INFORMATIONS.... has ' . $origin - $destiny . ' registers.');

        $this->info('ORIGIN DB DONE...');

        $fix = false;

        if ($origin < $destiny) {
            $this->comment('ORIGIN DATABASE HAS LOSED INFORMATIONS.... has ' . $origin - $destiny . ' registers.');
            $fix = true;
        } elseif ($origin > $destiny) {
            $this->info('ORIGINS HAS SIZE OK');

            $fix = true;
        } elseif ($origin == $destiny) {
            $this->info('NO HAS DIFERENCE SIZE OK');
        }

        if ($fix || $this->option('force')) {
            $status = BaseOV::Where('ultimoStatus', 1)
                ->select('numStat', DB::raw('count(*) as count'))
                ->groupBy('numStat')
                ->get();

            $filteredRecords = [];

            foreach ($status as $stat) {

                if ((int) $stat->count <= 50000) {
                    $destiny = Note::where('nstats', $stat->numStat)->where('type_note', 2)->get();

                    if ((int) $stat->count != $destiny->count()) {
                        $this->comment("ORIGINS STATUS ($stat->numStat) NOT OK (o: " . (int) $stat->count . '/d:' . (int) $destiny->count() . ')');
                        $this->info('READING ORIGINS....');
                        $origins = BaseOV::where('numStat', $stat->numStat)->where('ultimoStatus', 1)->get();
                        $this->info('READING DONE....');
                        $this->info('COMPARING....');

                        // $progressBar = new ProgressBar($this->output);
                        // $progressBar->start($origins->count());

                        // $filteredRecords = $destiny->filter(function ($record) use (&$origins, &$progressBar) {
                        //     $progressBar->advance();
                        //     return !$origins->pluck('note')->contains($record->note);
                        // });

                        // dd($origins->pluck('OV')->toArray(), $destiny->pluck('note')->toArray());

                        $this->info('Sizing Origem: ' . count($origins->pluck('OV')->toArray()) . ' Destino: ' . count($destiny->pluck('note')->toArray()));
                        $origem  = $origins->pluck('OV')->toArray();
                        $destino = $destiny->pluck('note')->toArray();

                        if (!empty($origem) && !empty($destino)) {
                            if (count($origem) > count($destino)) {
                                $filteredRecords = array_diff($origem, $destino);
                            } else {
                                $filteredRecords = array_diff($destino, $origem);
                            }
                        } else {
                            $this->info('Erro....');
                        }

                        $this->info('COMPARING DONE....');
                        $this->info('DIFF FOUNDS ' . count($filteredRecords));

                    } else {
                        $this->info("ORIGINS STATUS ($stat->numStat) HAS OK");
                    }
                }

            }

            $this->info('CHECANDO DIFERENÇAS... ' . count($filteredRecords));

            if ($allDifferences->count()) {
                $this->info('UPDATING (' . count($filteredRecords) . ') REGISTERS');

                // $this->update($allDifferences);
                foreach ($filteredRecords as $key => $value) {
                    echo $value . "\n";
                }
            }
        }

    }

    public function update($allDifferences)
    {
        $this->info('UPDATING... ');
        $progressBar = new ProgressBar($this->output);
        $progressBar->start($allDifferences->count());

        $notFound = [];

        foreach ($allDifferences as $diference) {
            $record         = BaseOv::Where('OV', $diference->note)->orderBy('dhStat', 'DESC')->first();
            $existingRecord = false;

            if ($record) {
                $existingRecord = Note::Where('note', $record->OV)->first();
            } else {
                $this->comment($diference->note);
                $notFound[] = $diference->note;
            }

            if ($existingRecord && $record) {
                $atualizar = false;

                if (Carbon::parse($record->dhStat)->greaterThanOrEqualTo(Carbon::parse($existingRecord->dt_status))) {
                    $atualizar = true;
                }

                if ($atualizar) {

                    if ($existingRecord->nstats != $record->numStat) {
                        $historic = [
                            'note_id'  => $existingRecord->id,
                            'old_date' => $existingRecord->dt_status,
                            'old_stat' => $existingRecord->nstats,
                            'new_date' => $record->dhStat,
                            'new_stat' => $record->numStat,
                        ];
                    }

                    try {
                        $chk = $existingRecord->update([
                            'created_by'    => $record->criadoPor,
                            'dt_created'    => "{$record->dtCriacao} {$record->hrCriacao}",
                            'dt_status'     => $record->dhStat,
                            'user'          => $record->usuario,
                            'value'         => $record->valorLiq,
                            'currency'      => $record->moeda,
                            'eq_venda'      => $record->eqVenda,
                            'numPedido'     => $record->numPedido,
                            'client'        => $record->emissorOV,
                            'group1'        => $record->grpCliente1,
                            'group2'        => $record->grpCliente2,
                            'group3'        => $record->grpCliente3,
                            'group4'        => $record->grpCliente4,
                            'group5'        => $record->grpCliente5,
                            'pze'           => $record->PzE,
                            'num_material'  => $record->numMaterial,
                            'material'      => $record->material,
                            'nexp'          => $record->numExp,
                            'lexp'          => $record->localExp,
                            'pep'           => $record->PEP,
                            'nstats'        => $record->numStat,
                            'status'        => $record->status,
                            'days'          => $record->dias,
                            'transaction'   => $record->transicao,
                            'validar_prazo' => $record->considerarPrazo,
                            'rubrica'       => $record->rubrica,
                            'pze_tratado'   => $record->PzETratado,
                            'days_stat'     => $record->diasNoStatus,
                            'pze_parecer'   => $record->parecerPrazo,
                            'days_left'     => $record->diasPVencimento,
                        ]);

                        if ($chk) {

                            if ($historic) {
                                HistoricNote::create($historic);
                                $historic = [];
                            }
                        }

                    } catch (\Throwable $th) {
                        dd($th->getMessage());
                    }

                }
            }

            $progressBar->advance();
        }
        $progressBar->finish();
    }
}
