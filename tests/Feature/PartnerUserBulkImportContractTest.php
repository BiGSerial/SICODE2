<?php

use App\Models\Andresscompany;
use App\Models\Company;
use App\Models\Contract;
use App\Models\PartnerCompanyPermissionGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Middleware\VerifyCsrfToken;

uses(RefreshDatabase::class);

function partnerBulkContractCompany(string $name = 'Parceira Bulk'): Company
{
    return Company::query()->create([
        'name' => $name,
        'email' => fake()->unique()->safeEmail(),
    ]);
}

function partnerBulkContractUser(Company $company): User
{
    return User::factory()->create([
        'company_id' => $company->id,
        'onlyparner' => true,
        'admin' => true,
        'superadm' => false,
        'management' => false,
        'operator' => false,
        'user' => false,
        'contract' => false,
        'first_pass' => false,
        'bypassprod' => false,
        'engineer' => false,
        'responsible' => false,
        'btzero' => false,
        'can_dispatch' => false,
        'analyst' => false,
        'legal_controller' => false,
        'legal_field' => false,
        'legal_manager' => false,
    ]);
}

function partnerBulkGrantWithPermissions(Company $company, array $enabledPermissions): void
{
    foreach (\App\Services\PartnerAccess\PartnerPermissionCatalog::allPermissionKeys() as $permissionKey) {
        PartnerCompanyPermissionGrant::query()->create([
            'company_id' => $company->id,
            'permission_key' => $permissionKey,
            'scope_type' => array_key_exists($permissionKey, \App\Services\PartnerAccess\PartnerPermissionCatalog::groups()) ? 'group' : 'item',
            'enabled' => in_array($permissionKey, $enabledPermissions, true),
        ]);
    }
}

it('associates partner bulk imported users to the resolved contract', function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    $company = partnerBulkContractCompany();
    $admin = partnerBulkContractUser($company);
    $contract = new Contract();
    $contract->company_id = $company->id;
    $contract->number = 'BULK-1';
    $contract->service = true;
    $contract->date_end = now()->addYear()->toDateString();
    $contract->save();
    $branch = Andresscompany::query()->create([
        'company_id' => $company->id,
        'city' => 'Campinas',
    ]);

    partnerBulkGrantWithPermissions($company, ['admin', 'admin_panel.access', 'admin_users.bulk_import']);

    session()->put('partner_user_import.test-token', [[
        'name' => 'Usuario Bulk',
        'email' => 'usuario.bulk@example.test',
        'branch_id' => $branch->id,
        'contract_id' => $contract->id,
    ]]);

    $this->actingAs($admin)
        ->post(route('partner.admin.users.import.confirm'), ['token' => 'test-token'])
        ->assertRedirect(route('partner.admin.users'));

    $user = User::query()->where('email', 'usuario.bulk@example.test')->firstOrFail();

    expect($user->Employee?->contract_id)->toBe($contract->id);
});
