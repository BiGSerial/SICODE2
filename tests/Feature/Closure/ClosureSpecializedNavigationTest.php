<?php

namespace Tests\Feature\Closure;

use App\Http\Livewire\Closure\Dispatch\{Control, Dashboard, Index as DispatchIndex, Transfer};
use App\Http\Livewire\Closure\Work\{Available, History, Index as WorkIndex};
use App\Models\{Company, ServiceUser, User};
use App\Services\Closure\{ClosureServiceAccess, ClosureServiceLocator};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * O Encerramento usa as telas padrão do Service (dispatch.* / services.*); as rotas closure.*
 * antigas são só redirects. Cada view da pasta `encerramento` embute o componente certo.
 */
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

    private function uuid(): string
    {
        return ClosureServiceLocator::uuid();
    }

    public function test_legacy_routes_redirect_to_the_standard_service_routes(): void
    {
        $user = $this->closureUser(operator: true);
        $uuid = $this->uuid();

        $this->actingAs($user);

        $this->get(route('closure.dispatch'))->assertRedirect(route('dispatch.main', ['service' => $uuid]));
        $this->get(route('closure.dispatch.dashboard'))->assertRedirect(route('dispatch.dashboard', ['service' => $uuid]));
        $this->get(route('closure.work'))->assertRedirect(route('services.accompany', ['service' => $uuid]));
        $this->get(route('closure.work.available'))->assertRedirect(route('services.main', ['service' => $uuid, 'aba' => 'disponiveis']));
    }

    public function test_standard_routes_use_the_service_controllers_and_middleware(): void
    {
        foreach (['dispatch.main', 'dispatch.stack', 'dispatch.transprod', 'dispatch.dashboard'] as $name) {
            $this->assertContains('check.service.dispatch:services', app('router')->getRoutes()->getByName($name)->middleware());
        }

        foreach (['services.main', 'services.accompany', 'services.historic'] as $name) {
            $this->assertContains('check.service.dispatch:services', app('router')->getRoutes()->getByName($name)->middleware());
        }
    }

    public function test_folder_views_embed_the_right_component(): void
    {
        $service = ClosureServiceLocator::service();
        $manager = $this->closureUser(manager: true);
        $this->actingAs($manager);

        $expected = [
            'dispatchs.encerramento.main'      => 'closure.dispatch.index',
            'dispatchs.encerramento.stack'     => 'closure.dispatch.control',
            'dispatchs.encerramento.transprod' => 'closure.dispatch.transfer',
            'dispatchs.encerramento.dashboard' => 'closure.dispatch.dashboard',
            'services.encerramento.main'       => 'closure.work.index',
            'services.encerramento.accompany'  => 'closure.work.index',
            'services.encerramento.historic'   => 'closure.work.history',
        ];

        foreach ($expected as $view => $component) {
            $source = file_get_contents(view($view)->getPath());
            $this->assertStringContainsString("@livewire('{$component}'", $source, $view);
        }

        $this->assertStringContainsString("@livewire('closure.work.available'", file_get_contents(view('services.encerramento.main')->getPath()));
        $this->assertStringContainsString("@livewire('partner.closure-issues.index'", file_get_contents(view('dispatchs.encerramento.partner_main')->getPath()));
        $this->assertSame($service->folder, 'encerramento');
    }

    public function test_dispatch_folder_pages_render_for_the_dispatcher(): void
    {
        $this->actingAs($this->closureUser(manager: true));

        Livewire::test(DispatchIndex::class)->assertOk()->assertSee('Despacho de Encerramento')->assertSee('Nota ou Ordem')->assertDontSee('Ver prévia do despacho');
        Livewire::test(Control::class)->assertOk()->assertSee('Controle de Encerramento')->assertSee('Empresa (posse atual)');
        Livewire::test(Transfer::class)->assertOk()->assertSee('Transferência de Encerramento')->assertSee('Pedidos de prioridade');
        Livewire::test(Dashboard::class)->assertOk()->assertSee('Painel analítico do Encerramento');
    }

    public function test_service_folder_pages_render_for_the_operator(): void
    {
        $this->actingAs($this->closureUser(operator: true));

        Livewire::test(WorkIndex::class)->assertOk()->assertSee('Ordens atribuídas a você')->assertSee('Fila operacional');
        Livewire::test(History::class)->assertOk()->assertSee('Meu histórico');
        Livewire::test(Available::class)->assertOk();
    }

    public function test_components_reject_users_without_their_gate(): void
    {
        $this->actingAs($this->closureUser());

        foreach ([DispatchIndex::class, Control::class, Transfer::class, Dashboard::class, WorkIndex::class, History::class, Available::class] as $component) {
            Livewire::test($component)->assertStatus(403);
        }
    }

    public function test_control_is_not_available_to_the_operator_gate_only(): void
    {
        $this->actingAs($this->closureUser(operator: true));

        Livewire::test(Control::class)->assertStatus(403);
        Livewire::test(Transfer::class)->assertStatus(403);
    }

    public function test_service_users_link_grants_domain_gates_without_closure_flags(): void
    {
        $dispatcher = $this->closureUser();
        $worker     = $this->closureUser();
        $none       = $this->closureUser();
        ServiceUser::create(['user_id' => $dispatcher->id, 'service_id' => $this->uuid(), 'service' => false, 'dispatch' => true]);
        ServiceUser::create(['user_id' => $worker->id, 'service_id' => $this->uuid(), 'service' => true, 'dispatch' => false]);

        $this->assertTrue($dispatcher->can('closure.dispatch'));
        $this->assertTrue($dispatcher->can('closure.manage'));
        $this->assertTrue($dispatcher->can('closure.view'));
        $this->assertFalse($dispatcher->can('closure.work'));
        $this->assertFalse($dispatcher->can('closure.manager'));

        $this->assertTrue($worker->can('closure.work'));
        $this->assertTrue($worker->can('closure.view'));
        $this->assertFalse($worker->can('closure.dispatch'));
        $this->assertFalse($worker->can('closure.manage'));

        foreach (['closure.dispatch', 'closure.work', 'closure.manage', 'closure.view', 'closure.manager'] as $ability) {
            $this->assertFalse($none->can($ability), $ability);
        }

        $this->assertTrue(ClosureServiceAccess::has($dispatcher, 'dispatch'));
        $this->assertFalse(ClosureServiceAccess::has($dispatcher, 'service'));
    }

    public function test_link_to_another_service_does_not_grant_closure_gates(): void
    {
        $user  = $this->closureUser();
        $other = \App\Models\Service::query()->create(['service' => 'Outro', 'folder' => 'pagamento', 'project' => false, 'construction' => false, 'icon' => 'ri-x']);
        ServiceUser::create(['user_id' => $user->id, 'service_id' => $other->uuid, 'service' => true, 'dispatch' => true]);

        $this->assertFalse($user->can('closure.dispatch'));
        $this->assertFalse($user->can('closure.work'));
    }

    public function test_service_page_http_access_follows_service_users_and_closure_flags(): void
    {
        $uuid = $this->uuid();

        // Sem vínculo e sem flag: 403 no middleware padrão.
        $this->actingAs($this->closureUser())->get(route('dispatch.main', ['service' => $uuid]))->assertForbidden();
        $this->actingAs($this->closureUser())->get(route('services.main', ['service' => $uuid]))->assertForbidden();

        // Operador sem despacho: entra em services.*, mas não em dispatch.*.
        $operator = $this->closureUser(operator: true);
        $this->actingAs($operator)->get(route('dispatch.main', ['service' => $uuid]))->assertForbidden();
    }

    public function test_full_pages_render_for_a_superadmin(): void
    {
        $uuid = $this->uuid();
        $user = User::factory()->create(['superadm' => true, 'admin' => false, 'closure_manager' => false, 'closure_operator' => false, 'company_id' => null, 'contract' => false]);
        $this->actingAs($user);
        // O layout legado lê o último registro de atualização do banco no rodapé.
        \App\Models\Bancoupdate::query()->forceCreate(['inserts' => 0, 'updates' => 0]);

        foreach ([
            route('dispatch.main', ['service' => $uuid])                         => 'Despacho de Encerramento',
            route('dispatch.stack', ['service' => $uuid])                        => 'Controle de Encerramento',
            route('dispatch.transprod', ['service' => $uuid])                    => 'Transferência de Encerramento',
            route('dispatch.dashboard', ['service' => $uuid])                    => 'Painel analítico',
            route('services.main', ['service' => $uuid])                         => 'Minha Atividade',
            route('services.main', ['service' => $uuid, 'aba' => 'disponiveis']) => 'Atividade Encerramento',
            route('services.accompany', ['service' => $uuid])                    => 'Minha Atividade',
            route('services.historic', ['service' => $uuid])                     => 'Meu histórico',
        ] as $url => $text) {
            $this->get($url)->assertOk()->assertSee($text, false);
        }
    }
}
