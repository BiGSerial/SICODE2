# Painel da equipe (gerência)

Serviços → Relatórios → **PAINEL DA EQUIPE** (`/reports/team-panel`, rota `reports.team_panel`, gate `can:management`). É o "Meu painel" ([../home/MEU_PAINEL.md](../home/MEU_PAINEL.md)) sobre **todos os usuários**, para responder às perguntas da gestão: quem está com o quê, há quanto tempo, quem produz, quem está parado, como cada empresa e atividade se comparam.

## Onde está o código

| Camada | Arquivo |
|---|---|
| Componente Livewire (filtros e orquestração) | `app/Http/Livewire/Reports/TeamPanel.php` + `resources/views/livewire/reports/team-panel.blade.php` |
| Dados (tela e Excel) | `app/Services/Reports/TeamPanel/TeamPanelAnalytics.php` |
| Gráficos (config Chart.js) | `app/Services/Reports/TeamPanel/TeamPanelCharts.php` |
| JS / CSS | os do Meu painel: `resources/js/home/my-panel.js` e `resources/sass/home/_my-panel.scss` (mesmo evento `my-panel:charts`, atualização in-place) |
| Excel | `app/Jobs/Reports/TeamPanelExportJob.php`, `app/Exports/Reports/TeamPanel/TeamPanelExport.php` (aba Resumo = `MyPanelSummarySheet` com `layout`) |
| Testes | `tests/Feature/Reports/TeamPanelTest.php` |

## Filtros

Empresa (`productions.company_id`), atividade, pessoa (`productions.user_id`), período (mesmos atalhos do Meu painel, máx. 366 dias) e "Incluir RI". Empresa → atividade → pessoa são em cascata: ao trocar um, o que deixou de existir naquele recorte é limpo. Clicar no nome de uma pessoa ou empresa nas tabelas filtra o painel nela. Os filtros vão na URL (`empresa`, `servico`, `usuario`, `periodo`, `dtin`, `dtout`, `ri`).

## Regras

- Tempo, RI e "sem SLA": as do Meu painel (`MyPanelAnalytics`, cujos helpers estáticos `informedSql/sinceSql/aggregateSql/group` são reaproveitados).
- **Média da equipe** = notas por pessoa em cada dia trabalhado **dentro do filtro aplicado** (`notas ÷ pessoa-dias`). Não usa cache (sai da mesma consulta).
- Atividades abertas sem `user_id` aparecem como "Sem responsável" (cartão, listas e Excel).
- "Sem conclusões no período": pessoa que concluiu algo ou tinha abertas nos últimos 6 meses (`ACTIVE_MONTHS`) e não concluiu nada no período.
- Gráficos de ranking mostram as 10 maiores (`TeamPanelCharts::TOP`); as tabelas e o Excel trazem todas.

## Excel

Botão **Exportar Excel** → `TeamPanelExportJob` (fila `exports`; recusa quem não tem `management`) → `exports/{AAAAMMDDHHMMSS}_{id}_painel_equipe.xlsx` + `SystemNotification`. Abas: Resumo · Pessoas · Empresas · Produtividade · Etapas · Abertas · Abertas por pessoa · Abertas por atividade · Por situação · Diário · Mensal · Pessoa × Atividade · Concluídas (corta em 50.000 linhas e avisa na descrição). Mesmo visual (`DashboardTableSheet`/`DashboardTheme`).

## Contrato dos gráficos

`TeamPanelCharts::all()` devolve `daily`, `monthly`, `situation`, `users`, `load`, `companies`, `productivity`. Quantidade e ordem dos datasets são fixas por gráfico; o JS atualiza por índice e nunca limpa o gráfico (CLAUDE.md, padrão Chart.js).

## Carregamento e desempenho

`productions` tem ~400 mil linhas. Sem índice por período, cada consulta de `completed_at`/`att_at` varria a tabela (7 a 10 s cada; o painel inteiro levava ~65 s e dava 504 no nginx).

- **Índices** (migration `2026_10_26_120000_add_period_indexes_to_productions_table`): `(completed, completed_at)` e `att_at`. Medido na base real: `daily` 38 s → 0,0 s e `production` 45 s → 1,1 s. **Rodar a migration em cada ambiente** (QA e produção); ela é aditiva e leva poucos segundos. O Meu painel também ganha.
- **Entrega em partes** (`TeamPanelAnalytics::PARTS`): `open` → `daily` → `production` → `monthly`. A tela abre na hora com cabeçalho e filtros; `loadNext` libera uma parte por resposta (encadeado pelo `wire:init` de um elemento com `wire:key` novo a cada passo) e o que ainda não chegou é esqueleto (`team-panel-skeleton.blade.php`, `.tp-skel*` em `_my-panel.scss`). Cada cartão/bloco declara de quais partes depende (`$ok('open', 'daily')` na view).
- **Cache** de 2 min por parte, filtro e dia (`CACHE_TTL`); listas dos filtros 10 min. Trocar de filtro e voltar, ou reabrir a tela, não recalcula.
- **Troca de filtro**: recalcula tudo (com cache) e um esqueleto cobre o conteúdo (`.tp-skeleton-overlay`, `wire:loading`); os canvases dos gráficos não são tocados (padrão in-place do CLAUDE.md).
- A série mensal agrupa por mês e atividade e aplica os grupos em PHP (sem `JOIN` com `services` linha a linha); as listas dos filtros leem `DISTINCT` pelo índice (3 s → 0,1 s). Animação do esqueleto desligada com `prefers-reduced-motion`.
