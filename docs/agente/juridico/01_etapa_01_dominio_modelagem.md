# Etapa 01 - Domínio e modelagem conceitual

## Objetivo

Criar o desenho de domínio antes de escrever migrations. O agente deve entender que o módulo não controla apenas linhas importadas, mas ciclos de trabalho internos gerados a partir de processos externos.

## Problema que esta etapa resolve

As três fontes atuais possuem estrutura parecida, mas não idêntica. Além disso, o mesmo número de processo pode aparecer mais de uma vez, em origens diferentes ou com motivos diferentes.

Portanto:

```txt
processo jurídico != demanda interna
```

Um processo jurídico pode ter várias demandas no SICODE.

## Entidades principais

### 1. `legal_cases`

Representa o processo jurídico de referência.

Responsabilidades:

- guardar o número do processo;
- consolidar dados gerais da ação;
- servir de vínculo entre várias demandas;
- permitir visão histórica do processo.

Campos conceituais:

```txt
id
uuid
process_number
process_number_normalized
company_name
external_status
legal_responsible_name
law_firm_name
main_origin_area
first_seen_at
last_seen_at
created_at
updated_at
```

### 2. `legal_demands`

Representa uma demanda tratável no SICODE.

Pode ser:

- liminar;
- sentença;
- cumprimento;
- subsídio;
- complemento;
- solicitação técnica;
- retorno jurídico;
- nova obrigação vinculada ao mesmo processo.

Campos conceituais:

```txt
id
uuid
legal_case_id
source_type
source_external_id
source_record_key
source_hash
title
description
subject
service_type
external_status
external_flow_status
origin_area_name
target_area_name
target_person_name
source_started_at
source_due_at
source_redirected_at
first_seen_at
last_seen_at
missing_since
source_presence_status
internal_status
priority
risk_level
controller_user_id
current_assigned_user_id
current_assigned_team_id
closed_by
closed_at
closure_reason
external_closed_at
external_protocol
external_closure_note
raw_payload
created_at
updated_at
```

### 3. `legal_source_snapshots`

Guarda a fotografia da linha importada.

Responsabilidades:

- preservar a origem bruta;
- detectar mudança de prazo, área, texto ou responsável;
- comprovar que determinado dado veio de fora;
- permitir auditoria futura.

Campos conceituais:

```txt
id
legal_demand_id
import_batch_id
source_type
source_external_id
source_record_key
source_hash
raw_payload
seen_at
created_at
```

### 4. `legal_import_batches`

Representa uma execução de importação.

Campos conceituais:

```txt
id
source_type
started_at
finished_at
total_rows
new_rows
updated_rows
unchanged_rows
missing_rows
failed_rows
status
error_message
created_at
updated_at
```

### 5. `legal_demand_assignments`

Representa o envio para usuário/equipe da ponta.

Campos conceituais:

```txt
id
uuid
legal_demand_id
assigned_by_user_id
assigned_to_user_id
assigned_to_team_id
status
message
due_at
sent_at
received_at
answered_at
returned_at
cancelled_at
closed_at
response_summary
controller_review_note
created_at
updated_at
```

### 6. `legal_demand_events`

Linha do tempo auditável.

Campos conceituais:

```txt
id
legal_demand_id
assignment_id
event_type
from_status
to_status
actor_user_id
target_user_id
target_team_id
description
metadata
occurred_at
created_at
```

### 7. `legal_demand_files`

Associação de arquivos/documentos com controle de visibilidade.

Campos conceituais:

```txt
id
legal_demand_id
assignment_id
file_id
uploaded_by_user_id
category
visibility
can_be_sent_external
is_evidence
is_final_response
created_at
updated_at
```

## Estados internos recomendados

Para `legal_demands.internal_status`:

```txt
new_imported
triage
waiting_controller_action
sent_to_field
field_received
waiting_field_response
returned_by_field
under_controller_review
returned_for_correction
ready_to_close_external
closed_internal
closed_external
cancelled
reopened
ignored
```

Para `legal_demand_assignments.status`:

```txt
sent
received
in_progress
answered
returned_to_controller
returned_for_correction
cancelled
closed
```

Para `source_presence_status`:

```txt
present
missing
returned
ignored
```

## Regras de domínio

### Regra 1 - Não confundir processo com demanda

Se o mesmo número de processo aparecer com novo motivo, nova origem ou novo ciclo operacional, criar nova `legal_demand` vinculada ao mesmo `legal_case`.

### Regra 2 - Ausência na fonte não encerra

Se uma demanda deixar de aparecer na origem, atualizar `missing_since` e `source_presence_status = missing`.

Não alterar `internal_status` para encerrado automaticamente.

### Regra 3 - Reaparecimento deve gerar evento

Se uma demanda marcada como `missing` voltar a aparecer:

```txt
source_presence_status = returned
missing_since = null
event_type = source_returned
```

### Regra 4 - Encerramento interno é diferente de encerramento externo

O controlador pode encerrar no SICODE, mas isso não significa que o sistema externo foi atualizado.

Usar campos separados:

```txt
closed_at
closed_by
external_closed_at
external_protocol
```

### Regra 5 - Alterações vindas da origem devem gerar snapshot

Toda importação deve gerar snapshot ou, no mínimo, snapshot quando houver alteração de hash.

## Checklist da etapa

- [ ] Entidades principais validadas.
- [ ] Diferença entre `legal_cases` e `legal_demands` documentada.
- [ ] Status internos aprovados.
- [ ] Status de presença da fonte aprovados.
- [ ] Regras de reaparecimento aprovadas.
- [ ] Regras de encerramento interno/externo aprovadas.
- [ ] Estratégia de snapshots aprovada.
- [ ] Estratégia de eventos aprovada.

## Observabilidade da etapa

O agente deve entregar documentação ou testes que permitam verificar:

- [ ] Um processo pode ter várias demandas.
- [ ] Uma demanda pode ter vários assignments.
- [ ] Uma demanda pode ter vários eventos.
- [ ] Uma demanda pode ter vários arquivos.
- [ ] Uma demanda pode ter vários snapshots.
- [ ] Ausência na origem não encerra a demanda.
- [ ] Reaparecimento gera evento.
