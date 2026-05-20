# Etapa 07 - Testes, validação e critérios de aceite

## Objetivo

Garantir que o módulo não duplique demandas, não perca histórico, não encerre indevidamente e consiga medir o ciclo de vida completo.

## Tipos de teste

O agente deve entregar:

- testes unitários para normalizadores;
- testes unitários para geradores de chave/hash;
- testes de integração para importação;
- testes de integração para workflow;
- testes de permissão;
- testes de arquivos e visibilidade;
- testes de dashboard/queries críticas quando aplicável.

## Testes de normalização

### Número de processo

Validar:

- remove espaços;
- preserva zeros;
- normaliza pontuação;
- trata valor vazio;
- trata valor inválido.

Checklist:

- [ ] `50015528820268080038` permanece consistente.
- [ ] `5001552-88.2026.8.08.0038` vira forma normalizada.
- [ ] valor `NULL` não quebra importação.
- [ ] valor com espaços é normalizado.

### Datas

Validar formatos:

```txt
08/04/2026 03:00:00
2026-04-15 10:55:07.405
08/05/2026 00:00:00
NULL
```

Checklist:

- [ ] data brasileira com hora parseia.
- [ ] timestamp ISO parseia.
- [ ] `NULL` vira null.
- [ ] data inválida registra erro controlado.

## Testes de importação

### Importação idempotente

Cenário:

```txt
Dado um arquivo/fonte com 10 linhas
Quando o importador roda duas vezes
Então deve haver 10 demands, não 20
```

Checklist:

- [ ] Não duplica `legal_cases`.
- [ ] Não duplica `legal_demands`.
- [ ] Atualiza `last_seen_at`.
- [ ] Incrementa `unchanged_rows`.

### Mudança de prazo

Cenário:

```txt
Dado uma demanda já importada
Quando a origem retorna a mesma demanda com prazo alterado
Então deve atualizar source_due_at
E gravar snapshot
E registrar evento updated_from_source
```

Checklist:

- [ ] `source_hash` muda.
- [ ] snapshot é criado.
- [ ] evento é criado.
- [ ] dados antigos continuam consultáveis via snapshot anterior.

### Sumiço da origem

Cenário:

```txt
Dado uma demanda presente no batch anterior
Quando ela não aparece no batch atual
Então deve marcar missing
E não deve encerrar internamente
```

Checklist:

- [ ] `source_presence_status = missing`.
- [ ] `missing_since` preenchido.
- [ ] `internal_status` preservado.
- [ ] evento `source_missing` criado.

### Reaparecimento

Cenário:

```txt
Dado uma demanda missing
Quando ela volta na fonte
Então limpar missing_since
E registrar source_returned
```

Checklist:

- [ ] `missing_since = null`.
- [ ] evento `source_returned`.
- [ ] histórico anterior preservado.

### Processo com várias demandas

Cenário:

```txt
Dado o mesmo número de processo em liminar e subsídio
Quando importar ambas as fontes
Então criar um legal_case
E criar duas legal_demands
```

Checklist:

- [ ] Uma referência em `legal_cases`.
- [ ] Duas demandas em `legal_demands`.
- [ ] `source_type` diferente em cada demanda.

## Testes de workflow

### Enviar para ponta

Checklist:

- [ ] cria assignment.
- [ ] atualiza status da demanda.
- [ ] registra evento.
- [ ] define usuário/equipe atual.
- [ ] não apaga assignment anterior.

### Receber

Checklist:

- [ ] preenche `received_at`.
- [ ] muda assignment para `received`.
- [ ] muda demanda para `field_received`.
- [ ] registra evento.

### Responder

Checklist:

- [ ] exige texto ou arquivo.
- [ ] preenche `answered_at`.
- [ ] muda assignment para `answered`.
- [ ] muda demanda para `returned_by_field`.
- [ ] registra evento.

### Devolver para correção

Checklist:

- [ ] muda status para `returned_for_correction`.
- [ ] preserva resposta anterior.
- [ ] registra justificativa.
- [ ] registra evento.

### Encerrar internamente

Checklist:

- [ ] exige permissão.
- [ ] preenche `closed_at`.
- [ ] preenche `closed_by`.
- [ ] preenche motivo.
- [ ] registra evento `internal_closed`.

### Encerrar externamente

Checklist:

- [ ] exige permissão.
- [ ] exige protocolo ou justificativa.
- [ ] preenche `external_closed_at`.
- [ ] preenche `external_protocol`.
- [ ] muda status para `closed_external`.
- [ ] registra evento.

## Testes de arquivos

Checklist:

- [ ] usuário autorizado anexa arquivo.
- [ ] arquivo cria vínculo com demanda.
- [ ] arquivo cria vínculo com assignment quando aplicável.
- [ ] visibilidade `controller_only` bloqueia usuário comum.
- [ ] visibilidade `external_ready` aparece na área de encerramento.
- [ ] remoção lógica preserva evento.

## Testes de dashboards

Checklist:

- [ ] demanda vencida aparece no card de vencidas.
- [ ] demanda com prazo hoje aparece no card de vencem hoje.
- [ ] demanda sem responsável aparece no card correto.
- [ ] demanda `closed_internal` sem `external_closed_at` aparece em pendências externas.
- [ ] demanda `missing` aparece em monitoramento de origem.
- [ ] demanda `reopened` aparece em reabertas.
- [ ] tempos de ponta usam assignments, não status externo textual.

## Critérios de aceite gerais

- [ ] Importação roda sem duplicar.
- [ ] Processo-mãe pode ter múltiplas demandas.
- [ ] Ausência na origem não encerra demanda.
- [ ] Reaparecimento é auditado.
- [ ] Toda transição relevante gera evento.
- [ ] Arquivos têm visibilidade.
- [ ] Encerramento interno e externo são separados.
- [ ] Dashboard mostra gargalos.
- [ ] Logs de importação mostram contadores.
- [ ] Testes principais passam.
