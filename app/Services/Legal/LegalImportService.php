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

        [$sourceTable, $sourceVersion] = $this->resolveSourceMeta($sourceType);

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
                'source_table' => $sourceTable,
                'source_version' => $sourceVersion,
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
                $normalized = $this->normalizeSourceRow($sourceType, $row, $sourceTable, $sourceVersion);
                if ($normalized === null) {
                    $stats['failed_rows']++;
                    $lineNumber = (int) $index + 1;
                    $rowArray = is_array($row)
                        ? $row
                        : (method_exists($row, 'toArray') ? $row->toArray() : (method_exists($row, 'getAttributes') ? $row->getAttributes() : []));

                    $stats['errors'][] = "Linha {$lineNumber} ignorada por dados de identidade insuficientes.";
                    $stats['ignored_rows'][] = [
                        'line' => $lineNumber,
                        'case_number' => (string) ($rowArray['case_number'] ?? 'N/A'),
                        'process_number' => (string) ($rowArray['process_number'] ?? 'N/A'),
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

    private function resolveSourceMeta(string $sourceType): array
    {
        return match ($sourceType) {
            'injunction' => ['subjus_r3_liminares', 'r3-v2'],
            'sentence' => ['subjus_r3_sentencas', 'r3-v2'],
            'subsidy' => ['subjus_r3_subsidios', 'r3-v2'],
            default => throw new \InvalidArgumentException("Fonte juridica invalida: {$sourceType}"),
        };
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

    private function normalizeSourceRow(string $sourceType, mixed $sourceRow, string $sourceTable, string $sourceVersion): ?array
    {
        $row = is_array($sourceRow)
            ? $sourceRow
            : (method_exists($sourceRow, 'toNormalizedArray') ? $sourceRow->toNormalizedArray() : $sourceRow->getAttributes());

        $caseNumberRaw = $row['case_number'] ?? null;
        $processNumberRaw = $row['source_process_number'] ?? $row['process_number'] ?? null;

        $caseNumberNormalized = $this->normalizer->normalizeProcessNumber($caseNumberRaw);
        $processNumberNormalized = $this->normalizer->normalizeProcessNumber($processNumberRaw);

        if ($caseNumberNormalized === null || $processNumberNormalized === null) {
            return null;
        }

        $sourceSubject = $this->normalizer->normalizeText($row['subject'] ?? null);
        $sourceDescription = $this->normalizer->normalizeText($row['description'] ?? null);
        $sourceStatus = $this->normalizer->normalizeText($row['external_flow_status'] ?? $row['external_status'] ?? null);
        $processStatus = $this->normalizer->normalizeText($row['external_status'] ?? null);

        $requestingArea = $this->normalizer->normalizeText(
            $row['origin_area_name']
                ?? $row['requesting_area']
                ?? $row['rquesting_area']
                ?? $row['required_area']
                ?? null
        );
        $delegatedResponsible = $this->normalizer->normalizeText($row['delegated_responsible_name'] ?? null);
        $responsibleArea = $this->normalizer->normalizeText($row['responsible_area_name'] ?? null);

        $sourceDueAt = $this->normalizer->parseExternalDate($row['source_due_at'] ?? null);
        $sourceDecisionAt = $this->normalizer->parseExternalDate($row['source_executed_at'] ?? null);
        $sourceStatusAt = $this->normalizer->parseExternalDate($row['source_changed_at'] ?? $row['source_executed_at'] ?? null);
        $delegatedAt = $this->normalizer->parseExternalDate($row['source_started_at'] ?? null);

        $recordKeyData = [
            'source_type' => $sourceType,
            'case_number_normalized' => $caseNumberNormalized,
            'process_number_normalized' => $processNumberNormalized,
            'source_due_at' => $sourceDueAt?->toDateTimeString(),
        ];

        [$sourceRecordKey, $sourceRecordKeyConfidence] = $this->buildSourceRecordKeyAndConfidence($recordKeyData);

        $normalized = [
            'source_type' => $sourceType,
            'source_table' => $sourceTable,
            'source_version' => $sourceVersion,
            'case_number' => $this->normalizer->normalizeText($row['case_number'] ?? null),
            'case_number_normalized' => $caseNumberNormalized,
            'source_process_number' => $this->normalizer->normalizeText($row['source_process_number'] ?? $row['process_number'] ?? null),
            'process_number_normalized' => $processNumberNormalized,
            'source_installation_number' => null,
            'process_status_at_import' => $processStatus,
            'company_name' => $this->normalizer->normalizeText($row['company_name'] ?? null),
            'process_manager' => $this->normalizer->normalizeText($row['process_manager'] ?? null),
            'law_firm' => $this->normalizer->normalizeText($row['law_firm'] ?? null),
            'district' => $this->normalizer->normalizeText($row['district'] ?? null),
            'process_nature' => $this->normalizer->normalizeText($row['process_nature'] ?? null),
            'process_cause' => $this->normalizer->normalizeText($row['process_cause'] ?? null),
            'source_subject' => $sourceSubject,
            'source_description' => $sourceDescription,
            'source_status' => $sourceStatus,
            'source_status_at' => $sourceStatusAt,
            'source_status_group' => $this->mapSourceStatusGroup($sourceStatus),
            'requesting_area_name' => $requestingArea,
            'requesting_responsible_name' => $this->normalizer->normalizeText($row['requesting_responsible_name'] ?? null),
            'responsible_area_name' => $responsibleArea,
            'delegated_responsible_name' => $delegatedResponsible,
            'delegated_by_name' => null,
            'delegated_at' => $delegatedAt,
            'source_due_at' => $sourceDueAt,
            'source_decision_at' => $sourceDecisionAt,
            'source_end_at' => null,
            'priority' => null,
            'risk_level' => null,
            'source_record_key' => $sourceRecordKey,
            'source_record_key_strategy' => 'source_type_case_process_source_due_at',
            'source_record_key_confidence' => $sourceRecordKeyConfidence,
            'source_entity_key' => $this->makeSourceEntityKey($sourceType, $caseNumberNormalized, $processNumberNormalized),
            'raw_payload' => $row['raw_payload'] ?? $row,
            'source_specific_payload' => [
                'service_type' => $row['service_type'] ?? null,
                'required_area' => $row['required_area'] ?? null,
                'city' => $row['city'] ?? null,
                'region' => $row['region'] ?? null,
                'regional' => $row['regional'] ?? null,
                'source_analysis_at' => $row['source_analysis_at'] ?? null,
                'source_started_at' => $row['source_started_at'] ?? null,
                'source_changed_at' => $row['source_changed_at'] ?? null,
                'observation' => $row['observation'] ?? null,
            ],
        ];

        $normalized['identity_key'] = $this->makeCaseIdentityKey(
            $normalized['case_number_normalized'],
            $normalized['process_number_normalized']
        );

        $normalized['needs_identity_review'] = $sourceRecordKeyConfidence === 'low';
        $normalized['needs_status_review'] = $this->needsStatusReview($normalized['process_status_at_import'], $normalized['source_status_group']);
        $normalized['source_hash'] = $this->makeSourceHash($normalized);
        $normalized['normalized_payload'] = $this->buildNormalizedPayload($normalized);

        return $normalized;
    }

    private function buildSourceRecordKeyAndConfidence(array $data): array
    {
        $hasDueAt = !empty($data['source_due_at']);

        $confidence = 'low';
        if ($hasDueAt) {
            $confidence = 'high';
        }

        $parts = [
            $data['source_type'] ?? '',
            $data['case_number_normalized'] ?? '',
            $data['process_number_normalized'] ?? '',
            (string) ($data['source_due_at'] ?? ''),
        ];

        return [hash('sha256', implode('|', $parts)), $confidence];
    }

    private function makeCaseIdentityKey(string $caseNumberNormalized, string $processNumberNormalized): string
    {
        return hash('sha256', implode('|', [$caseNumberNormalized, $processNumberNormalized]));
    }

    private function makeSourceEntityKey(string $sourceType, string $caseNumberNormalized, string $processNumberNormalized): string
    {
        return hash('sha256', implode('|', [$sourceType, $caseNumberNormalized, $processNumberNormalized]));
    }

    private function makeSourceHash(array $row): string
    {
        $fields = [
            'source_status',
            'source_status_at',
            'process_status_at_import',
            'requesting_area_name',
            'requesting_responsible_name',
            'responsible_area_name',
            'delegated_responsible_name',
            'delegated_by_name',
            'delegated_at',
            'source_due_at',
            'source_decision_at',
            'source_end_at',
            'source_subject',
            'source_description',
            'source_specific_payload',
        ];

        $parts = [];
        foreach ($fields as $field) {
            $value = $row[$field] ?? null;
            if ($value instanceof Carbon) {
                $value = $value->toDateTimeString();
            }
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $parts[] = $value === null ? '' : (string) $value;
        }

        return hash('sha256', implode('|', $parts));
    }

    private function buildNormalizedPayload(array $row): array
    {
        $payload = $row;
        foreach (['source_status_at', 'delegated_at', 'source_due_at', 'source_decision_at', 'source_end_at'] as $field) {
            if (($payload[$field] ?? null) instanceof Carbon) {
                $payload[$field] = $payload[$field]->toDateTimeString();
            }
        }

        unset($payload['raw_payload']);

        return $payload;
    }

    private function mapSourceStatusGroup(?string $status): string
    {
        $value = mb_strtolower(trim((string) ($status ?? '')));
        if ($value === '') {
            return 'unknown';
        }

        $contains = static fn(string $haystack, array $terms): bool => collect($terms)->contains(fn($term) => str_contains($haystack, $term));

        if ($contains($value, ['completo', 'elaborado', 'encerrad', 'cumprid', 'procedimento realizado'])) {
            return 'closed_done';
        }

        if ($contains($value, ['cancelad', 'revogad', 'procedimento cancelado'])) {
            return 'closed_cancelled';
        }

        if ($contains($value, ['redirecion'])) {
            return 'open_redirected';
        }

        if ($contains($value, ['delegad'])) {
            return 'open_delegated';
        }

        if ($contains($value, ['andamento', 'andament', 'em andamento'])) {
            return 'open_in_progress';
        }

        return 'unknown';
    }

    private function needsStatusReview(?string $processStatus, string $sourceStatusGroup): bool
    {
        $process = mb_strtolower((string) ($processStatus ?? ''));
        if (str_contains($process, 'encerrad') && in_array($sourceStatusGroup, ['open_in_progress', 'open_delegated', 'open_redirected'], true)) {
            return true;
        }

        return false;
    }

    private function resolveLegalCase(array $row, ?LegalImportBatch $batch, bool $dryRun): LegalCase
    {
        if ($dryRun) {
            return new LegalCase();
        }

        $now = now();

        $case = LegalCase::query()->where('identity_key', $row['identity_key'])->first();
        if (!$case) {
            $case = new LegalCase();
            $case->uuid = (string) str()->uuid();
            $case->first_seen_at = $now;
        }

        $sourcesSeen = collect($case->sources_seen ?? [])->push($row['source_type'])->unique()->values()->all();

        $case->case_number = $row['case_number'];
        $case->case_number_normalized = $row['case_number_normalized'];
        $case->process_number = $row['source_process_number'];
        $case->process_number_normalized = $row['process_number_normalized'];
        $case->installation_number = $row['source_installation_number'];
        $case->installation_number_normalized = $this->normalizer->normalizeProcessNumber($row['source_installation_number']);
        $case->process_status = $row['process_status_at_import'];
        $case->company_name = $row['company_name'];
        $case->process_manager = $row['process_manager'];
        $case->law_firm = $row['law_firm'];
        $case->district = $row['district'];
        $case->process_nature = $row['process_nature'];
        $case->process_cause = $row['process_cause'];
        $case->identity_key = $row['identity_key'];
        $case->identity_strategy = 'case_number_plus_process_number';
        $case->identity_confidence = 'high';
        $case->sources_seen = $sourcesSeen;
        $case->last_seen_at = $now;
        $case->last_import_batch_id = $batch?->id;
        $case->raw_latest_payload = $row['raw_payload'];
        $case->normalized_latest_payload = $row['normalized_payload'];
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
            ->where('source_type', $row['source_type'])
            ->where('source_record_key', $row['source_record_key'])
            ->first();

        $matchedByStableSignature = false;
        if (!$existing) {
            $existing = $this->findExistingDemandByStableSignature($row, $case);
            $matchedByStableSignature = $existing !== null;
        }

        if (!$existing) {
            if ($dryRun) {
                return ['counter' => 'new_rows', 'demand_id' => null, 'returned' => false];
            }

            $demand = new LegalDemand();
            $demand->uuid = (string) str()->uuid();
            $demand->legal_case_id = $case->id;
            $demand->source_type = $row['source_type'];
            $demand->source_table = $row['source_table'];
            $demand->source_version = $row['source_version'];
            $demand->source_record_key = $row['source_record_key'];
            $demand->source_record_key_strategy = $row['source_record_key_strategy'];
            $demand->source_record_key_confidence = $row['source_record_key_confidence'];
            $demand->source_entity_key = $row['source_entity_key'];
            $demand->source_hash = $row['source_hash'];
            $demand->source_case_number = $row['case_number'];
            $demand->source_process_number = $row['source_process_number'];
            $demand->source_installation_number = $row['source_installation_number'];
            $demand->title = $this->buildDemandTitle($row['source_subject']);
            $demand->first_seen_at = $now;
            $demand->last_seen_at = $now;
            $demand->source_presence_status = LegalSourcePresenceStatus::PRESENT;
            $demand->internal_status = LegalDemandInternalStatus::NEW_IMPORTED;

            $this->fillDemandMutableFields($demand, $row, $batch, $now);
            $demand->action_state = $this->computeActionState($demand);
            $demand->save();

            $this->recordEvent($demand->id, $batch?->id, 'imported', null, LegalDemandInternalStatus::NEW_IMPORTED->value, 'Demanda criada via importacao juridica v2.');
            $this->recordSnapshot($demand, $batch, $row, $now);

            return ['counter' => 'new_rows', 'demand_id' => $demand->id, 'returned' => false];
        }

        $presenceStatus = $existing->source_presence_status;
        $presenceValue = $presenceStatus instanceof LegalSourcePresenceStatus
            ? $presenceStatus->value
            : (is_string($presenceStatus) ? $presenceStatus : null);
        $wasMissing = $presenceValue === LegalSourcePresenceStatus::MISSING->value;

        if ($existing->source_hash === $row['source_hash']) {
            if (!$dryRun) {
                $existing->last_seen_at = $now;
                $existing->source_presence_status = LegalSourcePresenceStatus::PRESENT;
                $existing->last_seen_import_batch_id = $batch?->id;

                if ($wasMissing) {
                    $existing->missing_since = null;
                    $existing->last_returned_batch_id = $batch?->id;
                    $this->recordEvent($existing->id, $batch?->id, 'source_returned', null, null, 'Demanda reapareceu na origem.');
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
        $oldRecordKey = $existing->source_record_key;
        $changes = $this->computeSourceChanges($existing, $row);

        $existing->legal_case_id = $case->id;
        $existing->source_table = $row['source_table'];
        $existing->source_version = $row['source_version'];
        $existing->source_record_key = $row['source_record_key'];
        $existing->source_record_key_strategy = $row['source_record_key_strategy'];
        $existing->source_record_key_confidence = $row['source_record_key_confidence'];
        $existing->source_entity_key = $row['source_entity_key'];
        $existing->source_hash = $row['source_hash'];
        $existing->source_case_number = $row['case_number'];
        $existing->source_process_number = $row['source_process_number'];
        $existing->source_installation_number = $row['source_installation_number'];
        $existing->title = $this->buildDemandTitle($row['source_subject']);
        $existing->missing_since = null;
        $existing->source_presence_status = LegalSourcePresenceStatus::PRESENT;

        $preservedDueAt = false;
        if (($row['source_due_at'] ?? null) === null && $existing->source_due_at !== null) {
            $preservedDueAt = true;
        }

        $this->fillDemandMutableFields($existing, $row, $batch, $now);

        if ($wasMissing) {
            $existing->last_returned_batch_id = $batch?->id;
            $this->recordEvent($existing->id, $batch?->id, 'source_returned', null, null, 'Demanda reapareceu na origem.');
        }

        $existing->action_state = $this->computeActionState($existing);
        $existing->save();

        if ($matchedByStableSignature && $oldRecordKey !== $row['source_record_key']) {
            $this->recordEvent(
                $existing->id,
                $batch?->id,
                'source_rekeyed',
                null,
                null,
                'Registro da origem mudou a chave tecnica, mas foi reconciliado pela assinatura estavel.'
            );
        }

        if ($preservedDueAt) {
            $this->recordEvent(
                $existing->id,
                $batch?->id,
                'source_due_at_preserved',
                null,
                null,
                'Origem veio sem prazo e o sistema preservou o primeiro prazo preenchido.'
            );
        }

        $this->recordEvent(
            $existing->id,
            $batch?->id,
            'updated_from_source',
            $fromStatus,
            $existing->internal_status?->value,
            'Demanda atualizada pela origem externa.',
            [
                'match_strategy' => $matchedByStableSignature ? 'stable_signature' : 'source_record_key',
                'old_source_record_key' => $oldRecordKey,
                'new_source_record_key' => $row['source_record_key'] ?? null,
                'changed_fields_count' => count($changes),
                'changed_fields' => $changes,
            ]
        );

        if ($forceSnapshot || $oldHash !== $row['source_hash']) {
            $this->recordSnapshot($existing, $batch, $row, $now, $changes);
        }

        return ['counter' => 'updated_rows', 'demand_id' => $existing->id, 'returned' => $wasMissing];
    }

    private function findExistingDemandByStableSignature(array $row, LegalCase $case): ?LegalDemand
    {
        $signature = $this->makeDemandStableSignature($row);
        if ($signature === null) {
            return null;
        }

        $candidates = LegalDemand::query()
            ->where('source_type', $row['source_type'])
            ->where('legal_case_id', $case->id)
            ->where('source_entity_key', $row['source_entity_key'])
            ->whereNotIn('internal_status', [
                LegalDemandInternalStatus::CLOSED_EXTERNAL->value,
                LegalDemandInternalStatus::CLOSED_INTERNAL->value,
                LegalDemandInternalStatus::CANCELLED->value,
                LegalDemandInternalStatus::IGNORED->value,
            ])
            ->orderByDesc('last_seen_at')
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->makeDemandStableSignature($candidate->toArray()) === $signature) {
                return $candidate;
            }
        }

        return null;
    }

    private function makeDemandStableSignature(array $data): ?string
    {
        $sourceType = (string) ($data['source_type'] ?? '');
        $entityKey = (string) ($data['source_entity_key'] ?? '');
        $subject = mb_strtolower(trim((string) ($data['source_subject'] ?? '')));
        $responsibleArea = mb_strtolower(trim((string) ($data['responsible_area_name'] ?? '')));
        $requestingArea = mb_strtolower(trim((string) ($data['requesting_area_name'] ?? '')));

        if ($sourceType === '' || $entityKey === '' || $subject === '') {
            return null;
        }

        $parts = [
            $sourceType,
            $entityKey,
            $subject,
            $responsibleArea,
            $requestingArea,
        ];

        return hash('sha256', implode('|', $parts));
    }

    private function fillDemandMutableFields(LegalDemand $demand, array $row, ?LegalImportBatch $batch, Carbon $now): void
    {
        $this->assignFromSourcePreservingNonNull($demand, 'source_subject', $row['source_subject'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'source_description', $row['source_description'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'source_status', $row['source_status'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'source_status_at', $row['source_status_at'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'source_status_group', $row['source_status_group'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'process_status_at_import', $row['process_status_at_import'] ?? null);

        $this->assignFromSourcePreservingNonNull($demand, 'requesting_area_name', $row['requesting_area_name'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'requesting_responsible_name', $row['requesting_responsible_name'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'responsible_area_name', $row['responsible_area_name'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'delegated_responsible_name', $row['delegated_responsible_name'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'delegated_by_name', $row['delegated_by_name'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'delegated_at', $row['delegated_at'] ?? null);

        $this->assignFromSourcePreservingNonNull($demand, 'source_due_at', $row['source_due_at'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'source_decision_at', $row['source_decision_at'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'source_end_at', $row['source_end_at'] ?? null);

        $this->assignFromSourcePreservingNonNull($demand, 'summary', $row['source_description'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'priority', $row['priority'] ?? null);
        $this->assignFromSourcePreservingNonNull($demand, 'risk_level', $row['risk_level'] ?? null);

        $demand->last_seen_at = $now;
        $demand->last_seen_import_batch_id = $batch?->id;

        $demand->needs_identity_review = (bool) $row['needs_identity_review'];
        $demand->needs_status_review = (bool) $row['needs_status_review'];

        $demand->raw_payload = $row['raw_payload'];
        $demand->normalized_payload = $row['normalized_payload'];
        $demand->source_specific_payload = $row['source_specific_payload'];
    }

    private function assignFromSourcePreservingNonNull(LegalDemand $demand, string $field, mixed $incoming): void
    {
        if ($incoming !== null) {
            $demand->{$field} = $incoming;
            return;
        }

        if ($demand->{$field} === null) {
            $demand->{$field} = null;
        }
    }

    private function buildDemandTitle(?string $subject): ?string
    {
        $value = $this->normalizer->normalizeText($subject);
        if ($value === null) {
            return null;
        }

        return mb_substr($value, 0, 255);
    }

    private function computeActionState(LegalDemand $demand): string
    {
        $internal = (string) ($demand->internal_status?->value ?? $demand->internal_status ?? '');
        $group = (string) ($demand->source_status_group ?? 'unknown');

        if ($internal === LegalDemandInternalStatus::IGNORED->value) {
            return 'ignored';
        }

        if (in_array($internal, [
            LegalDemandInternalStatus::CLOSED_INTERNAL->value,
            LegalDemandInternalStatus::CLOSED_EXTERNAL->value,
            LegalDemandInternalStatus::CANCELLED->value,
        ], true)) {
            return 'no_action_sicode_closed';
        }

        if (in_array($group, ['closed_done', 'closed_cancelled'], true)) {
            return 'no_action_source_closed';
        }

        if ($group === 'unknown' || (bool) $demand->needs_status_review) {
            return 'needs_review';
        }

        if (in_array($internal, [
            LegalDemandInternalStatus::RETURNED_BY_FIELD->value,
            LegalDemandInternalStatus::UNDER_CONTROLLER_REVIEW->value,
            LegalDemandInternalStatus::RETURNED_FOR_CORRECTION->value,
            LegalDemandInternalStatus::READY_TO_CLOSE_EXTERNAL->value,
        ], true)) {
            return 'waiting_controller_review';
        }

        if (in_array($group, ['open_in_progress', 'open_delegated', 'open_redirected'], true)) {
            if ($demand->current_assigned_user_id || $demand->current_assigned_team_id) {
                return 'waiting_user_action';
            }

            return 'waiting_controller_triage';
        }

        return 'needs_review';
    }

    private function recordSnapshot(LegalDemand $demand, ?LegalImportBatch $batch, array $row, Carbon $seenAt, ?array $changedFields = null): void
    {
        LegalSourceSnapshot::create([
            'legal_demand_id' => $demand->id,
            'import_batch_id' => $batch?->id,
            'source_type' => $row['source_type'],
            'source_record_key' => $row['source_record_key'],
            'source_hash' => $row['source_hash'],
            'raw_payload' => $row['raw_payload'],
            'normalized_payload' => $row['normalized_payload'],
            'source_specific_payload' => $row['source_specific_payload'],
            'changed_fields' => $changedFields,
            'seen_at' => $seenAt,
        ]);
    }

    private function recordEvent(
        int $demandId,
        ?int $importBatchId,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $description,
        array $metadata = []
    ): void {
        LegalDemandEvent::create([
            'legal_demand_id' => $demandId,
            'import_batch_id' => $importBatchId,
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'description' => $description,
            'metadata' => array_merge(['source' => 'legal_import_v2'], $metadata),
            'occurred_at' => now(),
        ]);
    }

    private function computeSourceChanges(LegalDemand $existing, array $row): array
    {
        $fields = [
            'source_record_key',
            'source_subject',
            'source_description',
            'source_status',
            'source_status_group',
            'process_status_at_import',
            'requesting_area_name',
            'requesting_responsible_name',
            'responsible_area_name',
            'delegated_responsible_name',
            'delegated_by_name',
            'delegated_at',
            'source_due_at',
            'source_decision_at',
            'source_end_at',
            'source_presence_status',
            'source_process_number',
            'source_case_number',
        ];

        $changes = [];
        foreach ($fields as $field) {
            $before = $existing->{$field} ?? null;
            $after = $row[$field] ?? null;

            $beforeNorm = $this->normalizeComparableValue($before);
            $afterNorm = $this->normalizeComparableValue($after);

            if ($beforeNorm !== $afterNorm) {
                $changes[$field] = [
                    'before' => $beforeNorm,
                    'after' => $afterNorm,
                ];
            }
        }

        return $changes;
    }

    private function normalizeComparableValue(mixed $value): mixed
    {
        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $value;
    }

    private function markMissingDemands(string $sourceType, array $seenDemandIds, ?LegalImportBatch $batch): int
    {
        $query = LegalDemand::query()
            ->where('source_type', $sourceType)
            ->whereNotIn('id', $seenDemandIds)
            ->whereNotIn('internal_status', [
                LegalDemandInternalStatus::CLOSED_EXTERNAL->value,
                LegalDemandInternalStatus::CLOSED_INTERNAL->value,
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
            $demand->action_state = $this->computeActionState($demand);
            $demand->save();

            $this->recordEvent($demand->id, $batch?->id, 'source_missing', null, null, 'Demanda ausente na leitura atual da origem.');
            $count++;
        }

        return $count;
    }
}
