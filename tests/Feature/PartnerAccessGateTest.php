<?php

use App\Models\Company;
use App\Models\Andresscompany;
use App\Models\Bancoupdate;
use App\Models\Note;
use App\Models\Order;
use App\Models\PartnerCompanyPermissionGrant;
use App\Models\PartnerRole;
use App\Models\PartnerUserBranch;
use App\Models\PartnerUserPermissionException;
use App\Models\User;
use App\Models\Viability;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Livewire\Partner\Actions\ViabResponse;
use App\Http\Livewire\Partner\Todoviability;
use App\Services\PartnerAccess\PartnerAccessGate;
use App\Services\PartnerAccess\PartnerBranchScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function partnerAccessCompany(array $attributes = []): Company
{
    return Company::query()->create(array_merge([
        'name' => 'Parceira Teste',
        'email' => fake()->unique()->safeEmail(),
    ], $attributes));
}

function partnerAccessUser(Company $company, array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'company_id' => $company->id,
        'superadm' => false,
        'admin' => false,
        'management' => false,
        'operator' => false,
        'user' => false,
        'contract' => false,
        'first_pass' => false,
        'bypassprod' => false,
        'engineer' => false,
        'onlyparner' => true,
        'responsible' => false,
        'btzero' => false,
        'can_dispatch' => false,
        'analyst' => false,
        'legal_controller' => false,
        'legal_field' => false,
        'legal_manager' => false,
    ], $attributes));
}

function partnerAccessViability(Company $company, User $user, string $noteNumber, string $branch): Viability
{
    $note = Note::query()->create([
        'note' => $noteNumber,
        'lexp' => $branch,
        'nexp' => $branch,
    ]);

    $order = Order::query()->create([
        'note_id' => $note->id,
        'ordem' => 'ORDEM-'.$noteNumber,
    ]);

    return Viability::query()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'order_id' => $order->id,
        'note_id' => $note->id,
        'visible_partner' => true,
    ]);
}

function partnerAccessRoleWithPermissions(Company $company, array $enabledPermissions, array $disabledPermissions = []): PartnerRole
{
    $role = PartnerRole::query()->create(['company_id' => $company->id, 'name' => 'Restrita']);

    foreach ($enabledPermissions as $permissionKey) {
        $role->permissions()->create([
            'permission_key' => $permissionKey,
            'scope_type' => array_key_exists($permissionKey, \App\Services\PartnerAccess\PartnerPermissionCatalog::groups()) ? 'group' : 'item',
            'enabled' => true,
        ]);
    }

    foreach ($disabledPermissions as $permissionKey) {
        $role->permissions()->create([
            'permission_key' => $permissionKey,
            'scope_type' => array_key_exists($permissionKey, \App\Services\PartnerAccess\PartnerPermissionCatalog::groups()) ? 'group' : 'item',
            'enabled' => false,
        ]);
    }

    return $role;
}

function partnerAccessGrantWithPermissions(Company $company, array $enabledPermissions, array $disabledPermissions = []): void
{
    $enabledPermissions = collect($enabledPermissions);
    $disabledPermissions = collect($disabledPermissions);

    foreach (\App\Services\PartnerAccess\PartnerPermissionCatalog::allPermissionKeys() as $permissionKey) {
        PartnerCompanyPermissionGrant::query()->create([
            'company_id' => $company->id,
            'permission_key' => $permissionKey,
            'scope_type' => array_key_exists($permissionKey, \App\Services\PartnerAccess\PartnerPermissionCatalog::groups()) ? 'group' : 'item',
            'enabled' => $enabledPermissions->contains($permissionKey) && !$disabledPermissions->contains($permissionKey),
        ]);
    }
}

it('keeps full access for a partner company without configured role permissions', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);

    expect(PartnerAccessGate::allows($user, 'viability.list'))->toBeTrue();
});

it('blocks all items when the configured group is disabled', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);
    $role = PartnerRole::query()->create(['company_id' => $company->id, 'name' => 'Restrita']);

    $role->permissions()->create([
        'permission_key' => 'viability',
        'scope_type' => 'group',
        'enabled' => false,
    ]);
    $role->permissions()->create([
        'permission_key' => 'viability.list',
        'scope_type' => 'item',
        'enabled' => true,
    ]);

    expect(PartnerAccessGate::allows($user, 'viability.list'))->toBeFalse();
});

it('allows all group items when the group is enabled and no item override exists', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);
    $role = PartnerRole::query()->create(['company_id' => $company->id, 'name' => 'Restrita']);

    $role->permissions()->create([
        'permission_key' => 'viability',
        'scope_type' => 'group',
        'enabled' => true,
    ]);

    expect(PartnerAccessGate::allows($user, 'viability.list'))->toBeTrue();
});

it('blocks a disabled item inside an enabled group', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);
    $role = PartnerRole::query()->create(['company_id' => $company->id, 'name' => 'Restrita']);

    $role->permissions()->create([
        'permission_key' => 'viability',
        'scope_type' => 'group',
        'enabled' => true,
    ]);
    $role->permissions()->create([
        'permission_key' => 'viability.export',
        'scope_type' => 'item',
        'enabled' => false,
    ]);

    expect(PartnerAccessGate::allows($user, 'viability.export'))->toBeFalse();
});

it('uses user exceptions before company role permissions', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);
    $role = PartnerRole::query()->create(['company_id' => $company->id, 'name' => 'Restrita']);

    $role->permissions()->create([
        'permission_key' => 'viability',
        'scope_type' => 'group',
        'enabled' => true,
    ]);
    $role->permissions()->create([
        'permission_key' => 'viability.export',
        'scope_type' => 'item',
        'enabled' => false,
    ]);

    PartnerUserPermissionException::query()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'permission_key' => 'viability.export',
        'enabled' => true,
    ]);

    expect(PartnerAccessGate::allows($user, 'viability.export'))->toBeTrue();
});

it('uses user exceptions to block an otherwise allowed permission', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);

    partnerAccessRoleWithPermissions($company, ['viability', 'viability.export']);

    PartnerUserPermissionException::query()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'permission_key' => 'viability.export',
        'enabled' => false,
    ]);

    expect(PartnerAccessGate::allows($user, 'viability.export'))->toBeFalse();
});

it('resolves branch permissions from the parent company configuration', function () {
    $parent = partnerAccessCompany();
    $branch = partnerAccessCompany(['parent_id' => $parent->id]);
    $user = partnerAccessUser($branch);

    partnerAccessRoleWithPermissions($parent, ['viability'], ['viability.export']);

    expect(PartnerAccessGate::allows($user, 'viability.list'))->toBeTrue()
        ->and(PartnerAccessGate::allows($user, 'viability.export'))->toBeFalse();
});

it('ignores branch permission configuration because the parent company is the authority', function () {
    $parent = partnerAccessCompany();
    $branch = partnerAccessCompany(['parent_id' => $parent->id]);
    $user = partnerAccessUser($branch);

    partnerAccessRoleWithPermissions($parent, ['viability'], ['viability.export']);
    partnerAccessRoleWithPermissions($branch, ['viability', 'viability.export']);

    expect(PartnerAccessGate::allows($user, 'viability.export'))->toBeFalse();
});

it('uses parent company user exceptions for branch users', function () {
    $parent = partnerAccessCompany();
    $branch = partnerAccessCompany(['parent_id' => $parent->id]);
    $user = partnerAccessUser($branch);

    partnerAccessRoleWithPermissions($parent, ['viability', 'viability.export']);

    PartnerUserPermissionException::query()->create([
        'company_id' => $parent->id,
        'user_id' => $user->id,
        'permission_key' => 'viability.export',
        'enabled' => false,
    ]);

    expect(PartnerAccessGate::allows($user, 'viability.export'))->toBeFalse();
});

it('does not allow company admin permissions beyond the contractor grant', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);

    partnerAccessGrantWithPermissions($company, ['viability', 'viability.list'], ['viability.export']);
    partnerAccessRoleWithPermissions($company, ['viability', 'viability.export']);

    expect(PartnerAccessGate::allows($user, 'viability.list'))->toBeTrue()
        ->and(PartnerAccessGate::allows($user, 'viability.export'))->toBeFalse();
});

it('requires a company admin for administrative partner permissions', function () {
    $company = partnerAccessCompany();

    expect(PartnerAccessGate::allows(partnerAccessUser($company), 'admin_panel.access'))->toBeFalse()
        ->and(PartnerAccessGate::allows(partnerAccessUser($company, ['admin' => true]), 'admin_panel.access'))->toBeTrue();
});

it('allows a company admin to see the admin panel even when the company role does not include admin permissions', function () {
    $company = partnerAccessCompany();
    $admin = partnerAccessUser($company, ['admin' => true]);

    partnerAccessGrantWithPermissions($company, ['admin', 'admin_panel.access', 'admin_users.view']);
    partnerAccessRoleWithPermissions($company, ['conclusion_reports', 'conclusion_reports.list']);

    expect(PartnerAccessGate::allows($admin, 'admin_panel.access'))->toBeTrue()
        ->and(PartnerAccessGate::allows($admin, 'admin_users.view'))->toBeTrue();
});

it('does not allow company admin permissions beyond contractor grants', function () {
    $company = partnerAccessCompany();
    $admin = partnerAccessUser($company, ['admin' => true]);

    partnerAccessGrantWithPermissions($company, ['conclusion_reports', 'conclusion_reports.list']);

    expect(PartnerAccessGate::allows($admin, 'admin_panel.access'))->toBeFalse()
        ->and(PartnerAccessGate::allows($admin, 'admin_users.view'))->toBeFalse();
});

it('returns forbidden for direct partner urls when the route permission is blocked', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);
    $role = PartnerRole::query()->create(['company_id' => $company->id, 'name' => 'Restrita']);

    $role->permissions()->create([
        'permission_key' => 'viability',
        'scope_type' => 'group',
        'enabled' => false,
    ]);

    $this->actingAs($user)
        ->get(route('partner.todo.viability'))
        ->assertForbidden();
});

it('keeps the partner portal entry available when viability is disabled but another module is granted', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);

    partnerAccessGrantWithPermissions($company, ['conclusion_reports', 'conclusion_reports.list']);

    $this->actingAs($user)
        ->get(route('partner.main.viability'))
        ->assertRedirect(route('partner.report.workedlist'));
});

it('keeps the partner portal entry available when viability is disabled but another role module is enabled', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);

    partnerAccessRoleWithPermissions($company, ['conclusion_reports', 'conclusion_reports.list'], ['viability']);

    $this->actingAs($user)
        ->get(route('partner.main.viability'))
        ->assertRedirect(route('partner.report.workreport'));
});

it('does not open the partner portal for non admin users with only admin permissions', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);

    partnerAccessGrantWithPermissions($company, ['admin', 'admin_panel.access', 'admin_users.view']);

    $this->actingAs($user)
        ->get(route('partner.main.viability'))
        ->assertForbidden();
});

it('shows editable user permissions in the partner user edit form without the admin group', function () {
    $company = partnerAccessCompany();
    $admin = partnerAccessUser($company, ['admin' => true]);
    $target = partnerAccessUser($company);
    Bancoupdate::query()->create(['last_update' => now()]);

    partnerAccessGrantWithPermissions($company, [
        'admin',
        'admin_panel.access',
        'admin_users.update',
        'admin_user_exceptions.manage',
        'viability',
        'viability.list',
    ]);

    $this->actingAs($admin)
        ->get(route('partner.admin.users.edit', $target))
        ->assertOk()
        ->assertSee('Permissões individuais')
        ->assertSee('Viabilidade')
        ->assertSee('Listar pendentes')
        ->assertDontSee('user-permission-admin');
});

it('does not show the partner permissions administration menu item', function () {
    $company = partnerAccessCompany();
    $admin = partnerAccessUser($company, ['admin' => true]);
    Bancoupdate::query()->create(['last_update' => now()]);

    partnerAccessGrantWithPermissions($company, [
        'admin',
        'admin_panel.access',
        'admin_users.view',
        'admin_audit.view',
    ]);

    $this->actingAs($admin)
        ->get(route('partner.admin.users'))
        ->assertOk()
        ->assertSee('Usuários')
        ->assertSee('Auditoria')
        ->assertDontSee('Permissões');
});

it('shows all branch addresses from the parent company in the partner user edit form', function () {
    $parent = partnerAccessCompany(['name' => 'Matriz Parceira']);
    $branch = partnerAccessCompany(['name' => 'Filial Norte', 'parent_id' => $parent->id]);
    $sibling = partnerAccessCompany(['name' => 'Filial Sul', 'parent_id' => $parent->id]);
    $admin = partnerAccessUser($branch, ['admin' => true]);
    $target = partnerAccessUser($branch);
    Bancoupdate::query()->create(['last_update' => now()]);

    Andresscompany::query()->create(['company_id' => $parent->id, 'city' => 'Vitoria', 'street' => 'Base Matriz']);
    Andresscompany::query()->create(['company_id' => $branch->id, 'city' => 'Linhares', 'street' => 'Base Norte']);
    Andresscompany::query()->create(['company_id' => $sibling->id, 'city' => 'Cachoeiro', 'street' => 'Base Sul']);

    partnerAccessGrantWithPermissions($parent, [
        'admin',
        'admin_panel.access',
        'admin_users.update',
    ]);

    $this->actingAs($admin)
        ->get(route('partner.admin.users.edit', $target))
        ->assertOk()
        ->assertSee('Matriz Parceira - Vitoria - Base Matriz')
        ->assertSee('Matriz Parceira / Filial Norte - Linhares - Base Norte')
        ->assertSee('Matriz Parceira / Filial Sul - Cachoeiro - Base Sul');
});

it('hides contractor blocked permissions from a branch admin user edit form', function () {
    $parent = partnerAccessCompany();
    $branch = partnerAccessCompany(['parent_id' => $parent->id]);
    $admin = partnerAccessUser($branch, ['admin' => true]);
    $target = partnerAccessUser($branch);
    Bancoupdate::query()->create(['last_update' => now()]);

    partnerAccessGrantWithPermissions($parent, [
        'admin',
        'admin_panel.access',
        'admin_users.update',
        'admin_user_exceptions.manage',
        'conclusion_reports',
        'conclusion_reports.list',
    ]);
    partnerAccessRoleWithPermissions($parent, [
        'viability',
        'viability.list',
        'conclusion_reports',
        'conclusion_reports.list',
    ]);

    $this->actingAs($admin)
        ->get(route('partner.admin.users.edit', $target))
        ->assertOk()
        ->assertSee('Permissões individuais')
        ->assertSee('Informes Conclusão')
        ->assertSee('Obras concluídas informadas')
        ->assertDontSee('user-permission-viability')
        ->assertDontSee('Listar pendentes');
});

it('allows a branch admin to assign sibling branch data visibility to a user', function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $parent = partnerAccessCompany();
    $branch = partnerAccessCompany(['parent_id' => $parent->id]);
    $sibling = partnerAccessCompany(['parent_id' => $parent->id]);
    $admin = partnerAccessUser($branch, ['admin' => true]);
    $target = partnerAccessUser($branch);

    $siblingAddress = Andresscompany::query()->create([
        'company_id' => $sibling->id,
        'city' => 'Cachoeiro',
        'street' => 'Base Sul',
    ]);

    partnerAccessGrantWithPermissions($parent, [
        'admin',
        'admin_panel.access',
        'admin_users.update',
        'viability',
        'viability.list',
    ]);

    $this->actingAs($admin)
        ->put(route('partner.admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'branches' => [$siblingAddress->id],
        ])
        ->assertRedirect(route('partner.admin.users.edit', $target));

    $visible = partnerAccessViability($branch, $target, '5001', 'Cachoeiro');
    $hidden = partnerAccessViability($branch, $target, '5002', 'Linhares');
    $query = Viability::query()->where('company_id', $branch->id);
    app(PartnerBranchScope::class)->applyToNoteRelation($query, $target, $branch->id);

    expect(PartnerUserBranch::query()
        ->where('company_id', $parent->id)
        ->where('user_id', $target->id)
        ->where('branch_id', $siblingAddress->id)
        ->exists())->toBeTrue()
        ->and($query->pluck('id')->all())->toBe([$visible->id])
        ->and($query->pluck('id')->all())->not->toContain($hidden->id);
});

it('updates individual user permissions from the partner user edit form', function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $company = partnerAccessCompany();
    $admin = partnerAccessUser($company, ['admin' => true]);
    $target = partnerAccessUser($company);

    partnerAccessGrantWithPermissions($company, [
        'admin',
        'admin_panel.access',
        'admin_users.update',
        'admin_user_exceptions.manage',
        'conclusion_reports',
        'conclusion_reports.list',
    ]);

    $this->actingAs($admin)
        ->put(route('partner.admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'branches' => [],
            'user_permissions' => [
                'conclusion_reports' => '1',
                'conclusion_reports.list' => '0',
            ],
        ])
        ->assertRedirect(route('partner.admin.users.edit', $target));

    expect(PartnerUserPermissionException::query()
        ->where('company_id', $company->id)
        ->where('user_id', $target->id)
        ->where('permission_key', 'conclusion_reports')
        ->value('enabled'))->toBeTrue()
        ->and(PartnerUserPermissionException::query()
            ->where('company_id', $company->id)
            ->where('user_id', $target->id)
            ->where('permission_key', 'conclusion_reports.list')
            ->value('enabled'))->toBeFalse()
        ->and(PartnerUserPermissionException::query()
            ->where('company_id', $company->id)
            ->where('user_id', $target->id)
            ->where('permission_key', 'admin_panel.access')
            ->exists())->toBeFalse();
});

it('stores branch user individual permission changes on the parent company', function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $parent = partnerAccessCompany();
    $branch = partnerAccessCompany(['parent_id' => $parent->id]);
    $admin = partnerAccessUser($branch, ['admin' => true]);
    $target = partnerAccessUser($branch);

    partnerAccessGrantWithPermissions($parent, [
        'admin',
        'admin_panel.access',
        'admin_users.update',
        'admin_user_exceptions.manage',
        'conclusion_reports',
        'conclusion_reports.list',
    ]);

    $this->actingAs($admin)
        ->put(route('partner.admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'branches' => [],
            'user_permissions' => [
                'conclusion_reports' => '1',
                'conclusion_reports.list' => '0',
            ],
        ])
        ->assertRedirect(route('partner.admin.users.edit', $target));

    expect(PartnerUserPermissionException::query()
        ->where('company_id', $parent->id)
        ->where('user_id', $target->id)
        ->where('permission_key', 'conclusion_reports.list')
        ->value('enabled'))->toBeFalse()
        ->and(PartnerUserPermissionException::query()
            ->where('company_id', $branch->id)
            ->where('user_id', $target->id)
            ->exists())->toBeFalse();
});

it('blocks partner admin routes for non admin company users', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);

    $this->actingAs($user)
        ->get(route('partner.admin.users'))
        ->assertForbidden();
});

it('does not list users from another company in partner admin', function () {
    $company = partnerAccessCompany();
    $otherCompany = partnerAccessCompany();
    $admin = partnerAccessUser($company, ['admin' => true]);
    $visible = partnerAccessUser($company, ['name' => 'Usuário Visível']);
    $hidden = partnerAccessUser($otherCompany, ['name' => 'Usuário Oculto']);
    $internal = User::factory()->create([
        'name' => 'Usuário Interno',
        'company_id' => $otherCompany->id,
        'onlyparner' => false,
    ]);
    $internal->Companies()->syncWithoutDetaching([$company->id]);
    Bancoupdate::query()->create(['last_update' => now()]);

    $this->actingAs($admin)
        ->get(route('partner.admin.users'))
        ->assertOk()
        ->assertSee($visible->name)
        ->assertDontSee($hidden->name)
        ->assertDontSee($internal->name);
});

it('rejects assigning a user to a branch from another company', function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $company = partnerAccessCompany();
    $otherCompany = partnerAccessCompany();
    $admin = partnerAccessUser($company, ['admin' => true]);
    $target = partnerAccessUser($company);
    $otherBranch = Andresscompany::query()->create([
        'company_id' => $otherCompany->id,
        'city' => 'Outra cidade',
        'street' => 'Rua externa',
    ]);

    $this->actingAs($admin)
        ->put(route('partner.admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'branches' => [$otherBranch->id],
        ])
        ->assertForbidden();
});

it('restricts partner data queries to assigned branches when branch links exist for the company', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);

    $allowedBranch = Andresscompany::query()->create([
        'company_id' => $company->id,
        'city' => 'Campinas',
        'street' => 'Base Campinas',
    ]);
    Andresscompany::query()->create([
        'company_id' => $company->id,
        'city' => 'Santos',
        'street' => 'Base Santos',
    ]);

    PartnerUserBranch::query()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'branch_id' => $allowedBranch->id,
    ]);

    $visible = partnerAccessViability($company, $user, '1001', 'Campinas');
    $hidden = partnerAccessViability($company, $user, '1002', 'Santos');

    $query = Viability::query()->where('company_id', $company->id);
    app(PartnerBranchScope::class)->applyToNoteRelation($query, $user, $company->id);

    expect($query->pluck('id')->all())->toBe([$visible->id])
        ->and($query->pluck('id')->all())->not->toContain($hidden->id);
});

it('does not restrict admins by branch assignment', function () {
    $company = partnerAccessCompany();
    $admin = partnerAccessUser($company, ['admin' => true]);

    $branch = Andresscompany::query()->create([
        'company_id' => $company->id,
        'city' => 'Campinas',
    ]);

    PartnerUserBranch::query()->create([
        'company_id' => $company->id,
        'user_id' => $admin->id,
        'branch_id' => $branch->id,
    ]);

    partnerAccessViability($company, $admin, '2001', 'Campinas');
    partnerAccessViability($company, $admin, '2002', 'Santos');

    $query = Viability::query()->where('company_id', $company->id);
    app(PartnerBranchScope::class)->applyToNoteRelation($query, $admin, $company->id);

    expect($query->count())->toBe(2);
});

it('keeps legacy data visibility when no branch assignment exists for the company', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);

    partnerAccessViability($company, $user, '3001', 'Campinas');
    partnerAccessViability($company, $user, '3002', 'Santos');

    $query = Viability::query()->where('company_id', $company->id);
    app(PartnerBranchScope::class)->applyToNoteRelation($query, $user, $company->id);

    expect($query->count())->toBe(2);
});

it('blocks a Livewire partner action when the permission is disabled', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);
    $viability = partnerAccessViability($company, $user, '4001', 'Campinas');

    partnerAccessRoleWithPermissions($company, ['viability'], ['viability.respond']);

    $this->actingAs($user);

    expect(fn () => app(ViabResponse::class)->getResponse($viability))
        ->toThrow(HttpException::class);
});

it('blocks a Livewire export action when the export permission is disabled', function () {
    $company = partnerAccessCompany();
    $user = partnerAccessUser($company);

    partnerAccessRoleWithPermissions($company, ['viability'], ['viability.export']);

    $this->actingAs($user);

    expect(fn () => app(Todoviability::class)->export_excel())
        ->toThrow(HttpException::class);
});

it('blocks direct access to partner import template without permission', function () {
    $company = partnerAccessCompany();
    $admin = partnerAccessUser($company, ['admin' => true]);

    partnerAccessGrantWithPermissions($company, ['admin', 'admin_panel.access']);

    $this->actingAs($admin)
        ->get(route('partner.admin.users.import_template'))
        ->assertForbidden();
});

it('blocks direct access to partner bulk import without permission', function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $company = partnerAccessCompany();
    $admin = partnerAccessUser($company, ['admin' => true]);

    partnerAccessGrantWithPermissions($company, ['admin', 'admin_panel.access']);

    $file = UploadedFile::fake()->createWithContent('usuarios.csv', "Nome,Email,Filial\nMaria,maria@example.test,Campinas\n");

    $this->actingAs($admin)
        ->post(route('partner.admin.users.import.preview'), ['file' => $file])
        ->assertForbidden();
});

it('shows import preview errors for duplicate emails and branches from another company', function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $company = partnerAccessCompany();
    $otherCompany = partnerAccessCompany();
    $admin = partnerAccessUser($company, ['admin' => true]);
    Bancoupdate::query()->create(['last_update' => now()]);

    Andresscompany::query()->create([
        'company_id' => $company->id,
        'city' => 'Campinas',
    ]);
    $otherBranch = Andresscompany::query()->create([
        'company_id' => $otherCompany->id,
        'city' => 'Santos',
    ]);

    $csv = "Nome,Email,Filial\n"
        ."Maria,maria@example.test,Campinas\n"
        ."Maria Duplicada,maria@example.test,Campinas\n"
        ."Joao,joao@example.test,{$otherBranch->id}\n";

    $file = UploadedFile::fake()->createWithContent('usuarios.csv', $csv);

    $this->actingAs($admin)
        ->post(route('partner.admin.users.import.preview'), ['file' => $file])
        ->assertOk()
        ->assertSee('Email duplicado no arquivo.')
        ->assertSee('Filial não encontrada na empresa.');
});
