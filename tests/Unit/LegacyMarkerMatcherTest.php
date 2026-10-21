<?php

namespace Tests\Unit;

use App\Services\Retrofill\LegacyMarkerMatcher;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class LegacyMarkerMatcherTest extends TestCase
{
    private function at(string $date): Carbon
    {
        return Carbon::parse($date);
    }

    public function test_dispatch_before_the_marker_is_rejected(): void
    {
        $result = (new LegacyMarkerMatcher())->match(['W1' => $this->at('2026-03-10')], [['id' => 1, 'at' => $this->at('2026-03-09 23:59')]]);

        $this->assertSame([], $result['links']);
        $this->assertSame([1 => LegacyMarkerMatcher::BEFORE_MARKER], $result['rejected']);
    }

    public function test_production_without_any_marker_is_rejected(): void
    {
        $result = (new LegacyMarkerMatcher())->match([], [['id' => 1, 'at' => $this->at('2026-03-09')]]);

        $this->assertSame([1 => LegacyMarkerMatcher::NO_MARKER], $result['rejected']);
    }

    public function test_fifo_assigns_the_oldest_free_marker_even_when_dispatch_is_late(): void
    {
        $markers = ['W1' => $this->at('2026-01-01'), 'W2' => $this->at('2026-02-01')];
        $result  = (new LegacyMarkerMatcher())->match($markers, [
            ['id' => 10, 'at' => $this->at('2026-03-05')],
            ['id' => 11, 'at' => $this->at('2026-03-20')],
        ]);

        $this->assertSame('W1', $result['links'][10]['marker']);
        $this->assertSame('W2', $result['links'][11]['marker']);
    }

    public function test_one_production_with_two_eligible_markers_is_ambiguous(): void
    {
        $markers = ['W1' => $this->at('2026-01-01'), 'W2' => $this->at('2026-02-01')];
        $result  = (new LegacyMarkerMatcher())->match($markers, [['id' => 10, 'at' => $this->at('2026-03-05')]]);

        $this->assertSame([], $result['links']);
        $this->assertSame([10 => LegacyMarkerMatcher::AMBIGUOUS], $result['rejected']);
    }

    public function test_markers_consumed_by_existing_links_leave_the_pool(): void
    {
        $markers = ['W1' => $this->at('2026-01-01'), 'W2' => $this->at('2026-02-01')];
        $result  = (new LegacyMarkerMatcher())->match(
            $markers,
            [['id' => 11, 'at' => $this->at('2026-03-05')]],
            [['id' => 10, 'at' => $this->at('2026-01-15'), 'marker' => 'W1']]
        );

        $this->assertSame('W2', $result['links'][11]['marker']);
    }

    public function test_extra_production_is_a_reassignment_of_the_previous_one(): void
    {
        $markers = ['W1' => $this->at('2026-01-01')];
        $result  = (new LegacyMarkerMatcher())->match($markers, [
            ['id' => 10, 'at' => $this->at('2026-01-05')],
            ['id' => 11, 'at' => $this->at('2026-01-09')],
        ]);

        $this->assertFalse($result['links'][10]['reassigned']);
        $this->assertTrue($result['links'][11]['reassigned']);
        $this->assertSame('W1', $result['links'][11]['marker']);
    }

    public function test_extra_production_is_surplus_when_repeat_is_not_allowed(): void
    {
        $markers = ['P1' => $this->at('2026-01-01')];
        $result  = (new LegacyMarkerMatcher())->match($markers, [
            ['id' => 10, 'at' => $this->at('2026-01-05')],
            ['id' => 11, 'at' => $this->at('2026-01-09')],
        ], [], false);

        $this->assertSame('P1', $result['links'][10]['marker']);
        $this->assertSame([11 => LegacyMarkerMatcher::SURPLUS], $result['rejected']);
    }
}
