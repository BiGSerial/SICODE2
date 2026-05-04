<?php

namespace App\Console\Commands\Update;

use App\Console\Commands\Concerns\ShowsProgress;
use App\Custom\RegistroJson;
use App\Models\Edp_depc\BaseOperation as Edp_depcBaseOperation;
use App\Models\Order;
use App\Models\Operation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class BaseOperation extends Command
{
    use ShowsProgress;

    protected $signature = 'sicode:upd_baseOperation';
    protected $description = 'Update Base Operation from SQLSERVER.';

    // tunables
    protected int $chunkSize       = 500;   // tamanho dos blocos de ORDERS
    protected int $upsertBatchSize = 1000;  // lote de upsert em OPERATIONS

    /** colunas a atualizar no upsert (NÃO inclua order_id/operacao/created_at) */
    protected array $updateColumns = [
        'descOperacao','inicioPlanejado','fimPlanejado','inicioReal','fimReal',
        'status','notaOv','cenPlan','cenTrab','txtCenTrab','updated_at',
    ];

    public function handle()
    {
        $log = null;

        try {
        // ===== TOTAL (para o log inicial)
        $totalRecords = Order::where('statusSist', 'Not Like', 'ENT%')
            ->where('statusSist', 'Not Like', 'ENC%')
            ->count();

        // ===== LOG: começo (apenas 1 registro; SLA será date_fim - date_inicio)
        $log = new RegistroJson('upd_baseOperation', $this->option(), $totalRecords);

        // ===== Barra de progresso
        $progressBar = $this->createProgressBar($totalRecords);
        $progressBar->setFormat("<bg=blue;fg=white>UPDATE OPERATION: %current%/%max% </><fg=white;options=bold> [Loop: %cloop%/%tloop%][C: %ctd%/U: %upd%/NF: %nf%]</> <fg=green> [%bar%] </><fg=white;options=bold> %percent%%</> <bg=red;options=bold> %elapsed:6s%/%estimated:-6s% </>\n<bg=blue;fg=white>READING: </> %message%");
        $progressBar->setMessage('Processing');
        $progressBar->start();

        $count = [
            'nf'    => 0, // not found (ordem sem operações na origem)
            'tloop' => (int) ceil(max(1, $totalRecords) / $this->chunkSize),
            'cloop' => 0,
            'ctd'   => 0, // created
            'upd'   => 0, // updated
        ];

        // ===== Processa ORDERS em chunks
        Order::where('statusSist', 'Not Like', 'ENT%')
            ->where('statusSist', 'Not Like', 'ENC%')
            ->select(['id','ordem']) // só o necessário
            ->chunk($this->chunkSize, function (Collection $orders) use (&$progressBar, &$count) {

                $count['cloop']++;

                // Mapa {ordem(trim) => order_id}
                $ordersByOrdem = $orders->mapWithKeys(function ($o) {
                    return [trim((string)$o->ordem) => (int)$o->id];
                });

                // Domínio de 'ordem' do chunk
                $originOrders = $ordersByOrdem->keys()->filter()->values();

                // Busca operações da ORIGEM para esse domínio
                $operationsSrc = Edp_depcBaseOperation::whereIn('ordem', $originOrders)->get();

                if ($operationsSrc->isEmpty()) {
                    // nenhum resultado para todo o domínio do chunk
                    $count['nf'] += $orders->count();
                    foreach ($orders as $o) {
                        // atualiza HUD
                        $progressBar->setMessage('Order ID: ' . $o->id, 'message');
                        $progressBar->setMessage($count['nf'], 'nf');
                        $progressBar->setMessage($count['ctd'], 'ctd');
                        $progressBar->setMessage($count['upd'], 'upd');
                        $progressBar->setMessage($count['cloop'], 'cloop');
                        $progressBar->setMessage($count['tloop'], 'tloop');
                        $progressBar->advance();
                    }
                    return;
                }

                // Agrupa por ordem (normalizada) -> lista de operações
                $operationsByOrdem = $operationsSrc->groupBy(function ($item) {
                    return trim((string)$item->ordem);
                });

                // ===== Preparar pares (order_id|operacao) para contar created x updated
                $pairs = [];
                foreach ($operationsByOrdem as $ordem => $ops) {
                    $orderId = $ordersByOrdem[$ordem] ?? null;
                    if (!$orderId) {
                        continue;
                    }
                    foreach ($ops as $op) {
                        $oper = trim((string)$op->operacao);
                        if ($oper === '') {
                            continue;
                        }
                        $pairs[] = $orderId.'|'.$oper;
                    }
                }
                $pairs = array_values(array_unique($pairs));

                // Busca EXISTENTES do lote
                $existingMap = collect();
                if (!empty($pairs)) {
                    $orderIds = [];
                    $operacoes = [];
                    foreach ($pairs as $pk) {
                        [$oid, $oper] = explode('|', $pk, 2);
                        $orderIds[]  = (int)$oid;
                        $operacoes[] = $oper;
                    }
                    $orderIds  = array_values(array_unique($orderIds));
                    $operacoes = array_values(array_unique($operacoes));

                    $existing = Operation::query()
                        ->whereIn('order_id', $orderIds)
                        ->whereIn('operacao', $operacoes)
                        ->get(['order_id','operacao']);

                    $existingMap = $existing->keyBy(fn ($r) => $r->order_id.'|'.$r->operacao);
                }

                // ===== Monta lote de upsert + contagem created/updated
                $bucket = [];
                $now    = now();

                foreach ($orders as $order) {
                    $ordemKey = trim((string)$order->ordem);

                    if (!$operationsByOrdem->has($ordemKey)) {
                        $count['nf']++;
                        // HUD
                        $progressBar->setMessage('Order ID: ' . $order->id, 'message');
                        $progressBar->setMessage($count['nf'], 'nf');
                        $progressBar->setMessage($count['ctd'], 'ctd');
                        $progressBar->setMessage($count['upd'], 'upd');
                        $progressBar->setMessage($count['cloop'], 'cloop');
                        $progressBar->setMessage($count['tloop'], 'tloop');
                        $progressBar->advance();
                        continue;
                    }

                    foreach ($operationsByOrdem->get($ordemKey) as $src) {
                        $oper = trim((string)$src->operacao);
                        if ($oper === '') {
                            continue;
                        }

                        $row = [
                            'order_id'        => (int)$order->id,
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

                            'created_at'      => $now, // só em INSERT
                            'updated_at'      => $now,
                        ];

                        // contabilização correta
                        $pairKey = $row['order_id'].'|'.$row['operacao'];
                        if (isset($existingMap[$pairKey])) {
                            $count['upd']++;
                        } else {
                            $count['ctd']++;
                        }

                        // dedup dentro do lote
                        $bucket[$pairKey] = $row;

                        if (count($bucket) >= $this->upsertBatchSize) {
                            Operation::upsert(array_values($bucket), ['order_id','operacao'], $this->updateColumns);
                            $bucket = [];
                        }
                    }

                    // HUD (por ordem)
                    $progressBar->setMessage('Order ID: ' . $order->id, 'message');
                    $progressBar->setMessage($count['nf'], 'nf');
                    $progressBar->setMessage($count['ctd'], 'ctd');
                    $progressBar->setMessage($count['upd'], 'upd');
                    $progressBar->setMessage($count['cloop'], 'cloop');
                    $progressBar->setMessage($count['tloop'], 'tloop');
                    $progressBar->advance();
                }

                if (!empty($bucket)) {
                    Operation::upsert(array_values($bucket), ['order_id','operacao'], $this->updateColumns);
                }
            });

        $progressBar->finish();

        // ===== LOG: fim (1 única gravação) — pronto para calcular SLA
        $log->setCreated($count['ctd']);
        $log->setUpdated($count['upd']);
        $log->setNoteUpdated($count['nf']); // usei este campo para "not found"
        $log->save();

        $this->info("\nBaseOperation finalizada. C={$count['ctd']} U={$count['upd']} NF={$count['nf']}");
        return self::SUCCESS;
        } catch (Throwable $e) {
            if ($log instanceof RegistroJson) {
                $log->setErrorMessage($e->getMessage());
                $log->fail($e->getMessage());
            }

            return self::FAILURE;
        }
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
