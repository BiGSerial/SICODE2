<?php

namespace Tests\Feature;

use App\Http\Livewire\Services\Payment\PendingD5Create;
use App\Models\{Company, FiveNote, Note, User, WorkReport};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendingD5BulkAssignTest extends TestCase
{
    use RefreshDatabase;

    public function test_informe_number_selects_which_d5_of_the_note_receives_the_number(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'c@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);
        $note   = Note::create(['note' => '4001940398', 'dt_status' => '2026-09-10 08:00:00', 'nstats' => 'NEW']);
        $report = WorkReport::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'date' => '2026-09-10', 'informed_at' => '2026-09-10 08:00:00', 'rejected' => false, 'canceled' => false,
        ]);
        $legacy = FiveNote::create(['note_id' => $note->id, 'note_d5' => '']);
        $own    = FiveNote::create(['note_id' => $note->id, 'work_report_id' => $report->id, 'note_d5' => '']);

        $component = Livewire::test(PendingD5Create::class)
            ->set('bulkD5Input', "4001940398 111 {$report->id}")
            ->call('processBulkD5');

        $this->assertSame([$own->id], collect($component->get('bulkD5Ready'))->pluck('five_note_id')->all());
        $component->call('confirmBulkD5');
        $this->assertSame('111', $own->fresh()->note_d5);
        $this->assertSame('', (string) $legacy->fresh()->note_d5);
    }

    public function test_note_with_more_than_one_pending_d5_is_not_guessed_without_informe(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'c@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);
        $note   = Note::create(['note' => '4001940398', 'dt_status' => '2026-09-10 08:00:00', 'nstats' => 'NEW']);
        $report = WorkReport::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'date' => '2026-09-10', 'informed_at' => '2026-09-10 08:00:00', 'rejected' => false, 'canceled' => false,
        ]);
        FiveNote::create(['note_id' => $note->id, 'note_d5' => '']);
        FiveNote::create(['note_id' => $note->id, 'work_report_id' => $report->id, 'note_d5' => '']);

        $component = Livewire::test(PendingD5Create::class)
            ->set('bulkD5Input', "4001940398 111")
            ->call('processBulkD5');

        $this->assertSame([], $component->get('bulkD5Ready'));
        $this->assertStringContainsString('mais de uma D5', $component->get('bulkD5Ignored')[0]['reason']);
    }

    public function test_note_with_a_single_d5_still_works_with_two_numbers(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'c@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);
        $note = Note::create(['note' => '4001940398', 'dt_status' => '2026-09-10 08:00:00', 'nstats' => 'NEW']);
        $five = FiveNote::create(['note_id' => $note->id, 'note_d5' => '']);

        $component = Livewire::test(PendingD5Create::class)
            ->set('bulkD5Input', "4001940398 222")
            ->call('processBulkD5');

        $this->assertSame([$five->id], collect($component->get('bulkD5Ready'))->pluck('five_note_id')->all());
    }
}
