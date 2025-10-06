<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        // PRODUCTIONS
        Schema::table('productions', function (Blueprint $table) {
            // Contagens frequentes: service_id + completed + user_id
            $table->index(['service_id','completed','user_id'], 'idx_prod_srv_comp_user');

            // Listagens por confirmado=0 e serviço
            $table->index(['service_id','confirmed'], 'idx_prod_srv_confirmed');

            // EXISTS por user/status != 29 (o otimizador usa índice mesmo com !=)
            $table->index(['user_id','status'], 'idx_prod_user_status');

            // Junções por note_id
            $table->index(['note_id'], 'idx_prod_note');

            // Subconsulta “latest” por service_id + note_id + created_at (+ id)
            $table->index(['service_id','note_id','created_at','id'], 'idx_prod_srv_note_created');
        });

        // PRODTRANSFERS
        Schema::table('prodtransfers', function (Blueprint $table) {
            // count(*) where service_id + to + read_to = 0
            $table->index(['service_id','to','read_to'], 'idx_pt_srv_to_read');
        });

        // USER_ASSIGNMENTS
        Schema::table('user_assignments', function (Blueprint $table) {
            // EXISTS ligando com med_protests.id
            $table->index(['assignable_type','assignable_id'], 'idx_ua_type_assignable');

            // Aggregations por user + filtros completed/responsible
            $table->index(['assignable_type','completed','responsible','user_id'], 'idx_ua_type_comp_resp_user');

            // (Opcional – se filtra muito por user=1/monitoring=1, crie depois conforme necessidade)
            // $table->index(['assignable_type','completed','responsible','user','monitoring','user_id'], 'idx_ua_type_comp_resp_user_mon');
        });

        // VIABILITIES
        Schema::table('viabilities', function (Blueprint $table) {
            // NOT EXISTS(tacit_comments) + faixa de data + flags + empresa
            $table->index(['company_id','approved','completed','tacit','tacit_at'], 'idx_viab_company_flags_tacit_at');
        });

        // TACIT_COMMENTS
        Schema::table('tacit_comments', function (Blueprint $table) {
            $table->index(['viability_id'], 'idx_tacit_viability_id');
        });

        // WORK_REPORTS
        Schema::table('work_reports', function (Blueprint $table) {
            // (0=1 OR company_id=...) and rejected=1
            $table->index(['company_id','rejected'], 'idx_wr_company_rejected');
        });

        // OPERATION_RESPS
        Schema::table('operation_resps', function (Blueprint $table) {
            // MAX(fimLancado) por note_id
            $table->index(['note_id','fimLancado'], 'idx_opresps_note_fim');
        });

        // PARTIALS
        Schema::table('partials', function (Blueprint $table) {
            // ROW_NUMBER() OVER (...) com filtros allow/deny/supervision e ordenação por id desc
            $table->index(['note_id','allow','deny','supervision','id'], 'idx_partials_note_flags_id');
        });

        // (Opcional) ANALYZE para atualizar estatísticas do otimizador após criar índices
        // Comente se preferir rodar manualmente fora da migration.
        // foreach ([
        //     'productions','prodtransfers','user_assignments','viabilities',
        //     'tacit_comments','work_reports','operation_resps','partials'
        // ] as $tbl) {
        //     try {
        //         DB::statement("ANALYZE TABLE {$tbl}");
        //     } catch (\Throwable $e) {
        //         // ignora erros de ANALYZE em ambientes sem permissão
        //     }
        // }
    }

    public function down(): void
    {
        // Reverte na ordem inversa

        Schema::table('partials', function (Blueprint $table) {
            $table->dropIndex('idx_partials_note_flags_id');
        });

        Schema::table('operation_resps', function (Blueprint $table) {
            $table->dropIndex('idx_opresps_note_fim');
        });

        Schema::table('work_reports', function (Blueprint $table) {
            $table->dropIndex('idx_wr_company_rejected');
        });

        Schema::table('tacit_comments', function (Blueprint $table) {
            $table->dropIndex('idx_tacit_viability_id');
        });

        Schema::table('viabilities', function (Blueprint $table) {
            $table->dropIndex('idx_viab_company_flags_tacit_at');
        });

        Schema::table('user_assignments', function (Blueprint $table) {
            $table->dropIndex('idx_ua_type_assignable');
            $table->dropIndex('idx_ua_type_comp_resp_user');
            // $table->dropIndex('idx_ua_type_comp_resp_user_mon'); // se tiver criado
        });

        Schema::table('prodtransfers', function (Blueprint $table) {
            $table->dropIndex('idx_pt_srv_to_read');
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->dropIndex('idx_prod_srv_comp_user');
            $table->dropIndex('idx_prod_srv_confirmed');
            $table->dropIndex('idx_prod_user_status');
            $table->dropIndex('idx_prod_note');
            $table->dropIndex('idx_prod_srv_note_created');
        });
    }
};
