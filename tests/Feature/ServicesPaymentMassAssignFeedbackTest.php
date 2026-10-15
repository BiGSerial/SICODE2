<?php

namespace Tests\Feature;

use App\Http\Livewire\Services\Payment\Main;
use App\Models\{Company, Service, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServicesPaymentMassAssignFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_mass_assign_opens_the_dispatch_modal_in_self_assign_mode(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $user    = User::factory()->create(['superadm' => true, 'company_id' => $company->id]);
        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);

        Livewire::actingAs($user)->test(Main::class, ['service' => $service->uuid])
            ->set('selected', ['999999991'])
            ->call('go_att_mass')
            ->assertEmitted('openForSelfAssign');
    }

    public function test_mass_assign_without_selection_warns(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $user    = User::factory()->create(['superadm' => true, 'company_id' => $company->id]);
        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);

        Livewire::actingAs($user)->test(Main::class, ['service' => $service->uuid])
            ->call('go_att_mass')
            ->assertDispatchedBrowserEvent('swal');
    }
}
