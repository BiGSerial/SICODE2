<?php

use App\Http\Livewire\Partner\FiveNote\D5list;
use App\Http\Livewire\Partner\FiveNote\Historic;
use App\Http\Livewire\Partner\PartialList;
use App\Models\Company;
use App\Models\FiveNote;
use App\Models\Note;
use App\Models\Order;
use App\Models\Partial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function partnerCompanyVisibilityCompany(string $name): Company
{
    return Company::query()->create([
        'name' => $name,
        'email' => fake()->unique()->safeEmail(),
    ]);
}

function partnerCompanyVisibilityUser(Company $company): User
{
    return User::factory()->create([
        'company_id' => $company->id,
        'superadm' => false,
        'onlyparner' => true,
        'contract' => false,
    ]);
}

function partnerCompanyVisibilityNote(string $noteNumber, string $orderNumber): Note
{
    $note = Note::query()->create([
        'note' => $noteNumber,
        'lexp' => 'Campinas',
        'nexp' => 'Campinas',
    ]);

    Order::query()->create([
        'note_id' => $note->id,
        'ordem' => $orderNumber,
    ]);

    return $note;
}

it('shows partial reports by company instead of informer user', function () {
    $company = partnerCompanyVisibilityCompany('Parceira A');
    $otherCompany = partnerCompanyVisibilityCompany('Parceira B');
    $informer = partnerCompanyVisibilityUser($company);
    $viewer = partnerCompanyVisibilityUser($company);
    $otherUser = partnerCompanyVisibilityUser($otherCompany);

    $visible = Partial::query()->create([
        'company_id' => $company->id,
        'user_id' => $informer->id,
        'note_id' => partnerCompanyVisibilityNote('500001', 'ORDEM-VISIVEL')->id,
    ]);
    Partial::query()->create([
        'company_id' => $otherCompany->id,
        'user_id' => $otherUser->id,
        'note_id' => partnerCompanyVisibilityNote('500002', 'ORDEM-OCULTA')->id,
    ]);

    $this->actingAs($viewer);

    $component = app(PartialList::class);

    expect($component->getListsProperty()->pluck('id')->all())->toContain($visible->id);

    $component->search = 'ORDEM-OCULTA';

    expect($component->getListsProperty()->pluck('id')->all())->toBe([]);
});

it('shows pending and historic d5 notes by company', function () {
    $company = partnerCompanyVisibilityCompany('Parceira A');
    $otherCompany = partnerCompanyVisibilityCompany('Parceira B');
    $viewer = partnerCompanyVisibilityUser($company);

    $visiblePending = FiveNote::query()->create([
        'company_id' => $company->id,
        'note_id' => partnerCompanyVisibilityNote('600001', 'ORDEM-D5-PENDENTE')->id,
        'note_d5' => 'D5-600001',
        'visible_partner' => true,
        'is_completed' => false,
        'returned' => false,
        'dispatch_at' => now(),
    ]);
    FiveNote::query()->create([
        'company_id' => $otherCompany->id,
        'note_id' => partnerCompanyVisibilityNote('600002', 'ORDEM-D5-OCULTA')->id,
        'note_d5' => 'D5-600002',
        'visible_partner' => true,
        'is_completed' => false,
        'returned' => false,
        'dispatch_at' => now(),
    ]);
    $visibleHistoric = FiveNote::query()->create([
        'company_id' => $company->id,
        'note_id' => partnerCompanyVisibilityNote('600003', 'ORDEM-D5-HIST')->id,
        'note_d5' => 'D5-600003',
        'visible_partner' => true,
        'is_completed' => true,
        'completed_at' => now(),
        'dispatch_at' => now()->subDay(),
    ]);

    $this->actingAs($viewer);

    expect(app(D5list::class)->getFivesProperty()->pluck('id')->all())->toContain($visiblePending->id);
    expect(app(Historic::class)->getFivesProperty()->pluck('id')->all())->toContain($visibleHistoric->id);
});

it('shows branch records to a partner user assigned to the matriz', function () {
    $matriz = partnerCompanyVisibilityCompany('Parceira Matriz');
    $filial = partnerCompanyVisibilityCompany('Parceira Filial');
    $filial->update(['parent_id' => $matriz->id]);
    $viewer = partnerCompanyVisibilityUser($matriz);

    $visibleBranchNote = FiveNote::query()->create([
        'company_id' => $filial->id,
        'note_id' => partnerCompanyVisibilityNote('600010', 'ORDEM-FILIAL-VISIVEL')->id,
        'note_d5' => 'D5-600010',
        'visible_partner' => true,
        'is_completed' => false,
        'returned' => false,
        'dispatch_at' => now(),
    ]);

    $this->actingAs($viewer);

    expect(app(D5list::class)->getFivesProperty()->pluck('id')->all())
        ->toContain($visibleBranchNote->id);
});
