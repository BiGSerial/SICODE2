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

    public function test_open_partial_only_blocks_final_of_the_same_scope(): void
    {
        $note  = $this->noteWithPartial('4000000023', 'network', allow: true);
        $guard = app(FinalWorkReportCreationGuard::class);

        $this->assertFalse($guard->canCreateFinalFor($note, ['network']));
        $this->assertTrue($guard->canCreateFinalFor($note, ['connection']));
    }

    public function test_general_partial_blocks_any_final_scope(): void
    {
        $note = $this->noteWithPartial('4000000024', 'general', allow: true);

        $this->assertFalse(app(FinalWorkReportCreationGuard::class)->canCreateFinalFor($note, ['connection']));
    }

    public function test_final_cancels_only_pending_partials_of_its_scope(): void
    {
        $note  = $this->noteWithPartial('4000000025', 'network', allow: false);
        $other = $this->noteWithPartial('4000000025b', 'connection', allow: false, note: $note);
        $guard = app(FinalWorkReportCreationGuard::class);

        $this->assertSame(1, $guard->cancelPendingPartialsFor($note, ['network']));

        $network = $note->Partials()->where('scope', 'network')->first();
        $this->assertTrue((bool) $network->deny);
        $this->assertTrue((bool) $network->complete);
        $this->assertFalse((bool) $note->Partials()->where('scope', 'connection')->first()->deny);
    }

    public function test_final_never_cancels_an_approved_partial(): void
    {
        $note = $this->noteWithPartial('4000000026', 'network', allow: true);

        $this->assertSame(0, app(FinalWorkReportCreationGuard::class)->cancelPendingPartialsFor($note, ['network']));
        $this->assertFalse((bool) $note->Partials()->first()->deny);
    }

    private function noteWithPartial(string $number, string $scope, bool $allow, ?Note $note = null): Note
    {
        $company = Company::first() ?? Company::create(['name' => 'Compel', 'email' => 'compel@example.com']);
        $user    = User::factory()->create();
        $note ??= Note::create(['note' => $number]);

        Partial::create([
            'note_id'    => $note->id,
            'scope'      => $scope,
            'company_id' => $company->id,
            'user_id'    => $user->id,
            'allow'      => $allow,
            'deny'       => false,
            'complete'   => false,
        ]);

        return $note;
    }
}
