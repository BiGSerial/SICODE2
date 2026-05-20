<?php

namespace Tests\Feature;

use App\Enum\LegalDemandInternalStatus;
use App\Models\Legal\LegalCase;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalImportBatch;
use App\Services\Legal\LegalObservabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalObservabilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeCase(): LegalCase
    {
        return LegalCase::create([
            'uuid' => (string) str()->uuid(),
            'process_number' => '1234567-89.2026.8.26.0100',
            'process_number_normalized' => '12345678920268260100',
            'company_name' => 'ACME',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    public function test_overview_cards_counts_match_dataset(): void
    {
        $case = $this->makeCase();

        LegalDemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_case_id' => $case->id,
            'source_type' => 'injunction',
            'source_external_id' => 'A',
            'source_occurrence_key' => hash('sha256', uniqid('a', true)),
            'source_hash' => hash('sha256', uniqid('ah', true)),
            'internal_status' => LegalDemandInternalStatus::TRIAGE->value,
            'source_presence_status' => 'present',
            'source_due_at' => now()->subDay(),
            'first_seen_at' => now()->subDays(2),
            'last_seen_at' => now(),
        ]);

        LegalDemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_case_id' => $case->id,
            'source_type' => 'sentence',
            'source_external_id' => 'B',
            'source_occurrence_key' => hash('sha256', uniqid('b', true)),
            'source_hash' => hash('sha256', uniqid('bh', true)),
            'internal_status' => LegalDemandInternalStatus::READY_TO_CLOSE_EXTERNAL->value,
            'source_presence_status' => 'missing',
            'source_due_at' => now()->addDay(),
            'first_seen_at' => now()->subDays(3),
            'last_seen_at' => now(),
        ]);

        LegalDemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_case_id' => $case->id,
            'source_type' => 'subsidy',
            'source_external_id' => 'C',
            'source_occurrence_key' => hash('sha256', uniqid('c', true)),
            'source_hash' => hash('sha256', uniqid('ch', true)),
            'internal_status' => LegalDemandInternalStatus::CLOSED_INTERNAL->value,
            'source_presence_status' => 'present',
            'external_closed_at' => null,
            'first_seen_at' => now()->subDays(5),
            'last_seen_at' => now(),
        ]);

        $cards = app(LegalObservabilityService::class)->overviewCards();

        $this->assertSame(3, $cards['total_abertas']);
        $this->assertSame(1, $cards['total_vencidas']);
        $this->assertSame(3, $cards['total_sem_responsavel']);
        $this->assertSame(1, $cards['total_missing_source']);
    }

    public function test_import_health_returns_duration_and_counters(): void
    {
        LegalImportBatch::create([
            'source_type' => 'injunction',
            'started_at' => now()->subMinutes(5),
            'finished_at' => now()->subMinutes(4),
            'total_rows' => 10,
            'new_rows' => 2,
            'updated_rows' => 1,
            'unchanged_rows' => 7,
            'missing_rows' => 1,
            'failed_rows' => 0,
            'status' => 'finished',
        ]);

        $rows = app(LegalObservabilityService::class)->importHealth(30);

        $this->assertCount(1, $rows);
        $this->assertSame(10, $rows[0]['total_rows']);
        $this->assertSame('finished', $rows[0]['status']);
        $this->assertIsInt($rows[0]['duration_seconds']);
    }
}
