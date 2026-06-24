<?php

namespace App\Console\Commands\Tools;

use App\Services\SqlsrvHealth\SqlsrvHealthCollector;
use Illuminate\Console\Command;

class CollectSqlsrvHealth extends Command
{
    protected $signature = 'sicode:collect-sqlsrv1-health
        {--connection=sqlsrv1}
        {--database=edp-depc}
        {--force : Executa mesmo dentro da janela critica de travamento}';

    protected $description = 'Coleta logs e metricas do SQL Server sqlsrv1 para o data lake local de saude.';

    public function handle(SqlsrvHealthCollector $collector): int
    {
        if (!$this->option('force') && $this->isCriticalWindow()) {
            $this->warn('Coleta ignorada: janela critica entre os minutos 45 e 00.');

            return self::SUCCESS;
        }

        $snapshot = $collector->collect(
            (string) $this->option('connection'),
            (string) $this->option('database'),
        );

        $this->info(sprintf(
            'Snapshot #%d: %s, logs=%d, metricas=%d, duracao=%dms',
            $snapshot->id,
            $snapshot->status,
            $snapshot->job_logs_count,
            $snapshot->metrics_count,
            $snapshot->duration_ms ?? 0,
        ));

        if ($snapshot->status === 'failed') {
            $this->error($snapshot->error_message ?? 'Coleta falhou.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function isCriticalWindow(): bool
    {
        $minute = (int) now('America/Sao_Paulo')->format('i');

        return $minute >= 45;
    }
}
