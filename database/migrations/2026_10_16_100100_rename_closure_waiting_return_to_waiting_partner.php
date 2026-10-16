<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $this->rename('WAITING_RETURN', 'WAITING_PARTNER');
    }

    public function down(): void
    {
        $this->rename('WAITING_PARTNER', 'WAITING_RETURN');
    }

    private function rename(string $from, string $to): void
    {
        DB::table('closure_order_states')
            ->where('operational_status', $from)
            ->update(['operational_status' => $to]);

        DB::table('closure_order_state_periods')
            ->where('operational_status', $from)
            ->update(['operational_status' => $to]);

        // Os eventos continuam imutáveis para a aplicação; aqui só o valor de status guardado nos snapshots é migrado.
        // O tipo do evento (coluna `type`) não é alterado: WAITING_RETURN segue como nome histórico de evento.
        foreach (['before_data', 'after_data'] as $column) {
            DB::table('closure_order_events')
                ->where($column, 'like', "%\"{$from}\"%")
                ->update([$column => DB::raw("REPLACE({$column}, '\"{$from}\"', '\"{$to}\"')")]);
        }

        DB::table('closure_issues')
            ->where('block_state_snapshot', 'like', "%\"{$from}\"%")
            ->update(['block_state_snapshot' => DB::raw("REPLACE(block_state_snapshot, '\"{$from}\"', '\"{$to}\"')")]);
    }
};
