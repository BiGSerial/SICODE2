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
        private readonly LegalDemandKeyGenerator $keyGenerator = new LegalDemandKeyGenerator(),
        private readonly LegalDemandHashGenerator $hashGenerator = new LegalDemandHashGenerator(),
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
            'failed_rows' => 0,
            'errors' => [],
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

        $seenDemandIds = [];
        $hasInjectedRows = array_key_exists('source_rows', $options);
        $sourceRows = collect($options['source_rows'] ?? []);
        if (!$hasInjectedRows) {
            $sourceRows = $this->buildSourceQuery($sourceType, $since, $limit)->get();
        }
        $stats['total_rows'] = $sourceRows->count();

        foreach ($sourceRows as $row) {
            try {
                $normalized = $this->normalizeSourceRow($sourceType, $row);
                if ($normalized === null) {
                    $stats['failed_rows']++;
                    $stats['errors'][] = 'Linha ignorada por numero de processo ausente.';
                    continue;
                }

                $now = now();
                $case = $this->resolveLegalCase($normalized, $dryRun);
                $outcome = $this->upsertDemand(
                    $normalized,
                    $case?->id,
                    $sourceType,
                    $batch?->id,
                    $now,
                    $dryRun,
                    $forceSnapshot
                );

                if ($outcome['demand_id'] !== null) {
                    $seenDemandIds[] = $outcome['demand_id'];
                }

                $stats[$outcome['counter']]++;
            } catch (Throwable $exception) {
                $stats['failed_rows']++;
                $stats['errors'][] = $exception->getMessage();
                report($exception);
            }
        }

        if (!$noMissingCheck && !$dryRun && (!$limit || $limit <= 0)) {
            $stats['missing_rows'] = $this->markMissingDemands($sourceType, $seenDemandIds);
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
            'liminar' => [LegalInjunction::class, 'Data Alteração'],
            'sentence' => [LegalJudgment::class, 'Data Alteração'],
            'subsidy' => [LegalSubsidy::class, 'Data Alteração'],
            default => throw new \InvalidArgumentException("Fonte juridica invalida: {$sourceType}"),
        };
    }

    private function normalizeSourceRow(string $sourceType, mixed $sourceRow): ?array
    {
        if (is_array($sourceRow)) {
            $raw = $sourceRow;
        } else {
            $raw = method_exists($sourceRow, 'toNormalizedArray') ? $sourceRow->toNormalizedArray() : $sourceRow->getAttributes();
        }
        $processNumber = $this->normalizer->normalizeText($raw['process_number'] ?? null);
        $processNumberNormalized = $this->normalizer->normalizeProcessNumber($processNumber);

        if ($processNumberNormalized === null) {
            return null;
        }

        $companyName = $this->normalizer->normalizeText($raw['company_name'] ?? null) ?? 'N/A';
        $subject = $this->normalizer->normalizeText($this->resolveSubject($sourceType, $raw));
        $description = $this->normalizer->normalizeText($this->resolveDescription($sourceType, $raw));
        $startedAt = $this->normalizer->parseExternalDate($this->resolveStartedAt($sourceType, $raw));
        $dueAt = $this->normalizer->parseExternalDate($this->resolveDueAt($sourceType, $raw));
        $redirectedAt = $this->normalizer->parseExternalDate($raw['changed_at'] ?? null);

        $normalized = [
            'source_type' => $sourceType,
            'source_external_id' => $this->normalizer->normalizeText($raw['external_case_number'] ?? null),
            'process_number' => $processNumber,
            'process_number_normalized' => $processNumberNormalized,
            'company_name' => $companyName,
            'external_status' => $this->normalizer->normalizeText($raw['external_status'] ?? null),
            'legal_responsible_name' => $this->normalizer->normalizeText($raw['process_manager'] ?? null),
            'law_firm_name' => $this->normalizer->normalizeText($raw['law_firm'] ?? null),
            'origin_area_name' => $this->normalizer->normalizeText($raw['requesting_area'] ?? null),
            'target_area_name' => $this->normalizer->normalizeText($raw['current_responsible_area'] ?? null),
            'target_person_name' => $this->normalizer->normalizeText($raw['current_responsible_name'] ?? null),
            'subject' => $subject,
            'description' => $description,
            'service_type' => $this->normalizer->normalizeText($raw['information_request_type'] ?? null),
            'external_flow_status' => $this->normalizer->normalizeText($this->resolveExternalFlowStatus($sourceType, $raw)),
            'source_started_at' => $startedAt,
            'source_due_at' => $dueAt,
            'source_redirected_at' => $redirectedAt,
            'raw_payload' => $raw['raw_payload'] ?? $raw,
        ];

        $normalized['source_record_key'] = $this->keyGenerator->make([
            ...$normalized,
            'source_started_at' => $startedAt?->toDateTimeString(),
            'source_redirected_at' => $redirectedAt?->toDateTimeString(),
        ]);

        $normalized['source_hash'] = $this->hashGenerator->make([
            ...$normalized,
            'source_started_at' => $startedAt?->toDateTimeString(),
            'source_due_at' => $dueAt?->toDateTimeString(),
        ]);

        return $normalized;
    }

    private function resolveSubject(string $sourceType, array $row): ?string
    {
        return match ($sourceType) {
            'liminar' => $row['injunction_modality'] ?? $row['injunction_situation'] ?? $row['injunction_status'] ?? null,
            'sentence' => $row['subject'] ?? $row['judgment_status'] ?? null,
            'subsidy' => $row['information_request_type'] ?? $row['information_request_status'] ?? null,
            default => null,
        };
    }

    private function resolveDescription(string $sourceType, array $row): ?string
    {
        return match ($sourceType) {
            'liminar' => $row['description'] ?? null,
            'sentence' => $row['agreement'] ?? null,
            'subsidy' => $row['rejection'] ?? null,
            default => null,
        };
    }

    private function resolveStartedAt(string $sourceType, array $row): mixed
    {
        return match ($sourceType) {
            'liminar' => $row['started_at'] ?? null,
            'sentence' => $row['decision_at'] ?? null,
            'subsidy' => $row['changed_at'] ?? null,
            default => null,
        };
    }

    private function resolveDueAt(string $sourceType, array $row): mixed
    {
        return match ($sourceType) {
            'liminar' => $row['redirect_deadline_at'] ?? null,
            'sentence' => $row['compliance_deadline_at'] ?? null,
            'subsidy' => $row['deadline_at'] ?? null,
            default => null,
        };
    }

    private function resolveExternalFlowStatus(string $sourceType, array $row): ?string
    {
        return match ($sourceType) {
            'liminar' => $row['injunction_status'] ?? $row['injunction_situation'] ?? null,
            'sentence' => $row['judgment_status'] ?? null,
            'subsidy' => $row['information_request_status'] ?? null,
            default => null,
        };
    }

    private function resolveLegalCase(array $normalized, bool $dryRun): ?LegalCase
    {
        if ($dryRun) {
            return new LegalCase();
        }

        $now = now();
        /** @var LegalCase $case */
        $case = LegalCase::query()->firstOrNew([
            'process_number_normalized' => $normalized['process_number_normalized'],
            'company_name' => $normalized['company_name'],
        ]);

        if (!$case->exists) {
            $case->uuid = (string) str()->uuid();
            $case->process_number = $normalized['process_number'];
            $case->first_seen_at = $now;
        }

        $case->external_status = $normalized['external_status'];
        $case->legal_responsible_name = $normalized['legal_responsible_name'];
        $case->law_firm_name = $normalized['law_firm_name'];
        $case->main_origin_area = $normalized['origin_area_name'];
        $case->last_seen_at = $now;
        $case->save();

        return $case;
    }

    private function upsertDemand(
        array $normalized,
        ?int $caseId,
        string $sourceType,
        ?int $batchId,
        Carbon $now,
        bool $dryRun,
        bool $forceSnapshot
    ): array {
        $existing = LegalDemand::query()
            ->where('source_record_key', $normalized['source_record_key'])
            ->first();

        if (!$existing) {
            if ($dryRun) {
                return ['counter' => 'new_rows', 'demand_id' => null];
            }

            $demand = new LegalDemand();
            $demand->uuid = (string) str()->uuid();
            $demand->legal_case_id = $caseId;
            $demand->source_type = $sourceType;
            $demand->source_external_id = $normalized['source_external_id'];
            $demand->source_record_key = $normalized['source_record_key'];
            $demand->source_hash = $normalized['source_hash'];
            $demand->title = $normalized['subject'];
            $demand->description = $normalized['description'];
            $demand->subject = $normalized['subject'];
            $demand->service_type = $normalized['service_type'];
            $demand->external_status = $normalized['external_status'];
            $demand->external_flow_status = $normalized['external_flow_status'];
            $demand->origin_area_name = $normalized['origin_area_name'];
            $demand->target_area_name = $normalized['target_area_name'];
            $demand->target_person_name = $normalized['target_person_name'];
            $demand->source_started_at = $normalized['source_started_at'];
            $demand->source_due_at = $normalized['source_due_at'];
            $demand->source_redirected_at = $normalized['source_redirected_at'];
            $demand->first_seen_at = $now;
            $demand->last_seen_at = $now;
            $demand->source_presence_status = LegalSourcePresenceStatus::PRESENT;
            $demand->internal_status = LegalDemandInternalStatus::NEW_IMPORTED;
            $demand->raw_payload = $normalized['raw_payload'];
            $demand->save();

            $this->logDemandEvent($demand->id, null, 'imported', null, LegalDemandInternalStatus::NEW_IMPORTED->value, 'Demanda criada via importacao.');
            $this->recordSnapshot($demand, $batchId, $normalized, $now);

            return ['counter' => 'new_rows', 'demand_id' => $demand->id];
        }

        $statusWasMissing = $existing->source_presence_status === LegalSourcePresenceStatus::MISSING;
        $statusWasClosed = in_array((string) $existing->internal_status?->value, [
            LegalDemandInternalStatus::CLOSED_INTERNAL->value,
            LegalDemandInternalStatus::CLOSED_EXTERNAL->value,
        ], true);

        if ($existing->source_hash === $normalized['source_hash']) {
            if (!$dryRun) {
                $existing->last_seen_at = $now;
                $existing->source_presence_status = LegalSourcePresenceStatus::PRESENT;
                $existing->save();

                if ($statusWasMissing) {
                    $existing->missing_since = null;
                    $existing->save();
                    $this->logDemandEvent($existing->id, null, 'source_returned', null, null, 'Demanda reapareceu na origem.');
                }
            }

            return ['counter' => 'unchanged_rows', 'demand_id' => $existing->id];
        }

        if ($dryRun) {
            return ['counter' => 'updated_rows', 'demand_id' => $existing->id];
        }

        $existing->legal_case_id = $caseId ?? $existing->legal_case_id;
        $existing->source_external_id = $normalized['source_external_id'];
        $existing->source_hash = $normalized['source_hash'];
        $existing->title = $normalized['subject'];
        $existing->description = $normalized['description'];
        $existing->subject = $normalized['subject'];
        $existing->service_type = $normalized['service_type'];
        $existing->external_status = $normalized['external_status'];
        $existing->external_flow_status = $normalized['external_flow_status'];
        $existing->origin_area_name = $normalized['origin_area_name'];
        $existing->target_area_name = $normalized['target_area_name'];
        $existing->target_person_name = $normalized['target_person_name'];
        $existing->source_started_at = $normalized['source_started_at'];
        $existing->source_due_at = $normalized['source_due_at'];
        $existing->source_redirected_at = $normalized['source_redirected_at'];
        $existing->source_presence_status = LegalSourcePresenceStatus::PRESENT;
        $existing->last_seen_at = $now;
        $existing->missing_since = null;
        $existing->raw_payload = $normalized['raw_payload'];

        if ($statusWasClosed) {
            $fromStatus = $existing->internal_status?->value;
            $existing->internal_status = LegalDemandInternalStatus::REOPENED;
            $this->logDemandEvent($existing->id, null, 'reopened_from_source', $fromStatus, LegalDemandInternalStatus::REOPENED->value, 'Demanda retornou da origem apos encerramento.');
        } elseif ($statusWasMissing) {
            $this->logDemandEvent($existing->id, null, 'source_returned', null, null, 'Demanda reapareceu na origem.');
        }

        $existing->save();
        $this->logDemandEvent($existing->id, null, 'updated_from_source', null, null, 'Demanda atualizada pela origem externa.');

        if ($forceSnapshot || $existing->wasChanged('source_hash')) {
            $this->recordSnapshot($existing, $batchId, $normalized, $now);
        }

        return ['counter' => 'updated_rows', 'demand_id' => $existing->id];
    }

    private function markMissingDemands(string $sourceType, array $seenDemandIds): int
    {
        $query = LegalDemand::query()
            ->where('source_type', $sourceType)
            ->whereNotIn('id', $seenDemandIds)
            ->whereNotIn('internal_status', [
                LegalDemandInternalStatus::CLOSED_EXTERNAL->value,
                LegalDemandInternalStatus::CANCELLED->value,
                LegalDemandInternalStatus::IGNORED->value,
            ]);

        $query->where(function ($builder) {
            $builder->whereNull('source_presence_status')
                ->orWhere('source_presence_status', '!=', LegalSourcePresenceStatus::MISSING->value);
        });

        $demands = $query->get();
        $count = 0;

        foreach ($demands as $demand) {
            $demand->source_presence_status = LegalSourcePresenceStatus::MISSING;
            $demand->missing_since = $demand->missing_since ?? now();
            $demand->save();

            $this->logDemandEvent($demand->id, null, 'source_missing', null, null, 'Demanda ausente na leitura atual da origem.');
            $count++;
        }

        return $count;
    }

    private function recordSnapshot(LegalDemand $demand, ?int $batchId, array $normalized, Carbon $now): void
    {
        LegalSourceSnapshot::create([
            'legal_demand_id' => $demand->id,
            'import_batch_id' => $batchId,
            'source_type' => $normalized['source_type'],
            'source_external_id' => $normalized['source_external_id'],
            'source_record_key' => $normalized['source_record_key'],
            'source_hash' => $normalized['source_hash'],
            'raw_payload' => $normalized['raw_payload'],
            'seen_at' => $now,
        ]);
    }

    private function logDemandEvent(
        int $demandId,
        ?int $assignmentId,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $description
    ): void {
        LegalDemandEvent::create([
            'legal_demand_id' => $demandId,
            'assignment_id' => $assignmentId,
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'description' => $description,
            'metadata' => [
                'source' => 'legal_import',
            ],
            'occurred_at' => now(),
        ]);
    }
}
