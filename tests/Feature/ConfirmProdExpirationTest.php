<?php

use App\Models\{Company, Note, Production, Service};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// `sicode:confirm_prod` confirma a Production concluída depois de 24 h (--days=1), marcada ou não como `noinconsistency`:
// a marca não pode segurar a Production "Finalizada" no Controle para sempre.

function confirmProdFixture(array $attributes): Production
{
    $note    = Note::create(['note' => '500000' . random_int(1000, 9999), 'type_note' => 1, 'dt_status' => now(), 'nstats' => 'NEW']);
    $service = Service::firstOrCreate(['folder' => 'medicao'], ['uuid' => (string) Str::uuid(), 'service' => 'Medicao']);

    $company = Company::firstOrCreate(['name' => 'Empresa de teste'], ['email' => 'empresa@example.test']);

    return Production::create($attributes + ['company_id' => $company->id, 'note_id' => $note->id, 'service_id' => $service->uuid, 'completed' => true, 'confirmed' => false, 'status' => 5]);
}

it('confirma por expiração a Production concluída há mais de 24 h, inclusive a marcada noinconsistency', function () {
    $plain  = confirmProdFixture(['completed_at' => now()->subDays(3), 'noinconsistency' => false]);
    $marked = confirmProdFixture(['completed_at' => now()->subDays(3), 'noinconsistency' => true]);
    $recent = confirmProdFixture(['completed_at' => now()->subHours(5), 'noinconsistency' => true]);

    $this->artisan('sicode:confirm_prod')->assertSuccessful();

    expect((bool) $plain->fresh()->confirmed)->toBeTrue()
        ->and((bool) $marked->fresh()->confirmed)->toBeTrue()
        // Dentro das 24 h a marca continua valendo: não confirma ainda.
        ->and((bool) $recent->fresh()->confirmed)->toBeFalse();
});
