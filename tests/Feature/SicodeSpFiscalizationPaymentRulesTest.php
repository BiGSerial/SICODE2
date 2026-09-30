<?php

namespace Tests\Feature;

use App\Models\{Note, Operation, Order, WorkReport};
use App\Repositories\SupervisionRepository;
use App\Services\Payment\NoteFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SicodeSpFiscalizationPaymentRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sp_operation_30_confirmed_enters_payment_without_operation_40_when_operation_50_is_released(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $note = $this->makeFinalWorkReportNote('4000001030', [
            ['operacao' => '0030', 'status' => 'CONF'],
            ['operacao' => '0050', 'status' => 'LIB'],
        ]);

        $this->assertTrue(
            app(NoteFilter::class)->filter(null, 'payments')->where('notes.id', $note->id)->exists()
        );
    }

    public function test_sp_operation_30_confirmed_does_not_enter_payment_without_operation_50_released(): void
    {
        config(['sicode.ruleset' => 'sp']);

        $note = $this->makeFinalWorkReportNote('4000001033', [
            ['operacao' => '0030', 'status' => 'CONF'],
        ]);

        $this->assertFalse(
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

    /** @dataProvider esOperation40Statuses */
    public function test_es_enters_payment_with_confirmed_30_accepted_40_and_released_50(string $operation40Status): void
    {
        config(['sicode.ruleset' => 'es']);

        $note = $this->makeFinalWorkReportNote('4000001040', [
            ['operacao' => '0030', 'status' => 'CONF'],
            ['operacao' => '0040', 'status' => $operation40Status],
            ['operacao' => '0050', 'status' => 'LIB'],
        ]);

        $this->assertTrue(
            app(NoteFilter::class)->filter(null, 'payments')->where('notes.id', $note->id)->exists()
        );
    }

    public static function esOperation40Statuses(): array
    {
        return [
            'cnpa'     => ['CNPA'],
            'jbfi lib' => ['JBFI LIB'],
            'lib'      => ['LIB'],
        ];
    }

    private function makeFinalWorkReportNote(string $number, array $operations): Note
    {
        $note = Note::create([
            'note'      => $number,
            'dt_status' => now(),
            'nstats'    => 'NEW',
        ]);

        $order = Order::create([
            'note_id'    => $note->id,
            'ordem'      => '1700001030',
            'statusSist' => 'LIB',
        ]);

        foreach ($operations as $operation) {
            Operation::create([
                'order_id' => $order->id,
                'operacao' => $operation['operacao'],
                'status'   => $operation['status'],
            ]);
        }

        $workReport = WorkReport::create([
            'note_id'     => $note->id,
            'date'        => '2026-09-10',
            'informed_at' => '2026-09-10 08:00:00',
            'rejected'    => false,
            'canceled'    => false,
        ]);
        $workReport->Orders()->sync([$order->id]);

        return $note;
    }
}
