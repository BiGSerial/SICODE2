<?php

use App\Services\Partner\PartnerDashboardCharts;

function partnerChartsFixture(): array
{
    $executive = [
        'labels'          => ['jan', 'fev', 'mar'],
        'informs_valid'   => [5, 3, 0],
        'informs_invalid' => [1, 0, 0],
        'aging'           => [['label' => '1–3 dias', 'total' => 2], ['label' => '4–7 dias', 'total' => 0], ['label' => '8–15 dias', 'total' => 1], ['label' => '16–30 dias', 'total' => 0], ['label' => 'Acima de 30 dias', 'total' => 4]],
        'due_by_day'      => [['label' => 'Hoje', 'ads' => 1, 'viability' => 2], ['label' => 'Amanhã', 'ads' => 0, 'viability' => 0]],
    ];

    return PartnerDashboardCharts::all(
        $executive,
        ['labels' => ['0', '1', '21+'], 'data' => [1, 0, 3]],
        ['labels' => ['0', '1', '30+'], 'data' => [2, 0, 1], 'rejected_data' => [0, 1, 0]],
        ['labels' => ['Sem foto', 'Sem PEP'], 'data' => [4, 2]],
    );
}

it('entrega os seis gráficos do painel com datasets fixos por gráfico', function () {
    $charts = partnerChartsFixture();

    expect(array_keys($charts))->toBe(['aging', 'due', 'informs', 'viabilityAge', 'd5Age', 'reasons']);

    $datasets = array_map(fn ($c) => count($c['data']['datasets']), $charts);

    expect($datasets)->toBe(['aging' => 1, 'due' => 2, 'informs' => 2, 'viabilityAge' => 1, 'd5Age' => 2, 'reasons' => 1]);
});

it('escreve os valores pelo plugin mpBarLabels, sem datalabels nem placeholders do Apex', function () {
    foreach (partnerChartsFixture() as $name => $chart) {
        expect($chart['options']['plugins'])->toHaveKey('mpBarLabels')
            ->and(json_encode($chart))->not->toContain('__VALUE_LABEL')->not->toContain('datalabels');
    }
});

it('usa barras horizontais com o valor à direita nas faixas de atraso e nos motivos', function () {
    $charts = partnerChartsFixture();

    expect($charts['aging']['options']['indexAxis'])->toBe('y')
        ->and($charts['reasons']['options']['indexAxis'])->toBe('y')
        ->and($charts['aging']['options']['plugins']['mpBarLabels']['outside'])->toBeTrue()
        ->and($charts['aging']['data']['datasets'][0]['backgroundColor'])->toHaveCount(5)
        ->and($charts['aging']['data']['datasets'][0]['data'])->toBe([2, 0, 1, 0, 4]);
});

it('empilha o que soma e mostra o total de cada pilha', function () {
    $charts = partnerChartsFixture();

    foreach (['due', 'informs', 'd5Age'] as $name) {
        expect($charts[$name]['options']['plugins']['mpBarLabels']['totals'])->toBeTrue()
            ->and($charts[$name]['options']['scales']['x']['stacked'])->toBeTrue();
    }

    expect($charts['informs']['data']['datasets'][1]['data'])->toBe([1, 0, 0]);
});

it('mantém a mesma estrutura sem dados, para o gráfico não ser recriado', function () {
    $empty = PartnerDashboardCharts::all([], [], [], []);

    expect(array_keys($empty))->toBe(['aging', 'due', 'informs', 'viabilityAge', 'd5Age', 'reasons'])
        ->and(count($empty['d5Age']['data']['datasets']))->toBe(2)
        ->and($empty['reasons']['data']['labels'])->toBe([]);
});

it('a view não tem CSS/JS inline nem usa o componente Apex', function () {
    $view = file_get_contents(resource_path('views/livewire/partner/main.blade.php'));

    expect($view)->not->toContain('<style')->not->toContain('x-grafico.apex')->not->toContain('pd-hero')
        ->and($view)->toContain('data-my-panel-chart')->toContain('x-home.hero-card')->toContain('x-ops.date');
});
