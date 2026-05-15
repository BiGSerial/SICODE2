<?php

namespace Tests\Feature;

use App\Enum\LegalDemandInternalStatus;
use App\Models\File;
use App\Models\Legal\LegalCase;
use App\Models\Legal\LegalDemand;
use App\Models\Legal\LegalDemandFile;
use App\Models\Note;
use App\Models\User;
use App\Services\Legal\LegalDemandFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LegalDemandFilesTest extends TestCase
{
    use RefreshDatabase;

    private function allowPermissions(): void
    {
        $abilities = [
            'legal.demands.view',
            'legal.demands.review',
            'legal.demands.manage_files',
            'legal.demands.close_external',
            'legal.demands.view_controller_files',
        ];

        foreach ($abilities as $ability) {
            Gate::define($ability, fn () => true);
        }
    }

    private function makeDemandWithController(User $controller): LegalDemand
    {
        $case = LegalCase::create([
            'uuid' => (string) str()->uuid(),
            'process_number' => '1234567-89.2026.8.26.0100',
            'process_number_normalized' => '12345678920268260100',
            'company_name' => 'ACME',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return LegalDemand::create([
            'uuid' => (string) str()->uuid(),
            'legal_case_id' => $case->id,
            'source_type' => 'liminar',
            'source_external_id' => 'EXT-1',
            'source_record_key' => hash('sha256', uniqid('file-demand', true)),
            'source_hash' => hash('sha256', uniqid('file-hash', true)),
            'title' => 'Demanda',
            'subject' => 'Assunto',
            'internal_status' => LegalDemandInternalStatus::TRIAGE->value,
            'source_presence_status' => 'present',
            'controller_user_id' => $controller->id,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    private function makeFile(User $user): File
    {
        $note = Note::create(['note' => 'N' . random_int(1000, 9999)]);

        return File::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'file_name' => 'arquivo_teste.pdf',
            'path' => 'tmp/arquivo_teste.pdf',
            'ext' => 'pdf',
        ]);
    }

    public function test_user_without_permission_cannot_view_controller_only_file(): void
    {
        $this->allowPermissions();
        Gate::define('legal.demands.view_controller_files', fn () => false);

        $controller = User::factory()->create();
        $other = User::factory()->create();
        $demand = $this->makeDemandWithController($controller);
        $file = $this->makeFile($controller);

        $service = new LegalDemandFileService();
        $link = $service->attach($demand, $file, $controller, [
            'category' => 'controller_note',
            'visibility' => 'controller_only',
        ]);

        $this->assertFalse($service->canView($link->fresh(), $other));
        $this->assertTrue($service->canView($link->fresh(), $controller));
    }

    public function test_external_ready_file_appears_in_external_closure_query(): void
    {
        $this->allowPermissions();

        $controller = User::factory()->create();
        $demand = $this->makeDemandWithController($controller);
        $file = $this->makeFile($controller);
        $service = new LegalDemandFileService();

        $service->attach($demand, $file, $controller, [
            'category' => 'final_response',
            'visibility' => 'external_ready',
            'is_final_response' => true,
            'can_be_sent_external' => true,
        ]);

        $this->assertSame(1, $service->queryExternalReady($demand)->count());
    }

    public function test_logical_delete_keeps_history_and_marks_removed_at(): void
    {
        $this->allowPermissions();

        $controller = User::factory()->create();
        $demand = $this->makeDemandWithController($controller);
        $file = $this->makeFile($controller);
        $service = new LegalDemandFileService();

        $link = $service->attach($demand, $file, $controller, [
            'category' => 'legal_document',
            'visibility' => 'internal_all',
        ]);

        $service->removeLogical($link, $controller, 'Arquivo substituído');

        $this->assertDatabaseHas('legal_demand_files', [
            'id' => $link->id,
            'file_id' => $file->id,
        ]);
        $this->assertNotNull(LegalDemandFile::find($link->id)?->removed_at);
        $this->assertDatabaseHas('legal_demand_events', [
            'legal_demand_id' => $demand->id,
            'event_type' => 'file_removed',
        ]);
    }
}
