<?php

namespace App\Support\Catalog;

use App\Models\{CancellationCategory, Category, ProjectReviewCategory, ProjectReviewItem, ProjectReviewSubcategory, QualityRejectionCategory, Subcategory, User};
use App\Models\Closure\{ClosureIssueCategory, ClosureIssueSubcategory};
use Illuminate\Support\Facades\Gate;

/**
 * Registro único dos catálogos "categoria → subcategoria → item" cadastráveis pelo admin (docs/admin/CATALOGOS.md).
 *
 * Cada catálogo descreve as tabelas que JÁ existem (nenhuma migration): o motor `CatalogService` e a tela
 * `Admin\Catalog\Manager` leem só daqui. Catálogo novo = uma entrada aqui, sem tela nova.
 *
 * Catálogo: `label`, `group` (área/atividade que usa), `icon`, `hint`, `consumers` (telas que leem o cadastro), `can` (quem edita),
 *   `levels` (1 a 3 níveis) e `settings` (configuração solta da área, opcional).
 * Nível: `label`/`plural`, `model`, `parent` (coluna FK do pai; no 1º nível, `self` = auto-relação), `order` (coluna de ordem ou null),
 *   `active` (tem coluna `active`), `description`, `code`, `uppercase`, `flags` (booleans), `numbers`, `usage` (relações que
 *   bloqueiam a exclusão: `relação => rótulo no plural`), `atLeastOne` (flags em que ao menos uma precisa estar ligada).
 */
final class CatalogRegistry
{
    /** Áreas na ordem do fluxo (mesma ideia do menu superior). */
    public const GROUPS = [
        'engenharia'   => ['label' => 'Engenharia', 'icon' => 'ri-compasses-2-line'],
        'responsavel'  => ['label' => 'Responsável e retorno interno', 'icon' => 'ri-reply-line'],
        'pagamento'    => ['label' => 'Pagamento e despacho', 'icon' => 'ri-bank-card-line'],
        'qualidade'    => ['label' => 'Qualidade', 'icon' => 'ri-shield-check-line'],
        'encerramento' => ['label' => 'Encerramento', 'icon' => 'ri-lock-2-line'],
    ];

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return [
            'analise-projetos' => [
                'label'     => 'Análise de projetos',
                'group'     => 'engenharia',
                'icon'      => 'ri-draft-line',
                'hint'      => 'Categoria, subcategoria e item usados nos apontamentos da análise (a fila lista em ordem alfabética).',
                'consumers' => ['Análise de projetos (fila e histórico)', 'Painel de governança', 'Exportação de governança'],
                'can'       => fn (User $user) => Gate::forUser($user)->allows('admin') || Gate::forUser($user)->allows('analyst'),
                'settings'  => 'survey_service',
                'levels'    => [
                    [
                        'label'  => 'Categoria', 'plural' => 'Categorias', 'model' => ProjectReviewCategory::class,
                        'active' => true,
                        'usage'  => ['Subcategories' => 'subcategorias'],
                    ],
                    [
                        'label'  => 'Subcategoria', 'plural' => 'Subcategorias', 'model' => ProjectReviewSubcategory::class, 'parent' => 'category_id',
                        'active' => true,
                        'usage'  => ['Items' => 'itens', 'Findings' => 'apontamentos'],
                    ],
                    [
                        'label'  => 'Item', 'plural' => 'Itens', 'model' => ProjectReviewItem::class, 'parent' => 'subcategory_id',
                        'active' => true,
                        'usage'  => ['Findings' => 'apontamentos'],
                    ],
                ],
            ],

            'retorno-interno' => [
                'label'     => 'Retorno interno',
                'group'     => 'responsavel',
                'icon'      => 'ri-arrow-go-back-line',
                'hint'      => 'Categoria e subcategoria escolhidas ao devolver uma nota (Retorno interno e protocolos).',
                'consumers' => ['Órgão externo: retorno interno e protocolos', 'Encerramento: retorno interno'],
                'can'       => fn (User $user) => Gate::forUser($user)->allows('admin'),
                'levels'    => [
                    [
                        'label'     => 'Categoria', 'plural' => 'Categorias', 'model' => Category::class,
                        'uppercase' => true, 'description' => true,
                        'usage'     => ['Subcategories' => 'subcategorias'],
                    ],
                    [
                        'label'     => 'Subcategoria', 'plural' => 'Subcategorias', 'model' => Subcategory::class, 'parent' => 'category_id',
                        'uppercase' => true, 'description' => true,
                        'flags'     => ['needFile' => ['label' => 'Exige arquivo', 'hint' => 'Quem usar esta subcategoria precisa anexar um arquivo.', 'icon' => 'ri-attachment-2', 'default' => false]],
                        'usage'     => ['Reclaims' => 'retornos'],
                    ],
                ],
            ],

            'cancelamento' => [
                'label'     => 'Cancelamento de pagamento',
                'group'     => 'pagamento',
                'icon'      => 'ri-close-circle-line',
                'hint'      => 'Motivos do pedido de cancelamento e a regra de evidência de cada um.',
                'consumers' => ['Serviços: pedido de cancelamento', 'Despacho: fila e análise de cancelamentos', 'Relatórios de cancelamento'],
                'can'       => fn (User $user) => $user->can('manage', \App\Models\CancellationCategory::class),
                'levels'    => [
                    [
                        'label'   => 'Motivo', 'plural' => 'Motivos', 'model' => CancellationCategory::class,
                        'order'   => 'display_order', 'active' => true, 'description' => true, 'slug' => true,
                        'flags'   => ['require_evidence' => ['label' => 'Exige evidência', 'hint' => 'O pedido só segue com arquivo(s) de evidência.', 'icon' => 'ri-attachment-2', 'default' => true]],
                        'numbers' => ['min_evidence_files' => ['label' => 'Mínimo de evidências', 'min' => 0, 'default' => 1, 'when' => 'require_evidence']],
                        'usage'   => ['requests' => 'pedidos'],
                    ],
                ],
            ],

            'qualidade-rejeicao' => [
                'label'     => 'Rejeição da Qualidade',
                'group'     => 'qualidade',
                'icon'      => 'ri-error-warning-line',
                'hint'      => 'Motivos de rejeição, por ciclo de aplicação (projeto e/ou orçamento).',
                'consumers' => ['Qualidade: rejeição de processos'],
                'can'       => fn (User $user) => Gate::forUser($user)->allows('quality.manage') || Gate::forUser($user)->allows('admin'),
                'levels'    => [
                    [
                        'label'      => 'Motivo', 'plural' => 'Motivos', 'model' => QualityRejectionCategory::class, 'parent' => 'self',
                        'order'      => 'sort_order', 'active' => true, 'description' => true, 'code' => true,
                        'flags'      => self::qualityFlags(),
                        'atLeastOne' => ['applies_project', 'applies_budget'],
                        'usage'      => ['Children' => 'subcategorias', 'Items' => 'rejeições'],
                    ],
                    [
                        'label'      => 'Subcategoria', 'plural' => 'Subcategorias', 'model' => QualityRejectionCategory::class, 'parent' => 'parent_id',
                        'order'      => 'sort_order', 'active' => true, 'description' => true, 'code' => true,
                        'flags'      => self::qualityFlags(),
                        'atLeastOne' => ['applies_project', 'applies_budget'],
                        'usage'      => ['SubItems' => 'rejeições'],
                    ],
                ],
            ],

            'encerramento-pendencias' => [
                'label'     => 'Pendências do Encerramento',
                'group'     => 'encerramento',
                'icon'      => 'ri-alert-line',
                'hint'      => 'Categoria e subcategoria das pendências abertas entre áreas durante o encerramento.',
                'consumers' => ['Encerramento: obras e pendências', 'Portal da parceira: pendências do encerramento'],
                'can'       => fn (User $user) => Gate::forUser($user)->allows('closure.manage') || Gate::forUser($user)->allows('admin'),
                'levels'    => [
                    [
                        'label' => 'Categoria', 'plural' => 'Categorias', 'model' => ClosureIssueCategory::class,
                        'order' => 'display_order', 'active' => true, 'description' => true, 'slug' => true,
                        'usage' => ['Subcategories' => 'subcategorias', 'Issues' => 'pendências'],
                    ],
                    [
                        'label' => 'Subcategoria', 'plural' => 'Subcategorias', 'model' => ClosureIssueSubcategory::class, 'parent' => 'closure_issue_category_id',
                        'order' => 'display_order', 'active' => true, 'description' => true, 'slug' => true,
                        'flags' => ['requires_description' => ['label' => 'Exige descrição', 'hint' => 'Quem abrir a pendência precisa descrever o problema.', 'icon' => 'ri-text', 'default' => false]],
                        'usage' => ['Issues' => 'pendências'],
                    ],
                ],
            ],
        ];
    }

    /** Catálogos que o usuário pode editar (as chaves seguem a ordem do registro). */
    public static function allowedFor(User $user): array
    {
        return array_filter(self::all(), fn (array $catalog) => (bool) $catalog['can']($user));
    }

    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    private static function qualityFlags(): array
    {
        return [
            'applies_project' => ['label' => 'Projeto', 'hint' => 'Vale para o ciclo de projeto.', 'icon' => 'ri-draft-line', 'default' => true],
            'applies_budget'  => ['label' => 'Orçamento', 'hint' => 'Vale para o ciclo de orçamento.', 'icon' => 'ri-money-dollar-circle-line', 'default' => true],
        ];
    }
}
