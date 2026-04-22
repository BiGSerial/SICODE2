# WALL V2 - Documentação Técnica

## Objetivo
Descreve a arquitetura, fontes de dados e mapa de arquivos do WALL V2 após refatoração completa para `app/Services/Wall/`.

---

## Visão Geral da Arquitetura

```
WallDataOrchestrator
        │
        ├── ScreenContextResolver (resolve tipo da tela)
        │
        ├── ProductionScreenDataService  (telas de produção dinâmica)
        │       └── NoteFilters / Repositories / ProductionQueryBuilder
        │
        └── FixedChartScreenDataService  (telas fixas)
                ├── AdsFixedDashboardDataService
                ├── ProjectReviewFixedDashboardDataService
                └── ComplaintsFixedDashboardDataService  [placeholder]
```

### Camadas

| Camada | Classe | Responsabilidade |
|--------|--------|-----------------|
| Orquestrador | `WallDataOrchestrator` | Roteamento de requisições, manifest/payload global |
| Contexto | `ScreenContextResolver` → `ScreenContext` | Normaliza `screen_type` e `fixed_chart` |
| Tela de produção | `ProductionScreenDataService` | Itens dinâmicos com cache por item (45 s) |
| Tela fixa | `FixedChartScreenDataService` | Roteia para o serviço fixo correto |
| ADS | `AdsFixedDashboardDataService` | Payload ADS com cache (120 s) |
| Análise de Projeto | `ProjectReviewFixedDashboardDataService` | Payload Project Review com cache dinâmico |
| Reclamação | `ComplaintsFixedDashboardDataService` | Placeholder (em desenvolvimento) |
| Suporte | `CacheLockTrait` | Cache com lock distribuído (stale-while-revalidate) |

---

## Tipos de Tela

### `production_services`
- Rotaciona serviços (`wall_screen_services`).
- Fonte de dados configurável por serviço via `screen_config.production_sources`.
- Cada item é cacheado **45 segundos** (`wall_v2:prod:s{screen_id}:svc:{uuid}:rb:{0|1}`).

### `fixed_chart`
- Tela única com um item sintético sem rotação de serviço.
- Cada dash fixa tem seu próprio service e cache:
  - `ads_dashboard` → `AdsFixedDashboardDataService` (cache 120 s)
  - `project_review_dashboard` → `ProjectReviewFixedDashboardDataService` (cache `max(120, min(600, refresh*3))` s)
  - `complaints_dashboard` → `ComplaintsFixedDashboardDataService` (placeholder sem cache)

### `ads_chart` (legado)
- Alias para `fixed_chart` com `fixed_chart=ads_dashboard`. Mantido por compatibilidade.

---

## Fontes de Dados por Serviço (produção)

| Chave | Classe | Notas |
|-------|--------|-------|
| `rule_builder` | `ProductionQueryBuilder` | Padrão; usa regras dinâmicas do banco |
| `publication_note_filter` | `Publication\NoteFilter` | Stateless (`filter_group` + `btzeroform`) |
| `payment_note_filter` | `Payment\NoteFilter` | Lê `$_SESSION` (ver limitação abaixo) |
| `publish_repository` | `PublishRepository::getBaseQuery()` | |
| `supervision_repository` | `SupervisionRepository::getBaseQuery()` | |
| `survey_repository` | `SurveyRepository::getBaseQuery()` | |

> **Limitação:** `payment_note_filter` usa `$_SESSION` para filtros de rubrica/cidade.
> Em requests de API sem sessão ativa, os filtros não se aplicam (comportamento seguro mas incompleto).

---

## `query_filters` por Serviço

Cada filtro aceita:

| Campo | Valores |
|-------|---------|
| `mode` | `include` \| `exclude` |
| `scope` | `base` \| `relation` |
| `relation` | Nome da relação Eloquent (obrigatório se `scope=relation`) |
| `column` | Coluna alvo — deve estar na whitelist de `ProductionScreenDataService::ALLOWED_FILTER_COLUMNS` |
| `operator` | `equals` \| `starts_with` \| `contains` \| `ends_with` |
| `value` | Valor de comparação |

**Segurança:** nomes de coluna são validados contra whitelist antes de serem usados na query. Colunas inválidas são silenciosamente ignoradas.

Exemplo de `screen_config`:

```json
{
  "production_source": "rule_builder",
  "production_sources": {
    "UUID_SERVICO_PUBLICACAO": {
      "source": "publication_note_filter",
      "filter_group": "publication",
      "btzeroform": true
    },
    "UUID_SERVICO_SUPERVISAO": {
      "source": "supervision_repository",
      "query_filters": [
        { "mode": "exclude", "scope": "base", "column": "rubrica", "operator": "equals", "value": "Acompanhamento" },
        { "mode": "exclude", "scope": "base", "column": "note",    "operator": "starts_with", "value": "RDA" }
      ]
    }
  }
}
```

---

## Cache (CacheLockTrait)

Todos os serviços que usam `CacheLockTrait` implementam stale-while-revalidate:

1. Verifica cache primário (TTL curto) → retorna se presente.
2. Verifica cache stale (30 min) → retorna enquanto recalcula.
3. Usa lock distribuído (Redis/etc.) para evitar dog-pile.
4. Fallback sem lock para drivers simples (ArrayStore, FileStore).

Chaves de cache por tipo:

| Tipo | Chave |
|------|-------|
| Item de produção | `wall_v2:prod:s{screen}:svc:{uuid}:rb:{0\|1}` |
| ADS dashboard | `wall_v2:fixed:ads:screen:{screen}` |
| Project Review | `wall_v2:fixed:project_review:screen:{screen}` |

---

## Fluxo de Execução

### 1) Configuração
- Admin acessa `/config/wall`.
- `WallController` (injeta `WallDataOrchestrator`) expõe `rotationSeconds`, `refreshSeconds` e schema de filtros.
- CRUD persiste em `wall_screens.screen_config`.

### 2) Consumo (API)

| Endpoint | Controller | Serviço |
|----------|------------|---------|
| `GET /api/v1/reports/walls/{wall}/production-v2` | `ProductionWallV2DataController` | `WallDataOrchestrator` |
| `GET /api/v1/reports/walls/{wall}/production-v2/{screen}` | `ProductionWallV2ScreenDataController` | `WallDataOrchestrator` |
| `GET /api/v1/reports/walls/{wall}/production-v2/{screen}/items/{serviceId}/charts` | `ProductionWallV2ItemChartsController` | `WallDataOrchestrator` |
| `GET /api/v1/reports/walls/{wall}/production-v2/{screen}/fixed/project-review` | `ProductionWallV2FixedProjectReviewController` | `ProjectReviewFixedDashboardDataService` (direto) |

### 3) Render
- `resources/views/reports/production-wall-v2.blade.php`
- Sincroniza manifest + refresh por componente.
- Rotaciona tela e serviço por `duration_seconds` / `service_rotation_seconds`.

---

## Mapa de Arquivos

### `app/Services/Wall/`

| Arquivo | Responsabilidade |
|---------|-----------------|
| `WallDataOrchestrator.php` | Orquestra screens, manifest, item-charts; constantes de settings |
| `Contracts/WallScreenDataService.php` | Interface dos serviços de tela |
| `Context/ScreenContext.php` | DTO de contexto normalizado |
| `Context/ScreenContextResolver.php` | Resolve `screen_type` → `ScreenContext` |
| `Support/CacheLockTrait.php` | Cache com lock distribuído (stale-while-revalidate) |
| `Screen/ProductionScreenDataService.php` | Telas de produção: payload, manifest, item com cache |
| `Screen/FixedChartScreenDataService.php` | Roteia para serviço fixo correto; placeholder |
| `Fixed/AdsFixedDashboardDataService.php` | Dashboard ADS com cache 120 s |
| `Fixed/ProjectReviewFixedDashboardDataService.php` | Dashboard Análise de Projeto com cache dinâmico |
| `Fixed/ComplaintsFixedDashboardDataService.php` | Dashboard Reclamação (placeholder) |

### Fontes de query (inalteradas)
- `app/Custom/ProductionQueryBuilder.php` — builder dinâmico de regras
- `app/Custom/RuleBuilder.php` — builder legado
- `app/Services/Publication/NoteFilter.php`
- `app/Services/Payment/NoteFilter.php`
- `app/Repositories/PublishRepository.php`
- `app/Repositories/SupervisionRepository.php`
- `app/Repositories/SurveyRepository.php`

### Configuração e UI
- `app/Http/Controllers/Config/WallController.php` — CRUD; injeta `WallDataOrchestrator`
- `resources/views/config/wall/index.blade.php`
- `resources/views/reports/production-wall-v2.blade.php`

### API / Rotas
- `routes/api.php` — rotas `/api/v1/reports/walls/...`
- `routes/web.php` — rotas `/config/wall`, `/reports/wall/...`

### Modelos
- `app/Models/Wall.php`, `WallScreen.php`, `WallScreenService.php`

---

## Operação e Troubleshooting

| Problema | Onde verificar |
|----------|---------------|
| Contagem congelada | `production-wall-v2.blade.php` — loop de 1 s não bloqueia por rede |
| Erro de `production_sources` | `WallController::parseProductionSourcesConfig()` |
| Lentidão em item de produção | Cache 45 s por item; checar `wall_v2:prod:*` no Redis |
| Lentidão em Project Review | Cache dinâmico; log `wall project-review payload slow` |
| ADS sem dados | Cache 120 s; checar `wall_v2:fixed:ads:*` |
| Coluna ignorada em `query_filters` | Verificar `ProductionScreenDataService::ALLOWED_FILTER_COLUMNS` |

---

## Padrão Obrigatório - Atualização de Gráficos (Chart.js)

Para qualquer gráfico novo ou alteração de gráfico existente no WALL V2:

1. Atualizar datasets in-place, sem substituir o array `chart.data.datasets`.
2. Preservar estado interno do Chart.js (`_metasets`) para animação correta `x -> y`.
3. Em "sem dados", exibir overlay/empty-state sem limpar os datasets atuais.
4. Não usar `updateChartData(..., [], [], ...)` para ocultar dados.
5. Reaplicar esse padrão também nos dashboards fixos (`ads_dashboard`, `project_review_dashboard`, `complaints_dashboard`).

Motivo: substituir o array de datasets ou limpar explicitamente datasets faz o Chart.js perder referência do estado anterior e tende a animar `valor_antigo -> 0 -> valor_novo`.

---

## Próximas Evoluções Sugeridas

1. Tornar `payment_note_filter` stateless (passar filtros via `sourceConfig` em vez de `$_SESSION`).
2. Implementar `ComplaintsFixedDashboardDataService` com dados reais.
3. Expandir `ALLOWED_FILTER_COLUMNS` conforme necessário (com revisão de segurança).
4. Cobertura de testes de contrato para payload/manifest/item-charts.
5. Persistir `production_sources` em tabela própria (evitar JSON por tela).
