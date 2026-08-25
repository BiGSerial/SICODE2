<?php

use App\Http\Livewire\Admin\User\Actions\Usuario;
use App\Http\Livewire\Admin\User\Actions\UsuarioMass;
use App\Models\Company;
use App\Models\Contract;
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
