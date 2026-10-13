<?php

use App\Models\{Note, Order, WorkReport};
use App\Services\Partner\PartialScopeAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('blocks only the scope covered by an active final report', function () {
    config(['sicode.ruleset' => 'sp']);

    $note = Note::query()->create([
        'note'      => '16696473',
        'nstats'    => 51,
        'type_note' => 1,
    ]);

    $networkOrder = Order::query()->create([
        'note_id'    => $note->id,
        'ordem'      => '170000000001',
        'statusSist' => 'ABER',
    ]);

    $connectionOrder = Order::query()->create([
        'note_id'    => $note->id,
        'ordem'      => '180000000001',
        'statusSist' => 'ABER',
    ]);

    $workReport = WorkReport::query()->create([
        'note_id'               => $note->id,
        'canceled'              => false,
        'selected_final_scopes' => ['network'],
    ]);
    $workReport->Orders()->attach($networkOrder->id);

    $rows = collect(app(PartialScopeAvailabilityService::class)->for($note->fresh()));

    expect($rows->firstWhere('scope', 'network'))
        ->toMatchArray([
            'blocked' => true,
            'reason'  => 'Informe de conclusão já emitido para este escopo',
        ])
        ->and($rows->firstWhere('scope', 'connection'))
        ->toMatchArray([
            'blocked' => false,
            'reason'  => null,
        ]);
});
