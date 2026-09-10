<?php

use App\Http\Livewire\Partner\Forms\Workreports;
use App\Models\Company;
use App\Models\Note;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkReport;
use App\Services\Partner\BlockEvaluator;
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

it('allows one active btzero ep work report for each final scope but blocks duplicates', function () {
    config(['sicode.ruleset' => 'sp']);
    config(['features.suspend_work_report_note_status_blocks' => true]);

    $company = Company::query()->create([
        'name' => 'Empreiteira BTZERO',
        'email' => 'btzero@example.test',
    ]);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'onlyparner' => true,
        'first_pass' => false,
    ]);

    $note = Note::query()->create([
        'note' => '1234567891',
        'nstats' => 51,
        'type_note' => 1,
    ]);

    $order170 = Order::query()->create([
        'note_id' => $note->id,
        'ordem' => '170000000001',
        'statusSist' => 'ABER',
    ]);

    $order180 = Order::query()->create([
        'note_id' => $note->id,
        'ordem' => '180000000001',
        'statusSist' => 'ABER',
    ]);

    $sendWorkReport = function (string $scope, Order $order) use ($user, $note) {
        Livewire::actingAs($user)
            ->test(Workreports::class)
            ->set('note', $note)
            ->set('temp_orders', [$order->id => ['id' => $order->id, 'ordem' => $order->ordem]])
            ->set('selectedFinalScopeMode', $scope)
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
    };

    $evaluator = new BlockEvaluator();

    $sendWorkReport('network', $order170);

    expect($evaluator->evaluate($note->fresh())->command)->toBeTrue();

    $sendWorkReport('connection', $order180);

    expect($evaluator->evaluate($note->fresh())->command)->toBeFalse();

    $sendWorkReport('network', $order170);

    expect(WorkReport::query()->where('note_id', $note->id)->where('canceled', false)->count())->toBe(2);

    $scopes = WorkReport::query()
        ->where('note_id', $note->id)
        ->orderBy('id')
        ->pluck('selected_final_scopes')
        ->map(fn ($scopes) => $scopes[0] ?? null)
        ->all();

    expect($scopes)->toBe(['network', 'connection']);
});
