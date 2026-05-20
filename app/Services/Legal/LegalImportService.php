<?php

namespace App\Services\Legal;

use App\Enum\LegalDemandInternalStatus;
use App\Enum\LegalSourcePresenceStatus;
use App\Models\Legal\LegalCase;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalDemandEvent;
use App\Models\Legal\LegalImportBatch;
use App\Models\Legal\LegalSourceSnapshot;
use App\Models\SicodeSql\Legal\LegalInjunction;
use App\Models\SicodeSql\Legal\LegalJudgment;
use App\Models\SicodeSql\Legal\LegalSubsidy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class LegalImportService
{
    public function __construct(
        private readonly LegalSourceNormalizer $normalizer = new LegalSourceNormalizer(),
    ) {}

    public function import(string $sourceType, array $options = []): array
    {
        $startedAt = now();
        $dryRun = (bool) ($options['dry'] ?? false);
        $limit = $options['limit'] ?? null;
        $since = $options['since'] ?? null;
        $forceSnapshot = (bool) ($options['force_snapshot'] ?? false);
        $noMissingCheck = (bool) ($options['no_missing_check'] ?? false);

        $stats = [
            'source' => $sourceType,
            'batch_id' => null,
            'total_rows' => 0,
            'new_rows' => 0,
            'updated_rows' => 0,
            'unchanged_rows' => 0,
            'missing_rows' => 0,
            'returned_rows' => 0,
            'failed_rows' => 0,
            'errors' => [],
            'ignored_rows' => [],
        ];

        $batch = null;
        if (!$dryRun) {
            $batch = LegalImportBatch::create([
                'source_type' => $sourceType,
                'started_at' => $startedAt,
                'status' => 'running',
            ]);
            $stats['batch_id'] = $batch->id;
        }

        $hasInjectedRows = array_key_exists('source_rows', $options);
        $sourceRows = collect($options['source_rows'] ?? []);
        if (!$hasInjectedRows) {
            $sourceRows = $this->buildSourceQuery($sourceType, $since, $limit)->get();
        }

        $stats['total_rows'] = $sourceRows->count();
        $seenDemandIds = [];

        foreach ($sourceRows as $index => $row) {
            try {
                $normalized = $this->normalizeSourceRow($sourceType, $row);
                if ($normalized === null) {
                    $stats['failed_rows']++;
                    $rowArray = is_array($row)
                        ? $row
                        : (method_exists($row, 'toArray') ? $row->toArray() : (method_exists($row, 'getAttributes') ? $row->getAttributes() : []));
                    $lineNumber = (int) $index + 1;
                    $case = $rowArray['case_number'] ?? $rowArray['case_number_normalized'] ?? $rowArray['source_external_id'] ?? $rowArray['external_case_number'] ?? 'N/A';
                    $process = $rowArray['source_process_number'] ?? $rowArray['process_number'] ?? $rowArray['process_number_normalized'] ?? 'N/A';
                    $stats['errors'][] = "Linha {$lineNumber} ignorada por dados de identidade insuficientes.";
                    $stats['ignored_rows'][] = [
                        'line' => $lineNumber,
                        'case_number' => (string) $case,
                        'process_number' => (string) $process,
                    ];
                    continue;
                }

                $now = now();
                $case = $this->resolveLegalCase($normalized, $batch, $dryRun);
                $result = $this->resolveLegalDemand($normalized, $case, $batch, $now, $dryRun, $forceSnapshot);

                if (($result['demand_id'] ?? null) !== null) {
                    $seenDemandIds[] = $result['demand_id'];
                }

                $stats[$result['counter']]++;
                if (($result['returned'] ?? false) === true) {
                    $stats['returned_rows']++;
                }
            } catch (Throwable $exception) {
                $stats['failed_rows']++;
                $stats['errors'][] = $exception->getMessage();
                report($exception);
            }
        }

        if (!$dryRun && !$noMissingCheck && (!$limit || $limit <= 0)) {
            $stats['missing_rows'] = $this->markMissingDemands($sourceType, $seenDemandIds, $batch);
        }

        $finishedAt = now();
        if (!$dryRun && $batch) {
            $batch->update([
                'finished_at' => $finishedAt,
                'total_rows' => $stats['total_rows'],
                'new_rows' => $stats['new_rows'],
                'updated_rows' => $stats['updated_rows'],
                'unchanged_rows' => $stats['unchanged_rows'],
                'missing_rows' => $stats['missing_rows'],
                'returned_rows' => $stats['returned_rows'],
                'failed_rows' => $stats['failed_rows'],
                'status' => $stats['failed_rows'] > 0 ? 'finished_with_errors' : 'finished',
                'error_message' => empty($stats['errors']) ? null : implode(' | ', array_slice($stats['errors'], 0, 5)),
            ]);
        }

        $stats['elapsed_seconds'] = round($finishedAt->floatDiffInSeconds($startedAt), 2);
        $stats['avg_row_seconds'] = $stats['total_rows'] > 0
            ? round($stats['elapsed_seconds'] / $stats['total_rows'], 4)
            : 0.0;

        return $stats;
    }

    public function makeCaseIdentityKey(array $row): string
    {
        return hash('sha256', implode('|', [
            $row['case_number_normalized'] ?? '',
            $row['process_number_core'] ?? '',
        ]));
    }

    public function makeSourceEntityKey(array $row): string
    {
        return hash('sha256', implode('|', [
            $row['source_type'] ?? '',
            $row['case_number_normalized'] ?? '',
            $row['process_number_core'] ?? '',
        ]));
    }

    public function makeSourceOccurrenceKey(array $row): string
    {
        $startedAt = $this->normalizer->parseExternalDate($row['source_started_at'] ?? null)?->toDateTimeString();

        return hash('sha256', implode('|', [
            $row['source_type'] ?? '',
            $row['case_number_normalized'] ?? '',
            $row['process_number_core'] ?? '',
            $row['service_type'] ?? '',
            $startedAt ?? '',
        ]));
    }

    public function makeSourceHash(array $row): string
    {
        $fields = [
            'external_status',
            'external_flow_status',
            'subject',
            'service_type',
            'description',
            'source_analysis_at',
            'source_due_at',
            'source_executed_at',
            'source_changed_at',
            'origin_area_name',
            'target_area_name',
            'target_person_name',
            'requesting_responsible_name',
            'responsible_area_name',
            'opposing_party',
            'process_manager',
            'required_area',
            'city',
            'region',
            'regional',
            'observation',
        ];

        $parts = [];
        foreach ($fields as $field) {
            $value = $row[$field] ?? null;
            if ($value instanceof Carbon) {
                $value = $value->toDateTimeString();
            }
            $parts[] = $value === null ? '' : (string) $value;
        }

        return hash('sha256', implode('|', $parts));
    }

    private function buildSourceQuery(string $sourceType, ?string $since, ?int $limit)
    {
        [$modelClass, $changedColumn] = $this->resolveSourceModelAndChangedColumn($sourceType);

        /** @var Model $model */
        $model = new $modelClass();
        $query = $model->newQuery()->normalized();

        if ($since) {
            $query->where($changedColumn, '>=', $since);
        }

        if ($limit && $limit > 0) {
            $query->limit($limit);
        }

        return $query;
    }

    private function resolveSourceModelAndChangedColumn(string $sourceType): array
    {
        return match ($sourceType) {
            'injunction' => [LegalInjunction::class, 'execution_at'],
            'sentence' => [LegalJudgment::class, 'execution_at'],
            'subsidy' => [LegalSubsidy::class, 'execution_at'],
            default => throw new \InvalidArgumentException("Fonte juridica invalida: {$sourceType}"),
        };
    }

    private function normalizeSourceRow(string $sourceType, mixed $sourceRow): ?array
    {
        $row = is_array($sourceRow)
            ? $sourceRow
            : (method_exists($sourceRow, 'toNormalizedArray') ? $sourceRow->toNormalizedArray() : $sourceRow->getAttributes());

        $caseNumberRaw = $row['case_number_normalized']
            ?? $row['case_number']
            ?? $row['source_external_id']
            ?? $row['external_case_number']
            ?? null;

        $processNumberRaw = $row['process_number_normalized']
            ?? $row['source_process_number']
            ?? $row['process_number']
            ?? null;

        $caseNumberNormalized = $this->normalizer->normalizeProcessNumber($caseNumberRaw);
        $processNumberNormalized = $this->normalizer->normalizeProcessNumber($processNumberRaw);

        if ($caseNumberNormalized === null || $processNumberNormalized === null) {
            return null;
        }

        $normalized = [
            'source_type' => $sourceType,
            'source_external_id' => $this->normalizer->normalizeText($row['source_external_id'] ?? $row['external_case_number'] ?? null),
            'case_number' => $this->normalizer->normalizeText($row['case_number'] ?? $row['source_external_id'] ?? $row['external_case_number'] ?? null),
            'case_number_normalized' => $caseNumberNormalized,
            'source_process_number' => $this->normalizer->normalizeText($row['source_process_number'] ?? $row['process_number'] ?? null),
            'process_number_normalized' => $processNumberNormalized,
            'process_number_core' => substr($processNumberNormalized, 0, 13),
            'company_name' => $this->normalizer->normalizeText($row['company_name'] ?? null),
            'external_status' => $this->normalizer->normalizeText($row['external_status'] ?? null),
            'external_flow_status' => $this->normalizer->normalizeText($row['external_flow_status'] ?? null),
            'subject' => $this->normalizer->normalizeText($row['subject'] ?? $row['injunction_subject'] ?? $row['sentence_subject'] ?? $row['subsidy_subject'] ?? null),
            'service_type' => $this->normalizer->normalizeText($row['service_type'] ?? $row['status_situation'] ?? $row['subsidy_type'] ?? null),
            'description' => $this->normalizer->normalizeText($row['description'] ?? $row['injunction_description'] ?? $row['observation'] ?? null),
            'source_analysis_at' => $this->normalizer->parseExternalDate($row['source_analysis_at'] ?? null),
            'source_started_at' => $this->normalizer->parseExternalDate($row['source_started_at'] ?? $row['start_at'] ?? $row['created_at'] ?? null),
            'source_due_at' => $this->normalizer->parseExternalDate($row['source_due_at'] ?? $row['deadline_at'] ?? null),
            'source_executed_at' => $this->normalizer->parseExternalDate($row['source_executed_at'] ?? $row['execution_at'] ?? null),
            'source_changed_at' => $this->normalizer->parseExternalDate($row['source_changed_at'] ?? $row['execution_at'] ?? null),
            'origin_area_name' => $this->normalizer->normalizeText($row['origin_area_name'] ?? null),
            'target_area_name' => $this->normalizer->normalizeText($row['target_area_name'] ?? null),
            'target_person_name' => $this->normalizer->normalizeText($row['target_person_name'] ?? null),
            'requesting_responsible_name' => $this->normalizer->normalizeText($row['requesting_responsible_name'] ?? null),
            'responsible_area_name' => $this->normalizer->normalizeText($row['responsible_area_name'] ?? null),
            'opposing_party' => $this->normalizer->normalizeText($row['opposing_party'] ?? null),
            'process_manager' => $this->normalizer->normalizeText($row['process_manager'] ?? null),
            'required_area' => $this->normalizer->normalizeText($row['required_area'] ?? null),
            'city' => $this->normalizer->normalizeText($row['city'] ?? null),
            'region' => $this->normalizer->normalizeText($row['region'] ?? null),
            'regional' => $this->normalizer->normalizeText($row['regional'] ?? null),
            'observation' => $this->normalizer->normalizeText($row['observation'] ?? null),
            'raw_payload' => $row['raw_payload'] ?? $row,
        ];

        $normalized['identity_key'] = $this->makeCaseIdentityKey($normalized);
        $normalized['source_entity_key'] = $this->makeSourceEntityKey($normalized);
        $normalized['source_occurrence_key'] = $this->makeSourceOccurrenceKey($normalized);
        $normalized['source_hash'] = $this->makeSourceHash($normalized);

        return $normalized;
    }

    private function resolveLegalCase(array $row, ?LegalImportBatch $batch, bool $dryRun): LegalCase
    {
        if ($dryRun) {
            return new LegalCase();
        }

        $now = now();

        $case = LegalCase::query()
            ->where('identity_key', $row['identity_key'])
            ->first();

        if (!$case) {
            $case = LegalCase::query()->firstOrNew([
                'case_number_normalized' => $row['case_number_normalized'],
                'process_number_core' => $row['process_number_core'],
            ]);
        }

        if (!$case->exists) {
            $case->uuid = (string) str()->uuid();
            $case->first_seen_at = $now;
        }

        $sourcesSeen = collect($case->sources_seen ?? [])->push($row['source_type'])->unique()->values()->all();

        $case->case_number = $row['case_number'];
        $case->case_number_normalized = $row['case_number_normalized'];
        $case->process_number = $row['source_process_number'];
        $case->process_number_normalized = $row['process_number_normalized'];
        $case->process_number_core = $row['process_number_core'];
        $case->company_name = $row['company_name'];
        $case->external_status = $row['external_status'];
        $case->legal_responsible_name = $row['process_manager'];
        $case->main_origin_area = $row['origin_area_name'];
        $case->identity_key = $row['identity_key'];
        $case->identity_strategy = 'case_number_plus_process_core';
        $case->identity_confidence = 100;
        $case->sources_seen = $sourcesSeen;
        $case->last_seen_at = $now;
        $case->last_import_batch_id = $batch?->id;
        $case->save();

        return $case;
    }

    private function resolveLegalDemand(
        array $row,
        LegalCase $case,
        ?LegalImportBatch $batch,
        Carbon $now,
        bool $dryRun,
        bool $forceSnapshot
    ): array {
        $existing = LegalDemand::query()
            ->where('source_occurrence_key', $row['source_occurrence_key'])
            ->first();

        if (!$existing) {
            if ($dryRun) {
                return ['counter' => 'new_rows', 'demand_id' => null, 'returned' => false];
            }

            $demand = new LegalDemand();
            $demand->uuid = (string) str()->uuid();
            $demand->legal_case_id = $case->id;
            $demand->source_type = $row['source_type'];
            $demand->source_external_id = $row['source_external_id'];
            $demand->source_case_number = $row['case_number'];
            $demand->source_case_number_normalized = $row['case_number_normalized'];
            $demand->source_process_number = $row['source_process_number'];
            $demand->source_process_number_normalized = $row['process_number_normalized'];
            $demand->source_process_number_core = $row['process_number_core'];
            $demand->source_entity_key = $row['source_entity_key'];
            $demand->source_occurrence_key = $row['source_occurrence_key'];
            $demand->source_hash = $row['source_hash'];
            $demand->title = $row['subject'];
            $this->fillDemandMutableFields($demand, $row, $batch, $now);
            $demand->first_seen_at = $now;
            $demand->last_seen_at = $now;
            $demand->source_presence_status = LegalSourcePresenceStatus::PRESENT;
            $demand->internal_status = LegalDemandInternalStatus::NEW_IMPORTED;
            $demand->save();

            $this->recordEvent($demand->id, null, $batch?->id, 'imported', null, LegalDemandInternalStatus::NEW_IMPORTED->value, 'Demanda criada via importacao R3.');
            $this->recordSnapshotIfNeeded($demand, $batch, $row, $now, true);

            return ['counter' => 'new_rows', 'demand_id' => $demand->id, 'returned' => false];
        }

        $presenceStatus = $existing->source_presence_status;
        $presenceValue = $presenceStatus instanceof LegalSourcePresenceStatus
            ? $presenceStatus->value
            : (is_string($presenceStatus) ? $presenceStatus : null);
        $wasMissing = $presenceValue === LegalSourcePresenceStatus::MISSING->value;
        $wasClosed = in_array((string) $existing->internal_status?->value, [
            LegalDemandInternalStatus::CLOSED_INTERNAL->value,
            LegalDemandInternalStatus::CLOSED_EXTERNAL->value,
        ], true);

        if ($existing->source_hash === $row['source_hash']) {
            if (!$dryRun) {
                $existing->last_seen_at = $now;
                $existing->source_presence_status = LegalSourcePresenceStatus::PRESENT;
                $existing->last_seen_import_batch_id = $batch?->id;

                if ($wasMissing) {
                    $existing->missing_since = null;
                    $existing->last_returned_batch_id = $batch?->id;
                    $existing->missing_count = max(0, (int) $existing->missing_count);
                    $this->recordEvent($existing->id, null, $batch?->id, 'source_returned', null, null, 'Demanda reapareceu na origem.');
                }

                if ($wasClosed) {
                    $existing->needs_identity_review = true;
                    $this->recordEvent($existing->id, null, $batch?->id, 'source_returned_closed_case', null, null, 'Origem retornou para demanda encerrada; revisão necessária.');
                }

                $existing->save();
            }

            return ['counter' => 'unchanged_rows', 'demand_id' => $existing->id, 'returned' => $wasMissing];
        }

        if ($dryRun) {
            return ['counter' => 'updated_rows', 'demand_id' => $existing->id, 'returned' => $wasMissing];
        }

        $fromStatus = $existing->internal_status?->value;
        $oldHash = $existing->source_hash;

        $existing->legal_case_id = $case->id;
        $existing->source_external_id = $row['source_external_id'];
        $existing->source_case_number = $row['case_number'];
        $existing->source_case_number_normalized = $row['case_number_normalized'];
        $existing->source_process_number = $row['source_process_number'];
        $existing->source_process_number_normalized = $row['process_number_normalized'];
        $existing->source_process_number_core = $row['process_number_core'];
        $existing->source_entity_key = $row['source_entity_key'];
        $existing->source_occurrence_key = $row['source_occurrence_key'];
        $existing->source_hash = $row['source_hash'];
        $existing->title = $row['subject'];
        $this->fillDemandMutableFields($existing, $row, $batch, $now);
        $existing->source_presence_status = LegalSourcePresenceStatus::PRESENT;
        $existing->missing_since = null;

        if ($wasMissing) {
            $existing->last_returned_batch_id = $batch?->id;
            $this->recordEvent($existing->id, null, $batch?->id, 'source_returned', null, null, 'Demanda reapareceu na origem.');
        }

        if ($wasClosed) {
            $existing->needs_identity_review = true;
            $this->recordEvent($existing->id, null, $batch?->id, 'source_returned_closed_case', $fromStatus, $fromStatus, 'Retorno de origem em demanda encerrada sem reabertura automática.');
        }

        $existing->save();

        $this->recordEvent($existing->id, null, $batch?->id, 'updated_from_source', null, null, 'Demanda atualizada pela origem externa.');

        if ($forceSnapshot || $oldHash !== $row['source_hash']) {
            $this->recordSnapshotIfNeeded($existing, $batch, $row, $now, true);
        }

        return ['counter' => 'updated_rows', 'demand_id' => $existing->id, 'returned' => $wasMissing];
    }

    private function fillDemandMutableFields(LegalDemand $demand, array $row, ?LegalImportBatch $batch, Carbon $now): void
    {
        $demand->description = $row['description'];
        $demand->subject = $row['subject'];
        $demand->service_type = $row['service_type'];
        $demand->external_status = $row['external_status'];
        $demand->external_flow_status = $row['external_flow_status'];
        $demand->origin_area_name = $row['origin_area_name'];
        $demand->target_area_name = $row['target_area_name'];
        $demand->target_person_name = $row['target_person_name'];
        $demand->requesting_responsible_name = $row['requesting_responsible_name'];
        $demand->responsible_area_name = $row['responsible_area_name'];
        $demand->opposing_party = $row['opposing_party'];
        $demand->process_manager = $row['process_manager'];
        $demand->required_area = $row['required_area'];
        $demand->city = $row['city'];
        $demand->region = $row['region'];
        $demand->regional = $row['regional'];
        $demand->source_analysis_at = $row['source_analysis_at'];
        $demand->source_started_at = $row['source_started_at'];
        $demand->source_due_at = $row['source_due_at'];
        $demand->source_executed_at = $row['source_executed_at'];
        $demand->source_changed_at = $row['source_changed_at'];
        $demand->last_seen_at = $now;
        $demand->last_seen_import_batch_id = $batch?->id;
        $demand->source_identity_strategy = 'source_type_case_core_service_started_at';
        $demand->source_identity_confidence = 100;
        $demand->raw_payload = $row['raw_payload'];
    }

    private function recordSnapshotIfNeeded(LegalDemand $demand, ?LegalImportBatch $batch, array $row, Carbon $seenAt, bool $shouldRecord): void
    {
        if (!$shouldRecord) {
            return;
        }

        $normalizedPayload = $row;
        foreach (['source_analysis_at', 'source_started_at', 'source_due_at', 'source_executed_at', 'source_changed_at'] as $dateField) {
            if (($normalizedPayload[$dateField] ?? null) instanceof Carbon) {
                $normalizedPayload[$dateField] = $normalizedPayload[$dateField]->toDateTimeString();
            }
        }

        LegalSourceSnapshot::create([
            'legal_demand_id' => $demand->id,
            'import_batch_id' => $batch?->id,
            'source_type' => $row['source_type'],
            'source_external_id' => $row['source_external_id'],
            'source_case_number_normalized' => $row['case_number_normalized'],
            'source_process_number_core' => $row['process_number_core'],
            'source_entity_key' => $row['source_entity_key'],
            'source_occurrence_key' => $row['source_occurrence_key'],
            'source_hash' => $row['source_hash'],
            'raw_payload' => $row['raw_payload'],
            'normalized_payload' => $normalizedPayload,
            'changed_fields' => null,
            'seen_at' => $seenAt,
        ]);
    }

    private function recordEvent(
        int $demandId,
        ?int $assignmentId,
        ?int $importBatchId,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $description
    ): void {
        LegalDemandEvent::create([
            'legal_demand_id' => $demandId,
            'assignment_id' => $assignmentId,
            'import_batch_id' => $importBatchId,
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'description' => $description,
            'metadata' => ['source' => 'legal_import_r3'],
            'occurred_at' => now(),
        ]);
    }

    private function markMissingDemands(string $sourceType, array $seenDemandIds, ?LegalImportBatch $batch): int
    {
        $query = LegalDemand::query()
            ->where('source_type', $sourceType)
            ->whereNotIn('id', $seenDemandIds)
            ->whereNotIn('internal_status', [
                LegalDemandInternalStatus::CLOSED_EXTERNAL->value,
                LegalDemandInternalStatus::CANCELLED->value,
                LegalDemandInternalStatus::IGNORED->value,
            ])
            ->where(function ($builder) {
                $builder->whereNull('source_presence_status')
                    ->orWhere('source_presence_status', '!=', LegalSourcePresenceStatus::MISSING->value);
            });

        $demands = $query->get();
        $count = 0;

        foreach ($demands as $demand) {
            $demand->source_presence_status = LegalSourcePresenceStatus::MISSING;
            $demand->missing_since = $demand->missing_since ?? now();
            $demand->missing_count = (int) $demand->missing_count + 1;
            $demand->last_missing_batch_id = $batch?->id;
            $demand->save();

            $this->recordEvent($demand->id, null, $batch?->id, 'source_missing', null, null, 'Demanda ausente na leitura atual da origem.');
            $count++;
        }

        return $count;
    }
}
