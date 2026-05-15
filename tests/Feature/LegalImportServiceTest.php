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
            'external_case_number' => 'EXT-100',
            'process_number' => '5001552-88.2026.8.08.0038',
            'external_status' => 'Em andamento',
            'company_name' => 'ACME',
            'process_manager' => 'Gestor 1',
            'law_firm' => 'Escritorio 1',
            'current_responsible_area' => 'Operacao',
            'current_responsible_name' => 'Analista A',
            'requesting_area' => 'Juridico',
            'requesting_responsible_name' => 'Solicitante',
            'subject' => 'Assunto base',
            'description' => 'Descricao base',
            'decision_at' => '2026-05-01 10:00:00',
            'compliance_deadline_at' => '2026-05-10 10:00:00',
            'judgment_status' => 'Aberto',
            'changed_at' => '2026-05-02 10:00:00',
            'raw_payload' => ['k' => 'v'],
        ], $override);
    }

    public function test_import_is_idempotent_for_same_source_rows(): void
    {
        $service = app(LegalImportService::class);
        $rows = [$this->row(), $this->row(['external_case_number' => 'EXT-101'])];

        $service->import('sentence', ['source_rows' => $rows]);
        $second = $service->import('sentence', ['source_rows' => $rows]);

        $this->assertDatabaseCount('legal_demands', 2);
        $this->assertDatabaseCount('legal_cases', 1);
        $this->assertSame(2, $second['unchanged_rows']);
    }

    public function test_due_date_change_updates_hash_creates_snapshot_and_event(): void
    {
        $service = app(LegalImportService::class);
        $service->import('sentence', ['source_rows' => [$this->row()]]);

        $updatedRow = $this->row(['compliance_deadline_at' => '2026-05-15 10:00:00']);
        $service->import('sentence', ['source_rows' => [$updatedRow], 'force_snapshot' => true]);

        $demand = LegalDemand::query()->firstOrFail();

        $this->assertSame('2026-05-15 10:00:00', $demand->source_due_at?->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('legal_demand_events', [
            'legal_demand_id' => $demand->id,
            'event_type' => 'updated_from_source',
        ]);
        $this->assertGreaterThanOrEqual(2, $demand->SourceSnapshots()->count());
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

        $this->assertNull($demand->missing_since);
        $this->assertDatabaseHas('legal_demand_events', [
            'legal_demand_id' => $demand->id,
            'event_type' => 'source_returned',
        ]);
    }

    public function test_same_process_can_generate_multiple_demands_from_different_sources(): void
    {
        $service = app(LegalImportService::class);

        $common = [
            'process_number' => '5001552-88.2026.8.08.0038',
            'company_name' => 'ACME',
            'external_case_number' => 'EXT-900',
        ];

        $service->import('liminar', ['source_rows' => [[
            ...$this->row([
                ...$common,
                'subject' => null,
                'description' => 'Desc liminar',
                'started_at' => '2026-05-01 08:00:00',
                'redirect_deadline_at' => '2026-05-09 08:00:00',
                'injunction_modality' => 'Modalidade X',
                'injunction_situation' => 'Ativa',
                'injunction_status' => 'Aberta',
            ]),
        ]]]);

        $service->import('subsidy', ['source_rows' => [[
            ...$this->row([
                ...$common,
                'information_request_type' => 'Subsidio tecnico',
                'information_request_status' => 'Pendente',
                'deadline_at' => '2026-05-12 08:00:00',
                'rejection' => null,
            ]),
        ]]]);

        $this->assertDatabaseCount('legal_cases', 1);
        $this->assertDatabaseCount('legal_demands', 2);
        $this->assertDatabaseHas('legal_demands', ['source_type' => 'liminar']);
        $this->assertDatabaseHas('legal_demands', ['source_type' => 'subsidy']);
    }
}
