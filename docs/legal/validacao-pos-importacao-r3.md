# Validação Pós-Importação R3

## Comando utilitário
- `php artisan legal:validate-import`
- Opcional: `php artisan legal:validate-import --limit=50`

O comando executa e exibe:
1. Duplicidade de casos (`case_number_normalized + process_number_core`).
2. Duplicidade de demandas (`source_occurrence_key`).
3. Casos com múltiplas demandas.
4. Demandas por fonte.
5. Ausências (`missing`) por fonte.

## Consultas SQL equivalentes

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

### Demandas por fonte
```sql
SELECT
    source_type,
    COUNT(*) AS total
FROM legal_demands
GROUP BY source_type
ORDER BY source_type;
```

### Ausências por fonte
```sql
SELECT
    source_type,
    COUNT(*) AS total_missing
FROM legal_demands
WHERE source_presence_status = 'missing'
GROUP BY source_type
ORDER BY source_type;
```
