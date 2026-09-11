<?php

namespace Tests\Feature;

use App\Models\{Company, Note, Order, Production, Service, User, WorkReport, WorkReportFlowProduction};
use App\Services\WorkReports\{WorkReportOperationalContext, WorkReportOperationalContextResolver};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkReportOperationalContextResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_many_work_report_contexts_with_their_own_orders(): void
    {
        [$networkReport, $connectionReport, $networkOrder, $connectionOrder] = $this->noteWithTwoWorkReports();

        $contexts = app(WorkReportOperationalContextResolver::class)
            ->forMany([$networkReport->id, $connectionReport->id], WorkReportFlowProduction::STAGE_FISCALIZATION);

        $this->assertSame(WorkReportOperationalContext::STATUS_VALID, $contexts[$networkReport->id]->status);
        $this->assertSame([$networkOrder->id], $contexts[$networkReport->id]->orders->pluck('id')->all());
        $this->assertSame([$connectionOrder->id], $contexts[$connectionReport->id]->orders->pluck('id')->all());
    }

    public function test_legacy_production_without_link_is_ambiguous_when_note_has_two_work_reports(): void
    {
        [$networkReport] = $this->noteWithTwoWorkReports();
        $production      = $this->productionFor($networkReport->Note);

        $context = app(WorkReportOperationalContextResolver::class)
            ->forLegacyProduction($production, WorkReportFlowProduction::STAGE_FISCALIZATION);

        $this->assertSame(WorkReportOperationalContext::STATUS_AMBIGUOUS, $context->status);
        $this->assertSame('multiple_active_work_reports_for_legacy_production', $context->reason);
    }

    public function test_legacy_publication_context_ignores_connection_only_work_report(): void
    {
        [$networkReport] = $this->noteWithTwoWorkReports();
        $production      = $this->productionFor($networkReport->Note);

        $context = app(WorkReportOperationalContextResolver::class)
            ->forLegacyProduction($production, WorkReportFlowProduction::STAGE_PUBLICATION, publicationOnly: true);

        $this->assertSame(WorkReportOperationalContext::STATUS_LEGACY_RESOLVED, $context->status);
        $this->assertSame($networkReport->id, $context->workReport?->id);
    }

    private function noteWithTwoWorkReports(): array
    {
        config(['sicode.ruleset' => 'sp']);

        $user    = User::factory()->create();
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $note    = Note::create(['note' => '4000000010', 'type_note' => 1]);

        $networkOrder = Order::create([
            'note_id'    => $note->id,
            'ordem'      => '170000000010',
            'statusSist' => 'ABER',
        ]);
        $connectionOrder = Order::create([
            'note_id'    => $note->id,
            'ordem'      => '180000000010',
            'statusSist' => 'ABER',
        ]);

        $networkReport = WorkReport::create([
            'note_id'               => $note->id,
            'company_id'            => $company->id,
            'user_id'               => $user->id,
            'date'                  => '2026-08-01',
            'informed_at'           => '2026-08-01 08:00:00',
            'selected_final_scopes' => ['network'],
        ]);
        $networkReport->Orders()->sync([$networkOrder->id]);

        $connectionReport = WorkReport::create([
            'note_id'               => $note->id,
            'company_id'            => $company->id,
            'user_id'               => $user->id,
            'date'                  => '2026-08-03',
            'informed_at'           => '2026-08-03 08:00:00',
            'selected_final_scopes' => ['connection'],
        ]);
        $connectionReport->Orders()->sync([$connectionOrder->id]);

        return [$networkReport, $connectionReport, $networkOrder, $connectionOrder];
    }

    private function productionFor(Note $note): Production
    {
        $service    = Service::create(['service' => 'Fiscalização']);
        $workReport = WorkReport::query()->where('note_id', $note->id)->firstOrFail();

        return Production::create([
            'note_id'    => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $workReport->company_id,
            'user_id'    => $workReport->user_id,
            'att_at'     => '2026-08-03 09:00:00',
            'completed'  => false,
            'partial'    => false,
        ]);
    }
}
