<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\Operation;
use App\Models\Order;
use App\Models\WorkReport;
use App\Repositories\SupervisionRepository;
use App\Services\Payment\NoteFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SicodeSpFiscalizationPaymentRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sp_operation_30_confirmed_enters_payment_without_operation_40_or_50(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $note = $this->makeFinalWorkReportNote('4000001030', [
            ['operacao' => '0030', 'status' => 'CONF'],
        ]);

        $this->assertTrue(
            app(NoteFilter::class)->filter(null, 'payments')->where('notes.id', $note->id)->exists()
        );
    }

    public function test_sp_operation_30_confirmed_leaves_supervision_even_when_legacy_operation_40_rule_matches(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $note = $this->makeFinalWorkReportNote('4000001031', [
            ['operacao' => '0010', 'status' => 'CONF'],
            ['operacao' => '0030', 'status' => 'CONF'],
            ['operacao' => '0040', 'status' => 'LIB'],
        ]);

        $this->assertFalse(
            app(SupervisionRepository::class)->getBaseQuery()->where('notes.id', $note->id)->exists()
        );
    }

    public function test_es_keeps_payment_waiting_for_legacy_operation_40_and_50_rules(): void
    {
        config(['sicode.ruleset' => 'es']);

        $note = $this->makeFinalWorkReportNote('4000001032', [
            ['operacao' => '0030', 'status' => 'CONF'],
        ]);

        $this->assertFalse(
            app(NoteFilter::class)->filter(null, 'payments')->where('notes.id', $note->id)->exists()
        );
    }

    private function makeFinalWorkReportNote(string $number, array $operations): Note
    {
        $note = Note::create([
            'note' => $number,
            'dt_status' => now(),
            'nstats' => 'NEW',
        ]);

        $order = Order::create([
            'note_id' => $note->id,
            'ordem' => '1700001030',
            'statusSist' => 'LIB',
        ]);

        foreach ($operations as $operation) {
            Operation::create([
                'order_id' => $order->id,
                'operacao' => $operation['operacao'],
                'status' => $operation['status'],
            ]);
        }

        WorkReport::create([
            'note_id' => $note->id,
            'date' => '2026-09-10',
            'informed_at' => '2026-09-10 08:00:00',
            'rejected' => false,
            'canceled' => false,
        ]);

        return $note;
    }
}
