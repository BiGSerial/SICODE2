<?php

namespace Database\Seeders;

use App\Models\CancellationCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CancellationCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Pedido do Cliente',
            'Duplicidade',
            'Erro de cadastro',
            'Mudança de escopo',
            'Falta de documentação',
        ];

        foreach ($categories as $index => $name) {
            $slug = Str::slug($name);
            CancellationCategory::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'active' => true,
                    'require_evidence' => true,
                    'min_evidence_files' => 1,
                    'display_order' => $index + 1,
                ]
            );
        }
    }
}
