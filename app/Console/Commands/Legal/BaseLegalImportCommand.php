<?php

namespace App\Console\Commands\Legal;

use App\Services\Legal\LegalImportService;
use Illuminate\Console\Command;

abstract class BaseLegalImportCommand extends Command
{
    protected function runImport(LegalImportService $service, string $sourceType): int
    {
        $startedAt = microtime(true);
        $stats = $service->import($sourceType, [
            'dry' => (bool) $this->option('dry'),
            'limit' => $this->option('limit') ? (int) $this->option('limit') : null,
            'since' => $this->option('since') ?: null,
            'force_snapshot' => (bool) $this->option('force-snapshot'),
            'no_missing_check' => (bool) $this->option('no-missing-check'),
        ]);

        $elapsed = round(microtime(true) - $startedAt, 2);
        $batchId = $stats['batch_id'] ?? 'DRY';

        $this->line("Fonte: {$stats['source']}");
        $this->line("Batch: {$batchId}");
        $this->line("Total lidas: {$stats['total_rows']}");
        $this->line("Novas: {$stats['new_rows']}");
        $this->line("Atualizadas: {$stats['updated_rows']}");
        $this->line("Sem alteração: {$stats['unchanged_rows']}");
        $this->line("Ausentes marcadas: {$stats['missing_rows']}");
        $this->line("Falhas: {$stats['failed_rows']}");
        $this->line("Tempo: {$elapsed}s");
        $this->line("Tempo médio por linha: {$stats['avg_row_seconds']}s");

        if (!empty($stats['errors'])) {
            $this->warn('Erros resumidos:');
            foreach (array_slice($stats['errors'], 0, 10) as $error) {
                $this->line("- {$error}");
            }
        }

        return empty($stats['errors']) ? self::SUCCESS : self::FAILURE;
    }
}
