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

O Encerramento é um **Service como os demais** (`folder = encerramento`, grupo PÓS OBRA do menu ATIVIDADES): usa as rotas padrão `dispatch.*` / `services.*` e as views das pastas `dispatchs/encerramento` e `services/encerramento`, com a sidebar padrão (ACOMPANHAMENTO).

1. **Despacho > Lista para Encerramento** (`dispatch.main`): lista por Ordem (Tipo P/M/F, Nota, Ordem, Obra/Município, Empresa, Status SAP, Operações, Prioridade), seleção em massa de Ordens de várias Notas (página ou todas as filtradas), cards-resumo/filtros em linha, despacho por **modal** (`Closure\Dispatch\DispatchModal`), painel de configurações (§4).
2. **Despacho > Controle de Encerramento** (`dispatch.stack`): o que já foi despachado, de todos; filtros por tipo, status, empresa, responsável e prioridade; ações em lote (atribuir, transferir, prioridade, status).
3. **Despacho > Transferência** (`dispatch.transprod`): pedidos de prioridade pendentes (aceitar/recusar), obras críticas e transferência de responsabilidade por obra, com recibo. Contador de pedidos pendentes no menu.
4. **Despacho > Painel analítico** (`dispatch.dashboard`): volumetria por usuário × tipo × status, interação usuário↔parceira, prioridades, obras críticas.
5. **Serviço > Lista / Acompanhamento** (`services.main` / `services.accompany`): "Minhas atividades" (ações por linha e em lote) e aba "Atividades disponíveis" (`?aba=disponiveis`, só com auto-atribuição ligada). **Meu histórico** (`services.historic`): Ordens finalizadas/encerradas/canceladas com o tipo congelado.
6. **Gestão da Obra** (por Nota, `closure.work.show`) e **Detalhe da Ordem** (`closure.order.detail`): chat, categorização do problema, status, finalizar.
7. **Gestão do ciclo** (Visão geral / Meta / Passivo, `closure.manager`): itens no menu do despacho.
8. **Parceira**: contrato é redirecionado para `dispatch.partner` (`dispatchs/encerramento/partner_main` = `Partner\ClosureIssues\Index`); a caixa de pendências do portal continua.

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

### 9.1 Rotas, views e gates

| Menu | Rota | View | Componente |
|---|---|---|---|
| Despacho > LISTA PARA ENCERRAMENTO | `dispatch.main` | `dispatchs.encerramento.main` | `Closure\Dispatch\Index` + `Closure\Dispatch\DispatchModal` (evento `openForOrders`) |
| Despacho > CONTROLE DE ENCERRAMENTO | `dispatch.stack` | `dispatchs.encerramento.stack` | `Closure\Dispatch\Control` (estende `Work\Index` em modo gestor) |
| Despacho > TRANSFERÊNCIA | `dispatch.transprod` | `dispatchs.encerramento.transprod` | `Closure\Dispatch\Transfer` (trait `ManagesPriorityAndTransfer`, comum ao Painel) |
| Despacho > PAINEL ANALÍTICO | `dispatch.dashboard` | `dispatchs.encerramento.dashboard` | `Closure\Dispatch\Dashboard` |
| (contrato) | `dispatch.partner` | `dispatchs.encerramento.partner_main` | `Partner\ClosureIssues\Index` |
| Serviço > LISTA / ATIVIDADES DISPONÍVEIS | `services.main` (`?aba=disponiveis`) | `services.encerramento.main` | `Work\Index` / `Work\Available` |
| Serviço > ACOMPANHAMENTO | `services.accompany` | `services.encerramento.accompany` | `Closure\Work\Index` |
| Serviço > MEU HISTÓRICO | `services.historic` | `services.encerramento.historic` | `Closure\Work\History` |
| Gestão do ciclo | `closure.overview/meta/passive` | `closure.*` | `Closure\Cycles\*` (`can:closure.manager`) |
| Obra / Ordem | `closure.work.show`, `closure.order.detail` | `closure.work-show`, `closure.order-detail` | `Works\Show`, `Orders\Detail` |

As rotas `closure.dispatch`, `closure.dispatch.dashboard`, `closure.work` e `closure.work.available` são **redirects 302** para as rotas padrão (resolvendo o uuid por `ClosureServiceLocator`), só para não quebrar links antigos.

Acesso: as rotas padrão passam pelo middleware `check.service.dispatch` (service_users `dispatch`/`service`; superadm passa). Para não dar 403 a quem tem só as flags, o middleware também libera o Service Encerramento a quem passa em `closure.dispatch` (rotas `dispatch`) ou `closure.work` (rotas `services`), o que inclui admin. Os componentes continuam checando os gates de domínio no `mount` e nas ações.

Gates (`AuthServiceProvider` + `ClosureServiceAccess`): `closure.dispatch` e `closure.manage` = flag `closure_manager` **ou** `service_users.dispatch` no Encerramento; `closure.work` = `closure_operator`/`closure_manager` **ou** `service_users.service`; `closure.view` = qualquer um dos anteriores; `closure.manager` (Visão geral/Meta/Passivo) = só flag `closure_manager` (não vem de vínculo). `admin`/`superadm` passam em todos. Vínculo em outro Service não concede nada. Níveis da Gestão da Obra: `DISPATCHER` (gate `closure.dispatch`), `INTERNAL`, `PARTNER`, senão sem acesso.

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

- **Lista para Encerramento**: lista por Ordem (`ClosureDispatchListQuery`), filtros, seleção em massa (várias Notas), barra flutuante "N selecionadas · Despachar" e atalho por linha que abrem o modal de despacho (`DispatchModal`: resumo por tipo/Nota, destino Empresa → Usuário opcional/pilha, prévia de elegibilidade por Ordem, "despachar só as elegíveis", recibo por Ordem, toast único; chama `ClosureDispatchService::dispatchOrders`), painel de configurações (§9.5).
- **Controle**: lista de todas as Ordens despachadas com filtros (tipo, status, empresa, responsável, prioridade) e ações em lote do gestor.
- **Transferência**: pedidos de prioridade, obras críticas, transferir responsabilidade com recibo.
- **Painel analítico**: filtros, matriz usuário×tipo×status clicável, gráficos, interação com parceiras, pedidos de prioridade, obras críticas, priorizar obra, transferir.
- **Minhas atividades / Acompanhamento**: filtros (status, tipo, busca), ações por linha e em lote (prévia + recibo), mudança de status, finalizar.
- **Atividades disponíveis**: Ordens `AVAILABLE` dos tipos liberados, assumir até 100 por vez com confirmação. Não notifica ninguém.
- **Meu histórico**: Ordens do usuário finalizadas/em aguardando/encerradas/canceladas, com o tipo congelado.
- **Gestão da Obra**: dados e Ordens, mudar status, finalizar (uma/todas), chat com anexos e visibilidade, enviar pendência à parceira, validar/devolver/resolver/responder, pedir prioridade.
- **Parceira**: caixa de pendências existente + a mesma tela da obra restrita ao nível `PARTNER`.

## 10. Testes

Suíte `tests/Feature/Closure`: **181 testes, verdes em 2026-10-16** (rodar via `docker exec sicode2-app ./vendor/bin/pest tests/Feature/Closure`). Arquivos novos: `ClosureAvailableWorkTest`, `ClosureDispatchDashboardScreenTest`, `ClosureDispatchScreenTest`, `ClosureDispatcherAnalyticsTest`, `ClosureMenuTest`, `ClosureMetaClassifierTest`, `ClosureNotifierTest`, `ClosurePartnerWorkScreenTest`, `ClosurePriorityServiceTest`, `ClosureSettingsTest`, `ClosureStatusTransitionsTest`, `ClosureSyncClosedCommandTest`, `ClosureWorkMessagesTest`, `ClosureWorkScreenTest`, `ClosureWorkServiceTest`, `ClosureWorkShowScreenTest` (+ `ClosureScreenFixture`); ampliados: Dispatch, IssueWorkflow, OrderWorkflow, SpecializedNavigation.

## 11. Pendências e limitações conhecidas

- **GET de página inteira nos testes**: o layout legado (`Status\Bancosicode`) dá 500 sem um registro em `bancoupdates`; `ClosureSpecializedNavigationTest::full_pages_render_for_a_superadmin` cria esse registro antes.
- **Contador do menu de serviço**: o item ATIVIDADES > ENCERRAMENTO usa o contador padrão `countnotes` (Productions), que fica zerado para o Encerramento.
- **Validação visual no navegador pendente** (stack efêmera + toast + gráficos).
- **Migrations ainda não aplicadas** no banco compartilhado; rodar antes de usar em QA (`deploy-sicode2 qa fast database`).
- **2F**: fechar a `Production` da Nota quando todos os itens estiverem terminais — pendente.
- **MIGO**: integração adiada (existe só como categoria da taxonomia).
- **Notificação de atribuição**: `closure.order_assigned` dispara no Despacho (`Dispatch\DispatchModal`) e também em `assign`/`transfer` do `ClosureOrderWorkflowService` (cobre transferência no painel/Transferência e ações em lote), com dedupe e após commit; o autor nunca é notificado. Pendente apenas a validação visual do toast.
- Etapas H e I do checklist continuam abertas.
