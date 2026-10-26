<?php

use App\Http\Livewire\Closure\Works\Show;
use App\Models\{Bancoupdate, Company, User};
use App\Models\Closure\{ClosureIssue, ClosureWorkMessage};
use App\Services\Closure\{ClosureIssueWorkflowService, ClosureWorkService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

// ISOLAMENTO DE VISÃO: cada rota da tela da obra tem UM público, independente de quem está logado.
//  - /partner/encerramento/obra/{nota}  => sempre a visão da parceira (superadm observa como as empresas da obra, sem controles internos);
//  - /encerramento/obra/{nota}          => nunca a visão da parceira (quem é só parceira é redirecionado para a rota dela).

uses(RefreshDatabase::class);

// O notificador enfileira em `jobs` (inexistente no banco de teste): como nos demais testes do módulo, as notificações são simuladas.
beforeEach(fn () => Notification::fake());

/** Controles que só a equipe do Encerramento pode ter. */
const CLOSURE_INTERNAL_CONTROLS = ['Enviar à parceira', 'Pedir prioridade', 'Prioridade da obra', 'Desbloquear sem retorno', 'Alterar status', 'Finalizar obra'];

function partnerAudience(array $f, User $user)
{
    return Livewire::actingAs($user)->test(Show::class, ['noteId' => $f['note']->id, 'audience' => 'partner']);
}

/** O layout das páginas completas lê o último registro de atualização do banco (status/bancosicode). */
function seedLayoutData(): void
{
    Bancoupdate::create(['last_update' => now(), 'error' => 0, 'inserts' => 0, 'updates' => 0, 'info' => 'teste']);
}

function observer(array $overrides = []): User
{
    return User::factory()->create(array_merge(['superadm' => false, 'admin' => false, 'closure_manager' => false, 'closure_operator' => false], $overrides));
}

it('shows a real partner user none of the internal controls', function () {
    $f = closureScreenFixture();

    $component = partnerAudience($f, $f['partnerUser']);

    foreach (CLOSURE_INTERNAL_CONTROLS as $control) {
        $component->assertDontSee($control);
    }

    $component->assertSet('level', ClosureWorkService::LEVEL_PARTNER)->assertDontSee('Modo observador');
});

it('shows the SUPERADM only the partner view on the partner route, with the observer notice and no internal controls', function () {
    $f     = closureScreenFixture();
    $super = observer(['superadm' => true]);

    $component = partnerAudience($f, $super);

    foreach (CLOSURE_INTERNAL_CONTROLS as $control) {
        $component->assertDontSee($control);
    }

    $component->assertSet('level', ClosureWorkService::LEVEL_PARTNER)
        ->assertSee('Modo observador')
        ->assertSee($f['company']->name);
});

it('isolates the data: the observer sees only what the partner sees (own orders, own issues, no internal chat, no other company)', function () {
    $f                                      = closureScreenFixture();
    ['category' => $c, 'subcategory' => $s] = closureIssueTaxonomy();

    $own     = app(ClosureIssueWorkflowService::class)->requestToPartner($f['state'], $c, $s, $f['company'], $f['dispatcher'], 'Pedido para a empresa certa.');
    $foreign = ClosureIssue::query()->create([
        'order_id'                     => $f['state']->order_id, 'closure_order_state_id' => $f['state']->id, 'closure_issue_category_id' => $c->id,
        'closure_issue_subcategory_id' => $s->id, 'destination_company_id' => $f['other']->id, 'status' => ClosureIssue::STATUS_REQUESTED,
        'blocking'                     => false, 'description' => 'Pedido SIGILOSO de outra empresa.', 'opened_at' => now(),
    ]);
    app(ClosureWorkService::class)->postMessage($f['note'], $f['dispatcher'], 'Conversa SOMENTE interna.', ClosureWorkMessage::VISIBILITY_INTERNAL_ONLY);
    app(ClosureWorkService::class)->postMessage($f['note'], $f['dispatcher'], 'Aviso para a parceira.', ClosureWorkMessage::VISIBILITY_WITH_PARTNER);

    foreach ([$f['partnerUser'], observer(['superadm' => true])] as $viewer) {
        partnerAudience($f, $viewer)
            ->assertSee('Aviso para a parceira.')
            ->assertSee('Pedido para a empresa certa.')
            ->assertDontSee('Conversa SOMENTE interna.')
            ->assertDontSee('Pedido SIGILOSO de outra empresa.');
    }

    expect($own->id)->not->toBe($foreign->id);

    // O observador escolhe UMA empresa por vez: ao trocar, vê a visão da outra e deixa de ver a primeira.
    partnerAudience($f, observer(['superadm' => true]))
        ->set('observeCompanyId', (string) $f['other']->id)
        ->assertSee('Pedido SIGILOSO de outra empresa.')
        ->assertDontSee('Pedido para a empresa certa.')
        ->assertDontSee('Conversa SOMENTE interna.');
});

it('refuses the observer and the partner the internal actions even on the partner route', function () {
    $f = closureScreenFixture();

    foreach ([$f['partnerUser'], observer(['superadm' => true])] as $viewer) {
        $component = partnerAudience($f, $viewer);

        $component->call('openPartnerModal', $f['state']->id)->assertForbidden();
        $component->call('sendToPartner')->assertForbidden();
        $component->call('requestPriority')->assertForbidden();
        $component->call('selectStateForStatus', $f['state']->id)->assertForbidden();
        $component->call('askFinish', 'all')->assertForbidden();
        $component->call('decidePriority', 1, 'accept')->assertForbidden();
    }
});

it('does not give the partner view to a user without partner permission or without a company on the note', function () {
    $f = closureScreenFixture();

    $stranger = observer(['company_id' => $f['other']->id]);
    $internal = $f['dispatcher'];

    Livewire::actingAs($stranger)->test(Show::class, ['noteId' => $f['note']->id, 'audience' => 'partner'])->assertForbidden();
    Livewire::actingAs($f['outsider'])->test(Show::class, ['noteId' => $f['note']->id, 'audience' => 'partner'])->assertForbidden();
    // gestor sem vínculo de parceira não ganha a visão da parceira só por ser gestor
    Livewire::actingAs($internal)->test(Show::class, ['noteId' => $f['note']->id, 'audience' => 'partner'])->assertForbidden();
});

it('never gives the partner view on the internal route and keeps the dispatcher view there', function () {
    $f = closureScreenFixture();

    Livewire::actingAs($f['partnerUser'])->test(Show::class, ['noteId' => $f['note']->id, 'audience' => 'internal'])->assertForbidden();

    foreach (['gestor' => $f['dispatcher'], 'superadm' => observer(['superadm' => true]), 'admin' => observer(['admin' => true])] as $label => $user) {
        Livewire::actingAs($user)->test(Show::class, ['noteId' => $f['note']->id, 'audience' => 'internal'])
            ->assertSet('level', ClosureWorkService::LEVEL_DISPATCHER)
            ->assertSee('Enviar à parceira')
            ->assertDontSee('Modo observador');
    }
});

it('redirects a partner-only user from the internal route to the partner route, and lets internal users stay', function () {
    $f = closureScreenFixture();
    seedLayoutData();

    $this->actingAs($f['partnerUser'])
        ->get(route('closure.work.show', ['note' => $f['note']->id]))
        ->assertRedirect(route('partner.closure.work.show', ['note' => $f['note']->id]));

    $this->actingAs($f['dispatcher'])
        ->get(route('closure.work.show', ['note' => $f['note']->id]))
        ->assertOk();
});

it('lets an observer open the partner route without redirect, while a company without link is blocked', function () {
    $f = closureScreenFixture();
    seedLayoutData();

    $this->actingAs(observer(['superadm' => true]))
        ->get(route('partner.closure.work.show', ['note' => $f['note']->id]))
        ->assertOk()
        ->assertSee('Modo observador');

    $stranger = observer(['company_id' => Company::query()->create(['name' => 'Estranha ' . fake()->unique()->numerify('###'), 'email' => fake()->unique()->safeEmail()])->id]);

    $this->actingAs($stranger)->get(route('partner.closure.work.show', ['note' => $f['note']->id]))->assertForbidden();
});
