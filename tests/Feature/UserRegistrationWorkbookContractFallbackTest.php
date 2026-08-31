<?php

use App\Models\Company;
use App\Models\Contract;
use App\Models\Service;
use App\Models\User;
use App\Services\Admin\Company\UserRegistrationWorkbookService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function workbookFallbackCompany(string $name = 'Empresa Ficha'): Company
{
    return Company::query()->create([
        'name' => $name,
        'email' => fake()->unique()->safeEmail(),
    ]);
}

function workbookFallbackContract(Company $company, string $number, ?string $dateEnd = null): Contract
{
    $contract = new Contract();
    $contract->company_id = $company->id;
    $contract->number = $number;
    $contract->service = true;
    $contract->construction = false;
    $contract->date_end = $dateEnd ?? now()->addYear()->toDateString();
    $contract->save();

    return $contract;
}

function workbookFallbackService(string $name): Service
{
    return Service::query()->create([
        'service' => $name,
        'status' => true,
        'folder' => false,
        'project' => false,
        'construction' => false,
        'canReturn' => false,
    ]);
}

function attachWorkbookFallbackContractService(Contract $contract, Service $service): void
{
    $contract->services()->attach($service->id, [
        'posts' => false,
        'qtd' => 1,
        'days' => 1,
        'dispatch' => false,
    ]);
}

it('associates workbook users without a selected contract to the oldest valid company contract', function () {
    $company = workbookFallbackCompany();
    workbookFallbackContract($company, 'EXPIRADO', now()->subDay()->toDateString());
    $oldestValid = workbookFallbackContract($company, 'VALIDO-ANTIGO', now()->addMonth()->toDateString());
    workbookFallbackContract($company, 'VALIDO-NOVO', now()->addYear()->toDateString());

    $result = app(UserRegistrationWorkbookService::class)->processValid($company, [
        'valid_units' => [],
        'valid_users' => [[
            'action' => 'Criar',
            'name' => 'Usuario Ficha',
            'email' => 'usuario.ficha@example.test',
            'registration' => '12345',
            'unit' => $company->display_name,
            'contract' => '',
            'admin' => false,
            'operator' => false,
            'user' => true,
            'management' => false,
        ]],
    ]);

    $user = User::query()->where('email', 'usuario.ficha@example.test')->firstOrFail();

    expect($result['createdUsers'])->toBe(1)
        ->and($user->Employee?->contract_id)->toBe($oldestValid->id)
        ->and($user->Employee?->service_id)->toBeNull();
});

it('preserves existing user activities when processing workbook users', function () {
    $company = workbookFallbackCompany();
    $contract = workbookFallbackContract($company, 'VALIDO');
    $contractService = workbookFallbackService('Contrato Atividade A');
    $existingService = workbookFallbackService('Atividade Existente');
    attachWorkbookFallbackContractService($contract, $contractService);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'usuario.existente@example.test',
        'admin' => false,
        'operator' => false,
        'user' => true,
    ]);
    $user->Employee()->create([
        'contract_id' => $contract->id,
        'service_id' => $existingService->uuid,
    ]);
    $user->ToServices()->create([
        'service_id' => $existingService->uuid,
        'service' => true,
        'dispatch' => false,
    ]);

    $result = app(UserRegistrationWorkbookService::class)->processValid($company, [
        'valid_units' => [],
        'valid_users' => [[
            'action' => 'Manter',
            'name' => 'Usuario Existente',
            'email' => 'usuario.existente@example.test',
            'registration' => '12345',
            'unit' => $company->display_name,
            'contract' => 'VALIDO | ' . $company->display_name,
            'primary_service' => 'Contrato Atividade A',
            'admin' => false,
            'operator' => false,
            'user' => true,
            'management' => false,
        ]],
    ]);

    $user->refresh();

    expect($result['updatedUsers'])->toBe(1)
        ->and($user->Employee?->contract_id)->toBe($contract->id)
        ->and($user->Employee?->service_id)->toBe($existingService->uuid)
        ->and($user->ToServices()->pluck('service_id')->all())->toBe([$existingService->uuid]);
});

it('does not add every contract activity when processing workbook users', function () {
    $company = workbookFallbackCompany();
    $contract = workbookFallbackContract($company, 'VALIDO');
    $firstService = workbookFallbackService('Contrato Atividade A');
    $secondService = workbookFallbackService('Contrato Atividade B');
    attachWorkbookFallbackContractService($contract, $firstService);
    attachWorkbookFallbackContractService($contract, $secondService);

    $result = app(UserRegistrationWorkbookService::class)->processValid($company, [
        'valid_units' => [],
        'valid_users' => [[
            'action' => 'Criar',
            'name' => 'Usuario Novo',
            'email' => 'usuario.novo@example.test',
            'registration' => '12345',
            'unit' => $company->display_name,
            'contract' => 'VALIDO | ' . $company->display_name,
            'primary_service' => '',
            'admin' => false,
            'operator' => false,
            'user' => true,
            'management' => false,
        ]],
    ]);

    $user = User::query()->where('email', 'usuario.novo@example.test')->firstOrFail();

    expect($result['createdUsers'])->toBe(1)
        ->and($user->Employee?->contract_id)->toBe($contract->id)
        ->and($user->Employee?->service_id)->toBe($firstService->uuid)
        ->and($user->ToServices()->pluck('service_id')->all())->toBe([$firstService->uuid]);
});
