<?php

use App\Http\Livewire\Partner\{PartialList, WorkEquipment, WorkedRejectedList, Workedlist};
use App\Models\{Bancoupdate, Company, Equipment, Note, Order, Partial, PartnerCompanyPermissionGrant, ReturnWork, User, WorkReport};
use App\Services\PartnerAccess\PartnerPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function plrCompany(array $attributes = []): Company
{
    return Company::query()->create(array_merge(['name' => 'Empreiteira ' . fake()->unique()->word(), 'email' => fake()->unique()->safeEmail()], $attributes));
}

function plrUser(Company $company, array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'company_id' => $company->id, 'superadm' => false, 'admin' => false, 'management' => false, 'operator' => false, 'user' => false,
        'contract'   => false, 'first_pass' => false, 'bypassprod' => false, 'engineer' => false, 'onlyparner' => true, 'responsible' => false,
        'btzero'     => false, 'can_dispatch' => false, 'analyst' => false, 'legal_controller' => false, 'legal_field' => false, 'legal_manager' => false,
    ], $attributes));
}

function plrGrant(Company $company, array $enabled): void
{
    foreach (PartnerPermissionCatalog::allPermissionKeys() as $key) {
        PartnerCompanyPermissionGrant::query()->create([
            'company_id' => $company->id, 'permission_key' => $key,
            'scope_type' => array_key_exists($key, PartnerPermissionCatalog::groups()) ? 'group' : 'item', 'enabled' => in_array($key, $enabled, true),
        ]);
    }
}

function plrNote(string $note, string $order, array $attributes = []): Note
{
    $model = Note::query()->create(array_merge(['note' => $note, 'lexp' => 'Campinas', 'nexp' => 'Campinas', 'rubrica' => 'RUB-A'], $attributes));
    Order::query()->create(['note_id' => $model->id, 'ordem' => $order]);

    return $model;
}

function plrReport(Company $company, User $user, Note $note, array $attributes = []): WorkReport
{
    return WorkReport::query()->create(array_merge([
        'note_id'     => $note->id, 'company_id' => $company->id, 'user_id' => $user->id, 'date' => '2026-08-01',
        'informed_at' => '2026-08-01 08:00:00', 'responsible' => 'Fulano', 'team' => 'Equipe 1',
    ], $attributes));
}

function plrViewSource(string $name): string
{
    return file_get_contents(resource_path('views/livewire/partner/' . $name . '.blade.php'));
}

beforeEach(function () {
    Bancoupdate::query()->create(['last_update' => now()]);
    $this->company = plrCompany();
    $this->other   = plrCompany();
    $this->user    = plrUser($this->company);
    $this->foreign = plrUser($this->other);
    plrGrant($this->company, ['conclusion_reports.list', 'conclusion_reports.reinform', 'conclusion_reports.export', 'conclusion_reports.equipment', 'partial_reports.list']);
});

it('renders every list empty without error', function () {
    foreach ([Workedlist::class, WorkedRejectedList::class, WorkEquipment::class, PartialList::class] as $component) {
        Livewire::actingAs($this->user)->test($component)->assertOk();
    }
});

it('lists only informs of the company and filters by situation, search and quick card', function () {
    $mine     = plrReport($this->company, $this->user, plrNote('700001', 'ORD-MINHA'));
    $rejected = plrReport($this->company, $this->user, plrNote('700002', 'ORD-REJ'), ['rejected' => true]);
    plrReport($this->other, $this->foreign, plrNote('700003', 'ORD-ALHEIA'));

    Livewire::actingAs($this->user)->test(Workedlist::class)
        ->assertSee('700001')->assertSee('700002')->assertDontSee('700003')
        ->call('quickSituation', 'rejected')->assertSee('700002')->assertDontSee('700001')
        ->call('quickSituation', 'rejected')->assertSee('700001')
        ->set('search', '700001')->assertSee('700001')->assertDontSee('700002')
        ->call('clearAllCriteria')->assertSee('700002');
});

it('keeps the company filter inside the permission scope', function () {
    plrReport($this->company, $this->user, plrNote('710001', 'ORD-A'));
    plrReport($this->other, $this->foreign, plrNote('710002', 'ORD-B'));

    Livewire::actingAs($this->user)->test(Workedlist::class)
        ->call('applyListFilters', ['company' => [(string) $this->other->id]])
        ->assertSet('filters', [])
        ->assertSee('710001')->assertDontSee('710002');
});

it('filters informs by rubrica and location filters', function () {
    plrReport($this->company, $this->user, plrNote('720001', 'ORD-1', ['rubrica' => 'RUB-X']));
    plrReport($this->company, $this->user, plrNote('720002', 'ORD-2', ['rubrica' => 'RUB-Y']));

    Livewire::actingAs($this->user)->test(Workedlist::class)
        ->call('applyListFilters', ['rubrica' => ['RUB-X']])
        ->assertSee('720001')->assertDontSee('720002')
        ->call('removeListFilter', 'rubrica')->assertSee('720002');
});

it('lists rejected informs of the company and opens the details modal', function () {
    $rejected = plrReport($this->company, $this->user, plrNote('730001', 'ORD-R'), ['rejected' => true]);
    ReturnWork::query()->create(['service_id' => \App\Models\Service::create(['service' => 'Fiscalização'])->uuid, 'work_report_id' => $rejected->id, 'user_id' => $this->user->id, 'category' => 'Foto ilegível', 'text_obs' => 'Refazer a foto']);
    plrReport($this->company, $this->user, plrNote('730002', 'ORD-OK'));
    plrReport($this->other, $this->foreign, plrNote('730003', 'ORD-X'), ['rejected' => true]);

    Livewire::actingAs($this->user)->test(WorkedRejectedList::class)
        ->assertSee('730001')->assertSee('Foto ilegível')->assertDontSee('730002')->assertDontSee('730003')
        ->call('openRejectDetails', $rejected->id)->assertSet('selectedRejectedNote', '730001')->assertSee('Refazer a foto')
        ->call('closeRejectDetails')->assertSet('selectedRejectedWorkReportId', null)
        ->set('search', 'inexistente')->assertDontSee('730001');
});

it('lists declared equipment of the company with movement filter', function () {
    $report = plrReport($this->company, $this->user, plrNote('740001', 'ORD-E'));
    Equipment::query()->create(['work_report_id' => $report->id, 'type' => 'BC', 'patrimony' => 'PAT-INST', 'installed' => true]);
    Equipment::query()->create(['work_report_id' => $report->id, 'type' => 'BC', 'patrimony' => 'PAT-REM', 'installed' => false]);
    $foreignReport = plrReport($this->other, $this->foreign, plrNote('740002', 'ORD-F'));
    Equipment::query()->create(['work_report_id' => $foreignReport->id, 'type' => 'BC', 'patrimony' => 'PAT-ALHEIO', 'installed' => true]);

    Livewire::actingAs($this->user)->test(WorkEquipment::class)
        ->assertSee('PAT-INST')->assertSee('PAT-REM')->assertDontSee('PAT-ALHEIO')
        ->call('quickMovement', '1')->assertSee('PAT-INST')->assertDontSee('PAT-REM')
        ->call('clearAllCriteria')->assertSee('PAT-REM');
});

it('keeps partial status filter, counts and row badge on the same rule', function () {
    $make = fn (string $noteNumber, array $flags) => Partial::query()->create(array_merge([
        'company_id' => $this->company->id, 'user_id' => $this->user->id, 'note_id' => plrNote($noteNumber, 'ORD-' . $noteNumber)->id,
    ], $flags));

    $rows = [
        'evaluation'  => $make('750001', []),
        'supervising' => $make('750002', ['allow' => true]),
        'measuring'   => $make('750003', ['allow' => true, 'supervision' => true]),
        'paid'        => $make('750004', ['allow' => true, 'supervision' => true, 'payment' => true]),
        'rejected'    => $make('750005', ['deny' => true]),
    ];
    Partial::query()->create(['company_id' => $this->other->id, 'user_id' => $this->foreign->id, 'note_id' => plrNote('750099', 'ORD-X')->id]);

    $component = app(PartialList::class);

    foreach ($rows as $key => $partial) {
        expect($component->partialStatus($partial)['key'])->toBe($key);
    }

    $test = Livewire::actingAs($this->user)->test(PartialList::class)->assertSee('750001')->assertDontSee('750099');

    foreach ($rows as $key => $partial) {
        $test->call('quickStatus', $key);

        foreach ($rows as $otherKey => $otherPartial) {
            $number = $otherPartial->Note->note;
            $otherKey === $key ? $test->assertSee($number) : $test->assertDontSee($number);
        }

        $test->call('quickStatus', $key);
    }
});

it('does not use inline style, script, bootstrap colors or hex in the migrated views', function () {
    foreach (['workedlist', 'worked-rejected-list', 'work-equipment', 'partial-list'] as $view) {
        $source = preg_replace('/\{\{--.*?--\}\}/s', '', plrViewSource($view));

        expect($source)->not->toContain('<style')->not->toContain('<script')->not->toContain('text-bg-')
            ->not->toContain('table-danger')->not->toContain('table-striped')->not->toContain('cursor')
            ->not->toContain('components.filter.filter')->not->toContain('x-show-loading')->not->toMatch('/#[0-9a-fA-F]{3,6}\b/');
    }

    foreach (['worksList', 'workedRejectedList', 'workequipment', 'partial_list'] as $shell) {
        expect(file_get_contents(resource_path('views/partner/' . $shell . '.blade.php')))->not->toContain('<script');
    }
});
