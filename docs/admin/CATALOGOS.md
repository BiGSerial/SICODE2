# Cadastro de categorias e motivos (catálogos)

Tela única: **Administração → Cadastros → Catálogos → Categorias e motivos** (`/admin/category`, `Admin\Catalog\Manager`). Atalhos que abrem o mesmo cadastro travado numa atividade (`['only' => chave]`): Análise de projetos (`/project-review/categories`) e Cancelamento (`/cancellations/categories` e `/admin/cancellation_categories`).

## Como funciona

- **Registro** `App\Support\Catalog\CatalogRegistry`: descreve cada catálogo (área, ícone, onde é usado, quem edita, níveis). **Nenhuma migration**: cada entrada aponta para as tabelas que já existem. Catálogo novo = uma entrada no registro, sem tela nova.
- **Motor** `App\Services\Catalog\CatalogService`: lista, cria (uma ou várias), edita, liga/desliga, reordena e exclui. Toda regra mora aqui; a tela só passa catálogo, nível e id.
- **Tela** em colunas (atividade → categoria → subcategoria → item): cadastro rápido com Enter, "Colar várias", filtro, interruptor de ativo, opções em selo clicável, menu "⋮" (editar, subir, descer, excluir) e modais `ops-modal`. Estilo em `resources/sass/ops/_catalog.scss` (só `var(--ops-*)`).

## Regras do motor

- Nome aparado, sem espaços duplos, único dentro do mesmo pai (`uppercase` no nível padroniza em maiúsculas, como Retorno interno sempre fez).
- **Ordem** só onde o consumidor respeita a coluna (`display_order`/`sort_order`): Cancelamento, Qualidade e Encerramento. Reordenar reindexa os irmãos em 1..n. A Análise de projetos é alfabética porque a fila lê por nome; setas ali enganariam.
- **Exclusão**: nunca exclui o que está em uso (`usage` do nível: filhos, pedidos, apontamentos…). Com coluna `active`, a mensagem manda desativar. Retorno interno ainda não tem `active`: só exclui o que está livre.
- Cadastros com `slug` imutável recusam nomes que gerariam o mesmo identificador (mensagem, não erro de banco).
- Qualidade: ao menos um ciclo (Projeto/Orçamento) ligado; `code` único.
- Toda ação revalida o acesso do catálogo (`can` no registro); o estado Livewire vem do navegador.

## Como adicionar um catálogo

1. Entrada em `CatalogRegistry::all()` (níveis, flags, `usage` com relações que existam no model).
2. Se faltar relação de uso no model, crie (`hasMany`) — o teste do registro falha se a relação, tabela ou coluna não existir.
3. Conferir **como o consumidor ordena** antes de ligar `order`.
4. `tests/Feature/CatalogManagerTest.php` já varre todo o registro; acrescente caso específico se houver regra nova.

## Levantamento (o que existe hoje)

**Já no cadastro único:**

| Catálogo | Tabelas | Atividade que usa |
|---|---|---|
| Análise de projetos | `project_review_categories/subcategories/items` | Engenharia: fila, histórico, governança |
| Retorno interno | `categories`, `subcategories` (+ `needFile`) | Órgão externo (retorno e protocolos), Encerramento |
| Cancelamento de pagamento | `cancellation_categories` | Serviços e Despacho de pagamento |
| Rejeição da Qualidade | `quality_rejection_categories` (auto-relação) | Qualidade |
| Pendências do Encerramento | `closure_issue_categories/subcategories` | Encerramento e portal da parceira (antes **sem tela**, só seed) |

**Fora do cadastro único (próximas fases):**

- `ProtestMeasureCodes` (código → classificação CIP/Construção): formato diferente, tela própria em `admin.protests.measure_codes`.
- `entity_types` (tipos de entidade do órgão externo): tabela sem tela de admin.
- Listas fixas em `app/Helpers/SelectOptions.php`, candidatas a virar catálogo: motivos de D5 e códigos D5 (`getD5Reasons`, `getD5codify`), motivos de devolução de obra (`getReasonPublication`, `getReasonSmcPublication`), motivos de protocolo (tem cor e prefixo), categoria de protesto, ações/rejeições do retorno interno (`getReclaimsOptions` duplica `Subcategory.needFile`), conclusões de Desenho/Levantamento/Comissionamento/Supervisão, tipos de arquivo. `getNewRejectOptions` e `getPauseNotesCategory` parecem sem uso.
- Texto livre sem catálogo: motivo de pausa/devolução do Encerramento, áreas e tipos de demanda do Jurídico.
- Enums de fluxo (status, estágio, prioridade) não são catálogo: não devem ser editáveis.

**Pendências conhecidas:** `Category`/`Subcategory` não têm `active` (migration nova + filtro nos 3 consumidores); a tela da Qualidade (`quality.categories`) continua separada e pode ser trocada pelo atalho `only`.
