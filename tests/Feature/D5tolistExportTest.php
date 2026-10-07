<?php

namespace Tests\Feature;

use App\Exports\Services\Payment\D5tolistExport;
use App\Models\{Company, FiveNote, Note, Order, Production, Service, User, WorkReport};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class D5tolistExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_d5_linked_to_an_informe_not_only_the_legacy_note_d5(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'c@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $service = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
        $note    = Note::create(['note' => '4001917766', 'dt_status' => '2026-09-10 08:00:00', 'nstats' => 'NEW']);
        $order   = Order::create(['note_id' => $note->id, 'ordem' => '200000094109', 'statusSist' => 'LIB']);
        $report  = WorkReport::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'date' => '2026-09-10', 'informed_at' => '2026-09-10 08:00:00', 'rejected' => false, 'canceled' => false,
        ]);
        $report->Orders()->sync([$order->id]);
        $five = FiveNote::create(['note_id' => $note->id, 'work_report_id' => $report->id, 'note_d5' => '1', 'reason' => '1_MOTIVO', 'codify' => '7_COD']);
        $production = Production::create([
            'note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'user_id' => $user->id,
            'completed' => false, 'confirmed' => false, 'status' => 2, 'dfive' => true,
        ]);
        $five->productions()->attach($production->id);

        // Produção sem D5 associada não entra.
        Production::create([
            'note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'user_id' => $user->id,
            'completed' => false, 'confirmed' => false, 'status' => 2, 'dfive' => false,
        ]);

        $export = new D5tolistExport($service, $user->id);
        $rows   = $export->collection();

        $this->assertCount(1, $rows);
        $mapped = $export->map($rows->first());
        $this->assertSame('Compel', $mapped[0]);
        $this->assertSame('200000094109', $mapped[2]);
        $this->assertSame('MOTIVO', $mapped[9]);
        $this->assertSame('4001917766', $mapped[16]);
        $this->assertSame('1', $mapped[5]);                 // Nota Gerada: D5 já existente
        $this->assertSame($report->id, $mapped[18]);        // Id Informe
        $this->assertNotSame('', $mapped[19]);              // Escopo
    }
}
