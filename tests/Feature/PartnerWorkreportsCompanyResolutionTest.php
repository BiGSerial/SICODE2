<?php

use App\Http\Livewire\Partner\Forms\Workreports;
use App\Models\Company;
use App\Models\Note;
use App\Models\User;
use App\Models\WorkReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('uses the partner user company when the user has no employee contract', function () {
    $company = Company::query()->create([
        'name' => 'Empreiteira Teste',
        'email' => 'empreiteira@example.test',
    ]);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'onlyparner' => true,
        'superadm' => false,
        'admin' => false,
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

    $note = Note::query()->create([
        'note' => '1234567890',
        'nstats' => 51,
        'type_note' => 2,
    ]);

    Livewire::actingAs($user)
        ->test(Workreports::class)
        ->set('note', $note)
        ->set('hasAsbuilt', true)
        ->set('hasEvidenceFile', true)
        ->set('form.date', now()->toDateString())
        ->set('form.equipment', false)
        ->set('form.connection', true)
        ->set('form.changes', false)
        ->set('form.damage', false)
        ->set('form.team', 'Equipe A')
        ->set('form.dd', 'DD-1')
        ->set('form.responsible', 'Responsavel Teste')
        ->set('form.informer', 'Informante Teste')
        ->set('form.acceptance_accepted', true)
        ->set('form.acceptance_name', 'Usuario Teste')
        ->set('form.asbuilt_confirmation', true)
        ->call('send_informe');

    $workReport = WorkReport::query()->first();

    expect($workReport)->not->toBeNull()
        ->and($workReport->company_id)->toBe($company->id)
        ->and($workReport->user_id)->toBe($user->id);
});
