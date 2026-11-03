<?php

use App\Support\Ops\ControlAge;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;

// Padrão "data + dias" das telas de controle (stack): docs/PADRAO_LISTAS_OPERACIONAIS.md §13.

beforeEach(fn () => Carbon::setTestNow('2026-10-31 10:00:00'));

afterEach(fn () => Carbon::setTestNow());

function ageBadge(string $attributes, ?string $date): string
{
    return Blade::render('<x-ops.age :date="$date" ' . $attributes . ' />', ['date' => $date ? Carbon::parse($date) : null]);
}

it('colore o tempo desde o despacho: verde até 21 dias, âmbar até 30, vermelho acima', function (int $days, string $tone) {
    $html = ageBadge('rule="dispatch"', now()->subDays($days)->toDateString());

    expect($html)->toContain('tone-' . $tone)->and($html)->toContain($days . ' d');
})->with([[0, 'green'], [21, 'green'], [22, 'amber'], [30, 'amber'], [31, 'red'], [90, 'red']]);

it('colore o tempo desde a atribuição: verde até 7 dias, âmbar até 9, vermelho acima', function (int $days, string $tone) {
    expect(ageBadge('rule="assigned"', now()->subDays($days)->toDateString()))->toContain('tone-' . $tone);
})->with([[0, 'green'], [7, 'green'], [8, 'amber'], [9, 'amber'], [10, 'red']]);

it('colore o tempo desde o informe: verde até 5 dias, âmbar até 8, vermelho acima', function (int $days, string $tone) {
    expect(ageBadge('rule="informed"', now()->subDays($days)->toDateString()))->toContain('tone-' . $tone);
})->with([[5, 'green'], [6, 'amber'], [8, 'amber'], [9, 'red']]);

it('uma tela com faixa própria troca os limites, e sem regra nomeada vale a idade padrão do projeto', function () {
    $date = now()->subDays(12)->toDateString();

    expect(ageBadge('rule="assigned" :ok="12" :warn="15"', $date))->toContain('tone-green')
        ->and(ageBadge('rule="assigned"', $date))->toContain('tone-red')
        ->and(ageBadge('rule="qualquer-outra"', now()->subDays(14)->toDateString()))->toContain('tone-green')
        ->and(ageBadge('rule="qualquer-outra"', now()->subDays(21)->toDateString()))->toContain('tone-red');
});

it('mostra a data em dd/mm/aaaa em cima e os dias embaixo, e traço sem data', function () {
    $html = ageBadge('rule="dispatch"', '2026-10-21');

    expect($html)->toContain('21/10/2026')->and($html)->toContain('10 d')->and($html)->toContain('ops-date-day');

    $empty = ageBadge('rule="dispatch"', null);

    expect($empty)->toContain('ops-empty')->and($empty)->not->toContain('tone-')->and($empty)->not->toContain(' d<');
});

it('usa os dias que a tela já calculou, em vez de recalcular', function () {
    $html = Blade::render('<x-ops.age :date="$date" :days="3" rule="dispatch" />', ['date' => Carbon::parse('2026-01-01')]);

    expect($html)->toContain('3 d')->and($html)->toContain('tone-green');
});

it('conta dias corridos: zero hoje, negativo no futuro, nulo sem data', function () {
    expect(ControlAge::days(now()->setTime(23, 59)))->toBe(0)
        ->and(ControlAge::days(now()->subDay()->setTime(23, 59)))->toBe(1)
        ->and(ControlAge::days(now()->addDays(2)))->toBe(-2)
        ->and(ControlAge::days(null))->toBeNull();
});

it('o tooltip explica o que a data é e as faixas de cor, em português', function () {
    $hint = ControlAge::hint(5, ControlAge::rule('dispatch'));

    expect($hint)->toContain('do despacho')->and($hint)->toContain('Verde até 21 dias')->and($hint)->toContain('âmbar até 30')->and($hint)->toContain('vermelho acima');
    expect(ControlAge::hint(null, ControlAge::rule('assigned')))->toContain('da atribuição')->and(ControlAge::hint(null, ControlAge::rule('assigned')))->toContain('Verde até 7 dias');
});

it('todo selo de data de uma tela de controle tem a cor do prazo e os dias embaixo (nunca só a hora)', function () {
    $views = array_merge(
        glob(resource_path('views/livewire/dispatchs/*/stack.blade.php')),
        [resource_path('views/livewire/dispatchs/pagamento/partner_stack.blade.php')],
    );

    // `stack2` é a tela legada do Levantamento, à parte do padrão.
    $views = array_filter($views, fn (string $v) => !str_contains($v, 'stack2'));

    expect(count($views))->toBeGreaterThanOrEqual(10);

    foreach ($views as $view) {
        preg_match_all('/<x-ops\.date\b.*?\/>/s', file_get_contents($view), $tags);

        foreach ($tags[0] as $tag) {
            $where = basename(dirname($view)) . '/' . basename($view) . ': ' . substr(preg_replace('/\s+/', ' ', $tag), 0, 80);

            expect(str_contains($tag, ':tone='))->toBeTrue("selo de data sem cor do prazo em {$where}")
                ->and(str_contains($tag, ':sub='))->toBeTrue("selo de data sem dias embaixo em {$where}")
                ->and(preg_match('/\stime\b/', $tag))->toBe(0, "selo de data mostrando hora em vez de dias em {$where}");
        }
    }
});

it('a ADS e o status da nota seguem a idade padrão do projeto (14 e 20 dias)', function (string $rule, int $days, string $tone) {
    expect(ageBadge('rule="' . $rule . '"', now()->subDays($days)->toDateString()))->toContain('tone-' . $tone);
})->with([['ads', 14, 'green'], ['ads', 15, 'amber'], ['ads', 20, 'amber'], ['ads', 21, 'red'], ['status', 14, 'green'], ['status', 21, 'red']]);

it('as telas de controle sem cor própria de despacho e atribuição usam o componente padrão', function () {
    foreach (['analises', 'analises-pre', 'default', 'desenho', 'publication', 'reverse', 'payment'] as $folder) {
        $html = file_get_contents(resource_path("views/livewire/dispatchs/{$folder}/stack.blade.php"));

        expect(substr_count($html, '<x-ops.age'))->toBeGreaterThanOrEqual(2, "{$folder}/stack deve ter despacho e atribuição em <x-ops.age>")
            ->and($html)->not->toContain('daysSince');
    }
});

it('escreve o prazo que vence em português, sem dias negativos', function () {
    expect(ControlAge::remainingLabel(12))->toBe('12 d')
        ->and(ControlAge::remainingLabel(0))->toBe('0 d')
        ->and(ControlAge::remainingLabel(-1))->toBe('1 dia vencido')
        ->and(ControlAge::remainingLabel(-1038))->toBe('1038 dias vencidos')
        ->and(ControlAge::remainingLabel(null))->toBeNull();
});
