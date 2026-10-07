<?php

namespace Tests\Feature;

use App\Enum\{CancellationRequestScope, CancellationRequestStatus};
use App\Models\{CancellationCategory, CancellationRequest, Note, User, WorkReport};
use App\Services\Payment\{CancellationRequestService, UncancellationRequestService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class CancellationWorkReportScopeTest extends TestCase
{
    use RefreshDatabase;

    private function makeNoteWithReports(int $count = 2): array
    {
        $note    = Note::create(['note' => 'N' . rand(1000, 9999)]);
        $reports = [];

        for ($i = 1; $i <= $count; $i++) {
            $reports[] = WorkReport::create([
                'note_id'     => $note->id,
                'date'        => '2026-08-0' . $i,
                'informed_at' => "2026-08-0{$i} 08:00:00",
            ]);
        }

        return [$note, $reports];
    }

    private function category(): CancellationCategory
    {
        return CancellationCategory::firstOrCreate(['slug' => 'pedido-do-cliente'], [
            'name'               => 'Pedido do Cliente',
            'active'             => true,
            'require_evidence'   => false,
            'min_evidence_files' => 0,
        ]);
    }

    private function user(): User
    {
        return User::factory()->create(['user' => true, 'can_dispatch' => true, 'superadm' => true]);
    }

    public function test_work_report_scope_requires_choosing_a_report(): void
    {
        [$note] = $this->makeNoteWithReports();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Selecione ao menos um informe');

        (new CancellationRequestService())->createRequest(
            $note,
            CancellationRequestScope::WORK_FORM_ONLY->value,
            $this->category(),
            [],
            [],
            $this->user(),
            'teste'
        );
    }

    public function test_report_from_another_note_is_rejected(): void
    {
        [$note]           = $this->makeNoteWithReports(1);
        [, $otherReports] = $this->makeNoteWithReports(1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não pertence à nota');

        (new CancellationRequestService())->createRequest(
            $note,
            CancellationRequestScope::WORK_FORM_ONLY->value,
            $this->category(),
            [],
            [],
            $this->user(),
            'teste',
            [$otherReports[0]->id]
        );
    }

    public function test_finalizing_cancels_only_the_chosen_report(): void
    {
        [$note, $reports] = $this->makeNoteWithReports(2);
        $user             = $this->user();
        $service          = new CancellationRequestService();

        $request = $service->createRequest(
            $note,
            CancellationRequestScope::WORK_FORM_ONLY->value,
            $this->category(),
            [],
            [],
            $user,
            'teste',
            [$reports[1]->id]
        );

        $this->assertDatabaseHas('cancellation_request_work_reports', [
            'cancellation_request_id' => $request->id,
            'work_report_id'          => $reports[1]->id,
        ]);

        $service->finalizeDone($request, $user);

        $this->assertFalse($reports[0]->fresh()->canceled);
        $this->assertTrue($reports[1]->fresh()->canceled);
        $this->assertFalse($note->fresh()->canceled);
    }

    public function test_open_request_blocks_overlapping_report_but_not_the_other(): void
    {
        [$note, $reports] = $this->makeNoteWithReports(2);
        $user             = $this->user();
        $service          = new CancellationRequestService();

        $service->createRequest($note, CancellationRequestScope::WORK_FORM_ONLY->value, $this->category(), [], [], $user, 'a', [$reports[0]->id]);

        // O outro informe continua livre para ser solicitado.
        $second = $service->createRequest($note, CancellationRequestScope::WORK_FORM_ONLY->value, $this->category(), [], [], $user, 'b', [$reports[1]->id]);
        $this->assertSame(CancellationRequestStatus::SUBMITTED, $second->status);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Já existe solicitação');

        $service->createRequest($note, CancellationRequestScope::WORK_FORM_ONLY->value, $this->category(), [], [], $user, 'c', [$reports[0]->id]);
    }

    public function test_legacy_request_without_reports_cancels_all_active_reports(): void
    {
        [$note, $reports] = $this->makeNoteWithReports(2);
        $user             = $this->user();

        $request = CancellationRequest::create([
            'note_id'      => $note->id,
            'scope'        => CancellationRequestScope::WORK_FORM_ONLY,
            'category_id'  => $this->category()->id,
            'requested_by' => $user->id,
            'status'       => CancellationRequestStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);

        (new CancellationRequestService())->finalizeDone($request, $user);

        $this->assertTrue($reports[0]->fresh()->canceled);
        $this->assertTrue($reports[1]->fresh()->canceled);
    }

    public function test_uncancellation_restores_only_the_chosen_report(): void
    {
        [$note, $reports] = $this->makeNoteWithReports(2);
        $user             = $this->user();

        foreach ($reports as $report) {
            $report->update(['canceled' => true, 'canceled_at' => now(), 'canceled_by' => $user->id]);
        }

        $service = new UncancellationRequestService();
        $request = $service->createRequest(
            $note,
            CancellationRequestScope::WORK_FORM_ONLY->value,
            [],
            $user,
            'voltar',
            [$reports[0]->id]
        );

        $service->finalizeDone($request, $user);

        $this->assertFalse($reports[0]->fresh()->canceled);
        $this->assertTrue($reports[1]->fresh()->canceled);

        // Desfazer o descancelamento recancela somente o informe restaurado.
        $service->revertDone($request->fresh(), $user, 'engano');

        $this->assertTrue($reports[0]->fresh()->canceled);
        $this->assertTrue($reports[1]->fresh()->canceled);
    }

    public function test_uncancellation_rejects_report_that_is_not_canceled(): void
    {
        [$note, $reports] = $this->makeNoteWithReports(2);
        $user             = $this->user();

        // O informe 2 está cancelado; pedir o descancelamento do informe 1 (ativo) deve falhar.
        $reports[1]->update(['canceled' => true, 'canceled_at' => now(), 'canceled_by' => $user->id]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não está cancelado');

        (new UncancellationRequestService())->createRequest(
            $note,
            CancellationRequestScope::WORK_FORM_ONLY->value,
            [],
            $user,
            'voltar',
            [$reports[0]->id]
        );
    }

    public function test_full_note_cancellation_cancels_every_active_report_and_records_them(): void
    {
        [$note, $reports] = $this->makeNoteWithReports(2);
        $user             = $this->user();
        $service          = new CancellationRequestService();

        $request = $service->createRequest($note, CancellationRequestScope::NOTE_FULL->value, $this->category(), [], [], $user, 'nota inteira');
        $service->finalizeDone($request, $user);

        $this->assertTrue($note->fresh()->canceled);
        $this->assertTrue($reports[0]->fresh()->canceled);
        $this->assertTrue($reports[1]->fresh()->canceled);

        $done = $request->Events()->where('type', 'done')->first();
        $this->assertEqualsCanonicalizing([$reports[0]->id, $reports[1]->id], $done->meta['work_report_ids']);
    }

    public function test_create_screen_lists_reports_and_submits_with_the_chosen_one(): void
    {
        [$note, $reports] = $this->makeNoteWithReports(2);
        $user             = $this->user();
        $category         = $this->category();

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Services\Payment\Cancellation\RequestCreate::class, ['service' => 'x'])
            ->set('noteSearch', $note->note)
            ->call('findNote')
            ->assertSee('Informe #' . $reports[0]->id)
            ->assertSee('Informe #' . $reports[1]->id)
            ->assertDontSee('Somente WorkForm')
            ->assertSee('Cancelar somente informe(s)')
            ->set('scope', CancellationRequestScope::WORK_FORM_ONLY->value)
            ->set('categoryId', $category->id)
            ->set('description', 'informe errado')
            ->call('submit')
            ->assertHasErrors('selectedWorkReports')
            ->set('selectedWorkReports', [(string) $reports[1]->id])
            ->call('confirmSubmit');

        $request = \App\Models\CancellationRequest::query()->where('note_id', $note->id)->first();

        $this->assertNotNull($request);
        $this->assertSame([$reports[1]->id], $request->WorkReports()->pluck('work_reports.id')->all());
    }

    public function test_uncancellation_screen_starts_without_a_report_selected(): void
    {
        [$note, $reports] = $this->makeNoteWithReports(2);
        $user             = $this->user();

        foreach ($reports as $report) {
            $report->update(['canceled' => true, 'canceled_at' => now(), 'canceled_by' => $user->id]);
        }

        Livewire::actingAs($user)
            ->test(\App\Http\Livewire\Services\Payment\Uncancellation\RequestCreate::class, ['service' => 'x'])
            ->set('noteSearch', $note->note)
            ->call('findNote')
            ->assertSee('Informe #' . $reports[0]->id)
            ->set('scope', CancellationRequestScope::WORK_FORM_ONLY->value)
            ->assertSet('selectedWorkReports', [])
            ->set('description', 'voltar')
            ->call('submit')
            ->assertHasErrors('selectedWorkReports');
    }
}
