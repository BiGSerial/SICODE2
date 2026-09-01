<?php

namespace Tests\Feature\Closure;

use App\Http\Livewire\Closure\Cycles\{Meta, Overview, Passive};
use App\Http\Livewire\Closure\Orders\Detail;
use App\Models\{Note, Order, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClosureGatesTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        $note = Note::create(['note' => (string) rand(1000000, 9999999), 'type_note' => 1]);

        return Order::create([
            'note_id'    => $note->id,
            'ordem'      => 'O' . rand(100000, 999999),
            'statusSist' => 'LIB CNPA',
            'canceled'   => false,
        ]);
    }

    public function test_user_without_any_closure_permission_gets_403_on_manager_routes(): void
    {
        $user  = User::factory()->create(['closure_operator' => false, 'closure_manager' => false, 'admin' => false, 'superadm' => false]);
        $order = $this->makeOrder();

        // A rota HTTP barra pelo middleware `can:closure.manager`/`can:closure.view` antes de
        // renderizar qualquer view, então este teste não depende da topbar/layout compartilhado.
        $this->actingAs($user)->get(route('closure.overview'))->assertForbidden();
        $this->actingAs($user)->get(route('closure.meta'))->assertForbidden();
        $this->actingAs($user)->get(route('closure.passive'))->assertForbidden();
        $this->actingAs($user)->get(route('closure.order.detail', $order->id))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('closure.overview'))->assertRedirect();
    }

    public function test_closure_operator_only_is_forbidden_on_manager_gate_but_allowed_on_view_gate(): void
    {
        $user  = User::factory()->create(['closure_operator' => true, 'closure_manager' => false, 'admin' => false, 'superadm' => false]);
        $order = $this->makeOrder();

        $this->assertFalse($user->can('closure.manager'));
        $this->assertTrue($user->can('closure.view'));

        // Componentes Livewire testados isoladamente (sem o layout/topbar completo), pra validar
        // só o gate no mount() de cada um — o mesmo abort_unless() que a rota HTTP dispara.
        $this->actingAs($user);

        Livewire::test(Overview::class)->assertStatus(403);
        Livewire::test(Meta::class)->assertStatus(403);
        Livewire::test(Passive::class)->assertStatus(403);
        Livewire::test(Detail::class, ['orderId' => $order->id])->assertOk();
    }

    public function test_closure_manager_is_allowed_on_all_module_gates(): void
    {
        $user  = User::factory()->create(['closure_operator' => false, 'closure_manager' => true, 'admin' => false, 'superadm' => false]);
        $order = $this->makeOrder();

        $this->assertTrue($user->can('closure.manager'));
        $this->assertTrue($user->can('closure.view'));

        $this->actingAs($user);

        Livewire::test(Overview::class)->assertOk();
        Livewire::test(Meta::class)->assertOk();
        Livewire::test(Passive::class)->assertOk();
        Livewire::test(Detail::class, ['orderId' => $order->id])->assertOk();
    }
}
