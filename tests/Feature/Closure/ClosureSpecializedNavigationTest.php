<?php

namespace Tests\Feature\Closure;

use App\Http\Controllers\ClosureController;
use App\Http\Livewire\Closure\Dispatch\Index as DispatchIndex;
use App\Http\Livewire\Closure\Work\Index as WorkIndex;
use App\Models\{Company, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClosureSpecializedNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function closureUser(bool $operator = false, bool $manager = false): User
    {
        $company = Company::query()->create([
            'name'  => 'Parceira de teste',
            'email' => fake()->unique()->safeEmail(),
        ]);

        return User::factory()->create([
            'company_id'       => $company->id,
            'closure_operator' => $operator,
            'closure_manager'  => $manager,
            'admin'            => false,
            'superadm'         => false,
        ]);
    }

    private function assertRouteContract(string $name, string $action, string $gate): void
    {
        $route = app('router')->getRoutes()->getByName($name);

        $this->assertNotNull($route);
        $this->assertSame(ClosureController::class . '@' . $action, $route->getActionName());
        $this->assertContains('auth', $route->middleware());
        $this->assertContains('can:' . $gate, $route->middleware());
    }

    public function test_dispatch_route_uses_its_specialized_action_and_gate(): void
    {
        $user = $this->closureUser(manager: true);

        $this->assertRouteContract('closure.dispatch', 'dispatch', 'closure.dispatch');

        $this->actingAs($user);

        Livewire::test(DispatchIndex::class)
            ->assertOk()
            ->assertSee('Buscar Nota ou Ordem')
            ->assertSee('Ver prévia do despacho');
    }

    public function test_my_work_route_uses_its_specialized_action_and_gate(): void
    {
        $user = $this->closureUser(operator: true);

        $this->assertRouteContract('closure.work', 'myWork', 'closure.work');

        $this->actingAs($user);

        Livewire::test(WorkIndex::class)
            ->assertOk()
            ->assertSee('Ordens atribuídas a você')
            ->assertSee('Fila operacional');
    }

    public function test_specialized_routes_and_components_reject_a_user_without_their_gate(): void
    {
        $user = $this->closureUser();

        $this->actingAs($user)->get(route('closure.dispatch'))->assertForbidden();
        $this->actingAs($user)->get(route('closure.work'))->assertForbidden();

        Livewire::test(DispatchIndex::class)->assertStatus(403);
        Livewire::test(WorkIndex::class)->assertStatus(403);
    }
}
