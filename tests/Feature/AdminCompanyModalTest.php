<?php

use App\Http\Livewire\Admin\Company\Action\Update;
use App\Models\{Centerjob, Company, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the company modal in tabs and manages centers/deposits with a real delete button', function () {
    $admin   = User::factory()->create(['superadm' => true, 'admin' => true]);
    $company = Company::query()->create(['name' => 'Engelmig Cachoeiro', 'email' => fake()->unique()->safeEmail()]);

    $component = Livewire::actingAs($admin)->test(Update::class)
        ->call('openModal', $company->id)
        ->assertSee('Engelmig Cachoeiro')
        ->assertSee('Dados e identidade')
        ->assertSee('Centros e depósitos')
        ->assertSee('Nenhum centro ou depósito vinculado a esta empresa.')
        ->call('addCenterjob')
        ->set('centerjob.centerjob', 'engelcac')
        ->set('centerjob.center', '5503')
        ->set('centerjob.deposit', '5011')
        ->call('saveCenterjob')
        ->assertSee('ENGELCAC');

    $row = Centerjob::query()->firstOrFail();
    expect($row->company_id)->toBe($company->id)->and($row->centerjob)->toBe('ENGELCAC');

    expect($component->lastRenderedDom)->toContain('wire:click="removeCenterjob(' . $row->id . ')"');
    $component->call('removeCenterjob', $row->id)->assertSee('Nenhum centro ou depósito vinculado a esta empresa.');
    expect(Centerjob::query()->count())->toBe(0);
});
