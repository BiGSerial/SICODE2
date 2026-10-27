<?php

use App\Models\{HistoricNote, Note};
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

it('status repetido com id_num diferente gera linhas distintas', function () {
    $this->service->upsertFromSource([
        ovRow(['idNum' => 1, 'numStat' => 10]),
        ovRow(['idNum' => 2, 'numStat' => 50]),
        ovRow(['idNum' => 3, 'numStat' => 10]),
    ], ['9001' => $this->note->id]);

    expect(HistoricNote::where('note_id', $this->note->id)->count())->toBe(3);
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
