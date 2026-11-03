<?php

use App\Http\Livewire\Dispatchs\ReturnD5;
use App\Models\{Note, Production, Reclaim};
use App\Services\Reclaims\{ReclaimRowPresenter, ReturnD5Query};
use App\Support\Ops\RowState;
use Carbon\Carbon;
use Illuminate\Support\Collection;

// Lista de Retorno Interno (Despacho) no padrão de listas operacionais: docs/PADRAO_LISTAS_OPERACIONAIS.md

/** Retorno em memória, com as relações que o apresentador lê, para testar a regra sem banco. */
function reclaimRow(array $relations = [], array $attributes = [], ?Carbon $updatedAt = null): Reclaim
{
    $reclaim             = new Reclaim($attributes + ['category' => 'ALTERAR PROJETO']);
    $reclaim->created_at = now()->subDays(2);
    $reclaim->updated_at = $updatedAt ?? now()->subHour();

    $note = new Note(['note' => '4001922955', 'rubrica' => 'Mercado', 'lexp' => 'VITORIA', 'group5' => 'DSR', 'material' => 'LN INSTALACAO', 'pze' => '10']);
    $note->setRelation('Files', collect());

    $defaults = [
        'Note'        => $note,
        'Approvals'   => new Collection(),
        'Waiting'     => null,
        'Viabilities' => new Collection(),
        'Externals'   => new Collection(),
        'Comments'    => new Collection(),
        'Production'  => null,
    ];

    foreach ($relations + $defaults as $name => $value) {
        $reclaim->setRelation($name, $value);
    }

    return $reclaim;
}

it('dá a cada origem um tom semântico, sem classe Bootstrap', function () {
    expect(ReclaimRowPresenter::listRow(reclaimRow())['originTone'])->toBe('muted')
        ->and(ReclaimRowPresenter::listRow(reclaimRow(['Approvals' => collect([(object) ['User' => null]])]))['originTone'])->toBe('amber');

    $row = ReclaimRowPresenter::listRow(reclaimRow());

    expect(json_encode($row))->not->toContain('text-bg-');
});

it('retorno sem atividade é livre; com usuário é azul; com atividade e sem usuário é pilha da empresa', function () {
    // `user_id` é texto (uuid): `match (true)` compara estrito, e uma string verdadeira não é `true`. Esse teste guarda a conversão para booleano.
    $assigned = reclaimRow(['Production' => (new Production(['user_id' => '9a57fd76-26a2-4533-82ca-af55c7feccf4', 'status' => 1]))]);
    $stack    = reclaimRow(['Production' => (new Production(['user_id' => null, 'status' => 1]))]);

    expect(ReclaimRowPresenter::listRow(reclaimRow())['state'])->toBe(RowState::FREE)
        ->and(ReclaimRowPresenter::listRow($assigned)['state'])->toBe(RowState::ASSIGNED)
        ->and(ReclaimRowPresenter::listRow($stack)['state'])->toBe(RowState::STACK)
        ->and(ReclaimRowPresenter::listRow(reclaimRow())['hasActivity'])->toBeFalse()
        ->and(ReclaimRowPresenter::listRow($assigned)['hasActivity'])->toBeTrue();
});

it('marca em vermelho o retorno sem andamento há mais de 24 horas, e só ele', function () {
    $now = now();

    expect(ReclaimRowPresenter::listRow(reclaimRow(updatedAt: $now->copy()->subHours(25)), $now)['staleTone'])->toBe('red')
        ->and(ReclaimRowPresenter::listRow(reclaimRow(updatedAt: $now->copy()->subHours(23)), $now)['staleTone'])->toBe('muted')
        ->and(ReclaimRowPresenter::listRow(reclaimRow(updatedAt: $now->copy()->subHours(24)->addMinute()), $now)['staleTone'])->toBe('muted');
});

it('escreve o tempo de atividade em português e identifica nota expressa', function () {
    $row = ReclaimRowPresenter::listRow(reclaimRow());

    expect($row['activeFor'])->toBe('2 dias')->and($row['express'])->toBeFalse();

    $express            = reclaimRow();
    $express->Note->pze = '25';

    expect(ReclaimRowPresenter::listRow($express)['express'])->toBeTrue();
});

it('declara as quatro origens e só as colunas de ordenação conhecidas', function () {
    expect(array_keys(ReturnD5Query::ORIGINS))->toBe(['contract', 'project', 'viability', 'external'])
        ->and(ReturnD5Query::ORIGINS['viability'])->toBe(ReclaimRowPresenter::ORIGIN_VIABILITY)
        ->and(ReturnD5Query::SORTABLE)->not->toContain('id')
        ->and(ReturnD5Query::SORTABLE)->toContain('created_at', 'note');
});

it('não zera a seleção em lote quando o navegador envia as caixas marcadas, mas zera ao mudar a busca', function () {
    $component           = new ReturnD5();
    $component->selected = ['1', '2', '3'];

    $component->updated('selected');
    $component->updated('selected.0');

    expect($component->selected)->toBe(['1', '2', '3']);

    $component->updated('search');

    expect($component->selected)->toBe([]);
});

it('ignora ordenação por coluna desconhecida', function () {
    $component = new ReturnD5();
    $component->sortBy('id; DROP TABLE reclaims');

    expect($component->sortField)->toBe('created_at')->and($component->sortDirection)->toBe('asc');

    $component->sortBy('note');
    $component->sortBy('note');

    expect($component->sortField)->toBe('note')->and($component->sortDirection)->toBe('desc');
});

// ---------- Excel ----------

function exportWith(array $reclaims): array
{
    $export = new App\Exports\Dispatchs\ReturnD5Export(collect($reclaims), 'Desenho', 'Ana', [['Usuário', 'Ana']], now());
    $sheets = [];

    foreach ($export->sheets() as $sheet) {
        $sheets[$sheet->title()] = $sheet->array();
    }

    return $sheets;
}

function assignedReclaim(?Carbon $updatedAt = null, ?Carbon $createdAt = null): Reclaim
{
    $production = new Production(['user_id' => 'uuid-1', 'status' => 1]);
    $production->setRelation('User', new App\Models\User(['name' => 'Ana Souza']));
    $production->setRelation('Company', new App\Models\Company(['name' => 'Satel']));

    $reclaim             = reclaimRow(['Production' => $production], updatedAt: $updatedAt);
    $reclaim->created_at = $createdAt ?? now()->subDays(3)->setTime(13, 50);

    return $reclaim;
}

it('monta o Excel com as abas na ordem do padrão e o controle da exportação por último', function () {
    expect(array_keys(exportWith([reclaimRow()])))->toBe(['Resumo', 'Retornos', 'Por origem', 'Por responsável', 'Controle Exportacao']);
});

it('escreve uma linha por retorno, com data real do Excel e dias em aberto como número', function () {
    $rows = exportWith([assignedReclaim(), reclaimRow()])['Retornos'];

    // As quatro primeiras linhas são marca, descrição, espaço e cabeçalho; os dados vêm depois.
    expect($rows[3][0])->toBe('Origem')->and($rows[3][11])->toBe('Enviado em')->and($rows[3][12])->toBe('Dias em aberto')
        ->and(array_slice($rows, 4))->toHaveCount(2);

    [$first, $second] = array_slice($rows, 4);

    expect($first[11])->toBeFloat()                       // data como número serial do Excel, não texto
        ->and($first[12])->toBe(3)
        ->and($first[15])->toBe('Ana Souza')
        ->and($first[16])->toBe('Satel')
        ->and($second[15])->toBe('—');                    // sem atribuição: traço, nunca vazio
});

it('marca "sem andamento" só quando o retorno passa de 24 horas parado', function () {
    $rows = array_slice(exportWith([
        assignedReclaim(updatedAt: now()->subHours(30)),
        assignedReclaim(updatedAt: now()->subHours(2)),
    ])['Retornos'], 4);

    expect($rows[0][13])->toBe('Sim')->and($rows[1][13])->toBe('Não');
});

it('mostra sempre as quatro origens na aba Por origem, mesmo sem retornos em algumas', function () {
    $rows = array_slice(exportWith([reclaimRow()])['Por origem'], 4);

    expect(array_column($rows, 0))->toBe(array_values(ReturnD5Query::ORIGINS))
        ->and(array_column($rows, 1))->toBe([1, 0, 0, 0]);
});

it('o resumo separa sem atribuição, com usuário e pilha, e fecha o total', function () {
    $stack = reclaimRow(['Production' => new Production(['user_id' => null, 'status' => 1])]);
    $stack->Production->setRelation('User', null);
    $stack->Production->setRelation('Company', null);

    $rows = array_slice(exportWith([reclaimRow(), assignedReclaim(), assignedReclaim(), $stack])['Resumo'], 4);

    expect([$rows[0][0], $rows[1][0], $rows[2][0], $rows[3][0]])->toBe(['Sem atribuição', 'Com usuário', 'Na pilha da empresa', 'Total de retornos'])
        ->and([$rows[0][1], $rows[1][1], $rows[2][1], $rows[3][1]])->toBe([1, 2, 1, 4]);
});
