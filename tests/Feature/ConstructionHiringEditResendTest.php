<?php

use App\Http\Livewire\Construction\Hiring\Actions\Edit;
use App\Models\Company;
use App\Models\Daysviab;
use App\Models\Note;
use App\Models\Order;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\User;
use App\Models\Viability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function hiringEditCompany(string $name = 'Parceira Viabilidade'): Company
{
    return Company::query()->create([
        'name' => $name,
        'email' => fake()->unique()->safeEmail(),
    ]);
}

function hiringEditResponsible(Company $company, string $name = 'Responsavel Viabilidade'): User
{
    $user = User::factory()->create([
        'name' => $name,
        'company_id' => $company->id,
        'responsible' => true,
    ]);

    $user->Companies()->attach($company->id);

    return $user;
}

function hiringEditService(string $name = 'Levantamento'): Service
{
    return Service::query()->create([
        'service' => $name,
        'status' => true,
        'folder' => false,
        'project' => false,
        'construction' => false,
        'canReturn' => true,
    ]);
}

function hiringEditViability(Company $company, User $actor, User $responsible): Viability
{
    $note = Note::query()->create([
        'note' => fake()->unique()->numerify('########'),
        'dt_created' => now()->subDays(10),
        'dt_status' => now()->subDays(2),
        'nstats' => '62',
        'status' => 'Suspensao de Obra',
    ]);
    $order = Order::query()->create([
        'note_id' => $note->id,
        'ordem' => fake()->unique()->numerify('##########'),
        'statusSist' => 'ABER',
    ]);

    $viability = Viability::query()->create([
        'note_id' => $note->id,
        'order_id' => $order->id,
        'company_id' => $company->id,
        'user_id' => $actor->id,
        'engineer_id' => $responsible->id,
        'sended_at' => now()->subDays(3),
        'completed' => true,
        'completed_at' => now()->subDay(),
        'hired' => true,
        'hired_at' => now()->subDay(),
        'status' => 9,
    ]);

    $viability->Orders()->attach($order->id);

    return $viability;
}

it('creates a new hiring viability when resending without changing the original record', function () {
    $company = hiringEditCompany();
    $actor = User::factory()->create(['superadm' => true, 'admin' => true]);
    $responsible = hiringEditResponsible($company);
    $newResponsible = hiringEditResponsible($company, 'Novo Responsavel');
    $survey = hiringEditService();
    $viability = hiringEditViability($company, $actor, $responsible);
    Daysviab::query()->create([
        'viability_id' => $viability->id,
        'user_id' => $actor->id,
        'days' => 5,
        'reason' => 'Prazo adicional original',
    ]);
    $originalProduction = Production::query()->create([
        'note_id' => $viability->note_id,
        'service_id' => $survey->uuid,
        'user_id' => $responsible->id,
        'company_id' => $company->id,
        'completed' => true,
        'completed_at' => now()->subHour(),
        'confirmed' => true,
        'status' => 5,
    ]);
    $originalCompletedAt = $originalProduction->completed_at;
    $originalCompletedViabilityAt = $viability->completed_at;

    Livewire::actingAs($actor)
        ->test(Edit::class)
        ->call('editHiring', $viability->id)
        ->set('companyS', $company->id)
        ->set('user_s', $newResponsible->id)
        ->set('newsend', true)
        ->call('alter_viability');

    $originalProduction->refresh();
    $viability->refresh();
    $newViability = Viability::query()->whereKeyNot($viability->id)->first();

    expect($originalProduction->completed)->toBeTrue()
        ->and($originalProduction->completed_at?->equalTo($originalCompletedAt))->toBeTrue()
        ->and($viability->completed)->toBeTrue()
        ->and($viability->completed_at?->equalTo($originalCompletedViabilityAt))->toBeTrue()
        ->and($viability->days()->count())->toBe(1)
        ->and(Viability::query()->count())->toBe(2)
        ->and($newViability)->not->toBeNull()
        ->and($newViability->note_id)->toBe($viability->note_id)
        ->and($newViability->order_id)->toBe($viability->order_id)
        ->and($newViability->company_id)->toBe($company->id)
        ->and($newViability->engineer_id)->toBe($newResponsible->id)
        ->and($newViability->completed)->toBeFalse()
        ->and($newViability->hired)->toBeTrue()
        ->and($newViability->status)->toBe(1)
        ->and($newViability->sended_at)->not->toBeNull()
        ->and($newViability->days()->count())->toBe(0)
        ->and($newViability->Orders()->pluck('orders.id')->all())->toBe([$viability->order_id]);
});

it('does not reopen linked return production when resending viability', function () {
    $company = hiringEditCompany();
    $actor = User::factory()->create(['superadm' => true, 'admin' => true]);
    $responsible = hiringEditResponsible($company);
    $newResponsible = hiringEditResponsible($company, 'Novo Responsavel');
    $survey = hiringEditService();
    $viability = hiringEditViability($company, $actor, $responsible);
    $originalProduction = Production::query()->create([
        'note_id' => $viability->note_id,
        'service_id' => $survey->uuid,
        'user_id' => $responsible->id,
        'company_id' => $company->id,
        'completed' => true,
        'completed_at' => now()->subDays(2),
        'confirmed' => true,
        'status' => 5,
    ]);
    $returnProduction = Production::query()->create([
        'note_id' => $viability->note_id,
        'service_id' => $survey->uuid,
        'user_id' => $responsible->id,
        'company_id' => $company->id,
        'completed' => true,
        'completed_at' => now()->subHour(),
        'confirmed' => false,
        'status' => 5,
        'd5' => true,
    ]);
    $reclaim = Reclaim::query()->create([
        'note_id' => $viability->note_id,
        'service_id' => $survey->uuid,
        'production_id' => $returnProduction->id,
        'completed' => true,
        'completed_at' => now()->subMinutes(30),
    ]);
    $viability->Reclaims()->attach($reclaim->id);

    Livewire::actingAs($actor)
        ->test(Edit::class)
        ->call('editHiring', $viability->id)
        ->set('companyS', $company->id)
        ->set('user_s', $newResponsible->id)
        ->set('newsend', true)
        ->call('alter_viability');

    $originalProduction->refresh();
    $returnProduction->refresh();
    $viability->refresh();
    $newViability = Viability::query()->whereKeyNot($viability->id)->first();

    expect($originalProduction->completed)->toBeTrue()
        ->and($originalProduction->completed_at)->not->toBeNull()
        ->and($returnProduction->completed)->toBeTrue()
        ->and($returnProduction->completed_at)->not->toBeNull()
        ->and($viability->completed)->toBeTrue()
        ->and($newViability)->not->toBeNull()
        ->and($newViability->completed)->toBeFalse()
        ->and($newViability->Reclaims()->count())->toBe(0);
});
