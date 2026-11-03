<?php

use App\Http\Livewire\Dispatchs\Comission\{Main, PartnerMain, PartnerStack, Stack};
use App\Http\Livewire\Services\Comission\Main as ServiceMain;
use App\Models\{Note, Production, Service, User};
use App\Services\Comission\ComissionListRowPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Listas do Comissionamento no padrão de listas operacionais: docs/PADRAO_LISTAS_OPERACIONAIS.md

function comissionService(): Service
{
    return Service::create(['service' => 'Comissionamento', 'folder' => 'comissionamento']);
}

function comissionNote(string $number, array $extra = []): Note
{
    return Note::create($extra + [
        'note'      => $number,
        'dt_status' => now(),
        'nstats'    => 'NEW',
        'rubrica'   => 'RUB-A',
        'type_note' => 1,
        'days_left' => 12,
    ]);
}

function comissionProduction(Note $note, Service $service, User $user, array $extra = []): Production
{
    return Production::create($extra + [
        'company_id'  => \App\Models\Company::query()->firstOrCreate(['name' => 'Empresa de teste'], ['email' => 'teste@example.com'])->id,
        'note_id'     => $note->id,
        'service_id'  => $service->uuid,
        'user_id'     => $user->id,
        'dispatch_by' => $user->id,
        'dt_note'     => $note->dt_status,
        'status_note' => $note->nstats,
        'dispatch_at' => now(),
        'att_at'      => now(),
        'status'      => 2,
    ]);
}

it('as views de Comissionamento não carregam CSS, JS nem cor inline, nem os filtros legados', function () {
    $views = [
        'livewire/dispatchs/comission/main.blade.php',
        'livewire/dispatchs/comission/stack.blade.php',
        'livewire/dispatchs/comissionamento/partner_main.blade.php',
        'livewire/dispatchs/comissionamento/partner_stack.blade.php',
        'livewire/services/comission/main.blade.php',
    ];

    foreach ($views as $view) {
        $html = file_get_contents(resource_path('views/' . $view));

        expect($html)
            ->not->toContain('<style')
            ->not->toContain('<script')
            ->not->toContain('components.filter.filter')
            ->not->toContain('.bounce.')
            ->not->toMatch('/text-bg-|table-(danger|warning|success|primary|striped|dark)|style="[^"]*(color|cursor|background)/')
            ->not->toMatch('/#[0-9a-fA-F]{3,8}\b|rgba?\(/');
    }
});

it('as telas da parceira renderizam a view que existe (antes apontavam para um arquivo ausente)', function () {
    expect(view()->exists('livewire.dispatchs.comissionamento.partner_main'))->toBeTrue()
        ->and(view()->exists('livewire.dispatchs.comissionamento.partner_stack'))->toBeTrue();
});

it('cor do prazo real: vermelho abaixo de 6 dias ou vencido, âmbar de 6 a 9, verde com 10 ou mais', function () {
    $presenter = new ComissionListRowPresenter();

    expect($presenter->dueTone(-3))->toBe('red')
        ->and($presenter->dueTone(0))->toBe('red')
        ->and($presenter->dueTone(5))->toBe('red')
        ->and($presenter->dueTone(6))->toBe('amber')
        ->and($presenter->dueTone(9))->toBe('amber')
        ->and($presenter->dueTone(10))->toBe('green');
});

it('lista de despacho: renderiza, filtra por rubrica e limpa os critérios', function () {
    $user    = User::factory()->create(['contract' => false]);
    $service = comissionService();
    comissionNote('4000000201', ['rubrica' => 'RUB-A']);
    comissionNote('4000000202', ['rubrica' => 'RUB-B']);

    $this->actingAs($user);

    $component = Livewire::test(Main::class, ['service' => $service->uuid])
        ->assertSee('4000000201')
        ->assertSee('4000000202')
        ->assertSee('Lista para comissionamento');

    $component->call('applyListFilters', ['rubrica' => ['RUB-A']]);
    expect($component->instance()->getListsProperty()->pluck('note')->all())->toContain('4000000201');

    $component->set('search', '4000000202')->assertSee('4000000202');
    $component->call('clearAllCriteria');
    expect($component->get('filters'))->toBe([])->and($component->get('search'))->toBe('');

    $component->set('advanceSearch', "4000000201\n4000000299")->call('buscarMulti');
    expect($component->get('multiSearch'))->toBe(['4000000201', '4000000299']);

    $component->call('go_att_mass')->assertDispatchedBrowserEvent('swal');
});

it('lista de despacho: nota com atividade aberta vira linha de situação e não pode ser selecionada', function () {
    $user    = User::factory()->create(['contract' => false]);
    $service = comissionService();
    $note    = comissionNote('4000000203');
    comissionProduction($note, $service, $user, ['status' => 2]);
    $free = comissionNote('4000000204');

    $this->actingAs($user);

    $component = Livewire::test(Main::class, ['service' => $service->uuid])->assertSee('ops-row-assigned', false);

    $component->call('setSelectAll');
    expect($component->get('selected'))->toBe([(string) $free->id]);

    $component->call('setSelectAll');
    expect($component->get('selected'))->toBe([]);
});

it('lista da parceira: renderiza e reaproveita a mesma lista', function () {
    $user    = User::factory()->create(['contract' => false]);
    $service = comissionService();
    comissionNote('4000000205');

    $this->actingAs($user);

    Livewire::test(PartnerMain::class, ['service' => $service->uuid])
        ->assertSee('4000000205')
        ->assertSee('Parceira');
});

it('controle: renderiza, filtra por status, busca e limpa', function () {
    $user    = User::factory()->create(['contract' => false]);
    $service = comissionService();
    $note    = comissionNote('4000000206', ['dt_created' => now()->subDays(25)]);
    comissionProduction($note, $service, $user, ['priority' => true]);

    $this->actingAs($user);

    foreach ([Stack::class, PartnerStack::class] as $class) {
        $component = Livewire::test($class, ['service' => $service->uuid])
            ->assertSee('4000000206')
            ->assertSee('ops-row-priority', false);

        $component->call('toggleStatus', 2);
        expect($component->get('statusFilter'))->toBe(2);

        $component->call('toggleStatus', 4);
        expect($component->instance()->getListsProperty()->total())->toBe(0);

        $component->call('clearAllCriteria');
        expect($component->get('statusFilter'))->toBeNull()->and($component->instance()->getListsProperty()->total())->toBe(1);

        $component->set('search', '4000000206');
        expect($component->instance()->getListsProperty()->total())->toBe(1);
    }
});

it('serviços: mostra só as atividades abertas do próprio usuário e filtra por tipo', function () {
    $user    = User::factory()->create(['contract' => false]);
    $other   = User::factory()->create(['contract' => false]);
    $service = comissionService();
    $mine    = comissionNote('4000000207');
    $ov      = comissionNote('4000000208', ['type_note' => 2]);
    $theirs  = comissionNote('4000000209');
    comissionProduction($mine, $service, $user);
    comissionProduction($ov, $service, $user);
    comissionProduction($theirs, $service, $other);

    $this->actingAs($user);

    $component = Livewire::test(ServiceMain::class, ['service' => $service->uuid])
        ->assertSee('4000000207')
        ->assertSee('4000000208')
        ->assertDontSee('4000000209');

    $component->set('note_type', '2')->assertSee('4000000208')->assertDontSee('4000000207');

    // A busca não escapa do recorte do usuário (antes o OR da busca por material vazava as atividades dos outros).
    $component->call('clearAllCriteria')->set('search', 'ZZZ')->assertDontSee('4000000209');
});
