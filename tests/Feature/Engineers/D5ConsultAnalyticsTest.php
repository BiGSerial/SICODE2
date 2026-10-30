<?php

namespace Tests\Feature\Engineers;

use App\Models\{Company, FiveNote, Note};
use App\Services\D5\D5StageResolver;
use App\Services\Engineers\{D5ConsultAnalytics, D5ConsultCharts};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class D5ConsultAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function five(Company $company, array $attrs): FiveNote
    {
        $note = Note::create(['note' => (string) (4000000000 + ++$this->seq), 'dt_status' => now()->toDateTimeString(), 'nstats' => 'NEW']);

        return FiveNote::create($attrs + [
            'note_id'         => $note->id,
            'note_d5'         => (string) $this->seq,
            'company_id'      => $company->id,
            'reason'          => '1_MOTIVO',
            'visible_partner' => true,
        ]);
    }

    /** Idade fora de dúvida mesmo com fuso diferente entre PHP e banco (margem de 12h). */
    private function ago(int $days)
    {
        return now()->subDays($days)->subHours(12);
    }

    private function seedAll(): array
    {
        $compel  = Company::create(['name' => 'Compel', 'email' => 'c@example.com']);
        $manserv = Company::create(['name' => 'Manserv', 'email' => 'm@example.com']);

        // Fornecedor: uma atrasada (8 dias), uma recente (1 dia).
        $this->five($compel, ['dispatch_at' => $this->ago(8), 'reason' => '1_REPARO']);
        $this->five($manserv, ['dispatch_at' => $this->ago(1), 'reason' => '1_REPARO']);

        // Fiscalização: voltou da empreiteira há 4 dias (atenção), despachada há 10.
        $this->five($compel, ['dispatch_at' => $this->ago(10), 'is_completed' => true, 'completed_at' => $this->ago(4), 'reason' => '2_OUTROS']);

        // Medição: fiscalizada há 2 dias (no prazo).
        $this->five($manserv, ['dispatch_at' => $this->ago(12), 'is_completed' => true, 'completed_at' => $this->ago(6), 'is_supervisioned' => true, 'supervisioned_at' => $this->ago(2), 'reason' => '2_OUTROS']);

        // Finalizada.
        $this->five($manserv, ['dispatch_at' => $this->ago(30), 'is_completed' => true, 'completed_at' => $this->ago(20), 'is_archived' => true]);

        return [$compel, $manserv];
    }

    public function test_summary_counts_each_stage_and_the_sla_buckets(): void
    {
        $this->seedAll();

        $snapshot = app(D5ConsultAnalytics::class)->snapshot(FiveNote::query());
        $s        = $snapshot['summary'];

        $this->assertSame(5, $s['total']);
        $this->assertSame(4, $s['open']);
        $this->assertSame(1, $s['finalized']);
        $this->assertSame(1, $s['late']);
        $this->assertSame(1, $s['warn']);
        $this->assertSame(2, $s['on_time']);
        $this->assertSame(8, $s['oldest']);

        $stages = $snapshot['stages'];
        $this->assertSame(2, $stages[D5StageResolver::SUPPLIER]['total']);
        $this->assertSame(1, $stages[D5StageResolver::FISCALIZATION]['total']);
        $this->assertSame(1, $stages[D5StageResolver::PAYMENT]['total']);
        $this->assertSame(1, $stages[D5StageResolver::SUPPLIER]['late']);
        $this->assertSame(1, $stages[D5StageResolver::FISCALIZATION]['warn']);
        $this->assertSame(8, $stages[D5StageResolver::SUPPLIER]['oldest']);
    }

    public function test_age_buckets_cover_every_open_d5(): void
    {
        $this->seedAll();

        $buckets = app(D5ConsultAnalytics::class)->snapshot(FiveNote::query())['age_buckets'];

        $this->assertCount(count(D5ConsultAnalytics::BUCKETS), $buckets['labels']);
        $this->assertSame(4, array_sum($buckets['values']));
        $this->assertSame([2, 1, 1, 0, 0, 0, 0], $buckets['values']);
    }

    public function test_rankings_group_open_d5_by_company_and_reason(): void
    {
        $this->seedAll();

        $snapshot = app(D5ConsultAnalytics::class)->snapshot(FiveNote::query());

        $companies = collect($snapshot['by_company'])->keyBy('label');
        $this->assertSame(2, $companies['Compel']['total']);
        $this->assertSame(1, $companies['Compel']['late']);
        $this->assertSame(2, $companies['Manserv']['total']);
        $this->assertSame(0, $companies['Manserv']['late']);

        $reasons = collect($snapshot['by_reason'])->keyBy('label');
        $this->assertSame(2, $reasons['1_REPARO']['total']);
        $this->assertSame(2, $reasons['2_OUTROS']['total']);
    }

    public function test_return_time_and_monthly_flow(): void
    {
        $this->seedAll();

        $snapshot = app(D5ConsultAnalytics::class)->snapshot(FiveNote::query());

        // 6 d (10→4) + 6 d (12→6) + 10 d (30→20) = média 7,3 dias.
        $this->assertEqualsWithDelta(7.3, $snapshot['summary']['return_days'], 0.1);

        $flow = $snapshot['flow'];
        $this->assertCount(D5ConsultAnalytics::FLOW_MONTHS, $flow['labels']);
        $this->assertSame(5, array_sum($flow['sent']));
        $this->assertSame(3, array_sum($flow['returned']));
    }

    public function test_respects_the_filtered_query_it_receives(): void
    {
        [$compel] = $this->seedAll();

        $snapshot = app(D5ConsultAnalytics::class)->snapshot(FiveNote::query()->where('company_id', $compel->id));

        $this->assertSame(2, $snapshot['summary']['total']);
        $this->assertSame(2, $snapshot['summary']['open']);
        $this->assertSame(0, $snapshot['summary']['finalized']);
    }

    public function test_empty_result_keeps_the_chart_contract(): void
    {
        $snapshot = app(D5ConsultAnalytics::class)->snapshot(FiveNote::query());
        $charts   = D5ConsultCharts::all($snapshot);

        $this->assertSame(0, $snapshot['summary']['open']);
        $this->assertNull($snapshot['summary']['oldest']);

        // Quantidade e ordem dos datasets são fixas, com ou sem dados (atualização in-place).
        $this->assertSame(['stage', 'flow', 'age'], array_keys($charts));
        $this->assertCount(1, $charts['stage']['data']['datasets']);
        $this->assertCount(4, $charts['stage']['data']['labels']);
        $this->assertCount(2, $charts['flow']['data']['datasets']);
        $this->assertCount(1, $charts['age']['data']['datasets']);
        $this->assertCount(count(D5ConsultAnalytics::BUCKETS), $charts['age']['data']['labels']);
    }

    public function test_stage_chart_orders_stages_like_the_flow(): void
    {
        $this->seedAll();

        $chart = D5ConsultCharts::all(app(D5ConsultAnalytics::class)->snapshot(FiveNote::query()))['stage'];

        $this->assertSame(['Fornecedor', 'Fiscalização', 'Medição', 'Finalizado'], $chart['data']['labels']);
        $this->assertSame([2, 1, 1, 1], $chart['data']['datasets'][0]['data']);
        $this->assertSame('4', $chart['options']['plugins']['mpCenter']['text']);
    }
}
