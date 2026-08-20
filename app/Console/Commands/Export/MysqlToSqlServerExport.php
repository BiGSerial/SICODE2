<?php

namespace App\Console\Commands\Export;

use App\Services\Database\MysqlToSqlServerExporter;
use Illuminate\Console\Command;

class MysqlToSqlServerExport extends Command
{
    protected $signature = 'sicode:export-mysql-to-sqlserver
        {--source=mysql : Conexão MySQL de origem}
        {--destination=sqlsrv2 : Conexão SQL Server de destino}
        {--schema=dbo : Schema SQL Server de destino}
        {--confirm-destination= : Nome exato do banco SQL Server de destino, obrigatório para gravar}
        {--tables= : Lista de tabelas separadas por vírgula; vazio exporta todas}
        {--except= : Lista de tabelas a ignorar}
        {--chunk=1000 : Tamanho do lote de leitura}
        {--fresh : Limpa cada tabela no destino antes de copiar}
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
                'dry_run' => (bool) $this->option('dry-run'),
                'continue_on_error' => (bool) $this->option('continue-on-error'),
                'identity_insert' => !(bool) $this->option('no-identity-insert'),
                'disable_constraints' => !(bool) $this->option('keep-constraints'),
            ], function (string $event, array $data): void {
                $this->showProgressEvent($event, $data);
            });
        } catch (\Throwable $e) {
            $this->error('Exportação interrompida: ' . $e->getMessage());
            report($e);

            return self::FAILURE;
        }

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
        if ($event === 'table_start') {
            $this->line('Tabela: ' . $data['table']);
            return;
        }

        if ($event === 'table_count') {
            $this->line("  Linhas: {$data['total']} | Colunas comuns: {$data['columns']}");
            return;
        }

        if ($event === 'table_advance') {
            $this->line("  Copiadas: {$data['rows_written']} / lidas: {$data['rows_read']}");
            return;
        }

        if ($event === 'table_error') {
            $this->warn("  Erro em {$data['table']}: {$data['error']}");
        }
    }
}
