# Caixas de Pendência entre Áreas e RI — Plano e Checklist

> Módulo: Controle Operacional de Encerramento. Documento **vivo**: marque os itens ao concluir e registre cada sessão no [Log de andamento](#11-log-de-andamento).
> Padrão de telas, cores, formulários e reatividade: [PADRAO_UI_ENCERRAMENTO.md](PADRAO_UI_ENCERRAMENTO.md) (obrigatório para toda tela desta entrega).
> Contexto existente: [GUIA_USUARIO_PENDENCIAS_ENCERRAMENTO.md](GUIA_USUARIO_PENDENCIAS_ENCERRAMENTO.md), [FASE_1_CHECKLIST.md](FASE_1_CHECKLIST.md).

**Status geral:** Fases 0 (núcleo), 1 e 2 concluídas; Fase P (portal da parceira) com núcleo concluído em 2026-10-25; próxima: Fase 3 — despachados e devolutivas.

---

## 0. Como retomar o trabalho (leia primeiro)

1. Leia §2 (regras inegociáveis) e a fase corrente (§4–§8).
2. Abra o [Log de andamento](#11-log-de-andamento) e veja a última entrada: ela diz onde parou e o que falta.
3. Trabalhe **uma fase por vez**, na ordem. Não comece tela antes de a Fase 0 (padrão de UI) e a Fase 1 (base) estarem fechadas.
4. Ao terminar cada item: marque `[x]`, anexe o que foi feito (arquivo/teste) e atualize o log.
5. Fase só fecha com o **Definition of Done** (§3) cumprido.

---

## 1. Escopo (decisões já tomadas — não reabrir)

### 1.1 Caixas de pendência
- Existem caixas **somente** nos Services **Pagamento/Medição** (`pagamento`) e **Fiscalização** (`fiscalizacao`). Desenho **não** tem caixa.
- Cada caixa tem duas abas, de naturezas diferentes:

| | **Para retorno** (despachado) | **Em acompanhamento** (status usuário) |
|---|---|---|
| Origem | Usuário abre e envia a uma área | Entra sozinho quando `orders.statusUser` pertence ao serviço |
| Registro | `closure_issues` (ciclo, eventos, exigências) | **Nenhum** — consulta ao vivo |
| Ação | **Exige resposta** (Responder); origem valida/devolve/resolve | **Só orientação** — sem ação |
| Sai quando | Resolvida | O status usuário muda (automático) |

- **Devolutivas diretas:** Encerramento ↔ Fiscalização são despachados em sentido contrário (`origin_area`/`destination_area`). O Encerramento as vê na aba **Recebidas** da Gestão da Obra. Na Fiscalização, a devolutiva é o botão **Devolver ao Encerramento** na linha da caixa.
- **Mapa de status usuário:** tela de configuração em que se escolhe o **serviço** (Medição ou Fiscalização) e **quais status** pertencem a ele. Um prefixo pertence a **um** serviço (índice único). A caixa só filtra os status do próprio serviço.
- **Tempo visível:** cada linha mostra datas e o tempo em cada etapa (dias corridos, informativo). **Sem** motor de SLA, sem `due_at`, sem alertas agendados, sem backfill de precisão. Cor por tempo é opcional e fica desligada por padrão.
- **Data de entrada no status usuário:** 2 colunas leves em `closure_order_states` (status visto + data da mudança), atualizadas pela rotina de sync. Ordens já no status na primeira execução recebem selo "desde o 1º registro".

### 1.1b Portal da parceira e isolamento de visão (decisões de 2026-10-25)
- **Cada rota tem UM público, qualquer que seja o usuário.** `/partner/encerramento/...` mostra sempre a visão da parceira; `/encerramento/...` nunca. Quem só tem a visão da parceira e abre a rota interna é **redirecionado** para a rota dela.
- **SUPERADM observa como UMA empresa por vez** (a que detém as Ordens; seletor quando a obra tem mais de uma), nunca pela união delas. Vê o aviso "Modo observador", **não vê controles internos e não responde em nome da parceira**.
- **Retorno rico da parceira:** tela própria da pendência com tudo o que o Encerramento pediu (descrição, exigências, motivo da devolução, histórico com datas e tempo entre etapas), resposta **por exigência** (atendido / não se aplica / não consigo, com justificativa), mensagem e **evidências** (anexos). O Encerramento enxerga as respostas por item.
- **Sinalizador no menu da parceira:** bolinha vermelha no item "Encerramento" e selo com a quantidade (aguardando resposta + devolvidas) no link "Pendências".
- **Histórico da parceira:** obras e pendências que ela respondeu, o que respondeu, quando, anexos e o resultado de cada resposta.
- **Toda tela exporta (Excel):** regra do padrão de UI (§ "Toda lista exporta"). Listas por Job + `SystemNotification`; o detalhe de uma pendência baixa na hora.

### 1.2 RI (Retorno Interno) para o Desenho
- RI = `Reclaim` (tabela `reclaims`) com `service_id` do Desenho. O Desenho trata na tela dele; o Encerramento **envia e acompanha**.
- **O RI da Nota vale para todas as Ordens da Nota.**
- **Trava sobreposta (não é novo status operacional):** enquanto há RI ativo para a Nota da Ordem, a Ordem mantém status e posse, mas um guard no `ClosureOrderWorkflowService::transition()` recusa as ações.
- **Lista branca** (passam pela trava, para nunca prender a Ordem): sync do SAP (encerramento/cancelamento), `restoreAfterIssue`, liberação de depósito, mensagens de chat.
- **Estados:** `SENT` (trava) → `RETURNED` (final; libera) | `CANCELLED` (final; só gestão, com motivo; **só destrava as Ordens** — não fecha o `Reclaim` nem mexe nas viabilidades, que são de outra atividade).

### 1.3 Invariantes do RI (evitam quebra de ação e ciclo)
1. **Um RI ativo por Nota** (o RI só vai ao Desenho, então equivale a "por Nota e serviço") — garantido por **índice único em coluna gerada** (`active_note_id`, padrão `open_state_id` dos períodos), não só por `if`. O MariaDB recusa texto em coluna gerada (CONCAT, literal ou coluna char → erro 1901), por isso a coluna é inteira e depende de `closed_at IS NULL`: **o service SEMPRE preenche `closed_at` ao retornar ou cancelar o RI**.
2. **Fim idempotente** — um único método `syncFromReclaim`, disparado por observer pós-commit e por varredura de segurança; rodar duas vezes dá o mesmo resultado.
3. **Reabertura do `Reclaim` pelo Desenho NÃO re-trava** — a equipe é avisada e abre um **novo** RI explicitamente (ciclo +1).
4. **Cancelar só destrava o Encerramento** — o `Reclaim` e a viabilidade (status 11) são de outra atividade e não são alterados (só um comentário informativo é acrescentado ao Reclaim). Um novo RI só é aceito depois que o Desenho concluir o Reclaim em andamento. *(Decisão revisada na Fase 2: antes previa fechar o Reclaim.)*
5. **Atomicidade** — `Reclaim` + comentário + vínculo + eventos numa transação; falhou, nada fica gravado. Preservar a regra "já existe retorno interno ativo para esta obra e serviço" de `ReturnD5`.
6. **Histórico por Ordem** — eventos `RI_SENT`, `RI_RETURNED`, `RI_CANCELLED` em `closure_order_events`, em lote (`batch_id`).

---

## 2. Regras inegociáveis

### 2.1 Migrations (produção compartilhada; o Encerramento ainda não está em uso)
- [ ] Sempre **migration nova**; nunca editar migration já mergeada.
- [ ] **Só pode ser recriada (drop + create)** tabela **nova do Encerramento** (`closure_*`, criada por esta entrega) — hoje: `closure_status_user_routes`, `closure_internal_returns`. Existe lista fixa dentro da migration; qualquer outra tabela é ignorada.
- [ ] Antes de dropar, consultar `information_schema`: se **qualquer tabela fora do módulo** tiver FK para ela, a migration **aborta com erro** e não dropa nada.
- [ ] Tabela nova: não existe → cria; existe no formato certo (`hasColumn` das colunas/índices-chave) → não faz nada; existe divergente → recria.
- [ ] `closure_issues` e `closure_order_states`: **nunca recriadas**. Só colunas **aditivas** (`ADD`), com `hasColumn` antes de cada coluna/índice e valor padrão. `down()` remove apenas as colunas que a própria migration adicionou, se existirem.
- [ ] **Proibido** alterar/renomear/remover coluna ou índice existente, e **proibido tocar** tabela de outra atividade: `orders`, `notes` (só leitura de `statusUser`), `reclaims` (compartilhada com Viabilidade/Desenho — sem coluna, sem FK; vínculo via `closure_internal_returns.reclaim_id` só com índice), `services`, `service_users`, Pagamento, Fiscalização, Desenho.
- [ ] Antes de nomear coluna nova, conferir que não colide com coluna já usada por outro código.
- [ ] Testar cada migration na stack do worktree (`scripts/orca-worktree-setup.sh`) — **nunca** no container da `develop`: banco limpo, tabela no formato certo, tabela divergente, e FK externo bloqueando o drop.
- [ ] Cabeçalho de comentário na migration no estilo de `2026_10_09_090000_create_closure_issue_tables.php` (o que recria, por quê, "sistema ainda sem uso").

### 2.2 Código e arquitetura
- [ ] Notificações: **sempre** `UserNotificationData` via `ClosureNotifier` (dedupe + `DB::afterCommit`); nunca no render/poll; nunca ao autor da ação.
- [ ] Arquivos/anexos: só via `app/Services/Files/` (`FileStorageService`, `EvidenceFileService`…). Nunca `Storage::`/`storage_path()` em Livewire/Controller. Rodar a busca por `Storage::`, `storeAs(`, `storage_path(` nos arquivos tocados.
- [ ] Regra de negócio em **Services**; componentes Livewire só orquestram.
- [ ] Exports pesados: Job + `SystemNotification` ao concluir.
- [ ] Pint: `./vendor/bin/pint --dirty` antes de commitar.
- [ ] Pest para toda regra nova; testes rodam na stack do worktree. **SEMPRE com o banco de teste forçado**: `$DC exec -T -e DB_DATABASE=sicode_teste -e APP_ENV=testing app ./vendor/bin/pest …`. O container define `DB_DATABASE=sicode` no ambiente e o `<env>` do `phpunit.xml` NÃO sobrescreve variável já definida — sem o `-e`, um `RefreshDatabase` apagaria o banco compartilhado.
- [ ] Chart.js (se houver): atualizar datasets **in-place**, nunca substituir o array (regra do `CLAUDE.md`).
- [ ] Sem `Co-Authored-By` em commits/PR (regra do usuário).

### 2.3 UI
- [ ] Toda tela/componente segue o [PADRAO_UI_ENCERRAMENTO.md](PADRAO_UI_ENCERRAMENTO.md). Nada de CSS/JS inline novo fora do que o padrão prevê; reutilizar componentes antes de criar.

---

## 3. Definition of Done (por fase)

- [ ] Todos os itens da fase marcados.
- [ ] Testes Pest da fase verdes (e suíte `tests/Feature/Closure` sem regressão).
- [ ] Pint aplicado.
- [ ] Regras de §2 conferidas (migrations, Storage, notificações).
- [ ] Telas: checklist de PR do padrão de UI cumprido, validadas no navegador na stack do worktree (`orca-ide tab create --url …`) e teardown rodado ao final.
- [ ] Log de andamento atualizado.

---

## 4. Fase 0 — Padrão de UI centralizado (pré-requisito das telas)

> Objetivo: ter um único lugar para tokens, componentes, JS e padrões de formulário, **antes** de criar as telas novas. Refatorar o existente **sem mudar o visual**.
> **Status: núcleo concluído em 2026-10-23.** Falta mover o JS inline restante (itens abertos abaixo).

- [x] Inventário do que já existia: `_styles.blade.php` (tokens `--dash-*`), `components/closure/multi-select`, `work/_confirm-script`, `closureFilterPanel` (inline em `dispatch/index.blade.php`), `btn-dash`, `_status-user-filter`.
- [x] `resources/sass/closure/` criado (precedente `sass/legal/`), importado em `app.scss`: `_tokens`, `_tones`, `_typography`, `_layout`, `_tables`, `_badges`, `_buttons`, `_filters`, `_modals`, `_order-detail`, `_kpi`, `_inbox`, com `_closure.scss` como índice (ordem de cascata preservada).
- [x] `_styles.blade.php` migrado para o SCSS **sem alterar o visual**: comparação automática regra a regra (139 regras originais × 139 compiladas, nenhuma faltando/sobrando, mesma ordem entre seletores repetidos). O arquivo virou stub depreciado; os `@include` antigos continuam válidos.
- [x] `resources/js/closure/` criado e importado no `app.js`: `index.js`, `confirm.js` (`closure:confirm`), `toast.js` (`closure:toast`), `drawer.js` (`closure:drawer-open/close`), `filter-panel.js` (`closureFilterPanel`). Cópia inline do `closureFilterPanel` removida do `dispatch/index.blade.php`; `work/_confirm-script.blade.php` virou stub.
- [x] Componentes Blade em `resources/views/components/closure/`: `stat-card`, `inbox-tabs`, `filter-chips`, `age-chip`, `status-badge`, `lock-badge`, `timeline`, `drawer`, `empty-state`, `form-field`, `action-button` (+ `multi-select` existente). A confirmação é o evento JS `closure:confirm`, não um componente.
- [x] Props documentadas no catálogo do padrão de UI (§5).
- [x] Testes: `tests/Feature/Closure/ClosureUiComponentsTest.php` (renderização + guarda "sem style/script inline nos componentes"). Teste existente do Despacho ajustado para ler o SCSS.
- [x] `npm run build` sem erro; Despacho validado no navegador (bundle expõe `closureFilterPanel`, Alpine inicializa, opções carregam, CSS aplicado, sem erros de console).
- [ ] Mover o JS inline restante para `resources/js/closure/` (cada um com guarda `window.__closure…`): `closureDispatchSelection` (`dispatch/index.blade.php`), `dispatch/_decide-script`, `dispatch/_control-actions`, `dispatch/dispatch-modal`, `dispatch/deposit-calendar`, `work/available`, `works/show`. Validar cada tela no navegador após mover.
- [ ] Remover os `@include` dos stubs (`_styles`, `_confirm-script`) e apagar os stubs quando nenhuma tela os referenciar.
- [ ] (Opcional) Página de guia visual `/encerramento/ui` restrita a superadm.

## 5. Fase 1 — Base: banco, gates, status usuário

> **Status: concluída em 2026-10-23** (código + testes). **As 4 migrations ainda NÃO foram aplicadas no banco compartilhado (`sicode`)** — só foram executadas no `sicode_teste`. Aplicar na stack do worktree (`$DC exec -T app php artisan migrate`) quando for validar as telas.

- [x] Migration `2026_10_23_100000_add_area_routing_to_closure_issues_table` (aditiva): `origin_area` (padrão `CLOSURE`), `destination_area` (padrão `PARTNER`), `kind` (padrão `ISSUE`), `source` (padrão `MANUAL`); índice `(destination_area, status)`. Sem colisão de nomes. `down()` remove só o que adicionou.
- [x] Migration `2026_10_23_100100_add_status_user_tracking_to_closure_order_states_table` (aditiva): `status_user_seen`, `status_user_changed_at`, `status_user_estimated`.
- [x] Migration `2026_10_23_100200_create_closure_status_user_routes_table` (nova, recriável): `area`, `status_user_prefix` **único**.
- [x] Migration `2026_10_23_100300_create_closure_internal_returns_table` (nova, recriável): `note_id`, `reclaim_id` (índice, **sem FK**), `design_service_id`, `status`, `cycle`, `message`, `sent_*`, `returned_at`, `cancelled_*`, `closed_at`; coluna gerada `active_note_id` + **índice único** = um RI ativo por Nota.
- [x] `App\Support\Database\GuardedTableRecreator`: só descarta tabela `closure_*` da lista fixa da migration e **aborta** se tabela de fora do módulo tiver FK para ela.
- [x] Testes das migrations (`ClosureInboxMigrationsTest`, 11): tabela no formato certo (não toca), divergente (recria), FK externo (aborta sem dropar), idempotência, `down()` sem remover colunas existentes, `reclaims`/`orders` intactas.
- [x] Models: `ClosureStatusUserRoute`, `ClosureInternalReturn` (escopo `active()`); constantes `KIND_*`/`SOURCE_*` e `fillable` em `ClosureIssue`; `fillable`/casts de status usuário em `ClosureOrderState`.
- [x] `App\Services\Closure\ClosureArea`: códigos, rótulos e Service (pasta) por área; caixas só `MEASUREMENT` (`pagamento`) e `SUPERVISION` (`fiscalizacao`).
- [x] Gates `closure.inbox.{measurement,supervision}` e `.respond` (vínculo `service_users` despacho **ou** serviço no Service da área; gestor/admin/superadm veem todas) via `ClosureServiceAccess::hasInbox()`.
- [x] **Onde `orders.statusUser` é gravado:** só em `sicode:upd_baseOrder` (`Console/Commands/Update/BaseOrder`), 7×/dia (05:30, 08, 10, 12, 14, 16, 20h). **Não foi alterado** (é de outra atividade). Criados `ClosureStatusUserTracker` + `closure:sync-status-user`, agendado `55 5,8,10,12,14,16,20 * * *` (logo após o sync de Ordens e o `closure:sync-closed`). Precisão da data = frequência desse sync (algumas horas).
- [x] Testes (`ClosureInboxFoundationTest`, 9): gates por vínculo/flags, defaults de `closure_issues`, rastreio (1ª vez = estimado; só muda quando o **prefixo** muda; idempotente; vazio; ignora encerradas/canceladas), comando e agendamento, unicidade do prefixo, escopo do RI.
- [ ] Aplicar as migrations no banco da stack do worktree e conferir (`php artisan migrate:status`).
- [ ] Rodar `closure:sync-status-user` uma vez na stack para semear a data (ficará "desde o 1º registro").

## 6. Fase 2 — RI (contrato antes das telas)

> **Status: concluída em 2026-10-23** (serviço, trava, observer, varredura, avisos, contrato e testes). Telas do RI ficam na Fase 5. Contrato: [CONTRATO_RI_ENCERRAMENTO.md](CONTRATO_RI_ENCERRAMENTO.md).

- [x] Lido `ReturnD5` por inteiro e reproduzido no service (Reclaim + comentário + `Viabilities` status 11 + `attach`); regra "já existe retorno interno ativo" preservada.
- [x] `ClosureInternalReturnService`: `send`, `syncFromReclaim`, `cancel`, `handleReclaimSaved`, `syncAll`, `lockFor`, `activeForNote(s)`.
- [x] Guard no `transition()` (`assertNotLockedByInternalReturn`) + lista branca (`restoreAfterIssue`, `releaseDepositByPartner`); SAP sync fora do guard. `ClosureDispatchService::dispatch` recusa Nota travada.
- [x] Eventos `RI_SENT/RETURNED/CANCELLED` por Ordem, em lote (`recordInternalReturnEvent`, sem mudar status/posse/período); rótulos em `ClosureEventLabels`.
- [x] `ClosureReclaimObserver` (registrado em `AppServiceProvider`; nunca quebra o salvamento do Reclaim) + comando `closure:sync-internal-returns` agendado a cada 15 min.
- [x] `ClosureNotifier::internalReturnChanged` (enviado / retornado / cancelado / reaberto), sem notificar o autor.
- [x] Documento do contrato ação × estado: `CONTRATO_RI_ENCERRAMENTO.md`.
- [x] Matriz Pest ação × estado (`ClosureInternalReturnTest`, 18 testes): 11 ações em livre/travada/liberada, lista branca, SAP, despacho, retorno idempotente, observer, varredura/órfãos, reabertura sem re-travar (ciclo 2), cancelamento (só gestão, sem tocar Reclaim/viabilidade), atomicidade, avisos.
- [ ] Concorrência real com 2 sessões (o índice único cobre no banco e está testado na migration; falta teste com duas conexões simultâneas).
- [ ] Tratar RI órfão na tela (Fase 5): a varredura só conta; a gestão cancela.

## 6b. Fase P — Portal da parceira (isolamento, retorno rico, histórico, menu, exportação)

> **Status: núcleo concluído em 2026-10-25** (isolamento, retorno rico, menu, histórico e exportação das telas da parceira, com testes). Faltam as exportações das telas **internas** do Encerramento que ainda não exportam e a validação no navegador.

- [x] **Isolamento por rota:** `ClosureWorkService::forAudience(partner|internal|auto)`; `Show` recebe o público da rota; `ClosureController::workShow` redireciona quem só tem visão de parceira; SUPERADM observa como **uma** empresa (`observingAs`), com seletor e aviso "Modo observador". Achado: a primeira versão observava pela união das empresas e vazava o pedido de outra empresa — corrigido e coberto por teste.
- [x] Observador **somente leitura**: `canRespond` falso e ações internas recusadas (403) na rota da parceira.
- [x] **Tela da pendência** da parceira (`/partner/encerramento/pendencia/{id}`, `Partner\ClosureIssues\Detail`): pedido completo, motivo da devolução em destaque, histórico com datas/tempo entre etapas, arquivos, formulário de retorno.
- [x] **Retorno rico:** `ClosureIssueWorkflowService::respond(…, ?array $answers, array $attachments)`: resposta por exigência (obrigatória quando informada; justificativa se não atendido), resumo no chat, evidências via `EvidenceFileUploader`, `meta.answers` no evento `RESPONDED`. Chamadores antigos (só mensagem) seguem válidos. Lista da parceira e tela da obra agora levam à nova tela (modal simples removido); o Encerramento vê as respostas por item no histórico da pendência.
- [x] **Menu:** `Partner\Count\ClosureIssuesCount` (`dot` no item pai, `badge` no link) + link "Meu histórico"; estilo em `sass/closure/_menu.scss`.
- [x] **Histórico** (`/partner/encerramento/historico`, `Partner\ClosureIssues\History`): retornos por obra/pendência, filtros (busca, situação, período, empresa), KPIs, resultado de cada resposta (aceita / devolvida / em validação / aguardando).
- [x] **Exportação:** `ClosurePartnerExporter` (linhas/rótulos únicos) + `ExportPartnerClosureListJob` (lista e histórico, no escopo do solicitante, aviso com link) + `ClosureIssueDetailExport` (detalhe da pendência: 3 abas, download imediato). Botões "Exportar Excel" nas 3 telas.
- [x] Utilitários de CSS (`_utilities.scss`: `pre-line`, `cell-wrap`, `col-pct-N`, `table-min-lg`) no lugar de `style=""`; teste-guarda estendido às novas telas.
- [x] Testes: `ClosurePartnerWorkScreenAccessTest` (8), `ClosurePartnerMenuBadgeTest` (5), `ClosurePartnerIssueDetailTest` (10), `ClosurePartnerHistoryExportTest` (10).
- [ ] Validar no navegador (stack do worktree): rota da parceira como superadm e como parceira, formulário com anexos, menu com selo/bolinha, exportações.
- [ ] Exportação nas telas internas que ainda não têm (conferir cada tela do Encerramento contra a regra "toda lista exporta").
- [ ] Anexo **por exigência** (hoje os anexos são do retorno todo) — avaliar se vale o custo.
- [ ] Observador: a lista de pendências da parceira (`Index`/`History`) segue o padrão das outras telas do portal (superadm vê todas as empresas, com filtro). Decidir se também exige escolher uma empresa.

## 6c. Fase S — Despacho único e sinalização por cor (2026-10-25)

> **Status: concluída** (regra, telas, servidor e testes). Validada na tela do Despacho no navegador.

- [x] **Despacho único:** Ordem que já tem posse (despachada, em qualquer status não terminal) **nunca é despachada de novo** — nem para a mesma empresa e responsável. Servidor: `ClosureDispatchService::assertNotAlreadyDispatched` recusa com "já foi despachada e está com {responsável | a pilha de {empresa}}. Para mudar o responsável use a Transferência." (em lote vira recusa por Ordem). Prévia do modal: mesma regra. O antigo despacho "idempotente" deixou de existir (2 testes antigos foram atualizados para a regra nova).
- [x] **Tela:** na lista do Despacho, Ordem com posse fica esmaecida, com checkbox e botão de despachar **desabilitados e com o motivo no tooltip**; "Selecionar página" e "Selecionar todas filtradas" só alcançam as despacháveis (`counts['selectable']`, `filteredIds(..., undispatchedOnly)`), e o rótulo mostra a contagem real.
- [x] **Sinalização por cor — regra única** (`App\Services\Closure\ClosureSignal`): 🔴 finalizada há **+24h** e o SAP ainda não encerrou (ENTE/ENCE) · 🟢 concluída (finalizada, aguardando a meta ou encerrada no SAP) · 🔵 atribuída e em tratativa · 🟡 na pilha de alguém e **não atribuída**. Precedência vermelho > verde > azul > amarelo. Aguardando a meta nunca fica vermelho.
- [x] Componentes `<x-closure.signal>` e `<x-closure.signal-legend>`; estilos em `sass/closure/_signals.scss`. Aplicado no **Despacho** (a **linha inteira** colorida, como nas outras atividades, + chip + legenda) e no **Controle** (e chip no **Histórico** da atividade).
- [x] Testes: `ClosureSignalTest` (7, matriz da regra), `ClosureDispatchSignalsTest` (8: cores por linha, controles desabilitados, pilha, seleção em massa, recusa no servidor, prévia, Controle).
- [x] **Ajustes pedidos depois (2026-10-25):** linha inteira colorida com **borda lateral, divisória inferior e zebra** para as linhas não se fundirem; **dropdowns dos filtros** deixam de ser cortados pelo card (`.filter-bar`, nos 4 cartões de filtro); **Buscar em massa** (`ClosureBulkSearch`, `WithBulkSearch`, `<x-closure.bulk-search>` e `-result`) no Despacho, Controle, Minha atividade, Disponíveis e Histórico, com resumo de não encontradas e presente na exportação. Teste: `ClosureBulkSearchTest` (9). Validado no navegador.
- [x] **Exportar do Painel analítico (2026-10-25):** Excel com uma aba por bloco da tela (Resumo, Volumetria, Por status, Matriz, Ordens ativas, Parceiras, Pendências por usuário, Obras críticas, Prioridades) e uma aba **por status** com as atividades detalhadas; visual moderno (cartões de KPI, linha colorida pelo sinal, barras de dados, 2 gráficos nativos, índice com links, zero como "–"). `ClosureDashboardReport` (dados) + `Exports/Closure/Dashboard/*` (visual) + `ExportClosureDashboardJob` (fila + aviso). Teste: `ClosureDashboardExportTest` (14). Validado na stack: toast → job → arquivo de 11 abas e 2 gráficos → notificação.
- [x] **Valor da obra e status limpo (2026-10-25):** coluna **Valor da obra (ADS)** em Despacho, Controle/Minha atividade, Disponíveis e Histórico (e no Excel das listas), via `ClosureInformValue` — ADS do informe resolvido pela mesma regra da empresa do informe (informe da Ordem → parcial → informe da Nota; ADS própria, senão a da Nota). Coluna de status mostra **só o status** (sem o selo repetido); a cor fica na linha, com o significado no tooltip da linha. Teste: `ClosureInformValueTest` (10). Validado nas telas reais.
- [ ] Filtro por sinalização (clicar na cor da legenda para filtrar) e contagem por cor — não pedido ainda; avaliar.
- [ ] Mostrar o sinal também na tela da obra (`Works\Show`) e no Detalhe da Ordem.

## 7. Fase 3 — Despachados e devolutivas

- [ ] `ClosureIssueWorkflowService`: abrir/enviar por área e tipo (`requestToMeasurement`, `requestToSupervision`, devolutiva Fiscalização→Encerramento e Encerramento→Fiscalização); `respond` por área sem exigir `Company`; origem `MEASUREMENT`/`SUPERVISION` nos eventos.
- [ ] Bloqueio opcional ao abrir (padrão: paralelo).
- [ ] `Partner/ClosureIssues/Index` e `visibleIssues()` filtram `destination_area = PARTNER` (**teste de regressão de vazamento**).
- [ ] `ClosureNotifier` para as novas áreas; chat da obra com papel por área.
- [ ] Testes: fluxo completo por área, isolamento entre áreas e parceira, usuário sem vínculo.

## 7b. Transferência entre usuários (2026-10-25)

> **Regra do usuário:** no Encerramento a transferência de atividade entre pessoas **não precisa de aprovação da gestão**: quem tem as Ordens envia a outro usuário do Encerramento; o destinatário **aceita ou rejeita**; quem enviou pode **cancelar** enquanto estiver pendente.

- [x] Tabelas novas `closure_transfer_requests` (de/para, status PENDING/ACCEPTED/REJECTED/CANCELLED, motivo, resposta) e `closure_transfer_request_items` (Ordens e resultado MOVED/SKIPPED por item) — migration `2026_10_25_100000`, criação protegida (`GuardedTableRecreator`), nada de outra atividade é tocado. Aplicada na stack.
- [x] `ClosureTransferService`: `send` (só o responsável, só status de trabalho, destinatário com `closure.work` e empresa, sem RI ativo, sem pedido pendente para a mesma Ordem), `accept` (só o destinatário; move pela transição `transfer` do workflow; Ordem que mudou de dono/status no meio é **pulada com o motivo**), `reject` (só o destinatário, motivo opcional), `cancel` (só quem enviou). Enquanto pendente a Ordem **não é travada**.
- [x] Avisos (`ClosureNotifier::transferChanged`): destinatário (enviada/cancelada) e remetente (aceita/rejeitada).
- [x] Telas: **Minha atividade** → Ações › *Transferir para outro usuário…* e botão *Transferir selecionadas* (modal `Transfers\SendModal`); **Transferências** (`services.transfers`, menu com contador): abas Recebidas / Enviadas / Histórico com Aceitar, Rejeitar (motivo opcional) e Cancelar (`Transfers\Center`); a mesma central aparece na tela *Transferência de Encerramento* do despacho.
- [x] A "transferência de obra" da gestão virou **Reatribuir uma obra (gestão)**: atalho direto, sem aceite (existe só para a gestão).
- [x] Testes: `ClosureTransferTest` (10).
- [ ] Decidir se a Ordem com transferência pendente deve mostrar um selo na lista.

## 7c. Lista Encerramento do Serviço = todas as Ordens (2026-10-25)

> **Regra do usuário:** a lista do Serviço mostra **todas** as Ordens elegíveis (não só as despachadas), mas ela **só existe** (aba/link e acesso) quando o **despacho** liberou a auto-atribuição; sem liberação nem o link aparece e o acesso direto é recusado (403). Com a liberação, é a aba padrão.

- [x] `Closure\Work\All` (aba **Todas as Ordens**, padrão do Serviço; *Minhas atividades* e *Atividades disponíveis* continuam como abas): usa a mesma consulta do Despacho (`ClosureDispatchListQuery::overview`), então enxerga Ordens sem posse; cartões Total / Sem posse / Minhas, busca, tipo, busca em massa, cor da linha e valor da obra; exporta Excel.
- [x] Regra de assumir (`assumability`, reavaliada no servidor a cada Ordem): precisa de `ClosureSettings::selfAssignEnabled()` **e** tipo em `selfAssignTypes()`; Ordem **sem posse** é despachada para o próprio usuário (`dispatchOrders`, exige empresa); Ordem **na pilha da empresa dele** é assumida (`claim`); Ordem com responsável ou na pilha de outra empresa nunca. Quando não pode, o checkbox fica desabilitado com o **motivo** no tooltip.
- [x] Testes: `ClosureWorkAllListTest` (8).
- [ ] O despacho libera/revoga em *Configurações do Encerramento* (já existia): considerar mostrar essa chave também na própria lista para a gestão.

## 8. Fase 4 — Acompanhamento por status usuário

> **Status: núcleo concluído em 2026-10-25** (mapa, tela de configuração e aba "Em acompanhamento", validados na tela real).

- [x] `ClosureStatusUserRouteService`: `map`, `prefixesFor`, `catalog` (prefixos reais das Ordens do Encerramento + contagem + dono) e `sync(área, prefixos, ator)` — substitui a seleção da área, normaliza para 2 tokens em maiúsculas, recusa prefixo de outra área, em transação.
- [x] **Tela de configuração** `Mapa de status por serviço` (`/encerramento/configuracoes/status-usuario`, gate `closure.manage`, menu "Gestão do ciclo"): um cartão por serviço (Medição, Fiscalização), busca, contagem de Ordens por status, status de outro serviço desabilitado com "Já em {serviço}", Salvar/Desfazer, aviso de conflito.
- [x] `ClosureInboxQuery::accompanying`: a caixa usa a **mesma base da lista do Despacho** (`ClosureDispatchListQuery`, todas as Ordens elegíveis, **despachadas ou não**) filtrada pelos status mapeados do serviço; sem mapa = caixa vazia, nunca "tudo"; prefixo não casa com palavra parecida; `accompanyingCounts` alimenta os cartões. (Correção de 2026-10-25: a 1ª versão olhava só `closure_order_states`, por isso mostrava 1 Ordem onde o Despacho tem 477 e 0 onde a Fiscalização tem 2.) A saída ao mudar o status segue o cache do conjunto elegível (até 5 min, como o Despacho). "No status desde" só existe para Ordem já despachada (o rastreio é por `closure_order_states`); as demais mostram "sem registro".
- [x] Aba "Em acompanhamento": só visão (aviso fixo), cartões por status como filtro, busca, tipo, busca em massa, "No status desde" (tempo + "desde o 1º registro"), exportação Excel (`ExportClosureListJob`).
- [x] Testes: `ClosureStatusMapInboxTest` (14): unicidade, sync, catálogo, saída automática ao mudar o status, prefixo parecido, acesso, tela, vazio.
- [ ] Rodar `closure:sync-status-user` periodicamente já está agendado; conferir em produção que o `sicode:upd_baseOrder` termina antes (a data do status tem a precisão desse sync).

## 9. Fase 5 — Telas (todas pelo padrão de UI)

- [x] **Caixa dentro da atividade** (2026-10-25): `CAIXA DO ENCERRAMENTO` no menu de Pagamento (Medição) e Fiscalização (serviço e despacho; gate `closure.inbox.{área}`; rotas `services.closure_inbox` / `dispatch.closure_inbox`; Desenho e demais → 404). `Closure\Inbox\Index`: abas **Em acompanhamento** (visão, pronta) e **Despachadas para {área}** (lista as pendências da área; ainda sem Responder).
- [ ] Caixa: painel lateral com linha do tempo, Responder, Devolver ao Encerramento (depende da Fase 3).
- [x] **Enviar RI ao Desenho** (modal listando as N Ordens, comentário e categoria obrigatórios) e **Cancelar RI** (gestão) no menu de Ações da Minha atividade/Controle; selo de trava em cada Ordem (`ClosureInternalReturnModalTest`).
- [x] Gestão da Obra (`Works\Show`): abas **Retorno Interno** (situação do RI, categoria, andamento no Desenho, comentários), **Informes** e **Arquivos** (2026-10-25).
- [ ] Gestão da Obra: seletor de destino (Parceira | Medição | Fiscalização) e aba **Recebidas**.
- [x] Selo de trava nas Ordens (`lock-badge`) e menu de Ações reduzido a "Obra travada" enquanto há RI ativo.
- [ ] Indicador de RI/trava na lista do Despacho.
- [ ] Validação no navegador (desktop e mobile), estados vazios, carregando e erro.

## 10. Fase 6 — Gestão e documentação

- [ ] Visão de volume e envelhecimento por serviço (faixas 0–3 / 4–7 / 8–15 / 15+ dias; lista dos mais antigos).
- [ ] (Opcional) Exportação Excel por Job + `SystemNotification`.
- [ ] (Opcional) Cor por tempo, desligada por padrão.
- [ ] Atualizar `GUIA_USUARIO_PENDENCIAS_ENCERRAMENTO.md` e criar guia das caixas e do RI.
- [ ] Rodar `teardown` da stack do worktree.

---

## 11. Log de andamento

> Uma linha por sessão de trabalho, mais recente no topo. Informe: data, fase, o que foi feito, o que falta/bloqueio.

| Data | Fase | Feito | Pendente / bloqueio |
|---|---|---|---|
| 2026-10-25 | Selos do menu | Dois selos no menu da caixa (Medição/Fiscalização): azul = em acompanhamento, vermelho = despachadas para resolver (`ClosureInboxCount`, atualiza a cada 5 min); cada link abre a aba certa (`?tab=dispatched`). | Fase 3. |
| 2026-10-25 | RI na tela | Menu de **Ações** reagrupado por intenção e **Enviar RI ao Desenho** (modal com as Ordens que travam) + **Cancelar RI** (gestão) + selo de trava por Ordem. Validado na tela real (modal sem enviar). | Fase 3. |
| 2026-10-25 | Tela do executor e Gestão da Obra | **Executor**: sem azul/amarelo nas linhas (só vermelho = sem baixa e verde = concluída; legenda `executor`), **sem “Devolver à pilha”** (servidor também recusa em `Work\Index::runAction`). **RI**: categoria/subcategoria (as do RI da Contratação, `categories`/`subcategories`) obrigatórias no modal e gravadas no Reclaim. **Gestão da Obra** (`Works\Show`): cartões-resumo, faixa de obra travada, abas **Retorno Interno** (estágio: Aguardando/Em tratamento/Retornado/Cancelado, andamento e comentários do Desenho), **Informes** (todos os WorkReport, valor da ADS, ADS tácita) e **Arquivos** (todos os da Nota, tipo/origem, download e ZIP; inclui ADS tácita para o Encerramento). `ClosureWorkDossier` (somente leitura). `ClosureWorkDossierTest` (7). | Destino do RI/aba Recebidas; Fase 3. |
| 2026-10-26 | Visual da pendência (parceira) | Detalhe da pendência com **andamento em 4 passos** (Pedido recebido → Seu retorno → Validação → Concluída), faixa **“é a sua vez / agora é com o Encerramento / resolvida”**, título pelo assunto, contador “N de M itens respondidos” e modo observador explícito. CSS em `_partner.scss`. | — |
| 2026-10-25 | Visual da Gestão da Obra | Cabeçalho novo (ícone, título, meta, ações, **barra de progresso** de Ordens concluídas, chips, faixa de RI) com as **abas dentro do cabeçalho** (aba ativa em destaque); conversa em balões, medidor de operações por Ordem, pendências em cartões. CSS em `resources/sass/closure/_work.scss` (escopo `.work-page`). | — |
| 2026-10-25 | Ajustes de UI | **Filtros que morriam** na lista até recarregar: causa = Livewire reescrevendo as opções geradas pelo Alpine após remover chip/limpar (reproduzido no navegador); corrigido com `wire:ignore` no multi-select. **Menu de Ações** reorganizado (grupos, ícones, flip e rolagem; não corta mais). **Filtros da caixa**: status, responsável, empresa e tempo no status (+ export). | Fase 3. |
| 2026-10-25 | Lista do Serviço | **Todas as Ordens** na Lista Encerramento do Serviço (1.099 na stack), com assumir condicionado à liberação do despacho (`Work\\All`, 8 testes). | Fase 3. |
| 2026-10-25 | Transferência entre usuários | Envio → aceite/rejeição → cancelamento sem gestão (service, modal, central, menu com contador, avisos, 10 testes). Mapa de status passou a listar **todos** os status do SAP e ganhou entrada pelo Despacho. Achado: nome de FK > 64 caracteres no MariaDB (1059) — constraints com nome curto. | Selo de transferência pendente na lista; Fase 3. |
| 2026-10-25 | Fase 4 + 5 (parcial) | **Mapa de status por serviço** (tela + service) e **Caixa do Encerramento** dentro de Pagamento/Fiscalização (abas Em acompanhamento e Despachadas; menu; exportação). Validado na stack: status reais JAND PPAG/JAND VIAB, Ordem aparece na caixa de Medição. Achado: `ClosureLegacySchemaUpgradeTest` recria o esquema antigo e não incluía as migrations da Fase 1, deixando o banco de teste sem colunas para testes posteriores — corrigido. | Fase 3 (despachar/responder/devolutivas), Gestão da Obra com destino, aba RI. |
| 2026-10-25 | Fase S (valor) | Coluna **Valor da obra (ADS)** (ADS do informe) nas listas de Despacho e Serviço e no Excel; status sem selo repetido. Achado: `collect($paginator)` devolve a estrutura do paginador (não os itens) — tratado em `informValues()`. Suíte do Encerramento + vizinhos: **369 verdes**. Validado nas telas reais (ex.: R$ 33.947,96). | Fase 3. |
| 2026-10-25 | Fase S (painel) | Exportar Excel do Painel analítico: abas por bloco + atividades por status, visual moderno, job + aviso. Achado: `FromArray` do Maatwebsite apaga o valor 0 sem `WithStrictNullComparison` (aparecia vazio) — corrigido e exibido como "–". Suíte do Encerramento + vizinhos: **363 verdes**. Validado fim a fim na stack (artefatos de teste removidos). | Fase 3. |
| 2026-10-25 | Fase S (ajustes) | Linha colorida com separação (borda lateral + divisória + zebra); dropdowns sem corte; busca em massa em 5 listas (exata por Nota/Ordem, resumo de não encontradas, vai na exportação). Suíte do Encerramento + vizinhos: **349 verdes**. Validado no navegador (menu vaza do card, linha azul com borda, busca em massa 3 de 4). | Fase 3. |
| 2026-10-25 | Fase S | Despacho único (servidor + tela + seleção em massa) e sinalização por cor com regra única (`ClosureSignal`), no Despacho, Controle e Histórico. Validado no navegador: as 2 Ordens já despachadas da tela ficaram azuis e travadas. Suíte do Encerramento + vizinhos: **340 verdes**. Teste intermitente observado em `ClosureDispatchModalTest` (dados aleatórios de fábrica), sem relação. | Fase 3; filtro por sinal (opcional). |
| 2026-10-25 | Fase P | Isolamento de visão por rota (SUPERADM observa como uma empresa, somente leitura; parceira pura redirecionada da rota interna); tela de pendência com retorno rico (por exigência + evidências); sinalizador no menu da parceira; histórico de retornos; exportação (lista, histórico e detalhe). **Falha corrigida:** observador via união de empresas vazava pedido de outra empresa. Suíte do Encerramento + vizinhos: 365 verdes; 5 falhas **pré-existentes** em `PartnerAccessGateTest` (formulário de usuário do parceiro: "company id field is required"), idênticas com o menu original. Pint limpo. | Validar no navegador; exportação nas telas internas; Fase 3. |
| 2026-10-23 | Fase 2 | `ClosureInternalReturnService`, guard de trava no `transition()` com lista branca, observer do `Reclaim` + varredura a cada 15 min, avisos, contrato documentado e matriz de testes (18). Despacho recusa Nota travada. Suíte `tests/Feature/Closure` + Reclaim/permissões: **294 testes verdes**; Pint limpo. **Decisão revisada:** cancelar RI só destrava (não fecha o Reclaim) para não alterar dado de outra atividade (viabilidade fica em "Criado RI"). Achados: o `Reclaim` é concluído por vários caminhos (comando em massa sem observer → varredura); `Container::bind` em serviço já resolvido executa a closure (usar `instance()` em testes); notificador enfileira em `jobs` (usar `Notification::fake()`). | Fase 3 (despachados e devolutivas). |
| 2026-10-23 | Fase 1 | 4 migrations (2 aditivas, 2 tabelas novas recriáveis) + `GuardedTableRecreator`; models; `ClosureArea`; gates das caixas; `ClosureStatusUserTracker` + comando + agendamento. Pint só nos arquivos tocados. Suíte `tests/Feature/Closure` + permissões: **270 testes verdes**. Achados: `statusUser` só é gravado por `sicode:upd_baseOrder`; MariaDB não aceita texto em coluna gerada (usamos `closed_at`); `phpunit.xml` não sobrescreve o `DB_DATABASE` do container (usar `-e DB_DATABASE=sicode_teste`). | Aplicar migrations no banco da stack; Fase 2 (RI). |
| 2026-10-23 | Fase 0 (núcleo) | CSS migrado para `resources/sass/closure/` (equivalência verificada regra a regra); JS compartilhado em `resources/js/closure/`; 11 componentes `<x-closure.*>` + testes; validado no navegador. Suíte `tests/Feature/Closure` verde (239 testes, linha de base 238 + 1 ajustado). | JS inline restante e remoção dos stubs; iniciar Fase 1. |
| 2026-10-23 | Planejamento | Escopo, regras de migration, contrato do RI e padrão de UI definidos e documentados. | Iniciar Fase 0 e Fase 1. |

## 12. Pontos em aberto

- [x] Onde `orders.statusUser` é gravado hoje: resolvido (§5).
- [ ] Worktree com ~72 arquivos modificados do trabalho em andamento do usuário (calendário de depósito etc.): ao commitar, separar o que é desta entrega (ver o log) do restante.
- [ ] Confirmar com o usuário: cancelar RI é só gestão e fecha o `Reclaim`; reabertura pelo Desenho avisa e não re-trava (assumidos como padrão).
