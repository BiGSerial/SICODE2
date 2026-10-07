<?php

namespace Tests\Feature;

use App\Exports\Dispatchs\SupervisionExportList;
use App\Models\{Company, FiveNote, Note, Order, User, WorkReport};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisionExportListTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_row_exports_only_the_orders_of_its_own_informe(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'c@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $note    = Note::create(['note' => '4000003900', 'dt_status' => '2026-09-10 08:00:00', 'nstats' => 'NEW']);

        $reports = collect(['1700003900', '1800003900'])->map(function ($ordem) use ($note, $company, $user) {
            $order  = Order::create(['note_id' => $note->id, 'ordem' => $ordem, 'statusSist' => 'LIB']);
            $report = WorkReport::create([
                'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
                'date'    => '2026-09-10', 'informed_at' => '2026-09-10 08:00:00', 'rejected' => false, 'canceled' => false,
            ]);
            $report->Orders()->sync([$order->id]);

            return $report;
        });
        $five = FiveNote::create(['note_id' => $note->id, 'note_d5' => 'D5-1', 'is_completed' => true]);

        $rows = collect([
            ['operational_work_report_id', $reports[0]->id],
            ['operational_work_report_id', $reports[1]->id],
            ['passive_d5_id', $five->id],
        ])->map(function ($attr) use ($note) {
            $fresh = Note::with(['orders', 'WorkReports.Orders', 'WorkReports.Company', 'WorkReports.FiveNote', 'FiveNotes', 'FiveNote', 'Productions', 'Partials', 'OldAds'])
                ->find($note->id);
            $fresh->setAttribute($attr[0], $attr[1]);

            return $fresh;
        });

        $export = new SupervisionExportList($rows, 'uuid');
        $mapped = $export->collection()->map(fn ($row) => $export->map($row));

        $this->assertSame('1700003900', $mapped[0][4]);
        $this->assertSame('1800003900', $mapped[1][4]);
        $this->assertSame('D5', $mapped[2][0]);
        $this->assertStringContainsString('1700003900', $mapped[2][4]);
        $this->assertSame('Compel', $mapped[0][8]);
    }
}
