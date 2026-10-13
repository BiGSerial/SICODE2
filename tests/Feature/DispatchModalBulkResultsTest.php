<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Shared\DispatchModal;
use App\Models\{Company, Contract, FiveNote, Note, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DispatchModalBulkResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_dispatch_dispatches_valid_items_and_reports_failures_in_modal(): void
    {
        [$service, $company, $actor] = $this->fixture();
        $ok                          = $this->noteWithPassiveD5('5000000001');
        $bad                         = $this->noteWithPassiveD5('5000000002');

        $component = $this->openModal($service, $actor, $company, [$ok, $bad]);

        // Invalida a segunda D5 depois da abertura (outro usuário alterou a demanda).
        $bad[1]->update(['is_archived' => true]);

        $component->call('confirmedAtt');

        $results = collect($component->get('dispatchResults'));
        $this->assertTrue($results->firstWhere('label', $ok[0]->note . ' · D5 #' . $ok[1]->id)['ok']);
        $failed = $results->firstWhere('ok', false);
        $this->assertStringContainsString($bad[0]->note, $failed['label']);
        $this->assertNotEmpty($failed['reason']);
        $this->assertSame(1, $component->get('notes')->count());
        $this->assertSame(1, \App\Models\Production::where('note_id', $ok[0]->id)->count());
        $this->assertSame(0, \App\Models\Production::where('note_id', $bad[0]->id)->count());
    }

    public function test_single_item_failure_keeps_previous_behavior_without_results_panel(): void
    {
        [$service, $company, $actor] = $this->fixture();
        $bad                         = $this->noteWithPassiveD5('5000000003');

        $component = $this->openModal($service, $actor, $company, [$bad]);
        $bad[1]->update(['is_archived' => true]);
        $component->call('confirmedAtt');

        $this->assertSame([], $component->get('dispatchResults'));
        $this->assertSame(1, $component->get('notes')->count());
        $component->assertDispatchedBrowserEvent('swal');
    }

    private function openModal(Service $service, User $actor, Company $company, array $pairs)
    {
        $component = Livewire::actingAs($actor)->test(DispatchModal::class, ['serviceId' => $service->uuid])
            ->call('openForNotes', collect($pairs)->map(fn ($p) => ['note_id' => $p[0]->id, 'five_note_id' => $p[1]->id])->all());

        return $component->set('type', '2')->set('company_s', (string) $company->id)->set('user_s', (string) $actor->id);
    }

    private function noteWithPassiveD5(string $number): array
    {
        $note = Note::create(['note' => $number, 'type_note' => 1, 'dt_status' => '2026-08-30 08:00:00', 'nstats' => 'NEW']);
        $five = FiveNote::create([
            'note_id'      => $note->id, 'note_d5' => 'P' . $number, 'isPassive' => true,
            'is_completed' => true, 'is_supervisioned' => true,
        ]);

        return [$note, $five];
    }

    private function fixture(): array
    {
        $company                = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $contract               = new Contract();
        $contract->company_id   = $company->id;
        $contract->number       = 'BULK-001';
        $contract->service      = true;
        $contract->construction = false;
        $contract->date_end     = now()->addYear()->toDateString();
        $contract->save();
        $actor = User::factory()->create(['contract' => true, 'company_id' => $company->id]);
        $actor->Employee()->create(['contract_id' => $contract->id, 'service_id' => null]);
        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);

        return [$service, $company, $actor];
    }
}
