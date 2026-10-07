<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\Control\D5Edit;
use App\Models\{Company, FiveNote, Note, Production, Service, User, WorkReport};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminD5EditTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_preserves_completed_status_and_dates_of_a_supervisioned_d5(): void
    {
        $five = $this->fiveNote([
            'is_completed'     => true,
            'completed_at'     => '2026-10-04 10:15:00',
            'is_supervisioned' => true,
            'supervisioned_at' => '2026-10-05 11:30:00',
            'is_payed'         => true,
            'payed_at'         => '2026-10-06 12:45:00',
        ]);

        Livewire::test(D5Edit::class)
            ->call('getInfoResponse', $five)
            ->set('five.description', 'Descricao mantida exatamente como editada.')
            ->assertSet('five.description', 'Descricao mantida exatamente como editada.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('five_notes', [
            'id'               => $five->id,
            'description'      => 'Descricao mantida exatamente como editada.',
            'is_completed'     => true,
            'completed_at'     => '2026-10-04 10:15:00',
            'is_supervisioned' => true,
            'supervisioned_at' => '2026-10-05 11:30:00',
            'is_payed'         => true,
            'payed_at'         => '2026-10-06 12:45:00',
        ]);
    }

    public function test_it_deletes_only_an_open_unassigned_production_without_history(): void
    {
        $five       = $this->fiveNote();
        $production = $this->productionFor($five->note_id, $five->company_id);
        $five->productions()->attach($production->id);

        Livewire::test(D5Edit::class)
            ->call('getInfoResponse', $five)
            ->call('requestDeleteProduction', $production->id)
            ->call('deleteProduction');

        $this->assertDatabaseMissing('productions', ['id' => $production->id]);
    }

    public function test_it_deletes_an_assigned_production_after_the_confirmation_action(): void
    {
        $five       = $this->fiveNote();
        $production = $this->productionFor($five->note_id, $five->company_id, ['att_by' => (string) Str::uuid()]);
        $five->productions()->attach($production->id);

        Livewire::test(D5Edit::class)
            ->call('getInfoResponse', $five)
            ->call('requestDeleteProduction', $production->id)
            ->call('deleteProduction');

        $this->assertDatabaseMissing('productions', ['id' => $production->id]);
    }

    public function test_it_associates_an_informe_to_the_linked_production(): void
    {
        $five       = $this->fiveNote();
        $production = $this->productionFor($five->note_id, $five->company_id);
        $five->productions()->attach($production->id);
        $user   = User::factory()->create(['company_id' => $five->company_id]);
        $report = WorkReport::create([
            'note_id'     => $five->note_id,
            'company_id'  => $five->company_id,
            'user_id'     => $user->id,
            'date'        => '2026-10-07',
            'informed_at' => '2026-10-07 10:00:00',
            'rejected'    => false,
            'canceled'    => false,
        ]);

        Livewire::test(D5Edit::class)
            ->call('getInfoResponse', $five)
            ->set("productionWorkReportIds.{$production->id}", $report->id)
            ->call('associateWorkReport', $production->id);

        $this->assertDatabaseHas('work_report_flow_productions', [
            'work_report_id' => $report->id,
            'production_id'  => $production->id,
            'stage'          => 'payment',
            'final_scope'    => 'general',
            'is_current'     => true,
        ]);
    }

    private function fiveNote(array $attributes = []): FiveNote
    {
        $company = Company::create(['name' => 'Empresa D5', 'email' => 'd5@example.test']);
        $note    = Note::create(['note' => '400000' . random_int(1000, 9999), 'dt_status' => '2026-10-01 08:00:00', 'nstats' => 'NEW']);

        return FiveNote::create(array_merge([
            'note_id'    => $note->id,
            'company_id' => $company->id,
            'note_d5'    => (string) random_int(100000, 999999),
        ], $attributes));
    }

    private function productionFor(int $noteId, string $companyId, array $attributes = []): Production
    {
        $service = Service::create([
            'uuid'    => (string) Str::uuid(),
            'service' => 'Medicao',
            'folder'  => 'medicao',
        ]);

        return Production::create(array_merge([
            'note_id'    => $noteId,
            'company_id' => $companyId,
            'service_id' => $service->uuid,
            'completed'  => false,
            'confirmed'  => false,
            'status'     => 2,
        ], $attributes));
    }
}
