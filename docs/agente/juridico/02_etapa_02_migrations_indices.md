# Etapa 02 - Migrations, índices e estrutura física

## Objetivo

Criar as tabelas necessárias para o módulo de Processos Comerciais/Jurídicos no SICODE, respeitando integridade, rastreabilidade e performance.

## Diretrizes gerais

- Usar `id` incremental como chave primária.
- Usar `uuid` para exposição pública em telas e rotas.
- Usar `foreignId` para relações internas.
- Usar `json` ou `jsonb`, conforme banco, para `raw_payload` e `metadata`.
- Nunca depender apenas do número do processo para identificar uma demanda.
- Criar índices desde o início para filtros operacionais e dashboards.
- Não criar cascata destrutiva em dados auditáveis.

## Tabela 1 - `legal_cases`

### Finalidade

Guardar a referência do processo jurídico.

### Campos recomendados

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

### Índices

```txt
unique(process_number_normalized, company_name)
index(external_status)
index(company_name)
index(last_seen_at)
```

## Tabela 2 - `legal_demands`

### Finalidade

Guardar cada demanda/ciclo tratável no SICODE.

### Campos recomendados

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

### Índices

```txt
index(legal_case_id)
index(source_type)
index(source_external_id)
unique(source_record_key)
index(source_hash)
index(internal_status)
index(source_presence_status)
index(source_due_at)
index(current_assigned_user_id)
index(controller_user_id)
index(last_seen_at)
index(missing_since)
```

## Tabela 3 - `legal_import_batches`

### Finalidade

Registrar cada execução de importação.

### Campos recomendados

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

### Índices

```txt
index(source_type)
index(status)
index(started_at)
index(finished_at)
```

## Tabela 4 - `legal_source_snapshots`

### Finalidade

Preservar cada fotografia da fonte.

### Campos recomendados

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

### Índices

```txt
index(legal_demand_id)
index(import_batch_id)
index(source_type)
index(source_external_id)
index(source_record_key)
index(source_hash)
index(seen_at)
```

## Tabela 5 - `legal_demand_assignments`

### Finalidade

Controlar envio, recebimento, resposta e devolução entre controlador e ponta.

### Campos recomendados

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

### Índices

```txt
index(legal_demand_id)
index(assigned_by_user_id)
index(assigned_to_user_id)
index(assigned_to_team_id)
index(status)
index(due_at)
index(sent_at)
index(answered_at)
```

## Tabela 6 - `legal_demand_events`

### Finalidade

Registrar toda mudança relevante da demanda.

### Campos recomendados

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

### Índices

```txt
index(legal_demand_id)
index(assignment_id)
index(event_type)
index(actor_user_id)
index(target_user_id)
index(target_team_id)
index(occurred_at)
```

## Tabela 7 - `legal_demand_files`

### Finalidade

Associar arquivos/documentos às demandas ou assignments.

### Campos recomendados

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

### Índices

```txt
index(legal_demand_id)
index(assignment_id)
index(file_id)
index(uploaded_by_user_id)
index(category)
index(visibility)
index(is_evidence)
index(is_final_response)
```

## Tabela 8 - `legal_demand_comments`

### Finalidade

Registrar comentários internos e respostas textuais.

### Campos recomendados

```txt
id
legal_demand_id
assignment_id
user_id
comment
visibility
created_at
updated_at
```

### Índices

```txt
index(legal_demand_id)
index(assignment_id)
index(user_id)
index(visibility)
index(created_at)
```

## Enums recomendados

### `source_type`

```txt
liminar
sentence
subsidy
```

### `internal_status`

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

### `priority`

```txt
low
normal
high
critical
```

### `risk_level`

```txt
low
medium
high
critical
```

### `source_presence_status`

```txt
present
missing
returned
ignored
```

## Checklist da etapa

- [ ] Criar migration de `legal_cases`.
- [ ] Criar migration de `legal_demands`.
- [ ] Criar migration de `legal_import_batches`.
- [ ] Criar migration de `legal_source_snapshots`.
- [ ] Criar migration de `legal_demand_assignments`.
- [ ] Criar migration de `legal_demand_events`.
- [ ] Criar migration de `legal_demand_files`.
- [ ] Criar migration de `legal_demand_comments`.
- [ ] Criar índices únicos e compostos.
- [ ] Criar modelos Eloquent.
- [ ] Criar relações entre os modelos.
- [ ] Criar factories básicas para testes.
- [ ] Criar seeders opcionais de status/enums se o projeto usar tabelas auxiliares.

## Observabilidade da etapa

O agente deve entregar evidências de que:

- [ ] `php artisan migrate` executa sem erro.
- [ ] `php artisan migrate:rollback` executa sem erro em ambiente de teste.
- [ ] Índice único de `legal_cases` impede duplicidade de processo/empresa.
- [ ] Índice único de `legal_demands.source_record_key` impede duplicidade da mesma demanda.
- [ ] Relacionamentos Eloquent funcionam:
  - [ ] `LegalCase::demands()`;
  - [ ] `LegalDemand::case()`;
  - [ ] `LegalDemand::assignments()`;
  - [ ] `LegalDemand::events()`;
  - [ ] `LegalDemand::files()`;
  - [ ] `LegalDemand::snapshots()`.
- [ ] Migrations não apagam dados auditáveis em rollback parcial sem aviso.
- [ ] Campos JSON aceitam payload bruto da origem.
