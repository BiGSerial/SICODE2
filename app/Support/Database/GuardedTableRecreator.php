<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\{DB, Schema};
use RuntimeException;

/**
 * Criação defensiva de tabelas NOVAS do módulo Encerramento (banco compartilhado/produção, módulo ainda sem uso).
 *
 *  - tabela não existe            => precisa ser criada;
 *  - existe no formato esperado   => não faz nada;
 *  - existe em formato divergente => é descartada e recriada, MAS só se for do módulo (prefixo `closure_`) e estiver
 *    na lista fixa informada pela migration, e só se NENHUMA tabela de fora do módulo tiver FK para ela.
 *
 * Tabelas de outras atividades nunca são tocadas. Regras: docs/encerramento/CAIXAS_PENDENCIAS_E_RI_CHECKLIST.md §2.1.
 */
class GuardedTableRecreator
{
    public const MODULE_PREFIX = 'closure_';

    /**
     * @param array<int, string> $columns       colunas que a tabela precisa ter
     * @param array<int, string> $indexes       índices (nomes) que a tabela precisa ter
     * @param array<int, string> $ownedTables   lista fixa de tabelas que a migration pode descartar
     *
     * @return bool true quando a tabela precisa ser (re)criada pela migration
     */
    public static function prepare(string $table, array $columns, array $indexes, array $ownedTables): bool
    {
        if (!Schema::hasTable($table)) {
            return true;
        }

        if (self::shapeMatches($table, $columns, $indexes)) {
            return false;
        }

        self::assertOwned($table, $ownedTables);
        self::assertNoExternalForeignKeys($table);

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists($table);
        Schema::enableForeignKeyConstraints();

        return true;
    }

    /** @param array<int, string> $columns @param array<int, string> $indexes */
    public static function shapeMatches(string $table, array $columns, array $indexes = []): bool
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        foreach ($indexes as $index) {
            if (!self::hasIndex($table, $index)) {
                return false;
            }
        }

        return true;
    }

    public static function hasIndex(string $table, string $index): bool
    {
        return DB::select('SHOW INDEX FROM `' . str_replace('`', '', $table) . '` WHERE Key_name = ?', [$index]) !== [];
    }

    /** @param array<int, string> $ownedTables */
    public static function assertOwned(string $table, array $ownedTables): void
    {
        if (!str_starts_with($table, self::MODULE_PREFIX) || !in_array($table, $ownedTables, true)) {
            throw new RuntimeException("A tabela `{$table}` não pertence ao módulo Encerramento: recriação recusada.");
        }
    }

    public static function assertNoExternalForeignKeys(string $table): void
    {
        $referencing = DB::select(
            'SELECT DISTINCT TABLE_NAME AS table_name FROM information_schema.KEY_COLUMN_USAGE
             WHERE REFERENCED_TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = ? AND TABLE_NAME NOT LIKE ?',
            [$table, 'closure\\_%']
        );

        if ($referencing !== []) {
            $names = implode(', ', array_map(fn ($row) => $row->table_name, $referencing));

            throw new RuntimeException("A tabela `{$table}` é referenciada por tabelas de fora do módulo ({$names}): recriação abortada, nada foi descartado.");
        }
    }
}
