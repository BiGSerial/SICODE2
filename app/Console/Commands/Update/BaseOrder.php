<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\Edp_depc\{BaseOrder as Edp_depcBaseOrder, City};
use App\Models\Note;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
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
            'descricao','locInstalacao','cenPlan','prioridade','statusSist','statusUser',
            'cenTrab','gpm','custPlanejado','custRealizado','modifPor','pep','conjunto',
            'denConjunto','dtEntrada','updated_at',
        ];

        $bar = $this->createProgressBar($total);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% - <info>Updating Base Orders</info>');
        $this->output->writeln("<fg=yellow>Starting update process for Base Orders...</>\n");

        $bar->start();

        // contadores para o log final
        $globalCreated = 0;
        $globalUpdated = 0;
        $globalCancelled = 0;
        $globalErrors = 0;

        Edp_depcBaseOrder::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($origins) use (
                $upsertBatchSize,
                $updateColumns,
                $bar,
                &$globalCreated,
                &$globalUpdated,
                &$globalErrors
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
                    $pairs[] = $notesMap[$noteStr].'|'.$ordemStr;
                }
                $pairs = array_values(array_unique($pairs));

                // existentes do lote
                $existingMap = collect();
                if (!empty($pairs)) {
                    $noteIds = [];
                    $ordens  = [];
                    foreach ($pairs as $pk) {
                        [$nid, $ord] = explode('|', $pk, 2);
                        $noteIds[] = (int) $nid;
                        $ordens[]  = $ord;
                    }
                    $noteIds = array_values(array_unique($noteIds));
                    $ordens  = array_values(array_unique($ordens));

                    $existing = Order::query()
                        ->whereIn('note_id', $noteIds)
                        ->whereIn('ordem', $ordens)
                        ->get(['note_id','ordem']);

                    $existingMap = $existing->keyBy(fn ($r) => $r->note_id.'|'.$r->ordem);
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

                    $row = [
                        'note_id'        => $noteId,
                        'ordem'          => $ordemStr,
                        'descricao'      => $o->descricao ?? null,
                        'locInstalacao'  => $o->locInstalacao ?? null,
                        'cenPlan'        => $o->cenPlan ?? null,
                        'prioridade'     => $o->prioridade ?? null,
                        'statusSist'     => $o->statusSist ?? null,
                        'statusUser'     => $o->statusUser ?? null,
                        'cenTrab'        => $o->cenTrab ?? null,
                        'gpm'            => $o->gpm ?? null,
                        'custPlanejado'  => $o->custPlanejado ?? null,
                        'custRealizado'  => $o->custRealizado ?? null,
                        'modifPor'       => $o->modifPor ?? null,
                        'pep'            => $o->pep ?? null,
                        'conjunto'       => $o->conjunto ?? null,
                        'denConjunto'    => $o->denConjunto ?? null,
                        'dtEntrada'      => $dtEntrada,
                        'created_at'     => $now, // só em INSERT
                        'updated_at'     => $now,
                    ];

                    // contabilização prévia: se existe o par, será "update"; senão, "create"
                    $pairKey = $noteId.'|'.$ordemStr;
                    if (isset($existingMap[$pairKey])) {
                        $globalUpdated++;
                    } else {
                        $globalCreated++;
                    }

                    $bucket[$pairKey] = $row;

                    if (count($bucket) >= $upsertBatchSize) {
                        Order::upsert(array_values($bucket), ['note_id','ordem'], $updateColumns);
                        $bucket = [];
                    }

                    $bar->advance();
                }

                if (!empty($bucket)) {
                    Order::upsert(array_values($bucket), ['note_id','ordem'], $updateColumns);
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
}
