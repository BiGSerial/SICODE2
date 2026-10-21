<?php

namespace Tests\Feature;

use App\Models\{Analise, Company, FiveNote, Note, Partial, Production, Service, User, WorkReport};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Storage};
use Tests\TestCase;

class RetrofillLegacyWorkReportD5Test extends TestCase
{
    use RefreshDatabase;

    private Note $note;

    private Service $fiscal;

    private Service $payment;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->note    = Note::create(['note' => uniqid('500'), 'dt_status' => '2026-09-10 08:00:00', 'nstats' => 'NEW']);
        $this->fiscal  = Service::create(['service' => 'Fiscalização', 'folder' => 'fiscalizacao']);
        $this->payment = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);
        $this->company = Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);
    }

    private function run_(array $options = []): void
    {
        $this->artisan('sicode:retrofill-legacy-work-report-d5', ['--note_id' => $this->note->id] + $options)->assertSuccessful();
    }

    private function production(Service $service, array $attrs = []): Production
    {
        return Production::create($attrs + [
            'company_id' => $this->company->id, 'note_id' => $this->note->id, 'service_id' => $service->uuid,
            'completed'  => false, 'confirmed' => false, 'status' => 2, 'dfive' => false,
        ]);
    }

    private function report(string $informedAt): WorkReport
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        return WorkReport::create([
            'note_id' => $this->note->id, 'company_id' => $this->company->id, 'user_id' => $user->id,
            'date'    => substr($informedAt, 0, 10), 'informed_at' => $informedAt, 'rejected' => false, 'canceled' => false,
        ]);
    }

    private function partial(array $attrs): Partial
    {
        return Partial::create($attrs + ['note_id' => $this->note->id, 'scope' => 'general', 'allow' => true, 'deny' => false]);
    }

    public function test_partial_fiscalization_links_respecting_the_decision_floor_and_fixes_supervision_date(): void
    {
        $partial = $this->partial(['decision_at' => '2026-03-01 10:00:00', 'supervision' => true, 'supervision_at' => '2026-03-20 10:00:00']);
        $before  = $this->production($this->fiscal, ['partial' => true, 'dispatch_at' => '2026-02-28 09:00:00']);
        $after   = $this->production($this->fiscal, ['partial' => true, 'completed' => true, 'dispatch_at' => '2026-03-05 09:00:00', 'completed_at' => '2026-03-12 16:30:00']);

        $this->run_(['--apply' => true]);

        $this->assertSame([$after->id], $partial->productions()->pluck('productions.id')->all());
        $this->assertFalse($partial->productions()->whereKey($before->id)->exists());
        $this->assertSame('2026-03-12 16:30:00', $partial->fresh()->supervision_at->format('Y-m-d H:i:s'));
    }

    public function test_dry_run_changes_nothing(): void
    {
        $partial = $this->partial(['decision_at' => '2026-03-01 10:00:00']);
        $this->production($this->fiscal, ['partial' => true, 'dispatch_at' => '2026-03-05 09:00:00']);

        $this->run_();

        $this->assertSame(0, $partial->productions()->count());
    }

    public function test_final_fiscalization_is_linked_to_the_informe_only_after_its_date(): void
    {
        $report = $this->report('2026-03-10 08:00:00');
        $early  = $this->production($this->fiscal, ['dispatch_at' => '2026-03-09 08:00:00']);
        $ok     = $this->production($this->fiscal, ['dispatch_at' => '2026-03-15 08:00:00']);

        $this->run_(['--apply' => true]);

        $this->assertDatabaseMissing('work_report_flow_productions', ['production_id' => $early->id]);
        $this->assertDatabaseHas('work_report_flow_productions', [
            'work_report_id' => $report->id, 'production_id' => $ok->id, 'stage' => 'fiscalization', 'is_current' => true, 'source' => 'retrofill_legacy',
        ]);
    }

    public function test_informe_with_rejection_also_considers_created_at_as_floor(): void
    {
        $report = $this->report('2026-03-20 08:00:00');
        $report->forceFill(['retry' => 1, 'created_at' => '2026-03-10 08:00:00'])->save();
        $production = $this->production($this->fiscal, ['dispatch_at' => '2026-03-12 08:00:00']);

        $this->run_(['--apply' => true]);

        $this->assertDatabaseHas('work_report_flow_productions', ['work_report_id' => $report->id, 'production_id' => $production->id]);
    }

    public function test_informe_without_rejection_keeps_informed_at_as_floor(): void
    {
        $report = $this->report('2026-03-20 08:00:00');
        $report->forceFill(['created_at' => '2026-03-10 08:00:00'])->save();
        $production = $this->production($this->fiscal, ['dispatch_at' => '2026-03-12 08:00:00']);

        $this->run_(['--apply' => true]);

        $this->assertDatabaseMissing('work_report_flow_productions', ['production_id' => $production->id]);
    }

    /**
     * Sequência: C1 (Fiscalização cria a D5) → M1 (Pagamento libera a empreiteira) → empreiteira conclui →
     * C2 (Fiscalização) → M2 (Pagamento). Cada elo só entra se você pedir (para testar o ponto em que a cadeia para).
     *
     * @return array<string, mixed>
     */
    private function chain(array $with = ['m1', 'partner', 'c2', 'm2'], string $conclusion = 'FISCALIZADO SEM PENDENCIAS'): array
    {
        $report = WorkReport::query()->where('note_id', $this->note->id)->first() ?? $this->report('2026-02-20 08:00:00');
        $c1     = $this->production($this->fiscal, ['completed' => true, 'dispatch_at' => '2026-03-01 08:00:00', 'completed_at' => '2026-03-05 10:00:00']);
        $five   = FiveNote::create([
            'note_id'      => $this->note->id, 'note_d5' => uniqid('D5'), 'work_report_id' => $report->id,
            'is_completed' => in_array('partner', $with, true), 'completed_at' => in_array('partner', $with, true) ? '2026-03-20 10:00:00' : null,
        ]);
        $five->forceFill(['created_at' => '2026-03-05 10:00:00'])->save();
        $five->productions()->attach($c1->id);

        $chain = ['report' => $report, 'five' => $five, 'c1' => $c1];

        if (in_array('m1', $with, true)) {
            $chain['m1'] = $this->production($this->payment, ['completed' => true, 'dispatch_at' => '2026-03-06 08:00:00', 'completed_at' => '2026-03-07 12:00:00']);
        }

        if (in_array('c2', $with, true)) {
            $chain['c2'] = $this->production($this->fiscal, ['completed' => true, 'dispatch_at' => '2026-03-22 09:00:00', 'completed_at' => '2026-03-25 14:00:00']);
            Analise::create(['production_id' => $chain['c2']->id, 'conclusion' => $conclusion]);
        }

        if (in_array('m2', $with, true)) {
            $chain['m2'] = $this->production($this->payment, ['completed' => true, 'dispatch_at' => '2026-03-26 08:00:00', 'completed_at' => '2026-03-27 11:00:00']);
        }

        return $chain;
    }

    public function test_full_d5_cycle_marks_every_step_and_archives(): void
    {
        $chain = $this->chain();

        $this->run_(['--apply' => true]);

        $five = $chain['five']->fresh();

        $this->assertFalse((bool) $chain['c1']->fresh()->dfive, 'o ciclo 1 da Fiscalização nunca é dfive');
        $this->assertTrue((bool) $chain['m1']->fresh()->dfive);
        $this->assertTrue((bool) $chain['c2']->fresh()->dfive);
        $this->assertTrue((bool) $chain['m2']->fresh()->dfive);

        $this->assertTrue($five->visible_partner);
        $this->assertTrue($five->is_payed);
        $this->assertSame('2026-03-07 12:00:00', $five->dispatch_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-07 12:00:00', $five->payed_at->format('Y-m-d H:i:s'));
        $this->assertTrue($five->is_supervisioned);
        $this->assertSame('2026-03-25 14:00:00', $five->supervisioned_at->format('Y-m-d H:i:s'));
        $this->assertTrue($five->is_archived);
    }

    public function test_cycle_stops_at_the_first_missing_step(): void
    {
        $chain = $this->chain(['m1', 'partner']);

        $this->run_(['--apply' => true]);

        $five = $chain['five']->fresh();
        $this->assertTrue($five->is_payed);
        $this->assertFalse($five->is_supervisioned);
        $this->assertFalse($five->is_archived);
    }

    public function test_without_cycle_1_payment_nothing_is_released(): void
    {
        $chain = $this->chain([]);

        $this->run_(['--apply' => true]);

        $this->assertFalse($chain['five']->fresh()->is_payed);
    }

    public function test_supervised_d5_without_cycle_2_payment_is_not_archived(): void
    {
        $chain = $this->chain(['m1', 'partner', 'c2']);

        $this->run_(['--apply' => true]);

        $five = $chain['five']->fresh();
        $this->assertTrue($five->is_supervisioned);
        $this->assertFalse($five->is_archived);
    }

    public function test_open_cycle_2_payment_is_marked_dfive_but_does_not_archive(): void
    {
        $chain = $this->chain(['m1', 'partner', 'c2']);
        $open  = $this->production($this->payment, ['completed' => false, 'dispatch_at' => '2026-03-26 08:00:00']);

        $this->run_(['--apply' => true]);

        $this->assertTrue((bool) $open->fresh()->dfive);
        $this->assertFalse($chain['five']->fresh()->is_archived);
    }

    public function test_cycle_2_fiscalization_with_pending_conclusion_returns_the_d5(): void
    {
        $chain = $this->chain(['m1', 'partner', 'c2'], 'FISCALIZADO COM PENDENCIAS');

        $this->run_(['--apply' => true]);

        $five = $chain['five']->fresh();
        $this->assertFalse($five->is_supervisioned);
        $this->assertTrue($five->returned);
        $this->assertFalse($five->is_completed);
    }

    public function test_fiscalization_dispatched_before_the_partner_completion_is_not_cycle_2(): void
    {
        $chain = $this->chain(['m1', 'partner']);
        $early = $this->production($this->fiscal, ['completed' => true, 'dispatch_at' => '2026-03-10 08:00:00', 'completed_at' => '2026-03-11 08:00:00']);
        Analise::create(['production_id' => $early->id, 'conclusion' => 'FISCALIZADO SEM PENDENCIAS']);

        $this->run_(['--apply' => true]);

        $this->assertFalse($chain['five']->fresh()->is_supervisioned);
        $this->assertFalse((bool) $early->fresh()->dfive);
    }

    public function test_production_inside_a_canceled_informe_window_links_to_it_and_the_rest_to_the_active_one(): void
    {
        $canceled = $this->report('2026-03-01 08:00:00');
        $canceled->forceFill(['canceled' => true, 'canceled_at' => '2026-03-20 08:00:00'])->save();
        $active = $this->report('2026-03-25 08:00:00');

        $old = $this->production($this->fiscal, ['dispatch_at' => '2026-03-10 08:00:00']);
        $new = $this->production($this->fiscal, ['dispatch_at' => '2026-03-28 08:00:00']);

        $this->run_(['--apply' => true]);

        $this->assertDatabaseHas('work_report_flow_productions', ['production_id' => $old->id, 'work_report_id' => $canceled->id]);
        $this->assertDatabaseHas('work_report_flow_productions', ['production_id' => $new->id, 'work_report_id' => $active->id]);
    }

    public function test_d5_created_inside_a_canceled_informe_window_is_associated_to_it(): void
    {
        $canceled = $this->report('2026-03-01 08:00:00');
        $canceled->forceFill(['canceled' => true, 'canceled_at' => '2026-03-20 08:00:00'])->save();
        $this->report('2026-03-25 08:00:00');

        $five = FiveNote::create(['note_id' => $this->note->id, 'note_d5' => uniqid('D5')]);
        // legada: o model associa ao informe vigente na criação; aqui simulamos a D5 ainda sem informe
        DB::table('five_notes')->where('id', $five->id)->update(['work_report_id' => null, 'created_at' => '2026-03-10 08:00:00']);

        $this->run_(['--apply' => true]);

        $this->assertSame($canceled->id, $five->fresh()->work_report_id);
    }

    public function test_partial_is_matched_by_completion_date_when_dispatch_order_alone_is_ambiguous(): void
    {
        $this->partial(['decision_at' => '2026-03-01 10:00:00', 'supervision' => true, 'supervision_at' => '2026-03-08 10:00:00']);
        $second = $this->partial(['decision_at' => '2026-03-10 10:00:00', 'supervision' => true, 'supervision_at' => '2026-03-18 15:00:00']);
        $prod   = $this->production($this->fiscal, ['partial' => true, 'completed' => true, 'dispatch_at' => '2026-03-20 09:00:00', 'completed_at' => '2026-03-18 15:01:00']);

        $this->run_(['--apply' => true]);

        $this->assertSame([$prod->id], $second->productions()->pluck('productions.id')->all());
    }

    public function test_passive_d5_without_informe_starts_at_the_partner_completion(): void
    {
        $five = FiveNote::create(['note_id' => $this->note->id, 'note_d5' => uniqid('D5'), 'isPassive' => true, 'is_completed' => true, 'completed_at' => '2026-03-20 10:00:00']);
        $c2   = $this->production($this->fiscal, ['completed' => true, 'dispatch_at' => '2026-03-22 09:00:00', 'completed_at' => '2026-03-25 14:00:00']);
        Analise::create(['production_id' => $c2->id, 'conclusion' => 'FISCALIZADO SEM PENDENCIAS']);
        $m2 = $this->production($this->payment, ['completed' => true, 'dispatch_at' => '2026-03-26 08:00:00', 'completed_at' => '2026-03-27 11:00:00']);

        $this->run_(['--apply' => true]);

        $five->refresh();
        $this->assertNull($five->work_report_id);
        $this->assertTrue((bool) $c2->fresh()->dfive);
        $this->assertTrue((bool) $m2->fresh()->dfive);
        $this->assertSame('2026-03-25 14:00:00', $five->supervisioned_at->format('Y-m-d H:i:s'));
        $this->assertTrue($five->is_archived);
    }

    public function test_passive_d5_ignores_productions_before_the_partner_completion(): void
    {
        $five  = FiveNote::create(['note_id' => $this->note->id, 'note_d5' => uniqid('D5'), 'isPassive' => true, 'is_completed' => true, 'completed_at' => '2026-03-20 10:00:00']);
        $early = $this->production($this->fiscal, ['completed' => true, 'dispatch_at' => '2026-03-10 09:00:00', 'completed_at' => '2026-03-11 14:00:00']);
        Analise::create(['production_id' => $early->id, 'conclusion' => 'FISCALIZADO SEM PENDENCIAS']);

        $this->run_(['--apply' => true]);

        $this->assertFalse($five->fresh()->is_supervisioned);
        $this->assertFalse((bool) $early->fresh()->dfive);
    }

    private function passiveFive(array $attrs = []): FiveNote
    {
        $five = FiveNote::create($attrs + ['note_id' => $this->note->id, 'note_d5' => uniqid('D5'), 'isPassive' => true]);
        DB::table('five_notes')->where('id', $five->id)->update(['work_report_id' => null]);

        return $five->fresh();
    }

    public function test_passive_d5_is_associated_to_the_informe_that_precedes_its_completion(): void
    {
        $report = $this->report('2026-03-01 08:00:00');
        $five   = $this->passiveFive(['is_completed' => true, 'completed_at' => '2026-03-20 10:00:00']);

        $this->run_(['--apply' => true]);

        $this->assertSame($report->id, $five->fresh()->work_report_id);
    }

    public function test_passive_d5_is_not_associated_to_an_informe_created_after_it(): void
    {
        $this->report('2026-04-01 08:00:00');
        $five = $this->passiveFive(['is_completed' => true, 'completed_at' => '2026-03-20 10:00:00']);

        $this->run_(['--apply' => true]);

        $this->assertNull($five->fresh()->work_report_id);
    }

    public function test_passive_d5_takes_the_informe_already_used_by_its_cycle_productions_and_links_them(): void
    {
        $report = $this->report('2026-03-01 08:00:00');
        $five   = $this->passiveFive(['is_completed' => true, 'completed_at' => '2026-03-20 10:00:00']);
        $c2     = $this->production($this->fiscal, ['completed' => true, 'dispatch_at' => '2026-03-22 09:00:00', 'completed_at' => '2026-03-25 14:00:00']);
        Analise::create(['production_id' => $c2->id, 'conclusion' => 'FISCALIZADO SEM PENDENCIAS']);
        DB::table('work_report_flow_productions')->insert([
            'work_report_id' => $report->id, 'production_id' => $c2->id, 'stage' => 'fiscalization', 'final_scope' => 'general',
            'is_current'     => true, 'source' => 'dispatch_workflow', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->run_(['--apply' => true]);

        $five->refresh();
        $this->assertSame($report->id, $five->work_report_id);
        $this->assertTrue((bool) $c2->fresh()->dfive);
        $this->assertTrue($five->productions()->whereKey($c2->id)->exists());
        $this->assertTrue($five->is_supervisioned);
    }

    public function test_running_twice_is_idempotent(): void
    {
        $report = $this->report('2026-03-10 08:00:00');
        $this->production($this->fiscal, ['dispatch_at' => '2026-03-15 08:00:00']);

        $this->run_(['--apply' => true]);
        $this->run_(['--apply' => true]);

        $this->assertSame(1, DB::table('work_report_flow_productions')->where('work_report_id', $report->id)->count());
    }

    private function applyAndGetJournal(): string
    {
        $this->run_(['--apply' => true, '--report' => 'retrofill/t.csv']);

        return 'retrofill/t-journal.jsonl';
    }

    private function seedEverything(): array
    {
        $report  = $this->report('2026-03-10 08:00:00');
        $partial = $this->partial(['decision_at' => '2026-03-01 10:00:00', 'supervision' => true, 'supervision_at' => '2026-03-20 10:00:00']);
        $this->production($this->fiscal, ['partial' => true, 'completed' => true, 'dispatch_at' => '2026-03-05 09:00:00', 'completed_at' => '2026-03-12 16:30:00']);
        $final = $this->production($this->fiscal, ['dispatch_at' => '2026-03-15 08:00:00']);
        $five  = $this->chain(['m1', 'partner', 'c2'])['five'];

        return [$report, $partial, $final, $five];
    }

    public function test_rollback_restores_everything_the_retrofill_changed(): void
    {
        [$report, $partial, $final, $five] = $this->seedEverything();
        $events                            = DB::table('timeline_events')->count();

        $journal = $this->applyAndGetJournal();

        $this->assertTrue($five->fresh()->is_supervisioned);
        $this->assertSame(1, $partial->productions()->count());
        $this->assertSame(1, DB::table('work_report_flow_productions')->where('production_id', $final->id)->count());

        $this->artisan('sicode:rollback-legacy-work-report-d5', ['journal' => $journal, '--apply' => true])->assertSuccessful();

        $five->refresh();
        $this->assertFalse($five->is_supervisioned);
        $this->assertNull($five->supervisioned_at);
        $this->assertSame(0, $partial->productions()->count());
        $this->assertSame('2026-03-20 10:00:00', $partial->fresh()->supervision_at->format('Y-m-d H:i:s'));
        $this->assertSame(0, DB::table('work_report_flow_productions')->where('production_id', $final->id)->count());
        $this->assertSame($events, DB::table('timeline_events')->count());
    }

    public function test_rollback_dry_run_changes_nothing(): void
    {
        [, , $final, $five] = $this->seedEverything();
        $journal            = $this->applyAndGetJournal();

        $this->artisan('sicode:rollback-legacy-work-report-d5', ['journal' => $journal])->assertSuccessful();

        $this->assertTrue($five->fresh()->is_supervisioned);
        $this->assertSame(1, DB::table('work_report_flow_productions')->where('production_id', $final->id)->count());
    }

    public function test_rollback_preserves_rows_changed_after_the_retrofill(): void
    {
        [, , , $five] = $this->seedEverything();
        $journal      = $this->applyAndGetJournal();

        $five->forceFill(['supervisioned_at' => '2026-05-01 12:00:00'])->save();

        $this->artisan('sicode:rollback-legacy-work-report-d5', ['journal' => $journal, '--apply' => true])->assertSuccessful();

        $five->refresh();
        $this->assertTrue($five->is_supervisioned);
        $this->assertSame('2026-05-01 12:00:00', $five->supervisioned_at->format('Y-m-d H:i:s'));
        Storage::disk('local')->assertExists('retrofill/t-journal-rollback-conflitos.csv');
    }

    public function test_rollback_cannot_run_twice(): void
    {
        $this->seedEverything();
        $journal = $this->applyAndGetJournal();

        $this->artisan('sicode:rollback-legacy-work-report-d5', ['journal' => $journal, '--apply' => true])->assertSuccessful();
        $this->artisan('sicode:rollback-legacy-work-report-d5', ['journal' => $journal, '--apply' => true])->assertFailed();
    }
}
