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
use Illuminate\Support\Facades\DB;
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

        $canUseUpsert = $this->canUseSafeUpsert();

        // --- ETAPA 1: Processamento Principal em Lotes ---
        // Lemos a tabela de origem em pedaços para manter o uso de memória baixo.
        $total = Edp_depcBaseEP::query()->count();
        $bar = $this->createProgressBar($total);
        $bar->start();

        $chunkReadSize = 2000; // Tamanho do lote para ler da origem
        $upsertBatchSize = 500; // Lotes de escrita. 500 é um número muito seguro.
        $updateColumns = [ 'created_by', 'dt_created', 'dt_status', 'user', 'numPedido', 'pze', 'num_material', 'material', 'nexp', 'lexp', 'nstats', 'status', 'rubrica', 'centerjob', 'type_note', 'mesalization', 'txpriority', 'updated_at' ];

        Edp_depcBaseEP::query()->orderBy('id')->chunkById($chunkReadSize, function (Collection $sourceRecords) use ($bar, $upsertBatchSize, $updateColumns, $canUseUpsert, &$createdCount, &$updatedCount) {

            // Dentro de cada lote, pegamos apenas as notas e cidades necessárias.
            // O whereIn aqui terá no máximo o tamanho de $chunkReadSize (2000), o que é seguro.
            $sourceRecordsByNote = $sourceRecords
                ->filter(fn ($record) => $this->normalizeNote($record->nota ?? null) !== null)
                ->keyBy(fn ($record) => $this->normalizeNote($record->nota));

            $notasInChunk = $sourceRecordsByNote->keys();
            $grpPlansInChunk = $sourceRecords->pluck('grpPlan')->unique()->filter();

            $existingNotes = Note::whereIn('note', $notasInChunk)
                ->orderBy('id')
                ->get()
                ->groupBy('note')
                ->map(fn ($notes) => $notes->first());
            $cities = City::whereIn('gpm', $grpPlansInChunk)->get()->keyBy('gpm');
            $dataToUpsert = [];

            foreach ($sourceRecordsByNote as $record) {
                $nota = $this->normalizeNote($record->nota);
                $existing = $existingNotes->get($nota);

                $row = $this->buildNoteRow($record, $nota, $cities->get($record->grpPlan), $existing);

                if (!$existing || $this->option('full') || $this->noteChanged($existing, $row)) {
                    if ($existing) {
                        $updatedCount++;
                    } else {
                        $createdCount++;
                    }
                    $row['_existing_id'] = $existing?->id;
                    $dataToUpsert[$nota] = $row;
                }
                $bar->advance();

                if (count($dataToUpsert) >= $upsertBatchSize) {
                    $this->persistNoteRows(array_values($dataToUpsert), $updateColumns, $canUseUpsert);
                    $dataToUpsert = [];
                }
            }

            if (!empty($dataToUpsert)) {
                $this->persistNoteRows(array_values($dataToUpsert), $updateColumns, $canUseUpsert);
            }
        });

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

    private function canUseSafeUpsert(): bool
    {
        if (!$this->indexExists('notes', 'notes_note_unique')) {
            $this->warn('Blindagem: notes_note_unique nao existe. O comando usara escrita manual por id para nao duplicar notas EP.');

            return false;
        }

        $duplicateGroups = Note::query()
            ->select('note')
            ->groupBy('note')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($duplicateGroups) {
            $this->warn('Blindagem: existem notas duplicadas em notes.note. O comando atualizara apenas o registro canonico de menor id e nao usara upsert.');

            return false;
        }

        return true;
    }

    private function persistNoteRows(array $rows, array $updateColumns, bool $canUseUpsert): void
    {
        if (empty($rows)) {
            return;
        }

        if ($canUseUpsert) {
            Note::upsert(
                array_map(fn (array $row) => $this->withoutInternalColumns($row), $rows),
                ['note'],
                $updateColumns
            );

            return;
        }

        foreach ($rows as $row) {
            $existingId = $row['_existing_id'] ?? null;
            $row = $this->withoutInternalColumns($row);

            if ($existingId) {
                $updates = [];
                foreach ($updateColumns as $column) {
                    $updates[$column] = $row[$column] ?? null;
                }

                Note::whereKey($existingId)->update($updates);
                continue;
            }

            Note::create($row);
        }
    }

    private function withoutInternalColumns(array $row): array
    {
        unset($row['_existing_id']);

        return $row;
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function normalizeNote(mixed $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : $note;
    }

    private function buildNoteRow(object $record, string $nota, ?City $city, ?Note $existing): array
    {
        return [
            'note' => $nota,
            'created_by' => $record->criadoPor,
            'dt_created' => $this->parseSourceDate($record->dtNota),
            'dt_status' => now(),
            'user' => $record->notificador,
            'numPedido' => $record->descricao,
            'pze' => $record->PzE ?: null,
            'num_material' => $record->conjunto ?: null,
            'material' => $record->denomConjunto ?: null,
            'nexp' => $city->rdMunicipio ?? null,
            'lexp' => $city->cidade ?? null,
            'nstats' => $record->statusUsuario,
            'status' => $record->status,
            'rubrica' => $record->rubrica,
            'centerjob' => $record->cenTrabResp,
            'type_note' => 1,
            'mesalization' => $record->mensalizacao,
            'txpriority' => $record->txtPrioridade,
            'created_at' => $existing->created_at ?? now(),
            'updated_at' => now(),
        ];
    }

    private function parseSourceDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay()->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    private function noteChanged(Note $existing, array $incoming): bool
    {
        $columns = [
            'created_by',
            'dt_created',
            'user',
            'numPedido',
            'pze',
            'num_material',
            'material',
            'nexp',
            'lexp',
            'nstats',
            'status',
            'rubrica',
            'centerjob',
            'type_note',
            'mesalization',
            'txpriority',
        ];

        foreach ($columns as $column) {
            if ($this->comparableValue($existing->getRawOriginal($column)) !== $this->comparableValue($incoming[$column] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function comparableValue(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
