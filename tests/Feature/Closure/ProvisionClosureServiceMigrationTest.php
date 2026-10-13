<?php

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function closureServiceProvisionMigration(): object
{
    return require base_path('database/migrations/2026_10_06_142513_provision_closure_service.php');
}

it('provisions the closure service with its technical identity and no contract links', function () {
    $service = Service::query()
        ->where('folder', 'encerramento')
        ->sole();

    expect($service->service)->toBe('Encerramento')
        ->and($service->uuid)->not->toBeEmpty()
        ->and((bool) $service->project)->toBeTrue()
        ->and((bool) $service->construction)->toBeTrue()
        ->and($service->icon)->toBe('ri-checkbox-circle-line')
        ->and((bool) $service->canReturn)->toBeFalse()
        ->and(DB::table('service_contract_rules')->where('service_id', $service->id)->count())->toBe(0)
        ->and(DB::table('service_users')->where('service_id', $service->uuid)->count())->toBe(0);
});

it('is idempotent when the closure service was already provisioned', function () {
    $uuid = Service::query()->where('folder', 'encerramento')->sole()->uuid;

    closureServiceProvisionMigration()->up();
    closureServiceProvisionMigration()->up();

    expect(Service::query()->where('folder', 'encerramento')->count())->toBe(1)
        ->and(Service::query()->where('service', 'Encerramento')->count())->toBe(1)
        ->and(Service::query()->where('folder', 'encerramento')->sole()->uuid)->toBe($uuid);
});
