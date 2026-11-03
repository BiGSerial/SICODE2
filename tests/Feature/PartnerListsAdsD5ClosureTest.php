<?php

use App\Enum\AdsRequestStatus;
use App\Http\Livewire\Partner\AdsRequests;
use App\Http\Livewire\Partner\ClosureIssues\{History, Index};
use App\Http\Livewire\Partner\FiveNote\{D5list, D5listReturned, Historic};
use App\Models\{AdsRequest, Company, FiveNote, Note, Order, SystemSetting, User};
use App\Services\Closure\ClosureIssueWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification};
use Illuminate\Support\Str;
use Livewire\Livewire;

require_once __DIR__ . '/Closure/ClosureScreenFixture.php';

uses(RefreshDatabase::class);

beforeEach(fn () => Notification::fake());

// Listas do portal da parceira no padrão de controle: Solicitações de ADS (duas abas), D5 (espera, rejeitadas, histórico) e Encerramento (pendências, histórico).

function pldCompany(string $name, array $attributes = []): Company
{
    return Company::query()->create(array_merge(['name' => $name, 'email' => fake()->unique()->safeEmail()], $attributes));
}

function pldUser(Company $company): User
{
    return User::factory()->create(['company_id' => $company->id, 'superadm' => false, 'onlyparner' => true, 'contract' => false]);
}

function pldNote(string $number, string $order): Note
{
    $note = Note::query()->create(['note' => $number, 'lexp' => 'Campinas', 'nexp' => 'Campinas']);
    Order::query()->create(['note_id' => $note->id, 'ordem' => $order]);

    return $note;
}

function pldFive(Company $company, string $number, array $attributes = []): FiveNote
{
    return FiveNote::query()->create(array_merge([
        'company_id'      => $company->id,
        'note_id'         => pldNote($number, 'ORD-' . $number)->id,
        'note_d5'         => 'D5-' . $number,
        'visible_partner' => true,
        'is_completed'    => false,
        'returned'        => false,
        'dispatch_at'     => now()->subDays(3),
    ], $attributes));
}

function pldAds(Company $company, User $user, string $number, AdsRequestStatus $status, array $attributes = []): AdsRequest
{
    return AdsRequest::query()->create(array_merge([
        'requested_by' => $user->id,
        'company_id'   => $company->id,
        'note_id'      => pldNote($number, 'ORD-' . $number)->id,
        'batch_id'     => (string) Str::uuid(),
        'partner'      => true,
        'completed'    => $status === AdsRequestStatus::DONE,
        'status'       => $status,
        'version'      => 1,
    ], $attributes));
}

// ---------------------------------------------------------------- higiene das views

it('keeps the migrated views free of inline style, script, hex colors and bootstrap color classes', function () {
    $views = [
        'livewire/partner/ads-requests', 'livewire/partner/five-note/d5list', 'livewire/partner/five-note/historic',
        'livewire/partner/closure-issues/index', 'livewire/partner/closure-issues/history',
    ];

    foreach ($views as $view) {
        $html = file_get_contents(resource_path("views/{$view}.blade.php"));

        expect($html)->not->toContain('<style')
            ->and($html)->not->toContain('<script')
            ->and($html)->not->toMatch('/(?<![&\w])#[0-9a-fA-F]{3,8}\b/')
            ->and($html)->not->toMatch('/rgba?\(/')
            ->and($html)->not->toMatch('/text-bg-|table-(danger|warning|success|info|secondary|striped|hover|dark|light)/')
            ->and($html)->not->toMatch('/style="[^"]*(color|cursor|background)/')
            ->and($html)->toContain('closure-dash my-panel ops-list ops-control')
            ->and($html)->toContain('<colgroup>');
    }
});

// ---------------------------------------------------------------- D5

it('lists the pending d5 of the company only and counts the tabs', function () {
    $company = pldCompany('Parceira A');
    $other   = pldCompany('Parceira B');
    $viewer  = pldUser($company);

    pldFive($company, '700001');
    pldFive($company, '700002', ['isPassive' => true]);
    pldFive($other, '700003');
    pldFive($company, '700004', ['returned' => true]);
    pldFive($company, '700005', ['is_completed' => true, 'completed_at' => now()]);

    $component = Livewire::actingAs($viewer)->test(D5list::class)
        ->assertSee('D5-700001')->assertSee('D5-700002')
        ->assertDontSee('D5-700003')->assertDontSee('D5-700004')->assertDontSee('D5-700005')
        ->assertSee('Tudo (2)')->assertSee('Metas atuais (1)')->assertSee('Passivos (1)');

    $component->set('passiveFilter', 'passive')->assertSee('D5-700002')->assertDontSee('D5-700001')
        ->set('passiveFilter', 'current')->assertSee('D5-700001')->assertDontSee('D5-700002');
});

it('searches, filters by period and clears the criteria on the d5 list', function () {
    $company = pldCompany('Parceira A');
    $viewer  = pldUser($company);

    pldFive($company, '710001', ['dispatch_at' => now()->subDays(40)]);
    pldFive($company, '710002', ['dispatch_at' => now()->subDay()]);

    Livewire::actingAs($viewer)->test(D5list::class)
        ->set('search', '710001')->assertSee('D5-710001')->assertDontSee('D5-710002')
        ->call('toClean')->assertSee('D5-710002')
        ->set('startDate', now()->subDays(5)->toDateString())->assertSee('D5-710002')->assertDontSee('D5-710001')
        ->call('toClean')->assertSee('D5-710001')
        ->set('multiSearch', "710002\nXYZ")->call('multiSearch')->assertSee('D5-710002')->assertDontSee('D5-710001');
});

it('shows an empty state with and without data on the d5 screens', function () {
    $viewer = pldUser(pldCompany('Sem dados'));

    Livewire::actingAs($viewer)->test(D5list::class)->assertSee('Sem D5 para execução');
    Livewire::actingAs($viewer)->test(D5listReturned::class)->assertSee('Nenhuma D5 rejeitada');
    Livewire::actingAs($viewer)->test(Historic::class)->assertSee('Sem registros no período');
});

it('shows only the returned d5 on the rejected list and the stage on the historic', function () {
    $company = pldCompany('Parceira A');
    $viewer  = pldUser($company);

    pldFive($company, '720001', ['returned' => true]);
    pldFive($company, '720002');
    pldFive($company, '720003', ['is_completed' => true, 'completed_at' => now(), 'is_payed' => true, 'is_archived' => true]);
    pldFive($company, '720004', ['is_completed' => true, 'completed_at' => now(), 'is_payed' => false]);

    Livewire::actingAs($viewer)->test(D5listReturned::class)->assertSee('D5-720001')->assertDontSee('D5-720002')->assertSee('D5 rejeitadas');
    Livewire::actingAs($viewer)->test(Historic::class)->assertSee('D5-720003')->assertSee('Finalizada')->assertSee('Aguardando despacho da medição')->assertDontSee('D5-720002');
});

it('does not query once per row on the d5 list', function () {
    $company = pldCompany('Parceira A');
    $viewer  = pldUser($company);

    foreach (range(1, 12) as $i) {
        pldFive($company, '73' . str_pad((string) $i, 4, '0', STR_PAD_LEFT));
    }

    $this->actingAs($viewer);
    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    Livewire::test(D5list::class)->assertSee('D5-730012');

    expect($queries)->toBeLessThan(40);
});

// ---------------------------------------------------------------- ADS

it('shows the ads requests in two tabs and only queries the open one', function () {
    SystemSetting::setValue('ads_auto_test_mode', '1'); // sem SQL Server no teste
    $company = pldCompany('Parceira A');
    $other   = pldCompany('Parceira B');
    $viewer  = pldUser($company);

    pldAds($company, $viewer, '800001', AdsRequestStatus::QUEUED);
    pldAds($company, $viewer, '800002', AdsRequestStatus::DONE, ['url' => 'https://exemplo.test/ads.zip', 'completed_at' => now()]);
    pldAds($company, $viewer, '800003', AdsRequestStatus::CANCELED, ['canceled_at' => now()]);
    pldAds($other, pldUser($other), '800004', AdsRequestStatus::QUEUED);

    Livewire::actingAs($viewer)->test(AdsRequests::class)
        ->assertSee('Em andamento (1)')->assertSee('Histórico (2)')
        ->assertSee('800001')->assertDontSee('800002')->assertDontSee('800004')
        ->call('setTab', 'history')
        ->assertSet('tab', 'history')
        ->assertSee('800002')->assertSee('800003')->assertDontSee('800001')->assertDontSee('800004')
        ->assertSee('Baixar ADS')->assertSee('Sem link')
        ->set('tab', 'active')->assertSee('800001');
});

it('searches the ads history by note and period and clears the history filters', function () {
    SystemSetting::setValue('ads_auto_test_mode', '1');
    $company = pldCompany('Parceira A');
    $viewer  = pldUser($company);

    pldAds($company, $viewer, '810001', AdsRequestStatus::DONE);
    pldAds($company, $viewer, '810002', AdsRequestStatus::FAILED);

    Livewire::actingAs($viewer)->test(AdsRequests::class)->set('tab', 'history')
        ->set('historySearch', '810001')->assertSee('810001')->assertDontSee('810002')
        ->call('clearHistoryFilters')->assertSee('810001')->assertSee('810002')
        ->set('historyStart', now()->addDay()->toDateString())->assertDontSee('810001');
});

it('lets the superadm filter the ads history by company', function () {
    SystemSetting::setValue('ads_auto_test_mode', '1');
    $a = pldCompany('Empresa Alfa');
    $b = pldCompany('Empresa Beta');

    pldAds($a, pldUser($a), '820001', AdsRequestStatus::DONE);
    pldAds($b, pldUser($b), '820002', AdsRequestStatus::DONE);

    $admin = User::factory()->create(['company_id' => $a->id, 'superadm' => true]);

    Livewire::actingAs($admin)->test(AdsRequests::class)->set('tab', 'history')
        ->assertSee('820001')->assertSee('820002')
        ->call('applyListFilters', ['company' => [(string) $b->id]])
        ->assertSee('820002')->assertDontSee('820001')
        ->call('clearHistoryFilters')->assertSee('820001');
});

// ---------------------------------------------------------------- Encerramento

it('renders the closure issue list with the status tabs, the company scope and the filters', function () {
    $f                                      = closureScreenFixture();
    ['category' => $c, 'subcategory' => $s] = closureIssueTaxonomy();

    $issue = app(ClosureIssueWorkflowService::class)->requestToPartner($f['state'], $c, $s, $f['company'], $f['dispatcher'], 'Faltam documentos.', null, false, ['Enviar a ART']);

    Livewire::actingAs($f['partnerUser'])->test(Index::class)
        ->assertSee($f['order']->ordem)->assertSee('Aguardando resposta (1)')->assertSee('Todas (1)')
        ->call('setStatus', 'RESOLVED')->assertSet('status', 'RESOLVED')->assertDontSee($f['order']->ordem)->assertSee('Nenhuma pendência com esses filtros')
        ->call('setStatus', 'RESOLVED')->assertSet('status', '')->assertSee($f['order']->ordem)
        ->set('search', 'NOTA-INEXISTENTE')->assertDontSee($f['order']->ordem)
        ->call('clearFilters')->assertSet('search', '')->assertSee($f['order']->ordem)
        ->call('applyListFilters', ['category' => [(string) $c->id]])->assertSee($f['order']->ordem)
        ->call('applyListFilters', ['category' => ['999999']])->assertSee($f['order']->ordem);

    Livewire::actingAs($f['outsider'])->test(Index::class)->assertDontSee($f['order']->ordem);

    expect($issue->fresh()->status)->toBe('REQUESTED');
});

it('renders the closure history with totals, status tabs and period', function () {
    $f                                      = closureScreenFixture();
    ['category' => $c, 'subcategory' => $s] = closureIssueTaxonomy();
    $svc                                    = app(ClosureIssueWorkflowService::class);
    $issue                                  = $svc->requestToPartner($f['state'], $c, $s, $f['company'], $f['dispatcher'], 'Faltam documentos.', null, false, ['Enviar a ART']);
    $svc->respond($issue, $f['company'], $f['partnerUser'], 'Segue a ART.', [['status' => 'DONE', 'note' => '']], []);

    Livewire::actingAs($f['partnerUser'])->test(History::class)
        ->assertSee('Segue a ART.')->assertSee('Retornos enviados')
        ->set('status', 'RESOLVED')->assertDontSee('Segue a ART.')->assertSee('Nenhum retorno encontrado')
        ->call('clearFilters')->assertSee('Segue a ART.')
        ->set('from', now()->addDay()->toDateString())->assertDontSee('Segue a ART.');

    Livewire::actingAs($f['outsider'])->test(History::class)->assertDontSee('Segue a ART.');
});
