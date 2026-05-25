# Backlog - Historias de Usuario do Modulo Juridico

## 1. Estrutura de priorizacao

- Epic: agrupador funcional.
- Prioridade: P0, P1, P2.
- Story points: estimativa inicial para refinamento.
- Criterios de aceite: base para QA e homologacao.

## 2. Epic A - Cockpit e fila operacional

### US-A01 (P0, 5 pts)
Como controlador, quero ver a lista consolidada de demandas para priorizar o trabalho do dia.

Criterios de aceite:

- Exibe colunas essenciais (processo, origem, prazo, status, responsavel, risco, prioridade).
- Permite ordenar por prazo e ultima atualizacao.
- Permite abrir detalhe em 1 clique.

### US-A02 (P0, 3 pts)
Como controlador, quero filtrar demandas vencidas, vence hoje e sem responsavel para reduzir risco de perda de prazo.

Criterios de aceite:

- Filtros rapidos disponiveis no topo.
- Contadores atualizados conforme filtro.
- Estado vazio com acao recomendada.

### US-A03 (P1, 5 pts)
Como usuario da ponta, quero uma fila exclusiva das minhas demandas para responder sem ruido.

Criterios de aceite:

- Lista mostra recebidas, em andamento, correcao e atrasadas.
- Nao exibe demandas de outros usuarios sem permissao.

## 3. Epic B - Ciclo de tratativa

### US-B01 (P0, 5 pts)
Como controlador, quero iniciar triagem e definir prioridade/risco para organizar o tratamento da demanda.

Criterios de aceite:

- Acao disponivel em status elegiveis.
- Registra evento de triagem.
- Persiste prioridade e risco.

### US-B02 (P0, 8 pts)
Como controlador, quero enviar a demanda para usuario ou equipe com prazo interno e mensagem para delegar execucao.

Criterios de aceite:

- Bloqueia envio sem destino.
- Cria novo assignment sem sobrescrever historico.
- Atualiza responsavel atual.
- Registra evento sent_to_field.

### US-B03 (P0, 5 pts)
Como usuario da ponta, quero confirmar recebimento para formalizar inicio do atendimento.

Criterios de aceite:

- Marca assignment como received.
- Atualiza status da demanda para field_received.
- Registra evento field_received.

### US-B04 (P0, 8 pts)
Como usuario da ponta, quero responder com texto, evidencia ou justificativa de impossibilidade para devolver a demanda de forma valida.

Criterios de aceite:

- Bloqueia resposta sem conteudo minimo.
- Registra answered_at e response_summary.
- Atualiza status para returned_by_field.

### US-B05 (P0, 5 pts)
Como controlador, quero solicitar correcao com nota para a ponta complementar a resposta quando necessario.

Criterios de aceite:

- Atualiza status para returned_for_correction.
- Persiste nota de revisao.
- Registra evento correspondente.

### US-B06 (P1, 3 pts)
Como controlador, quero aprovar retorno da ponta para preparar o fechamento externo.

Criterios de aceite:

- Atualiza status para ready_to_close_external.
- Registra evento controller_approved.

## 4. Epic C - Encerramento e reabertura

### US-C01 (P0, 5 pts)
Como controlador, quero encerrar internamente com motivo para formalizar conclusao no SICODE.

Criterios de aceite:

- Exige motivo de encerramento.
- Persiste closed_by e closed_at.
- Registra evento internal_closed.

### US-C02 (P0, 5 pts)
Como controlador autorizado, quero registrar encerramento externo com protocolo para comprovar baixa no sistema externo.

Criterios de aceite:

- Exige protocolo obrigatorio.
- Persiste external_closed_at, external_protocol, external_closure_note.
- Registra evento external_closed.

### US-C03 (P1, 3 pts)
Como perfil autorizado, quero reabrir demanda encerrada para tratar retorno ou inconsistencias.

Criterios de aceite:

- Atualiza status para reopened.
- Registra motivo e evento reopened.

## 5. Epic D - Arquivos e comentarios

### US-D01 (P0, 8 pts)
Como usuario autorizado, quero anexar arquivo com categoria e visibilidade para manter documentacao rastreavel.

Criterios de aceite:

- Exige categoria e visibilidade validas.
- Permite vinculo ao assignment.
- Registra evento file_attached.

### US-D02 (P1, 5 pts)
Como controlador, quero alterar visibilidade de anexos para controlar exposicao de documentos sensiveis.

Criterios de aceite:

- Altera visibilidade com permissao.
- Registra evento file_visibility_changed.

### US-D03 (P1, 3 pts)
Como usuario autorizado, quero remover logicamente um anexo sem apagar historico para manter trilha de auditoria.

Criterios de aceite:

- Define removed_at no vinculo.
- Nao apaga registro fisico.
- Registra evento file_removed.

### US-D04 (P1, 3 pts)
Como usuario interno, quero comentar na demanda com visibilidade para colaborar sem perder contexto.

Criterios de aceite:

- Permite comentario por demanda e por assignment.
- Ordena por data de criacao.
- Aplica regra de visibilidade.

## 6. Epic E - Observabilidade e alertas

### US-E01 (P0, 5 pts)
Como gestor, quero ver cards de operacao para monitorar risco de prazo e carga operacional.

Criterios de aceite:

- Exibe metricas principais (abertas, vencidas, sem responsavel, aguardando ponta, etc).
- Dados rastreaveis por query.

### US-E02 (P1, 8 pts)
Como gestor, quero dashboards por origem, area e usuario para identificar gargalos e performance.

Criterios de aceite:

- Permite recorte por periodo.
- Mostra tempo medio de ciclo e pendencias.

### US-E03 (P1, 5 pts)
Como controlador, quero alertas de prazo e baixa externa pendente para agir antes de estourar SLA.

Criterios de aceite:

- Alerta para vence hoje, vence em 3 dias e vencida.
- Alerta para closed_internal sem external_closed_at apos X dias.

### US-E04 (P1, 5 pts)
Como controlador, quero alertas de source_missing e source_returned para investigar mudancas na origem.

Criterios de aceite:

- Alerta quando demanda some da origem.
- Alerta quando demanda reaparece.
- Eventos visiveis na timeline.

## 7. Epic F - Saude de importacao

### US-F01 (P1, 5 pts)
Como operador tecnico, quero visualizar batches de importacao para auditar sincronizacao e falhas.

Criterios de aceite:

- Exibe started_at, finished_at, total, novos, atualizados, missing, failed e status.
- Permite filtrar por periodo e origem.

### US-F02 (P2, 3 pts)
Como operador tecnico, quero detalhar erro de importacao para acelerar correcao de integracao.

Criterios de aceite:

- Mostra error_message por batch.
- Permite copiar detalhes para incidente.

## 8. Dependencias tecnicas para refinamento

- Permissoes legal.demands.* configuradas por perfil.
- Eventos completos no legal_demand_events.
- Consistencia de status internos e de assignment.
- Estrategia de notificacao central definida.
- Definicao de SLA interno por tipo de demanda.

## 9. Sugestao de planejamento por sprint

Sprint 1:

- US-A01, US-A02, US-B01, US-B02.

Sprint 2:

- US-B03, US-B04, US-B05, US-C01.

Sprint 3:

- US-C02, US-D01, US-D02, US-E01.

Sprint 4:

- US-A03, US-B06, US-D03, US-D04, US-E03, US-E04, US-F01.

Sprint 5:

- US-E02, US-F02 e refinamentos de UX/performance.
