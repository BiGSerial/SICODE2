<?php

namespace App\Services\SqlLog;

use App\Models\ProtestJob;
use App\Models\SicodeSql\LogProtestJobSync;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SyncProtestJobsToSqlServerService
{
    private const SQLSERVER_MAX_BINDS = 2000;

    /**
     * Mantido separado para facilitar ativação manual do fallback.
     */
    private bool $preferUpsert = true;
    private array $columnLimits = [];
    private bool $columnLimitsLoaded = false;

    public function sync(
        bool $full = false,
        int $hours = 2,
        ?Carbon $from = null,
        ?Carbon $to = null,
        int $chunk = 60,
        ?callable $progress = null
    ): array {
        $startedAt = microtime(true);

        $chunk = max(1, $chunk);
        $hours = max(1, $hours);

        [$mode, $resolvedFrom, $resolvedTo] = $this->resolveWindow($full, $hours, $from, $to);

        $stats = [
            'mode' => $mode,
            'from' => $resolvedFrom?->toDateTimeString(),
            'to' => $resolvedTo?->toDateTimeString(),
            'chunk' => $chunk,
            'total' => 0,
            'read' => 0,
            'inserted' => 0,
            'updated' => 0,
            'synced' => 0,
            'duration_seconds' => 0.0,
            'column_limits_loaded' => false,
        ];

        $query = ProtestJob::query()
            ->with([
                'protest',
                'protest.Notes:id,note',
                'medProtest',
                'medProtest.Notes:id,note',
                'creator',
                'creator.Company',
                'owner',
                'owner.Company',
                'closer',
                'closer.Company',
            ])
            ->orderBy('id');

        if (!$full) {
            if ($resolvedFrom) {
                $query->where('updated_at', '>=', $resolvedFrom);
            }

            if ($resolvedTo) {
                $query->where('updated_at', '<=', $resolvedTo);
            }
        }

        $stats['total'] = (clone $query)->count();
        $this->loadColumnLimits();
        $stats['column_limits_loaded'] = $this->columnLimitsLoaded;

        if ($progress) {
            $progress('init', ['total' => $stats['total']]);
        }

        $query->chunkById($chunk, function (Collection $jobs) use (&$stats, $progress): void {
            $syncedAt = now();
            $rows = [];

            foreach ($jobs as $job) {
                $rows[] = $this->mapJobToSqlPayload($job, $syncedAt);
            }

            $stats['read'] += count($rows);
            if ($progress) {
                $progress('advance', ['steps' => count($rows), 'read' => $stats['read']]);
            }

            foreach ($this->splitRowsByBindLimit($rows) as $batch) {
                if (empty($batch)) {
                    continue;
                }

                $result = $this->persistBatch($batch);
                $stats['inserted'] += $result['inserted'];
                $stats['updated'] += $result['updated'];
                $stats['synced'] += $result['synced'];
            }
        }, 'id');

        $stats['duration_seconds'] = round(microtime(true) - $startedAt, 2);

        return $stats;
    }

    protected function mapJobToSqlPayload(ProtestJob $job, ?Carbon $syncedAt = null): array
    {
        $syncedAt ??= now();

        $row = [
            'protest_job_id' => (int) $job->id,

            // complaint_number deve sempre espelhar protest.nota
            'complaint_number' => $job->protest?->nota,
            'protest_tipo_nota' => $job->protest?->tipoNota,
            'protest_codecodf' => $job->protest?->codecodf,
            'protest_type' => $this->enumValue($job->protest?->type),

            'measure_number' => $this->resolveMeasureNumber($job),
            'med_id' => $job->medProtest?->med_id,
            'med_status_sist' => $job->medProtest?->statusSist,
            'med_result' => $job->medProtest?->result,

            'dispatcher_name' => $job->creator?->name,
            'dispatcher_company_name' => $job->creator?->Company?->name,
            'owner_name' => $job->owner?->name,
            'owner_company_name' => $job->owner?->Company?->name,
            'closer_name' => $job->closer?->name,
            'closer_company_name' => $job->closer?->Company?->name,

            'priority_label' => $job->priority_label,
            'status_label' => $job->status_label,

            'sent_at' => $job->sent_at,
            'accepted_at' => $job->accepted_at,
            'started_at' => $job->started_at,
            'sla_due_at' => $job->sla_due_at,
            'sla_breached_at' => $job->sla_breached_at,
            'finished_at' => $job->finished_at,
            'closed_at' => $job->closed_at,

            'close_reason' => $job->close_reason,
            'notes_json' => $this->resolveNotesJson($job),
            'is_advance' => (bool) $job->is_advance,
            'confirmed' => (bool) $job->confirmed,
            'confirmed_at' => $job->confirmed_at,

            'synced_at' => $syncedAt,
            // Mantém updated_at original do ProtestJob
            'updated_at' => $job->updated_at,
        ];

        return $this->truncateRow($row);
    }

    private function resolveWindow(
        bool $full,
        int $hours,
        ?Carbon $from,
        ?Carbon $to
    ): array {
        if ($full) {
            return ['full', null, null];
        }

        if ($from || $to) {
            return ['incremental', $from, $to];
        }

        return ['incremental', now()->subHours($hours), null];
    }

    private function persistBatch(array $rows): array
    {
        if ($this->preferUpsert) {
            try {
                return $this->persistBatchWithUpsert($rows);
            } catch (\Throwable $e) {
                return $this->persistBatchWithFallback($rows);
            }
        }

        return $this->persistBatchWithFallback($rows);
    }

    private function persistBatchWithUpsert(array $rows): array
    {
        $table = $this->destinationTable();
        $ids = array_column($rows, 'protest_job_id');

        $existingIds = $this->destinationConnection()
            ->table($table)
            ->whereIn('protest_job_id', $ids)
            ->pluck('protest_job_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $existingLookup = array_flip($existingIds);
        $updated = 0;

        foreach ($ids as $id) {
            if (isset($existingLookup[(int) $id])) {
                $updated++;
            }
        }

        $inserted = count($rows) - $updated;

        $this->destinationConnection()
            ->table($table)
            ->upsert($rows, ['protest_job_id'], $this->updatableColumns());

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'synced' => count($rows),
        ];
    }

    private function persistBatchWithFallback(array $rows): array
    {
        $table = $this->destinationTable();
        $ids = array_column($rows, 'protest_job_id');

        $existingIds = $this->destinationConnection()
            ->table($table)
            ->whereIn('protest_job_id', $ids)
            ->pluck('protest_job_id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $existingLookup = array_flip($existingIds);
        $insertRows = [];
        $updateRows = [];

        foreach ($rows as $row) {
            $jobId = (int) ($row['protest_job_id'] ?? 0);
            if (isset($existingLookup[$jobId])) {
                $updateRows[] = $row;
            } else {
                $insertRows[] = $row;
            }
        }

        if (!empty($insertRows)) {
            $this->destinationConnection()->table($table)->insert($insertRows);
        }

        foreach ($updateRows as $row) {
            $jobId = (int) $row['protest_job_id'];
            $payload = $row;
            unset($payload['protest_job_id']);

            $this->destinationConnection()
                ->table($table)
                ->where('protest_job_id', $jobId)
                ->update($payload);
        }

        return [
            'inserted' => count($insertRows),
            'updated' => count($updateRows),
            'synced' => count($rows),
        ];
    }

    private function resolveMeasureNumber(ProtestJob $job): ?string
    {
        $med = $job->medProtest;
        if (!$med) {
            return null;
        }

        // Número de negócio da medida: prioriza código textual.
        return $med->txtCodMedida
            ?? $med->codMedida
            ?? ($med->med_id !== null ? (string) $med->med_id : null);
    }

    private function resolveNotesJson(ProtestJob $job): string
    {
        $notes = collect();

        if ($job->protest?->relationLoaded('Notes')) {
            $notes = $notes->merge($job->protest->Notes);
        }

        if ($job->medProtest?->relationLoaded('Notes')) {
            $notes = $notes->merge($job->medProtest->Notes);
        }

        $normalized = $notes
            ->pluck('note')
            ->filter(static fn ($note) => $note !== null && $note !== '')
            ->map(function ($note) {
                if (is_numeric($note) && preg_match('/^-?\d+$/', (string) $note) === 1) {
                    return (int) $note;
                }

                return (string) $note;
            })
            ->unique()
            ->values()
            ->all();

        return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    private function splitRowsByBindLimit(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $columnsPerRow = count($rows[0]);
        $maxRows = $this->maxRowsPerBatch($columnsPerRow);

        return array_chunk($rows, $maxRows);
    }

    private function maxRowsPerBatch(int $columnsPerRow): int
    {
        if ($columnsPerRow <= 0) {
            return 1;
        }

        return max(1, (int) floor(self::SQLSERVER_MAX_BINDS / $columnsPerRow));
    }

    private function destinationConnection()
    {
        return DB::connection((new LogProtestJobSync())->getConnectionName());
    }

    private function destinationTable(): string
    {
        return (new LogProtestJobSync())->getTable();
    }

    private function updatableColumns(): array
    {
        return [
            'complaint_number',
            'protest_tipo_nota',
            'protest_codecodf',
            'protest_type',
            'measure_number',
            'med_id',
            'med_status_sist',
            'med_result',
            'dispatcher_name',
            'dispatcher_company_name',
            'owner_name',
            'owner_company_name',
            'closer_name',
            'closer_company_name',
            'priority_label',
            'status_label',
            'sent_at',
            'accepted_at',
            'started_at',
            'sla_due_at',
            'sla_breached_at',
            'finished_at',
            'closed_at',
            'close_reason',
            'notes_json',
            'is_advance',
            'confirmed',
            'confirmed_at',
            'synced_at',
            'updated_at',
        ];
    }

    private function enumValue(mixed $value): mixed
    {
        return is_object($value) && isset($value->value) ? $value->value : $value;
    }

    private function loadColumnLimits(): void
    {
        if (!empty($this->columnLimits)) {
            $this->columnLimitsLoaded = true;
            return;
        }

        [$schema, $table] = $this->resolveSchemaAndTable();

        try {
            $rows = $this->destinationConnection()
                ->table('INFORMATION_SCHEMA.COLUMNS')
                ->select([
                    'COLUMN_NAME as column_name',
                    'CHARACTER_MAXIMUM_LENGTH as max_length',
                ])
                ->where('TABLE_SCHEMA', $schema)
                ->where('TABLE_NAME', $table)
                ->get();

            foreach ($rows as $row) {
                $maxLength = (int) ($row->max_length ?? 0);
                if ($maxLength > 0) {
                    $this->columnLimits[(string) $row->column_name] = $maxLength;
                }
            }
        } catch (\Throwable $e) {
            // tenta fallback abaixo
        }

        if (!empty($this->columnLimits)) {
            $this->columnLimitsLoaded = true;
            return;
        }

        try {
            $fullName = $schema . '.' . $table;
            $rows = $this->destinationConnection()->select(
                "SELECT c.name AS column_name, c.max_length
                 FROM sys.columns c
                 INNER JOIN sys.objects o ON o.object_id = c.object_id
                 WHERE o.object_id = OBJECT_ID(?)",
                [$fullName]
            );

            foreach ($rows as $row) {
                $maxLengthBytes = (int) ($row->max_length ?? 0);
                if ($maxLengthBytes <= 0 || $maxLengthBytes === -1) {
                    continue;
                }

                // NVARCHAR usa 2 bytes por caractere
                $limit = (int) floor($maxLengthBytes / 2);
                if ($limit > 0) {
                    $this->columnLimits[(string) $row->column_name] = $limit;
                }
            }
        } catch (\Throwable $e) {
            // segue sem truncamento por metadado
        }

        if (!empty($this->columnLimits)) {
            $this->columnLimitsLoaded = true;
            return;
        }

        // Falta metadado: mantém vazio e reporta no resumo final.
        $this->columnLimitsLoaded = false;
        $this->columnLimits = [];
    }

    private function resolveSchemaAndTable(): array
    {
        $table = $this->destinationTable();

        if (str_contains($table, '.')) {
            [$schema, $name] = explode('.', $table, 2);
            return [trim($schema), trim($name)];
        }

        return ['dbo', trim($table)];
    }

    private function truncateRow(array $row): array
    {
        if (empty($this->columnLimits)) {
            // proteção mínima para não quebrar em textos gigantes quando o metadata falhar
            if (isset($row['close_reason']) && is_string($row['close_reason'])) {
                $row['close_reason'] = $this->stringSubstr($row['close_reason'], 0, 1000);
            }

            return $row;
        }

        foreach ($row as $key => $value) {
            if (!is_string($value) || !isset($this->columnLimits[$key])) {
                continue;
            }

            $limit = $this->columnLimits[$key];
            if ($limit <= 0) {
                continue;
            }

            if ($this->stringLength($value) > $limit) {
                $row[$key] = $this->stringSubstr($value, 0, $limit);
            }
        }

        return $row;
    }

    private function stringLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value);
        }

        return strlen($value);
    }

    private function stringSubstr(string $value, int $start, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, $start, $length);
        }

        return substr($value, $start, $length);
    }
}
