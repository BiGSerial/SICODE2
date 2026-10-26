<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\Edp_depc\{BaseOrder as Edp_depcBaseOrder};
use App\Models\{Note, Order};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BaseOrder extends Command
{
    use ShowsProgress;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sicode:upd_baseOrder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Base order from SQLSERVER.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $log = null;

        try {
            $this->info('Starting Orders upsert {note_id, ordem}…');

            // 1) CONTAGEM TOTAL (para o início do log)
            $total = Edp_depcBaseOrder::query()->count();

            // 2) INÍCIO DO LOG (apenas uma vez, no começo)
            $log = new RegistroJson('upd_baseOrders_v5', $this->options(), $total);
            // Se quiser mudar retenção:
            // $log->setPruneDays(5);

            // ---- processamento normal ----
            $chunkSize       = 2000;
            $upsertBatchSize = 1000;

            $updateColumns = [
                'descricao', 'locInstalacao', 'cenPlan', 'prioridade', 'statusSist', 'statusUser',
                'cenTrab', 'gpm', 'custPlanejado', 'custRealizado', 'modifPor', 'pep', 'conjunto',
                'denConjunto', 'dtEntrada', 'updated_at',
            ];

            $bar = $this->createProgressBar($total);
            $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% - <info>Updating Base Orders</info>');
            $this->output->writeln("<fg=yellow>Starting update process for Base Orders...</>\n");

            $bar->start();

            // contadores para o log final
            $globalCreated   = 0;
            $globalUpdated   = 0;
            $globalCancelled = 0;
            $globalErrors    = 0;
            $canUseUpsert    = $this->canUseSafeUpsert();

            Edp_depcBaseOrder::query()
                ->orderBy('id')
                ->chunkById($chunkSize, function ($origins) use (
                    $upsertBatchSize,
                    $updateColumns,
                    $bar,
                    &$globalCreated,
                    &$globalUpdated,
                    &$globalErrors,
                    $canUseUpsert
                ) {
                    // lookup de notes para o lote
                    $notesMap = Note::whereIn('note', $origins->pluck('ovNota')->filter()->unique())
                        ->pluck('id', 'note');

                    // preparar pares do lote (note_id|ordem) para identificar created x updated
                    $pairs = [];

                    foreach ($origins as $o) {
                        $noteStr  = (string) ($o->ovNota ?? '');
                        $ordemStr = trim((string) ($o->ordem ?? ''));

                        if ($noteStr === '' || $ordemStr === '' || !isset($notesMap[$noteStr])) {
                            $globalErrors++; // considera skip como “erro leve”

                            continue;
                        }
                        $pairs[] = $notesMap[$noteStr] . '|' . $ordemStr;
                    }
                    $pairs = array_values(array_unique($pairs));

                    // existentes do lote
                    $existingMap = collect();

                    if (!empty($pairs)) {
                        $noteIds = [];
                        $ordens  = [];

                        foreach ($pairs as $pk) {
                            [$nid, $ord] = explode('|', $pk, 2);
                            $noteIds[]   = (int) $nid;
                            $ordens[]    = $ord;
                        }
                        $noteIds = array_values(array_unique($noteIds));
                        $ordens  = array_values(array_unique($ordens));

                        $existing = Order::query()
                            ->whereIn('note_id', $noteIds)
                            ->whereIn('ordem', $ordens)
                            ->orderBy('id')
                            ->get(array_merge(['id', 'note_id', 'ordem'], array_diff($updateColumns, ['updated_at'])))
                            ->groupBy(fn ($r) => $r->note_id . '|' . $r->ordem)
                            ->map(fn ($orders) => $orders->first());

                        $existingMap = $existing;
                    }

                    // montar upsert em lote e contar created/updated
                    $bucket = [];
                    $now    = now();

                    foreach ($origins as $o) {
                        $noteStr  = (string) ($o->ovNota ?? '');
                        $ordemStr = trim((string) ($o->ordem ?? ''));

                        if ($noteStr === '' || $ordemStr === '' || !isset($notesMap[$noteStr])) {
                            $globalErrors++;
                            $bar->advance();

                            continue;
                        }

                        $noteId = $notesMap[$noteStr];

                        $dtEntrada = null;

                        if (!empty($o->dtEntrada)) {
                            try {
                                $dtEntrada = Carbon::parse($o->dtEntrada)->format('Y-m-d H:i:s');
                            } catch (\Throwable $e) {
                            }
                        }

                        // A origem pode preencher PEP com espaços à direita; a chave
                        // financeira local usa o valor normalizado de dbo.PEPs.objeto.
                        $pep = trim((string) ($o->pep ?? ''));

                        $row = [
                            'note_id'       => $noteId,
                            'ordem'         => $ordemStr,
                            'descricao'     => $o->descricao ?? null,
                            'locInstalacao' => $o->locInstalacao ?? null,
                            'cenPlan'       => $o->cenPlan ?? null,
                            'prioridade'    => $o->prioridade ?? null,
                            'statusSist'    => $o->statusSist ?? null,
                            'statusUser'    => $o->statusUser ?? null,
                            'cenTrab'       => $o->cenTrab ?? null,
                            'gpm'           => $o->gpm ?? null,
                            'custPlanejado' => $o->custPlanejado ?? null,
                            'custRealizado' => $o->custRealizado ?? null,
                            'modifPor'      => $o->modifPor ?? null,
                            'pep'           => $pep ?: null,
                            'conjunto'      => $o->conjunto ?? null,
                            'denConjunto'   => $o->denConjunto ?? null,
                            'dtEntrada'     => $dtEntrada,
                            'created_at'    => $now, // só em INSERT
                            'updated_at'    => $now,
                        ];

                        // contabilização prévia: se existe o par, será "update"; senão, "create"
                        $pairKey  = $noteId . '|' . $ordemStr;
                        $existing = $existingMap->get($pairKey);

                        // Ordem existente e sem alteração: não reescreve (evita upsert à toa)
                        if ($existing && !$this->orderChanged($existing, $row, $updateColumns)) {
                            $bar->advance();

                            continue;
                        }

                        if ($existing) {
                            $globalUpdated++;
                        } else {
                            $globalCreated++;
                        }

                        $row['_existing_id'] = $existing?->id;
                        $bucket[$pairKey]    = $row;

                        if (count($bucket) >= $upsertBatchSize) {
                            $this->persistOrderRows(array_values($bucket), $updateColumns, $canUseUpsert);
                            $bucket = [];
                        }

                        $bar->advance();
                    }

                    if (!empty($bucket)) {
                        $this->persistOrderRows(array_values($bucket), $updateColumns, $canUseUpsert);
                    }
                });

            $bar->finish();
            $this->info("\nUpsert concluído.");

            // (Opcional) cancelamento: se você usar, atualize $globalCancelled
            // $globalCancelled = ...;

            // 3) FIM DO LOG (UMA única gravação): preenche métricas e salva
            $log->setCreated($globalCreated);
            $log->setUpdated($globalUpdated);
            $log->setNoteUpdated($globalCancelled); // usei este campo para “cancelados”

            if ($globalErrors > 0) {
                $log->setErrorMessage("Skips/erros leves durante o processamento: {$globalErrors}");
            }
            $log->save(); // grava com date_fim => dá pra medir o SLA pelo intervalo início/fim

            $this->info("\nAtualização concluída.");

            return 0; // Success exit code
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            return self::FAILURE;
        }
    }

    /** Normaliza a ordem numérica para string estável (aqui só trim). */
    protected function normalizeOrder(string $v): string
    {
        $v = trim($v);

        // Se quiser considerar "00123" == "123", descomente:
        // $v = ltrim($v, '0'); if ($v === '') $v = '0';
        return $v;
    }

    private function canUseSafeUpsert(): bool
    {
        if (!$this->indexExists('orders', 'uniq_note_ordem')) {
            $this->warn('Blindagem: uniq_note_ordem nao existe. O comando usara escrita manual por id para nao duplicar ordens.');

            return false;
        }

        $duplicatePairs = Order::query()
            ->select('note_id', 'ordem')
            ->groupBy('note_id', 'ordem')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($duplicatePairs) {
            $this->warn('Blindagem: existem ordens duplicadas por note_id/ordem. O comando atualizara apenas o registro canonico de menor id e nao usara upsert.');

            return false;
        }

        return true;
    }

    private function persistOrderRows(array $rows, array $updateColumns, bool $canUseUpsert): void
    {
        if (empty($rows)) {
            return;
        }

        if ($canUseUpsert) {
            // INSERT ... ON DUPLICATE KEY UPDATE consome um auto_increment por linha enviada,
            // mesmo quando ela só atualiza. Linhas existentes vão com o id explícito (não consomem);
            // só as realmente novas vão sem id.
            $existingRows = [];
            $newRows      = [];

            foreach ($rows as $row) {
                $existingId = $row['_existing_id'] ?? null;
                $row        = $this->withoutInternalColumns($row);

                if ($existingId) {
                    $existingRows[] = ['id' => $existingId] + $row;
                } else {
                    $newRows[] = $row;
                }
            }

            if ($existingRows) {
                Order::upsert($existingRows, ['note_id', 'ordem'], $updateColumns);
            }

            if ($newRows) {
                Order::upsert($newRows, ['note_id', 'ordem'], $updateColumns);
            }

            return;
        }

        foreach ($rows as $row) {
            $existingId = $row['_existing_id'] ?? null;
            $row        = $this->withoutInternalColumns($row);

            if ($existingId) {
                $updates = [];

                foreach ($updateColumns as $column) {
                    $updates[$column] = $row[$column] ?? null;
                }

                Order::whereKey($existingId)->update($updates);

                continue;
            }

            Order::create($row);
        }
    }

    private function orderChanged(Order $existing, array $incoming, array $columns): bool
    {
        foreach ($columns as $column) {
            if ($column === 'updated_at') {
                continue;
            }

            $old = $existing->getRawOriginal($column);
            $new = $incoming[$column] ?? null;

            $oldEmpty = $old === null || trim((string) $old) === '';
            $newEmpty = $new === null || trim((string) $new) === '';

            if ($oldEmpty || $newEmpty) {
                if ($oldEmpty !== $newEmpty) {
                    return true;
                }

                continue;
            }

            if (is_numeric($old) && is_numeric($new)) {
                if (abs((float) $old - (float) $new) > 0.000001) {
                    return true;
                }

                continue;
            }

            if (trim((string) $old) !== trim((string) $new)) {
                return true;
            }
        }

        return false;
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
}
