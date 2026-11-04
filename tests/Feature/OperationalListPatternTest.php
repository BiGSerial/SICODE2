<?php

use App\Http\Livewire\Concerns\WithListFilters;
use App\Services\Payment\BlockEvaluator;
use App\Support\Ops\{Deadline, RowState};
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;

// Padrão de listas operacionais: docs/PADRAO_LISTAS_OPERACIONAIS.md

it('colore prazo restante por faixa: vencido vermelho, até 15 dias âmbar, acima verde', function () {
    expect(Deadline::remaining(null))->toBe('muted')
        ->and(Deadline::remaining(-1))->toBe('red')
        ->and(Deadline::remaining(0))->toBe('amber')
        ->and(Deadline::remaining(15))->toBe('amber')
        ->and(Deadline::remaining(16))->toBe('green')
        ->and(Deadline::remaining(4, 4))->toBe('amber')
        ->and(Deadline::remaining(5, 4))->toBe('green');
});

it('colore idade: até 14 dias verde, até 20 âmbar, acima vermelho', function () {
    expect(Deadline::age(null))->toBe('muted')
        ->and(Deadline::age(14))->toBe('green')
        ->and(Deadline::age(15))->toBe('amber')
        ->and(Deadline::age(20))->toBe('amber')
        ->and(Deadline::age(21))->toBe('red');
});

it('escreve o prazo em português', function () {
    expect(Deadline::remainingText(0))->toBe('Vence hoje')
        ->and(Deadline::remainingText(1))->toBe('Vence em 1 dia')
        ->and(Deadline::remainingText(-3))->toBe('Venceu há 3 dias')
        ->and(Deadline::ageText(0))->toBe('Hoje')
        ->and(Deadline::ageText(2))->toBe('Há 2 dias');
});

it('mapeia cada nível do BlockEvaluator para uma situação de linha com legenda', function () {
    expect(RowState::fromBlock(BlockEvaluator::FREE))->toBe(RowState::FREE)
        ->and(RowState::fromBlock(BlockEvaluator::HOLD_BLUE))->toBe(RowState::ASSIGNED)
        ->and(RowState::fromBlock(BlockEvaluator::HOLD_YELLOW))->toBe(RowState::STACK)
        ->and(RowState::fromBlock(BlockEvaluator::HOLD_GREEN))->toBe(RowState::DONE)
        ->and(RowState::fromBlock(BlockEvaluator::HOLD_RED))->toBe(RowState::LATE);

    foreach (RowState::all() as $state) {
        expect($state)->toHaveKeys(['label', 'tone', 'hint']);
    }
});

/** Componente mínimo com o trait: Região → Regional → Município. */
function listFiltersHarness(): object
{
    return new class () {
        use WithListFilters;

        public array $events = [];

        public array $selected = ['x'];

        public bool $selectall = true;

        public int $resets = 0;

        protected function listFilterDefinitions(): array
        {
            return [
                'region'   => ['label' => 'Região'],
                'regional' => ['label' => 'Regional', 'depends' => ['region']],
                'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
            ];
        }

        protected function listFilterOptions(): array
        {
            return [
                'region'   => [['v' => 'N', 'l' => 'Norte'], ['v' => 'S', 'l' => 'Sul']],
                'regional' => [['v' => 'R1', 'l' => 'Regional 1', 'p' => ['region' => 'N']], ['v' => 'R2', 'l' => 'Regional 2', 'p' => ['region' => 'S']]],
                'city'     => [
                    ['v' => 'C1', 'l' => 'Cidade 1', 'p' => ['region' => 'N', 'regional' => 'R1']],
                    ['v' => 'C2', 'l' => 'Cidade 2', 'p' => ['region' => 'S', 'regional' => 'R2']],
                ],
            ];
        }

        public function resetPage(): void
        {
            $this->resets++;
        }

        public function dispatchBrowserEvent(string $event, array $data = []): void
        {
            $this->events[] = [$event, $data];
        }
    };
}

it('descarta filtro desconhecido e valor que não existe nas opções', function () {
    $c = listFiltersHarness();
    $c->applyListFilters(['region' => ['N', 'ZZ'], 'hack' => ['1'], 'city' => ['C1']]);

    expect($c->filters)->toBe(['region' => ['N'], 'city' => ['C1']]);
});

it('aplica a cascata no servidor: filho fora do escopo do pai é removido', function () {
    $c = listFiltersHarness();
    $c->applyListFilters(['region' => ['N'], 'regional' => ['R2'], 'city' => ['C2']]);

    expect($c->filters)->toBe(['region' => ['N']]);
});

it('volta para a página 1, limpa a seleção em lote e avisa o navegador', function () {
    $c = listFiltersHarness();
    $c->applyListFilters(['region' => ['S']]);

    expect($c->resets)->toBe(1)
        ->and($c->selected)->toBe([])
        ->and($c->selectall)->toBeFalse()
        ->and($c->events[0][0])->toBe('ops-filters');
});

it('monta um chip por filtro: um valor mostra o nome, vários mostram a contagem', function () {
    $c = listFiltersHarness();
    $c->applyListFilters(['region' => ['N', 'S'], 'regional' => ['R1']]);

    expect($c->listFilterChips())->toBe([
        ['key' => 'region', 'label' => 'Região', 'value' => '2 selecionados'],
        ['key' => 'regional', 'label' => 'Regional', 'value' => 'Regional 1'],
    ]);
});

it('remover o filtro-pai libera o escopo dos filhos', function () {
    $c = listFiltersHarness();
    $c->applyListFilters(['region' => ['N'], 'regional' => ['R1']]);
    $c->removeListFilter('region');

    // Sem Região marcada, a Regional R1 continua válida (o escopo deixou de restringir).
    expect($c->filters)->toBe(['regional' => ['R1']]);

    $c->clearListFilters();
    expect($c->filters)->toBe([])->and($c->hasListFilters())->toBeFalse();
});

it('entrega o payload do painel de filtros com definições, opções e seleção', function () {
    $payload = listFiltersHarness()->listFilterPayload();

    expect($payload['defs'][1])->toBe(['key' => 'regional', 'label' => 'Regional', 'depends' => ['region']])
        ->and($payload['options'])->toHaveKeys(['region', 'regional', 'city']);
});

it('renderiza data com tom e hora, e traço quando não há data', function () {
    $html = Blade::render('<x-ops.date :date="$d" tone="red" time hint="Venceu" />', ['d' => Carbon::parse('2026-03-05 14:30:00')]);

    expect($html)->toContain('05/03/2026')->toContain('14:30')->toContain('tone-red')->toContain('is-alert')->toContain('title="Venceu"');
    expect(Blade::render('<x-ops.date :date="null" />'))->toContain('ops-empty');
    expect(Blade::render('<x-ops.date :date="$d" />', ['d' => Carbon::parse('2026-03-05')]))->not->toContain('is-alert');
});

it('renderiza valor em reais e colore saldo pelo sinal', function () {
    expect(Blade::render('<x-ops.money :value="1234.5" />'))->toContain('R$ 1.234,50');
    expect(Blade::render('<x-ops.money :value="-10" signed />'))->toContain('tone-red');
    expect(Blade::render('<x-ops.money :value="10" signed />'))->toContain('tone-green');
    expect(Blade::render('<x-ops.money :value="null" />'))->toContain('ops-empty');
});

it('renderiza selo com tom, ícone e dica', function () {
    $html = Blade::render('<x-ops.badge tone="amber" label="Parcial" icon="ri-user-line" hint="Informe parcial" sm />');

    expect($html)->toContain('ops-badge')->toContain('tone-amber')->toContain('ops-badge--sm')->toContain('ri-user-line')->toContain('title="Informe parcial"');
});

it('a lista de pagamento do despacho não carrega CSS nem JS inline', function () {
    $view = file_get_contents(resource_path('views/livewire/dispatchs/payment/main.blade.php'));

    expect($view)->not->toContain('<style')->not->toContain('<script')->not->toContain('components.filter.filter');
});

it('o CSS das listas operacionais não tem cor literal fora de _tokens.scss', function () {
    foreach (['_ops.scss', '_modal.scss'] as $file) {
        $scss = file_get_contents(resource_path('sass/ops/' . $file));

        expect(preg_match('/#[0-9a-fA-F]{3,8}\b|rgba?\(/', $scss))->toBe(0, "{$file} tem cor literal; use um token em _tokens.scss");
    }
});

it('as views das listas operacionais não têm cor nem cursor em style inline', function () {
    $views = [
        'livewire/dispatchs/payment/main.blade.php',
        'livewire/dispatchs/payment/stack.blade.php',
        'livewire/dispatchs/pagamento/partner_main.blade.php',
        'livewire/services/payment/main.blade.php',
        'livewire/services/payment/accompany/main.blade.php',
        'livewire/components/status/show-status.blade.php',
        'livewire/components/modal/priority.blade.php',
        'livewire/engineers/ads/status.blade.php',
    ];

    foreach ($views as $view) {
        expect(file_get_contents(resource_path('views/' . $view)))->not->toMatch('/style="[^"]*(color|cursor|background)/');
    }
});
