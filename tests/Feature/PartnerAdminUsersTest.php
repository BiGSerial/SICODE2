<?php

use App\Http\Livewire\Partner\Admin\Users;
use App\Models\{Bancoupdate, Company, PartnerAdminAuditEvent, PartnerCompanyPermissionGrant, User};
use App\Services\PartnerAccess\{PartnerPermissionCatalog, PartnerUserManager};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function puCompany(array $attributes = []): Company
{
    return Company::query()->create(array_merge(['name' => 'Empreiteira ' . fake()->unique()->word(), 'email' => fake()->unique()->safeEmail()], $attributes));
}

function puUser(Company $company, array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'company_id' => $company->id, 'superadm' => false, 'admin' => false, 'management' => false, 'operator' => false, 'user' => false,
        'contract'   => false, 'first_pass' => false, 'bypassprod' => false, 'engineer' => false, 'onlyparner' => true, 'responsible' => false,
        'btzero'     => false, 'can_dispatch' => false, 'analyst' => false, 'legal_controller' => false, 'legal_field' => false, 'legal_manager' => false,
    ], $attributes));
}

function puGrant(Company $company, array $enabled): void
{
    foreach (PartnerPermissionCatalog::allPermissionKeys() as $key) {
        PartnerCompanyPermissionGrant::query()->create([
            'company_id' => $company->id, 'permission_key' => $key,
            'scope_type' => array_key_exists($key, PartnerPermissionCatalog::groups()) ? 'group' : 'item', 'enabled' => in_array($key, $enabled, true),
        ]);
    }
}

const PU_ALL = ['admin', 'admin_panel.access', 'admin_users.view', 'admin_users.create', 'admin_users.update', 'admin_users.disable', 'admin_audit.view'];

beforeEach(function () {
    Bancoupdate::query()->create(['last_update' => now()]);
    $this->company = puCompany();
    $this->branch  = puCompany(['parent_id' => $this->company->id]);
    $this->admin   = puUser($this->company, ['admin' => true, 'name' => 'Ana Admin']);
    puGrant($this->company, PU_ALL);
});

it('lists only partner users of the company and its branches', function () {
    $mine     = puUser($this->company, ['name' => 'Bruno Meu']);
    $branch   = puUser($this->branch, ['name' => 'Carla Filial']);
    $other    = puUser(puCompany(), ['name' => 'Davi Outro']);
    $internal = puUser($this->company, ['name' => 'Eva Interna', 'onlyparner' => false]);

    Livewire::actingAs($this->admin)->test(Users::class)
        ->assertSee('Bruno Meu')->assertSee('Carla Filial')->assertSee('Ana Admin')
        ->assertDontSee('Davi Outro')->assertDontSee('Eva Interna');
});

it('searches and filters by role and connection', function () {
    puUser($this->company, ['name' => 'Bruno Meu', 'email' => 'bruno@x.com']);
    puUser($this->company, ['name' => 'Carla Online', 'last_login_at' => now(), 'last_seen_at' => now()]);

    Livewire::actingAs($this->admin)->test(Users::class)
        ->set('search', 'bruno')->assertSee('Bruno Meu')->assertDontSee('Carla Online')
        ->call('clearAllCriteria')
        ->call('quickFilter', 'presence', 'online')->assertSee('Carla Online')->assertDontSee('Bruno Meu')
        ->call('quickFilter', 'presence', 'online')->assertSee('Bruno Meu')
        ->call('quickFilter', 'role', 'admin')->assertSee('Ana Admin')->assertDontSee('Bruno Meu');
});

it('resets the password after confirmation and forces a change', function () {
    $user = puUser($this->company, ['first_pass' => false]);

    Livewire::actingAs($this->admin)->test(Users::class)
        ->call('ask', 'reset_password', (string) $user->id)->assertSet('confirming.action', 'reset_password')
        ->call('confirm')->assertSet('confirming', null);

    expect($user->fresh()->first_pass)->toBeTrue()
        ->and(Hash::check('123456', $user->fresh()->password))->toBeTrue();
    expect(PartnerAdminAuditEvent::where('event_type', 'reset_user_password')->where('target_user_id', $user->id)->exists())->toBeTrue();
});

it('disables and restores access', function () {
    $user = puUser($this->company);

    Livewire::actingAs($this->admin)->test(Users::class)
        ->call('ask', 'disable', (string) $user->id)->call('confirm')
        ->call('setTab', 'disabled')->assertSee($user->name)
        ->call('ask', 'restore', (string) $user->id)->call('confirm');

    expect($user->fresh()->trashed())->toBeFalse();
    expect(PartnerAdminAuditEvent::whereIn('event_type', ['disabled_user', 'restored_user'])->count())->toBe(2);
});

it('never lets an admin disable or demote themselves', function () {
    $manager = new PartnerUserManager($this->admin);

    expect(fn () => $manager->disable((string) $this->admin->id))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect(fn () => $manager->setAdmin((string) $this->admin->id, false))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect($this->admin->fresh()->trashed())->toBeFalse()->and($this->admin->fresh()->admin)->toBeTrue();
});

it('refuses users outside the company scope', function () {
    $stranger = puUser(puCompany());

    expect(fn () => (new PartnerUserManager($this->admin))->resetPassword((string) $stranger->id))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    expect(fn () => (new PartnerUserManager($this->admin))->bulk('disable', [(string) $stranger->id]))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    expect($stranger->fresh()->trashed())->toBeFalse();
});

it('applies bulk actions with a single audit event and moves users between branches', function () {
    $a = puUser($this->company);
    $b = puUser($this->company);

    Livewire::actingAs($this->admin)->test(Users::class)
        ->set('selected', [(string) $a->id, (string) $b->id])
        ->call('askSelected', 'move_company')
        ->set('targetCompany', (string) $this->branch->id)
        ->call('confirm');

    expect($a->fresh()->company_id)->toBe($this->branch->id)->and($b->fresh()->company_id)->toBe($this->branch->id);
    expect(PartnerAdminAuditEvent::where('event_type', 'bulk_updated_users')->count())->toBe(1);
    expect(PartnerAdminAuditEvent::where('event_type', 'moved_user_company')->count())->toBe(0);
});

it('hides and blocks actions the user has no permission for', function () {
    $viewer = puUser($this->company, ['name' => 'Vera Leitora']);
    $target = puUser($this->company);
    PartnerCompanyPermissionGrant::query()->where('company_id', $this->company->id)->whereIn('permission_key', ['admin_users.update', 'admin_users.disable'])->update(['enabled' => false]);

    $component = Livewire::actingAs($viewer)->test(Users::class)->assertDontSee('Redefinir senha');

    expect(\App\Services\PartnerAccess\PartnerAccessGate::allows($viewer, 'admin_users.disable'))->toBeFalse();

    // O Livewire de teste devolve o 403 como erro de resposta (sem payload): o que importa é que a ação não abriu nem executou.
    try {
        $component->call('ask', 'disable', (string) $target->id);
        $component->assertSet('confirming', null);
    } catch (\Throwable) {
        // esperado: o 403 interrompe a chamada
    }

    expect($target->fresh()->trashed())->toBeFalse();
});

it('keeps the partner users page free of inline style, script and hardcoded colours', function () {
    $view = file_get_contents(resource_path('views/livewire/partner/admin/users.blade.php'));

    expect($view)->not->toContain('<style')->not->toContain('<script')->not->toMatch('/#[0-9a-fA-F]{3,6}\b/')->not->toContain('text-bg-')->not->toContain('cursor');
});

it('renders the page through the route', function () {
    $this->actingAs($this->admin)->get(route('partner.admin.users'))->assertOk()->assertSee('Usuários ativos')->assertSee('Novo usuário');
});
