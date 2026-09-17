<?php

use App\Models\{ApplicationApiToken, Note, Operation, Order, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function manualImportApiToken(): string
{
    $plain = 'sicode_' . Str::random(64);
    $user = User::factory()->create();

    ApplicationApiToken::create([
        'created_by_user_id' => $user->id,
        'name' => 'Teste API',
        'token_prefix' => substr($plain, 0, 16),
        'token_hash' => hash('sha256', $plain),
        'active' => true,
    ]);

    return $plain;
}

it('imports one manual note, order and operations with a bearer token', function () {
    $token = manualImportApiToken();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/imports/manual-records', [
            'notaEP' => ['note' => 'EP-100', 'numPedido' => 'Cliente API'],
            'ordem' => ['ordem' => 'ORDEM-100', 'descricao' => 'Ordem API'],
            'operacoes' => [
                ['operacao' => '0010', 'status' => 'LIB'],
                ['operacao' => '0020', 'status' => 'ENCE'],
            ],
        ]);

    $response->assertOk()->assertJsonPath('summary.records_created', 1);

    $note = Note::query()->where('note', 'EP-100')->firstOrFail();
    $order = Order::query()->where('note_id', $note->id)->where('ordem', 'ORDEM-100')->firstOrFail();

    expect(Operation::query()->where('order_id', $order->id)->count())->toBe(2);
});

it('rejects manual import without a valid bearer token', function () {
    $this->postJson('/api/v1/imports/manual-records', [
        'notaEP' => 'EP-401',
        'ordem' => 'ORDEM-401',
    ])->assertUnauthorized();
});

it('rejects fields outside the update command contract', function () {
    $token = manualImportApiToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/imports/manual-records', [
            'notaEP' => ['note' => 'EP-422', 'client' => 'Não permitido'],
            'ordem' => 'ORDEM-422',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Payload inválido.');
});
