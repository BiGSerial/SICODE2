<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo inicial de motivos de rejeição (categoria → subcategoria) para mapear por que a Qualidade devolve.
 * Só popula quando o catálogo está vazio; depois tudo é mantido pela Gestão em Qualidade > Motivos de rejeição.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (DB::table('quality_rejection_categories')->exists()) {
            return;
        }

        // [categoria, 1ª passada, 2ª passada, [subcategorias]]
        $catalog = [
            ['Croqui', true, false, ['Medida incorreta', 'Simbologia incorreta', 'Traçado divergente do campo', 'Croqui ilegível']],
            ['Documentação', true, true, ['Arquivo ausente', 'Arquivo incorreto ou de outra Nota', 'Informação incompleta']],
            ['Desenho', false, true, ['Não corresponde ao croqui aprovado', 'Erro de projeto', 'Padrão de apresentação']],
            ['Orçamento', false, true, ['Quantidade incorreta', 'Material incorreto', 'Valor inconsistente', 'Ordem incorreta']],
        ];

        $now = now();

        foreach ($catalog as $order => [$name, $project, $budget, $children]) {
            $parentId = DB::table('quality_rejection_categories')->insertGetId([
                'name'       => $name, 'active' => true, 'applies_project' => $project, 'applies_budget' => $budget,
                'sort_order' => ($order + 1) * 10, 'created_at' => $now, 'updated_at' => $now,
            ]);

            foreach ($children as $childOrder => $child) {
                DB::table('quality_rejection_categories')->insert([
                    'parent_id'  => $parentId, 'name' => $child, 'active' => true, 'applies_project' => $project, 'applies_budget' => $budget,
                    'sort_order' => ($childOrder + 1) * 10, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Catálogo é dado operacional: não é removido no rollback.
    }
};
