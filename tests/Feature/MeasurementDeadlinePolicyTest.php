<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Services\Payment\MeasurementDeadlinePolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeasurementDeadlinePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_es_measurement_deadline_uses_fifteen_calendar_days(): void
    {
        config()->set('sicode.ruleset', 'es');

        $dueAt = app(MeasurementDeadlinePolicy::class)->dueAt(Carbon::parse('2026-10-01 12:00:00'));

        $this->assertSame('2026-10-16', $dueAt->toDateString());
    }

    public function test_sp_measurement_deadline_uses_eight_business_days_and_skips_holidays(): void
    {
        config()->set('sicode.ruleset', 'sp');
        Holiday::create([
            'state'  => 'SP',
            'year'   => 2026,
            'date'   => '2026-10-12',
            'name'   => 'Feriado',
            'source' => 'feriados_api',
        ]);

        $dueAt = app(MeasurementDeadlinePolicy::class)->dueAt(Carbon::parse('2026-10-02 12:00:00'));

        $this->assertSame('2026-10-15', $dueAt->toDateString());
    }

    public function test_deadline_status_turns_yellow_in_the_last_four_days_and_red_after_due_date(): void
    {
        config()->set('sicode.ruleset', 'es');
        $policy = app(MeasurementDeadlinePolicy::class);

        $this->assertSame('no_prazo', $policy->statusForRemainingDays(5));
        $this->assertSame('vencendo', $policy->statusForRemainingDays(4));
        $this->assertSame('vencendo', $policy->statusForRemainingDays(0));
        $this->assertSame('vencido', $policy->statusForRemainingDays(-1));
    }
}
