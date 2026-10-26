# Contrato do RI (Retorno Interno com o Desenho)

> Fonte única das regras do RI no Encerramento. Código: `App\Services\Closure\ClosureInternalReturnService`, guard em `ClosureOrderWorkflowService::transition()`, observer `App\Observers\ClosureReclaimObserver`. Testes: `tests/Feature/Closure/ClosureInternalReturnTest.php` (a matriz abaixo é verificada lá).
> Plano e fases: [CAIXAS_PENDENCIAS_E_RI_CHECKLIST.md](CAIXAS_PENDENCIAS_E_RI_CHECKLIST.md).

## 1. O que é

O RI é um `Reclaim` (tabela `reclaims`) apontando para o serviço **Desenho**, criado pelo Encerramento pelo mesmo fluxo de `ReturnD5` (Reclaim + comentário + viabilidades em status 11 "Criado RI"). **O Desenho trata na tela dele.** O Encerramento só **envia, acompanha e fica travado enquanto espera**.

- O RI é da **Nota** e vale para **todas as Ordens** dela (inclusive Ordens que entrarem na Nota durante o RI).
- A trava é **sobreposta**: as Ordens mantêm status e posse; nada precisa ser restaurado ao fim.
- Tabela do vínculo: `closure_internal_returns` (`reclaim_id` sem FK — `reclaims` é de outra atividade).

## 2. Estados do RI

| Estado | Trava as Ordens? | Como entra | Como sai |
|---|---|---|---|
| `SENT` | **Sim** | `send()` | `RETURNED` (Reclaim concluído) ou `CANCELLED` (gestão) |
| `RETURNED` | Não | `syncFromReclaim()` | final |
| `CANCELLED` | Não | `cancel()` | final |

`closed_at` é preenchido em **todo** encerramento (retorno ou cancelamento). Ele libera o índice único `active_note_id` (**um RI ativo por Nota**, garantido no banco). Quem encerra um RI **sempre** preenche `closed_at`.

## 3. Ações do RI × quem pode × quando

| Ação | Quem | Pré-condições (senão recusa com mensagem) | Efeito |
|---|---|---|---|
| `send(Nota, ator, comentário)` | `closure.manage` ou `closure.work` | comentário preenchido; Nota não cancelada; serviço Desenho existe; ≥ 1 Ordem ativa; **sem RI ativo na Nota**; **sem Reclaim ativo do Desenho para a Nota**; Nota com viabilidade | cria Reclaim + comentário + viabilidades em status 11; RI `SENT` (ciclo = anterior + 1); evento `RI_SENT` em cada Ordem ativa (lote); aviso |
| `syncFromReclaim(RI)` | sistema (observer pós-save + varredura) | RI ativo **e** Reclaim `completed` | RI `RETURNED` (`returned_at` = `completed_at` do Reclaim); evento `RI_RETURNED` por Ordem; aviso. **Idempotente.** |
| `cancel(RI, ator, motivo)` | **só `closure.manage`** | motivo preenchido; RI ativo | RI `CANCELLED`; evento `RI_CANCELLED` por Ordem; comentário informativo no Reclaim; aviso. **Não** fecha o Reclaim nem altera viabilidade |
| `handleReclaimSaved(Reclaim)` | observer | `completed` mudou | `true` → `syncFromReclaim`; `false` (reabertura) → **só avisa, nunca re-trava** |
| `syncAll()` | comando `closure:sync-internal-returns` (a cada 15 min) | — | repete `syncFromReclaim` para todo RI ativo; conta RI ativo cujo Reclaim sumiu (`orphans`) |

## 4. Matriz: ação sobre a Ordem × estado

`Livre` = nenhum RI ativo na Nota (inclui RI `RETURNED`/`CANCELLED`). `Travada` = RI `SENT` na Nota da Ordem. A recusa é feita **antes** de qualquer regra de status, com a mensagem *"A Ordem está travada: há um RI (Retorno Interno) com o Desenho…"*.

| Ação sobre a Ordem (`ClosureOrderWorkflowService`) | Livre | Travada |
|---|---|---|
| `claim` (assumir) | regra normal | **recusada** |
| `assign` / `transfer` | regra normal | **recusada** |
| `returnToPool` | regra normal | **recusada** |
| `start` / `finish` | regra normal | **recusada** |
| `waitForReturn` / `changeStatus` | regra normal | **recusada** |
| `setPriority` | regra normal | **recusada** |
| `blockForIssue` / abrir pendência **bloqueante** | regra normal | **recusada** |
| Abrir pendência **não bloqueante** | permitida | **permitida** (não muda a Ordem) |
| `promoteWaiting` (promoção automática da meta) | promove | adiada: a Ordem fica em espera até destravar |
| `ClosureDispatchService::dispatch` (despachar Ordens da Nota) | regra normal | **recusado** |
| Mensagens do chat da obra | permitidas | **permitidas** |

### Lista branca (passam pela trava para ela nunca prender a Ordem)

| Fluxo | Por quê |
|---|---|
| `restoreAfterIssue` (pendência resolvida/validada) | a Ordem em `WAITING_PARTNER` precisa voltar ao ponto anterior |
| `releaseDepositByPartner` (depósito liberado) | idem para `DEPOSIT_BLOCKED` |
| `syncFromSap` (encerrada `ENTE/ENCE` ou cancelada no SAP) | o SAP manda; não passa pelo guard (`guard = false`) |
| Eventos do próprio RI (`RI_SENT/RETURNED/CANCELLED`) | `recordInternalReturnEvent` não passa pelo guard e não muda status/posse/período |

## 5. Invariantes (o que evita quebra de ação e ciclo)

1. **Um RI ativo por Nota** — índice único no banco; o pré-check do service dá a mensagem amigável.
2. **Sem Reclaim duplicado** — se o Desenho já tem retorno interno ativo para a Nota (por qualquer fluxo), `send` recusa.
3. **Fim idempotente** — rodar `syncFromReclaim` N vezes dá o mesmo resultado; nenhum evento duplicado.
4. **Reabertura nunca re-trava** — o Desenho pode reabrir o Reclaim. O RI fica `RETURNED`, a equipe é avisada, e esperar de novo exige um **novo RI explícito** (ciclo + 1) — que só é aceito depois que o Desenho concluir o atual.
5. **Cancelar só destrava o Encerramento** — o Reclaim e a viabilidade (status 11) são de outra atividade e não são tocados; apenas um comentário informativo é acrescentado ao Reclaim. Por isso, após cancelar, um novo RI só é aceito quando o Desenho concluir o Reclaim em andamento.
6. **Atomicidade** — Reclaim, comentário, viabilidades, RI e eventos são gravados numa transação; qualquer recusa deixa o banco intacto.
7. **O observer nunca quebra outro módulo** — erro na sincronização é só reportado (`report()`); a varredura corrige em até 15 min.
8. **RI órfão** (Reclaim apagado) não se resolve sozinho: a varredura o conta em `orphans` e a gestão **cancela** o RI para destravar.
9. **Ordem encerrada/cancelada no SAP** durante o RI: o SAP prevalece (lista branca); essas Ordens não recebem os eventos do RI.

## 6. Eventos e avisos

| Evento na Ordem | Rótulo (`ClosureEventLabels`) | Aviso (`ClosureNotifier::internalReturnChanged`) |
|---|---|---|
| `RI_SENT` | RI enviado ao Desenho | gestores + responsáveis das Ordens da Nota + quem enviou (exceto o autor) |
| `RI_RETURNED` | RI retornado pelo Desenho | idem (autor = sistema, todos recebem) |
| `RI_CANCELLED` | RI cancelado pela gestão | idem, exceto o autor |
| — (reabertura) | — | `closure.internal_return_reopened` (sem re-travar) |

## 7. Como o usuário faz o RI na tela (implementado)

- **Enviar:** em **Minha atividade** (ou Controle), menu **Ações › Devolver / pedir correção › Enviar RI ao Desenho…** (quem é o responsável da Ordem ou a gestão). O modal `Closure\InternalReturns\Modal` mostra a Nota e **todas as Ordens ativas** que vão travar, exige o comentário ("O que o Desenho precisa corrigir?") e chama `ClosureInternalReturnService::send`. Recusas do serviço aparecem dentro do modal.
- **Enquanto ativo:** cada Ordem da obra mostra o selo **Travada · RI com o Desenho** (`<x-closure.lock-badge>`, com a data de envio) e o menu de Ações vira só **Obra travada** (aviso + Abrir obra/ordem). Nenhuma outra ação é oferecida (o guard do servidor também recusa).
- **Cancelar (só gestão):** no mesmo menu, **Cancelar RI…** (motivo obrigatório); só destrava, o Reclaim do Desenho continua.
- **Retorno:** quando o Desenho conclui o Reclaim, o RI retorna sozinho (observer + varredura de 15 min) e o selo some.
- Para listas: `lockFor($state)` → `['reason', 'since', 'internal_return_id']` ou `null`; `activeForNotes($noteIds)` evita N+1.
