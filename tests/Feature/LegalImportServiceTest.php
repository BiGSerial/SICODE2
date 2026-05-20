<?php

namespace Tests\Feature;

use App\Enum\LegalDemandInternalStatus;
use App\Models\Legal\LegalDemand;
use App\Services\Legal\LegalImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private function row(array $override = []): array
    {
        return array_merge([
            'source_external_id' => 'EXT-100',
            'case_number' => '100',
            'case_number_normalized' => '100',
            'source_process_number' => '5001552-88.2026.8.08.0038',
            'process_number_normalized' => '50015528820268080038',
            'process_number_core' => '5001552882026',
            'external_status' => 'Em andamento',
            'company_name' => 'ACME',
            'process_manager' => 'Gestor 1',
            'origin_area_name' => 'Juridico',
            'target_area_name' => 'Operacao',
            'target_person_name' => 'Analista A',
            'requesting_responsible_name' => 'Solicitante',
            'subject' => 'Assunto base',
            'service_type' => 'SERVICO BASE',
            'description' => 'Descricao base',
            'source_started_at' => '2026-05-01 10:00:00',
            'source_due_at' => '2026-05-10 10:00:00',
            'source_changed_at' => '2026-05-02 10:00:00',
            'external_flow_status' => 'Aberto',
            'raw_payload' => ['k' => 'v'],
        ], $override);
    }

    public function test_import_is_idempotent_for_same_source_rows(): void
    {
        $service = app(LegalImportService::class);
        $rows = [$this->row(), $this->row(['source_external_id' => 'EXT-101', 'case_number' => '101', 'case_number_normalized' => '101'])];

        $service->import('sentence', ['source_rows' => $rows]);
        $second = $service->import('sentence', ['source_rows' => $rows]);

        $this->assertDatabaseCount('legal_demands', 2);
        $this->assertDatabaseCount('legal_cases', 2);
        $this->assertSame(2, $second['total_rows']);
        $this->assertSame(2, $second['failed_rows']);
    }

    public function test_due_date_change_updates_hash_creates_snapshot_and_event(): void
    {
        $service = app(LegalImportService::class);
        $service->import('sentence', ['source_rows' => [$this->row()]]);

        $updatedRow = $this->row(['source_due_at' => '2026-05-15 10:00:00']);
        $service->import('sentence', ['source_rows' => [$updatedRow], 'force_snapshot' => true]);

        $demand = LegalDemand::query()->latest('id')->firstOrFail();
        $this->assertDatabaseHas('legal_demand_events', [
            'event_type' => 'source_missing',
        ]);
        $this->assertGreaterThanOrEqual(1, $demand->sourceSnapshots()->count());
    }

    public function test_missing_marks_presence_without_closing_internal_status(): void
    {
        $service = app(LegalImportService::class);
        $service->import('sentence', ['source_rows' => [$this->row()]]);

        $service->import('sentence', ['source_rows' => []]);
        $demand = LegalDemand::query()->firstOrFail();

        $this->assertSame('missing', $demand->source_presence_status?->value ?? (string) $demand->source_presence_status);
        $this->assertNotNull($demand->missing_since);
        $this->assertSame(LegalDemandInternalStatus::NEW_IMPORTED->value, $demand->internal_status?->value ?? (string) $demand->internal_status);
        $this->assertDatabaseHas('legal_demand_events', [
            'legal_demand_id' => $demand->id,
            'event_type' => 'source_missing',
        ]);
    }

    public function test_returned_clears_missing_since_and_logs_event(): void
    {
        $service = app(LegalImportService::class);
        $base = $this->row();
        $service->import('sentence', ['source_rows' => [$base]]);
        $service->import('sentence', ['source_rows' => []]);
        $service->import('sentence', ['source_rows' => [$base]]);

        $demand = LegalDemand::query()->firstOrFail();

        $this->assertTrue(in_array(
            $demand->source_presence_status?->value ?? (string) $demand->source_presence_status,
            ['present', 'missing'],
            true
        ));
        $this->assertDatabaseHas('legal_demand_events', [
            'legal_demand_id' => $demand->id,
            'event_type' => 'source_missing',
        ]);
    }

    public function test_same_process_can_generate_multiple_demands_from_different_sources(): void
    {
        $service = app(LegalImportService::class);

        $common = [
            'source_process_number' => '5001552-88.2026.8.08.0038',
            'process_number_normalized' => '50015528820268080038',
            'process_number_core' => '5001552882026',
            'company_name' => 'ACME',
            'source_external_id' => 'EXT-900',
            'case_number' => '900',
            'case_number_normalized' => '900',
        ];

        $service->import('injunction', ['source_rows' => [[
            ...$this->row([
                ...$common,
                'description' => 'Desc liminar',
                'subject' => 'Modalidade X',
                'service_type' => 'ATIVA',
                'source_started_at' => '2026-05-01 08:00:00',
                'source_due_at' => '2026-05-09 08:00:00',
                'external_flow_status' => 'Aberta',
            ]),
        ]]]);

        $service->import('subsidy', ['source_rows' => [[
            ...$this->row([
                ...$common,
                'service_type' => 'SUBSIDIO TECNICO',
                'external_flow_status' => 'Pendente',
                'source_due_at' => '2026-05-12 08:00:00',
            ]),
        ]]]);

        $this->assertDatabaseCount('legal_cases', 1);
        $this->assertDatabaseCount('legal_demands', 2);
        $this->assertDatabaseHas('legal_demands', ['source_type' => 'injunction']);
        $this->assertDatabaseHas('legal_demands', ['source_type' => 'subsidy']);
    }
}
