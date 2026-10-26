<?php

use App\Support\Database\GuardedTableRecreator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Pendências entre áreas (Medição, Fiscalização, Encerramento) além da parceira. MIGRATION ADITIVA: closure_issues NUNCA é
 * recriada (guarda o estado operacional do Encerramento). Só ADD de colunas/índice, cada um com hasColumn/hasIndex, e valores
 * padrão que mantêm as linhas existentes como pendências da parceira: origem Encerramento, destino PARTNER, tipo ISSUE, manual.
 * Nenhuma coluna existente é alterada, renomeada ou removida.
 */
return new class () extends Migration {
    private const INDEX = 'closure_issue_destination_area_status_index';

    public function up(): void
    {
        if (!Schema::hasTable('closure_issues')) {
            return;
        }

        Schema::table('closure_issues', function (Blueprint $table) {
            if (!Schema::hasColumn('closure_issues', 'origin_area')) {
                $table->string('origin_area', 20)->default('CLOSURE')->after('destination_company_id');
            }

            if (!Schema::hasColumn('closure_issues', 'destination_area')) {
                $table->string('destination_area', 20)->default('PARTNER')->after('origin_area');
            }

            if (!Schema::hasColumn('closure_issues', 'kind')) {
                $table->string('kind', 20)->default('ISSUE')->after('destination_area');
            }

            if (!Schema::hasColumn('closure_issues', 'source')) {
                $table->string('source', 20)->default('MANUAL')->after('kind');
            }
        });

        if (!$this->hasIndex()) {
            Schema::table('closure_issues', function (Blueprint $table) {
                $table->index(['destination_area', 'status'], self::INDEX);
            });
        }
    }

    /** Remove apenas o que esta migration adicionou (e só se existir). */
    public function down(): void
    {
        if (!Schema::hasTable('closure_issues')) {
            return;
        }

        if ($this->hasIndex()) {
            Schema::table('closure_issues', function (Blueprint $table) {
                $table->dropIndex(self::INDEX);
            });
        }

        foreach (['source', 'kind', 'destination_area', 'origin_area'] as $column) {
            if (Schema::hasColumn('closure_issues', $column)) {
                Schema::table('closure_issues', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }

    private function hasIndex(): bool
    {
        return GuardedTableRecreator::hasIndex('closure_issues', self::INDEX);
    }
};
