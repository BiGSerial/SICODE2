<?php

namespace Tests\Feature;

use App\Models\{Analise, Company, Note, Partial, Production, Service};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeduplicatePaymentProductionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_force_analysis_discards_analyses_from_removed_productions_and_preserves_the_kept_one(): void
    {
        $note    = Note::create(['note' => '4000000001', 'dt_status' => '2026-10-07 08:00:00', 'nstats' => 'NEW']);
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.test']);
        $service = Service::create(['uuid' => (string) Str::uuid(), 'service' => 'Pagamento', 'folder' => 'pagamento']);
        $partial = Partial::create(['note_id' => $note->id, 'company_id' => $company->id]);

        $keep   = $this->paymentProduction($note->id, $company->id, $service->uuid);
        $remove = $this->paymentProduction($note->id, $company->id, $service->uuid);
        $partial->productions()->attach([$keep->id, $remove->id]);

        $keptAnalysis      = Analise::create(['production_id' => $keep->id, 'info' => 'preservar']);
        $discardedAnalysis = Analise::create(['production_id' => $remove->id, 'info' => 'descartar']);

        $this->artisan('sicode:deduplicate-payment-productions', [
            '--keep'           => $keep->id,
            '--remove'         => [$remove->id],
            '--cycle'          => 'partial',
            '--force-analysis' => true,
            '--reason'         => 'Teste de descarte explícito da análise duplicada',
            '--apply'          => true,
            '--yes'            => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('productions', ['id' => $keep->id]);
        $this->assertDatabaseMissing('productions', ['id' => $remove->id]);
        $this->assertDatabaseHas('analises', ['id' => $keptAnalysis->id, 'production_id' => $keep->id]);
        $this->assertDatabaseMissing('analises', ['id' => $discardedAnalysis->id]);
    }

    private function paymentProduction(int $noteId, string $companyId, string $serviceId): Production
    {
        return Production::create([
            'company_id' => $companyId,
            'note_id'    => $noteId,
            'service_id' => $serviceId,
            'completed'  => false,
            'confirmed'  => false,
            'partial'    => true,
            'status'     => 2,
        ]);
    }
}
