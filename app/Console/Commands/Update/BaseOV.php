<?php

namespace App\Console\Commands\Update;

use App\Custom\RegistroJson;
use App\Models\Bancoupdate;
use App\Models\Edp_depc\BaseOV as Edp_depcBaseOV;
use App\Models\HistoricNote;
use App\Models\Note;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Carbon\Carbon;
use DateTime;
use GrahamCampbell\ResultType\Success;

class BaseOV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_baseov {--full} {--prazos} {--force} {--days=7}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Table Notes with BaseOV Sql info';

    /**
     * Execute the console command.
     */
    public function handle()
    {


        $DaysAgo = Carbon::now()->subDays($this->option('days'));
        $chunkSize = 500;

        if ($this->option('full')) {
            $chunkSize = 1000;
        }



        $count = ['upd' => 0, 'ins' => 0, 'tins' => 1, 'errors' => 0];



        $totalRecords = Edp_depcBaseOV::where('ultimoStatus', 1)

                        ->when(!$this->option('full')  && !$this->option('prazos'), function ($q) use ($DaysAgo) {
                            return $q->whereDate('dhStat', '>=', $DaysAgo);
                        })
                        ->when($this->option('prazos'), function ($q) {
                            return $q->where('numStat', '<', 98);
                        })
                        ->count();

        // $totalRecords = Edp_depcBaseOV::where('ultimoStatus', 1)->count();
        $progressBar = new ProgressBar($this->output, $totalRecords);

        $progressBar->setFormat('%current%/%max% [%tins%][I: %ins%/U: %upd%] [%bar%] %percent%% %elapsed:6s%/%estimated:-6s% %message%');
        $progressBar->setMessage('Inserting in bulk');
        $progressBar->start($totalRecords);



        // Edp_depcBaseOV::where('ultimoStatus', 1)->chunk($chunkSize, function ($records) use ($progressBar, &$count) {
        Edp_depcBaseOV::where('ultimoStatus', 1)

                        ->when(!$this->option('full') && !$this->option('prazos'), function ($q) use ($DaysAgo) {
                            return $q->whereDate('dhStat', '>=', $DaysAgo);
                        })
                        ->when($this->option('prazos'), function ($q) {
                            return $q->where('numStat', '<', 98);
                        })
                        ->chunk($chunkSize, function ($records) use ($progressBar, &$count) {

                            $historic = null;

                            $notes = Note::whereIn('note', $records->pluck('OV'))->get();

                            foreach ($records as $record) {

                                $existingRecord = $notes->where('note', $record->OV)->first();

                                if ($existingRecord) {
                                    $atualizar = false;

                                    if(Carbon::parse($record->dhStat)->isAfter($existingRecord->dt_status)) {
                                        $atualizar = true;
                                    }

                                    if ($this->option('full')) {
                                        $atualizar = true;
                                    }

                                    if ($this->option('force')) {
                                        $atualizar = true;
                                    }

                                    if ($atualizar) {


                                        if ($existingRecord->nstats != $record->numStat) {
                                            $historic = [
                                                'note_id' => $existingRecord->id,
                                                'old_date' => $existingRecord->dt_status,
                                                'old_stat' => $existingRecord->nstats,
                                                'new_date' => $record->dhStat,
                                                'new_stat' => $record->numStat,
                                            ];
                                        }


                                        try {
                                            $chk = $existingRecord->update([
                                                'created_by' => $record->criadoPor,
                                                'dt_created' =>  "{$record->dtCriacao} {$record->hrCriacao}",
                                                'dt_status' => $record->dhStat,
                                                'user' => $record->usuario,
                                                'value' => $record->valorLiq,
                                                'currency' => $record->moeda,
                                                'eq_venda' => $record->eqVenda,
                                                'numPedido' => $record->numPedido,
                                                'client' => $record->emissorOV,
                                                'group1' => $record->grpCliente1,
                                                'group2' => $record->grpCliente2,
                                                'group3' => $record->grpCliente3,
                                                'group4' => $record->grpCliente4,
                                                'group5' => $record->grpCliente5,
                                                'pze' => $record->PzE,
                                                'num_material' => $record->numMaterial,
                                                'material' => $record->material,
                                                'nexp' => $record->numExp,
                                                'lexp' => $record->localExp ?? $existingRecord->lexp,
                                                'pep' => $record->PEP,
                                                'nstats' => $record->numStat,
                                                'status' => $record->status,
                                                'days' => $record->dias,
                                                'transaction' => $record->transicao,
                                                'validar_prazo' => $record->considerarPrazo,
                                                'rubrica' => $record->rubrica,
                                                'pze_tratado' => $record->PzETratado,
                                                'days_stat' => $record->diasNoStatus,
                                                'pze_parecer' => $record->parecerPrazo,
                                                'days_left' => $record->diasPVencimento,
                                                'type_note' => 2,
                                            ]);

                                            if ($chk) {
                                                $count['upd']++;

                                                if ($historic) {
                                                    HistoricNote::create($historic);
                                                    $historic = [];
                                                }
                                            }



                                        } catch (\Throwable $th) {
                                            dd($th->getMessage());
                                        }

                                    }

                                } elseif (!$existingRecord) {


                                    try {
                                        $chk = Note::create([
                                            'note' => $record->OV,
                                            'created_by' => $record->criadoPor,
                                            'dt_created' =>  "{$record->dtCriacao} {$record->hrCriacao}",
                                            'dt_status' => $record->dhStat,
                                            'user' => $record->usuario,
                                            'value' => $record->valorLiq,
                                            'currency' => $record->moeda,
                                            'eq_venda' => $record->eqVenda,
                                            'numPedido' => $record->numPedido,
                                            'client' => $record->emissorOV,
                                            'group1' => $record->grpCliente1,
                                            'group2' => $record->grpCliente2,
                                            'group3' => $record->grpCliente3,
                                            'group4' => $record->grpCliente4,
                                            'group5' => $record->grpCliente5,
                                            'pze' => $record->PzE,
                                            'num_material' => $record->numMaterial,
                                            'material' => $record->material,
                                            'nexp' => $record->numExp,
                                            'lexp' => $record->localExp ?? $existingRecord->lexp,
                                            'pep' => $record->PEP,
                                            'nstats' => $record->numStat,
                                            'status' => $record->status,
                                            'days' => $record->dias,
                                            'transaction' => $record->transicao,
                                            'validar_prazo' => $record->considerarPrazo,
                                            'rubrica' => $record->rubrica,
                                            'pze_tratado' => $record->PzETratado,
                                            'days_stat' => $record->diasNoStatus,
                                            'pze_parecer' => $record->parecerPrazo,
                                            'days_left' => $record->diasPVencimento,
                                            'type_note' => 2,
                                        ]);

                                        if ($chk) {
                                            $count['ins']++;
                                        }

                                    } catch (\Throwable $th) {
                                        dd($th->getMessage());
                                    }
                                }


                                $progressBar->setMessage($count['tins'], 'tins');
                                $progressBar->setMessage($count['upd'], 'upd');
                                $progressBar->setMessage($count['ins'], 'ins');
                                $progressBar->setMessage('Charging to memory');
                                $progressBar->advance();
                            }

                            $count['tins']++;
                        });

        // Registra atualizações
        Bancoupdate::Create([
            'last_update' => date('Y-m-d H:i:s'),
            'error' => $count['errors'],
            'inserts' => $count['ins'],
            'updates' => $count['upd']
        ]);

        Bancoupdate::whereDate('created_at', '<', Carbon::now()->subDays(30))->delete();

        $filePath = base_path('registroUpdate.json');

        if (!file_exists($filePath)) {

            $registroUpdate[] = [
                'tarefa' => 'BaseOV',
                'options' => $this->option(),
                'total' => $totalRecords,
                'updated' => $count['upd'],
                'created' => $count['ins'],
                'notupdated' => '',
                'erros' => $count['errors'],
                'date' => date('Y-m-d H:i:s')
            ];



        } else {

            $registroUpdate = json_decode(file_get_contents($filePath), true);

            $registroUpdate[] = [
                'tarefa' => 'BaseOV',
                'options' => $this->option(),
                'total' => $totalRecords,
                'updated' => $count['upd'],
                'created' => $count['ins'],
                'notupdated' => '',
                'erros' => $count['errors'],
                'date' => date('Y-m-d H:i:s')
            ];


        }

        $registroUpdate = array_filter($registroUpdate, function ($item) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $item['date']);
            return $date && $date->diff(new DateTime())->days <= 15;
        });



        file_put_contents($filePath, json_encode($registroUpdate));


        $progressBar->finish();
        $this->info('Data transfer completed.');

        return;
    }
}
