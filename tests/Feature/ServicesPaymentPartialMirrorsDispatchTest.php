<?php

use App\Http\Livewire\Services\Payment\Main as ServicesMain;
use App\Models\{Company, Note, Partial, Production, Service, User};
use App\Support\Ops\RowState;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// A lista de Serviços é o reflexo da Lista para Pagamento do Despacho: uma linha por Parcial em medição, com o id da Parcial no
// contexto. A Medição já concluída de outra Parcial da mesma Nota não pode bloquear a Parcial nova (caso real: Nota 3505668,
// Parcial #1550 paga e Parcial #1587 aguardando).

function mirrorFixture(): array
{
    $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento', 'status' => 2]);
    $company = Company::create(['name' => 'Manserv', 'email' => 'm@example.test']);
    $user    = User::factory()->create(['company_id' => $company->id]);
    $note    = Note::create(['note' => '3505668', 'type_note' => 1, 'dt_status' => '2026-10-29 08:00:00', 'nstats' => 'NEW']);

    $paid = Partial::create(['note_id' => $note->id, 'allow' => true, 'deny' => false, 'supervision' => true, 'payment' => false, 'supervision_at' => '2026-09-17 12:00:00']);
    $next = Partial::create(['note_id' => $note->id, 'allow' => true, 'deny' => false, 'supervision' => true, 'payment' => false, 'supervision_at' => '2026-10-29 12:00:00']);

    $production = Production::create([
        'note_id'      => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'user_id' => $user->id, 'status' => 5,
        'dt_note'      => now(), 'status_note' => 2, 'partial' => true, 'completed' => true, 'completed_at' => now()->subDays(40), 'confirmed' => true,
    ]);
    $production->partialInforms()->attach($paid->id);

    return [$service, $user, $note->fresh(), $paid, $next];
}

it('cria uma linha por Parcial em medição com o id da Parcial no contexto, como o Despacho', function () {
    [$service, $user, $note, $paid, $next] = mirrorFixture();
    $this->actingAs($user);

    $note->load(['Partials', 'Productions', 'WorkForms', 'FiveNote', 'FiveNotes']);

    $component = app(ServicesMain::class);
    $component->service = $service;

    $method = new ReflectionMethod(ServicesMain::class, 'expandPaymentRows');
    $method->setAccessible(true);
    $rows = $method->invoke($component, collect([$note]));

    expect($rows->pluck('dispatch_partial_id')->sort()->values()->all())->toBe([$paid->id, $next->id])
        ->and($rows->pluck('payment_context_key')->all())->toContain($note->id . ':0:p' . $next->id);
});

it('a Medição concluída de uma Parcial não bloqueia a Parcial nova da mesma Nota', function () {
    [$service, $user, $note, $paid, $next] = mirrorFixture();
    $this->actingAs($user);

    $note->load(['Partials', 'Productions', 'WorkForms', 'FiveNote', 'FiveNotes']);

    $component = app(ServicesMain::class);
    $component->service = $service;

    $method = new ReflectionMethod(ServicesMain::class, 'expandPaymentRows');
    $method->setAccessible(true);
    $rows = $method->invoke($component, collect([$note]))->keyBy('dispatch_partial_id');

    // Sem o id da Parcial a linha pegava a produção mais recente da Nota (a da outra Parcial) e ficava vermelha.
    $free = $component->rowDispatchState($rows[$next->id]);

    expect($free['eval']['block'])->toBe(0)
        ->and($free['canDispatch'])->toBeTrue()
        ->and(RowState::fromBlock((int) $free['eval']['block']))->toBe(RowState::FREE);

    // A Parcial que já foi medida continua bloqueada: o bloqueio é por Parcial, não da Nota inteira.
    expect($component->rowDispatchState($rows[$paid->id])['eval']['block'])->not->toBe(0);
});
