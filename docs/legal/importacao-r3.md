# Importação Jurídica R3

## Fontes externas R3
- `dbo.subjus_r3_subsidios` (`source_type=subsidy`)
- `dbo.subjus_r3_sentencas` (`source_type=sentence`)
- `dbo.subjus_r3_liminares` (`source_type=injunction`)

## Motivo do descarte do modelo antigo
- O legado dependia de tabelas/colunas antigas e não atendia ao contrato normalizado único.
- O fluxo R3 exige identidade explícita e rastreabilidade por lote, evento e snapshot.

## `process_number_core`
- Derivado de `process_number_normalized`.
- Regra: primeiros 13 dígitos.
- Uso: chave de identidade de caso e de demanda.

## Identidade de `legal_cases`
- `identity_key = sha256(case_number_normalized|process_number_core)`.
- Estratégia registrada em `identity_strategy`.

## Identidade de `legal_demands`
- `source_entity_key = sha256(source_type|case_number_normalized|process_number_core)`.
- `source_occurrence_key = sha256(source_type|case_number_normalized|process_number_core|service_type|source_started_at)`.

## Campos usados no `source_hash`
- `external_status`
- `external_flow_status`
- `subject`
- `service_type`
- `description`
- `source_analysis_at`
- `source_due_at`
- `source_executed_at`
- `source_changed_at`
- `origin_area_name`
- `target_area_name`
- `target_person_name`
- `requesting_responsible_name`
- `responsible_area_name`
- `opposing_party`
- `process_manager`
- `required_area`
- `city`
- `region`
- `regional`
- `observation`

## Campos que não entram na identidade
- `description`
- `observation`
- status externos
- prazos
- responsável atual

Esses campos podem alterar `source_hash`, mas não devem gerar nova demanda.

## Fluxo de importação
1. Command inicia lote (`legal_import_batches`).
2. Fonte externa R3 lê e normaliza via `toNormalizedArray()`.
3. Service gera `identity_key`, `source_entity_key`, `source_occurrence_key`, `source_hash`.
4. Resolve/cria `legal_cases`.
5. Resolve/cria/atualiza `legal_demands`.
6. Registra `imported` para novos e `updated_from_source` quando `source_hash` muda.
7. Cria snapshot em `legal_source_snapshots`.
8. Fecha lote com estatísticas.

## Fluxo de ausência
- Ao final de cada lote por fonte, demandas não vistas viram `missing`:
  - `source_presence_status=missing`
  - `missing_since` (se nulo)
  - `missing_count += 1`
  - `last_missing_batch_id`
  - evento `source_missing`

## Fluxo de retorno
- Quando demanda `missing` reaparece:
  - `source_presence_status=present`
  - `missing_since=null`
  - `last_returned_batch_id`
  - evento `source_returned`
- Se já encerrada internamente, não reabre automaticamente; pode marcar `needs_identity_review`.

## Papel de snapshots
- Guardar `raw_payload` e `normalized_payload` por leitura de origem.
- Permitir auditoria temporal de mudanças.

## Papel de eventos
- Timeline operacional e de importação (`imported`, `updated_from_source`, `source_missing`, `source_returned`).

## Papel de batches
- Consolidar estatísticas por execução/fonte:
  - total/new/updated/unchanged/missing/returned/failed
  - status e tempo de execução

## Execução dos commands
- `php artisan legal:import-subsidies [--dry] [--limit=] [--since=] [--force-snapshot] [--no-missing-check]`
- `php artisan legal:import-sentences [--dry] [--limit=] [--since=] [--force-snapshot] [--no-missing-check]`
- `php artisan legal:import-injunctions [--dry] [--limit=] [--since=] [--force-snapshot] [--no-missing-check]`
- `php artisan legal:import-all [--source=injunction|sentence|subsidy] [--dry] [--limit=] [--since=] [--force-snapshot] [--no-missing-check]`

## Painel mínimo operacional
- `php artisan legal:metrics --days=7`
- Conferir:
  - volumes por `internal_status`
  - pendências por responsável
  - demandas com/sem anexos e comentários
  - saúde dos lotes de importação

## Consultas de validação pós-importação

### Duplicidade de casos
```sql
SELECT
    case_number_normalized,
    process_number_core,
    COUNT(*) AS total
FROM legal_cases
GROUP BY case_number_normalized, process_number_core
HAVING COUNT(*) > 1;
```

### Duplicidade de demandas
```sql
SELECT
    source_occurrence_key,
    COUNT(*) AS total
FROM legal_demands
GROUP BY source_occurrence_key
HAVING COUNT(*) > 1;
```

### Casos com múltiplas demandas
```sql
SELECT
    legal_case_id,
    COUNT(*) AS total_demands
FROM legal_demands
GROUP BY legal_case_id
HAVING COUNT(*) > 1;
```

## Critérios para ativar `unique` depois
- Ausência de duplicidades em produção por janela mínima acordada.
- Reprocessamentos idempotentes validados com `--dry` e execução real.
- Auditoria de eventos/snapshots sem divergências.
- Plano de rollback de migration definido antes de aplicar `unique`.
