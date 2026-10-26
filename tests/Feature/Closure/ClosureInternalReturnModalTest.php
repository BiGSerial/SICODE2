<?php

use App\Http\Livewire\Closure\InternalReturns\Modal;
use App\Http\Livewire\Closure\Work\Index as WorkIndex;
use App\Models\{Category, Service, Subcategory, Viability};
use App\Models\Closure\ClosureInternalReturn;
use App\Services\Closure\ClosureInternalReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

// Tela do RI: Ações › Enviar RI ao Desenho… (modal com as Ordens que travam) e Cancelar RI (gestão).

uses(RefreshDatabase::class);

beforeEach(fn () => Notification::fake());

function riModalFixture(): array
{
    $f              = closureCollabFixture();
    $f['design']    = Service::query()->firstOrCreate(['folder' => 'desenho'], ['service' => 'Desenho', 'project' => true, 'construction' => false, 'icon' => 'ri-pencil-ruler-line']);
    $f['viability'] = Viability::query()->create(['note_id' => $f['note']->id, 'company_id' => $f['company']->id, 'user_id' => $f['internal']->id, 'status' => 6]);
    $f['second']    = closureCollabExtraOrder($f);
    // Ordem do responsável em tratativa: aparece na lista "Minha atividade" e permite as ações.
    $f['state']->update(['operational_status' => 'IN_PROGRESS', 'assigned_user_id' => $f['internal']->id, 'current_holder_type' => 'INTERNAL_USER', 'current_holder_user_id' => $f['internal']->id]);

    return $f;
}

it('lists every active order of the work before sending, requires category and comment and sends the RI', function () {
    $f   = riModalFixture();
    $cat = Category::query()->create(['name' => 'PROJETO']);
    $sub = Subcategory::query()->create(['name' => 'ALTERAÇÃO NO PROJETO', 'category_id' => $cat->id, 'needFile' => true]);

    $component = Livewire::actingAs($f['internal'])->test(Modal::class)
        ->call('openSend', $f['note']->id)
        ->assertSee('Enviar RI ao Desenho')
        ->assertSee($f['order']->ordem)
        ->assertSee($f['second']->Order->ordem)
        ->assertSee('travadas')
        ->assertSee('PROJETO')
        ->call('submit')->assertHasErrors(['categoryId']);

    $component->set('categoryId', (string) $cat->id)->assertSee('ALTERAÇÃO NO PROJETO')
        ->set('subcategoryId', (string) $sub->id)->call('submit')->assertHasErrors(['message']);

    expect(ClosureInternalReturn::query()->count())->toBe(0);

    $component->set('message', 'Projeto divergente do executado.')->call('submit')
        ->assertDispatchedBrowserEvent('notification:toast', ['title' => 'RI enviado ao Desenho', 'text' => 'Todas as Ordens da obra ficam travadas até o Desenho concluir.', 'icon' => 'success'])
        ->assertEmitted('closureRiChanged')->assertSet('mode', '');

    $ri = ClosureInternalReturn::query()->where('note_id', $f['note']->id)->where('status', 'SENT')->first();

    expect($ri)->not->toBeNull()
        ->and(\App\Models\Reclaim::query()->find($ri->reclaim_id))->subcategory_id->toBe($sub->id)->category->toBe('PROJETO');
});

it('resets the subcategory when the category changes', function () {
    $f = riModalFixture();
    $a = Category::query()->create(['name' => 'A']);
    $b = Category::query()->create(['name' => 'B']);
    $s = Subcategory::query()->create(['name' => 'SUB A', 'category_id' => $a->id]);

    Livewire::actingAs($f['internal'])->test(Modal::class)->call('openSend', $f['note']->id)
        ->set('categoryId', (string) $a->id)->set('subcategoryId', (string) $s->id)
        ->set('categoryId', (string) $b->id)->assertSet('subcategoryId', '');
});

it('shows the service refusal inside the modal instead of breaking', function () {
    $f   = riModalFixture();
    $cat = Category::query()->create(['name' => 'PROJETO']);
    $sub = Subcategory::query()->create(['name' => 'ALTERAÇÃO', 'category_id' => $cat->id]);
    app(ClosureInternalReturnService::class)->send($f['note'], $f['dispatcher'], 'primeiro');

    Livewire::actingAs($f['internal'])->test(Modal::class)
        ->call('openSend', $f['note']->id)->set('categoryId', (string) $cat->id)->set('subcategoryId', (string) $sub->id)->set('message', 'segundo')->call('submit')
        ->assertSee('Já existe um RI ativo');

    expect(ClosureInternalReturn::query()->count())->toBe(1);
});

it('lets only the management cancel an active RI', function () {
    $f  = riModalFixture();
    $ri = app(ClosureInternalReturnService::class)->send($f['note'], $f['dispatcher'], 'x');

    Livewire::actingAs($f['internal'])->test(Modal::class)
        ->call('openCancel', $ri->id)->set('message', 'tentando')->call('submit')
        ->assertSee('Somente a gestão pode cancelar');

    expect($ri->fresh()->status)->toBe('SENT');

    Livewire::actingAs($f['dispatcher'])->test(Modal::class)
        ->call('openCancel', $ri->id)->assertSee('Cancelar RI')->assertSee('destrava')
        ->call('submit')->assertHasErrors(['message'])
        ->set('message', 'Enviado por engano')->call('submit')
        ->assertDispatchedBrowserEvent('notification:toast', ['title' => 'RI cancelado', 'text' => 'As Ordens da obra foram destravadas. O retorno do Desenho não foi alterado.', 'icon' => 'success']);

    expect($ri->fresh()->status)->toBe('CANCELLED');
});

it('offers the RI in the actions menu only to the owner or the management, and swaps it for the lock when active', function () {
    $f    = riModalFixture();
    $list = new WorkIndex();

    $this->actingAs($f['internal']);
    expect($list->canSendRi($f['state']->fresh()))->toBeTrue();

    $this->actingAs($f['outsider']);
    expect($list->canSendRi($f['state']->fresh()))->toBeFalse();

    $this->actingAs($f['dispatcher']);
    expect($list->canSendRi($f['state']->fresh()))->toBeTrue();

    $ri = app(ClosureInternalReturnService::class)->send($f['note'], $f['dispatcher'], 'x');

    $html = Livewire::actingAs($f['internal'])->test(WorkIndex::class)->payload['effects']['html'];

    expect($html)->toContain('RI com o Desenho')->toContain('Obra travada')
        ->not->toContain('Enviar RI ao Desenho…')
        ->not->toContain('Iniciar</button>');

    // a gestão vê "Cancelar RI…" no menu
    expect(Livewire::actingAs($f['dispatcher'])->test(WorkIndex::class)->payload['effects']['html'])->toContain('Cancelar RI…');

    expect($ri->status)->toBe('SENT');
});

it('groups the actions menu by intent when the work is free', function () {
    $f    = riModalFixture();
    $html = Livewire::actingAs($f['internal'])->test(WorkIndex::class)->payload['effects']['html'];

    $missing = array_values(array_filter(['Trabalhar a Ordem', 'Devolver / pedir correção', 'Enviar RI ao Desenho…', 'Enviar à parceira…', 'Passar para outra pessoa', 'Abrir'], fn ($text) => !str_contains($html, $text)));

    expect($missing)->toBe([])->and($html)->not->toContain('Devolver à pilha');
});

it('does not let the executor return an order to the pile from the work screen', function () {
    $f = riModalFixture();

    Livewire::actingAs($f['internal'])->test(WorkIndex::class)->call('runAction', $f['state']->id, 'return');

    expect($f['state']->fresh())->operational_status->toBe('IN_PROGRESS')->assigned_user_id->toBe($f['internal']->id);
});
