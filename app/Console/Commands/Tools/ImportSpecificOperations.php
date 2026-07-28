<?php

namespace App\Console\Commands\Tools;

use App\Models\Edp_depc\BaseOperation;
use App\Models\Order;
use App\Models\Operation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSpecificOperations extends Command
{
    protected $signature = 'sicode:sync-order-operations
                            {ordens? : Lista de ordens separadas por virgula/espaco, ou caminho de arquivo}
                            {--file= : Arquivo com uma ordem por linha, CSV ou TSV}
                            {--dry-run : Simula sem gravar}';

    protected $description = 'Busca operacoes na BaseOperation para ordens especificas e associa em operations.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $ordens = $this->readOrdens();

        if ($ordens->isEmpty()) {
            $this->warn('Nenhuma ordem informada.');
            return self::SUCCESS;
        }

        $ordersByNumber = Order::query()
            ->whereIn('ordem', $ordens)
            ->get(['id', 'ordem'])
            ->keyBy(fn (Order $order) => $this->clean($order->ordem));

        $localOperationsCount = $ordersByNumber->isEmpty()
            ? 0
            : Operation::query()
                ->whereIn('order_id', $ordersByNumber->pluck('id'))
                ->count();

        try {
            $sourceOperations = BaseOperation::query()
                ->whereIn('ordem', $ordens)
                ->get()
                ->filter(fn ($operation) => $this->clean($operation->operacao ?? null) !== null)
                ->keyBy(function ($operation) {
                    return $this->clean($operation->ordem ?? null) . '|' . $this->clean($operation->operacao ?? null);
                })
                ->values();
        } catch (\Throwable $e) {
            $this->error('Falha ao consultar BaseOperation no sqlsrv1: ' . $e->getMessage());
            return self::FAILURE;
        }

        $sourceOperationsByOrder = $sourceOperations->groupBy(fn ($operation) => $this->clean($operation->ordem ?? null));

        $summary = [
            'orders_requested' => $ordens->count(),
            'orders_found' => $ordersByNumber->count(),
            'local_operations' => $localOperationsCount,
            'source_operations' => $sourceOperations->count(),
            'updated' => 0,
            'created' => 0,
            'unchanged' => 0,
            'missing_order' => 0,
            'missing_source_operations' => 0,
        ];

        DB::beginTransaction();

        try {
            foreach ($ordens as $ordem) {
                $order = $ordersByNumber->get($ordem);
                if (! $order) {
                    $summary['missing_order']++;
                    $this->warn("Order nao encontrada para ordem {$ordem}.");
                    continue;
                }

                $operationsForOrder = $sourceOperationsByOrder->get($ordem, collect())->values();

                if ($operationsForOrder->isEmpty()) {
                    $summary['missing_source_operations']++;
                    $this->warn("Sem operacoes na BaseOperation para ordem {$ordem}.");
                    continue;
                }

                foreach ($operationsForOrder as $sourceOperation) {
                    $operacao = $this->clean($sourceOperation->operacao);
                    $payload = $this->payloadFromSource($sourceOperation);

                    $query = Operation::query()
                        ->where('order_id', $order->id)
                        ->where('operacao', $operacao);

                    $existing = $query->get();

                    if ($existing->isEmpty()) {
                        if (! $dryRun) {
                            Operation::create(array_merge($payload, [
                                'order_id' => $order->id,
                                'operacao' => $operacao,
                            ]));
                        }

                        $summary['created']++;
                        continue;
                    }

                    $changed = $existing->contains(fn (Operation $operation) => $this->operationChanged($operation, $payload));

                    if (! $changed) {
                        $summary['unchanged'] += $existing->count();
                        continue;
                    }

                    if (! $dryRun) {
                        $query->update(array_merge($payload, ['updated_at' => now()]));
                    }

                    $summary['updated'] += $existing->count();
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Falha ao importar operacoes: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info($dryRun ? 'Simulacao concluida.' : 'Importacao concluida.');
        $this->line("Ordens informadas              : {$summary['orders_requested']}");
        $this->line("Ordens locais encontradas      : {$summary['orders_found']}");
        $this->line("Operacoes locais encontradas   : {$summary['local_operations']}");
        $this->line("Operacoes encontradas na origem: {$summary['source_operations']}");
        $this->line("Operacoes atualizadas          : {$summary['updated']}");
        $this->line("Operacoes criadas              : {$summary['created']}");
        $this->line("Operacoes sem mudanca          : {$summary['unchanged']}");
        $this->line("Ordens nao encontradas         : {$summary['missing_order']}");
        $this->line("Ordens sem operacoes na origem : {$summary['missing_source_operations']}");

        return self::SUCCESS;
    }

    private function readOrdens()
    {
        return collect(preg_split('/[\s,;\t]+/', $this->readContents()))
            ->map(fn ($value) => $this->clean($value))
            ->filter()
            ->reject(fn ($value) => strtolower((string) $value) === 'ordem')
            ->unique()
            ->values();
    }

    private function readContents(): string
    {
        $file = $this->option('file');
        if ($file) {
            return $this->readFile((string) $file);
        }

        $ordens = $this->argument('ordens');
        if ($ordens) {
            $ordens = (string) $ordens;

            if (is_readable($ordens)) {
                return $this->readFile($ordens);
            }

            return $ordens;
        }

        return (string) stream_get_contents(STDIN);
    }

    private function readFile(string $path): string
    {
        if (! is_readable($path)) {
            throw new \RuntimeException("Arquivo nao legivel: {$path}");
        }

        return (string) file_get_contents($path);
    }

    private function payloadFromSource($sourceOperation): array
    {
        return [
            'descOperacao' => $this->clean($sourceOperation->descOperacao ?? null),
            'inicioPlanejado' => $this->parseDateTime($sourceOperation->inicioPlanejado ?? null),
            'fimPlanejado' => $this->parseDateTime($sourceOperation->fimPlanejado ?? null),
            'inicioReal' => $this->parseDateTime($sourceOperation->inicioReal ?? null),
            'fimReal' => $this->parseDateTime($sourceOperation->fimReal ?? null),
            'status' => $this->clean($sourceOperation->status ?? null),
            'notaOv' => $this->clean($sourceOperation->notaOv ?? null),
            'cenPlan' => $this->clean($sourceOperation->cenPlan ?? null),
            'cenTrab' => $this->clean($sourceOperation->cenTrab ?? null),
            'txtCenTrab' => $this->clean($sourceOperation->txtCenTrab ?? null),
        ];
    }

    private function operationChanged(Operation $operation, array $incoming): bool
    {
        foreach ($incoming as $column => $value) {
            if ($this->comparableValue($operation->getRawOriginal($column)) !== $this->comparableValue($value)) {
                return true;
            }
        }

        return false;
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || strtoupper($value) === 'NULL') {
            return null;
        }

        return $value;
    }

    private function parseDateTime(mixed $value): ?string
    {
        $value = $this->clean($value);

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function comparableValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return trim((string) $value);
    }
}
