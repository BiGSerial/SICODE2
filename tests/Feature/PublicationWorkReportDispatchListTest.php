<?php

namespace Tests\Feature;

use App\Models\{Company, Note, Operation, Order, Production, Service, User, WorkReport, WorkReportFlowProduction};
use App\Http\Livewire\Dispatchs\Publication\Main as PublicationDispatchMain;
use App\Repositories\PublishRepository;
use App\Services\Dispatch\DispatchWorkflowService;
use App\Services\Publication\NoteFilter as PublicationNoteFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicationWorkReportDispatchListTest extends TestCase
{
    use RefreshDatabase;

    public function test_publication_uses_only_orders_associated_to_the_final_work_report(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $service = Service::create(['service' => 'Publicação']);
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $note = Note::create(['note' => '4000006100', 'type_note' => 1, 'dt_status' => now()]);

        $connectionOrder = $this->orderWithPublicationOperation($note, '1800006100');
        $lateServiceOrder = $this->orderWithPublicationOperation($note, '1500006100');

        $workReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'date' => '2026-09-10',
            'informed_at' => '2026-09-10 08:00:00',
            'rejected' => false,
            'canceled' => false,
            'selected_final_scopes' => ['connection'],
        ]);
        $workReport->Orders()->sync([$connectionOrder->id]);

        $this->assertFalse(
            app(PublishRepository::class)
                ->getBaseQuery(false, $service->uuid)
                ->where('notes.id', $note->id)
                ->exists()
        );

        $this->assertFalse(
            app(PublicationNoteFilter::class)
                ->filter('publishing')
                ->where('notes.id', $note->id)
                ->exists()
        );

        $this->assertNotContains($lateServiceOrder->id, $workReport->Orders()->pluck('orders.id')->all());
    }

    public function test_publication_accepts_network_order_when_it_belongs_to_the_work_report(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $service = Service::create(['service' => 'Publicação']);
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $note = Note::create(['note' => '4000006101', 'type_note' => 1, 'dt_status' => now()]);
        $networkOrder = $this->orderWithPublicationOperation($note, '1500006101');

        $workReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'date' => '2026-09-10',
            'informed_at' => '2026-09-10 08:00:00',
            'rejected' => false,
            'canceled' => false,
            'selected_final_scopes' => ['network'],
        ]);
        $workReport->Orders()->sync([$networkOrder->id]);

        $this->assertTrue(
            app(PublishRepository::class)
                ->getBaseQuery(false, $service->uuid)
                ->where('notes.id', $note->id)
                ->exists()
        );

        $this->assertTrue(
            app(PublicationNoteFilter::class)
                ->filter('publishing')
                ->where('notes.id', $note->id)
                ->exists()
        );
    }

    public function test_publication_production_does_not_remove_work_report_until_operation_20_is_confirmed(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $service = Service::create(['service' => 'Publicação']);
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $note = Note::create(['note' => '4000006102', 'type_note' => 1, 'dt_status' => now()]);
        $networkOrder = $this->orderWithPublicationOperation($note, '1700006102', 'LIB');

        $workReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'informed_at' => '2026-09-10 08:00:00',
            'rejected' => false,
            'canceled' => false,
            'selected_final_scopes' => ['network'],
        ]);
        $workReport->Orders()->sync([$networkOrder->id]);

        $production = Production::create([
            'note_id' => $note->id,
            'service_id' => $service->uuid,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'completed' => false,
            'confirmed' => false,
            'partial' => false,
        ]);

        WorkReportFlowProduction::create([
            'work_report_id' => $workReport->id,
            'production_id' => $production->id,
            'stage' => WorkReportFlowProduction::STAGE_PUBLICATION,
            'final_scope' => WorkReportFlowProduction::SCOPE_NETWORK,
            'is_current' => true,
            'source' => 'test',
        ]);

        $this->assertTrue(
            app(PublishRepository::class)
                ->getBaseQuery(false, $service->uuid)
                ->where('notes.id', $note->id)
                ->exists()
        );
    }

    public function test_open_fiscalization_does_not_remove_work_report_from_publication_dispatch_list(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $publicationService = Service::create(['service' => 'Publicação']);
        $fiscalizationService = Service::create(['service' => 'Fiscalização']);
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $note = Note::create(['note' => '4000006103', 'type_note' => 1, 'dt_status' => now()]);
        $networkOrder = $this->orderWithPublicationOperation($note, '1700006103');

        $workReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'informed_at' => '2026-09-10 08:00:00',
            'rejected' => false,
            'canceled' => false,
            'selected_final_scopes' => ['network'],
        ]);
        $workReport->Orders()->sync([$networkOrder->id]);

        Production::create([
            'note_id' => $note->id,
            'service_id' => $fiscalizationService->uuid,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'completed' => false,
            'confirmed' => false,
            'partial' => false,
        ]);

        $this->assertTrue(
            app(PublishRepository::class)
                ->getBaseQuery(false, $publicationService->uuid)
                ->where('notes.id', $note->id)
                ->exists()
        );
    }

    public function test_confirmed_operation_20_removes_work_report_from_dispatch_list_even_without_publication_production(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $publicationService = Service::create(['service' => 'Publicação']);
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $note = Note::create(['note' => '4000006104', 'type_note' => 1, 'dt_status' => now()]);
        $networkOrder = $this->orderWithPublicationOperation($note, '1700006104', 'CONF');

        $workReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'informed_at' => '2026-09-10 08:00:00',
            'rejected' => false,
            'canceled' => false,
            'selected_final_scopes' => ['network'],
        ]);
        $workReport->Orders()->sync([$networkOrder->id]);

        $this->assertFalse(
            app(PublishRepository::class)
                ->getBaseQuery(false, $publicationService->uuid)
                ->where('notes.id', $note->id)
                ->exists()
        );
    }

    public function test_publication_dispatch_list_opens_shared_dispatch_modal(): void
    {
        $this->actingAs(User::factory()->create(['contract' => false]));

        $service = Service::create(['service' => 'Publicação', 'folder' => 'publicacao']);
        Note::create(['note' => '4000006199', 'dt_status' => now()]);

        Livewire::test(PublicationDispatchMain::class, ['service' => $service->uuid])
            ->set('selected', [10, 20])
            ->call('go_att_mass')
            ->assertEmittedTo('dispatchs.shared.dispatch-modal', 'openForNotes', [10, 20]);
    }

    public function test_shared_dispatch_workflow_links_publication_to_work_report(): void
    {
        $actor = User::factory()->create(['contract' => false]);
        $this->actingAs($actor);

        $service = Service::create(['service' => 'Publicação', 'folder' => 'publicacao']);
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $targetUser = User::factory()->create(['company_id' => $company->id]);
        $note = Note::create(['note' => '4000006105', 'dt_status' => now()]);
        $networkOrder = $this->orderWithPublicationOperation($note, '1700006105');

        $workReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $targetUser->id,
            'date' => '2026-09-10',
            'informed_at' => '2026-09-10 08:00:00',
            'rejected' => false,
            'canceled' => false,
        ]);
        $workReport->Orders()->sync([$networkOrder->id]);

        $production = app(DispatchWorkflowService::class)
            ->dispatchToUser($note, $service, $company, $targetUser, $actor);

        $this->assertDatabaseHas('work_report_flow_productions', [
            'work_report_id' => $workReport->id,
            'production_id' => $production->id,
            'stage' => WorkReportFlowProduction::STAGE_PUBLICATION,
            'final_scope' => WorkReportFlowProduction::SCOPE_GENERAL,
            'is_current' => true,
        ]);
    }

    private function orderWithPublicationOperation(Note $note, string $number, string $operationStatus = 'LIB'): Order
    {
        $order = Order::create([
            'note_id' => $note->id,
            'ordem' => $number,
            'statusSist' => 'LIB',
        ]);

        Operation::create([
            'order_id' => $order->id,
            'operacao' => '0020',
            'status' => $operationStatus,
        ]);

        return $order;
    }
}
