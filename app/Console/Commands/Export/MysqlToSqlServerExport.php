<?php

namespace App\Console\Commands\Export;

use App\Services\Database\MysqlToSqlServerExporter;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class MysqlToSqlServerExport extends Command
{
    private ?ProgressBar $overallBar = null;

    protected $signature = 'sicode:export-mysql-to-sqlserver
        {--source=mysql : Conexão MySQL de origem}
        {--destination=sqlsrv2 : Conexão SQL Server de destino}
        {--schema=dbo : Schema SQL Server de destino}
        {--confirm-destination= : Nome exato do banco SQL Server de destino, obrigatório para gravar}
        {--tables= : Lista de tabelas separadas por vírgula; vazio exporta todas}
        {--except= : Lista de tabelas a ignorar}
        {--chunk=1000 : Tamanho do lote de leitura}
        {--fresh : Limpa cada tabela no destino antes de copiar}
        {--resume : Pula tabelas cuja contagem origem/destino já bate e recopia a primeira divergente}
        {--no-identity-insert : Não habilita IDENTITY_INSERT em tabelas com coluna identity}
        {--keep-constraints : Mantém constraints ativas durante --fresh}
        {--dry-run : Simula a exportação sem gravar no SQL Server}
        {--continue-on-error : Continua nas próximas tabelas se uma falhar}';

    protected $description = 'Exporta dados do MySQL para SQL Server, respeitando limites de lote e tamanho de colunas.';

    public function handle(MysqlToSqlServerExporter $exporter): int
    {
        $chunk = (int) $this->option('chunk');
        if ($chunk < 1) {
            $this->error('O parâmetro --chunk deve ser >= 1.');
            return self::FAILURE;
        }

        $this->line('Exportação MySQL -> SQL Server');
        $this->line('Origem: ' . $this->option('source'));
        $this->line('Destino: ' . $this->option('destination') . '.' . $this->option('schema'));
        $this->line('Modo: ' . ((bool) $this->option('dry-run') ? 'dry-run' : 'gravação'));
        $this->line('Limpeza destino: ' . ((bool) $this->option('fresh') ? 'sim' : 'não'));
        $this->line('Continuação por contagem: ' . ((bool) $this->option('resume') ? 'sim' : 'não'));
        $this->newLine();

        try {
            $summary = $exporter->export([
                'source' => $this->option('source'),
                'destination' => $this->option('destination'),
                'schema' => $this->option('schema'),
                'confirm_destination' => $this->option('confirm-destination'),
                'tables' => $this->parseListOption($this->option('tables')),
                'except' => $this->parseListOption($this->option('except')),
                'chunk' => $chunk,
                'fresh' => (bool) $this->option('fresh'),
                'resume' => (bool) $this->option('resume'),
                'dry_run' => (bool) $this->option('dry-run'),
                'continue_on_error' => (bool) $this->option('continue-on-error'),
                'identity_insert' => !(bool) $this->option('no-identity-insert'),
                'disable_constraints' => !(bool) $this->option('keep-constraints'),
            ], function (string $event, array $data): void {
                $this->showProgressEvent($event, $data);
            });
        } catch (\Throwable $e) {
            $this->finishOverallBar();
            $this->error('Exportação interrompida: ' . $e->getMessage());
            report($e);

            return self::FAILURE;
        }

        $this->finishOverallBar();

        $this->newLine();
        $this->info('Exportação finalizada.');
        $this->line('Banco origem: ' . $summary['source_database']);
        $this->line('Banco destino: ' . $summary['destination_database']);
        $this->line('Tabelas encontradas: ' . $summary['tables_total']);
        $this->line('Tabelas exportadas: ' . $summary['tables_exported']);
        $this->line('Tabelas ignoradas: ' . $summary['tables_skipped']);
        $this->line('Linhas lidas: ' . $summary['rows_read']);
        $this->line('Linhas gravadas: ' . $summary['rows_written']);
        $this->line('Duração (s): ' . $summary['duration_seconds']);

        if (!empty($summary['errors'])) {
            $this->newLine();
            $this->warn('Erros:');
            foreach ($summary['errors'] as $table => $error) {
                $this->line("- {$table}: {$error}");
            }
        }

        return empty($summary['errors']) ? self::SUCCESS : self::FAILURE;
    }

    private function parseListOption(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return collect(explode(',', (string) $value))
            ->map(static fn ($item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function showProgressEvent(string $event, array $data): void
    {
        if ($event === 'export_plan') {
            $rows = (int) ($data['rows_planned'] ?? 0);
            if ($rows > 0) {
                $this->overallBar = $this->output->createProgressBar($rows);
                $this->overallBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%');
                $this->overallBar->start();
            }

            return;
        }

        if ($event === 'table_start') {
            return;
        }

        if ($event === 'table_count') {
            return;
        }

        if ($event === 'table_skip_matching_count') {
            $this->advanceOverall((int) ($data['total'] ?? 0));
            return;
        }

        if ($event === 'table_resume_mismatch') {
            return;
        }

        if ($event === 'table_advance') {
            $this->advanceOverall((int) ($data['steps'] ?? 0));
            return;
        }

        if ($event === 'identity_insert_on') {
            return;
        }

        if ($event === 'table_error') {
            $this->finishOverallBar();
            $this->warn("  Erro em {$data['table']}: {$data['error']}");
        }
    }

    private function advanceOverall(int $steps): void
    {
        if ($steps <= 0) {
            return;
        }

        if ($this->overallBar instanceof ProgressBar) {
            $this->overallBar->advance($steps);
        }
    }

    private function finishOverallBar(): void
    {
        if (!$this->overallBar instanceof ProgressBar) {
            return;
        }

        if ($this->overallBar->getProgress() < $this->overallBar->getMaxSteps()) {
            $this->overallBar->finish();
        }

        $this->newLine(2);
        $this->overallBar = null;
    }
}
