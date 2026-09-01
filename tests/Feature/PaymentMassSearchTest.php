<?php

namespace Tests\Feature;

use App\Http\Livewire\Dispatchs\Payment\Main as DispatchPaymentMain;
use App\Http\Livewire\Dispatchs\Shared\DispatchModal;
use App\Http\Livewire\Services\Payment\Main as ServicePaymentMain;
use App\Jobs\Dispatchs\ExportDispatchPaymentJob;
use App\Models\Note;
use App\Models\Partial;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentMassSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_payment_mass_search_respects_payment_criteria_by_default(): void
    {
        $this->actingAs(User::factory()->create(['contract' => false]));

        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
        $eligible = $this->paymentEligibleNote('4000000101');
        $outsideFlow = Note::create(['note' => '4000000102', 'dt_status' => now(), 'nstats' => 'NEW']);

        $component = Livewire::test(DispatchPaymentMain::class, ['service' => $service->uuid])
            ->set('advanceSearch', "{$eligible->note}\n{$outsideFlow->note}")
            ->call('buscarMulti');

        $noteIds = $component->instance()->getListsProperty()->pluck('id')->all();

        $this->assertContains($eligible->id, $noteIds);
        $this->assertNotContains($outsideFlow->id, $noteIds);
    }

    public function test_service_payment_mass_search_respects_payment_criteria_by_default(): void
    {
        $this->actingAs(User::factory()->create(['contract' => false]));

        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
        $eligible = $this->paymentEligibleNote('4000000103');
        $outsideFlow = Note::create(['note' => '4000000104', 'dt_status' => now(), 'nstats' => 'NEW']);

        $component = Livewire::test(ServicePaymentMain::class, ['service' => $service->uuid])
            ->set('advanceSearch', "{$eligible->note}\n{$outsideFlow->note}")
            ->call('buscarMulti');

        $noteIds = $component->instance()->getListsProperty()->pluck('id')->all();

        $this->assertContains($eligible->id, $noteIds);
        $this->assertNotContains($outsideFlow->id, $noteIds);
    }

    public function test_service_payment_mass_search_can_use_any_situation_risk_mode(): void
    {
        $this->actingAs(User::factory()->create(['contract' => false]));

        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
        $eligible = $this->paymentEligibleNote('4000000105');
        $outsideFlow = Note::create(['note' => '4000000106', 'dt_status' => now(), 'nstats' => 'NEW']);

        $component = Livewire::test(ServicePaymentMain::class, ['service' => $service->uuid])
            ->set('bulkSearchAnyStatus', true)
            ->set('advanceSearch', "{$eligible->note}\n{$outsideFlow->note}")
            ->call('buscarMulti');

        $noteIds = $component->instance()->getListsProperty()->pluck('id')->all();

        $this->assertContains($eligible->id, $noteIds);
        $this->assertContains($outsideFlow->id, $noteIds);
    }

    public function test_dispatch_payment_export_is_queued_with_current_filters(): void
    {
        Bus::fake();
        $this->actingAs(User::factory()->create(['contract' => false]));

        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);

        Livewire::test(DispatchPaymentMain::class, ['service' => $service->uuid])
            ->set('search', '4000000107')
            ->set('typeNote', '1')
            ->set('not_assigned', true)
            ->set('filter_d5', true)
            ->call('export_excel');

        Bus::assertDispatched(ExportDispatchPaymentJob::class, function (ExportDispatchPaymentJob $job) use ($service) {
            return $job->params['source'] === 'dispatch'
                && $job->params['service_uuid'] === $service->uuid
                && $job->params['search'] === '4000000107'
                && $job->params['typeNote'] === '1'
                && $job->params['not_assigned'] === true
                && $job->params['filter_d5'] === true;
        });
    }

    public function test_service_payment_export_is_queued_with_current_filters(): void
    {
        Bus::fake();
        $this->actingAs(User::factory()->create(['contract' => false]));

        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);

        Livewire::test(ServicePaymentMain::class, ['service' => $service->uuid])
            ->set('bulkSearchAnyStatus', true)
            ->set('advanceSearch', '4000000108')
            ->call('buscarMulti')
            ->set('typeNote', '2')
            ->call('export_excel');

        Bus::assertDispatched(ExportDispatchPaymentJob::class, function (ExportDispatchPaymentJob $job) use ($service) {
            return $job->params['source'] === 'service'
                && $job->params['service_uuid'] === $service->uuid
                && $job->params['multiSearch'] === ['4000000108']
                && $job->params['bulkSearchAnyStatus'] === true
                && $job->params['typeNote'] === '2';
        });
    }

    public function test_shared_dispatch_modal_confirmation_targets_modal_component(): void
    {
        $this->actingAs(User::factory()->create(['contract' => false]));

        $company = \App\Models\Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $targetUser = User::factory()->create(['contract' => false, 'company_id' => $company->id]);
        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
        $note = $this->paymentEligibleNote('4000000109');

        Livewire::test(DispatchModal::class, ['serviceId' => $service->uuid])
            ->call('openForNotes', [$note->id])
            ->set('company_s', (string) $company->id)
            ->set('type', '2')
            ->set('user_s', (string) $targetUser->id)
            ->call('confirmAtt')
            ->assertDispatchedBrowserEvent('alertar', function (string $event, array $data) {
                return $event === 'alertar'
                    && ($data['target'] ?? null) === 'dispatchs.shared.dispatch-modal'
                    && ($data['action'] ?? null) === 'confirm_dispatch_modal';
            });
    }

    private function paymentEligibleNote(string $number): Note
    {
        $note = Note::create([
            'note' => $number,
            'dt_status' => now(),
            'nstats' => 'NEW',
        ]);

        Partial::create([
            'note_id' => $note->id,
            'allow' => true,
            'deny' => false,
            'payment' => false,
            'supervision' => true,
            'supervision_at' => now(),
            'value' => 100,
        ]);

        return $note;
    }
}
