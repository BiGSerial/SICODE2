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

    public function test_d5_without_pending_issues_is_supervised_with_the_production_completion_date(): void
    {
        $report = $this->report('2026-03-10 08:00:00');
        $five   = FiveNote::create(['note_id' => $this->note->id, 'note_d5' => uniqid('D5'), 'work_report_id' => $report->id, 'is_completed' => true, 'completed_at' => '2026-03-20 10:00:00', 'visible_partner' => true]);
        $prod   = $this->production($this->fiscal, ['dfive' => true, 'completed' => true, 'dispatch_at' => '2026-03-22 09:00:00', 'completed_at' => '2026-03-25 14:00:00']);
        $five->productions()->attach($prod->id);
        Analise::create(['production_id' => $prod->id, 'conclusion' => 'FISCALIZADO SEM PENDENCIAS']);

        $this->run_(['--apply' => true]);

        $five->refresh();
        $this->assertTrue($five->is_supervisioned);
        $this->assertSame('2026-03-25 14:00:00', $five->supervisioned_at->format('Y-m-d H:i:s'));
        $this->assertTrue($five->isEligibleForPaymentReturn());
    }

    public function test_d5_with_pending_conclusion_is_returned_not_supervised(): void
    {
        $five = FiveNote::create(['note_id' => $this->note->id, 'note_d5' => uniqid('D5'), 'visible_partner' => true]);
        $prod = $this->production($this->fiscal, ['dfive' => true, 'completed' => true, 'dispatch_at' => '2026-03-22 09:00:00', 'completed_at' => '2026-03-25 14:00:00']);
        $five->productions()->attach($prod->id);
        Analise::create(['production_id' => $prod->id, 'conclusion' => 'FISCALIZADO COM PENDENCIAS']);

        $this->run_(['--apply' => true]);

        $five->refresh();
        $this->assertFalse($five->is_supervisioned);
        $this->assertTrue($five->returned);
        $this->assertFalse($five->is_completed);
    }

    public function test_d5_whose_fiscalization_predates_the_partner_completion_is_left_alone(): void
    {
        $five = FiveNote::create(['note_id' => $this->note->id, 'note_d5' => uniqid('D5'), 'is_completed' => true, 'completed_at' => '2026-04-01 10:00:00', 'visible_partner' => true]);
        $prod = $this->production($this->fiscal, ['dfive' => true, 'completed' => true, 'dispatch_at' => '2026-03-22 09:00:00', 'completed_at' => '2026-03-25 14:00:00']);
        $five->productions()->attach($prod->id);
        Analise::create(['production_id' => $prod->id, 'conclusion' => 'FISCALIZADO SEM PENDENCIAS']);

        $this->run_(['--apply' => true]);

        $this->assertFalse($five->fresh()->is_supervisioned);
    }

    public function test_running_twice_is_idempotent(): void
    {
        $report = $this->report('2026-03-10 08:00:00');
        $this->production($this->fiscal, ['dispatch_at' => '2026-03-15 08:00:00']);

        $this->run_(['--apply' => true]);
        $this->run_(['--apply' => true]);

        $this->assertSame(1, DB::table('work_report_flow_productions')->where('work_report_id', $report->id)->count());
    }
}
