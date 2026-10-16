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
            ['service' => 'Encerramento', 'project' => true, 'construction' => true, 'icon' => 'ri-checkbox-circle-line'],
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

    public function test_service_link_shows_closure_under_construction_with_specialized_route(): void
    {
        $user = $this->user();
        ServiceUser::create(['user_id' => $user->id, 'service_id' => $this->service()->uuid, 'service' => true, 'dispatch' => true]);

        $html = $this->render($user);

        $this->assertStringContainsString('CONSTRUÇÃO', $html);
        $this->assertStringContainsString('ENCERRAMENTO', $html);
        $this->assertStringContainsString(route('closure.dispatch'), $html);
        $this->assertStringContainsString(route('closure.work'), $html);
        $this->assertStringNotContainsString('href="' . route('closure.overview') . '"', $html);
    }

    public function test_dispatch_only_link_hides_service_route(): void
    {
        $user = $this->user();
        ServiceUser::create(['user_id' => $user->id, 'service_id' => $this->service()->uuid, 'service' => false, 'dispatch' => true]);

        $html = $this->render($user);

        $this->assertStringContainsString(route('closure.dispatch'), $html);
        $this->assertStringNotContainsString(route('closure.work'), $html);
    }

    public function test_user_without_link_does_not_see_closure(): void
    {
        $this->service();

        $html = $this->render($this->user());

        $this->assertStringNotContainsString('ENCERRAMENTO', $html);
        $this->assertStringNotContainsString(route('closure.dispatch'), $html);
    }

    public function test_sidebar_links_follow_gates_and_self_assign_setting(): void
    {
        $this->actingAs($this->user());
        $html = view('closure.closure-menu')->render();

        foreach (['closure.dispatch', 'closure.dispatch.dashboard', 'closure.work', 'closure.overview', 'closure.meta', 'closure.passive'] as $name) {
            $this->assertStringContainsString(route($name), $html);
        }

        $this->actingAs($this->user(operator: true, manager: false));
        $html = view('closure.closure-menu')->render();
        $this->assertStringContainsString(route('closure.work'), $html);
        $this->assertStringNotContainsString('href="' . route('closure.overview') . '"', $html);
        $this->assertStringNotContainsString(route('closure.dispatch'), $html);
    }
}
