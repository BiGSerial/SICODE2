<?php

namespace Tests\Feature;

use App\Http\Livewire\Partner\Show\ShowWorkForm;
use App\Models\{Company, File, Note, Production, Service, User, WorkReport, WorkReportFlowProduction};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShowWorkFormFilesTest extends TestCase
{
    use RefreshDatabase;

    private function file(Note $note, Service $service, string $name): File
    {
        return File::create([
            'note_id' => $note->id, 'service_id' => $service->uuid, 'file_name' => $name,
            'path'    => "test/{$name}", 'ext' => 'pdf', 'mime' => 'application/pdf',
        ]);
    }

    private function report(Note $note, Company $company, User $user, string $informedAt): WorkReport
    {
        return WorkReport::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'date'    => substr($informedAt, 0, 10), 'informed_at' => $informedAt, 'rejected' => false, 'canceled' => false,
        ]);
    }

    private function production(Note $note, Service $service, Company $company, User $user, WorkReport $report, string $stage): Production
    {
        $production = Production::create([
            'note_id'   => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'user_id' => $user->id,
            'completed' => false, 'partial' => false,
        ]);
        WorkReportFlowProduction::create([
            'work_report_id' => $report->id, 'production_id' => $production->id, 'stage' => $stage,
            'final_scope'    => 'general', 'is_current' => true, 'linked_at' => now(),
        ]);

        return $production;
    }

    public function test_modal_lists_only_files_of_the_productions_and_attachments_of_that_report(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        $fiscal = Service::create(['service' => 'Fiscalização']);
        $note   = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);

        $reportA = $this->report($note, $company, $user, '2026-09-25 09:00:00');
        $reportB = $this->report($note, $company, $user, '2026-10-03 09:00:00');

        $prodA = $this->production($note, $fiscal, $company, $user, $reportA, 'fiscalization');
        $prodB = $this->production($note, $fiscal, $company, $user, $reportB, 'fiscalization');

        $legacyA = $this->file($note, $fiscal, 'FISC_A_LEGADO.pdf');
        $morphA  = $this->file($note, $fiscal, 'FISC_A_MORPH.pdf');
        $ownA    = $this->file($note, $fiscal, 'INFO_A_PROPRIO.pdf');
        $fileB   = $this->file($note, $fiscal, 'FISC_B_OUTRO_INFORME.pdf');
        $loose   = $this->file($note, $fiscal, 'SOLTO_DA_NOTA.pdf');

        $prodA->Files()->attach($legacyA->id);
        $prodA->morphFiles()->attach($morphA->id);
        $reportA->Files()->attach($ownA->id);
        $prodB->Files()->attach($fileB->id);

        Livewire::test(ShowWorkForm::class)
            ->call('show_form', $reportA->id)
            ->assertSee('FISC_A_LEGADO')
            ->assertSee('FISC_A_MORPH')
            ->assertSee('INFO_A_PROPRIO')
            ->assertDontSee('FISC_B_OUTRO_INFORME')
            ->assertDontSee('SOLTO_DA_NOTA')
            ->assertSee('3 arquivo(s)');

        Livewire::test(ShowWorkForm::class)
            ->call('show_form', $reportB->id)
            ->assertSee('FISC_B_OUTRO_INFORME')
            ->assertDontSee('FISC_A_LEGADO')
            ->assertSee('1 arquivo(s)');
    }

    public function test_report_without_linked_productions_shows_no_files(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        $service = Service::create(['service' => 'Fiscalização']);
        $note    = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        $report  = $this->report($note, $company, $user, '2026-09-25 09:00:00');
        $this->file($note, $service, 'SOLTO_DA_NOTA.pdf');

        Livewire::test(ShowWorkForm::class)
            ->call('show_form', $report->id)
            ->assertSee('NENHUM ARQUIVO ANEXADO')
            ->assertDontSee('SOLTO_DA_NOTA');
    }
}
