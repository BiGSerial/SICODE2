# Fase 2 — Atividade de Produção de Encerramento

> Execução do replanejamento de 2026-10-06. Complementa o [planejamento principal](PLANEJAMENTO_CONTROLE_OPERACIONAL_ENCERRAMENTO.md#24-replanejamento-atividade-de-produção-especializada-2026-10-06). A Fase 1 já está concluída; este documento não autoriza alteração da regra de Meta/Passivo.

## Resultado esperado

O Encerramento passa a ser uma atividade de Produção despachável para a parceira e atribuível a usuários habilitados. Cada Ordem continua tendo seu próprio controle operacional, suas pendências e sua linha do tempo. A tela gerencial deve responder, para qualquer Ordem: situação SAP, situação operacional, quem detém a próxima ação, desde quando, motivo/categoria da pendência e histórico das transferências.

## Princípios de execução

- Criar o serviço `Encerramento` (`folder=encerramento`) uma única vez por seed/migration idempotente; não depender do nome exibido para regra de negócio.
- Registrar o serviço no contrato e explicitamente em `service_users`; possuir permissão de atividade não concede automaticamente despacho.
- Criar e atualizar `Production` somente por `ClosureDispatchService`. O `DispatchWorkflowService` atual não deve ser chamado diretamente nesta atividade, pois ele não cria nem bloqueia os itens por Ordem selecionados.
- A Production é o envelope de despacho por Nota; `closure_order_states` é a unidade operacional por Ordem. Nunca gravar um único `productions.status` como substituto do estado de todas as Ordens.
- Toda mudança de posse/status abre ou fecha período e grava evento imutável na mesma transação.
- A fonte SAP é somente leitura. O encerramento definitivo continua sendo `statusSist LIKE 'ENTE%' OR 'ENCE%'`.

## 2A — Fundar a atividade e os acessos — concluída no código em 2026-10-06

O serviço técnico, gates, catálogo parceiro e navegação interna especializada foram entregues. A vinculação operacional a contratos e usuários permanece uma configuração administrativa deliberada: não foi atribuída automaticamente a nenhuma empresa ou pessoa.

### Escopo

1. Criar o serviço Encerramento, seu ícone e as associações de contrato necessárias para o ambiente de validação.
2. Reutilizar `service_users` para atividade (`service=true`) e despacho (`dispatch=true`), mantendo `closure_operator` e `closure_manager` como gates de domínio.
3. Definir gates: `closure.dispatch`, `closure.work`, `closure.manage` e `closure.view`; separar leitura gerencial, execução e despacho.
4. Criar menu/rotas especializadas para `services/encerramento` e `dispatch/encerramento`; não encaminhar para `Dispatchs/Default`.
5. Adicionar grupo parceiro `closure` ao `PartnerPermissionCatalog`, inicialmente com `closure.issues.list`, `closure.issues.respond`, `closure.issues.history` e `closure.issues.view_files`.

### Aceite

- Um usuário sem o serviço/gate recebe 403; um usuário habilitado vê apenas a atividade compatível.
- Administrador consegue conceder serviço e despacho separadamente ao usuário.
- A parceira só vê a nova área ao receber a permissão do catálogo.

### Validação executada

- `ProvisionClosureServiceMigrationTest`, `ClosureAuthorizationGatesTest`, `ClosureSpecializedNavigationTest` e `PartnerPermissionCatalogTest`: **10 testes, 54 asserções**.
- `php artisan view:cache` e `./vendor/bin/pint --dirty` executados na stack efêmera do worktree.
- O GET HTTP positivo das telas compartilhadas não é usado como prova de renderização em teste porque o layout legado tenta carregar `Status\\Bancosicode` sem conexão configurada e gera 500; essa falha preexistente foi isolada. As rotas são verificadas por action/middleware, os componentes por Livewire e os acessos negados por HTTP 403.

## 2B — Despacho e criação atômica dos itens — concluída no código em 2026-10-06

### Escopo

1. Criar `closure_order_states`, `closure_order_events` e `closure_order_state_periods` com os índices por `order_id`, `production_id`, estado, detentor e início do período.
2. Implementar `ClosureDispatchService`: seleciona uma Nota e suas Ordens ativas que possuam `closure_target`; cria/reaproveita a Production de Encerramento, despacha à empresa/pilha/usuário e vincula cada Ordem selecionada a seu item de trabalho.
3. Aplicar `DB::transaction()` e `lockForUpdate()` na Nota, Production reaproveitada e Ordens. Recusar Ordem cancelada, SAP já encerrada, sem meta ou já vinculada a uma Production aberta incompatível.
4. Registrar `DISPATCHED`, `ASSIGNED` e `ITEM_ADDED_TO_PRODUCTION`, incluindo origem, antes/depois, ator e dados do destino.
5. Criar telas de despacho com busca por Nota/Ordem, filtros Meta/Passivo e prévia de elegibilidade.

### Aceite

- Duas requisições concorrentes não produzem itens duplicados por Ordem.
- Repetir o despacho da mesma seleção é idempotente ou retorna o motivo preciso, sem criar uma segunda Production aberta conflitante.
- A linha de timeline da Nota informa o despacho agregado; o detalhe da Ordem mostra o evento granular.

### Entrega e validação executada

- A tela **Despacho de Encerramento** permite buscar por Nota/Ordem, filtrar Meta/Passivo, escolher empresa e usuário opcional, selecionar Ordens de uma única Nota/OV, conferir a prévia e confirmar o envio.
- `ClosureDispatchService` bloqueia Nota, Ordens, Production e estados na mesma transação (com até três tentativas). Ele recusa Nota/Ordem cancelada, SAP encerrado, Ordem sem `closure_target`, usuário fora da empresa de destino e destino incompatível.
- A repetição da mesma seleção para o mesmo destino é idempotente: não cria nova Production, estado, evento, período ou timeline.
- `closure_order_states`, `closure_order_events` e `closure_order_state_periods` preservam estado atual, eventos imutáveis e início da posse por Ordem. A duração e as transições completas serão exibidas e movimentadas na 2C.
- Validação serial: `ClosureOrderOperationalStateSchemaTest` (**3 testes, 15 asserções**), `ClosureDispatchServiceTest` (**6 testes, 45 asserções**) e testes de rota/gate especializados (**6 testes, 32 asserções**); `php artisan view:cache`, Pint e `git diff --check` aprovados.
- Consulte o [Guia do usuário — Despacho de Encerramento](GUIA_USUARIO_DESPACHO_ENCERRAMENTO.md) para o passo a passo operacional e os limites da fase.

## 2C — Tratativa, posse e massa — concluída no código em 2026-10-07

### Escopo

1. Implementar os status operacionais e suas transições no `ClosureOrderWorkflowService`.
2. Preencher e fechar `closure_order_state_periods` para cada mudança de estado, usuário, empresa ou localização.
3. Criar Minha atividade, Pilha da parceira e detalhe de Ordem com badges separados “SAP” e “Tratativa”.
4. Implementar ações em massa com seleção, prévia, confirmação, teto inicial de 100, `batch_id` e recibo de sucessos/recusas.
5. Permitir: assumir, atribuir, transferir, devolver à pilha, iniciar, aguardar retorno e ajustar prioridade. Exigir motivo em transferências forçadas e alterações que removam um responsável.

### Aceite

- O gerencial aponta corretamente o detentor atual e a duração de sua posse, mesmo após várias transferências.
- Um lote parcial não silencia erros: cada Ordem recebe resultado e evento próprios.
- Nenhuma ação em massa altera `statusSist`, `operations.status` ou elimina eventos.

### Entrega e validação executada

- `ClosureOrderWorkflowService` centraliza assumir, atribuir, transferir, devolver à pilha, iniciar, aguardar retorno e prioridade. Cada mudança bloqueia o item, grava antes/depois, fecha o período anterior quando há mudança de posse/status e abre o novo período na mesma transação.
- `ClosureOrderBatchWorkflowService` limita o lote a 100 itens, cria `batch_id`, apresenta a prévia sem gravar e executa item a item. O recibo devolve cada sucesso e cada recusa; uma recusa não desfaz os demais itens.
- **Minha Atividade** mostra SAP e Tratativa em campos distintos, posse e tempo decorrido. Operador/parceira vê somente sua fila ou pilha da empresa; gestor vê a carteira e recebe controles de atribuição, transferência e prioridade.
- O detalhe da Ordem mostra detentor, desde quando, eventos e períodos apenas ao detentor, à pilha responsável ou ao gestor; o SAP continua somente leitura.
- Consulte o [Guia do usuário — Tratativa de Encerramento](GUIA_USUARIO_TRATATIVA_ENCERRAMENTO.md) para os passos, motivos obrigatórios, prévia e recibo.
- Validação serial: `ClosureOrderWorkflowServiceTest` (**4 testes, 37 asserções**), `ClosureOrderBatchWorkflowServiceTest` (**3 testes, 22 asserções**) e navegação/autorização (**6 testes, 33 asserções**); `php artisan view:cache`, Pint e `git diff --check` aprovados.

## 2D — Pendências classificadas e portal parceiro — em andamento desde 2026-10-09

### Escopo

1. Criar categoria e subcategoria administráveis, com inativação sem apagar valores já usados.
2. Criar `closure_issues` e `closure_issue_events`; exigir categoria e subcategoria compatível, destino e descrição quando a subcategoria exigir detalhamento.
3. Implementar fluxo abrir → solicitar → responder → validar/devolver → resolver, com comentários e evidências pelos serviços de arquivo existentes.
4. Criar caixa da parceira limitada a pendências de sua empresa; resposta, comentário e evidência entram como evento com ator parceiro.
5. Atualizar automaticamente o estado/local da Ordem conforme existir pendência bloqueadora, preservando a causa explícita.

### Aceite

- Filtros por categoria e subcategoria retornam também pendências históricas, inclusive categorias inativas.
- A parceira não acessa Ordem/pedência de outra empresa e não pode alterar posse interna nem estado SAP.
- Devolução para correção incrementa o ciclo da mesma pendência; não cria histórico fragmentado.

### Entrega parcial — núcleo de domínio em 2026-10-09

- Criadas as tabelas de categorias, subcategorias, pendências e eventos. Categoria e subcategoria são inativáveis; registros históricos continuam ligados à taxonomia original, inclusive depois da inativação.
- `ClosureIssueWorkflowService` valida a taxonomia, descrição obrigatória, destino e transições. O fluxo já preserva o mesmo identificador da pendência ao devolvê-la para correção, incrementando o seu ciclo.
- Uma pendência bloqueadora move somente a **tratativa** da Ordem para `WAITING_RETURN`, registrando `ISSUE_BLOCKED`; ao resolver a última pendência bloqueadora, a Ordem retorna ao seu ponto operacional anterior com `ISSUE_RELEASED`. Nenhum método altera `statusSist` ou `operations.status`.
- Eventos de pendência são imutáveis e registram ator, origem interna/parceira, estado anterior e posterior e mensagem quando fornecida.
- A caixa parceira foi implementada em `/partner/encerramento/pendencias`: lista somente pendências destinadas às empresas visíveis, filtra por Nota/Ordem, situação e categoria — incluindo categoria histórica inativa — e permite responder somente quando o usuário possui `closure.issues.respond`. A resposta é registrada como evento de origem `PARTNER` e não expõe controles de posse ou SAP.
- Anexos/evidências permanecem pendentes: o uploader existente ainda é específico de cancelamentos e será adaptado sem reutilização insegura nesta fase.

Consulte o [Guia do usuário — Pendências de Encerramento](GUIA_USUARIO_PENDENCIAS_ENCERRAMENTO.md) para o comportamento já definido e os limites temporários da entrega.

## 2E — Gestão, tempos e exportação

### Escopo

1. Central gerencial com Meta/Passivo já existentes acrescidos de situação operacional, posse, categoria/subcategoria, empresa, usuário e aging.
2. Indicadores: volume por estado, backlog por categoria, tempo em posse, tempo por etapa, tempo de resposta da parceira, reaberturas/devoluções e itens sem movimentação.
3. Visões “onde está parado?” e “por que está parado?”, com drill-down até a Ordem e seu período atual.
4. Exportações por job, usando o padrão de notificações e exports do projeto para cargas grandes.
5. Centralizar avisos em `SystemNotification`, com destino interno obrigatório para a Ordem, pendência ou lista de Minha atividade correspondente. Reutilizar `Notifys`/`AllNotifies` para sino, contador e central; acrescentar badges de volume no menu ATIVIDADES e na sidebar do Encerramento. Usar UUID determinístico por evento/destinatário para impedir duplicidade em retries.
6. Exibir toast imediato para eventos de ação: despacho/atribuição, transferência, solicitação à parceira, resposta recebida, devolução, lembrete e confirmação SAP. O toast terá ação “Abrir” com o mesmo deep link da central; desaparecer não marca o aviso como lido.

### Aceite

- Todo número do painel é navegável até a lista filtrada que o compõe.
- Tempo corrido, tempo em posse e tempo de ação são rotulados de forma distinta; o sistema não apresenta `users.last_seen_at` como produtividade.
- Abrir a notificação ou usar “Abrir” no toast leva ao item certo e só então marca o aviso como lido. Usuário sem acesso ao item não recebe o aviso.

## 2F — Automação e encerramento técnico

### Escopo

1. Implementar comando/job após a sincronização SAP para detectar transições `statusSist` e cancelamento.
2. Fechar somente o item da Ordem por confirmação SAP; fechar a Production apenas quando todos os itens vinculados forem terminais.
3. Publicar na central e por toast a confirmação de encerramento para os envolvidos, conforme as regras de visibilidade da atividade.
4. Agendar congelamento mensal conforme a regra já implantada, sem misturá-lo ao despacho.

### Aceite

- Reexecutar o job não duplica evento de fechamento.
- A conclusão SAP, o usuário/rotina que a registrou e os períodos anteriores ficam consultáveis.
- Uma Ordem fechada nunca é reaberta por tela; qualquer exceção futura exige novo requisito e trilha própria.

## Sequência técnica e validação

1. Aprovar nomenclatura do serviço e a matriz inicial de categorias/subcategorias.
2. Implementar 2A e testar permissões com usuário interno e parceiro.
3. Implementar 2B com testes de concorrência e idempotência antes de abrir a tela a usuários.
4. Implementar 2C e validar com uma carteira pequena, incluindo lote e transferências.
5. Implementar 2D, validar a persistência e homologar o retorno da parceira.
6. Construir 2E com dados reais de 2B–2D; só então ativar 2F em modo observação antes de fechamento automático.

## Arquivos previstos

- Novos: migrations e models `ClosureOrderState`, `ClosureOrderEvent`, `ClosureOrderStatePeriod`, `ClosureIssueCategory`, `ClosureIssueSubcategory`, `ClosureIssue`, `ClosureIssueEvent`.
- Novos: `App\Services\Closure\ClosureDispatchService`, `ClosureOrderWorkflowService`, `ClosureIssueWorkflowService`, `ClosureIndicatorsService` e comando de detecção SAP.
- Alterados: `Order`, `Production`, `Service`, `AuthServiceProvider`, `PartnerPermissionCatalog`, `routes/web.php`, menu de atividades e telas de administração de usuário/contrato.
- Novos: componentes Livewire dedicados em `Closure\Dispatch`, `Closure\Work`, `Closure\Issues`, `Closure\Management` e telas Partner de Encerramento.
- Testes: feature tests para autorização, despacho, concorrência, transições, períodos, lote, taxonomia, isolamento parceiro, idempotência do job e indicadores.
