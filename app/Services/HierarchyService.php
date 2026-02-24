<?php

namespace App\Services;

use App\Models\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class HierarchyService
{
    /**
     * Reconstrói toda a user_closure a partir de users.manager_id.
     * - Limpa a tabela (TRUNCATE) e refaz tudo do zero (rápido e seguro).
     * - Insere as linhas reflexivas (id,id,0).
     * - Propaga ancestrais com laços até estabilizar.
     */
    public function rebuildAll(bool $dryRun = false): void
    {
        // 1) Limpa FORA de transação para não quebrar o TX
        if (!$dryRun) {
            // Opcional: desliga FK se quiser usar TRUNCATE. Mas com DELETE basta manter.
            // DB::statement('SET FOREIGN_KEY_CHECKS=0');
            // DB::statement('TRUNCATE TABLE user_closure');
            // DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::table('user_closure')->delete(); // <= use DELETE no lugar do TRUNCATE
        }

        // 2) Agora sim, tudo dentro de uma transação
        DB::transaction(function () use ($dryRun) {
            // 2.1) self rows (id,id,0)
            $ids = DB::table('users')->pluck('id');
            $rows = [];
            foreach ($ids as $id) {
                $rows[] = ['ancestor_id' => $id, 'descendant_id' => $id, 'depth' => 0];
                if (count($rows) >= 1000 && !$dryRun) {
                    DB::table('user_closure')->insertOrIgnore($rows);
                    $rows = [];
                }
            }
            if (!$dryRun && $rows) {
                DB::table('user_closure')->insertOrIgnore($rows);
            }

            // 2.2) propagar ancestrais do manager até estabilizar
            do {
                $sql = "
                INSERT IGNORE INTO user_closure (ancestor_id, descendant_id, depth)
                SELECT uc.ancestor_id, u.id AS descendant_id, uc.depth + 1 AS depth
                FROM users u
                JOIN user_closure uc ON uc.descendant_id = u.manager_id
                LEFT JOIN user_closure exists_row
                  ON exists_row.ancestor_id = uc.ancestor_id
                 AND exists_row.descendant_id = u.id
                WHERE u.manager_id IS NOT NULL
                  AND exists_row.ancestor_id IS NULL
            ";
                $inserted = $dryRun ? 0 : DB::affectingStatement($sql);
            } while ($inserted > 0);
        });
    }


    /**
     * Move uma subárvore (userId e todos seus descendentes) para um novo manager.
     * - Atualiza users.manager_id
     * - Remove as ligações externas antigas na closure
     * - Conecta a subárvore aos ancestrais do newManager
     */
    public function moveSubtree(string $userId, ?string $newManagerId): void
    {
        DB::transaction(function () use ($userId, $newManagerId) {
            if ($newManagerId === $userId) {
                throw new \InvalidArgumentException('Um usuário não pode gerenciar a si mesmo.');
            }

            $oldManagerId = DB::table('users')->where('id', $userId)->value('manager_id');

            // Sem mudança efetiva de vínculo hierárquico.
            if ((string) $oldManagerId === (string) $newManagerId) {
                return;
            }

            // Evita ciclo: newManager não pode estar na subárvore de $userId
            if ($newManagerId) {
                $cycle = DB::table('user_closure')
                    ->where('ancestor_id', $userId)
                    ->where('descendant_id', $newManagerId)
                    ->exists();
                if ($cycle) {
                    throw new \InvalidArgumentException('Ciclo detectado: o novo gestor está na subárvore do usuário.');
                }
            }

            // 1) Atualiza manager
            DB::table('users')->where('id', $userId)->update(['manager_id' => $newManagerId]);

            // 2) Subárvore (inclui o próprio userId)
            /** @var Collection $subtree */
            $subtree = DB::table('user_closure')
                ->where('ancestor_id', $userId)
                ->select('descendant_id')
                ->pluck('descendant_id');

            if ($subtree->isEmpty()) {
                // Falha rara: se não existir linha reflexiva do próprio user, garante
                DB::table('user_closure')->insertOrIgnore([
                    ['ancestor_id' => $userId, 'descendant_id' => $userId, 'depth' => 0]
                ]);
                $subtree = collect([$userId]);
            }

            // 3) Remove ligações externas -> subárvore (mantém internas)
            DB::table('user_closure')
                ->whereIn('descendant_id', $subtree)
                ->whereNotIn('ancestor_id', $subtree)
                ->delete();

            // 4) Conecta ancestrais do newManager à subárvore
            if ($newManagerId) {
                // ancestrais do newManager (inclui ele mesmo com depth=0)
                $ancestors = DB::table('user_closure')
                    ->where('descendant_id', $newManagerId)
                    ->select('ancestor_id', 'depth')
                    ->get();

                if ($ancestors->isEmpty()) {
                    // Garante linha reflexiva do newManager
                    DB::table('user_closure')->insertOrIgnore([
                        ['ancestor_id' => $newManagerId, 'descendant_id' => $newManagerId, 'depth' => 0]
                    ]);
                    $ancestors = DB::table('user_closure')
                        ->where('descendant_id', $newManagerId)
                        ->select('ancestor_id', 'depth')
                        ->get();
                }

                // todas as distâncias internas a partir do root da subárvore (userId)
                $descents = DB::table('user_closure')
                    ->where('ancestor_id', $userId)
                    ->select('descendant_id', 'depth')
                    ->get();

                // monta linhas: (ancestor_of_manager) x (descendant_of_userRoot)
                $batch = [];
                foreach ($ancestors as $a) {
                    foreach ($descents as $d) {
                        $batch[] = [
                            'ancestor_id'   => $a->ancestor_id,
                            'descendant_id' => $d->descendant_id,
                            'depth'         => ($a->depth + 1 + $d->depth),
                        ];
                        if (count($batch) >= 1000) {
                            DB::table('user_closure')->insertOrIgnore($batch);
                            $batch = [];
                        }
                    }
                }
                if ($batch) {
                    DB::table('user_closure')->insertOrIgnore($batch);
                }
            }

            $this->logHierarchyChange(
                userId: $userId,
                oldManagerId: $oldManagerId,
                newManagerId: $newManagerId,
                context: 'move_subtree'
            );
        });
    }

    /**
     * Garante que exista a linha reflexiva (id,id,0) para todos os users.
     * Útil em migrações antigas/ambientes híbridos.
     */
    public function ensureSelfRows(): void
    {
        DB::transaction(function () {
            $ids = DB::table('users')->select('id')->pluck('id');
            $rows = [];
            foreach ($ids as $id) {
                $rows[] = ['ancestor_id' => $id, 'descendant_id' => $id, 'depth' => 0];
                if (count($rows) >= 1000) {
                    DB::table('user_closure')->insertOrIgnore($rows);
                    $rows = [];
                }
            }
            if ($rows) {
                DB::table('user_closure')->insertOrIgnore($rows);
            }
        });
    }

    /**
     * Conecta um novo usuário ao manager (sem rebuild total).
     * - Assume que a linha reflexiva do novo usuário já existe (se não, cria).
     * - Propaga ancestrais do manager ao novo usuário.
     */
    public function attachNewUserUnderManager(string $newUserId, ?string $managerId): void
    {
        DB::transaction(function () use ($newUserId, $managerId) {
            // reflexiva
            DB::table('user_closure')->insertOrIgnore([
                ['ancestor_id' => $newUserId, 'descendant_id' => $newUserId, 'depth' => 0]
            ]);

            // seta manager_id
            DB::table('users')->where('id', $newUserId)->update(['manager_id' => $managerId]);

            if ($managerId) {
                // ancestrais do manager (inclui ele com depth=0)
                $ancestors = DB::table('user_closure')
                    ->where('descendant_id', $managerId)
                    ->select('ancestor_id', 'depth')
                    ->get();

                $rows = [];
                foreach ($ancestors as $a) {
                    $rows[] = [
                        'ancestor_id'   => $a->ancestor_id,
                        'descendant_id' => $newUserId,
                        'depth'         => $a->depth + 1,
                    ];
                }
                if ($rows) {
                    DB::table('user_closure')->insertOrIgnore($rows);
                }
            }

            $this->logHierarchyChange(
                userId: $newUserId,
                oldManagerId: null,
                newManagerId: $managerId,
                context: 'attach_new_user'
            );
        });
    }

    private function logHierarchyChange(string $userId, ?string $oldManagerId, ?string $newManagerId, string $context): void
    {
        $action = match (true) {
            $oldManagerId === null && $newManagerId !== null => 'hierarchy_assigned',
            $oldManagerId !== null && $newManagerId === null => 'hierarchy_unassigned',
            default => 'hierarchy_reassigned',
        };

        Audit::create([
            'user_id' => auth()->id(),
            'model_class' => 'App\\Models\\User',
            'action' => $action,
            'before' => json_encode([
                'id' => $userId,
                'manager_id' => $oldManagerId,
                'context' => $context,
            ]),
            'after' => json_encode([
                'id' => $userId,
                'manager_id' => $newManagerId,
                'context' => $context,
            ]),
        ]);
    }
}
