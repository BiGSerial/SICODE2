<?php

use App\Models\{HistoricNote, Note};
use Illuminate\Support\Facades\DB;
use App\Services\Notes\OvStatusHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ovRow(array $o = []): object
{
    return (object) array_merge([
        'OV'               => '9001',
        'idNum'            => 1,
        'numStat'          => 10,
        'dtStat'           => '2026-03-01',
        'dhStat'           => '2026-03-01 08:30:00',
        'considerarPrazo'  => 'Sim',
        'PzE'              => 5,
        'status'           => 'Aberta',
        'transicaoUsuario' => 'U1',
    ], $o);
}

beforeEach(function () {
    $this->note    = Note::create(['note' => '9001', 'type_note' => 2]);
    $this->service = app(OvStatusHistoryService::class);
});

it('é idempotente e não duplica eventos', function () {
    $rows = [ovRow(), ovRow(['idNum' => 2, 'numStat' => 50, 'dhStat' => '2026-03-02 09:00:00'])];

    $this->service->upsertFromSource($rows, ['9001' => $this->note->id]);
    $this->service->upsertFromSource($rows, ['9001' => $this->note->id]);

    expect(HistoricNote::count())->toBe(2);
});

it('status repetido em outra data/hora gera linhas distintas', function () {
    $this->service->upsertFromSource([
        ovRow(['idNum' => 1, 'numStat' => 10]),
        ovRow(['idNum' => 2, 'numStat' => 50, 'dhStat' => '2026-03-02 09:00:00']),
        ovRow(['idNum' => 3, 'numStat' => 10, 'dtStat' => '2026-03-05', 'dhStat' => '2026-03-05 10:00:00']),
    ], ['9001' => $this->note->id]);

    expect(HistoricNote::where('note_id', $this->note->id)->count())->toBe(3);
});

it('o mesmo status na mesma data/hora é um evento só, mesmo renumerado na origem', function () {
    $this->service->upsertFromSource([ovRow(['idNum' => 1, 'numStat' => 51, 'transicaoUsuario' => null])], ['9001' => $this->note->id]);

    // A origem renumerou o evento (id_num mudou): não pode virar uma segunda linha.
    $this->service->upsertFromSource([ovRow(['idNum' => 7, 'numStat' => 51, 'transicaoUsuario' => null])], ['9001' => $this->note->id]);

    $h = HistoricNote::sole();
    expect($h->id_num)->toBe(7)->and($h->num_stat)->toBe(51);
});

it('duplicata no mesmo lote mantém o usuário da transição', function () {
    $this->service->upsertFromSource([
        ovRow(['idNum' => 4, 'numStat' => 30, 'transicaoUsuario' => '10204671']),
        ovRow(['idNum' => 5, 'numStat' => 30, 'transicaoUsuario' => null]),
    ], ['9001' => $this->note->id]);

    $h = HistoricNote::sole();
    expect($h->transicao_usuario)->toBe('10204671')->and($h->id_num)->toBe(5);
});

it('atualiza prazo do mesmo evento sem duplicar', function () {
    $this->service->upsertFromSource([ovRow()], ['9001' => $this->note->id]);
    $this->service->upsertFromSource([ovRow(['PzE' => 9, 'considerarPrazo' => 'Expurgar'])], ['9001' => $this->note->id]);

    $h = HistoricNote::sole();
    expect($h->pze)->toBe(9)->and($h->considerar_prazo)->toBe('Expurgar');
});

it('ignora OV sem nota e linhas inválidas', function () {
    $result = $this->service->upsertFromSource([
        ovRow(['OV' => '7777']),
        ovRow(['idNum' => null]),
        ovRow(['dtStat' => null, 'dhStat' => null]),
    ], ['9001' => $this->note->id]);

    expect($result)->toBe(['written' => 0, 'skipped' => 3])
        ->and(HistoricNote::count())->toBe(0);
});

it('concatena dtStat com a hora quando dhStat vem sem data', function () {
    $this->service->upsertFromSource([ovRow(['dhStat' => '08:30:00'])], ['9001' => $this->note->id]);

    expect(HistoricNote::sole()->dt_stats->format('Y-m-d H:i:s'))->toBe('2026-03-01 08:30:00');
});

it('resolve note_id apenas de notas type_note 2', function () {
    Note::create(['note' => '9002', 'type_note' => 1]);

    expect($this->service->noteIdsByOv(['9001', '9002']))->toBe(['9001' => $this->note->id]);
});

it('a migration remove as duplicatas antigas, guarda a linha com usuário e troca a chave', function () {
    $migration = require base_path('database/migrations/2026_10_30_150000_dedupe_historic_notes_by_event_date.php');

    // Volta ao esquema antigo (chave com id_num) e recria o cenário da tela: mesmo status e data, id_num diferente.
    $migration->down();

    $event = ['note_id' => $this->note->id, 'num_stat' => 30, 'dt_stats' => '2026-10-28 10:11:21', 'created_at' => now(), 'updated_at' => now()];

    DB::table('historic_notes')->insert([
        $event + ['id_num' => 10, 'status' => 'Retorno Ent. Externa', 'transicao_usuario' => null],
        $event + ['id_num' => 11, 'status' => 'Retorno Ent. Externa', 'transicao_usuario' => '10204671'],
        $event + ['id_num' => 12, 'status' => 'Retorno Ent. Externa', 'transicao_usuario' => null],
        // Evento distinto (outra data) e outra nota não podem ser tocados.
        array_replace($event, ['dt_stats' => '2026-10-29 07:47:51']) + ['id_num' => 13, 'status' => 'Em Execução', 'transicao_usuario' => null],
    ]);

    $migration->up();

    $rows = HistoricNote::orderBy('dt_stats')->get();
    expect($rows)->toHaveCount(2)
        ->and($rows[0]->transicao_usuario)->toBe('10204671')
        ->and($rows[1]->status)->toBe('Em Execução');

    // A nova chave barra a repetição na própria tabela.
    expect(fn () => DB::table('historic_notes')->insert($event + ['id_num' => 99, 'status' => 'x']))->toThrow(\Illuminate\Database\QueryException::class);
});
