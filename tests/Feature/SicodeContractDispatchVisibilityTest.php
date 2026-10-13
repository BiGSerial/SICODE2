<?php

namespace Tests\Feature;

use App\Models\{Company, Note, Production, User};
use App\Support\SicodeRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SicodeContractDispatchVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervision_can_show_an_eligible_note_after_the_partner_completed_its_previous_activity(): void
    {
        $company = Company::create(['name' => 'Parceira', 'email' => 'parceira@example.com']);
        $user    = User::factory()->create(['contract' => true, 'company_id' => $company->id]);
        $note    = Note::create(['note' => '4000000901']);

        Production::create([
            'note_id'    => $note->id,
            'service_id' => 'supervision-service',
            'company_id' => $company->id,
            'completed'  => true,
            'confirmed'  => true,
        ]);

        $query = Note::query();
        SicodeRules::applyContractDispatchMainVisibility(
            $query,
            $user,
            'supervision-service',
            fn ($statusQuery) => $statusQuery->whereRaw('1 = 1'),
            includeCompletedCompanyActivity: true
        );

        $this->assertSame([$note->id], $query->pluck('id')->all());
    }

    public function test_completed_activity_is_not_visible_when_the_caller_does_not_opt_in(): void
    {
        $company = Company::create(['name' => 'Parceira', 'email' => 'parceira@example.com']);
        $user    = User::factory()->create(['contract' => true, 'company_id' => $company->id]);
        $note    = Note::create(['note' => '4000000902']);

        Production::create([
            'note_id'    => $note->id,
            'service_id' => 'supervision-service',
            'company_id' => $company->id,
            'completed'  => true,
            'confirmed'  => true,
        ]);

        $query = Note::query();
        SicodeRules::applyContractDispatchMainVisibility(
            $query,
            $user,
            'supervision-service',
            fn ($statusQuery) => $statusQuery->whereRaw('1 = 1')
        );

        $this->assertSame([], $query->pluck('id')->all());
    }
}
