<?php

use App\Http\Livewire\Closure\Orders\Detail;
use App\Models\{Note, Order, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

function closureDetailUser(array $flags = []): User
{
    return User::factory()->create(array_merge(['superadm' => false, 'admin' => false, 'closure_manager' => false, 'closure_operator' => true], $flags));
}

it('links to the work management and offers dispatch when the order has no tratativa yet', function () {
    $note  = Note::query()->create(['note' => 'DT-' . fake()->unique()->numerify('######'), 'type_note' => 1]);
    $order = Order::query()->create(['note_id' => $note->id, 'ordem' => fake()->unique()->numerify('400#########'), 'statusSist' => 'LIB CNPA']);

    $component = Livewire::actingAs(closureDetailUser(['closure_manager' => true]))
        ->test(Detail::class, ['orderId' => $order->id])
        ->assertSee('Não despachada')
        ->assertSee('Gestão da Obra')
        ->assertSee('Competência')
        ->assertSee('closure-dispatch-open', false);

    expect($component->payload['effects']['html'])->toContain(route('closure.work.show', $note->id));
});

it('hides the dispatch shortcut from users without dispatch permission and from already dispatched orders', function () {
    $note  = Note::query()->create(['note' => 'DT-' . fake()->unique()->numerify('######'), 'type_note' => 1]);
    $order = Order::query()->create(['note_id' => $note->id, 'ordem' => fake()->unique()->numerify('400#########'), 'statusSist' => 'LIB CNPA']);

    Livewire::actingAs(closureDetailUser())
        ->test(Detail::class, ['orderId' => $order->id])
        ->assertSee('Gestão da Obra')
        ->assertDontSee('closure-dispatch-open', false);

    $f = closureScreenFixture();

    Livewire::actingAs(closureDetailUser(['closure_manager' => true, 'superadm' => true]))
        ->test(Detail::class, ['orderId' => $f['order']->id])
        ->assertDontSee('closure-dispatch-open', false)
        ->assertDontSee('Não despachada');
});
