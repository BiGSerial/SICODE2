<?php

namespace Tests\Feature;

use App\Models\Legal\LegalCase;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalImportBatch;
use App\Services\Legal\LegalImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class LegalCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_all_rejects_invalid_source_option(): void
    {
        $this->artisan('legal:import-all', ['--source' => 'invalid'])
            ->expectsOutputToContain('Fonte invalida: invalid')
            ->assertExitCode(1);
    }

    public function test_import_all_runs_single_source_with_shared_service(): void
    {
        $this->mock(LegalImportService::class, function (MockInterface $mock) {
            $mock->shouldReceive('import')
                ->once()
                ->with('injunction', \Mockery::type('array'))
                ->andReturn([
                    'source' => 'injunction',
                    'batch_id' => null,
                    'total_rows' => 1,
                    'new_rows' => 1,
                    'updated_rows' => 0,
                    'unchanged_rows' => 0,
                    'missing_rows' => 0,
                    'returned_rows' => 0,
                    'failed_rows' => 0,
                    'errors' => [],
                    'avg_row_seconds' => 0.0,
                ]);
        });

        $this->artisan('legal:import-all', ['--source' => 'injunction', '--dry' => true])
            ->expectsOutputToContain('Importando fonte injunction...')
            ->expectsOutputToContain('Retornadas: 0')
            ->assertExitCode(0);
    }

    public function test_metrics_command_prints_operational_panels(): void
    {
        $case = LegalCase::create([
            'uuid' => (string) str()->uuid(),
            'case_number' => '100',
            'case_number_normalized' => '100',
            'process_number' => '5001552-88.2026.8.08.0038',
            'process_number_normalized' => '50015528820268080038',
            'process_number_core' => '5001552882026',
            'identity_key' => hash('sha256', '100|5001552882026'),
            'company_name' => 'ACME',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        LegalDemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_case_id' => $case->id,
            'source_type' => 'injunction',
            'source_occurrence_key' => hash('sha256', 'injunction|100|5001552882026|SERVICO|2026-05-01 10:00:00'),
            'source_hash' => hash('sha256', 'h'),
            'internal_status' => 'triage',
            'source_presence_status' => 'present',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        LegalImportBatch::create([
            'source_type' => 'injunction',
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'total_rows' => 1,
            'new_rows' => 1,
            'updated_rows' => 0,
            'unchanged_rows' => 0,
            'missing_rows' => 0,
            'returned_rows' => 0,
            'failed_rows' => 0,
            'status' => 'finished',
        ]);

        $this->artisan('legal:metrics', ['--days' => 30])
            ->expectsOutputToContain('Cards - Visão Geral')
            ->expectsOutputToContain('Volumes por fonte')
            ->expectsOutputToContain('Pendências por responsável')
            ->expectsOutputToContain('Demandas com anexos/comentários')
            ->expectsOutputToContain('Saúde da importação')
            ->assertExitCode(0);
    }
}

