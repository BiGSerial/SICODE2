<?php

use App\Http\Livewire\Admin\User\Actions\Usuario;
use App\Http\Livewire\Admin\User\Actions\UsuarioMass;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function optionalActivityCompany(): Company
{
    return Company::query()->create([
        'name' => 'Empresa sem atividade',
        'email' => fake()->unique()->safeEmail(),
    ]);
}

function optionalActivityContract(Company $company): Contract
{
    $contract = new Contract();
    $contract->company_id = $company->id;
    $contract->number = 'SEM-ATIVIDADE';
    $contract->service = true;
    $contract->construction = false;
    $contract->date_end = now()->addYear()->toDateString();
    $contract->save();

    return $contract;
}

function optionalActivityService(string $name): Service
{
    return Service::query()->create([
        'service' => $name,
        'status' => true,
        'folder' => false,
        'project' => false,
        'construction' => false,
        'canReturn' => false,
    ]);
}

function attachOptionalActivityContractService(Contract $contract, Service $service): void
{
    $contract->services()->attach($service->id, [
        'posts' => false,
        'qtd' => 1,
        'days' => 1,
        'dispatch' => false,
    ]);
}

it('allows creating a user without selecting an activity when the contract has no activities', function () {
    $actor = User::factory()->create(['superadm' => true, 'admin' => true]);
    $company = optionalActivityCompany();
    $contract = optionalActivityContract($company);

    Livewire::actingAs($actor)
        ->test(Usuario::class)
        ->call('newUser')
        ->set('user.email', 'sem-atividade@example.test')
        ->set('user.name', 'Usuario Sem Atividade')
        ->set('user.company_id', $company->id)
        ->set('company', $company->id)
        ->set('contract', $contract->id)
        ->call('Save');

    $user = User::query()->where('email', 'sem-atividade@example.test')->firstOrFail();

    expect($user->Employee?->contract_id)->toBe($contract->id)
        ->and($user->Employee?->service_id)->toBeNull()
        ->and($user->ToServices()->count())->toBe(0);
});

it('validates required user identity fields before creating a user', function () {
    $actor = User::factory()->create(['superadm' => true, 'admin' => true]);
    $company = optionalActivityCompany();
    $contract = optionalActivityContract($company);

    Livewire::actingAs($actor)
        ->test(Usuario::class)
        ->call('newUser')
        ->set('user.name', 'Usuario Sem Email')
        ->set('user.company_id', $company->id)
        ->set('contract', $contract->id)
        ->call('Save')
        ->assertHasErrors(['user.email' => 'required']);

    expect(User::query()->where('name', 'Usuario Sem Email')->exists())->toBeFalse();
});

it('allows mass updating users without selecting an activity when the contract has no activities', function () {
    $actor = User::factory()->create(['superadm' => true, 'admin' => true]);
    $company = optionalActivityCompany();
    $contract = optionalActivityContract($company);
    $users = User::factory()->count(2)->create(['company_id' => $company->id]);

    Livewire::actingAs($actor)
        ->test(UsuarioMass::class)
        ->call('alterUsers', $users->pluck('id')->all())
        ->set('company', $company->id)
        ->set('contract', $contract->id)
        ->call('Save');

    foreach ($users->fresh() as $user) {
        expect($user->Employee?->contract_id)->toBe($contract->id)
            ->and($user->Employee?->service_id)->toBeNull()
            ->and($user->ToServices()->count())->toBe(0);
    }
});

it('preserves existing user activities during mass update', function () {
    $actor = User::factory()->create(['superadm' => true, 'admin' => true]);
    $company = optionalActivityCompany();
    $contract = optionalActivityContract($company);
    $contractService = optionalActivityService('Contrato Atividade A');
    $existingService = optionalActivityService('Atividade Existente');
    attachOptionalActivityContractService($contract, $contractService);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'admin' => false,
        'operator' => false,
        'user' => true,
    ]);
    $user->Employee()->create([
        'contract_id' => $contract->id,
        'service_id' => $existingService->uuid,
    ]);
    $user->ToServices()->create([
        'service_id' => $existingService->uuid,
        'service' => true,
        'dispatch' => false,
    ]);

    Livewire::actingAs($actor)
        ->test(UsuarioMass::class)
        ->call('alterUsers', [$user->id])
        ->set('company', $company->id)
        ->set('contract', $contract->id)
        ->call('Save');

    $user->refresh();

    expect($user->Employee?->contract_id)->toBe($contract->id)
        ->and($user->Employee?->service_id)->toBe($existingService->uuid)
        ->and($user->ToServices()->pluck('service_id')->all())->toBe([$existingService->uuid]);
});

it('does not add every contract activity during mass update', function () {
    $actor = User::factory()->create(['superadm' => true, 'admin' => true]);
    $company = optionalActivityCompany();
    $contract = optionalActivityContract($company);
    $firstService = optionalActivityService('Contrato Atividade A');
    $secondService = optionalActivityService('Contrato Atividade B');
    attachOptionalActivityContractService($contract, $firstService);
    attachOptionalActivityContractService($contract, $secondService);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'admin' => false,
        'operator' => false,
        'user' => true,
    ]);

    Livewire::actingAs($actor)
        ->test(UsuarioMass::class)
        ->call('alterUsers', [$user->id])
        ->set('company', $company->id)
        ->set('contract', $contract->id)
        ->call('Save');

    $user->refresh();

    expect($user->Employee?->contract_id)->toBe($contract->id)
        ->and($user->Employee?->service_id)->toBe($firstService->uuid)
        ->and($user->ToServices()->pluck('service_id')->all())->toBe([$firstService->uuid]);
});
