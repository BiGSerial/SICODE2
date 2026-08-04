# Auditoria de portabilidade de consultas SQL

Data: 2026-07-31

## Objetivo

Registrar pontos do sistema que nao estao 100% apoiados em Eloquent/Schema Builder e podem exigir revisao se o banco principal sair de MariaDB/MySQL para PostgreSQL ou Azure SQL Server.

Esta auditoria nao classifica todo uso de `DB::table()` como erro. O risco real esta em SQL cru, funcoes especificas de dialeto, DDL manual, ordenacao por expressao booleana, views e dependencias explicitas de conexoes SQL Server.

## Resumo executivo

Foram encontrados usos relevantes de SQL nao portavel em quatro grupos:

- Migrations com `DB::statement`, `DB::unprepared`, `ALTER TABLE ... MODIFY`, `CREATE OR REPLACE VIEW` e consultas a `information_schema`.
- Telas e relatorios com funcoes MySQL como `TIMESTAMPDIFF`, `DATEDIFF`, `CURDATE`, `DATE_FORMAT`, `DATE_ADD`, `STR_TO_DATE`, `IFNULL`, `GROUP_CONCAT`, `YEAR` e `MONTH`.
- Ordenacoes e filtros com `orderByRaw`, `whereRaw`, `selectRaw` e expressoes booleanas que funcionam em MySQL, mas podem falhar ou mudar resultado em SQL Server.
- Integracoes intencionais com SQL Server (`sqlsrv1`, `sqlsrv2`, `sqlsrv3`) que devem ser tratadas como fronteira externa, nao como parte do banco principal.

Antes de migrar o banco principal, os pontos de alto risco devem ser isolados por driver ou reescritos para APIs portaveis.

## Alto risco

| Area | Arquivo | Evidencia | Risco | Acao recomendada |
| --- | --- | --- | --- | --- |
| DDL MySQL em migration | `database/migrations/2024_10_09_143344_modify_order_id_on_viabilities_table.php:25` | `ALTER TABLE ... MODIFY COLUMN ... AFTER ...` | `MODIFY COLUMN` e `AFTER` sao sintaxe MySQL. | Reescrever com `Schema::table()` ou criar branch por driver. |
| DDL MySQL em migration | `database/migrations/2026_01_12_000001_fix_ads_requests_uuid_columns.php:14` | `ALTER TABLE ads_requests MODIFY ...` e leitura de `information_schema.KEY_COLUMN_USAGE` | Sintaxe de alteracao e metadados mudam em PostgreSQL/SQL Server. | Usar Schema Builder/Doctrine DBAL ou adaptador de metadados por driver. |
| DDL MySQL em migration | `database/migrations/2026_03_17_130000_update_project_review_findings_for_structured_analysis.php:30` | `ALTER TABLE project_review_findings MODIFY item_id ...` | Migration nao executa fora de MySQL/MariaDB. | Separar migrations por banco ou substituir por API de schema. |
| DDL MySQL em migration | `database/migrations/2026_03_26_130000_alter_fail_reason_in_update_execution_logs_table.php:13` | `ALTER TABLE update_execution_logs MODIFY fail_reason ...` | Implementacao cobre somente MySQL/MariaDB. | Definir comportamento para PostgreSQL e SQL Server antes da migracao. |
| Enum MySQL | `database/migrations/2026_03_30_180000_add_alterar_to_project_review_findings_action_type_enum.php:20` | `ALTER TABLE ... ENUM(...)` | `ENUM` e alteracao de enum sao especificos. | Trocar para `string` + check constraint portavel ou branch por driver. |
| Views | `database/migrations/2025_10_16_164819_create_user_visibility_view.php:9` | `CREATE OR REPLACE VIEW user_visibility_current` | Sintaxe de view, `NOW()` e regras de replace variam. | Encapsular DDL da view por driver e testar em cada banco alvo. |
| Views | `database/migrations/2026_02_24_120100_rebuild_user_visibility_view_with_observations.php:9` | `DB::unprepared("CREATE OR REPLACE VIEW ...")` | Mesmo risco acima, com rebuild de view em producao. | Criar gerador de SQL da view por driver. |
| Metadata de indice | `database/migrations/2026_03_04_170000_add_active_note_lock_to_work_reports.php:62` | `information_schema.statistics` | Checagem de indice nao e portavel. | Mesma solucao: helper por driver. |
| Metadata de indice | `database/migrations/2026_03_19_121000_update_project_review_findings_unique_for_origin.php:50` | `information_schema.statistics` com `LIMIT 1` | Checagem de indice e `LIMIT` sao especificos do dialeto. | Extrair `hasIndex()` para helper por driver. |
| Metadata de indice | `database/migrations/2026_03_19_210000_update_project_review_findings_unique_for_action_type.php:56` | `information_schema.statistics` com `LIMIT 1` | Mesmo risco. | Usar helper por driver antes de trocar uniques. |
| Metadata de indice | `database/migrations/2026_03_26_120000_update_project_review_findings_unique_for_point_label.php:54` | `information_schema.statistics` com `LIMIT 1` | Mesmo risco. | Usar helper por driver antes de trocar uniques. |
| Metadata de indice | `database/migrations/2026_07_31_090000_add_external_organ_release_performance_indexes.php:73` | `information_schema.statistics` | `statistics` e colunas consultadas sao MySQL/MariaDB. | Extrair `indexExists()` para helper por driver antes de rodar fora de MySQL. |
| Metadata de FK | `database/migrations/2026_01_12_000001_fix_ads_requests_uuid_columns.php:42` | `information_schema.KEY_COLUMN_USAGE` e `DROP FOREIGN KEY` | Nome e DDL de foreign key mudam por banco. | Usar metadata/DDL por driver ou nomes explicitos de constraints via Schema Builder. |
| Coluna gerada | `database/migrations/2026_03_04_170000_add_active_note_lock_to_work_reports.php:13` | `storedAs('CASE WHEN canceled = 0 THEN note_id ELSE NULL END')->after('note_id')` | Colunas geradas existem nos bancos alvo, mas sintaxe, persistencia e `after` variam. | Validar por driver ou substituir por coluna materializada mantida pela aplicacao/job. |

### Migrations com `information_schema`

Varredura em `database/migrations` encontrou estes usos diretos de `information_schema`:

- `database/migrations/2026_01_12_000001_fix_ads_requests_uuid_columns.php:42`: `information_schema.KEY_COLUMN_USAGE`.
- `database/migrations/2026_03_04_170000_add_active_note_lock_to_work_reports.php:62`: `information_schema.statistics`.
- `database/migrations/2026_03_19_121000_update_project_review_findings_unique_for_origin.php:50`: `information_schema.statistics`.
- `database/migrations/2026_03_19_210000_update_project_review_findings_unique_for_action_type.php:56`: `information_schema.statistics`.
- `database/migrations/2026_03_26_120000_update_project_review_findings_unique_for_point_label.php:54`: `information_schema.statistics`.
- `database/migrations/2026_07_31_090000_add_external_organ_release_performance_indexes.php:73`: `information_schema.statistics`.

Todos devem ser tratados como nao portaveis sem uma camada de introspeccao por driver. PostgreSQL e SQL Server tambem possuem `information_schema`, mas as views, nomes de colunas, regras de schema/catalogo e comandos DDL associados nao sao equivalentes aos usados nessas migrations.

### Analise de padronizacao das migrations com `information_schema`

Em migrations, a padronizacao correta nao e "Eloquent puro". Eloquent e orientado a registros/modelos; para DDL, indices e constraints, o padrao Laravel adequado e `Schema`/`Blueprint`. Como o projeto ja expoe `Schema::getIndexes()`, `Schema::getForeignKeys()` e `Schema::getColumns()`, da para remover varias consultas manuais a `information_schema` sem instalar dependencia nova.

| Migration | Consulta atual | Da para padronizar sem SQL cru? | Padrao recomendado | Observacao |
| --- | --- | --- | --- | --- |
| `2026_03_19_121000_update_project_review_findings_unique_for_origin.php` | `SELECT 1 FROM information_schema.statistics ... LIMIT 1` | Sim | Helper `schemaIndexExists($table, $indexName)` baseado em `Schema::getIndexes($table)` | Mantem a logica atual de criar/dropar unique por nome, mas remove dependencia MySQL. |
| `2026_03_19_210000_update_project_review_findings_unique_for_action_type.php` | `SELECT 1 FROM information_schema.statistics ... LIMIT 1` | Sim | Mesmo helper `schemaIndexExists()` | Deve ser compartilhado para evitar quatro copias do mesmo `hasIndex()`. |
| `2026_03_26_120000_update_project_review_findings_unique_for_point_label.php` | `SELECT 1 FROM information_schema.statistics ... LIMIT 1` | Sim | Mesmo helper `schemaIndexExists()` | A troca de unique continua via `Blueprint`, que e aceitavel. |
| `2026_07_31_090000_add_external_organ_release_performance_indexes.php` | `DB::table('information_schema.statistics')->...->exists()` | Sim | Mesmo helper `schemaIndexExists()` | Boa candidata para ser a primeira refatorada, porque e migration recente e isolada. |
| `2026_03_04_170000_add_active_note_lock_to_work_reports.php` | `SELECT 1 FROM information_schema.statistics ... DATABASE() ... LIMIT 1` | Parcialmente | Trocar a checagem por `schemaIndexExists()` | A checagem de indice fica portavel; a coluna `storedAs(...)->after(...)` ainda precisa validacao/branch por banco. |
| `2026_01_12_000001_fix_ads_requests_uuid_columns.php` | `SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE ...` | Parcialmente | Usar `Schema::getForeignKeys($table)` para descobrir constraints, ou preferir nomes explicitos e `dropForeign([...])` | A descoberta da FK pode ser padronizada; `ALTER TABLE ... MODIFY` e `DROP FOREIGN KEY` continuam MySQL e precisam outro tratamento. |

Exemplo de helper recomendado para migrations novas:

```php
private function schemaIndexExists(string $table, string $indexName): bool
{
    return collect(Schema::getIndexes($table))
        ->contains(fn (array $index) => ($index['name'] ?? null) === $indexName);
}
```

Para foreign keys:

```php
private function schemaForeignKeyExists(string $table, string $foreignName): bool
{
    return collect(Schema::getForeignKeys($table))
        ->contains(fn (array $foreign) => ($foreign['name'] ?? null) === $foreignName);
}
```

Quando o objetivo for dropar FK por coluna, preferir primeiro nomes explicitos de constraint nas migrations novas. Descobrir FK por coluna em runtime e possivel com `Schema::getForeignKeys()`, mas os formatos retornados por cada driver devem ser cobertos por teste antes de usar em producao.

Conclusao: os `information_schema.statistics` podem ser substituidos por API Laravel agora. Isso reduz risco para PostgreSQL e Azure SQL Server sem alterar comportamento funcional. Ja `ALTER TABLE ... MODIFY`, `DROP FOREIGN KEY`, `ENUM`, `CREATE OR REPLACE VIEW`, `storedAs()` e `after()` nao ficam resolvidos por Eloquent; esses pontos precisam Schema Builder compativel, helper por driver ou migration separada por banco.

## Funcoes SQL especificas de dialeto

| Area | Arquivo | Evidencia | Risco | Acao recomendada |
| --- | --- | --- | --- | --- |
| SLA/tempos | `app/Services/Reports/ProjectReviewGovernanceExportService.php:454` | `TIMESTAMPDIFF(HOUR, ...)` | MySQL. PostgreSQL usa `EXTRACT(EPOCH FROM ...)`; SQL Server usa `DATEDIFF`. | Centralizar calculo de diferenca de datas por driver ou calcular em PHP quando o volume permitir. |
| SLA/tempos | `app/Services/Legal/LegalObservabilityService.php:127` | `TIMESTAMPDIFF(HOUR, ...)` | MySQL. | Mesmo tratamento de helper por driver. |
| Dashboard parede | `app/Services/Wall/Screen/ProductionScreenDataService.php:422` | `CURDATE()`, `DATE()`, `GREATEST`, `DATEDIFF` | Sintaxe e ordem dos argumentos mudam entre bancos. | Criar expressoes por driver para buckets de idade. |
| Dashboard fixo | `app/Services/Wall/Fixed/ProjectReviewFixedDashboardDataService.php:398` | `GREATEST`, `DATEDIFF`, `CURDATE`, `CAST(... AS CHAR)` | MySQL. | Isolar query em servico com variantes MySQL/PostgreSQL/SQL Server. |
| Historicos mensais | `app/Http/Livewire/Services/Historic.php:205` | `DATE_FORMAT(completed_at, "%Y-%m")` | MySQL. | Preferir filtros por intervalo de datas e formatacao em PHP. |
| Historicos mensais | `app/Http/Livewire/Services/Analises_pre/Historic.php:65` | `DATE_FORMAT(completed_at, "%Y-%m")` | MySQL. | Mesma recomendacao. |
| Home/dashboard | `app/Http/Livewire/Home/Dashboard/Dashboard.php:390` | `DATE_FORMAT(completed_at, "%Y-%m")` | MySQL. | Trocar por `whereBetween` por mes ou helper de truncamento por driver. |
| Agregacao textual | `app/Console/Commands/Tools/RemoveViabDuplicate.php:48` | `GROUP_CONCAT(id)` | MySQL. PostgreSQL usa `string_agg`; SQL Server usa `STRING_AGG`. | Evitar agregacao textual no SQL ou usar helper por driver. |
| Relatorio ADS tacito | `app/Services/Reports/InformAdsTacitReportService.php:237` | `GROUP_CONCAT(DISTINCT ... ORDER BY ... SEPARATOR ', ')` | MySQL com sintaxe propria. | Gerar agregacao por driver ou montar a lista em PHP. |
| Publicacao | `app/Http/Livewire/Services/Publication/Main.php:308` | `DATE_ADD(CURDATE(), INTERVAL ...)`, `STR_TO_DATE(...)` | MySQL. | Converter para datas em PHP/Carbon ou expressao por driver. |
| Despacho publicacao | `app/Http/Livewire/Dispatchs/Publication/Stack.php:701` | subquery com `LIMIT 1` | `LIMIT` nao funciona em SQL Server. | Reescrever com `latestOfMany`, `joinSub` agregado ou branch `TOP 1`/window function. |
| Ordenacao ASCII | `app/Http/Livewire/Services/Oexterno/Actions/InterReturn.php:107` | `CONVERT(name USING ASCII)` | MySQL. | Usar collation configurada, normalizacao em coluna auxiliar ou expressao por driver. |
| Indicadores home | `app/Http/Controllers/HomeController.php:114` | `IFNULL(...)` | MySQL. | Trocar para `COALESCE`, que e aceito nos principais bancos. |

## Ordenacao e filtros raw

Alguns `orderByRaw` sao logicamente simples, mas podem quebrar em SQL Server porque usam resultado booleano direto. Exemplos:

- `app/Http/Livewire/Services/Oexterno/ReleasedWorks.php:301`: `orderByRaw('exported_at IS NOT NULL')`.
- `app/Http/Livewire/Services/Oexterno/ListToPayment.php:200`: `orderByRaw('last_comment_at IS NULL, last_comment_at ASC')`.
- `app/Http/Livewire/Services/Oexterno/ListReturnOE.php:195`: mesmo padrao.
- `app/Http/Livewire/Services/Oexterno/ListUndefined.php:194`: mesmo padrao.
- `app/Http/Livewire/Services/Oexterno/ListTax.php:195`: mesmo padrao.
- `app/Http/Livewire/Partner/WorkedRejectedList.php:176`: `orderByRaw('last_returned_at IS NULL')`.
- `app/Http/Livewire/Dispatchs/Payment/Main.php:903`: `orderByRaw('(fimLancado IS NULL) DESC')`.
- `app/Http/Controllers/Api/DispatchPaymentController.php:178`: mesmo padrao.

Acao recomendada: padronizar um helper como `DatabaseExpression::nullsLast($column, $direction)` que gere `CASE WHEN ... THEN ... END` para SQL Server e a expressao adequada para MySQL/PostgreSQL.

### Analise de padronizacao das consultas de runtime

Para consultas de leitura/escrita em telas, relatorios e jobs, a regra deve ser: usar Eloquent/Query Builder nativo quando o Laravel possui uma API sem perda semantica; quando a consulta depende de funcao SQL inexistente no Builder, isolar a expressao em helper por driver; quando o volume for pequeno ou o resultado for apenas exibicao, preferir calcular em PHP/Carbon depois de buscar os dados.

| Padrao encontrado | Exemplos | Da para manter mesmo resultado com Laravel nativo? | Padrao recomendado |
| --- | --- | --- | --- |
| Filtros por mes com `DATE_FORMAT(col, "%Y-%m") = ?` | `app/Http/Livewire/Services/Historic.php:205`, `app/Http/Livewire/Services/Levantamento/Histviab.php:136` | Sim | Trocar por `whereBetween('completed_at', [$inicioMes, $fimMes])` usando Carbon. Mantem o resultado e aproveita indice em `completed_at`. |
| Filtros por dia com `DATE(col)` | `app/Http/Livewire/Home/Dashboard/Dashboard.php:354` | Sim para filtro | Usar `whereDate()` ou intervalo `startOfDay/endOfDay`. Para listas grandes, intervalo e melhor para indice. |
| Subquery raw com `LIMIT 1` | `app/Exports/Reports/ReturnInternReportExport.php:45`, `app/Http/Livewire/Dispatchs/Publication/Stack.php:701` | Sim | Usar `selectSub()`/`joinSub()` com Query Builder e `limit(1)`, ou relacao Eloquent `oldestOfMany/latestOfMany`. O Laravel compila `limit` conforme o driver. |
| `whereRaw('1 = 0')` ou `whereRaw('0 = 1')` | `app/Jobs/Reports/ExportUserListJob.php:140`, `app/Http/Livewire/Partner/Workedlist.php:159` | Sim | Usar `whereKey([])` em models, `whereIn('id', [])` quando existir chave, ou retornar query com filtro impossivel encapsulado em helper `emptyResult($query)`. |
| `COUNT(*)`, `MIN()`, `MAX()`, `SUM()` simples em grupos | `app/Http/Livewire/Services/Oexterno/ListToPayment.php:126`, `app/Services/Wall/Screen/ProductionScreenDataService.php:416` | Parcialmente | Para relacoes, preferir `withCount`, `withSum`, `withMax`, `withMin`. Para agregacao agrupada arbitraria, Query Builder com `selectRaw('COUNT(*)')` ainda e aceitavel e portavel se nao misturar funcoes de dialeto. |
| `COALESCE` apenas para fallback de texto/numero | `app/Services/Reports/ProjectReviewGovernanceExportService.php:524`, `app/Http/Livewire/Services/Oexterno/Dashboard.php:501` | Parcialmente | Se for exibicao, buscar valor cru e aplicar fallback em PHP/accessor/resource. Se for agrupamento/ordenacao, manter SQL ou criar coluna normalizada/materializada. |
| `IFNULL(...)` | `app/Http/Controllers/HomeController.php:114`, `app/Http/Livewire/Services/Supervision/Main.php:210` | Parcialmente | Substituir por `COALESCE` quando precisar continuar no SQL. Se for apenas exibicao, mover para PHP. |
| Ordenacao por nulos com booleano SQL | `app/Http/Livewire/Services/Oexterno/ListToPayment.php:200`, `app/Http/Livewire/Services/Oexterno/ReleasedWorks.php:301`, `app/Http/Livewire/Dispatchs/Payment/Main.php:903` | Nao ha API nativa completa para `NULLS FIRST/LAST` cross-driver | Criar helper `DatabaseExpression::nullsLast()`/`nullsFirst()` usando `CASE WHEN`. Evitar `col IS NULL` direto em `orderByRaw`. |
| `LOWER(...) LIKE`, `LOWER(name)` e normalizacao textual | `app/Http/Livewire/ProjectReview/Queue.php:1548`, `app/Http/Livewire/Partner/Workedlist.php:283` | Parcialmente | Para filtros simples, usar collation/acento/case adequada no banco ou busca normalizada em coluna auxiliar. Laravel nao resolve diferenca de collation sozinho. |
| `CONVERT(name USING ASCII)` | `app/Http/Livewire/Services/Oexterno/Actions/InterReturn.php:107` | Nao | Trocar por ordenacao comum `orderBy('name')` se aceitavel, ou criar coluna `sort_name` normalizada pela aplicacao. |
| `DATE_FORMAT` em `select` para label | `app/Http/Livewire/Partner/Main.php:534`, `app/Http/Livewire/Engineers/Ads/Dashboard.php:236` | Sim se o label nao precisa ser calculado no banco | Selecionar `MIN(data)`/data crua e formatar com Carbon no PHP. |
| Agrupamento mensal com `YEAR()`/`MONTH()` | `app/Http/Controllers/HomeController.php:71`, `app/Http/Livewire/Home/Dashboard/Dashboard.php:326`, `app/Http/Livewire/Services/Oexterno/Dashboard.php:375` | Parcialmente | Para periodos pequenos, buscar por intervalo e agrupar em collection por `format('Y-m')`. Para volumes grandes, helper de truncamento por driver (`DATE_TRUNC`, `DATEFROMPARTS`, `DATE_FORMAT`). |
| Diferenca de datas com `TIMESTAMPDIFF`, `DATEDIFF`, `CURDATE`, `NOW`, `GETDATE` | `app/Services/Reports/ProjectReviewGovernanceExportService.php:587`, `app/Http/Livewire/Reports/CancellationDashboard.php:140`, `app/Services/Reports/AdsRequestedReportService.php:398` | Parcialmente | Para listas paginadas/exports pequenos, calcular com Carbon apos a consulta. Para medias, buckets e filtros grandes, criar helper por driver para `dateDiff(unit, from, to)`. |
| `DATE_ADD(CURDATE(), INTERVAL ...)` e `STR_TO_DATE(...)` | `app/Http/Livewire/Services/Publication/Main.php:308`, `app/Http/Livewire/Dispatchs/Publication/Main.php:580`, `app/Services/Wall/Screen/ProductionScreenDataService.php:483` | Parcialmente | Se a data vem de campos estruturados, montar com Carbon em PHP. Se precisa filtrar/ordenar em SQL, helper por driver ou coluna persistida com data calculada. |
| `GROUP_CONCAT` | `app/Services/Reports/InformAdsTacitReportService.php:237`, `app/Console/Commands/Tools/RemoveViabDuplicate.php:48` | Nao por API nativa universal | Usar `pluck()->implode()` quando o conjunto for pequeno; para grande volume, helper por driver (`GROUP_CONCAT`, `string_agg`, `STRING_AGG`). |
| Razoes/percentuais em `whereRaw` | `app/Http/Livewire/ProjectReview/Queue.php:300`, `app/Jobs/Reports/ExportProjectReviewQueueListJob.php:156` | Parcialmente | Pode ser mantido em Query Builder com `whereColumn` e comparacoes aritmeticas simples quando possivel. Para expressoes com divisao/zero, criar helper e testes por driver. |

Conclusao: ha bastante coisa que pode sair de SQL cru sem mudar resultado, principalmente filtros por data, labels formatados, subqueries `LIMIT 1`, resultados vazios e alguns agregados de relacionamento. Ja dashboards com bucket/SLA, agregacoes textuais, ordenacao `NULLS FIRST/LAST`, parse de data textual e filtros por diferenca de datas nao possuem equivalente Eloquent universal; nesses casos o caminho tecnico e helper de expressao por driver ou calculo/materializacao fora do SQL.

### Prioridade sugerida para padronizacao de consultas

1. Substituir filtros `DATE_FORMAT(...)=?` por `whereBetween` com Carbon. Baixo risco e melhora uso de indice.
2. Substituir subqueries raw com `LIMIT 1` por `selectSub()`/relacoes `latestOfMany` ou `oldestOfMany`.
3. Criar helper unico para ordenacao de nulos e trocar os `orderByRaw('... IS NULL')`.
4. Trocar `IFNULL` por `COALESCE` quando ainda precisar SQL; quando for exibicao, mover fallback para PHP.
5. Criar helper `dateDiff()`/`dateBucket()` por driver antes de atacar dashboards e relatorios com `TIMESTAMPDIFF`/`DATEDIFF`.
6. Decidir caso a caso entre agregacao em PHP ou helper por driver para `GROUP_CONCAT`.

## Blocos com maior concentracao de query builder/raw

Estes arquivos nao precisam ser descartados, mas devem ser priorizados em testes de portabilidade porque concentram muitos `DB::table`, `selectRaw`, `whereRaw`, `joinSub`, `leftJoinSub` e agregacoes:

- `app/Console/Commands/Tools/SyncNoteInformFlows.php`: rotina de sincronismo com subconsultas, agregacoes `CASE WHEN`, `COALESCE`, `MAX`, `upsert` e filtros raw.
- `app/Console/Commands/SqlLog/SyncFiveNotesReportToSqlServer.php`: exportacao/sincronismo para SQL Server com query builder pesado e agregacoes.
- `app/Services/Reports/ProjectReviewGovernanceExportService.php`: relatorio de governanca de aprovacao de projeto com muitos `DB::table`, `selectRaw`, `TIMESTAMPDIFF` e `joinSub`.
- `app/Services/Reports/AdsRequestedReportService.php`: mistura consultas do banco principal com consulta explicita `sqlsrv2`; usa sintaxe MySQL e SQL Server em metodos diferentes.
- `app/Services/Wall/Screen/ProductionScreenDataService.php`: buckets e parse de datas com funcoes MySQL, inclusive `STR_TO_DATE`.
- `app/Services/Wall/Fixed/ProjectReviewFixedDashboardDataService.php`: indicadores de aprovacao de projeto com `TIMESTAMPDIFF`, `DATEDIFF`, `CURDATE`, `GREATEST`.
- `app/Http/Livewire/Dispatchs/Payment/Main.php` e `app/Http/Livewire/Dispatchs/Payment/Stack.php`: subconsultas e ordenacao raw da fila de pagamento.
- `app/Http/Livewire/Services/Oexterno/*`: dashboards/listas usam subconsultas e ordenacoes por `IS NULL`; a tela de obras liberadas tambem possui filtro raw por ciclo de custo.

## Dependencias explicitas de SQL Server

O projeto ja possui integracoes SQL Server que devem ser preservadas como fronteira externa:

- Modelos em `app/Models/SicodeSql/*` usam `protected $connection = 'sqlsrv2'`.
- Modelos em `app/Models/Edp_depc/*` usam `protected $connection = 'sqlsrv1'`.
- Modelos em `app/Models/Edp_cipqa/*` usam `protected $connection = 'sqlsrv3'`.
- Rotinas como `app/Console/Commands/SqlLog/SyncNoteInformFlowsToSqlServer.php`, `app/Console/Commands/SqlLog/SyncPartialsInformsToSqlServer.php`, `app/Console/Commands/SqlLog/SyncFiveNotesReportToSqlServer.php` e `app/Console/Commands/SqlLog/SyncLogProtestJobsToSqlServer.php` gravam/leem `sqlsrv2`.
- `app/Http/Livewire/Responsible/AdsRequests.php`, `app/Http/Livewire/Partner/AdsRequests.php` e `app/Console/Commands/Ads/GenerateTacitAds.php` tambem usam `DB::connection('sqlsrv2')`.

Esses pontos nao impedem migrar o banco principal, mas exigem configuracao e testes de integracao separados.

## Recomendacoes para a migracao

1. Criar uma camada pequena de dialeto para SQL cru inevitavel: diferenca de datas, truncamento por dia/mes, ordenacao com nulos, agregacao textual e checagem de indice.
2. Trocar migrations com `ALTER TABLE ... MODIFY`, `ENUM`, `information_schema.statistics` e views cruas por Schema Builder ou migrations por driver.
3. Substituir `IFNULL` por `COALESCE` onde possivel.
4. Evitar `DATE_FORMAT` em filtros. Preferir `whereBetween` com Carbon e formatar a exibicao em PHP.
5. Reescrever `LIMIT 1` em subquery para relacao Eloquent, `joinSub` agregado ou window function conforme banco alvo.
6. Criar testes de smoke para os blocos de maior risco antes de decidir o banco: filas de despacho, OE, dashboards Wall, relatórios de aprovacao de projeto, ADS e sincronismos SQL Server.
7. Adicionar matriz de CI temporaria com o banco candidato antes da migracao final.

## Observacao sobre a tela OE

A otimizacao recente da tela `Obras liberadas para OE` reduziu carga removendo fechamento automatico durante o render e adicionou indices por migration. Se o banco principal mudar, a migration `2026_07_31_090000_add_external_organ_release_performance_indexes.php` deve ser ajustada porque sua checagem defensiva de indice usa `information_schema.statistics`, especifica de MySQL/MariaDB.
