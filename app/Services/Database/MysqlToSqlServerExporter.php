<?php

namespace App\Services\Database;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MysqlToSqlServerExporter
{
    private const SQLSERVER_BIND_LIMIT = 2100;
    private const SQLSERVER_BIND_BUFFER = 100;

    public function export(array $options, ?callable $progress = null): array
    {
        $startedAt = microtime(true);

        $source = (string) ($options['source'] ?? 'mysql');
        $destination = (string) ($options['destination'] ?? 'sqlsrv2');
        $schema = (string) ($options['schema'] ?? 'dbo');
        $chunk = max(1, (int) ($options['chunk'] ?? 1000));
        $fresh = (bool) ($options['fresh'] ?? false);
        $resume = (bool) ($options['resume'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $continueOnError = (bool) ($options['continue_on_error'] ?? false);
        $identityInsert = (bool) ($options['identity_insert'] ?? true);
        $disableConstraints = (bool) ($options['disable_constraints'] ?? $fresh);

        $sourceConnection = DB::connection($source);
        $destinationConnection = DB::connection($destination);

        $this->validateConnections(
            $sourceConnection,
            $destinationConnection,
            (string) ($options['confirm_destination'] ?? ''),
            $dryRun
        );

        DB::disableQueryLog();

        $tables = $this->resolveTables(
            $sourceConnection,
            (array) ($options['tables'] ?? []),
            (array) ($options['except'] ?? [])
        );

        $summary = [
            'source' => $source,
            'source_database' => $sourceConnection->getDatabaseName(),
            'destination' => $destination,
            'destination_database' => $destinationConnection->getDatabaseName(),
            'schema' => $schema,
            'dry_run' => $dryRun,
            'fresh' => $fresh,
            'resume' => $resume,
            'tables_total' => count($tables),
            'tables_exported' => 0,
            'tables_skipped' => 0,
            'rows_read' => 0,
            'rows_written' => 0,
            'errors' => [],
            'duration_seconds' => 0.0,
            'tables' => [],
        ];

        $tableTotals = [];
        $plannedRows = 0;

        foreach ($tables as $table) {
            $tableTotals[$table] = (int) $sourceConnection->table($table)->count();
            $plannedRows += $tableTotals[$table];
        }

        $summary['rows_planned'] = $plannedRows;

        if ($progress) {
            $progress('export_plan', [
                'tables' => count($tables),
                'rows_planned' => $plannedRows,
            ]);
        }

        try {
            if (($fresh || $resume) && !$dryRun && $disableConstraints) {
                $this->toggleConstraints($destinationConnection, $schema, $tables, false);
            }

            foreach ($tables as $table) {
                try {
                    $tableSummary = $this->exportTable(
                        $sourceConnection,
                        $destinationConnection,
                        $schema,
                        $table,
                        $chunk,
                        $fresh,
                        $resume,
                        $dryRun,
                        $identityInsert,
                        $tableTotals[$table] ?? null,
                        $progress
                    );

                    $summary['tables'][$table] = $tableSummary;
                    $summary['rows_read'] += $tableSummary['rows_read'];
                    $summary['rows_written'] += $tableSummary['rows_written'];

                    if ($tableSummary['status'] === 'exported') {
                        $summary['tables_exported']++;
                    } else {
                        $summary['tables_skipped']++;
                    }
                } catch (\Throwable $e) {
                    $summary['errors'][$table] = $e->getMessage();

                    if (!$continueOnError) {
                        throw $e;
                    }

                    if ($progress) {
                        $progress('table_error', [
                            'table' => $table,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        } finally {
            if (($fresh || $resume) && !$dryRun && $disableConstraints) {
                $this->toggleConstraints($destinationConnection, $schema, $tables, true);
            }
        }

        $summary['duration_seconds'] = round(microtime(true) - $startedAt, 2);

        return $summary;
    }

    private function validateConnections(
        Connection $source,
        Connection $destination,
        string $confirmedDestination,
        bool $dryRun
    ): void {
        $sourceDriver = $source->getDriverName();
        $destinationDriver = $destination->getDriverName();

        if (!in_array($sourceDriver, ['mysql', 'mariadb'], true)) {
            throw new \InvalidArgumentException("A conexão de origem precisa ser MySQL/MariaDB. Driver atual: {$sourceDriver}.");
        }

        if ($destinationDriver !== 'sqlsrv') {
            throw new \InvalidArgumentException("A conexão de destino precisa ser SQL Server. Driver atual: {$destinationDriver}.");
        }

        if ($dryRun) {
            return;
        }

        $destinationDatabase = (string) $destination->getDatabaseName();
        if ($confirmedDestination !== $destinationDatabase) {
            throw new \InvalidArgumentException(
                "Confirme o banco de destino com --confirm-destination={$destinationDatabase}. " .
                "Isso evita gravar no SQL Server errado."
            );
        }
    }

    private function exportTable(
        Connection $source,
        Connection $destination,
        string $schema,
        string $table,
        int $chunk,
        bool $fresh,
        bool $resume,
        bool $dryRun,
        bool $identityInsert,
        ?int $knownTotal,
        ?callable $progress
    ): array {
        if ($progress) {
            $progress('table_start', ['table' => $table]);
        }

        $sourceColumns = $source->getSchemaBuilder()->getColumnListing($table);
        $destinationColumns = $this->destinationColumns($destination, $schema, $table);

        if (empty($destinationColumns)) {
            return [
                'status' => 'skipped',
                'reason' => 'destination_table_not_found',
                'rows_read' => 0,
                'rows_written' => 0,
                'columns' => 0,
            ];
        }

        $destinationColumnNames = array_keys($destinationColumns);
        $columns = array_values(array_intersect($sourceColumns, $destinationColumnNames));

        if (empty($columns)) {
            return [
                'status' => 'skipped',
                'reason' => 'no_common_columns',
                'rows_read' => 0,
                'rows_written' => 0,
                'columns' => 0,
            ];
        }

        $total = $knownTotal ?? (int) $source->table($table)->count();
        $statementTable = $this->statementTable($schema, $table);
        $queryTable = $this->queryTable($schema, $table);
        $destinationTotal = $resume ? $this->destinationCount($destination, $queryTable) : null;

        if ($progress) {
            $progress('table_count', [
                'table' => $table,
                'total' => $total,
                'destination_total' => $destinationTotal,
                'columns' => count($columns),
            ]);
        }

        if ($resume && $destinationTotal === $total) {
            if ($progress) {
                $progress('table_skip_matching_count', [
                    'table' => $table,
                    'total' => $total,
                ]);
            }

            return [
                'status' => 'skipped',
                'reason' => 'matching_count',
                'rows_read' => 0,
                'rows_written' => 0,
                'columns' => count($columns),
            ];
        }

        if ($dryRun) {
            return [
                'status' => 'exported',
                'reason' => 'dry_run',
                'rows_read' => $total,
                'rows_written' => 0,
                'columns' => count($columns),
            ];
        }

        if ($fresh || $resume) {
            if ($resume && $progress) {
                $progress('table_resume_mismatch', [
                    'table' => $table,
                    'source_total' => $total,
                    'destination_total' => $destinationTotal ?? 0,
                ]);
            }

            $this->clearDestinationTable($destination, $statementTable);
        }

        $identityColumn = $this->identityColumn($destinationColumns);
        $shouldTryIdentityInsert = $identityInsert
            && (
                ($identityColumn !== null && in_array($identityColumn, $columns, true))
                || in_array('id', $columns, true)
            );

        $rowsRead = 0;
        $rowsWritten = 0;
        $insertBatchSize = min($chunk, $this->safeBatchSize(count($columns)));
        $identityInsertEnabled = false;

        try {
            if ($shouldTryIdentityInsert) {
                $identityInsertEnabled = $this->enableIdentityInsert($destination, $statementTable, $identityColumn !== null);

                if ($identityInsertEnabled && $progress) {
                    $progress('identity_insert_on', ['table' => $table]);
                }
            }

            $source->table($table)
                ->select($columns)
                ->orderBy($this->orderColumn($sourceColumns))
                ->chunk($chunk, function ($rows) use (
                    $destination,
                    $queryTable,
                    $statementTable,
                    $destinationColumns,
                    $insertBatchSize,
                    $identityInsertEnabled,
                    &$rowsRead,
                    &$rowsWritten,
                    $progress,
                    $table
                ): void {
                    $payload = [];

                    foreach ($rows as $row) {
                        $payload[] = $this->normalizeRow((array) $row, $destinationColumns);
                    }

                    $rowsRead += count($payload);

                    foreach (array_chunk($payload, $insertBatchSize) as $batch) {
                        if (empty($batch)) {
                            continue;
                        }

                        if ($identityInsertEnabled) {
                            $this->insertIdentityBatch($destination, $statementTable, $batch);
                        } else {
                            $destination->table($queryTable)->insert($batch);
                        }

                        $rowsWritten += count($batch);
                    }

                    if ($progress) {
                        $progress('table_advance', [
                            'table' => $table,
                            'steps' => count($payload),
                            'rows_read' => $rowsRead,
                            'rows_written' => $rowsWritten,
                        ]);
                    }
                });
        } finally {
            if ($identityInsertEnabled) {
                $destination->statement('SET IDENTITY_INSERT ' . $statementTable . ' OFF');
            }
        }

        return [
            'status' => 'exported',
            'reason' => null,
            'rows_read' => $rowsRead,
            'rows_written' => $rowsWritten,
            'columns' => count($columns),
        ];
    }

    private function resolveTables(Connection $source, array $onlyTables, array $exceptTables): array
    {
        $database = (string) $source->getDatabaseName();

        $available = $source
            ->table('information_schema.tables')
            ->where('table_schema', $database)
            ->where('table_type', 'BASE TABLE')
            ->orderBy('table_name')
            ->pluck('table_name')
            ->map(static fn ($table) => (string) $table)
            ->all();

        if (!empty($onlyTables)) {
            $wanted = array_flip($onlyTables);
            $available = array_values(array_filter($available, static fn ($table) => isset($wanted[$table])));
        }

        if (!empty($exceptTables)) {
            $blocked = array_flip($exceptTables);
            $available = array_values(array_filter($available, static fn ($table) => !isset($blocked[$table])));
        }

        return $available;
    }

    private function destinationColumns(Connection $destination, string $schema, string $table): array
    {
        $rows = collect($destination->select(
            "SELECT
                COLUMN_NAME AS column_name,
                DATA_TYPE AS data_type,
                CHARACTER_MAXIMUM_LENGTH AS max_length,
                COLUMNPROPERTY(OBJECT_ID(TABLE_SCHEMA + '.' + TABLE_NAME), COLUMN_NAME, 'IsIdentity') AS is_identity
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION",
            [$schema, $table]
        ));

        if ($rows->isEmpty()) {
            return [];
        }

        $columns = [];

        foreach ($rows as $row) {
            $name = (string) $this->metadataValue($row, 'column_name');
            $columns[$name] = [
                'data_type' => strtolower((string) $this->metadataValue($row, 'data_type')),
                'max_length' => (int) ($this->metadataValue($row, 'max_length') ?? 0),
                'identity' => (int) ($this->metadataValue($row, 'is_identity') ?? 0) === 1,
            ];
        }

        return $columns;
    }

    private function metadataValue(object $row, string $key): mixed
    {
        if (property_exists($row, $key)) {
            return $row->{$key};
        }

        $upperKey = strtoupper($key);
        if (property_exists($row, $upperKey)) {
            return $row->{$upperKey};
        }

        return null;
    }

    private function identityColumn(array $columns): ?string
    {
        foreach ($columns as $name => $metadata) {
            if ((bool) ($metadata['identity'] ?? false)) {
                return $name;
            }
        }

        return null;
    }

    private function clearDestinationTable(Connection $destination, string $qualifiedTable): void
    {
        try {
            $destination->statement('TRUNCATE TABLE ' . $qualifiedTable);
        } catch (\Throwable) {
            $destination->statement('DELETE FROM ' . $qualifiedTable);
        }
    }

    private function destinationCount(Connection $destination, string $queryTable): int
    {
        return (int) $destination->table($queryTable)->count();
    }

    private function enableIdentityInsert(Connection $destination, string $statementTable, bool $mustEnable): bool
    {
        try {
            $destination->statement('SET IDENTITY_INSERT ' . $statementTable . ' ON');
            return true;
        } catch (\Throwable $e) {
            if ($mustEnable) {
                throw $e;
            }

            return false;
        }
    }

    private function insertIdentityBatch(Connection $destination, string $statementTable, array $batch): void
    {
        if (empty($batch)) {
            return;
        }

        $columns = array_keys($batch[0]);
        $columnSql = collect($columns)
            ->map(fn (string $column) => '[' . str_replace(']', ']]', $column) . ']')
            ->implode(', ');

        $bindings = [];
        $valueRows = [];

        foreach ($batch as $row) {
            $valueRows[] = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

            foreach ($columns as $column) {
                $bindings[] = $row[$column] ?? null;
            }
        }

        $sql = 'SET IDENTITY_INSERT ' . $statementTable . ' ON; '
            . 'INSERT INTO ' . $statementTable . ' (' . $columnSql . ') VALUES '
            . implode(', ', $valueRows)
            . '; SET IDENTITY_INSERT ' . $statementTable . ' OFF;';

        $destination->statement($sql, $bindings);
    }

    private function toggleConstraints(Connection $destination, string $schema, array $tables, bool $enabled): void
    {
        $command = $enabled ? 'CHECK CONSTRAINT ALL' : 'NOCHECK CONSTRAINT ALL';

        foreach ($tables as $table) {
            if (empty($this->destinationColumns($destination, $schema, $table))) {
                continue;
            }

            $destination->statement('ALTER TABLE ' . $this->statementTable($schema, $table) . ' ' . $command);
        }
    }

    private function normalizeRow(array $row, array $destinationColumns): array
    {
        foreach ($row as $column => $value) {
            if ($value === null) {
                continue;
            }

            $metadata = $destinationColumns[$column] ?? null;
            if ($metadata === null) {
                unset($row[$column]);
                continue;
            }

            if (is_bool($value)) {
                $row[$column] = $value ? 1 : 0;
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                $row[$column] = $value->format('Y-m-d H:i:s');
                continue;
            }

            if (is_string($value)) {
                $row[$column] = $this->normalizeString($value, $metadata);
            }
        }

        return $row;
    }

    private function normalizeString(string $value, array $metadata): string
    {
        $dataType = (string) ($metadata['data_type'] ?? '');
        $maxLength = (int) ($metadata['max_length'] ?? 0);

        if (Str::contains($dataType, ['char', 'text']) && $maxLength > 0 && mb_strlen($value) > $maxLength) {
            return mb_substr($value, 0, $maxLength);
        }

        return $value;
    }

    private function safeBatchSize(int $columnsPerRow): int
    {
        if ($columnsPerRow <= 0) {
            return 1;
        }

        return max(1, (int) floor((self::SQLSERVER_BIND_LIMIT - self::SQLSERVER_BIND_BUFFER) / $columnsPerRow));
    }

    private function orderColumn(array $sourceColumns): string
    {
        return in_array('id', $sourceColumns, true) ? 'id' : $sourceColumns[0];
    }

    private function statementTable(string $schema, string $table): string
    {
        return '[' . str_replace(']', ']]', $schema) . '].[' . str_replace(']', ']]', $table) . ']';
    }

    private function queryTable(string $schema, string $table): string
    {
        return $schema . '.' . $table;
    }
}
