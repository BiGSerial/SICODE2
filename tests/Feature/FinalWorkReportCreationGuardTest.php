<?php

namespace Tests\Feature;

use App\Models\{Company, Note, Partial, User};
use App\Services\WorkReports\FinalWorkReportCreationGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalWorkReportCreationGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_final_creation_when_allowed_partial_is_open(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $user    = User::factory()->create();
        $note    = Note::create(['note' => '4000000020']);

        $partial = Partial::create([
            'note_id'    => $note->id,
            'company_id' => $company->id,
            'user_id'    => $user->id,
            'allow'      => true,
            'deny'       => false,
            'complete'   => false,
        ]);

        $guard = app(FinalWorkReportCreationGuard::class);

        $this->assertFalse($guard->canCreateFinalFor($note));
        $this->assertSame($partial->id, $guard->openPartialFor($note)?->id);
    }

    public function test_allows_final_creation_when_partial_is_completed(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $user    = User::factory()->create();
        $note    = Note::create(['note' => '4000000021']);

        Partial::create([
            'note_id'    => $note->id,
            'company_id' => $company->id,
            'user_id'    => $user->id,
            'allow'      => true,
            'deny'       => false,
            'complete'   => true,
        ]);

        $this->assertTrue(app(FinalWorkReportCreationGuard::class)->canCreateFinalFor($note));
    }

    public function test_allows_final_creation_when_partial_is_waiting_engineer_approval(): void
    {
        $company = Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $user    = User::factory()->create();
        $note    = Note::create(['note' => '4000000022']);

        Partial::create([
            'note_id'    => $note->id,
            'company_id' => $company->id,
            'user_id'    => $user->id,
            'allow'      => false,
            'deny'       => false,
            'complete'   => false,
        ]);

        $this->assertTrue(app(FinalWorkReportCreationGuard::class)->canCreateFinalFor($note));
    }
}
