# Guia do usuário — Despacho de Encerramento

Para quem tem a permissão de despacho (gestor/despachante). O despacho trabalha por **Ordem**, não por Nota: você pode marcar Ordens de várias Notas de uma só vez.

## Acesso

Menu **ATIVIDADES > PÓS OBRA > DESPACHO > ENCERRAMENTO**. Na barra lateral: **LISTA PARA ENCERRAMENTO** (despachar e configurações), **CONTROLE DE ENCERRAMENTO** (o que já foi despachado: filtre por tipo, status, empresa, responsável e prioridade, e atribua, transfira ou priorize em lote), **TRANSFERÊNCIA DE ENCERRAMENTO** (pedidos de prioridade pendentes e transferência de responsabilidade por obra; o número vermelho mostra os pedidos pendentes) e **PAINEL ANALÍTICO**. Gestores veem ainda **GESTÃO DO CICLO** (Visão geral, Meta, Passivo).

## Tipos de Ordem: P, M e F

O tipo é calculado pela data de fim real da operação 0020 confirmada, em Ordens liberadas e ainda não encerradas.

| Tipo | Cor | Significa |
|---|---|---|
| **M** Meta atual | verde | fim real no mês anterior ao atual |
| **P** Passivo | vermelho | fim real antes do mês anterior e ainda não encerrada |
| **F** Meta futura | azul | fim real no mês atual |

O tipo muda sozinho enquanto a Ordem está aberta (F vira M na virada do mês; M vira P se não encerrar). Ao encerrar no SAP, o tipo **congela** e passa a medir se foi feito na meta ou fora dela.

## Como despachar

A lista de Despacho tem busca, filtros (Tipo P/M/F, **Região, Regional, Município, Status usuário**, empresa, **competência**, status operacional, responsável e "só não despachadas") e cards-resumo clicáveis (Total, P, M, F, Não despachadas) que também filtram.

**Empresa (informe).** A coluna e o filtro **Empresa** mostram a empresa da relação com o **informe** da obra (a empreiteira que informou), a mesma da coluna EMPRESA da Lista para Medição. Regra por Ordem: o informe não cancelado ligado à Ordem (se houver vários, o mais recente); sem informe, a empresa da parcial da Ordem; sem parcial, o informe não cancelado da Nota. Ordens sem nenhum dos três mostram "—". Quem está com a Ordem agora (empresa do despacho) aparece ao passar o mouse sobre a célula. O resultado é guardado em cache por alguns minutos (mesmo TTL do conjunto de Ordens).

**Paginação.** A lista não tem janela de rolagem própria: a página rola normalmente e a rolagem horizontal só aparece em telas estreitas. O seletor **Itens por página** (10, 25, 50 ou 100) fica no cabeçalho do card e no rodapé, junto de "Exibindo X–Y de N" e dos links de página. A seleção de Ordens vale entre páginas. O Controle de Encerramento tem o mesmo seletor e rodapé.

1. Filtre a lista e marque as Ordens (pode ser de Notas diferentes). Use **Selecionar página** ou **Selecionar todas filtradas** (limite de 1.000 por vez); a seleção vale entre páginas.
2. Com ao menos uma Ordem marcada, o cabeçalho do card **Ordens** mostra o botão **Despachar selecionadas (N)** e um discreto **Limpar seleção** (não há barra flutuante). Para uma Ordem só, use o ícone de avião de papel na linha.
3. O **modal abre na hora**, sem esperar o servidor: o resumo vem dos dados já exibidos na lista e as empresas/usuários já estão na página. Confira o resumo (contagem por tipo e por Nota) e a lista de Ordens. Escolha a **empresa de destino** e, se quiser, um **usuário**; sem usuário, a Ordem vai para a pilha da empresa. Só aparecem empresas e usuários habilitados no serviço Encerramento. A empresa do informe já vem **pré-selecionada** quando todas as Ordens escolhidas têm a mesma (e ela está habilitada no serviço); se divergirem ou faltar informe, o modal avisa e você escolhe manualmente.
4. A prévia de elegibilidade é buscada logo após a abertura (a coluna Situação mostra uma animação até chegar) e refeita ao trocar empresa/usuário; **Confirmar despacho** só habilita quando a prévia chegou. Por Ordem: as recusadas ficam destacadas com o motivo (cancelada, já encerrada no SAP, fora da meta ou **já despachada**). Se houver recusadas, marque **Despachar só as elegíveis** para liberar o botão.
5. Clique em **Confirmar despacho**. O servidor revalida tudo (permissão, empresa, usuário e elegibilidade). O botão mostra o andamento e o modal passa a exibir o **recibo por Ordem**: despachada ou recusada (com o motivo). Feche o modal (botão Fechar, Esc ou clique fora).

As recusadas continuam marcadas na lista para você corrigir e tentar de novo. A situação é conferida de novo na confirmação.

Se a Ordem foi para um usuário, ele recebe aviso no sino (uma vez por Ordem) e você vê um toast ao concluir.

### Valor da obra (ADS) e status

Nas listas do **Despacho**, do **Controle**, da **Minha atividade**, das **Atividades disponíveis** e do **Histórico** há a coluna **Valor da obra (ADS)**: o **valor da ADS do informe** a que a Ordem pertence (o mesmo "Valor ADS" da Lista para Medição). O informe da Ordem segue a mesma regra da coluna **Empresa**: o informe não cancelado ligado à Ordem; sem ele, a **parcial** da Ordem (vale o valor da parcial); sem ela, o informe não cancelado da **Nota**. Se o informe não tem ADS próprio, vale a ADS da Nota. Sem informe ou sem valor, aparece "—". O valor também sai na coluna **Valor da obra (ADS) R$** do Excel exportado.

A coluna **Status** mostra **apenas o status da atividade** (Atribuída, Em andamento, Aguardando Parceira…). A sinalização por cor fica na **linha inteira** e na legenda; passe o mouse sobre a linha para ler o significado da cor.

### Buscar em massa

Ao lado da busca (Despacho, Controle, Minha atividade, Atividades disponíveis e Histórico) há o botão **Buscar em massa**. Cole uma lista de **Notas e/ou Ordens** — uma por linha, ou separadas por vírgula, ponto e vírgula ou espaço — e clique em **Buscar**. A lista passa a mostrar só o que casa **exatamente** com o número da Nota **ou** da Ordem (repetidos são ignorados; até 2.000 por vez). O contador no modal mostra quantos termos foram detectados.

Abaixo dos filtros aparece o resumo: **"X de N encontradas com os filtros atuais"**. Se algo não casou, abra **"não encontradas"** e use **Copiar não encontradas** para levar a lista. **Editar lista** reabre o modal com os termos; **Limpar** remove a busca (também some com **Limpar filtros**). A busca em massa combina com os demais filtros e vai junto na **exportação** em Excel. No Despacho, **Selecionar todas filtradas** alcança só as Ordens da lista colada que ainda podem ser despachadas.

### Uma Ordem só é despachada uma vez

Ordem que **já tem posse** (está com um responsável ou na pilha de uma empresa) **não pode ser despachada de novo**, nem para a mesma empresa e responsável. Na lista ela aparece esmaecida, com o **checkbox e o botão de despachar desabilitados**; passe o mouse para ver com quem está ("Já despachada: está com Fulano" ou "a pilha de Empresa X"). **Selecionar página** e **Selecionar todas filtradas** só pegam Ordens ainda não despachadas, e o botão mostra quantas são. Para mudar o responsável de uma Ordem já despachada, use a **Transferência de Encerramento** (ou o Controle). O servidor recusa o despacho mesmo que a tela seja burlada.

### Cores das Ordens (sinalização)

Cada Ordem despachada tem a **linha inteira colorida** (como nas outras atividades; sem selo repetido na coluna de status), com a mesma regra no Despacho, no Controle e no histórico. Para as linhas não se confundirem quando várias seguidas têm a mesma cor, cada uma tem uma **borda lateral** na cor do sinal, uma **divisória** mais escura embaixo e uma **alternância sutil** de tom. A legenda fica acima da tabela:

| Cor | Significa |
|---|---|
| 🔵 Azul — **Atribuída** | Atribuída a um responsável e em tratativa |
| 🟢 Verde — **Concluída** | Finalizada pelo responsável, aguardando a meta, ou já encerrada no SAP |
| 🔴 Vermelho — **Sem baixa no SAP** | Finalizada há **mais de 24h** e o SAP ainda não encerrou a Ordem (status ENTE/ENCE): precisa de atenção |
| 🟡 Amarelo — **Na pilha, sem atribuição** | Está na pilha de alguém (empresa ou usuário) e ainda não foi atribuída a um responsável |

Quando mais de uma vale, vence a mais grave: vermelho, depois verde, azul e amarelo. Ordem não despachada não tem cor. "Aguardando a meta" nunca fica vermelha (a espera é esperada).

### Filtros de Região, Regional, Município e Status usuário

O card de filtros tem a busca (Nota/Ordem), os botões P/M/F, "Só não despachadas" e "Limpar filtros" na primeira linha, e uma grade de filtros logo abaixo (em telas pequenas, abra com **Mais filtros**). Região, Regional, Município e Status usuário são de **seleção múltipla** (o menu abre por cima do card, sem ser cortado): abra o menu, use a busca, **Selecionar todos** ou **Limpar**, marque as opções e feche o menu (ou clique em **Aplicar**) para filtrar. O botão mostra a quantidade marcada, ex.: "Município (3)". A barra **Filtros ativos** lista cada filtro como chip removível (x) e mostra o total de Ordens do resultado.

- **Região e Regional** (tabela de municípios, colunas `regiao` e `regional`) funcionam como pré-filtro: ao marcar uma ou mais, o menu de **Município** passa a listar só os municípios delas, e a Regional também acompanha a Região. Marcações que saem do escopo são desmarcadas sozinhas.
- **Município**: sem nenhum marcado, vale **todos** os municípios do que já foi filtrado por Região/Regional (ou todos, sem Região/Regional); com 1 ou mais marcados, só esses. O município da Ordem é o da sua Nota.
- As opções e a contagem entre parênteses vêm apenas das Ordens do conjunto elegível.
- **Status usuário**: o status da Ordem tem vários códigos (ex.: `JAND INVE BLOQ`); o filtro mostra só os **dois primeiros** (`JAND INVE`), sem repetição e com a contagem. Marcar `JAND INVE` traz as Ordens que **começam** com esse par (`JAND INVE` e `JAND INVE BLOQ`, mas não `JAND INVEX`); vários marcados somam (OU).
- Os filtros ficam no endereço da página (Região `regions`, Regional `regionals`, Município `cities`, Status usuário `su`), então o link pode ser compartilhado. As listas de opções são guardadas em cache por alguns minutos, como o conjunto de Ordens.

### Coluna e filtro de Competência

A coluna **Competência** (`MM/AAAA`, ex.: 10/2026) é o mês da meta em que a Ordem entra: o ciclo do alvo (target) quando existir; senão o mês seguinte ao do fim real da operação 0020 (Meta futura: fim real no mês corrente, entra no próximo mês; Meta atual: fim real no mês anterior, entra no mês corrente; Passivo: competências anteriores); para Ordem encerrada, a competência congelada no encerramento. Clique no título para ordenar (de novo para inverter). O filtro **Competência** lista as competências presentes, com a contagem de Ordens. A coluna também aparece no modal e nas listas do Controle, Minha atividade e Atividades disponíveis.

## Configurações

Abra **Configurações** na tela de Despacho:

- **Permitir concluir obras de Meta Futura**: quando ligado, o usuário pode finalizar Ordens F e elas ficam em **Aguardando** até a meta chegar. Desligado, Ordens F não podem ser finalizadas.
- **Habilitar lista de auto-atribuição**: libera a tela **Atividades disponíveis** para os usuários pegarem Ordens.
- **Tipos liberados** (P, M, F): quais tipos podem ser pegos na auto-atribuição.

As mudanças valem em cerca de 1 minuto.

## Painel analítico

Em **PAINEL ANALÍTICO**:

- **Matriz de volumetria** usuário × tipo × status. Clique numa célula para ver as Ordens.
- **Gráficos** por usuário e por status.
- **Interação com parceiras**: pendências abertas e tempo de resposta, por parceira e por usuário.
- **Pedidos de prioridade**: aceite ou recuse (com observação). Aceitar marca as Ordens abertas da obra como prioritárias; quem pediu é avisado.
- **Obras críticas** e botão para priorizar uma obra.
- **Transferir responsabilidade**: escolha a obra, o destino e o motivo; o painel mostra um recibo.

### Exportar o painel (Excel)

O botão **Exportar Excel** no topo do painel gera, em segundo plano, uma planilha com **uma aba por bloco da tela** e, em separado, as **atividades detalhadas por status**. Você é avisado no sino quando ela estiver pronta (com o link). O arquivo respeita os **filtros** que estavam no painel (tipo, empresa, período e "parada há (dias)"). Diferente da tela, ele traz **todas** as Ordens ativas, e não só as 50 primeiras. Só o despachante exporta.

| Aba | O que traz |
|---|---|
| **Resumo** | Indicadores em cartões (estoque ativo, atividades por situação e a **sinalização por cor**: sem baixa no SAP, concluídas, atribuídas, na pilha), filtros aplicados e um **índice de abas com link** |
| **Volumetria** | Ordens por usuário × tipo (P/M/F), total, % e **gráfico de barras empilhadas** |
| **Por status** | Distribuição do estoque por situação e **gráfico de rosca** |
| **Matriz** | Usuário × tipo × status (cada célula da matriz da tela), com totais por tipo e total geral |
| **Ordens ativas** | Todas as Ordens ativas, com a **linha colorida pelo sinal** (azul atribuída · verde concluída · vermelho +24h sem baixa no SAP · amarelo na pilha sem atribuição), responsável, empresa, desde quando e dias no status |
| **Atividades · {status}** | Uma aba **para cada status que tem Ordens** (Em andamento, Atribuída, Pausa, Aguard. parceira, Dep. bloqueado, Dep. liberado, Finalizado, Aguardando meta, Disponível), da mais antiga para a mais recente |
| **Parceiras** | Pendências abertas, aguardando resposta, espera média e maior espera (em dias), devoluções e resposta média |
| **Pendências por usuário** | Pendências enviadas por usuário e resposta média da parceira |
| **Obras críticas** | Obras com Ordens P paradas ou pendência parada, com o motivo |
| **Prioridades** | Pedidos de prioridade sem decisão |

Visual: faixa de título e descrição em cada aba, cabeçalho escuro congelado com filtro, **barras de dados** nas colunas de volume, zeros como "–", cor na aba (verde-água = volume, azul = status, amarelo = parceiras, vermelho = alertas) e as mesmas cores da tela.

## Importante

O despacho nunca altera o status no SAP. Cada Ordem encerrada ou cancelada no SAP é detectada automaticamente várias vezes ao dia.

## Transferir atividade para outro usuário

Não precisa de aprovação da gestão. Em **Minha atividade**, abra **Ações › Transferir para outro usuário…** (ou marque várias Ordens e use **Transferir selecionadas**), escolha quem vai receber (precisa ser usuário do Encerramento) e informe o motivo.

- **Quem recebe** vê em **Transferências › Recebidas** (e no contador do menu) e **aceita** ou **rejeita** (com motivo opcional). Ao aceitar, as Ordens passam a ele; ao rejeitar, continuam com você.
- **Quem enviou** acompanha em **Enviadas** e pode **cancelar** enquanto estiver pendente.
- Enquanto pendente, a Ordem continua com você. Se ela mudar de responsável ou de status antes do aceite, ela é pulada e o motivo aparece no histórico.
- A gestão ainda tem **Reatribuir uma obra** na tela *Transferência de Encerramento*, que move direto, sem aceite.

## Lista Encerramento no Serviço (todas as Ordens)

No Serviço, **Lista Encerramento › Todas as Ordens** mostra todas as Ordens do Encerramento, despachadas ou não (cartões *Total*, *Sem posse* e *Minhas* filtram). Para **assumir** uma Ordem:

- A aba **Todas as Ordens** só aparece quando o **despacho liberou a auto-atribuição** em *Configurações do Encerramento*. Sem a liberação, a aba e o link não existem e abrir direto é recusado: o Serviço mostra só *Minhas atividades*.
- Com a liberação, o tipo P/M/F da Ordem também precisa estar liberado; nas Ordens de tipo não liberado o checkbox fica desabilitado, com o motivo no tooltip.
- Ordem **sem posse** é despachada para você; Ordem **na pilha da sua empresa** é assumida; Ordem com outro responsável ou na pilha de outra empresa não pode ser assumida.
- As assumidas aparecem em **Minhas atividades**.

## Menu de Ações da Ordem e devolução por RI

O menu **Ações** agrupa por intenção:

- **Trabalhar a Ordem:** Iniciar, Finalizar.
- **Alterar o status:** Voltar a Em andamento, Pausa, Depósito Bloqueado/Liberado.
- **Devolver / pedir correção:** *Enviar à parceira…* (pendência para a parceira responder), *Enviar RI ao Desenho…* (devolve a **obra inteira** ao Desenho) e *Devolver à pilha* (sai de você e volta à fila da empresa).
- **Passar para outra pessoa:** Transferir para outro usuário… (ele aceita ou rejeita).
- **Abrir:** obra e ordem.

### Como devolver ao Desenho pelo RI (Retorno Interno)
1. **Ações › Enviar RI ao Desenho…** na Ordem da obra.
2. O modal lista as Ordens da obra que vão ficar **travadas**. Escolha a **categoria e a subcategoria** do retorno (as mesmas do RI da Contratação), escreva o que o Desenho precisa corrigir e clique em **Enviar RI**.
3. O sistema cria o retorno (Reclaim) para o Desenho, igual ao RI da Contratação. O Desenho trata na tela dele.
4. Enquanto isso, todas as Ordens da obra mostram **Travada · RI com o Desenho** e só dá para acompanhar. Status e responsável não mudam.
5. Quando o Desenho conclui, o RI retorna sozinho (quem enviou recebe o aviso) e as Ordens destravam, de onde pararam. Na **Gestão da Obra › Retorno Interno** você vê a situação: *Aguardando o Desenho*, *Em tratamento no Desenho*, *Retornado* ou *Cancelado*, com a categoria, o responsável no Desenho e os comentários.
6. Enviado por engano? A **gestão** usa **Cancelar RI…** (com motivo): destrava as Ordens, mas o retorno aberto no Desenho continua; um novo RI só depois que o Desenho concluir.

## Gestão da Obra: Informes e Arquivos
Abra a obra (Ações › Abrir obra). Além da **Visão geral** (Ordens, conversa, prioridade e pendências), há três abas, só para a equipe do Encerramento:
- **Retorno Interno:** histórico dos RI da obra e botão para enviar um novo.
- **Informes:** todos os informes registrados (empresa, data, situação, Ordens, valor da ADS e ADS tácita).
- **Arquivos:** todos os arquivos da obra, de qualquer atividade, com busca e filtro por tipo; baixe um a um ou marque vários e use **Baixar ZIP** (você é avisado na Central de Notificações). Os arquivos de ADS tácita também ficam disponíveis aqui.

Na tela **Minha atividade** não há "Devolver à pilha": para devolver use *Enviar à parceira*, *RI ao Desenho* ou *Transferir*. As cores da linha são só vermelho (sem baixa no SAP) e verde (concluída).

