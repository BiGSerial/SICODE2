<?php

use App\Http\Livewire\Admin\User\Actions\Usuario;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function userRegionCompany(): Company
{
    return Company::query()->create([
        'name' => 'Empresa de Regiões',
        'email' => fake()->unique()->safeEmail(),
    ]);
}

it('persists regions when creating a user', function () {
    $actor = User::factory()->create(['superadm' => true, 'admin' => true]);
    $company = userRegionCompany();

    Livewire::actingAs($actor)
        ->test(Usuario::class)
        ->call('newUser')
        ->set('user.name', 'Usuário Regional')
        ->set('user.email', 'usuario.regional@example.test')
        ->set('user.company_id', $company->id)
        ->set('temporaryRegions', ['Norte', 'Sul', 'Norte'])
        ->call('Save');

    $user = User::query()->where('email', 'usuario.regional@example.test')->firstOrFail();

    expect($user->regionNames()->sort()->values()->all())->toBe(['Norte', 'Sul'])
        ->and($user->regions()->count())->toBe(2);
});

it('replaces a users regions when editing the association', function () {
    $actor = User::factory()->create(['superadm' => true, 'admin' => true]);
    $company = userRegionCompany();
    $user = User::factory()->create(['company_id' => $company->id]);
    $user->regions()->createMany([
        ['region' => 'Norte'],
        ['region' => 'Sul'],
    ]);

    Livewire::actingAs($actor)
        ->test(Usuario::class)
        ->call('openUser', ['id' => $user->id])
        ->set('temporaryRegions', ['Centro'])
        ->call('Save');

    expect($user->fresh()->regionNames()->all())->toBe(['Centro']);
});
