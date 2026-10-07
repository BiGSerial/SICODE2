<?php

namespace Tests\Feature;

use App\Models\{Company, Contract, FiveNote, MeasurementProductionContext, Note, Partial, Production, Service, User, WorkReport};
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class MeasurementProductionContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_cycle_one_context_persists_its_specific_work_report(): void
    {
        [$production, $report] = $this->finalFixture();

        $context = MeasurementProductionContext::create([
            'production_id'  => $production->id,
            'work_report_id' => $report->id,
            'cycle'          => MeasurementProductionContext::CYCLE_ONE,
        ]);

        $this->assertSame($production->id, $context->production->id);
        $this->assertSame($report->id, $context->workReport->id);
        $this->assertNull($context->partial_id);
        $this->assertNull($context->five_note_id);
    }

    public function test_context_requires_exactly_one_informe_target_and_a_d5_for_return(): void
    {
        [$production, $report, $partial] = $this->finalFixture(withPartial: true);

        $this->expectException(InvalidArgumentException::class);

        MeasurementProductionContext::create([
            'production_id'  => $production->id,
            'work_report_id' => $report->id,
            'partial_id'     => $partial->id,
            'cycle'          => MeasurementProductionContext::CYCLE_ONE,
        ]);
    }

    public function test_d5_return_requires_its_specific_five_note(): void
    {
        [$production, $report] = $this->finalFixture();

        $this->expectException(InvalidArgumentException::class);

        MeasurementProductionContext::create([
            'production_id'  => $production->id,
            'work_report_id' => $report->id,
            'cycle'          => MeasurementProductionContext::CYCLE_D5_RETURN,
        ]);
    }

    public function test_passive_eligible_d5_return_may_be_identified_without_an_informe(): void
    {
        [$production, $report] = $this->finalFixture();
        $fiveNote              = FiveNote::create([
            'note_id'          => $report->note_id,
            'note_d5'          => 'D5-PASSIVE-CTX',
            'is_completed'     => true,
            'is_supervisioned' => true,
            'is_archived'      => false,
            'returned'         => false,
            'isPassive'        => true,
        ]);

        $context = MeasurementProductionContext::create([
            'production_id' => $production->id,
            'five_note_id'  => $fiveNote->id,
            'cycle'         => MeasurementProductionContext::CYCLE_D5_RETURN,
        ]);

        $this->assertNull($context->work_report_id);
        $this->assertSame($fiveNote->id, $context->five_note_id);
    }

    public function test_d5_return_without_informe_rejects_non_passive_d5(): void
    {
        [$production, $report] = $this->finalFixture();
        $fiveNote              = FiveNote::create([
            'note_id'          => $report->note_id,
            'note_d5'          => 'D5-NON-PASSIVE-CTX',
            'is_completed'     => true,
            'is_supervisioned' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);

        MeasurementProductionContext::create([
            'production_id' => $production->id,
            'five_note_id'  => $fiveNote->id,
            'cycle'         => MeasurementProductionContext::CYCLE_D5_RETURN,
        ]);
    }

    public function test_database_allows_only_one_cycle_one_for_an_informe(): void
    {
        [$firstProduction, $report] = $this->finalFixture();
        $secondProduction           = $this->newProduction($report->Note, $report->Company, $report->User);

        MeasurementProductionContext::create([
            'production_id'  => $firstProduction->id,
            'work_report_id' => $report->id,
            'cycle'          => MeasurementProductionContext::CYCLE_ONE,
        ]);

        $this->expectException(QueryException::class);

        MeasurementProductionContext::create([
            'production_id'  => $secondProduction->id,
            'work_report_id' => $report->id,
            'cycle'          => MeasurementProductionContext::CYCLE_ONE,
        ]);
    }

    public function test_database_allows_only_one_d5_return_for_a_specific_d5(): void
    {
        [$firstProduction, $report] = $this->finalFixture();
        $secondProduction           = $this->newProduction($report->Note, $report->Company, $report->User);
        $fiveNote                   = FiveNote::create([
            'note_id'        => $report->note_id,
            'work_report_id' => $report->id,
            'note_d5'        => 'D5-CTX-1',
        ]);

        MeasurementProductionContext::create([
            'production_id'  => $firstProduction->id,
            'work_report_id' => $report->id,
            'five_note_id'   => $fiveNote->id,
            'cycle'          => MeasurementProductionContext::CYCLE_D5_RETURN,
        ]);

        $this->expectException(QueryException::class);

        MeasurementProductionContext::create([
            'production_id'  => $secondProduction->id,
            'work_report_id' => $report->id,
            'five_note_id'   => $fiveNote->id,
            'cycle'          => MeasurementProductionContext::CYCLE_D5_RETURN,
        ]);
    }

    public function test_partial_active_key_blocks_only_the_same_partial_demand(): void
    {
        [$firstProduction, $report, $partial] = $this->finalFixture(withPartial: true);
        $secondPartial                        = Partial::create([
            'note_id'    => $report->note_id,
            'company_id' => $report->company_id,
            'user_id'    => $report->user_id,
        ]);
        $secondProduction = $this->newProduction($report->Note, $report->Company, $report->User);

        MeasurementProductionContext::create([
            'production_id'     => $firstProduction->id,
            'partial_id'        => $partial->id,
            'cycle'             => MeasurementProductionContext::CYCLE_PARTIAL,
            'active_demand_key' => MeasurementProductionContext::partialActiveDemandKey($partial->id),
        ]);
        MeasurementProductionContext::create([
            'production_id'     => $secondProduction->id,
            'partial_id'        => $secondPartial->id,
            'cycle'             => MeasurementProductionContext::CYCLE_PARTIAL,
            'active_demand_key' => MeasurementProductionContext::partialActiveDemandKey($secondPartial->id),
        ]);

        $this->assertDatabaseCount('measurement_production_contexts', 2);
    }

    public function test_database_rejects_a_second_active_context_for_the_same_partial(): void
    {
        [$firstProduction, $report, $partial] = $this->finalFixture(withPartial: true);
        $secondProduction                     = $this->newProduction($report->Note, $report->Company, $report->User);

        MeasurementProductionContext::create([
            'production_id'     => $firstProduction->id,
            'partial_id'        => $partial->id,
            'cycle'             => MeasurementProductionContext::CYCLE_PARTIAL,
            'active_demand_key' => MeasurementProductionContext::partialActiveDemandKey($partial->id),
        ]);

        $this->expectException(QueryException::class);

        MeasurementProductionContext::create([
            'production_id'     => $secondProduction->id,
            'partial_id'        => $partial->id,
            'cycle'             => MeasurementProductionContext::CYCLE_PARTIAL,
            'active_demand_key' => MeasurementProductionContext::partialActiveDemandKey($partial->id),
        ]);
    }

    private function finalFixture(bool $withPartial = false): array
    {
        $company                = Company::create(['name' => 'Context Company', 'email' => 'context@example.com']);
        $contract               = new Contract();
        $contract->company_id   = $company->id;
        $contract->number       = 'CTX-001';
        $contract->service      = true;
        $contract->construction = false;
        $contract->date_end     = now()->addYear()->toDateString();
        $contract->save();
        $user = User::factory()->create(['contract' => true, 'company_id' => $company->id]);
        $user->Employee()->create(['contract_id' => $contract->id, 'service_id' => null]);
        $service = Service::create(['service' => 'Pagamento Contexto', 'folder' => 'pagamento-contexto']);
        $note    = Note::create(['note' => '4000000999', 'type_note' => 1]);
        $report  = WorkReport::create([
            'note_id'    => $note->id,
            'company_id' => $company->id,
            'user_id'    => $user->id,
            'date'       => now()->toDateString(),
        ]);
        $production = Production::create([
            'note_id'    => $note->id,
            'service_id' => $service->id,
            'user_id'    => $user->id,
            'company_id' => $company->id,
        ]);
        $partial = $withPartial
            ? Partial::create(['note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id])
            : null;

        return [$production, $report, $partial];
    }

    private function newProduction(Note $note, Company $company, User $user): Production
    {
        $service = Service::query()->firstOrFail();

        return Production::create([
            'note_id'    => $note->id,
            'service_id' => $service->id,
            'user_id'    => $user->id,
            'company_id' => $company->id,
        ]);
    }
}
