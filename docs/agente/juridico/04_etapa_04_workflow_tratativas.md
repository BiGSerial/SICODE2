# Etapa 04 - Workflow interno e tratativas

## Objetivo

Criar o fluxo interno do SICODE para controlar o trabalho do controlador e dos usuários/equipes da ponta.

## Conceito

A demanda importada não deve ser apenas visualizada. Ela precisa virar uma tratativa rastreável.

O fluxo básico é:

```txt
Nova demanda
  -> Triagem do controlador
  -> Envio para ponta
  -> Recebimento pela ponta
  -> Resposta da ponta
  -> Revisão do controlador
  -> Correção, se necessário
  -> Encerramento interno
  -> Encerramento externo
```

## Status da demanda

### `new_imported`

Demanda acabou de entrar pela fonte externa.

Ações permitidas:

- iniciar triagem;
- ignorar;
- definir prioridade;
- definir controlador;
- enviar para ponta.

### `triage`

Controlador está analisando.

Ações permitidas:

- alterar prioridade;
- anexar documentos;
- enviar para ponta;
- encerrar se não exigir ação;
- ignorar.

### `sent_to_field`

Demanda enviada para usuário/equipe da ponta.

Ações permitidas:

- registrar recebimento;
- cancelar envio;
- reenviar;
- alterar prazo interno.

### `field_received`

Usuário da ponta recebeu/visualizou formalmente.

Ações permitidas:

- marcar em andamento;
- responder;
- anexar evidência.

### `waiting_field_response`

Aguardando resposta.

Ações permitidas:

- responder;
- anexar evidência;
- solicitar prorrogação;
- devolver com impossibilidade.

### `returned_by_field`

Ponta devolveu ao controlador.

Ações permitidas:

- revisar;
- devolver para correção;
- marcar como pronto para encerramento externo.

### `under_controller_review`

Controlador está revisando retorno.

Ações permitidas:

- aprovar;
- solicitar correção;
- anexar documento final;
- encerrar internamente.

### `returned_for_correction`

Controlador devolveu para a ponta corrigir.

Ações permitidas:

- usuário responder novamente;
- anexar novos arquivos;
- controlador cancelar correção.

### `ready_to_close_external`

Tratativa interna pronta. Falta encerrar no programa externo.

Ações permitidas:

- informar protocolo externo;
- marcar encerramento externo;
- reabrir se necessário.

### `closed_internal`

Encerrado dentro do SICODE, mas ainda sem comprovação de encerramento externo.

Ações permitidas:

- registrar encerramento externo;
- reabrir.

### `closed_external`

Encerrado também no sistema externo.

Ações permitidas:

- reabrir somente por permissão administrativa;
- consultar histórico.

## Assignments

Cada envio para ponta deve criar um registro em `legal_demand_assignments`.

Não sobrescrever assignment antigo.

Se enviar novamente para outra pessoa, criar novo assignment.

## Eventos obrigatórios

Cada transição deve gravar evento.

Exemplos:

```txt
triage_started
priority_changed
sent_to_field
field_received
field_answered
returned_to_controller
returned_for_correction
correction_answered
controller_approved
internal_closed
external_closed
reopened
assignment_cancelled
```

## Regras de envio para ponta

Ao enviar para a ponta:

1. Validar se a demanda não está encerrada.
2. Validar se existe usuário ou equipe destino.
3. Criar `legal_demand_assignments`.
4. Atualizar `legal_demands.current_assigned_user_id` ou `current_assigned_team_id`.
5. Atualizar `internal_status = sent_to_field`.
6. Registrar evento `sent_to_field`.
7. Notificar usuário/equipe, se o SICODE tiver notificação.

## Regras de recebimento

O recebimento pode ocorrer:

- manualmente pelo usuário;
- automaticamente ao abrir a demanda pela primeira vez.

Ao receber:

```txt
assignment.status = received
assignment.received_at = now()
demand.internal_status = field_received
evento = field_received
```

## Regras de resposta da ponta

A resposta deve exigir pelo menos:

- texto de retorno; ou
- arquivo/evidência; ou
- marcação de impossibilidade com justificativa.

Ao responder:

```txt
assignment.status = answered
assignment.answered_at = now()
assignment.response_summary = texto
demand.internal_status = returned_by_field
evento = field_answered
```

## Regras de revisão do controlador

O controlador pode:

1. Aprovar retorno.
2. Solicitar correção.
3. Encerrar internamente.
4. Encerrar internamente e já registrar encerramento externo.
5. Reabrir demanda.

## Regras de correção

Ao solicitar correção:

```txt
assignment.status = returned_for_correction
demand.internal_status = returned_for_correction
evento = returned_for_correction
```

A resposta corrigida deve registrar novo evento, preservando o histórico anterior.

## Encerramento interno

Ao encerrar internamente:

```txt
demand.internal_status = closed_internal
demand.closed_by = user_id
demand.closed_at = now()
demand.closure_reason = motivo
evento = internal_closed
```

## Encerramento externo

Ao encerrar no programa externo:

```txt
demand.internal_status = closed_external
demand.external_closed_at = now()
demand.external_protocol = protocolo
demand.external_closure_note = observação
evento = external_closed
```

## Permissões recomendadas

```txt
legal.demands.view
legal.demands.triage
legal.demands.assign
legal.demands.answer
legal.demands.review
legal.demands.close_internal
legal.demands.close_external
legal.demands.reopen
legal.demands.ignore
legal.demands.manage_files
legal.demands.view_controller_files
```

## Checklist da etapa

- [ ] Criar actions/services para transições de status.
- [ ] Criar validação de transições permitidas.
- [ ] Criar envio para usuário.
- [ ] Criar envio para equipe.
- [ ] Criar recebimento manual/automático.
- [ ] Criar resposta da ponta.
- [ ] Criar revisão do controlador.
- [ ] Criar devolução para correção.
- [ ] Criar encerramento interno.
- [ ] Criar encerramento externo.
- [ ] Criar reabertura.
- [ ] Criar permissões.
- [ ] Criar eventos para cada ação.
- [ ] Criar notificações, se aplicável.

## Observabilidade da etapa

O agente deve entregar:

- [ ] Evento para toda transição de status.
- [ ] Histórico visual por demanda.
- [ ] Log quando uma transição for bloqueada.
- [ ] Métrica de tempo entre `sent_at` e `received_at`.
- [ ] Métrica de tempo entre `received_at` e `answered_at`.
- [ ] Métrica de tempo entre `answered_at` e `closed_at`.
- [ ] Lista de assignments abertos por usuário.
- [ ] Lista de demandas sem responsável.
- [ ] Lista de demandas vencidas.
- [ ] Lista de demandas devolvidas para correção.
- [ ] Teste para impedir encerramento sem permissão.
- [ ] Teste para impedir resposta em demanda encerrada.
- [ ] Teste para garantir que novo envio cria novo assignment, sem apagar o anterior.
