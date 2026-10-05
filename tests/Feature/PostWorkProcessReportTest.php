<?php

namespace Tests\Feature;

use App\Http\Livewire\Reports\PostWorkProcessReport;
use App\Models\{Company, FiveNote, Note, Partial, User, WorkReport};
use App\Services\Reports\PostWorkProcessReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostWorkProcessReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejected_partial_is_not_a_pending_row_and_old_cells_list_rows(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        $open     = $this->partial($company, $user, false);
        $rejected = $this->partial($company, $user, true);

        $rows = app(PostWorkProcessReportService::class)->rows(['from' => '2026-01-01', 'to' => now()->toDateString()]);
        $this->assertSame([$open->id], $rows->where('type', 'partial')->pluck('id')->all());

        Livewire::test(PostWorkProcessReport::class, ['from' => '2026-01-01'])
            ->set('view', 'matrix')
            ->call('pickCell', 'await_fiscal_dispatch', 12)
            ->assertSee($open->Note->note)
            ->assertDontSee($rejected->Note->note);
    }

    private function partial(Company $company, User $user, bool $deny): Partial
    {
        $note = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        $partial = Partial::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'allow' => !$deny, 'deny' => $deny,
        ]);
        $partial->forceFill(['created_at' => '2026-03-02 10:00:00'])->save();

        return $partial;
    }

    public function test_old_final_and_d5_rows_are_listed_when_cell_is_clicked(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        $note = Note::create(['note' => uniqid('4'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        WorkReport::create([
            'note_id' => $note->id, 'company_id' => $company->id, 'user_id' => $user->id,
            'date' => '2026-03-02', 'informed_at' => '2026-03-02 10:00:00', 'rejected' => false, 'canceled' => false,
        ]);
        $d5note = Note::create(['note' => uniqid('5'), 'dt_status' => '2026-01-10 08:00:00', 'nstats' => 'NEW']);
        FiveNote::create(['note_id' => $d5note->id, 'note_d5' => uniqid('D5'), 'is_completed' => true, 'completed_at' => '2026-03-02 10:00:00']);

        Livewire::test(PostWorkProcessReport::class, ['from' => '2026-01-01'])
            ->set('view', 'matrix')
            ->call('pickCell', 'await_fiscal_dispatch', 12)
            ->assertSee($note->note)
            ->assertSee($d5note->note);
    }

    public function test_every_matrix_cell_lists_its_rows_for_all_ages(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($user);

        foreach (range(1, 30) as $daysAgo) {
            $partial = $this->partial($company, $user, false);
            $partial->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
        }

        $service = app(PostWorkProcessReportService::class);
        $rows    = $service->rows(['from' => '2020-01-01', 'to' => now()->toDateString()]);
        $max     = PostWorkProcessReportService::MATRIX_MAX_DAY;

        foreach ($rows as $row) {
            $day = min($row['age_days'], $max);
            $listed = $service->filterByCell($rows, $row['current_stage'], $day);
            $this->assertTrue($listed->contains('id', $row['id']), "age {$row['age_days']} not listed for cell {$day}");
        }
    }
}
