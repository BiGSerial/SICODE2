# Guia do usuário — Despacho de Encerramento

Para quem tem a permissão de despacho (gestor/despachante). O despacho trabalha por **Ordem**, não por Nota: você pode marcar Ordens de várias Notas de uma só vez.

## Acesso

Menu **ATIVIDADES > ENCERRAMENTO**: **DESPACHO** (lista e configurações) e **PAINEL ANALÍTICO**.

## Tipos de Ordem: P, M e F

O tipo é calculado pela data de fim real da operação 0020 confirmada, em Ordens liberadas e ainda não encerradas.

| Tipo | Cor | Significa |
|---|---|---|
| **M** Meta atual | verde | fim real no mês anterior ao atual |
| **P** Passivo | vermelho | fim real antes do mês anterior e ainda não encerrada |
| **F** Meta futura | azul | fim real no mês atual |

O tipo muda sozinho enquanto a Ordem está aberta (F vira M na virada do mês; M vira P se não encerrar). Ao encerrar no SAP, o tipo **congela** e passa a medir se foi feito na meta ou fora dela.

## Como despachar

1. Filtre por Nota, Ordem, tipo ou outros campos da lista.
2. Escolha a **empresa de destino** e, se quiser, um **usuário** dessa empresa. Sem usuário, a Ordem vai para a pilha da empresa.
3. Marque as Ordens (pode ser de Notas diferentes).
4. Clique em **Ver prévia** e confira o que segue e o motivo de cada recusa.
5. Clique em **Confirmar despacho**.

O resultado mostra quantas foram despachadas, quantas já estavam despachadas (sem duplicar) e quantas foram recusadas (cancelada, já encerrada no SAP, fora da meta ou em outro despacho). As recusadas continuam marcadas para você corrigir. A situação é conferida de novo na confirmação.

Se a Ordem foi para um usuário, ele recebe aviso no sino e um toast.

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
