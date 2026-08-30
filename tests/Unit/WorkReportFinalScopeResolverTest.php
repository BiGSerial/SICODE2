<?php

namespace Tests\Unit;

use App\Services\WorkReports\WorkReportFinalScopeResolver;
use Illuminate\Support\Collection;
use Tests\TestCase;

class WorkReportFinalScopeResolverTest extends TestCase
{
    private WorkReportFinalScopeResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new WorkReportFinalScopeResolver();
    }

    public function test_es_ruleset_keeps_legacy_general_scope(): void
    {
        config(['sicode.ruleset' => 'es']);

        $scopes = $this->resolver->resolve(1, $this->orders(['170000001', '180000001']));

        $this->assertSame(['general'], array_column($scopes, 'scope'));
        $this->assertTrue($this->resolver->publicationRequired('general'));
    }

    public function test_sp_ruleset_splits_ep_work_report_by_canonical_network_prefixes_and_other_orders(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $scopes = $this->resolver->resolve(1, $this->orders(['170000001', '190000001', '180000001', '999000001']));

        $this->assertSame(['network', 'connection'], array_column($scopes, 'scope'));
        $this->assertSame(['170000001', '190000001'], array_column($scopes[0]['orders'], 'number'));
        $this->assertSame(['180000001', '999000001'], array_column($scopes[1]['orders'], 'number'));
        $this->assertSame('non_network_order_prefix', $scopes[1]['resolution']);
    }

    public function test_connection_scope_does_not_require_publication(): void
    {
        $this->assertFalse($this->resolver->publicationRequired('connection'));
        $this->assertSame('skipped_by_scope', $this->resolver->publicationPolicy('connection'));
    }

    public function test_unknown_ep_order_prefix_is_treated_as_connection_on_split_ruleset(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $scopes = $this->resolver->resolve(1, $this->orders(['999000001']));

        $this->assertSame(['connection'], array_column($scopes, 'scope'));
        $this->assertSame('non_network_order_prefix', $scopes[0]['resolution']);
    }

    public function test_ep_without_orders_keeps_general_scope_on_split_ruleset(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $scopes = $this->resolver->resolve(1, collect());

        $this->assertSame(['general'], array_column($scopes, 'scope'));
        $this->assertSame('no_matching_btzero_ep_order_prefix', $scopes[0]['resolution']);
    }

    public function test_non_ep_note_keeps_general_scope_even_on_sp_ruleset(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $scopes = $this->resolver->resolve(2, $this->orders(['170000001', '180000001']));

        $this->assertSame(['general'], array_column($scopes, 'scope'));
    }

    private function orders(array $numbers): Collection
    {
        return collect($numbers)
            ->map(fn (string $number, int $index) => (object) [
                'order_id' => $index + 1,
                'order_number' => $number,
            ]);
    }
}
