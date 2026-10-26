<?php

use App\Http\Livewire\Partner\Count\ClosureIssuesCount;
use App\Http\Livewire\Partner\Menu;
use App\Models\Closure\ClosureIssue;
use App\Models\User;
use App\Services\Closure\ClosureIssueWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

require_once __DIR__ . '/ClosureScreenFixture.php';

// Sinalizador do menu da parceira: bolinha vermelha no item "Encerramento" e selo com a quantidade no link "Pendências".

uses(RefreshDatabase::class);

beforeEach(fn () => Notification::fake());

/** Pendência da empresa no status pedido (REQUESTED | RETURNED | RESPONDED | RESOLVED). */
function badgeIssue(array $f, string $status, $company = null): ClosureIssue
{
    ['category' => $c, 'subcategory' => $s] = closureIssueTaxonomy();
    $company ??= $f['company'];
    $workflow = app(ClosureIssueWorkflowService::class);

    $issue = $workflow->requestToPartner($f['state'], $c, $s, $company, $f['dispatcher'], 'Pedido ' . fake()->unique()->numerify('###'), null, false);

    if ($status === ClosureIssue::STATUS_REQUESTED) {
        return $issue;
    }

    $issue = $workflow->respond($issue, $company, $f['partnerUser'], 'Retorno.');

    return match ($status) {
        ClosureIssue::STATUS_RETURNED => $workflow->returnForCorrection($issue, $f['dispatcher'], 'Corrija.'),
        ClosureIssue::STATUS_RESOLVED => $workflow->resolve($issue, $f['dispatcher']),
        default                       => $issue,
    };
}

it('counts only the issues waiting on the partner (requested or returned) of the user company', function () {
    $f = closureScreenFixture();
    badgeIssue($f, ClosureIssue::STATUS_REQUESTED);
    badgeIssue($f, ClosureIssue::STATUS_REQUESTED);
    badgeIssue($f, ClosureIssue::STATUS_RETURNED);
    badgeIssue($f, ClosureIssue::STATUS_RESPONDED);
    badgeIssue($f, ClosureIssue::STATUS_RESOLVED);
    badgeIssue($f, ClosureIssue::STATUS_REQUESTED, $f['other']);

    Livewire::actingAs($f['partnerUser'])->test(ClosureIssuesCount::class, ['mode' => 'badge'])
        ->assertSet('mode', 'badge')
        ->assertSeeHtml('bg-danger')
        ->assertSeeHtml('>3<');
});

it('shows the red dot only when there is something to check', function () {
    $f = closureScreenFixture();

    Livewire::actingAs($f['partnerUser'])->test(ClosureIssuesCount::class, ['mode' => 'dot'])->assertDontSeeHtml('closure-menu-dot');
    Livewire::actingAs($f['partnerUser'])->test(ClosureIssuesCount::class, ['mode' => 'badge'])->assertSeeHtml('bg-light')->assertSeeHtml('>0<')->assertDontSeeHtml('bg-danger');

    badgeIssue($f, ClosureIssue::STATUS_REQUESTED);

    Livewire::actingAs($f['partnerUser'])->test(ClosureIssuesCount::class, ['mode' => 'dot'])->assertSeeHtml('closure-menu-dot');
});

it('never counts another company or a user without access', function () {
    $f = closureScreenFixture();
    badgeIssue($f, ClosureIssue::STATUS_REQUESTED, $f['other']);

    $otherCompanyUser = User::factory()->create(['superadm' => false, 'admin' => false, 'company_id' => $f['company']->id]);

    Livewire::actingAs($f['partnerUser'])->test(ClosureIssuesCount::class)->assertSeeHtml('>0<');
    Livewire::actingAs($otherCompanyUser)->test(ClosureIssuesCount::class)->assertSeeHtml('>0<');
    Livewire::actingAs($f['outsider'])->test(ClosureIssuesCount::class)->assertSeeHtml('>0<');
});

it('lets the superadm see the total of every company, like the other partner counters', function () {
    $f = closureScreenFixture();
    badgeIssue($f, ClosureIssue::STATUS_REQUESTED);
    badgeIssue($f, ClosureIssue::STATUS_REQUESTED, $f['other']);

    $super = User::factory()->create(['superadm' => true, 'admin' => false]);

    Livewire::actingAs($super)->test(ClosureIssuesCount::class)->assertSeeHtml('>2<');
});

it('renders both indicators in the partner menu: dot on the parent and count on the link', function () {
    $f = closureScreenFixture();
    badgeIssue($f, ClosureIssue::STATUS_REQUESTED);

    $menu = Livewire::actingAs($f['partnerUser'])->test(Menu::class);

    $menu->assertSeeHtml('closure-menu-dot')->assertSeeHtml('Pendências')->assertSeeHtml('badge ms-2 bg-danger');
});
