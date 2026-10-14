<?php

namespace Tests\Feature;

use App\Http\Livewire\Reports\PostWorkProcessReport;
use App\Models\{Company, FiveNote, Note, Partial, Production, Service, User, WorkReport};
use App\Services\Reports\PostWorkProcessReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PostWorkProcessReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejected_partial_is_not_a_pending_row_and_old_cells_list_rows(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        $open     = $this->partial($company, $user, false);
        $rejected = $this->partial($company, $user, true);

        $rows = app(PostWorkProcessReportService::class)->rows(['from' => '2026-01-01', 'to' => now()->toDateString()]);
        $this->assertSame([$open->id], $rows->where('type', 'partial')->pluck('id')->all());

        Livewire::test(PostWorkProcessReport::class, ['from' => '2026-01-01'])
            ->set('view', 'matrix')
            ->call('pickCell', 'await_fiscal_dispatch', 12)
            ->assertSee($open->Note->note)
            ->assertDontSee($rejected->Note->note);
    }

    private function partial(Company $company, User $user, bool $deny): Partial
    {
        $note    = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        $partial = Partial::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'allow'   => !$deny, 'deny' => $deny,
        ]);
        $partial->forceFill(['created_at' => '2026-03-02 10:00:00'])->save();

        return $partial;
    }

    public function test_old_final_and_d5_rows_are_listed_when_cell_is_clicked(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        $note = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        WorkReport::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'date'    => '2026-03-02', 'informed_at' => '2026-03-02 10:00:00', 'rejected' => false, 'canceled' => false,
        ]);
        $d5note = Note::create(['note' => uniqid('5'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        FiveNote::create(['note_id' => $d5note->id, 'note_d5' => uniqid('D5'), 'is_completed' => true, 'completed_at' => '2026-03-02 10:00:00']);

        Livewire::test(PostWorkProcessReport::class, ['from' => '2026-01-01'])
            ->set('view', 'matrix')
            ->call('pickCell', 'await_fiscal_dispatch', 12)
            ->assertSee($note->note)
            ->assertSee($d5note->note);
    }

    public function test_every_matrix_cell_lists_its_rows_for_all_ages(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        foreach (range(1, 30) as $daysAgo) {
            $partial = $this->partial($company, $user, false);
            $partial->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
        }

        $service = app(PostWorkProcessReportService::class);
        $rows    = $service->rows(['from' => '2020-01-01', 'to' => now()->toDateString()]);
        $max     = PostWorkProcessReportService::MATRIX_MAX_DAY;

        foreach ($rows as $row) {
            $day    = min($row['age_days'], $max);
            $listed = $service->filterByCell($rows, $row['current_stage'], $day);
            $this->assertTrue($listed->contains('id', $row['id']), "age {$row['age_days']} not listed for cell {$day}");
        }
    }

    private function rows(string $type): \Illuminate\Support\Collection
    {
        return app(PostWorkProcessReportService::class)->rows(['type' => $type, 'from' => '2026-01-01', 'to' => '2026-12-31']);
    }

    private function actor(): array
    {
        $company = Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        return [$company, $user];
    }

    public function test_partial_stages_come_from_the_partial_own_fields(): void
    {
        [$company, $user] = $this->actor();

        $note    = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        $partial = Partial::create([
            'note_id'     => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'allow'       => true, 'decision_at' => '2026-10-02 09:00:00', 'engineer_id' => $user->id,
            'supervision' => true, 'supervision_at' => '2026-10-05 09:00:00', 'supervision_id' => $user->id,
            'payment'     => true, 'payment_at' => '2026-10-06 09:00:00', 'payment_id' => $user->id,
        ]);
        $partial->forceFill(['created_at' => '2026-10-01 10:00:00'])->save();

        $row = $this->rows('partial')->firstWhere('id', $partial->id);

        $this->assertSame('done', $row['current_stage']);
        $this->assertSame(1, $row['stages']['fiscal_dispatch']['days']);
        $this->assertSame(1, $row['stages']['fiscalization']['days']);
        $this->assertSame(1, $row['stages']['measurement']['days']);
        $this->assertSame(3, $row['stages']['total']['days']);
        $this->assertFalse($row['any_late']);
        $this->assertSame([], $row['issues']);
        // A chave `payment` guarda os marcos (array) e não a flag booleana do parcial.
        $this->assertIsArray($row['payment']);
        $this->assertTrue($row['payment_flag']);
    }

    public function test_d5_marked_as_paid_is_done_and_keeps_payment_milestones_as_array(): void
    {
        $this->actor();

        $note = Note::create(['note' => uniqid('5'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        $five = FiveNote::create([
            'note_id'          => $note->id, 'note_d5' => uniqid('D5'), 'is_completed' => true, 'completed_at' => '2026-10-01 10:00:00',
            'is_supervisioned' => true, 'supervisioned_at' => '2026-10-02 10:00:00',
            'is_payed'         => true, 'payed_at' => '2026-10-05 10:00:00',
        ]);

        $row = $this->rows('d5')->firstWhere('id', $five->id);

        $this->assertSame('done', $row['current_stage']);
        $this->assertIsArray($row['payment']);
        $this->assertSame('2026-10-05', $row['payment']['completed_at']->toDateString());
        $this->assertSame(2, $row['stages']['total']['days']);
        // Pagamento marcado na própria D5 não tem despacho: não vira "inconsistência" nem atraso de despacho.
        $this->assertSame([], $row['issues']);
        $this->assertNull($row['dispatch_gap']);
    }

    public function test_final_uses_the_note_inform_flows_productions(): void
    {
        [$company, $user] = $this->actor();

        $note   = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        $report = WorkReport::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'date'    => '2026-10-01', 'informed_at' => '2026-10-01 10:00:00', 'rejected' => false, 'canceled' => false,
        ]);
        $fiscal  = Service::create(['service' => 'Fiscalização']);
        $payment = Service::create(['service' => 'Pagamento']);

        $make = fn (Service $service, string $dispatch, ?string $done) => Production::create([
            'note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'user_id' => $user->id,
            'att_at'  => $dispatch, 'dispatch_at' => $dispatch, 'completed' => (bool) $done, 'completed_at' => $done, 'partial' => false,
        ]);
        $old = $make($fiscal, '2026-09-01 09:00:00', '2026-09-03 09:00:00');
        $cur = $make($fiscal, '2026-10-02 09:00:00', '2026-10-05 09:00:00');
        $pay = $make($payment, '2026-10-05 10:00:00', '2026-10-07 10:00:00');

        // O vínculo do informe aponta a produção antiga; o consolidado (fonte do log SAP) aponta a correta.
        DB::table('work_report_flow_productions')->insert([
            'work_report_id' => $report->id, 'production_id' => $old->id, 'stage' => 'fiscalization',
            'is_current'     => 1, 'linked_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('note_inform_flows')->insert([
            'note_id'    => $note->id, 'flow_type' => 'final', 'work_report_id' => $report->id, 'flow_key' => "final:{$report->id}:general",
            'active'     => 1, 'fiscalization_production_id' => $cur->id, 'measurement_production_id' => $pay->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $row = $this->rows('final')->firstWhere('id', $report->id);

        $this->assertSame($cur->id, $row['fiscal']['id']);
        $this->assertSame($pay->id, $row['payment']['id']);
        $this->assertSame('note_inform_flows', $row['data_source']);
        $this->assertSame('done', $row['current_stage']);
    }

    public function test_summary_separates_closed_from_open_and_flags_inconsistent_dates(): void
    {
        [$company, $user] = $this->actor();

        $mk = function (array $attrs) use ($company, $user) {
            $note    = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
            $partial = Partial::create(array_merge(['note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id, 'allow' => true], $attrs['fields']));
            $partial->forceFill(['created_at' => $attrs['created_at']])->save();

            return $partial;
        };

        // Concluído no prazo, concluído fora do prazo e em aberto estourado.
        $mk(['created_at' => '2026-10-01 10:00:00', 'fields' => ['decision_at' => '2026-10-02 09:00:00', 'supervision_at' => '2026-10-05 09:00:00', 'payment_at' => '2026-10-06 09:00:00']]);
        $mk(['created_at' => '2026-03-02 10:00:00', 'fields' => ['decision_at' => '2026-03-20 09:00:00', 'supervision_at' => '2026-04-20 09:00:00', 'payment_at' => '2026-05-20 09:00:00']]);
        $mk(['created_at' => '2026-03-02 10:00:00', 'fields' => []]);
        // Pagamento com fiscalização em aberto.
        $bad = $mk(['created_at' => '2026-10-01 10:00:00', 'fields' => ['payment_at' => '2026-10-03 09:00:00']]);

        $service = app(PostWorkProcessReportService::class);
        $rows    = $service->rows(['type' => 'partial', 'from' => '2026-01-01', 'to' => '2026-12-31']);
        $summary = $service->summarize($rows);

        $this->assertSame(4, $summary['total']);
        $this->assertSame(3, $summary['done']);
        $this->assertSame(1, $summary['done_on_time']);
        $this->assertSame(2, $summary['done_late']);
        $this->assertSame(1, $summary['open']);
        $this->assertSame(1, $summary['open_late']);
        $this->assertSame(67, $summary['late_rate_done']);
        $this->assertSame(1, $summary['with_issues']);
        $this->assertNotEmpty($rows->firstWhere('id', $bad->id)['issues']);
    }

    public function test_gantt_and_table_views_render_milestones_for_every_type(): void
    {
        [$company, $user] = $this->actor();

        $note = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        Partial::create([
            'note_id'     => $note->id, 'company_id' => $company->id, 'user_id' => $user->id, 'allow' => true,
            'decision_at' => '2026-10-02 09:00:00', 'supervision_at' => '2026-10-05 09:00:00', 'payment_at' => '2026-10-06 09:00:00',
        ])->forceFill(['created_at' => '2026-10-01 10:00:00'])->save();

        foreach (['gantt', 'table', 'matrix'] as $view) {
            Livewire::test(PostWorkProcessReport::class, ['from' => '2026-01-01', 'to' => '2026-12-31'])
                ->set('view', $view)
                ->assertSee('Concluídos no prazo')
                ->assertSee('Dados inconsistentes');
        }
    }
}
