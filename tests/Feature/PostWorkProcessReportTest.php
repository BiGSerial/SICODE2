<?php

namespace Tests\Feature;

use App\Http\Livewire\Reports\PostWorkProcessReport;
use App\Models\{Adsform, Company, FiveNote, Note, Partial, Production, Service, SystemSetting, User, WorkReport};
use App\Support\SicodeRules;
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
        WorkReport::create([
            'note_id' => $d5note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'date'    => '2026-03-01', 'informed_at' => '2026-03-01 10:00:00', 'rejected' => false, 'canceled' => false,
        ]);
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
        [$company, $user] = $this->actor();

        $note = Note::create(['note' => uniqid('5'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        WorkReport::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'date'    => '2026-09-30', 'informed_at' => '2026-09-30 10:00:00', 'rejected' => false, 'canceled' => false,
        ]);
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

    public function test_only_final_follows_the_deadline_and_d5_without_informe_is_hidden(): void
    {
        [$company, $user] = $this->actor();

        $note   = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        $report = WorkReport::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'date'    => '2026-01-05', 'informed_at' => '2026-01-05 10:00:00', 'rejected' => false, 'canceled' => false,
        ]);
        $withInform    = FiveNote::create(['note_id' => $note->id, 'work_report_id' => $report->id, 'company_id' => $company->id, 'note_d5' => '17600001', 'completed_at' => '2026-01-20 10:00:00']);
        $orphanNote    = Note::create(['note' => uniqid('5'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        $withoutInform = FiveNote::create(['note_id' => $orphanNote->id, 'company_id' => $company->id, 'note_d5' => '17600002', 'completed_at' => '2026-01-21 10:00:00']);

        $final = $this->rows('final')->firstWhere('id', $report->id);

        // Informe final com D5 segue o prazo regulatório (total estourado desde janeiro).
        $this->assertTrue($final['deadline_applies']);
        $this->assertTrue($final['any_late']);

        $d5 = $this->rows('d5');

        $this->assertNotNull($d5->firstWhere('id', $withInform->id));
        $this->assertFalse($d5->firstWhere('id', $withInform->id)['deadline_applies']);
        $this->assertFalse($d5->firstWhere('id', $withInform->id)['any_late']);
        $this->assertNull($d5->firstWhere('id', $withoutInform->id));
    }

    public function test_start_can_be_the_ads_delivery_date_and_limits_are_user_parameterizable(): void
    {
        [$company, $user] = $this->actor();

        $note   = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        $report = WorkReport::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'date'    => '2026-01-05', 'informed_at' => '2026-01-05 10:00:00', 'rejected' => false, 'canceled' => false,
        ]);
        $ads = Adsform::create(['work_report_id' => $report->id, 'note_id' => $note->id, 'user_id' => $user->id, 'amount' => 10, 'partial' => false, 'tacit' => true, 'tacit_delivered_at' => '2026-01-06 08:00:00']);
        DB::table('adsforms')->where('id', $ads->id)->update(['created_at' => '2026-01-08 09:00:00']);

        $this->assertSame('2026-01-05 10:00:00', $this->rows('final')->firstWhere('id', $report->id)['start_at']->format('Y-m-d H:i:s'));

        SystemSetting::setValue(SicodeRules::databaseRuleKey('post_work_process.start_basis'), SicodeRules::POST_WORK_PROCESS_START_ADS);
        SystemSetting::setValue(SicodeRules::databaseRuleKey('post_work_process.limits.fiscal_dispatch'), '5');

        $row = $this->rows('final')->firstWhere('id', $report->id);

        // Data de entrega da ADS (created_at), nunca tacit_delivered_at.
        $this->assertSame('2026-01-08 09:00:00', $row['start_at']->format('Y-m-d H:i:s'));
        $this->assertSame(5, $row['stages']['fiscal_dispatch']['limit']);
    }

    public function test_d5_row_shows_only_d5_data_and_not_the_informe_productions(): void
    {
        [$company, $user] = $this->actor();

        $note   = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        $report = WorkReport::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'date'    => '2026-09-10', 'informed_at' => '2026-09-10 10:00:00', 'rejected' => false, 'canceled' => false,
        ]);
        $five    = FiveNote::create(['note_id' => $note->id, 'work_report_id' => $report->id, 'company_id' => $company->id, 'note_d5' => '17600009', 'completed_at' => '2026-10-29 10:59:00']);
        $fiscal  = Service::create(['service' => 'Fiscalização']);
        $payment = Service::create(['service' => 'Medição']);

        $make = fn (Service $service, string $dispatch, string $done, bool $dfive) => Production::create([
            'note_id' => $note->id, 'service_id' => $service->uuid, 'company_id' => $company->id, 'user_id' => $user->id,
            'att_at'  => $dispatch, 'dispatch_at' => $dispatch, 'completed' => true, 'completed_at' => $done, 'partial' => false, 'dfive' => $dfive,
        ]);
        $f = $make($fiscal, '2026-09-16 09:00:00', '2026-09-18 11:00:00', false);
        $p = $make($payment, '2026-10-09 08:00:00', '2026-10-09 13:00:00', true);

        foreach ([[$f, 'fiscalization'], [$p, 'payment']] as [$production, $stage]) {
            DB::table('work_report_flow_productions')->insert([
                'work_report_id' => $report->id, 'production_id' => $production->id, 'stage' => $stage,
                'is_current'     => 1, 'linked_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $row = $this->rows('d5')->firstWhere('id', $five->id);

        $this->assertNull($row['fiscal']);
        $this->assertNull($row['payment']);
        $this->assertSame('2026-10-29', $row['start_at']->toDateString());
        $this->assertFalse($row['any_late']);

        $own = $make($fiscal, '2026-10-30 09:00:00', '2026-10-30 15:00:00', false);
        $five->productions()->attach($own->id);

        $this->assertSame($own->id, $this->rows('d5')->firstWhere('id', $five->id)['fiscal']['id']);
    }

    public function test_params_panel_renders_and_persists_limits(): void
    {
        [, $user] = $this->actor();
        \Illuminate\Support\Facades\Gate::define('management', fn () => true);

        Livewire::test(PostWorkProcessReport::class)
            ->set('showParams', true)
            ->assertSee('Parâmetros do relatório')
            ->set('params.total', 10)
            ->set('params.start_basis', 'ads_delivery')
            ->call('saveParams')
            ->assertHasNoErrors();

        $this->assertSame(10, app(PostWorkProcessReportService::class)->limits()['total']);
        $this->assertSame('ads_delivery', SicodeRules::postWorkProcessStartBasis());
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

        // Parcial não segue prazo regulatório: nunca estoura, mas inconsistências continuam sendo apontadas.
        $mk(['created_at' => '2026-03-02 10:00:00', 'fields' => ['decision_at' => '2026-03-20 09:00:00', 'supervision_at' => '2026-04-20 09:00:00', 'payment_at' => '2026-05-20 09:00:00']]);
        $bad = $mk(['created_at' => '2026-10-01 10:00:00', 'fields' => ['payment_at' => '2026-10-03 09:00:00']]);

        $service = app(PostWorkProcessReportService::class);
        $rows    = $service->rows(['type' => 'partial', 'from' => '2026-01-01', 'to' => '2026-12-31']);

        $this->assertFalse($rows->contains('any_late', true));
        $this->assertSame(1, $service->summarize($rows)['with_issues']);

        // Contas do resumo: concluídos no prazo/fora e em aberto estourado/no prazo.
        $summary = $service->summarize(collect([
            ['current_stage' => 'done', 'any_late' => false, 'issues' => [], 'type' => 'final'],
            ['current_stage' => 'done', 'any_late' => true, 'issues' => [], 'type' => 'final'],
            ['current_stage' => 'done', 'any_late' => true, 'issues' => [], 'type' => 'final'],
            ['current_stage' => 'in_fiscalization', 'any_late' => true, 'issues' => [], 'type' => 'final'],
        ]));

        $this->assertSame(4, $summary['total']);
        $this->assertSame(3, $summary['done']);
        $this->assertSame(1, $summary['done_on_time']);
        $this->assertSame(2, $summary['done_late']);
        $this->assertSame(1, $summary['open']);
        $this->assertSame(1, $summary['open_late']);
        $this->assertSame(67, $summary['late_rate_done']);
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
