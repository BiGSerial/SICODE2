<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

/*
 * Índices ADITIVOS para o painel gerencial do Encerramento (consulta mensal por tipo de evento e por data de abertura/envio).
 * Nenhuma coluna nem tabela muda; só entra índice quando ele ainda não existe.
 */
return new class () extends Migration {
    private const INDEXES = [
        ['closure_order_events', ['type', 'created_at'], 'closure_order_events_type_created_index'],
        ['closure_issues', ['opened_at'], 'closure_issues_opened_at_index'],
        ['closure_issue_events', ['type', 'created_at'], 'closure_issue_events_type_created_index'],
        ['closure_internal_returns', ['sent_at'], 'closure_internal_returns_sent_at_index'],
        ['closure_partner_signals', ['created_at'], 'closure_partner_signals_created_at_index'],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as [$table, $columns, $name]) {
            if (Schema::hasTable($table) && !$this->hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as [$table, , $name]) {
            if (Schema::hasTable($table) && $this->hasIndex($table, $name)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
            }
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
