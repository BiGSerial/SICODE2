<?php

namespace Tests\Feature\Closure;

use App\Models\{Company, Service, ServiceUser, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ClosureMenuTest extends TestCase
{
    use RefreshDatabase;

    private function service(): Service
    {
        return Service::query()->firstOrCreate(
            ['folder' => 'encerramento'],
            ['service' => 'Encerramento', 'project' => false, 'construction' => false, 'post_construction' => true, 'icon' => 'ri-checkbox-circle-line'],
        );
    }

    private function user(bool $operator = true, bool $manager = true): User
    {
        $company = Company::query()->create(['name' => 'Parceira', 'email' => fake()->unique()->safeEmail()]);

        return User::factory()->create([
            'company_id'       => $company->id,
            'closure_operator' => $operator,
            'closure_manager'  => $manager,
            'admin'            => false,
            'superadm'         => false,
            'operator'         => true,
            'user'             => true,
        ]);
    }

    private function render(User $user): string
    {
        $this->actingAs($user);

        return Blade::render('<x-menu.activities-dropdown />');
    }

    public function test_service_link_shows_closure_under_post_construction_with_standard_routes(): void
    {
        $user    = $this->user();
        $service = $this->service();
        ServiceUser::create(['user_id' => $user->id, 'service_id' => $service->uuid, 'service' => true, 'dispatch' => true]);

        $html = $this->render($user);

        $this->assertStringContainsString('PÓS OBRA', $html);
        $this->assertStringNotContainsString('CONSTRUÇÃO', $html);
        $this->assertStringContainsString('ENCERRAMENTO', $html);
        $this->assertStringContainsString(route('dispatch.main', ['service' => $service->uuid]), $html);
        $this->assertStringContainsString(route('services.main', ['service' => $service->uuid]), $html);
        $this->assertStringNotContainsString('encerramento/despacho', $html);
        $this->assertStringNotContainsString('encerramento/minha-atividade', $html);
    }

    public function test_dispatch_only_link_hides_service_route(): void
    {
        $user    = $this->user();
        $service = $this->service();
        ServiceUser::create(['user_id' => $user->id, 'service_id' => $service->uuid, 'service' => false, 'dispatch' => true]);

        $html = $this->render($user);

        $this->assertStringContainsString(route('dispatch.main', ['service' => $service->uuid]), $html);
        $this->assertStringNotContainsString(route('services.main', ['service' => $service->uuid]), $html);
    }

    public function test_user_without_link_does_not_see_closure(): void
    {
        $service = $this->service();

        $html = $this->render($this->user());

        $this->assertStringNotContainsString('ENCERRAMENTO', $html);
        $this->assertStringNotContainsString(route('dispatch.main', ['service' => $service->uuid]), $html);
    }

    public function test_dispatch_sidebar_follows_the_standard_pattern_and_gates(): void
    {
        $service = $this->service();
        $this->actingAs($this->user());
        $html = view('dispatchs.encerramento.menu', ['service' => $service])->render();

        foreach (['dispatch.main', 'dispatch.stack', 'dispatch.transprod', 'dispatch.dashboard'] as $name) {
            $this->assertStringContainsString(route($name, ['service' => $service->uuid]), $html);
        }

        foreach (['closure.overview', 'closure.meta', 'closure.passive'] as $name) {
            $this->assertStringContainsString(route($name), $html);
        }

        $this->assertStringContainsString('LISTA PARA', $html);
        $this->assertStringContainsString('CONTROLE DE', $html);
        $this->assertStringContainsString('TRANSFERÊNCIA', $html);

        // Operador: sem itens de despacho nem de gestão.
        $this->actingAs($this->user(operator: true, manager: false));
        $html = view('dispatchs.encerramento.menu', ['service' => $service])->render();
        $this->assertStringNotContainsString(route('dispatch.main', ['service' => $service->uuid]), $html);
        $this->assertStringNotContainsString(route('closure.overview'), $html);
    }

    public function test_service_sidebar_respects_self_assign_setting(): void
    {
        $service = $this->service();
        $user    = $this->user(operator: true, manager: false);
        $this->actingAs($user);

        $html = view('services.encerramento.menu', ['service' => $service])->render();
        $this->assertStringContainsString(route('services.main', ['service' => $service->uuid]), $html);
        $this->assertStringContainsString(route('services.accompany', ['service' => $service->uuid]), $html);
        $this->assertStringContainsString(route('services.historic', ['service' => $service->uuid]), $html);
        $this->assertStringNotContainsString('aba=disponiveis', $html);

        app(\App\Services\Closure\ClosureSettings::class)->setSelfAssignEnabled(true, $user);
        $html = view('services.encerramento.menu', ['service' => $service])->render();
        $this->assertStringContainsString('aba=disponiveis', $html);
    }

    public function test_management_pages_sidebar_shim_picks_the_menu_by_gate(): void
    {
        $service = $this->service();

        $this->actingAs($this->user());
        $this->assertStringContainsString(route('dispatch.stack', ['service' => $service->uuid]), view('closure.closure-menu')->render());

        $this->actingAs($this->user(operator: true, manager: false));
        $html = view('closure.closure-menu')->render();
        $this->assertStringContainsString(route('services.accompany', ['service' => $service->uuid]), $html);
        $this->assertStringNotContainsString(route('dispatch.stack', ['service' => $service->uuid]), $html);
    }
}
