<?php

use App\Http\Livewire\Partner\{Histviab, TacitList, Todoviability, ViabRejectedList};
use App\Models\{Bancoupdate, Company, Note, Order, PartnerCompanyPermissionGrant, User, Viability};
use App\Services\PartnerAccess\PartnerPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function plvCompany(array $attributes = []): Company
{
    return Company::query()->create(array_merge(['name' => 'Empreiteira ' . fake()->unique()->word(), 'email' => fake()->unique()->safeEmail()], $attributes));
}

function plvUser(Company $company): User
{
    return User::factory()->create([
        'company_id' => $company->id, 'superadm' => false, 'admin' => false, 'management' => false, 'operator' => false, 'user' => false,
        'contract'   => false, 'first_pass' => false, 'bypassprod' => false, 'engineer' => false, 'onlyparner' => true, 'responsible' => false,
        'btzero'     => false, 'can_dispatch' => false, 'analyst' => false, 'legal_controller' => false, 'legal_field' => false, 'legal_manager' => false,
    ]);
}

function plvGrant(Company $company, array $enabled): void
{
    foreach (PartnerPermissionCatalog::allPermissionKeys() as $key) {
        PartnerCompanyPermissionGrant::query()->create([
            'company_id' => $company->id, 'permission_key' => $key,
            'scope_type' => array_key_exists($key, PartnerPermissionCatalog::groups()) ? 'group' : 'item', 'enabled' => in_array($key, $enabled, true),
        ]);
    }
}

function plvNote(string $number, array $attributes = []): Note
{
    $note = Note::query()->create(array_merge(['note' => $number, 'lexp' => 'Campinas', 'nexp' => 'Campinas', 'rubrica' => 'EXP'], $attributes));
    Order::query()->create(['note_id' => $note->id, 'ordem' => 'OV' . $number]);

    return $note;
}

function plvViability(Company $company, User $user, Note $note, array $attributes = []): Viability
{
    $order = Order::query()->where('note_id', $note->id)->first();

    return Viability::query()->create(array_merge([
        'order_id'  => $order->id, 'company_id' => $company->id, 'user_id' => $user->id, 'note_id' => $note->id,
        'sended_at' => now()->subDays(3), 'status' => 1,
    ], $attributes));
}

const PLV_ALL = ['portal', 'viability', 'portal.access', 'viability.list', 'viability.history', 'viability.rejected', 'viability.tacit', 'viability.respond', 'viability.return', 'viability.export', 'viability.view_files'];

beforeEach(function () {
    Bancoupdate::query()->create(['last_update' => now()]);
    $this->company = plvCompany();
    $this->other   = plvCompany();
    $this->user    = plvUser($this->company);
    $this->foreign = plvUser($this->other);
    plvGrant($this->company, PLV_ALL);
    plvGrant($this->other, PLV_ALL);
});

// ---------------------------------------------------------------- ViabRejectedList (duas abas)

it('renders the rejected list with two tabs and counts', function () {
    Livewire::actingAs($this->user)->test(ViabRejectedList::class)->assertOk()->assertSee('Nada aguardando a sua resposta');

    plvViability($this->company, $this->user, plvNote('900001'), ['rejected' => true, 'status' => 5, 'returned_at' => now()]);
    plvViability($this->company, $this->user, plvNote('900002'), ['rejected' => true, 'status' => 7, 'returned_at' => now()]);
    plvViability($this->other, $this->foreign, plvNote('900003'), ['rejected' => true, 'status' => 5]);

    Livewire::actingAs($this->user)->test(ViabRejectedList::class)
        ->assertSet('tab', 'awaiting')
        ->assertSee('900001')->assertDontSee('900002')->assertDontSee('900003')
        ->assertSee('Aguardando resposta (1)')->assertSee('Em tratativa (1)')
        ->set('tab', 'treating')->assertSee('900002')->assertDontSee('900001')
        ->set('tab', 'invalido')->assertSet('tab', 'awaiting');
});

it('searches, filters and clears the rejected list', function () {
    plvViability($this->company, $this->user, plvNote('910001', ['rubrica' => 'AAA']), ['rejected' => true, 'status' => 5]);
    plvViability($this->company, $this->user, plvNote('910002', ['rubrica' => 'BBB']), ['rejected' => true, 'status' => 5]);

    Livewire::actingAs($this->user)->test(ViabRejectedList::class)
        ->set('search', '910001')->assertSee('910001')->assertDontSee('910002')
        ->call('clearAllCriteria')->assertSee('910002')
        ->call('applyListFilters', ['rubrica' => ['AAA']])->assertSee('910001')->assertDontSee('910002')
        ->assertSet('filters', ['rubrica' => ['AAA']])
        ->call('clearAllCriteria')->assertSet('filters', [])->assertSee('910002');
});

it('keeps the respond action wired to the response modal', function () {
    $viab = plvViability($this->company, $this->user, plvNote('920001'), ['rejected' => true, 'status' => 5]);

    Livewire::actingAs($this->user)->test(ViabRejectedList::class)
        ->assertSeeHtml("emitTo('partner.actions.viab-response', 'getResponse', '{$viab->id}')");
});

// ---------------------------------------------------------------- TacitList

it('lists tacit viabilities only from the user company', function () {
    $tacit = ['approved' => true, 'completed' => true, 'tacit' => true, 'tacit_at' => now()->subDay()];
    plvViability($this->company, $this->user, plvNote('930001'), $tacit);
    plvViability($this->other, $this->foreign, plvNote('930002'), $tacit);

    Livewire::actingAs($this->user)->test(TacitList::class)
        ->assertSee('930001')->assertDontSee('930002')->assertSee('Justificar')
        ->set('search', '930999')->assertDontSee('930001')->assertSee('Nenhuma viabilidade com esses filtros');
});

// ---------------------------------------------------------------- Todoviability

it('lists viabilities to execute, scoped, filtered and without per-row queries', function () {
    foreach (range(1, 6) as $i) {
        plvViability($this->company, $this->user, plvNote('94000' . $i, ['rubrica' => $i % 2 ? 'AAA' : 'BBB', 'is45' => $i === 1]));
    }
    plvViability($this->other, $this->foreign, plvNote('940099'));

    $component = Livewire::actingAs($this->user)->test(Todoviability::class)
        ->assertSee('940001')->assertSee('940006')->assertDontSee('940099')->assertSee('Expressa');

    DB::enableQueryLog();
    $component->call('$refresh');
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBeLessThan(40);

    $component->call('applyListFilters', ['rubrica' => ['AAA']])->assertSee('940001')->assertDontSee('940002')
        ->call('clearAllCriteria')->assertSee('940002');
});

it('does not let another company toggle in-activity', function () {
    $mine = plvViability($this->company, $this->user, plvNote('950001'), ['inActivity' => false]);

    Livewire::actingAs($this->foreign)->test(Todoviability::class)->call('putInActivity', $mine->id);
    expect($mine->fresh()->inActivity)->toBeFalse();

    Livewire::actingAs($this->user)->test(Todoviability::class)->call('putInActivity', $mine->id);
    expect($mine->fresh()->inActivity)->toBeTrue();
});

// ---------------------------------------------------------------- Histviab

it('renders the history, filters by note type and period and rejects unknown date columns', function () {
    plvViability($this->company, $this->user, plvNote('960001', ['type_note' => 1]), ['sended_at' => now()->subDays(40), 'completed_at' => now()->subDays(30)]);
    plvViability($this->company, $this->user, plvNote('960002', ['type_note' => 2]), ['sended_at' => now()->subDays(2), 'completed_at' => now()->subDay()]);
    plvViability($this->other, $this->foreign, plvNote('960003'));

    Livewire::actingAs($this->user)->test(Histviab::class)
        ->assertSee('960001')->assertSee('960002')->assertDontSee('960003')
        ->set('typeNote', '2')->assertSee('960002')->assertDontSee('960001')
        ->set('typeNote', '')->set('date_in', now()->subDays(5)->format('Y-m-d'))->assertSee('960002')->assertDontSee('960001')
        ->set('dateBy', 'id; drop table users')->assertSet('dateBy', 'sended_at')
        ->call('clearAllCriteria')->assertSet('date_in', '')->assertSee('960001');
});

it('searches the history by many notes at once', function () {
    plvViability($this->company, $this->user, plvNote('970001'));
    plvViability($this->company, $this->user, plvNote('970002'));

    Livewire::actingAs($this->user)->test(Histviab::class)
        ->set('advanceSearch', "970001\n")->call('buscarMultinotas')
        ->assertSee('970001')->assertDontSee('970002');
});

// ---------------------------------------------------------------- Padrão de lista

it('keeps the viability list views free of inline style, script and legacy classes', function () {
    foreach (['viab-rejected-list', 'tacit-list', 'todoviability', 'histviab', 'partials.viability-toolbar'] as $view) {
        $source = file_get_contents(resource_path('views/livewire/partner/' . str_replace('.', '/', $view) . '.blade.php'));
        $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source);

        expect($source)->not->toContain('<style')->not->toContain('<script')->not->toContain('text-bg-')
            ->not->toContain('cursor')->not->toContain('components.filter.filter')->not->toContain('$_SESSION')
            ->and(preg_match('/#[0-9a-fA-F]{3,6}\b|rgb\(|table-(striped|success|danger|warning|hover)|style="[^"]*(color|background|cursor)/', $source))->toBe(0);
    }
});
