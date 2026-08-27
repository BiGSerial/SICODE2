<?php

use App\Models\Company;
use App\Models\Contract;
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
