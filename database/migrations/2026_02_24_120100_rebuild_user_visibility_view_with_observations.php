<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE VIEW user_visibility_current AS
            SELECT
                v.viewer_id,
                v.descendant_id,
                MIN(v.depth) AS depth
            FROM (
                -- visão nativa da hierarquia
                SELECT
                    uc.ancestor_id AS viewer_id,
                    uc.descendant_id,
                    uc.depth
                FROM user_closure uc

                UNION ALL

                -- observação de subárvore (alvo + descendentes)
                SELECT
                    o.observer_id AS viewer_id,
                    uc.descendant_id,
                    uc.depth + 1 AS depth
                FROM user_observations o
                JOIN user_closure uc ON uc.ancestor_id = o.target_id
                WHERE o.mode = 'subtree'
                  AND NOW() >= o.valid_from
                  AND (o.valid_to IS NULL OR NOW() <= o.valid_to)

                UNION ALL

                -- observação pontual (somente o nó alvo)
                SELECT
                    o.observer_id AS viewer_id,
                    o.target_id AS descendant_id,
                    1 AS depth
                FROM user_observations o
                WHERE o.mode = 'node_only'
                  AND NOW() >= o.valid_from
                  AND (o.valid_to IS NULL OR NOW() <= o.valid_to)
            ) v
            GROUP BY v.viewer_id, v.descendant_id
        ");
    }

    public function down(): void
    {
        DB::unprepared("
            CREATE OR REPLACE VIEW user_visibility_current AS
            -- visão nativa (closure)
            SELECT uc.ancestor_id AS viewer_id,
                   uc.descendant_id,
                   uc.depth
            FROM user_closure uc
            UNION
            -- visão delegada (vigente)
            SELECT d.delegate_id AS viewer_id,
                   uc.descendant_id,
                   uc.depth
            FROM user_delegations d
            JOIN user_closure uc ON uc.ancestor_id = d.principal_id
            WHERE NOW() >= d.valid_from
              AND (d.valid_to IS NULL OR NOW() <= d.valid_to)
        ");
    }
};
