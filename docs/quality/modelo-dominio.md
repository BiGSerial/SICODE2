# Modelo de domínio

## Tabelas

| Tabela | Papel |
|--------|-------|
| `quality_processes` | 1 por Production (`unique`). `company_id` = empresa do N1. `state`, `phase`, `round_number`, `n1_user_id`, `current_designer_id`, `original_designer_id` (dono da Production no despacho), `dispatched_by/at`, `completed_by/at` |
| `quality_stages` | Histórico de etapas/rodadas. `type` (ciclo), `level` (DRAWING/N1/N2), `kind` (DISPATCH/RETURN/EXECUTION/REVIEW), `round_number`, `assigned_user_id` (quem recebeu), `dispatched_by/at`, `executed_by`, `started_at`, `completed_at`, `approved_by/at`, `observation`, `submission_data` (ordens). Único: `(process, type, level, kind, round)` |
| `quality_stage_files` | Arquivos por etapa (`file_id`, checksum, quem/quando) |
| `quality_rejections` / `quality_rejection_items` | Rejeição e seus N motivos |
| `quality_rejection_categories` | Catálogo (categoria → subcategoria via `parent_id`), `applies_project`, `applies_budget`, `sort_order`, `active` |
| `quality_events` | Histórico completo: `type`, `actor_id`, `actor_role`, `company_id`, `designer_id`, `target_user_id`, `stage_type/level`, `round_number`, `from_state`, `to_state`, `observation`, `payload` |
| `quality_sap_requests` | Legado: tentativas de SAP de processos antigos (ver `encerramento.md`) |
| `quality_members` | N1/N2 por empresa (`user_id`, `company_id`, `role`, `active`) |
| `quality_pool_rules` | Critérios do Pool (formato de `auxiliar_services`, aplicados por `RuleBuilder` em `notes`) |
| `quality_settings` | `activities` (1º e 2º ciclo → Service) |

Migrations: `2026_09_29_120000_create_quality_domain_tables` (base) e `2026_09_30_100000_rework_quality_flow_n2_n1_designer` (modelo N2/N1/desenhista; a base não foi editada). A segunda faz *backfill* dos processos criados pelo fluxo antigo.

## Production

Uma `Production` **por ciclo**: a do 1º ciclo nasce no despacho do N1 (na atividade configurada para o 1º ciclo); a do 2º ciclo é aberta automaticamente quando o N2 aprova o 1º, para o mesmo usuário/empresa. Rodadas do ciclo reaproveitam a Production; só o N1 troca `productions.user_id/company_id/dispatch_by/dispatch_at` (valor anterior em `payload.previous_production_user_id`). Quem recebeu/executou cada rodada fica em `quality_stages` (`production_id`, `assigned_user_id`, `executed_by`). Ao aprovar o ciclo (N2), a Production é encerrada: `status = 5`, `completed = true`, `completed_at` = data do último envio ao N1, `status_note` e fechamento de `Reclaim` abertos.

## Enums (todos com `label()` em PT-BR)

`QualityProcessStatus`, `QualityProcessState` (+`holder()`, `badge()`), `QualityStageType` (`label()`/`short()`), `QualityStageLevel`, `QualityStageKind`, `QualityStageStatus`, `QualityEventType`, `QualitySapRequestStatus`.

## Services (`app/Services/Quality/`)

| Service | Responsabilidade |
|---------|------------------|
| `QualityWorkflowService` | Máquina de estados, transações, locks, eventos, encerramento |
| `QualityRoles` | Papéis (Gestão por flags; N1/N2 por `quality_members`); escopo de visibilidade por empresa |
| `QualityActivities` | Atividade (Service) de cada ciclo, configurável |
| `SurveyInformRules` | Informe de encerramento do Levantamento (1º ciclo) |
| `QualityBoard` | Dados das páginas do N1 (obras, usuários) e do N2 (decisões, por empresa) |
| `QualityNoteFilters` | Filtros por dados da Nota (rubrica, região, regional, município, localização, grupos, status, datas) |
| `QualityDesignerResolver` | Desenhistas elegíveis (mesma empresa + habilitado na atividade da ciclo) |
| `QualityEligibilityService` | Query do Pool (Notas), regras fixas + `quality_pool_rules` |
| `QualityQueues` | Filas por papel (filtros sobre `state`/`phase`) |
| `BudgetOrderRules` | Validação das ordens da 2º ciclo |

## Permissões

| Gate | Quem | Uso |
|------|------|-----|
| `quality.access` | Gestão ou membro N1/N2 | Visão geral, filas, histórico |
| `quality.n2` | N2 (membro) ou Gestão | Cartões e filas do N2 |
| `quality.pool` | Gestão | Pool e despacho ao N1 |
| `quality.manage` | `management`, `admin`, `superadm` | Motivos e configurações |
| `QualityProcessPolicy` | — | `view`, `actAsN1`, `actAsN2`, `comment` (portão 403; o service revalida) |

## Telas (todas em `layouts.padrao`: breadcrumb + sidebar + hero/metric-cards)

`quality/dashboard`, `pool`, `queue` (13 filas por papel), `history`, `show` (progresso por ciclo, ações por papel, abas Histórico / Rodadas / Rejeições / SAP), `categories` (busca, filtro, criar/editar/ativar/ordenar), `settings`. Menu superior e sidebar filtrados por perfil.

## Testes

- `tests/Feature/QualityWorkflowFeatureTest.php` — fluxo completo de ponta a ponta e regras de negócio.
- `tests/Feature/QualityScreensFeatureTest.php` — telas, permissões HTTP, formulário Livewire dedicado.
- `tests/Unit/QualityDomainContractTest.php` — enums em PT-BR e regras de ordens.
