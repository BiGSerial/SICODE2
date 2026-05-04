<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\City;
use App\Models\Edp_depc\BaseEP as Edp_depcBaseEP;
// use App\Models\Edp_depc\Gpm;
use App\Models\Note;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class BaseEP extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_baseEP {--full} {--days=7}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Table Notes with BaseEP SQL info';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $log = null;

        try {
        $this->info('Starting process with V5 - Robust, Memory-Efficient Strategy.');
        $log = new RegistroJson('upd_baseEP_v5', $this->options());
        $createdCount = 0;
        $updatedCount = 0;

        // --- ETAPA 1: Processamento Principal em Lotes ---
        // Lemos a tabela de origem em pedaços para manter o uso de memória baixo.
        $total = Edp_depcBaseEP::query()->count();
        $bar = $this->createProgressBar($total);
        $bar->start();

        $dataToUpsert = [];
        $chunkReadSize = 2000; // Tamanho do lote para ler da origem
        $upsertBatchSize = 500; // Lotes de escrita. 500 é um número muito seguro.
        $updateColumns = [ 'created_by', 'dt_created', 'dt_status', 'user', 'numPedido', 'pze', 'num_material', 'material', 'nexp', 'lexp', 'nstats', 'status', 'rubrica', 'centerjob', 'type_note', 'mesalization', 'txpriority', 'updated_at' ];

        Edp_depcBaseEP::query()->orderBy('id')->chunkById($chunkReadSize, function (Collection $sourceRecords) use ($bar, &$dataToUpsert, $upsertBatchSize, $updateColumns, &$createdCount, &$updatedCount) {

            // Dentro de cada lote, pegamos apenas as notas e cidades necessárias.
            // O whereIn aqui terá no máximo o tamanho de $chunkReadSize (2000), o que é seguro.
            $notasInChunk = $sourceRecords->pluck('nota')->unique();
            $grpPlansInChunk = $sourceRecords->pluck('grpPlan')->unique()->filter();

            $existingNotes = Note::whereIn('note', $notasInChunk)->get()->keyBy('note');
            $cities = City::whereIn('gpm', $grpPlansInChunk)->get()->keyBy('gpm');

            foreach ($sourceRecords as $record) {
                $nota = $record->nota;
                $existing = $existingNotes->get($nota);

                $modified = is_null($existing) || $this->option('full') || $existing->created_by !== $record->criadoPor || Carbon::parse($existing->dt_created)->toDateString() !== Carbon::parse($record->dtNota)->toDateString() || $existing->user !== $record->notificador || $existing->numPedido !== $record->descricao || $existing->pze != ($record->PzE ?: null) || $existing->num_material !== ($record->conjunto ?: null) || $existing->material !== ($record->denomConjunto ?: null) || $existing->nstats != $record->statusUsuario || $existing->status != $record->status || $existing->centerjob !== $record->cenTrabResp || $existing->mesalization !== $record->mensalizacao || $existing->txpriority !== $record->txtPrioridade;

                if ($modified) {
                    $city = $cities->get($record->grpPlan);
                    if ($existing) {
                        $updatedCount++;
                    } else {
                        $createdCount++;
                    }
                    $dataToUpsert[] = [ 'note' => $nota, 'created_by' => $record->criadoPor, 'dt_created' => "{$record->dtNota} 00:00:00", 'dt_status' => now(), 'user' => $record->notificador, 'numPedido' => $record->descricao, 'pze' => $record->PzE ?: null, 'num_material' => $record->conjunto ?: null, 'material' => $record->denomConjunto ?: null, 'nexp' => $city->rdMunicipio ?? null, 'lexp' => $city->cidade ?? null, 'nstats' => $record->statusUsuario, 'status' => $record->status, 'rubrica' => $record->rubrica, 'centerjob' => $record->cenTrabResp, 'type_note' => 1, 'mesalization' => $record->mensalizacao, 'txpriority' => $record->txtPrioridade, 'created_at' => $existing->created_at ?? now(), 'updated_at' => now() ];
                }
                $bar->advance();
            }

            if (count($dataToUpsert) >= $upsertBatchSize) {
                Note::upsert($dataToUpsert, ['note'], $updateColumns);
                $dataToUpsert = [];
            }
        });

        if (!empty($dataToUpsert)) {
            Note::upsert($dataToUpsert, ['note'], $updateColumns);
        }

        $bar->finish();
        $this->info("\nMain processing complete.");

        // --- ETAPA 2: Lógica de Cancelamento Segura e com Baixo Uso de Memória ---
        $this->info('Starting cancellation process...');

        $sourceNotas = Edp_depcBaseEP::query()->pluck('nota');
        $cancelCount = 0;
        $stale = Carbon::now()->subDays(2);

        Note::query()
            ->where('type_note', 1)
            ->where('updated_at', '<', $stale)
            ->whereNotIn('nstats', [99])
            ->select('id', 'note')
            ->chunkById(2000, function (Collection $localNotesChunk) use ($sourceNotas, &$cancelCount) {

                // Compara em PHP para não sobrecarregar o DB
                $notesToCancel = $localNotesChunk->pluck('note')->diff($sourceNotas);

                if ($notesToCancel->isNotEmpty()) {
                    // O whereIn aqui é no máximo do tamanho do chunk (2000), o que é seguro.
                    Note::whereIn('note', $notesToCancel)->update([
                        'nstats' => 99,
                        'centerjob' => 'LIMBO',
                    ]);
                    $cancelCount += $notesToCancel->count();
                }
            });

        $this->info("Cancellation complete. Cancelled notes: {$cancelCount}");
        $this->info('Process finished successfully.');
        $log->setCreated($createdCount);
        $log->setUpdated($updatedCount);
        $log->setNoteUpdated($cancelCount);
        $log->save();

        return 0;
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            return self::FAILURE;
        }
    }
}
