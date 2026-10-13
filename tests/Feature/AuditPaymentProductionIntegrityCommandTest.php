<?php

namespace Tests\Feature;

use App\Models\{Company, Note, Production, Service};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditPaymentProductionIntegrityCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_legacy_payment_context_gaps_without_writing_data(): void
    {
        $note = Note::create([
            'note'      => '4000000001',
            'dt_status' => '2026-10-06 08:00:00',
            'nstats'    => 'NEW',
        ]);
        $company    = Company::create(['name' => 'Compel', 'email' => 'compel@example.test']);
        $service    = Service::create(['uuid' => (string) Str::uuid(), 'service' => 'Pagamento', 'folder' => 'pagamento']);
        $production = Production::create([
            'company_id' => $company->id,
            'note_id'    => $note->id,
            'service_id' => $service->uuid,
            'completed'  => false,
            'confirmed'  => false,
            'partial'    => true,
            'dfive'      => true,
            'status'     => 2,
        ]);

        $before = Production::count();

        $this->artisan('sicode:audit-payment-production-integrity', ['--json' => true])
            ->expectsOutputToContain('"read_only": true')
            ->assertExitCode(0);

        $this->assertSame($before, Production::count());
        $this->assertDatabaseHas('productions', ['id' => $production->id]);
        $this->assertDatabaseCount('productionables', 0);
    }
}
