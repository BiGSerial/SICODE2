<?php

namespace App\Services\SqlsrvHealth;

use App\Models\SqlsrvHealthSnapshot;
use App\Models\SqlsrvJobLogSnapshot;
use App\Models\SqlsrvSourceMetricSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SqlsrvHealthCollector
{
    public function collect(string $connection = 'sqlsrv1', string $database = 'edp-depc'): SqlsrvHealthSnapshot
    {
        $this->configureConnectionTimeouts($connection);

        $startedAt = now();

        $snapshot = SqlsrvHealthSnapshot::create([
            'uuid' => (string) Str::uuid(),
            'connection_name' => $connection,
            'database_name' => $database,
            'collected_at' => $startedAt,
            'started_at' => $startedAt,
            'status' => 'running',
        ]);

        $errors = [];
        $jobLogCount = 0;
        $metricCount = 0;

        try {
            $this->prepareConnection($connection);
            $jobLogCount = $this->collectJobLogs($snapshot, $connection, $database);
        } catch (Throwable $exception) {
            $errors[] = [
                'stage' => 'job_logs',
                'message' => $exception->getMessage(),
            ];
        }

        foreach ($this->metricQueries($database) as $metric) {
            try {
                $this->prepareConnection($connection);
                $metricCount += $this->collectMetric($snapshot, $connection, $metric);
            } catch (Throwable $exception) {
                $errors[] = [
                    'stage' => 'metric',
                    'source' => $metric['source_name'],
                    'message' => $exception->getMessage(),
                ];
            }
        }

        $finishedAt = now();

        $snapshot->update([
            'finished_at' => $finishedAt,
            'duration_ms' => (int) $startedAt->diffInMilliseconds($finishedAt),
            'status' => empty($errors) ? 'success' : ($jobLogCount || $metricCount ? 'partial' : 'failed'),
            'job_logs_count' => $jobLogCount,
            'metrics_count' => $metricCount,
            'error_message' => empty($errors) ? null : collect($errors)->pluck('message')->filter()->first(),
            'collection_errors' => empty($errors) ? null : $errors,
        ]);

        return $snapshot->fresh(['jobLogs', 'sourceMetrics']);
    }

    protected function collectJobLogs(SqlsrvHealthSnapshot $snapshot, string $connection, string $database): int
    {
        $database = $this->sqlServerIdentifier($database);

        $rows = DB::connection($connection)->select("
            DECLARE @fromDate DATETIME2 = DATEADD(
                HOUR,
                19,
                CAST(CAST(DATEADD(DAY, -1, GETDATE()) AS DATE) AS DATETIME2)
            );

            SELECT
                [fileName] AS file_name,
                [fileFolder] AS file_folder,
                [dtRun] AS dt_run,
                [dtLastDate] AS dt_last_date,
                [host],
                [status],
                [error]
            FROM [{$database}].[dbo].[tbld_dba_logs]
                WITH (NOLOCK)
            WHERE [dtRun] >= @fromDate
                AND [fileFolder] <> 'edp-depc'
            ORDER BY [dtRun] DESC
        ");

        foreach ($rows as $row) {
            $error = $this->nullableString($row->error ?? null);
            $status = $this->nullableString($row->status ?? null);

            SqlsrvJobLogSnapshot::create([
                'snapshot_id' => $snapshot->id,
                'file_name' => $this->nullableString($row->file_name ?? null),
                'file_folder' => $this->nullableString($row->file_folder ?? null),
                'dt_run' => $this->parseDate($row->dt_run ?? null),
                'dt_last_date' => $this->parseDate($row->dt_last_date ?? null),
                'host' => $this->nullableString($row->host ?? null),
                'status' => $status,
                'error' => $error,
                'error_hash' => $error ? hash('sha256', $error) : null,
                'has_error' => $this->hasError($status, $error),
            ]);
        }

        return count($rows);
    }

    protected function collectMetric(SqlsrvHealthSnapshot $snapshot, string $connection, array $metric): int
    {
        $row = DB::connection($connection)->selectOne($metric['sql']);

        SqlsrvSourceMetricSnapshot::create([
            'snapshot_id' => $snapshot->id,
            'source_name' => $metric['source_name'],
            'row_count' => isset($row->row_count) ? (int) $row->row_count : null,
            'last_update_at' => $this->parseDate($row->last_update_at ?? null),
            'first_update_at' => $this->parseDate($row->first_update_at ?? null),
            'max_reference_value' => $this->nullableString($row->max_reference_value ?? null),
            'metric_payload' => [
                'label' => $metric['label'],
                'table' => $metric['table'],
                'reference_column' => $metric['reference_column'],
            ],
        ]);

        return 1;
    }

    protected function metricQueries(string $database): array
    {
        $database = $this->sqlServerIdentifier($database);

        return [
            $this->metric($database, 'tbld_usr_baseOV', 'Base OV usuario', 'tbld_usr_baseOV', 'dhStat'),
            $this->metric($database, 'tbld_usr_baseEP', 'Base EP', 'tbld_usr_baseEP', 'dtCriacao'),
            $this->metric($database, 'tbld_usr_baseOrdens', 'Base Ordens', 'tbld_usr_baseOrdens', 'dtEntrada'),
            $this->metric($database, 'tbld_usr_baseOperacoes', 'Base Operacoes', 'tbld_usr_baseOperacoes', 'fimReal'),
            $this->metric($database, 'tbld_usr_baseOperacoesResps', 'Base Operacoes Resps', 'tbld_usr_baseOperacoesResps', 'fimReal'),
            [
                'source_name' => 'tbld_usr_baseCustos',
                'label' => 'Base Custos',
                'table' => 'tbld_usr_baseCustos',
                'reference_column' => 'ordem',
                'sql' => "
                    SELECT
                        {$this->rowCountSubquery($database, 'tbld_usr_baseCustos')} AS row_count,
                        NULL AS last_update_at,
                        NULL AS first_update_at,
                        MAX([ordem]) AS max_reference_value
                    FROM [{$database}].[dbo].[tbld_usr_baseCustos] WITH (NOLOCK)
                    OPTION (MAXDOP 1)
                ",
            ],
            $this->metric($database, 'tbld_usr_baseD5', 'Base D5', 'tbld_usr_baseD5', 'dtCriacao'),
            $this->metric($database, 'tbld_usr_baseReclamacoes', 'Base Reclamacoes', 'tbld_usr_baseReclamacoes', 'dtCriacaoMedida'),
            $this->metric($database, 'tbld_usr_baseDD', 'Base DD', 'tbld_usr_baseDD', 'IssueDate'),
            $this->metric($database, 'tbld_bov_baseSAP', 'Base SAP BOV', 'tbld_bov_baseSAP', 'dtStat'),
            $this->metric($database, 'tbld_bov_baseOV', 'Base OV BOV', 'tbld_bov_baseOV', 'dtStat'),
        ];
    }

    protected function metric(string $database, string $sourceName, string $label, string $table, string $dateColumn): array
    {
        return [
            'source_name' => $sourceName,
            'label' => $label,
            'table' => $table,
            'reference_column' => $dateColumn,
            'sql' => "
                SELECT
                    {$this->rowCountSubquery($database, $table)} AS row_count,
                    MAX([{$dateColumn}]) AS last_update_at,
                    NULL AS first_update_at,
                    NULL AS max_reference_value
                FROM [{$database}].[dbo].[{$table}] WITH (NOLOCK)
                OPTION (MAXDOP 1)
            ",
        ];
    }

    protected function rowCountSubquery(string $database, string $table): string
    {
        return "
            (
                SELECT SUM([rows])
                FROM [{$database}].sys.partitions WITH (NOLOCK)
                WHERE [object_id] = OBJECT_ID(N'[{$database}].[dbo].[{$table}]')
                    AND [index_id] IN (0, 1)
            )
        ";
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) && strtoupper(trim($value)) === 'NAT') {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function prepareConnection(string $connection): void
    {
        $database = DB::connection($connection);
        $timeoutAttribute = defined('PDO::SQLSRV_ATTR_QUERY_TIMEOUT') ? constant('PDO::SQLSRV_ATTR_QUERY_TIMEOUT') : null;

        if ($timeoutAttribute !== null) {
            $database->getPdo()->setAttribute($timeoutAttribute, 60);
        }

        $database->statement('SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED; SET LOCK_TIMEOUT 30000;');
    }

    protected function configureConnectionTimeouts(string $connection): void
    {
        $options = config("database.connections.{$connection}.options", []);

        if (defined('PDO::SQLSRV_ATTR_LOGIN_TIMEOUT')) {
            $options[constant('PDO::SQLSRV_ATTR_LOGIN_TIMEOUT')] = 15;
        }

        if (defined('PDO::SQLSRV_ATTR_QUERY_TIMEOUT')) {
            $options[constant('PDO::SQLSRV_ATTR_QUERY_TIMEOUT')] = 60;
        }

        config(["database.connections.{$connection}.options" => $options]);
        DB::purge($connection);
    }

    protected function hasError(?string $status, ?string $error): bool
    {
        if ($error) {
            return true;
        }

        if (!$status) {
            return false;
        }

        return !in_array(strtoupper($status), ['DONE', 'OK', 'SUCCESS', 'SUCESSO'], true);
    }

    protected function sqlServerIdentifier(string $identifier): string
    {
        return str_replace(']', ']]', $identifier);
    }
}
