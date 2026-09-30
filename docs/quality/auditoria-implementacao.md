# Auditoria da implementação inicial do módulo Qualidade

Data: 2026-09-30. Base: código da branch `BiGSerial/teste-orca-codex` **antes** da revisão (entrega inicial do Codex).
Método: leitura do código (migration, services, controller, policies, Livewire de Desenho, views e testes). O relatório do Codex não foi tomado como prova.

Legenda: **OK** · **PARCIAL** · **AUSENTE** · **INCORRETO**

| # | Requisito | Esperado | Implementado (evidência) | Status | Problema | Correção |
|---|-----------|----------|--------------------------|--------|----------|----------|
| 1 | N2 → N1 | N2 despacha ao N1 da empresa; N1 recebe fila própria | `QualityWorkflowService::dispatch` cria `QualityProcess` da empresa e já cria estágio N1 de *análise* (`createStage(... N1, 1)`) | INCORRETO | O N1 nasce "analisando" um croqui que ninguém fez; não existe "aguardando despacho para desenhista" | Estado `AWAITING_N1_DISPATCH` + ação `dispatchToDesigner` |
| 2 | N1 → Desenhista | N1 escolhe desenhista da própria empresa, habilitado em DESENHO | Não existe. `original_designer_id = production->user_id` é fixo | AUSENTE | Nenhuma ação de despacho N1, nem validação de empresa/serviço | `QualityWorkflowService::assignDesigner` + `QualityDesignerResolver` (backend) |
| 3 | Usuário Desenho por empresa | `service_users.service=true` + `users.company_id` = empresa do processo | Não validado | AUSENTE | — | Validação no service (não só no filtro visual) |
| 4 | 1ª passada | Desenhista → N1 → (rejeita ↔ corrige) → N2 → aprova | `submitDrawing` → N1; `approve` → N2; `approve` N2 libera BUDGET | PARCIAL | Sem despacho N1→desenhista; rejeição do N2 volta direto ao desenhista | Ver itens 1, 2, 5 |
| 5 | Retorno N2 → N1 → Desenhista | N2 devolve **ao N1**, que encaminha ao desenhista | `reject` cria estágio `DRAWING` para o desenhista original independente de quem rejeitou (teste `project n2 rejection must return to n1...` só valida que volta ao desenhista) | INCORRETO | N2 pula o N1 — viola regra central | Estado `N2_RETURNED` (fila do N1) + `forwardReturn` |
| 6 | 2ª passada | N2 aprova 1ª → libera 2ª; N1 despacha; desenhista faz Desenho+Orçamento | `BUDGET_RELEASED` cria DRAWING para `original_designer_id` diretamente | INCORRETO | N1 não despacha; executor fixo | Após aprovação N2: `AWAITING_N1_DISPATCH` fase `BUDGET` |
| 7 | Formulário dedicado | Componente próprio p/ Qualidade, sem `if` no encerramento normal | `Forms/QualityClosing` existe e `Desenho/Main::getAnalise` desvia para ele | PARCIAL | Só aceita o desenhista **original** (`production->user_id`); mostra só a última rejeição; orçamento é 3 campos soltos sem regras reaproveitadas; sem comentários; sem contexto de N1/origem | Reescrever aceitando o designado da rodada; contexto completo |
| 8 | Rejeições estruturadas | categoria, subcategoria, nível, etapa, rodada, usuário, empresa, desenhista, observação, timestamp; múltiplos motivos | `quality_rejections` + `quality_rejection_items` | PARCIAL | `designer_id` é sempre o original; N2 pode rejeitar sem motivo? Não: exige ≥1 motivo (exigência do N1, mas N2 devia poder devolver só com comentário) | `designer_id` = executor da rodada; N2 exige motivo **ou** observação |
| 9 | Cadastro de motivos | CRUD real: criar/editar/ativar/ordenar, subcategoria, aplicação por etapa, pesquisa | `categories.blade.php` + `storeCategory/updateCategory/toggleCategory` | PARCIAL | Sem ordenação na tela, sem pesquisa, sem filtro; não valida profundidade (subcategoria de subcategoria); aplicabilidade só como 2 booleans | Busca, ordenação, validação de nível, tela reorganizada |
| 10 | Histórico completo | eventos com usuário, empresa, data, etapa, nível, rodada, estado anterior/posterior | `quality_events` | PARCIAL | Sem `from_state`/`to_state`, sem papel, sem destinatário; faltam eventos (despacho N1→desenhista, início desenhista, análise N1/N2, comentário, SAP, encerramento Production) | Colunas novas + tipos de evento novos |
| 11 | Rodadas | cada retorno gera rodada rastreável | `round_number` incrementa só na rejeição | PARCIAL | Retorno N2→N1→desenhista não existe; chave única não distingue tipos de estágio | `kind` de estágio + `process.round_number` |
| 12 | Pool | é query, sem tabela | `QualityEligibilityService::query` | OK | Sem explicação "por que apareceu" | `explain()` + painel na UI |
| 13 | Elegibilidade configurável | regras fixas x configuráveis | `quality_settings.pool.criteria` (status, empresas) | PARCIAL | Regras fixas hardcoded sem exposição; `eligible()` duplica a query | Separar e expor fixas; `eligible()` reusa a query |
| 14 | SAP | usar infra existente; registrar solicitação/retorno/erro | Nada | AUSENTE | O SICODE **não tem** integração de escrita ao SAP: `notes.nstats` é importado (`BaseOV`/`BaseEP`). O encerramento normal fecha só a Production (`status=5`, `completed`) | `QualitySapStatusGateway` + tabela `quality_sap_requests` (ver `integracao-sap.md`) |
| 15 | Encerramento da Production | N2 final finaliza a atividade do desenhista | Teste afirma `assertFalse($production->completed)` | INCORRETO | Contrato oposto ao pedido | Encerrar Production na aprovação final, após SAP OK |
| 16 | Falha SAP | não concluir silenciosamente | — | AUSENTE | — | Estado `SAP_FAILED`, retry, evento |
| 17 | Visão N2 | pool, despachados, empresa, N1, desenhista, passada, rodada, aguardando N1/N2/desenhista, rejeitados, concluídos | Dashboard com 8 contadores | PARCIAL | Sem "aguardando desenhista" por passada com N1/desenhista visíveis; lista sem N1/desenhista atual/rodada | Novo dashboard e filas |
| 18 | Visão N1 | 7 filas próprias, só da empresa | `queue` genérica (`?queue=n2`) | PARCIAL | Sem as 7 filas | Filas por estado |
| 19 | Visão do desenhista | distinguir normal x Qualidade; mostra etapa, rodada, N1, origem da devolução, comentários, motivos, arquivos | Nenhum sinal na lista do Desenho | AUSENTE | — | Badge + resumo na lista de Desenho |
| 20 | Permissões | `quality.access`/`quality.manage` | `Gate` em `AuthServiceProvider`; N1 = `analyst`, N2 = `can_dispatch/management` | PARCIAL | `assertCurrent` mistura regras; papéis espalhados em 3 lugares (policy, service, model scope); `quality.manage` deixa N2 `can_dispatch` sem Pool | `QualityRoles` central |
| 21 | Segurança | backend valida empresa | Policy `act` + scope `visibleTo` | PARCIAL | Desenhista validado como "original"; nenhuma validação de designado | Validar designado por rodada |
| 22 | Concorrência | lock | `lockForUpdate` + `assertCurrent` | OK | manter | — |
| 23 | Duplicidade | 1 processo por Production | `unique(production_id)` | OK | manter | — |
| 24 | Menu | entrada QUALIDADE por perfil | `menu_itens.blade.php` (5 itens) | PARCIAL | Faltam "Em andamento", "Aguardando N2" | Completar |
| 25 | Layout | parecer SICODE | Views estendem `layouts.app` (scaffold Laravel com `<h4>OLA MUNDO</h4>`), sem breadcrumb, HTML em linha única | INCORRETO | Layout errado (`layouts.padrao` é o padrão); textos em inglês/PT misturados nos enums/labels (PROJECT/BUDGET/DRAWING/PENDING) | Migrar para `layouts.padrao`, PT-BR único |
| 26 | Testes | fluxo real completo | 5 testes feature | PARCIAL | Validam o fluxo errado (N2 rejeita → desenhista) | Reescrita completa |

## Conclusão

O que existia era um esqueleto (tabelas, CRUD, telas de consulta) com um fluxo **diferente** do especificado: o N1 não despacha, o desenhista é fixo, o N2 devolve direto ao desenhista, não há SAP nem encerramento da Production, e a UI usa o layout errado. O backend foi refeito antes de qualquer trabalho de UI (ver `modelo-dominio.md` e `fluxo-funcional.md`).

## Situação após a revisão

| # | Status atual | Evidência |
|---|--------------|-----------|
| 1 N2 → N1 | OK | `QualityWorkflowService::dispatch`; `test_only_n2_can_dispatch_and_company_needs_an_n1` |
| 2 N1 → desenhista | OK | `assignDesigner`; `test_full_flow_...` |
| 3 Desenhista por empresa/serviço | OK | `QualityDesignerResolver`; `test_n1_cannot_dispatch_to_designer_of_another_company`, `..._without_desenho_service`, `test_service_flag_false_is_not_enabled_in_desenho`, `test_http_flow_...` |
| 4 1ª passada | OK | `test_full_flow_...` (rejeição N1 + devolução N2 + troca de desenhista) |
| 5 N2 → N1 → desenhista | OK | `reject()` (N2 → `N2_RETURNED`); asserções `current_stage = N1` no teste de fluxo |
| 6 2ª passada | OK | `approve()` (N2 na 1ª passada → `AWAITING_N1_DISPATCH`, fase `BUDGET`) |
| 7 Formulário dedicado | OK | `Forms\QualityClosing` reescrito; `test_dedicated_desenho_form_only_opens_...` |
| 8 Rejeições estruturadas | OK | `quality_rejections(_items)`; `test_n1_rejection_requires_a_reason_...`, `test_n2_return_accepts_comment_only_...` |
| 9 Cadastro de motivos | OK | tela `quality/categories` (busca, filtros, criar/editar/ativar/ordem, passada) |
| 10 Histórico | OK | `from_state/to_state/actor_role/target_user_id` + 17 tipos de evento |
| 11 Rodadas | OK | `process.round_number`, `kind` em `quality_stages`; asserção `[1, 2, 3]` |
| 12 Pool | OK | `explain()` + painel "Por que uma Nota aparece aqui" |
| 13 Elegibilidade | OK | `FIXED_RULES` x `pool.criteria`; `eligible()` reusa a query |
| 14 SAP | PARCIAL (por desenho) | Gateway + tabela de tentativas; sem API real no SICODE (ver `integracao-sap.md`) |
| 15 Encerramento da Production | OK | `test_full_flow_...` (`completed`, `status 5`, `nstats`) |
| 16 Falha SAP | OK | `test_sap_failure_keeps_process_open_and_retry_completes_it`, `test_missing_sap_target_status_blocks_completion` |
| 17 Visão N2 | OK | dashboard + filas `aguardando-n2`, `aguardando-desenhista`, etc. |
| 18 Visão N1 | OK | 7 filas próprias (`QualityQueues`) |
| 19 Visão do desenhista | OK | selo na lista do Desenho + formulário com contexto |
| 20 Permissões | OK | `QualityRoles`, gates `quality.access/n1/n2/manage`; `test_menu_and_routes_respect_profiles` |
| 21 Segurança | OK | validação no service; `test_http_flow_...` |
| 22 Concorrência | PARCIAL | `lockForUpdate` + `test_stale_action_on_already_handled_round_is_rejected` (não há teste com processos paralelos reais) |
| 23 Duplicidade | OK | `test_duplicate_dispatch_is_blocked` |
| 24 Menu | OK | menu superior e sidebar por perfil |
| 25 Layout | OK | `layouts.padrao`; `test_every_screen_renders_in_the_standard_layout_in_portuguese` |
| 26 Testes | OK | 29 testes / 216 asserções |
