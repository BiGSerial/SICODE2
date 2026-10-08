<?php

namespace Tests\Feature;

use App\Models\{Adsform, Company, FiveNote, Note, Operation, Order, User, WorkReport};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncNoteInformFlowsD5AssociationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_final_report_uses_only_its_explicitly_linked_d5(): void
    {
        $company      = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $user         = User::factory()->create(['company_id' => $company->id]);
        $note         = Note::create(['note' => '4000009920']);
        $firstReport  = $this->workReport($note, $company, $user, '2026-10-01 08:00:00');
        $secondReport = $this->workReport($note, $company, $user, '2026-10-03 08:00:00');
        $firstD5      = FiveNote::create([
            'note_id'        => $note->id,
            'work_report_id' => $firstReport->id,
            'company_id'     => $company->id,
            'note_d5'        => '17500001',
        ]);
        $secondD5 = FiveNote::create([
            'note_id'        => $note->id,
            'work_report_id' => $secondReport->id,
            'company_id'     => $company->id,
            'note_d5'        => '17500002',
        ]);

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'         => "work_report:{$firstReport->id}",
            'five_note_id'     => $firstD5->id,
            'five_note_number' => '17500001',
        ]);
        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'         => "work_report:{$secondReport->id}",
            'five_note_id'     => $secondD5->id,
            'five_note_number' => '17500002',
        ]);
    }

    public function test_it_exports_the_preferred_operation_20_and_ads_amount(): void
    {
        $company = Company::create(['name' => 'Empresa Teste', 'email' => 'teste@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $note    = Note::create(['note' => '4000009921']);
        $report  = $this->workReport($note, $company, $user, '2026-10-01 08:00:00');

        foreach ([
            ['1500000001', '2026-09-01 08:00:00'],
            ['1930000001', '2026-09-02 08:00:00'],
            ['1800000001', '2026-09-03 08:00:00'],
        ] as [$number, $finishedAt]) {
            $order = Order::create(['note_id' => $note->id, 'ordem' => $number]);
            $report->Orders()->attach($order->id);
            Operation::create(['order_id' => $order->id, 'operacao' => '0020', 'fimReal' => $finishedAt]);
        }

        Adsform::create([
            'work_report_id' => $report->id,
            'note_id'        => $note->id,
            'user_id'        => $user->id,
            'amount'         => 1234.56,
            'partial'        => false,
        ]);

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'          => "work_report:{$report->id}",
            'op20_order_number' => '1800000001',
            'op20_finished_at'  => '2026-09-03 08:00:00',
            'ads_amount'        => 1234.56,
        ]);

        $fallbackReport = $this->workReport($note, $company, $user, '2026-10-02 08:00:00');

        foreach ([
            ['1500000002', '2026-09-04 08:00:00'],
            ['1930000002', '2026-09-05 08:00:00'],
        ] as [$number, $finishedAt]) {
            $order = Order::create(['note_id' => $note->id, 'ordem' => $number]);
            $fallbackReport->Orders()->attach($order->id);
            Operation::create(['order_id' => $order->id, 'operacao' => '0020', 'fimReal' => $finishedAt]);
        }

        $this->artisan('sicode:sync-note-inform-flows', ['--note_id' => $note->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('note_inform_flows', [
            'flow_key'          => "work_report:{$fallbackReport->id}",
            'op20_order_number' => '1930000002',
            'op20_finished_at'  => '2026-09-05 08:00:00',
        ]);
    }

    private function workReport(Note $note, Company $company, User $user, string $informedAt): WorkReport
    {
        return WorkReport::create([
            'note_id'     => $note->id,
            'company_id'  => $company->id,
            'user_id'     => $user->id,
            'date'        => substr($informedAt, 0, 10),
            'informed_at' => $informedAt,
            'rejected'    => false,
            'canceled'    => false,
        ]);
    }
}
