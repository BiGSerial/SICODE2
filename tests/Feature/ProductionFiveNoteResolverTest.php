<?php

namespace Tests\Feature;

use App\Models\{Company, FiveNote, Note, Production, Service, User, WorkReport};
use App\Services\D5\ProductionFiveNoteResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionFiveNoteResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_without_dfive_flag_never_resolves_a_d5(): void
    {
        [$note, $payment] = $this->fixture();
        $this->five($note);
        $production = $this->production($note, $payment, ['dfive' => false]);

        $this->assertNull(app(ProductionFiveNoteResolver::class)->resolve($production));
    }

    public function test_resolves_the_explicitly_linked_d5_not_the_legacy_one(): void
    {
        [$note, $payment] = $this->fixture();
        $report = $this->report($note);
        $this->five($note); // legada (sem informe)
        $linked = $this->five($note, ['work_report_id' => $report->id]);
        $production = $this->production($note, $payment);
        $linked->productions()->attach($production->id);

        $this->assertSame($linked->id, app(ProductionFiveNoteResolver::class)->resolve($production->fresh())->id);
    }

    public function test_ambiguous_note_without_link_does_not_guess(): void
    {
        [$note, $payment] = $this->fixture();
        $this->five($note);
        $this->five($note, ['work_report_id' => $this->report($note)->id]);
        $production = $this->production($note, $payment);

        $this->assertNull(app(ProductionFiveNoteResolver::class)->resolve($production));
    }

    public function test_dfive_production_without_associated_d5_resolves_nothing(): void
    {
        [$note, $payment] = $this->fixture();
        $this->five($note);
        $production = $this->production($note, $payment);

        $this->assertNull(app(ProductionFiveNoteResolver::class)->resolve($production));
    }

    public function test_dispatched_d5_does_not_await_payment_creation(): void
    {
        [$note, $payment] = $this->fixture();

        $fresh      = $this->five($note);
        $dispatched = $this->five($note, ['work_report_id' => $this->report($note)->id, 'visible_partner' => true]);
        $assigned   = $this->five($note, ['work_report_id' => $this->report($note)->id]);
        $assigned->productions()->attach($this->production($note, $payment)->id);

        $this->assertTrue($fresh->isAwaitingPaymentCreation());
        $this->assertFalse($dispatched->isAwaitingPaymentCreation());
        $this->assertFalse($assigned->isAwaitingPaymentCreation());
        $this->assertSame([$fresh->id], FiveNote::awaitingPaymentCreation()->pluck('id')->all());
        $this->assertTrue($fresh->needsPaymentAction());
        $this->assertFalse($dispatched->needsPaymentAction());
    }

    public function test_dispatch_context_ignores_dispatched_d5_without_supervision_for_payment(): void
    {
        [$note, $payment] = $this->fixture();
        $payment->update(['folder' => 'pagamento']);
        $this->five($note, ['visible_partner' => true, 'is_completed' => true]);

        $context = app(\App\Services\Dispatch\DispatchContextResolver::class)->for($note->fresh(), $payment);

        $this->assertFalse($context['is_d5_fiscalization']);
    }

    public function test_payment_context_keeps_legacy_and_informe_d5_independent(): void
    {
        [$note, $payment] = $this->fixture();
        $payment->update(['folder' => 'pagamento']);
        $report = $this->report($note);
        $legacy = $this->five($note); // legada, ainda sem despacho
        $own    = $this->five($note, ['work_report_id' => $report->id, 'visible_partner' => true, 'is_completed' => true]);
        $resolver = app(\App\Services\Dispatch\DispatchContextResolver::class);

        $legacyRow = Note::find($note->id);
        $this->assertTrue($resolver->for($legacyRow, $payment)['is_d5_fiscalization']);

        $informeRow = Note::find($note->id);
        $informeRow->setAttribute('dispatch_work_report_id', $report->id);
        $informeRow->setRelation('WorkForm', $report->load('FiveNote'));
        $this->assertFalse($resolver->for($informeRow, $payment)['is_d5_fiscalization']);
        $this->assertTrue($legacy->needsPaymentAction());
        $this->assertFalse($own->needsPaymentAction());
    }

    private function fixture(): array
    {
        $note    = Note::create(['note' => uniqid('400'), 'dt_status' => '2026-09-10 08:00:00', 'nstats' => 'NEW']);
        $payment = Service::create(['service' => 'Pagamento', 'folder' => 'pagamento']);

        return [$note, $payment];
    }

    private function report(Note $note): WorkReport
    {
        $company = Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);
        $user    = User::factory()->create(['company_id' => $company->id]);

        return WorkReport::create([
            'note_id'     => $note->id,
            'company_id'  => $company->id,
            'user_id'     => $user->id,
            'date'        => '2026-09-10',
            'informed_at' => '2026-09-10 08:00:00',
            'rejected'    => false,
            'canceled'    => false,
        ]);
    }

    private function five(Note $note, array $attrs = []): FiveNote
    {
        return FiveNote::create(array_merge(['note_id' => $note->id, 'note_d5' => uniqid('D5')], $attrs));
    }

    private function production(Note $note, Service $service, array $attrs = []): Production
    {
        $company = Company::first() ?? Company::create(['name' => 'Compel', 'email' => uniqid('c') . '@example.com']);

        return Production::create(array_merge([
            'company_id' => $company->id,
            'note_id'    => $note->id,
            'service_id' => $service->uuid,
            'completed'  => false,
            'confirmed'  => false,
            'status'     => 2,
            'dfive'      => true,
        ], $attrs));
    }
}
