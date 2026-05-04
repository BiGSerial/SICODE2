<?php

namespace App\Console\Commands\Tools;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Models\Edp_depc\BaseOperation as Edp_depcBaseOperation;
use App\Models\Order;
use App\Models\Operation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class SyncOperationsFromBaseOperation extends Command
{
    use ShowsProgress;

    protected $signature = 'sicode:tools_sync_operations
                            {--limit=0 : Limita a quantidade de operações da origem para teste}';

    protected $description = 'Sincroniza todas as operações da BaseOperation (SQLSERVER) para a tabela operations local.';

    // tunables
    protected int $chunkSize       = 1000;  // tamanho dos blocos da ORIGEM
    protected int $upsertBatchSize = 1000;  // lote de upsert em OPERATIONS

    /** colunas a atualizar no upsert (NÃO inclua order_id/operacao/created_at) */
    protected array $updateColumns = [
        'descOperacao',
        'inicioPlanejado',
        'fimPlanejado',
        'inicioReal',
        'fimReal',
        'status',
        'notaOv',
        'cenPlan',
        'cenTrab',
        'txtCenTrab',
        'updated_at',
    ];

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        // Query base
        $baseQuery = Edp_depcBaseOperation::query();

        if ($limit > 0) {
            $baseQuery->limit($limit);
        }

        $totalRecords = $baseQuery->count();

        if ($totalRecords === 0) {
            $this->warn('Nenhum registro encontrado na BaseOperation de origem.');
            return self::SUCCESS;
        }

        // Progress bar
        $progressBar = $this->createProgressBar($totalRecords);
        $progressBar->setFormat("<bg=blue;fg=white>SYNC OPERATIONS: %current%/%max% </><fg=white;options=bold> [C: %ctd%/U: %upd%/NO: %noorder%]</> <fg=green> [%bar%] </><fg=white;options=bold> %percent%%</> <bg=red;options=bold> %elapsed:6s%/%estimated:-6s% </>\n<bg=blue;fg=white>READING: </> %message%");
        $progressBar->setMessage('Iniciando...', 'message');
        $progressBar->setMessage('0', 'ctd');
        $progressBar->setMessage('0', 'upd');
        $progressBar->setMessage('0', 'noorder');
        $progressBar->start();

        $count = [
            'ctd'     => 0, // created
            'upd'     => 0, // updated
            'noorder' => 0, // operações cuja ordem não existe localmente
            'processed' => 0,
        ];

        // Processa ORIGEM em chunks, por id (supondo coluna 'id' na origem)
        Edp_depcBaseOperation::query()
            ->when($limit > 0, fn ($q) => $q->limit($limit))
            ->orderBy('id')
            ->chunkById($this->chunkSize, function (Collection $operationsSrc) use (&$progressBar, &$count) {

                // 1) Pegar domínio de ordens da origem (normalizadas)
                $originOrdens = $operationsSrc
                    ->pluck('ordem')
                    ->filter()
                    ->map(fn ($v) => trim((string) $v))
                    ->unique()
                    ->values();

                if ($originOrdens->isEmpty()) {
                    foreach ($operationsSrc as $src) {
                        $count['processed']++;
                        $progressBar->setMessage('Sem ordem válida na origem', 'message');
                        $progressBar->setMessage((string) $count['ctd'], 'ctd');
                        $progressBar->setMessage((string) $count['upd'], 'upd');
                        $progressBar->setMessage((string) $count['noorder'], 'noorder');
                        $progressBar->advance();
                    }
                    return;
                }

                // 2) Buscar ORDERS locais para essas ordens
                $orders = Order::whereIn('ordem', $originOrdens)->get(['id', 'ordem']);

                $ordersByOrdem = $orders->mapWithKeys(function ($o) {
                    return [trim((string) $o->ordem) => (int) $o->id];
                });

                // 3) Montar todos os pares order_id|operacao desse chunk
                $pairs = [];
                foreach ($operationsSrc as $src) {
                    $ordemKey = trim((string) $src->ordem);
                    if ($ordemKey === '' || !isset($ordersByOrdem[$ordemKey])) {
                        continue;
                    }
                    $oper = trim((string) $src->operacao);
                    if ($oper === '') {
                        continue;
                    }
                    $orderId = $ordersByOrdem[$ordemKey];
                    $pairs[] = $orderId . '|' . $oper;
                }

                $pairs = array_values(array_unique($pairs));

                // 4) Buscar operations EXISTENTES desse conjunto
                $existingMap = collect();
                if (!empty($pairs)) {
                    $orderIds  = [];
                    $operacoes = [];

                    foreach ($pairs as $pk) {
                        [$oid, $oper] = explode('|', $pk, 2);
                        $orderIds[]  = (int) $oid;
                        $operacoes[] = $oper;
                    }

                    $orderIds  = array_values(array_unique($orderIds));
                    $operacoes = array_values(array_unique($operacoes));

                    $existing = Operation::query()
                        ->whereIn('order_id', $orderIds)
                        ->whereIn('operacao', $operacoes)
                        ->get(['order_id', 'operacao']);

                    $existingMap = $existing->keyBy(fn ($r) => $r->order_id . '|' . $r->operacao);
                }

                // 5) Montar bucket de upsert
                $bucket = [];
                $now    = now();

                foreach ($operationsSrc as $src) {
                    $count['processed']++;

                    $ordemKey = trim((string) $src->ordem);
                    if ($ordemKey === '' || !isset($ordersByOrdem[$ordemKey])) {
                        // origem tem operação para ordem que não existe localmente
                        $count['noorder']++;
                        $progressBar->setMessage("Sem Order para ordem {$ordemKey}", 'message');
                        $progressBar->setMessage((string) $count['ctd'], 'ctd');
                        $progressBar->setMessage((string) $count['upd'], 'upd');
                        $progressBar->setMessage((string) $count['noorder'], 'noorder');
                        $progressBar->advance();
                        continue;
                    }

                    $oper = trim((string) $src->operacao);
                    if ($oper === '') {
                        $progressBar->setMessage("Operação vazia para ordem {$ordemKey}", 'message');
                        $progressBar->advance();
                        continue;
                    }

                    $orderId = $ordersByOrdem[$ordemKey];

                    $row = [
                        'order_id'        => $orderId,
                        'operacao'        => $oper,

                        'descOperacao'    => $src->descOperacao ?? null,
                        'inicioPlanejado' => $this->parseDateTime($src->inicioPlanejado),
                        'fimPlanejado'    => $this->parseDateTime($src->fimPlanejado),
                        'inicioReal'      => $this->parseDateTime($src->inicioReal),
                        'fimReal'         => $this->parseDateTime($src->fimReal),
                        'status'          => $src->status ?? null,
                        'notaOv'          => $src->notaOv ?? null,
                        'cenPlan'         => $src->cenPlan ?? null,
                        'cenTrab'         => $src->cenTrab ?? null,
                        'txtCenTrab'      => $src->txtCenTrab ?? null,

                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];

                    $pairKey = $row['order_id'] . '|' . $row['operacao'];

                    if (isset($existingMap[$pairKey])) {
                        $count['upd']++;
                    } else {
                        $count['ctd']++;
                    }

                    // dedup dentro do bucket por (order_id|operacao)
                    $bucket[$pairKey] = $row;

                    if (count($bucket) >= $this->upsertBatchSize) {
                        Operation::upsert(
                            array_values($bucket),
                            ['order_id', 'operacao'],
                            $this->updateColumns
                        );
                        $bucket = [];
                    }

                    $progressBar->setMessage("Order ID: {$orderId} / Oper: {$oper}", 'message');
                    $progressBar->setMessage((string) $count['ctd'], 'ctd');
                    $progressBar->setMessage((string) $count['upd'], 'upd');
                    $progressBar->setMessage((string) $count['noorder'], 'noorder');
                    $progressBar->advance();
                }

                if (!empty($bucket)) {
                    Operation::upsert(
                        array_values($bucket),
                        ['order_id', 'operacao'],
                        $this->updateColumns
                    );
                }
            });

        $progressBar->finish();

        $this->info("\nSincronização concluída. Criados={$count['ctd']} Atualizados={$count['upd']} SemOrder={$count['noorder']}");

        return self::SUCCESS;
    }

    private function parseDateTime($v): ?string
    {
        if (empty($v)) {
            return null;
        }

        try {
            return Carbon::parse($v)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
