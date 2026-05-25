# Etapa 03 - Importação, normalização e deduplicação

## Objetivo

Criar o processo de importação das três fontes externas para dentro do SICODE, consolidando os campos comuns e preservando o payload bruto.

## Princípio central

O importador deve ser idempotente.

Rodar a importação duas vezes com a mesma fonte não deve duplicar demandas.

## Fontes previstas

```txt
liminar
sentence
subsidy
```

## Fluxo de importação

```txt
1. Iniciar batch de importação.
2. Ler linhas da fonte.
3. Normalizar campos.
4. Normalizar número do processo.
5. Localizar ou criar legal_case.
6. Calcular source_record_key.
7. Calcular source_hash.
8. Localizar ou criar legal_demand.
9. Atualizar campos comuns.
10. Gravar snapshot se necessário.
11. Registrar evento.
12. Marcar demandas antigas não vistas como missing.
13. Fechar batch com estatísticas.
```

## Normalização recomendada

### Número do processo

Criar função:

```txt
normalizeProcessNumber(value)
```

Responsabilidades:

- remover espaços;
- remover pontuação se necessário;
- padronizar zeros à esquerda quando aplicável;
- preservar versão original em campo separado se necessário.

### Texto/assunto

Criar função:

```txt
normalizeText(value)
```

Responsabilidades:

- trim;
- converter múltiplos espaços em um;
- remover caracteres invisíveis;
- preservar acentos;
- converter `NULL`, `null`, string vazia e hífen para `null` quando fizer sentido.

### Data

Criar função:

```txt
parseExternalDate(value)
```

Responsabilidades:

- aceitar `08/04/2026 03:00:00`;
- aceitar `2026-04-15 10:55:07.405`;
- aceitar data sem hora;
- retornar `Carbon|null`;
- registrar erro de parse no batch se inválido.

## Chave da demanda

Criar uma chave que represente uma demanda única.

Sugestão inicial:

```txt
source_record_key = hash(
    source_type
    + source_external_id
    + process_number_normalized
    + normalized_subject
    + source_started_at
    + source_redirected_at
)
```

Alternativa mais estável, caso as datas mudem demais:

```txt
source_record_key = hash(
    source_type
    + source_external_id
    + process_number_normalized
    + normalized_subject
    + target_area_name
)
```

## Hash de alteração

Criar hash do conteúdo relevante:

```txt
source_hash = hash(
    source_type
    + source_external_id
    + process_number_normalized
    + external_status
    + legal_responsible_name
    + law_firm_name
    + origin_area_name
    + target_area_name
    + target_person_name
    + subject
    + description
    + source_started_at
    + source_due_at
    + external_flow_status
)
```

## Regras de criação/atualização

### Caso 1 - Demanda nova

Condição:

```txt
source_record_key não existe
```

Ação:

```txt
criar legal_demand
internal_status = new_imported
source_presence_status = present
first_seen_at = now()
last_seen_at = now()
registrar evento imported
gravar snapshot
```

### Caso 2 - Demanda já existe e não mudou

Condição:

```txt
source_record_key existe
source_hash igual
```

Ação:

```txt
atualizar last_seen_at
source_presence_status = present
não criar nova demanda
opcional: não gravar snapshot repetido
incrementar unchanged_rows
```

### Caso 3 - Demanda já existe e mudou

Condição:

```txt
source_record_key existe
source_hash diferente
```

Ação:

```txt
atualizar campos normalizados
atualizar source_hash
atualizar last_seen_at
source_presence_status = present
registrar evento updated_from_source
gravar snapshot
```

### Caso 4 - Demanda sumiu da origem

Condição:

```txt
demanda daquela source_type existia antes
não apareceu no batch atual
internal_status não está closed_external/cancelled/ignored
```

Ação:

```txt
source_presence_status = missing
missing_since = now(), se ainda estiver null
registrar evento source_missing
```

### Caso 5 - Demanda voltou

Condição:

```txt
source_record_key existe
source_presence_status = missing
voltou no batch atual
```

Ação:

```txt
source_presence_status = returned ou present
missing_since = null
registrar evento source_returned
```

### Caso 6 - Demanda encerrada internamente voltou

Condição:

```txt
source_record_key existe
internal_status em closed_internal ou closed_external
voltou no batch atual
```

Ação recomendada:

```txt
internal_status = reopened
registrar evento reopened_from_source
manter histórico anterior
não criar nova demanda automaticamente
```

## Serviços/classes sugeridas

```txt
LegalImportService
LegalSourceNormalizer
LegalDemandKeyGenerator
LegalDemandHashGenerator
LegalCaseUpserter
LegalDemandUpserter
LegalSnapshotRecorder
LegalDemandEventLogger
LegalMissingMarker
```

## Commands sugeridos

```bash
php artisan legal:import-liminares
php artisan legal:import-sentences
php artisan legal:import-subsidies
php artisan legal:import-all
```

Opções úteis:

```bash
--dry
--source=liminar
--limit=100
--since=2026-05-01
--force-snapshot
--no-missing-check
```

## Resultado esperado no terminal

```txt
Fonte: liminar
Batch: 123
Total lidas: 120
Novas: 8
Atualizadas: 4
Sem alteração: 104
Ausentes marcadas: 3
Falhas: 1
Tempo: 4.82s
```

## Checklist da etapa

- [ ] Criar normalizador de número de processo.
- [ ] Criar normalizador de texto.
- [ ] Criar parser de datas.
- [ ] Criar gerador de `source_record_key`.
- [ ] Criar gerador de `source_hash`.
- [ ] Criar service de importação.
- [ ] Criar command para liminares.
- [ ] Criar command para sentenças/cumprimentos.
- [ ] Criar command para subsídios.
- [ ] Criar command geral.
- [ ] Criar batch de importação.
- [ ] Criar snapshots.
- [ ] Criar eventos de importação.
- [ ] Marcar demandas ausentes como `missing`.
- [ ] Tratar reaparecimento.
- [ ] Implementar modo `--dry`.

## Observabilidade da etapa

O agente deve entregar:

- [ ] Log estruturado por batch.
- [ ] Contadores de novas/atualizadas/inalteradas/ausentes/falhas.
- [ ] Tempo total da importação.
- [ ] Tempo médio por linha.
- [ ] ID do batch exibido no terminal.
- [ ] Registro de erro por linha inválida.
- [ ] Evento `imported` para novas demandas.
- [ ] Evento `updated_from_source` para alterações.
- [ ] Evento `source_missing` para ausências.
- [ ] Evento `source_returned` para reaparecimentos.
- [ ] Teste provando que rodar duas vezes não duplica.
- [ ] Teste provando que mudança de prazo atualiza e gera snapshot.
- [ ] Teste provando que sumiço da origem não encerra internamente.
