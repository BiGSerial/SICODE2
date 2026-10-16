<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class () extends Migration {
    /**
     * Taxonomia inicial de pendências de encerramento de obra.
     * Idempotente: só insere o que ainda não existe (por slug) e nunca altera
     * categorias/subcategorias já existentes, que podem ter sido editadas.
     *
     * @var array<string, array<int, array{0: string, 1: bool}>>
     */
    private array $taxonomy = [
        'Material / MIGO pendente' => [
            ['MIGO não realizada', false],
            ['Material não baixado no SAP', false],
            ['Divergência de quantidade de material', true],
            ['Sucata / material retornado pendente', false],
        ],
        'Documentação / As-built' => [
            ['As-built não enviado', false],
            ['As-built com divergência', true],
            ['Documento ilegível ou incompleto', true],
            ['Termo de aceite / comprovante pendente', false],
        ],
        'Medição' => [
            ['Medição não lançada', false],
            ['Medição divergente da execução', true],
            ['Medição aguardando aprovação', false],
        ],
        'Depósito' => [
            ['Depósito bloqueado', true],
            ['Liberação de depósito pendente', false],
        ],
        'Apontamento de horas / operações' => [
            ['Horas não apontadas', false],
            ['Operação sem confirmação', false],
            ['Apontamento divergente', true],
        ],
        'Dados da obra' => [
            ['Dados cadastrais incorretos', true],
            ['Município / localização divergente', true],
            ['Quantitativo de postes divergente', true],
        ],
        'Outros' => [
            ['Outro motivo', true],
        ],
    ];

    public function up(): void
    {
        $categoryOrder = 0;

        foreach ($this->taxonomy as $categoryName => $subcategories) {
            $categoryOrder += 10;
            $slug       = Str::slug($categoryName);
            $categoryId = DB::table('closure_issue_categories')->where('slug', $slug)->value('id');

            if (!$categoryId) {
                $categoryId = DB::table('closure_issue_categories')->insertGetId([
                    'name'          => $categoryName,
                    'slug'          => $slug,
                    'active'        => true,
                    'display_order' => $categoryOrder,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            $order = 0;

            foreach ($subcategories as [$name, $requiresDescription]) {
                $order += 10;
                $subSlug = Str::slug($name);

                $exists = DB::table('closure_issue_subcategories')
                    ->where('closure_issue_category_id', $categoryId)
                    ->where('slug', $subSlug)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('closure_issue_subcategories')->insert([
                    'closure_issue_category_id' => $categoryId,
                    'name'                      => $name,
                    'slug'                      => $subSlug,
                    'active'                    => true,
                    'requires_description'      => $requiresDescription,
                    'display_order'             => $order,
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Taxonomia é dado de configuração e pode já estar referenciada por pendências; não é revertida.
    }
};
