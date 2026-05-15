<?php

namespace App\Console\Commands\Legal;

use App\Services\Legal\LegalObservabilityService;
use Illuminate\Console\Command;

class LegalMetricsCommand extends Command
{
    protected $signature = 'legal:metrics {--days=7 : Janela em dias para saúde de importação}';

    protected $description = 'Exibe indicadores operacionais e de observabilidade do módulo jurídico.';

    public function handle(LegalObservabilityService $service): int
    {
        $this->info('Cards - Visão Geral');
        foreach ($service->overviewCards() as $metric => $value) {
            $this->line("{$metric}: {$value}");
        }

        $this->newLine();
        $this->info('Gargalos (horas médias)');
        foreach ($service->bottlenecks() as $metric => $value) {
            $this->line("{$metric}: " . ($value ?? 'N/A'));
        }

        $this->newLine();
        $this->info('Saúde da importação');
        $rows = $service->importHealth((int) $this->option('days'))->all();
        if (empty($rows)) {
            $this->line('Sem batches no período.');
        } else {
            foreach ($rows as $row) {
                $this->line(sprintf(
                    '#%d %s status=%s total=%d new=%d upd=%d unchanged=%d missing=%d failed=%d duration=%s',
                    $row['batch_id'],
                    $row['source_type'],
                    $row['status'],
                    $row['total_rows'],
                    $row['new_rows'],
                    $row['updated_rows'],
                    $row['unchanged_rows'],
                    $row['missing_rows'],
                    $row['failed_rows'],
                    $row['duration_seconds'] ?? 'N/A'
                ));
            }
        }

        return self::SUCCESS;
    }
}
