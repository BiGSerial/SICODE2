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

A lista de Despacho tem busca, filtros em linha (Tipo P/M/F, status, empresa, município, **competência**, responsável e "só não despachadas") e cards-resumo clicáveis (Total, P, M, F, Não despachadas) que também filtram.

**Empresa (informe).** A coluna e o filtro **Empresa** mostram a empresa da relação com o **informe** da obra (a empreiteira que informou), a mesma da coluna EMPRESA da Lista para Medição. Regra por Ordem: o informe não cancelado ligado à Ordem (se houver vários, o mais recente); sem informe, a empresa da parcial da Ordem; sem parcial, o informe não cancelado da Nota. Ordens sem nenhum dos três mostram "—". Quem está com a Ordem agora (empresa do despacho) aparece ao passar o mouse sobre a célula. O resultado é guardado em cache por alguns minutos (mesmo TTL do conjunto de Ordens).

**Paginação.** A lista não tem janela de rolagem própria: a página rola normalmente e a rolagem horizontal só aparece em telas estreitas. O seletor **Itens por página** (10, 25, 50 ou 100) fica no cabeçalho do card e no rodapé, junto de "Exibindo X–Y de N" e dos links de página. A seleção de Ordens vale entre páginas. O Controle de Encerramento tem o mesmo seletor e rodapé.

1. Filtre a lista e marque as Ordens (pode ser de Notas diferentes). Use **Selecionar página** ou **Selecionar todas filtradas** (limite de 1.000 por vez); a seleção vale entre páginas.
2. Com ao menos uma Ordem marcada, o cabeçalho do card **Ordens** mostra o botão **Despachar selecionadas (N)** e um discreto **Limpar seleção** (não há barra flutuante). Para uma Ordem só, use o ícone de avião de papel na linha.
3. O **modal abre na hora**, sem esperar o servidor: o resumo vem dos dados já exibidos na lista e as empresas/usuários já estão na página. Confira o resumo (contagem por tipo e por Nota) e a lista de Ordens. Escolha a **empresa de destino** e, se quiser, um **usuário**; sem usuário, a Ordem vai para a pilha da empresa. Só aparecem empresas e usuários habilitados no serviço Encerramento. A empresa do informe já vem **pré-selecionada** quando todas as Ordens escolhidas têm a mesma (e ela está habilitada no serviço); se divergirem ou faltar informe, o modal avisa e você escolhe manualmente.
4. A prévia de elegibilidade é buscada logo após a abertura (a coluna Situação mostra uma animação até chegar) e refeita ao trocar empresa/usuário; **Confirmar despacho** só habilita quando a prévia chegou. Por Ordem: as recusadas ficam destacadas com o motivo (cancelada, já encerrada no SAP, fora da meta ou em outro despacho). Se houver recusadas, marque **Despachar só as elegíveis** para liberar o botão.
5. Clique em **Confirmar despacho**. O servidor revalida tudo (permissão, empresa, usuário e elegibilidade). O botão mostra o andamento e o modal passa a exibir o **recibo por Ordem**: despachada, já despachada ou recusada (com o motivo). Feche o modal (botão Fechar, Esc ou clique fora).

As recusadas continuam marcadas na lista para você corrigir e tentar de novo. A situação é conferida de novo na confirmação.

Se a Ordem foi para um usuário, ele recebe aviso no sino (uma vez por Ordem) e você vê um toast ao concluir.

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

## Importante

O despacho nunca altera o status no SAP. Cada Ordem encerrada ou cancelada no SAP é detectada automaticamente várias vezes ao dia.
