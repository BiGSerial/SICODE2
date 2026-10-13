<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\Control\WorkReportEdit;
use App\Models\{Company, Note, Order, User, WorkReport};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkReportScopeOrderAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_an_order_from_another_scope_does_not_merge_or_create_a_report(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $user            = User::factory()->create();
        $company         = Company::create(['name' => 'Empresa', 'email' => 'empresa@example.com']);
        $note            = Note::create(['note' => '4001951584', 'type_note' => 1]);
        $networkOrder    = Order::create(['note_id' => $note->id, 'ordem' => '170000000001', 'statusSist' => 'ABER']);
        $connectionOrder = Order::create(['note_id' => $note->id, 'ordem' => '180000000001', 'statusSist' => 'ABER']);
        $networkReport   = WorkReport::create([
            'note_id'               => $note->id,
            'company_id'            => $company->id,
            'user_id'               => $user->id,
            'date'                  => '2026-10-01',
            'selected_final_scopes' => ['network'],
        ]);
        $networkReport->Orders()->sync([$networkOrder->id]);

        Livewire::actingAs($user)
            ->test(WorkReportEdit::class)
            ->set('workReport', $networkReport)
            ->call('addOrder', $connectionOrder->id);

        $this->assertDatabaseHas('order_work_report', [
            'work_report_id' => $networkReport->id,
            'order_id'       => $networkOrder->id,
        ]);
        $this->assertDatabaseMissing('order_work_report', [
            'work_report_id' => $networkReport->id,
            'order_id'       => $connectionOrder->id,
        ]);

        $this->assertSame(1, WorkReport::query()->where('note_id', $note->id)->count());
        $this->assertDatabaseMissing('order_work_report', [
            'order_id' => $connectionOrder->id,
        ]);
    }
}
