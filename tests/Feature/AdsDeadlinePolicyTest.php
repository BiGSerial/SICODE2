<?php

namespace Tests\Feature;

use App\Models\{AdsNonWorkingDayAdjustment, Adsform, Company, Holiday, Note, User, WorkReport};
use App\Services\Ads\AdsDeadlinePolicy;
use App\Services\Holidays\{HolidayImportService, NormalizeImportedHoliday};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdsDeadlinePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_date_counts_three_business_days_skipping_weekend_and_holiday(): void
    {
        Holiday::create([
            'state'       => 'ES',
            'year'        => 2026,
            'date'        => '2026-04-06',
            'name'        => 'Feriado estadual',
            'type'        => 'ESTADUAL',
            'source'      => 'feriados_api',
            'imported_at' => now(),
        ]);

        $policy = app(AdsDeadlinePolicy::class);
        $dueAt  = $policy->dueAt(Carbon::parse('2026-04-03 10:00:00'), 'ES');

        $this->assertSame('2026-04-09 23:59:59', $dueAt->format('Y-m-d H:i:s'));
    }

    public function test_sp_ruleset_counts_calendar_days_from_registration_day_zero(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $policy = app(AdsDeadlinePolicy::class);
        // Sexta-feira + 3 dias corridos = segunda-feira.
        $dueAt = $policy->dueAt(Carbon::parse('2026-04-03 10:00:00'), 'ES');

        $this->assertSame('2026-04-06 23:59:59', $dueAt->format('Y-m-d H:i:s'));
    }

    public function test_work_report_specific_adjustment_is_not_counted_as_business_day(): void
    {
        $user       = User::factory()->create();
        $company    = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $note       = Note::create(['note' => '4000000001']);
        $workReport = WorkReport::create([
            'note_id'     => $note->id,
            'company_id'  => $company->id,
            'user_id'     => $user->id,
            'informed_at' => '2026-04-01 08:00:00',
            'date'        => '2026-04-01',
        ]);

        AdsNonWorkingDayAdjustment::create([
            'work_report_id' => $workReport->id,
            'date'           => '2026-04-02',
            'reason'         => 'Ocorrência específica abonada pela engenharia.',
            'created_by'     => $user->id,
        ]);

        $policy = app(AdsDeadlinePolicy::class);
        $dueAt  = $policy->dueAt($workReport->informed_at, 'ES', $workReport->id);

        $this->assertSame('2026-04-07 23:59:59', $dueAt->format('Y-m-d H:i:s'));
    }

    public function test_penalty_percentage_uses_new_bands_without_15_percent(): void
    {
        $policy = app(AdsDeadlinePolicy::class);

        $this->assertSame(0.0, $policy->penaltyPercentage(0));
        $this->assertSame(0.5, $policy->penaltyPercentage(1));
        $this->assertSame(5.0, $policy->penaltyPercentage(10));
        $this->assertSame(10.0, $policy->penaltyPercentage(11));
        $this->assertSame(10.0, $policy->penaltyPercentage(30));
    }

    public function test_normalizer_accepts_documented_and_iso_date_formats(): void
    {
        $normalizer = app(NormalizeImportedHoliday::class);

        $documented = $normalizer->handle([
            'id'       => 'abc',
            'data'     => '13/04/2026',
            'nome'     => 'Nossa Senhora da Penha',
            'tipo'     => 'ESTADUAL',
            'bancario' => false,
        ], 'ES', 2026);

        $iso = $normalizer->handle([
            'id'   => 'def',
            'data' => '2026-01-01',
            'nome' => 'Confraternização Universal',
            'tipo' => 'NACIONAL',
        ], 'ES', 2026);

        $this->assertSame('2026-04-13', $documented['date']);
        $this->assertSame('2026-01-01', $iso['date']);
    }

    public function test_holiday_import_deduplicates_api_rows_by_state_and_date(): void
    {
        $service = app(HolidayImportService::class);

        $count = $service->replaceCalendar('ES', 2026, [
            [
                'id'       => 'first',
                'data'     => '20/11/2026',
                'nome'     => 'Consciência Negra',
                'tipo'     => 'NACIONAL',
                'bancario' => true,
            ],
            [
                'id'       => 'second',
                'data'     => '20/11/2026',
                'nome'     => 'Dia Nacional de Zumbi e da Consciência Negra',
                'tipo'     => 'NACIONAL',
                'bancario' => true,
            ],
        ]);

        $this->assertSame(1, $count);
        $this->assertSame(1, Holiday::query()->where('state', 'ES')->whereDate('date', '2026-11-20')->count());
    }

    private function makeWorkReport(string $informedAt): WorkReport
    {
        $user    = User::factory()->create();
        $company = Company::create(['name' => 'Compel', 'email' => 'compel' . uniqid() . '@example.com']);
        $note    = Note::create(['note' => (string) random_int(4000000100, 4999999999)]);

        return WorkReport::create([
            'note_id'     => $note->id,
            'company_id'  => $company->id,
            'user_id'     => $user->id,
            'informed_at' => $informedAt,
            'date'        => substr($informedAt, 0, 10),
        ]);
    }

    public function test_ads_due_at_is_stored_on_create_and_recalculated_on_reinform(): void
    {
        $workReport = $this->makeWorkReport('2026-04-03 10:00:00');

        $this->assertSame('2026-04-08 23:59:59', $workReport->fresh()->ads_due_at->format('Y-m-d H:i:s'));

        $workReport->update(['rejected' => true, 'informed_at' => null]);
        $this->assertNull($workReport->fresh()->ads_due_at);

        $workReport->update(['rejected' => false, 'informed_at' => '2026-04-10 09:00:00']);
        $this->assertSame('2026-04-15 23:59:59', $workReport->fresh()->ads_due_at->format('Y-m-d H:i:s'));
    }

    public function test_ads_due_at_uses_calendar_days_in_sp(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $workReport = $this->makeWorkReport('2026-04-03 10:00:00');

        $this->assertSame('2026-04-06 23:59:59', $workReport->fresh()->ads_due_at->format('Y-m-d H:i:s'));
    }

    public function test_ads_due_at_is_frozen_once_a_tacit_adsform_exists_but_follows_non_tacit_ads(): void
    {
        $workReport = $this->makeWorkReport('2026-04-03 10:00:00');
        $original   = $workReport->fresh()->ads_due_at->format('Y-m-d H:i:s');

        $adsform = Adsform::create([
            'work_report_id' => $workReport->id,
            'note_id'        => $workReport->note_id,
            'user_id'        => $workReport->user_id,
            'amount'         => 0,
            'tacit'          => false,
        ]);

        $workReport->update(['informed_at' => '2026-04-10 09:00:00']);
        $this->assertSame('2026-04-15 23:59:59', $workReport->fresh()->ads_due_at->format('Y-m-d H:i:s'));

        $adsform->update(['tacit' => true, 'tacit_due_at' => '2026-04-15 23:59:59']);
        $workReport->update(['informed_at' => '2026-04-20 09:00:00']);

        $this->assertSame('2026-04-15 23:59:59', $workReport->fresh()->ads_due_at->format('Y-m-d H:i:s'));
        $this->assertNotSame($original, $workReport->fresh()->ads_due_at->format('Y-m-d H:i:s'));
    }

    public function test_sync_pending_recalculates_after_a_new_holiday(): void
    {
        $workReport = $this->makeWorkReport('2026-04-03 10:00:00');

        Holiday::create([
            'state'       => 'ES',
            'year'        => 2026,
            'date'        => '2026-04-06',
            'name'        => 'Feriado estadual',
            'type'        => 'ESTADUAL',
            'source'      => 'feriados_api',
            'imported_at' => now(),
        ]);

        app(AdsDeadlinePolicy::class)->syncPending('ES');

        $this->assertSame('2026-04-09 23:59:59', $workReport->fresh()->ads_due_at->format('Y-m-d H:i:s'));
    }
}
