<?php

use App\Http\Livewire\Admin\Catalog\Manager;
use App\Models\{CancellationCategory, Category, ProjectReviewCategory, ProjectReviewSubcategory, User};
use App\Models\Closure\ClosureIssueCategory;
use App\Services\Catalog\CatalogService;
use App\Support\Catalog\{CatalogException, CatalogRegistry};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Schema};
use Livewire\Livewire;

// Cadastro de categorias e motivos: docs/admin/CATALOGOS.md

uses(RefreshDatabase::class);

// As migrations semeiam catálogos (Geral, taxonomia da Qualidade e do Encerramento): cada teste começa com as tabelas vazias.
beforeEach(function () {
    Schema::disableForeignKeyConstraints();

    foreach (['project_review_items', 'project_review_subcategories', 'project_review_categories', 'subcategories', 'categories', 'cancellation_categories',
        'quality_rejection_categories', 'closure_issue_subcategories', 'closure_issue_categories'] as $table) {
        DB::table($table)->truncate();
    }

    Schema::enableForeignKeyConstraints();
});

function catalogs(): CatalogService
{
    return app(CatalogService::class);
}

it('todo catálogo do registro aponta para tabela, colunas e relações de uso que existem', function () {
    foreach (CatalogRegistry::all() as $key => $catalog) {
        expect(CatalogRegistry::GROUPS)->toHaveKey($catalog['group']);

        foreach ($catalog['levels'] as $i => $level) {
            $model = new $level['model']();
            $table = $model->getTable();

            foreach (array_filter([$level['order'] ?? null, !empty($level['active']) ? 'active' : null, !empty($level['code']) ? 'code' : null, !empty($level['description']) ? 'description' : null, !empty($level['slug']) ? 'slug' : null]) as $column) {
                expect(Schema::hasColumn($table, $column))->toBeTrue("{$key} nível {$i}: coluna {$column} não existe em {$table}");
            }

            foreach (array_keys($level['flags'] ?? []) + array_keys($level['numbers'] ?? []) as $column) {
                expect(Schema::hasColumn($table, $column) || isset($level['flags'][$column]) || isset($level['numbers'][$column]))->toBeTrue();
            }

            foreach (array_keys($level['flags'] ?? []) as $flag) {
                expect(Schema::hasColumn($table, $flag))->toBeTrue("{$key} nível {$i}: coluna {$flag} não existe em {$table}");
            }

            foreach (array_keys($level['usage'] ?? []) as $relation) {
                expect(method_exists($model, $relation))->toBeTrue("{$key} nível {$i}: relação {$relation} não existe em {$level['model']}");
            }

            if (!empty($level['parent']) && $level['parent'] !== 'self') {
                expect(Schema::hasColumn($table, $level['parent']))->toBeTrue();
            }
        }
    }
});

it('cria normalizando o nome, na ordem de cadastro, e recusa repetido no mesmo pai', function () {
    $first  = catalogs()->create('retorno-interno', 0, null, ['name' => '  entidade   interna ']);
    $second = catalogs()->create('cancelamento', 0, null, ['name' => 'Duplicidade']);
    $third  = catalogs()->create('cancelamento', 0, null, ['name' => 'Valor incorreto']);

    expect($first->name)->toBe('ENTIDADE INTERNA')
        ->and($third->display_order)->toBe($second->display_order + 1)
        ->and($second->require_evidence)->toBeTrue()
        ->and($second->min_evidence_files)->toBe(1);

    expect(fn () => catalogs()->create('retorno-interno', 0, null, ['name' => 'entidade interna']))->toThrow(CatalogException::class, 'Já existe');

    // Mesmo nome em outro pai é permitido.
    $a = catalogs()->create('analise-projetos', 0, null, ['name' => 'A']);
    $b = catalogs()->create('analise-projetos', 0, null, ['name' => 'B']);
    catalogs()->create('analise-projetos', 1, $a->id, ['name' => 'Anexo']);

    expect(catalogs()->create('analise-projetos', 1, $b->id, ['name' => 'Anexo'])->exists)->toBeTrue();
});

it('exige o item de cima para criar um nível interno', function () {
    expect(fn () => catalogs()->create('analise-projetos', 1, null, ['name' => 'Solto']))->toThrow(CatalogException::class);
});

it('adiciona várias de uma vez ignorando vazias e repetidas', function () {
    catalogs()->create('retorno-interno', 0, null, ['name' => 'Projeto']);

    $result = catalogs()->createMany('retorno-interno', 0, null, "Projeto\n\n Orçamento \nORÇAMENTO\nEntidade");

    expect($result)->toBe(['created' => 2, 'skipped' => 1])
        ->and(Category::query()->count())->toBe(3);
});

it('reordena trocando com o vizinho e reindexando os irmãos', function () {
    $a = catalogs()->create('cancelamento', 0, null, ['name' => 'A']);
    $b = catalogs()->create('cancelamento', 0, null, ['name' => 'B']);
    $c = catalogs()->create('cancelamento', 0, null, ['name' => 'C']);
    CancellationCategory::query()->update(['display_order' => 0]); // ordens repetidas (legado): o motor não pode depender delas

    catalogs()->move('cancelamento', 0, $c->id, 'up');

    expect(catalogs()->rows('cancelamento', 0, null)->pluck('name')->all())->toBe(['A', 'C', 'B']);

    catalogs()->move('cancelamento', 0, $a->id, 'up'); // já é o primeiro: não faz nada

    expect(catalogs()->rows('cancelamento', 0, null)->pluck('name')->all())->toBe(['A', 'C', 'B']);
});

it('não exclui o que está em uso e sugere desativar; exclui o que está livre', function () {
    $category = catalogs()->create('analise-projetos', 0, null, ['name' => 'Com filho']);
    catalogs()->create('analise-projetos', 1, $category->id, ['name' => 'Filho']);

    expect(fn () => catalogs()->delete('analise-projetos', 0, $category->id))->toThrow(CatalogException::class, 'Desative');

    $free = catalogs()->create('analise-projetos', 0, null, ['name' => 'Livre']);
    catalogs()->delete('analise-projetos', 0, $free->id);

    expect(ProjectReviewCategory::query()->whereKey($free->id)->exists())->toBeFalse()
        ->and(ProjectReviewSubcategory::query()->count())->toBe(1);
});

it('Qualidade: ao menos um ciclo ligado, código único e subcategoria não vira motivo', function () {
    $motive = catalogs()->create('qualidade-rejeicao', 0, null, ['name' => 'Documentação', 'code' => 'DOC']);
    $sub    = catalogs()->create('qualidade-rejeicao', 1, $motive->id, ['name' => 'Ilegível', 'applies_budget' => false]);

    expect($sub->parent_id)->toBe($motive->id)
        ->and($sub->applies_project)->toBeTrue()
        ->and($sub->applies_budget)->toBeFalse();

    expect(fn () => catalogs()->toggleFlag('qualidade-rejeicao', 1, $sub->id, 'applies_project'))->toThrow(CatalogException::class, 'ao menos uma');
    expect(fn () => catalogs()->create('qualidade-rejeicao', 0, null, ['name' => 'Outro', 'code' => 'DOC']))->toThrow(CatalogException::class, 'já está em uso');

    // O id de uma subcategoria não é aceito como motivo (e vice-versa).
    expect(fn () => catalogs()->update('qualidade-rejeicao', 0, $sub->id, ['name' => 'X']))->toThrow(CatalogException::class, 'não encontrado');
    expect(fn () => catalogs()->update('qualidade-rejeicao', 1, $motive->id, ['name' => 'X']))->toThrow(CatalogException::class, 'não encontrado');

    expect(catalogs()->rows('qualidade-rejeicao', 0, null)->pluck('id')->all())->toBe([$motive->id])
        ->and(catalogs()->rows('qualidade-rejeicao', 1, $motive->id)->pluck('id')->all())->toBe([$sub->id]);
});

it('nomes que viram o mesmo identificador (slug) são recusados com mensagem, não com erro de banco', function () {
    catalogs()->create('encerramento-pendencias', 0, null, ['name' => 'Material faltante']);

    expect(fn () => catalogs()->create('encerramento-pendencias', 0, null, ['name' => 'Material  Faltante!']))->toThrow(CatalogException::class);
    expect(ClosureIssueCategory::query()->count())->toBe(1);
});

it('a tela abre no primeiro catálogo permitido, cadastra, e recusa quem não pode editar', function () {
    $admin = User::factory()->create(['superadm' => true, 'admin' => true]);

    Livewire::actingAs($admin)->test(Manager::class)
        ->assertSet('catalog', 'analise-projetos')
        ->call('pickCatalog', 'retorno-interno')
        ->assertSet('catalog', 'retorno-interno')
        ->set('add.0', 'Orçamento')
        ->call('addItem', 0)
        ->assertSet('add.0', '')
        ->assertSee('ORÇAMENTO')
        ->set('add.1', 'Sem categoria escolhida?')
        ->call('addItem', 1);

    expect(Category::query()->pluck('name')->all())->toBe(['ORÇAMENTO']);

    $plain = User::factory()->create(['superadm' => false, 'admin' => false, 'management' => false, 'operator' => false, 'can_dispatch' => false, 'analyst' => false]);

    Livewire::actingAs($plain)->test(Manager::class)->assertForbidden();
});

it('a tela trava no catálogo do atalho e não deixa trocar de catálogo', function () {
    $admin = User::factory()->create(['superadm' => true, 'admin' => true]);

    Livewire::actingAs($admin)->test(Manager::class, ['only' => 'cancelamento'])
        ->assertSet('catalog', 'cancelamento')
        ->call('pickCatalog', 'retorno-interno')
        ->assertSet('catalog', 'cancelamento');
});

it('a view do cadastro segue o padrão: sem CSS/JS/style inline', function () {
    $view = file_get_contents(resource_path('views/livewire/admin/catalog/manager.blade.php'));

    expect($view)->not->toContain('<style')->not->toContain('<script')->not->toContain('style=')->not->toMatch('/text-bg-|table-dark/');
});
