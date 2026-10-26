<?php

use Carbon\Carbon;

// Componentes do padrão de UI do Encerramento (docs/encerramento/PADRAO_UI_ENCERRAMENTO.md §5).
// Renderização pura: não toca no banco.

beforeEach(fn () => Carbon::setTestNow('2026-10-23 10:00:00'));
afterEach(fn () => Carbon::setTestNow());

it('renders a stat card as a pressed-aware filter button with tone and note', function () {
    $html = (string) $this->blade('<x-closure.stat-card label="Para retorno" :value="7" tone="green" icon="ri-inbox-line" note="aguardando" :active="true" wire:click="setTab(\'return\')" />');

    expect($html)->toContain('kpi tone-green is-active')
        ->and($html)->toContain('aria-pressed="true"')
        ->and($html)->toContain('wire:click="setTab(\'return\')"')
        ->and($html)->toContain('Para retorno')
        ->and($html)->toContain('aguardando');
});

it('renders inbox tabs with counts and marks the active one', function () {
    $html = (string) $this->blade(
        '<x-closure.inbox-tabs :tabs="$tabs" active="watch" />',
        ['tabs' => [
            ['key' => 'return', 'label' => 'Para retorno', 'count' => 3, 'tone' => 'green'],
            ['key' => 'watch', 'label' => 'Em acompanhamento', 'count' => 12],
        ]]
    );

    expect($html)->toContain("wire:click=\"setTab('return')\"")
        ->and($html)->toContain('Para retorno')
        ->and($html)->toContain('>12<')
        ->and($html)->toContain('is-active')
        ->and(substr_count($html, 'aria-selected="true"'))->toBe(1);
});

it('renders nothing for filter chips without active filters and the chips with them', function () {
    expect(trim((string) $this->blade('<x-closure.filter-chips :filters="[]" />')))->toBe('');

    $html = (string) $this->blade('<x-closure.filter-chips :filters="$f" />', ['f' => [['key' => 'search', 'label' => 'Busca', 'value' => '123']]]);

    expect($html)->toContain('Busca:')
        ->and($html)->toContain('123')
        ->and($html)->toContain("wire:click=\"removeFilter('search')\"")
        ->and($html)->toContain('wire:click="clearFilters"')
        ->and($html)->not->toContain('style=');
});

it('shows elapsed calendar days, flags estimated starts and colors only when thresholds are given', function () {
    $plain = (string) $this->blade('<x-closure.age-chip :since="$s" />', ['s' => '2026-10-12 08:00:00']);
    expect($plain)->toContain('há 11 dias')->and($plain)->toContain('tone-muted')->and($plain)->toContain('12/10/2026 08:00');

    expect((string) $this->blade('<x-closure.age-chip :since="$s" />', ['s' => '2026-10-23 07:00:00']))->toContain('hoje');
    expect((string) $this->blade('<x-closure.age-chip :since="$s" />', ['s' => '2026-10-22 07:00:00']))->toContain('há 1 dia')->not->toContain('há 1 dias');

    $estimated = (string) $this->blade('<x-closure.age-chip :since="$s" :estimated="true" />', ['s' => '2026-10-20']);
    expect($estimated)->toContain('desde o 1º registro');

    $red = (string) $this->blade('<x-closure.age-chip :since="$s" :warn-after="5" :danger-after="10" />', ['s' => '2026-10-12']);
    expect($red)->toContain('tone-red');

    $amber = (string) $this->blade('<x-closure.age-chip :since="$s" :warn-after="5" :danger-after="20" />', ['s' => '2026-10-12']);
    expect($amber)->toContain('tone-amber');

    expect((string) $this->blade('<x-closure.age-chip :since="null" />'))->toContain('—');
});

it('renders status and lock badges with the reason', function () {
    expect((string) $this->blade('<x-closure.status-badge label="Retornado" tone="green" />'))->toContain('status-badge tone-green')->toContain('Retornado');

    $lock = (string) $this->blade('<x-closure.lock-badge reason="RI com o Desenho" since="2026-10-20" />');
    expect($lock)->toContain('lock-badge')
        ->and($lock)->toContain('title="RI com o Desenho"')
        ->and($lock)->toContain('Travada · RI com o Desenho')
        ->and($lock)->toContain('há 3 dias');
});

it('renders a timeline with dates, authors, durations and notes', function () {
    $html = (string) $this->blade('<x-closure.timeline :items="$i" />', ['i' => [
        ['label' => 'Enviado', 'at' => '2026-10-20 09:30:00', 'by' => 'Ana', 'duration' => '3 dias', 'tone' => 'blue', 'note' => 'Faltou o as-built'],
        ['label' => 'Retornado', 'tone' => 'green'],
    ]]);

    expect($html)->toContain('ui-timeline')
        ->and($html)->toContain('20/10/2026 09:30')
        ->and($html)->toContain('· Ana')
        ->and($html)->toContain('3 dias')
        ->and($html)->toContain('Faltou o as-built')
        ->and($html)->toContain('tone-green');
});

it('renders the drawer, empty state and form field with validation error', function () {
    $drawer = (string) $this->blade('<x-closure.drawer id="inbox-detail" title="Detalhe">conteudo</x-closure.drawer>');
    expect($drawer)->toContain('id="inbox-detail"')->toContain('offcanvas-end')->toContain('Detalhe')->toContain('conteudo')->toContain('data-bs-dismiss="offcanvas"');

    $empty = (string) $this->blade('<x-closure.empty-state title="Nada por aqui" text="Sem itens" />');
    expect($empty)->toContain('dash-empty')->toContain('Nada por aqui')->toContain('Sem itens');

    $errors = new Illuminate\Support\ViewErrorBag();
    $errors->put('default', new Illuminate\Support\MessageBag(['reason' => ['Informe o motivo.']]));
    view()->share('errors', $errors);

    $field = (string) $this->blade('<x-closure.form-field label="Motivo" name="reason" hint="Obrigatório" :required="true"><textarea id="reason"></textarea></x-closure.form-field>');
    expect($field)->toContain('Motivo')->toContain('ui-required')->toContain('Obrigatório')->toContain('Informe o motivo.')->toContain('role="alert"');
});

it('disables an action button with the reason as tooltip', function () {
    $off = (string) $this->blade('<x-closure.action-button label="Iniciar" :disabled="true" reason="Ordem travada por RI" wire:click="start" />');
    expect($off)->toContain('title="Ordem travada por RI"')->toContain('disabled')->toContain('is-disabled');

    $on = (string) $this->blade('<x-closure.action-button label="Responder" icon="ri-send-plane-line" wire:click="reply" />');
    expect($on)->toContain('btn-success')->toContain('wire:click="reply"')->not->toContain('is-disabled');
});

it('keeps Blade components and the new partner screens free of inline styles and scripts', function () {
    $files = array_merge(
        glob(resource_path('views/components/closure/*.blade.php')),
        [
            resource_path('views/livewire/partner/closure-issues/detail.blade.php'),
            resource_path('views/livewire/partner/closure-issues/history.blade.php'),
            resource_path('views/partner/closure-issue.blade.php'),
            resource_path('views/partner/closure-history.blade.php'),
        ],
    );

    foreach ($files as $file) {
        $source = file_get_contents($file);

        expect($source)->not->toContain('style="', basename($file) . ' tem style inline')
            ->and($source)->not->toContain('<script', basename($file) . ' tem script inline')
            ->and($source)->not->toContain('<style', basename($file) . ' tem style inline');
    }
});
