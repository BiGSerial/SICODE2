# Reformulação do Encerramento (2026-10-16)

> Documento mestre da reformulação. Orquestrador: sessão principal. Cada item do checklist só é marcado `[x]` com evidência (teste, tela validada ou diff revisado). Complementa `FASE_2_ATIVIDADE_PRODUCAO.md` e `ACOMPANHAMENTO_ORQUESTRACAO.md`.

## 1. Objetivo

O Encerramento passa a se comportar como qualquer outra atividade do sistema (Despacho + Serviço), mas a **unidade de trabalho listada é a ORDEM**, não a Nota. A Nota (obra) continua como envelope (`Production` por Nota, item por Ordem em `closure_order_states`) e como contexto da tela de gestão da obra.

## 2. Tipo de meta: P / M / F

Derivado da operação `0020` (`operations.operacao='0020'`) com `status LIKE 'CONF%'` e `fimReal` preenchido, em Ordem com `statusSist LIKE 'LIB%'`, não cancelada, ainda não `ENTE%/ENCE%`:

| Tipo | Badge | Regra (mês corrente = Y) |
|---|---|---|
| **M** Meta atual | verde | `fimReal` no mês Y-1 |
| **P** Passivo | vermelho | `fimReal` antes de Y-1 e ainda não encerrada |
| **F** Meta futura | azul | `fimReal` no mês Y |

- O tipo é **dinâmico enquanto a atividade está aberta** (F vira M na virada do mês; M vira P se não encerrar) — vale para despacho, serviço e carteira.
- **Congela ao encerrar**: ao detectar `ENTE/ENCE` (ou finalizar tecnicamente), grava-se `closed_meta_type` + `closed_meta_period` em `closure_order_states`; nunca mais muda. Permite medir "executado na meta × fora da meta".
- Se existir `closure_target` (inclusive exceção atípica), o ciclo do target prevalece sobre a data (ciclo == mês corrente → M; ciclo < mês corrente → P).
- Serviço único: `App\Services\Closure\ClosureMetaClassifier` (também expõe `badge()`, label e classe CSS).

## 3. Status da atividade (`closure_order_states.operational_status`)

Mantidos: `AVAILABLE` (Disponível/pilha), `ASSIGNED` (Atribuída), `IN_PROGRESS` (Em andamento), `CLOSED`, `CANCELLED`.
Novos/renomeados na UI:

| Valor | Rótulo | Observação |
|---|---|---|
| `WAITING_PARTNER` | Aguardando Parceira | substitui `WAITING_RETURN` (migrar valor); acionado por pendência enviada à parceira |
| `DEPOSIT_BLOCKED` | Depósito Bloqueado | |
| `DEPOSIT_RELEASED` | Depósito Liberado | |
| `PAUSED` | Pausa | motivo obrigatório |
| `FINISHED` | Finalizado | usuário concluiu; aguarda o SAP (statusSist ENTE/ENCE) para virar `CLOSED` |
| `WAITING` | Aguardando | finalizado antes da hora (meta futura, se permitido) — aguarda a meta chegar |

## 4. Configurações do despachante (`closure_settings`, chave/valor)

- `allow_future_completion` (bool): usuário pode concluir Ordens F; ao concluir vão para `WAITING`.
- `self_assign_enabled` (bool): habilita a lista de auto-atribuição no Serviço.
- `self_assign_types` (json P/M/F): tipos liberados para auto-atribuição.

## 5. Telas

1. **Despacho** (`dispatch/encerramento`): lista por Ordem no padrão das demais atividades (colunas Tipo/Nota/Ordem/Obra/Município/Empresa/Status SAP/Operações/Prioridade…), seleção em massa de Ordens de várias Notas, painel de configurações (§4).
2. **Painel analítico do despachante**: volumetria por usuário × tipo × status, interação usuário↔parceira (pendências abertas, tempo de resposta), pedido de prioridade para as Ordens de uma obra, transferência de responsabilidade.
3. **Serviço** (`services/encerramento`): "Minha atividade" idêntica às demais; lista "Atividade Encerramento" para pegar atividade (se habilitada); alteração de status; finalizar.
4. **Gestão da Obra** (por Nota): dados da obra, Ordens e status, chat entre usuários envolvidos, categorização do problema ao enviar à parceira, troca de status, finalizar.
5. **Parceira**: caixa de pendências existente evolui para o chat/categorias.

## 6. Notificações

Sino (`SystemNotification` + `UserNotificationData`) **e** toast via `extras['toast']=true` (o `AllNotifies::pollForToasts` já deduplica por id; layout `padrao.blade.php:328` exibe). Disparo apenas na transição de estado do destinatário, nunca em render/poll. Ver §9 do doc de ondas.

## 7. Ondas e checklist (orquestrador cobra)

Só se marca `[x]` com evidência. "testes + relatório do agente" = suíte `tests/Feature/Closure` verde (ver §10) e relatório do agente revisado pelo orquestrador. Nenhuma tela foi validada no navegador ainda (ver §11).

### Onda 1 — Fundação
- [x] **A1 Domínio** _(2026-10-16: suíte Closure 102 verdes/510 asserções, relatório do agente)_ — `ClosureMetaClassifier`, `closure_settings` + `ClosureSettings`, novos status + migração `WAITING_RETURN`→`WAITING_PARTNER`, `closed_meta_type/period`, congelamento, `WAITING`/futura, despacho multi-Nota sem `closure_target`.
- [x] **A2 Colaboração** _(2026-10-16: 26 testes novos verdes, relatório do agente)_ — `closure_work_messages` (chat), taxonomia de categorias, `closure_priority_requests`, `ClosureWorkService`.

### Onda 2 — Telas
- [x] **B Despacho + configurações** _(testes + relatório do agente)_
- [x] **C Painel analítico** _(testes + relatório do agente)_
- [x] **D Serviço / Minha atividade / auto-atribuição** _(testes + relatório do agente)_
- [x] **E Gestão da Obra + parceira** _(testes + relatório do agente)_

### Onda 3 — Integração
- [x] **F Notificações + toast** por evento — `ClosureNotifier` + `ClosureNotifierTest`; `orderAssigned` agora também dispara em `assign`/`transfer` do workflow (painel analítico e lote), com dedupe e após commit. Pendente: validação visual do toast no navegador.
- [x] **G Menu/rotas/gates** _(testes + relatório do agente: `ClosureMenuTest`, `ClosureSpecializedNavigationTest`)_
- [x] **H Docs** — este documento, guias de usuário, ACOMPANHAMENTO (Rodada 6) e `docs/Notificacoes.md` atualizados e revisados pelo orquestrador.
- [ ] **I QA** _(parcial: suíte Closure 170 verdes/831 asserções; revisão independente feita e 7 achados corrigidos; PENDENTE: validação visual no navegador, aplicar migrations no DB compartilhado, 5 falhas em PartnerAccessGateTest a confirmar se preexistentes)_: Pint, `view:cache`, validação na stack efêmera e `/code-review` ainda pendentes.

## 8. Decisões tomadas pelo orquestrador (sem bloquear)

- Mantém-se Production por Nota + item por Ordem (já implementado e aderente ao padrão das outras atividades).
- Migrations nunca editam arquivos já mergeados: sempre novas.
- Migrations **não** são aplicadas no MariaDB compartilhado do host sem aval; testes usam o banco de teste.

## 9. Entregue (o que realmente existe no código)

### 9.1 Rotas e gates

| Rota (`closure.*`) | URL | Gate / acesso | Componente |
|---|---|---|---|
| `closure.dispatch` | `/encerramento/despacho` | `can:closure.dispatch` | `Closure\Dispatch\Index` |
| `closure.dispatch.dashboard` | `/encerramento/despacho/painel` | `can:closure.dispatch` | `Closure\Dispatch\Dashboard` |
| `closure.work` | `/encerramento/minha-atividade` | `can:closure.work` | `Closure\Work\Index` |
| `closure.work.available` | `/encerramento/atividades-disponiveis` | `can:closure.work` (menu só aparece com auto-atribuição ligada) | `Closure\Work\Available` |
| `closure.work.show` | `/encerramento/obra/{note}` | `auth`; nível de acesso resolvido por `ClosureWorkService::accessLevel` | `Closure\Works\Show` |
| `partner.closure.issues` | `/partner/encerramento/pendencias` | `partner.permission:closure.issues.list` | `Partner\ClosureIssues\Index` |
| `partner.closure.work.show` | `/partner/encerramento/obra/{note}` | `partner.permission:closure.issues.respond` | `Closure\Works\Show` (view `partner/closure-work`) |

Gates (`AuthServiceProvider`): `closure.dispatch` e `closure.manage` = flag `closure_manager`; `closure.work` e `closure.view` = `closure_operator` ou `closure_manager`; `closure.manager` é alias de compatibilidade. `admin`/`superadm` passam em todos. Níveis da Gestão da Obra: `DISPATCHER` (gate `closure.dispatch`), `INTERNAL` (tem Ordem atribuída/sob posse na Nota), `PARTNER` (empresa com item ou pendência na Nota), senão sem acesso. A rota `closure.order.detail` (detalhe de Ordem) continua existindo.

### 9.2 Serviços (`app/Services/Closure/`)

`ClosureMetaClassifier` (P/M/F), `ClosureSettings`, `ClosureDispatchService` (`dispatchOrders` multi-Nota → `ClosureMultiDispatchResult`), `ClosureOrderWorkflowService` (claim/assign/transfer/returnToPool/start/waitForReturn/setPriority/changeStatus/finish/promoteWaiting/syncFromSap), `ClosureOrderBatchWorkflowService` (prévia + execução, teto 100, ações: claim, assign, transfer, return_to_pool, start, wait_for_return, set_priority, change_status, finish), `ClosureIssueWorkflowService`, `ClosureWorkService` (visão por Nota, chat, acesso), `ClosurePriorityService` (pedir/aceitar/recusar/limpar), `ClosureSapSyncService`, `ClosureNotifier`, `Analytics\ClosureDispatcherAnalytics`, `Queries\ClosureDispatchListQuery`. Enum `App\Enum\ClosureOperationalStatus`.

### 9.3 Comandos agendados (`app/Console/Kernel.php`)

| Comando | Quando | O que faz |
|---|---|---|
| `closure:sync-closed` | 05:50, 08:50, 10:50, 12:50, 14:50, 16:50, 20:50 (20 min após cada sync de Ordens/Operações) | Detecta `statusSist` ENTE%/ENCE% (`CLOSED`) ou Ordem cancelada (`CANCELLED`); congela `closed_meta_type/period`. |
| `closure:promote-waiting` | diário 06:00 | `WAITING` → `FINISHED` quando a Ordem deixou de ser meta futura. |

### 9.4 Migrations (`2026_10_16_*`) — NÃO aplicadas no banco compartilhado

| Arquivo | Efeito |
|---|---|
| `100000_add_meta_freeze_and_closure_settings` | colunas `closed_meta_type/period` em `closure_order_states`; tabela `closure_settings`. |
| `100000_create_closure_work_messages_table` | chat por Nota (autor, papel, visibilidade, vínculo opcional a pendência/Ordem). |
| `100100_create_closure_priority_requests_table` | pedidos de prioridade por Nota. |
| `100100_rename_closure_waiting_return_to_waiting_partner` | migra o valor `WAITING_RETURN` → `WAITING_PARTNER` em estados, períodos, eventos e snapshots de pendência. |
| `100200_seed_closure_issue_taxonomy` | semeia categorias/subcategorias (Material/MIGO, Documentação/As-built, Medição, Depósito, Apontamento, Dados da obra, Outros); idempotente por slug. |

### 9.5 Configurações (`closure_settings`, editadas no painel do Despacho)

`allow_future_completion` (padrão desligado), `self_assign_enabled` (padrão desligado), `self_assign_types` (padrão P e M). Leitura com cache de 60 s, invalidado ao gravar.

### 9.6 Status → rótulo

| Valor | Rótulo | Valor | Rótulo |
|---|---|---|---|
| `AVAILABLE` | Disponível | `PAUSED` | Pausa |
| `ASSIGNED` | Atribuída | `FINISHED` | Finalizado |
| `IN_PROGRESS` | Em andamento | `WAITING` | Aguardando |
| `WAITING_PARTNER` | Aguardando Parceira | `CLOSED` | Encerrada |
| `DEPOSIT_BLOCKED` | Depósito Bloqueado | `CANCELLED` | Cancelada |
| `DEPOSIT_RELEASED` | Depósito Liberado | | |

Mudança manual de status (`MANUAL_TRANSITIONS`): de Atribuída/Em andamento/Pausa para Em andamento, Pausa, Aguardando Parceira, Depósito Bloqueado/Liberado (com restrições por origem); `FINISHED` e `WAITING` só reabrem para Em andamento. Motivo obrigatório ao ir para Pausa, Aguardando Parceira e Depósito Bloqueado, e ao reabrir de Finalizado/Aguardando. Pode finalizar quem está em Atribuída, Em andamento, Pausa ou Depósito Liberado (dono ou gestor).

### 9.7 Regras P / M / F e finalização

- Regras de tipo: ver §2 (implementadas em `ClosureMetaClassifier`; congelamento em `syncFromSap`).
- Finalizar Ordem **F**: recusa com a mensagem "meta futura não pode ser finalizada antes do mês da meta", a menos que `allow_future_completion` esteja ligado; ligado, a Ordem vai para `WAITING` (evento `WAITING_FUTURE`) e a rotina diária a promove a `FINISHED` quando deixar de ser F.
- Finalizar P/M: `FINISHED`; só vira `CLOSED` quando o SAP mostrar ENTE/ENCE (`closure:sync-closed`).

### 9.8 Mapa evento → notificação → toast → destino

Todos os eventos disparam sino + toast (`extras.toast=true`) pelo `ClosureNotifier`. Regras em `docs/Notificacoes.md`.

| Evento (`extras.event`) | Gatilho | Destinatários | Destino (link) |
|---|---|---|---|
| `closure.order_assigned` | despacho para usuário (tela de Despacho) | usuário atribuído | Gestão da Obra |
| `closure.issue_requested` | pendência enviada à parceira | usuários da empresa destinatária (e matriz) com `closure.issues.list` | `partner.closure.issues` |
| `closure.issue_returned` | pendência devolvida para correção | idem | `partner.closure.issues` |
| `closure.partner_responded` | parceira responde | quem abriu, atribuído e detentor | Gestão da Obra |
| `closure.work_message` | mensagem no chat | participantes internos; parceira só se visibilidade "Com parceira" | Gestão da Obra (parceira: caixa de pendências) |
| `closure.priority_requested` | pedido de prioridade | todos `closure_manager` | Gestão da Obra |
| `closure.priority_decided` | aceite/recusa | quem pediu | Gestão da Obra |

### 9.9 Telas (resumo funcional)

- **Despacho**: lista por Ordem (`ClosureDispatchListQuery`), filtros, seleção em massa (várias Notas), prévia e confirmação com recibo (já despachada/recusada), painel de configurações (§9.5).
- **Painel analítico**: filtros, matriz usuário×tipo×status clicável, gráficos, interação com parceiras, pedidos de prioridade, obras críticas, priorizar obra, transferir responsabilidade com recibo.
- **Minha atividade**: lista com filtros (status, tipo, busca), ações por linha e em lote (prévia + recibo), mudança de status, finalizar.
- **Atividades disponíveis**: lista de Ordens `AVAILABLE` dos tipos liberados, assumir até 100 por vez com confirmação. Não notifica ninguém.
- **Gestão da Obra**: dados e Ordens, mudar status, finalizar (uma/todas), chat com anexos e visibilidade, enviar pendência à parceira (categoria, subcategoria, empresa, bloqueante, Ordens), validar/devolver/resolver/responder, pedir prioridade.
- **Parceira**: caixa de pendências existente + a mesma tela da obra restrita ao nível `PARTNER`.

## 10. Testes

Suíte `tests/Feature/Closure`: **159 testes, 799 asserções, verdes em 2026-10-16** (rodar via `docker exec sicode2-app ./vendor/bin/pest tests/Feature/Closure`). Arquivos novos: `ClosureAvailableWorkTest`, `ClosureDispatchDashboardScreenTest`, `ClosureDispatchScreenTest`, `ClosureDispatcherAnalyticsTest`, `ClosureMenuTest`, `ClosureMetaClassifierTest`, `ClosureNotifierTest`, `ClosurePartnerWorkScreenTest`, `ClosurePriorityServiceTest`, `ClosureSettingsTest`, `ClosureStatusTransitionsTest`, `ClosureSyncClosedCommandTest`, `ClosureWorkMessagesTest`, `ClosureWorkScreenTest`, `ClosureWorkServiceTest`, `ClosureWorkShowScreenTest` (+ `ClosureScreenFixture`); ampliados: Dispatch, IssueWorkflow, OrderWorkflow, SpecializedNavigation.

## 11. Pendências e limitações conhecidas

- **Telas não testadas por GET de página inteira**: o layout legado (`Status\Bancosicode`) dá 500 no banco de teste; os testes validam rota, gate e o componente Livewire (403 HTTP preservado).
- **Validação visual no navegador pendente** (stack efêmera + toast + gráficos).
- **Migrations ainda não aplicadas** no banco compartilhado; rodar antes de usar em QA (`deploy-sicode2 qa fast database`).
- **2F**: fechar a `Production` da Nota quando todos os itens estiverem terminais — pendente.
- **MIGO**: integração adiada (existe só como categoria da taxonomia).
- **Notificação de atribuição**: `closure.order_assigned` é disparada apenas pelo Despacho (`Dispatch\Index`); transferências pelo painel analítico ou ações em lote da Minha atividade não notificam o novo responsável (verificado: nenhum uso de `ClosureNotifier` nesses componentes). Por isso F segue `[ ]`.
- Etapas H e I do checklist continuam abertas.
