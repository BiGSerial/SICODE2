<?php

use App\Http\Livewire\Admin\Hierarchy\Org;
use App\Models\{User, UserDelegation};
use App\Services\Hierarchy\HierarchyTreeService;
use App\Services\HierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function hierUser(string $name, ?User $manager = null): User
{
    $user = User::factory()->create(['name' => $name, 'manager_id' => $manager?->id]);
    app(HierarchyService::class)->attachNewUserUnderManager((string) $user->id, $manager ? (string) $manager->id : null);

    return $user;
}

function hierAdmin(): User
{
    return User::factory()->create(['admin' => true, 'superadm' => true]);
}

it('entrega a árvore plana com o gestor de cada um', function () {
    $boss   = hierUser('Chefe');
    $worker = hierUser('Analista', $boss);

    $nodes = collect(app(HierarchyTreeService::class)->nodes())->keyBy('i');

    expect($nodes[(string) $worker->id]['m'])->toBe((string) $boss->id)
        ->and($nodes[(string) $boss->id]['m'])->toBeNull();
});

it('barra mover para a própria equipe e para o mesmo gestor', function () {
    $boss = hierUser('Chefe');
    $mid  = hierUser('Meio', $boss);
    $low  = hierUser('Base', $mid);
    $svc  = app(HierarchyTreeService::class);

    expect($svc->impact((string) $boss->id, (string) $low->id)['ok'])->toBeFalse()
        ->and($svc->impact((string) $mid->id, (string) $mid->id)['ok'])->toBeFalse()
        ->and($svc->impact((string) $mid->id, (string) $boss->id)['ok'])->toBeFalse();
});

it('mostra o impacto antes de gravar e leva a equipe junto', function () {
    $a      = hierUser('A');
    $b      = hierUser('B');
    $leaf   = hierUser('Folha', $a);
    $impact = app(HierarchyTreeService::class)->impact((string) $a->id, (string) $b->id);

    expect($impact['ok'])->toBeTrue()
        ->and($impact['subtree'])->toBe(1)
        ->and($impact['to'])->toBe(['B'])
        ->and($leaf->fresh()->manager_id)->toBe($a->id);
});

it('realinha vários de uma vez pelo componente, confirmando antes', function () {
    $this->actingAs(hierAdmin());
    $newBoss = hierUser('Novo gestor');
    $x       = hierUser('X');
    $y       = hierUser('Y');

    Livewire::test(Org::class)
        ->call('prepareMove', [(string) $x->id, (string) $y->id], (string) $newBoss->id)
        ->assertSet('moveIds', [(string) $x->id, (string) $y->id])
        ->call('confirmMove')
        ->assertDispatchedBrowserEvent('hier-reload');

    expect($x->fresh()->manager_id)->toBe($newBoss->id)
        ->and($y->fresh()->manager_id)->toBe($newBoss->id)
        ->and(\DB::table('user_closure')->where('ancestor_id', $newBoss->id)->where('descendant_id', $x->id)->exists())->toBeTrue();
});

it('registra e encerra uma cobertura (quem cobre quem)', function () {
    $this->actingAs(hierAdmin());
    $titular = hierUser('Titular');
    $cobre   = hierUser('Cobre');

    Livewire::test(Org::class)
        ->call('select', (string) $titular->id)
        ->call('openDelegation')
        ->set('dlg_delegate_id', (string) $cobre->id)
        ->call('saveDelegation')
        ->assertHasNoErrors();

    $delegation = UserDelegation::query()->where('principal_id', $titular->id)->firstOrFail();
    expect(app(HierarchyTreeService::class)->badges()['covers'][(string) $titular->id]['d'])->toBe((string) $cobre->id);

    Livewire::test(Org::class)
        ->call('select', (string) $titular->id)
        ->call('askConfirm', 'delegation', 'finish', (string) $delegation->id)
        ->call('runConfirm');

    expect(app(HierarchyTreeService::class)->badges()['covers'])->toBeEmpty();
});

it('não deixa cobrir a si mesmo', function () {
    $this->actingAs(hierAdmin());
    $titular = hierUser('Titular');

    Livewire::test(Org::class)
        ->call('select', (string) $titular->id)
        ->set('dlg_delegate_id', (string) $titular->id)
        ->call('saveDelegation')
        ->assertHasErrors('dlg_delegate_id');
});

it('serve a árvore em JSON só para administrador', function () {
    hierUser('Alguém');

    $this->getJson(route('admin.user.hierarchy.nodes'))->assertUnauthorized();
    $this->actingAs(hierAdmin())->getJson(route('admin.user.hierarchy.nodes'))
        ->assertOk()->assertJsonStructure(['nodes', 'covers', 'observers']);
});
