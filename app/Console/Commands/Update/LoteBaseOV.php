<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\Edp_depc\BaseOV;
use App\Models\{Bancoupdate, HistoricNote, Note};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class LoteBaseOV extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_baseov_lote';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update in Lote Table Notes with BaseOV Sql info';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $log = null;

        try {
            $DaysAgo      = date('Y-m-d 0:00:00', strtotime('-7 days'));
            $chunkSize    = 500;
            $totalRecords = BaseOV::where('ultimoStatus', 1)->where('dhStat', '>=', $DaysAgo)->count();
            $log = new RegistroJson('upd_baseOV_lote', $this->options(), $totalRecords);
            $totalInserts = ceil($totalRecords / $chunkSize);

            $progressBar = $this->createProgressBar($totalRecords);
            $progressBar->setFormat('%current%/%max% Chunks: [%tins%/%itotal%] [I: %ins%/U: %upd%] [%bar%] %percent%% %elapsed:6s%/%estimated:-6s% %message%');
            $progressBar->setMessage($totalInserts, 'itotal');
            $progressBar->setMessage('Inserting in bulk');
            $progressBar->start();

            $count = ['upd' => 0, 'ins' => 0, 'tins' => 0, 'errors' => 0];

            $progressBar->setMessage('Inserting in bulk');

            BaseOV::where('ultimoStatus', 1)->where('dhStat', '>=', $DaysAgo)->chunk($chunkSize, function ($records) use ($progressBar, &$count) {

            $recordsToInsert  = [];
            $recordsToUpdate  = [];
            $recordsToHistory = [];

            $progressBar->setMessage('Inserting in bulk');
            $notes = Note::WhereIn('note', $records->pluck('OV'))->get();

            foreach ($records as $record) {
                // $existingRecord = Note::where('note', $record->OV)->first();
                $existingRecord = $notes->where('note', $record->OV)->first();

                if ($existingRecord && strtotime($existingRecord->dt_status) < strtotime($record->dhStat)) {

                    if ($existingRecord->nstats != $record->numStat) {
                        $recordsToHistory[] = [
                            'note_id'  => $existingRecord->id,
                            'old_date' => $existingRecord->dt_status,
                            'old_stat' => $existingRecord->nstats,
                            'new_date' => $record->dhStat,
                            'new_stat' => $record->numStat,
                        ];
                        $recordsToUpdate[] = $this->prepareRecordForUpdate($record);
                        $count['upd']++;
                    }

                } elseif (!$existingRecord) {
                    $recordsToInsert[] = $this->prepareRecordForInsert($record);
                    $count['ins']++;
                }

                $progressBar->setMessage($count['upd'], 'upd');
                $progressBar->setMessage($count['ins'], 'ins');
                $progressBar->setMessage($count['tins'], 'tins');

                $progressBar->advance();
            }

            $count['tins']++;
            $progressBar->setMessage('Discharging bulk...');

            // Inserção em lote dos registros novos
            if (!empty($recordsToInsert)) {
                $chk = Note::insert($recordsToInsert);

                if (!$chk) {
                    $count['errors']++;
                }
            }

            // Atualização em lote dos registros existentes
            if (!empty($recordsToUpdate)) {
                // $noteIdsToUpdate = array_column($recordsToUpdate, 'note_id');
                // Note::whereIn('id', $noteIdsToUpdate)->update($recordsToUpdate);
                foreach ($recordsToUpdate as $recordToUpdate) {
                    $noteId = $recordToUpdate['note_id'];
                    unset($recordToUpdate['note_id']);
                    $chk = Note::where('id', $noteId)->update($recordToUpdate);

                    if (!$chk) {
                        $count['errors']++;
                    }
                }
            }

            // Inserção em lote dos registros de histórico
            if (!empty($recordsToHistory)) {
                HistoricNote::insert($recordsToHistory);
            }

            });

            $progressBar->finish();

        // Registra atualizações
            Bancoupdate::Create([
                'last_update' => date('Y-m-d H:i:s'),
                'error'       => $count['errors'],
                'inserts'     => $count['ins'],
                'updates'     => $count['upd'],
            ]);

            Bancoupdate::whereDate('created_at', '<', Carbon::now()->subDays(30))->delete();

            $this->info('Data transfer completed.');
            $log->setCreated($count['ins']);
            $log->setUpdated($count['upd']);
            if ($count['errors'] > 0) {
                $log->setErrorMessage("Erros durante processamento: {$count['errors']}");
            }
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

    private function prepareRecordForUpdate($record)
    {
        return [
            'note_id'       => $record->id,
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
        ];
    }

    private function prepareRecordForInsert($record)
    {
        return [
            'note'          => $record->OV,
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
        ];
    }
}
