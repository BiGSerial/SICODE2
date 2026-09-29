<?php

namespace Tests\Feature;

use App\Http\Livewire\Partner\Forms\ReceiveAdsfomrm;
use App\Models\Adsform;
use App\Models\Company;
use App\Models\File;
use App\Models\Note;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReceiveAdsWorkReportSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_selects_the_requested_work_report_for_ads_delivery(): void
    {
        [$user, $note, $networkReport, $connectionReport] = $this->noteWithTwoWorkReports();

        Livewire::actingAs($user)
            ->test(ReceiveAdsfomrm::class)
            ->call('getNote', $note->id, $networkReport->id)
            ->assertSet('selectedWorkReportId', (string) $networkReport->id)
            ->assertSet('workReportOptions.0.id', (string) $connectionReport->id)
            ->assertSet('workReportOptions.1.id', (string) $networkReport->id);
    }

    public function test_blocks_only_the_work_report_that_already_has_delivered_ads(): void
    {
        [$user, $note, $networkReport, $connectionReport] = $this->noteWithTwoWorkReports();

        Adsform::create([
            'work_report_id' => $connectionReport->id,
            'note_id' => $note->id,
            'user_id' => $user->id,
            'amount' => 0,
            'partial' => false,
            'tacit' => true,
            'tacit_delivered_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(ReceiveAdsfomrm::class)
            ->call('getNote', $note->id)
            ->assertSet('selectedWorkReportId', (string) $networkReport->id)
            ->assertSet('workReportOptions.0.id', (string) $connectionReport->id)
            ->assertSet('workReportOptions.0.block', true)
            ->assertSet('workReportOptions.1.id', (string) $networkReport->id)
            ->assertSet('workReportOptions.1.block', false);
    }

    public function test_tacit_ads_without_partner_delivery_does_not_block_scope_selection(): void
    {
        [$user, $note, $networkReport, $connectionReport] = $this->noteWithTwoWorkReports();

        Adsform::create([
            'work_report_id' => $connectionReport->id,
            'note_id' => $note->id,
            'user_id' => $user->id,
            'amount' => 0,
            'partial' => false,
            'tacit' => true,
            'tacit_delivered_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(ReceiveAdsfomrm::class)
            ->call('getNote', $note->id)
            ->assertSet('selectedWorkReportId', null)
            ->assertSet('workReportOptions.0.id', (string) $connectionReport->id)
            ->assertSet('workReportOptions.0.block', false)
            ->assertSet('workReportOptions.1.id', (string) $networkReport->id)
            ->assertSet('workReportOptions.1.block', false);
    }

    public function test_normal_ads_with_file_blocks_only_that_scope(): void
    {
        [$user, $note, $networkReport, $connectionReport] = $this->noteWithTwoWorkReports();

        $adsForm = Adsform::create([
            'work_report_id' => $connectionReport->id,
            'note_id' => $note->id,
            'user_id' => $user->id,
            'amount' => 0,
            'partial' => false,
            'tacit' => false,
        ]);

        $file = File::create([
            'note_id' => $note->id,
            'user_id' => $user->id,
            'file_name' => 'ads-final.xlsx',
            'path' => '/tmp/ads-final.xlsx',
            'ext' => 'xlsx',
        ]);

        $adsForm->Files()->attach($file->id);

        Livewire::actingAs($user)
            ->test(ReceiveAdsfomrm::class)
            ->call('getNote', $note->id)
            ->assertSet('selectedWorkReportId', (string) $networkReport->id)
            ->assertSet('workReportOptions.0.id', (string) $connectionReport->id)
            ->assertSet('workReportOptions.0.block', true)
            ->assertSet('workReportOptions.1.id', (string) $networkReport->id)
            ->assertSet('workReportOptions.1.block', false);
    }

    private function noteWithTwoWorkReports(): array
    {
        config(['sicode.ruleset' => 'sp']);

        $user = User::factory()->create();
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $note = Note::create(['note' => '4001951584', 'type_note' => 1]);

        $networkOrder = Order::create([
            'note_id' => $note->id,
            'ordem' => '170000000001',
            'statusSist' => 'ABER',
        ]);
        $connectionOrder = Order::create([
            'note_id' => $note->id,
            'ordem' => '180000000001',
            'statusSist' => 'ABER',
        ]);

        $networkReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-08-01',
            'informed_at' => '2026-08-01 08:00:00',
            'selected_final_scopes' => ['network'],
        ]);
        $networkReport->Orders()->sync([$networkOrder->id]);

        $connectionReport = WorkReport::create([
            'note_id' => $note->id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'date' => '2026-08-02',
            'informed_at' => '2026-08-02 08:00:00',
            'selected_final_scopes' => ['connection'],
        ]);
        $connectionReport->Orders()->sync([$connectionOrder->id]);

        return [$user, $note, $networkReport, $connectionReport];
    }
}
