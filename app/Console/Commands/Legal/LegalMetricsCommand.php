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
        $this->info('Volumes por fonte');
        $bySource = $service->bySource()->all();
        if (empty($bySource)) {
            $this->line('Sem dados por fonte.');
        } else {
            foreach ($bySource as $row) {
                $sourceType = $row->source_type?->value ?? (string) ($row->source_type ?? 'N/A');
                $this->line(sprintf(
                    '%s total=%d overdue=%d missing=%d',
                    $sourceType,
                    (int) ($row->total ?? 0),
                    (int) ($row->overdue ?? 0),
                    (int) ($row->missing ?? 0)
                ));
            }
        }

        $this->newLine();
        $this->info('Pendências por responsável');
        $byAssignee = $service->byAssignee()->all();
        if (empty($byAssignee)) {
            $this->line('Sem dados por responsável.');
        } else {
            foreach ($byAssignee as $row) {
                $this->line(sprintf(
                    '%s received=%d pending=%d overdue=%d',
                    $row->assignee_name ?? 'N/A',
                    (int) ($row->received_total ?? 0),
                    (int) ($row->pending_answer ?? 0),
                    (int) ($row->overdue ?? 0)
                ));
            }
        }

        $this->newLine();
        $this->info('Demandas com anexos/comentários');
        $withFiles = \App\Models\Legal\LegalDemand::query()->whereHas('files')->count();
        $withComments = \App\Models\Legal\LegalDemand::query()->whereHas('comments')->count();
        $withoutFiles = \App\Models\Legal\LegalDemand::query()->whereDoesntHave('files')->count();
        $withoutComments = \App\Models\Legal\LegalDemand::query()->whereDoesntHave('comments')->count();
        $this->line("com_anexos: {$withFiles}");
        $this->line("sem_anexos: {$withoutFiles}");
        $this->line("com_comentarios: {$withComments}");
        $this->line("sem_comentarios: {$withoutComments}");

        $this->newLine();
        $this->info('Saúde da importação');
        $rows = $service->importHealth((int) $this->option('days'))->all();
        if (empty($rows)) {
            $this->line('Sem batches no período.');
        } else {
            foreach ($rows as $row) {
                $this->line(sprintf(
                    '#%d %s status=%s total=%d new=%d upd=%d unchanged=%d missing=%d returned=%d failed=%d duration=%s',
                    $row['batch_id'],
                    $row['source_type'],
                    $row['status'],
                    $row['total_rows'],
                    $row['new_rows'],
                    $row['updated_rows'],
                    $row['unchanged_rows'],
                    $row['missing_rows'],
                    $row['returned_rows'],
                    $row['failed_rows'],
                    $row['duration_seconds'] ?? 'N/A'
                ));
            }
        }

        return self::SUCCESS;
    }
}
