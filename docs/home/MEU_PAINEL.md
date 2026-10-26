# Meu painel (home do usuário)

Tela inicial (`/home`) do usuário interno. Substitui o antigo Dashboard (cartões de postes/notas + atividade recente). Mostra **o que está com o usuário, há quanto tempo e como foi a produção**, classificado por **atividade** (serviço).

## Onde está o código

| Camada | Arquivo |
|---|---|
| Componente Livewire (só filtros e orquestração) | `app/Http/Livewire/Home/Dashboard/MyPanel.php` + `resources/views/livewire/home/dashboard/my-panel.blade.php` |
| Dados (tudo que a tela e o Excel mostram) | `app/Services/Home/MyPanel/MyPanelAnalytics.php` |
| Gráficos (config Chart.js, só dados) | `app/Services/Home/MyPanel/MyPanelCharts.php` |
| JS (criação e atualização **in-place** dos gráficos) | `resources/js/home/my-panel.js` |
| CSS | `resources/sass/home/_my-panel.scss` (reaproveita tokens/hero/kpi/chart-card do `.closure-dash`) |
| Barras CSS + "Ver como tabela" | `<x-home.bar-list>` (`resources/views/components/home/bar-list.blade.php`) |
| Excel (Job + export) | `app/Jobs/Home/PersonalProductionsJob.php`, `app/Exports/Home/MyPanel/` |
| Testes | `tests/Feature/Home/MyPanelTest.php` |

## Visual

Mesma linguagem do Dashboard da Parceira (`livewire/partner/main.blade.php`): fundo com brilho suave, hero escuro em degradê, **cartões-destaque** coloridos com ícone e mini-gráfico (`<x-home.hero-card>` + `<x-home.spark>`), títulos de seção em caixa alta, painéis com cabeçalho em degradê e barras com degradê. Gráficos Chart.js com preenchimento em degradê (`gradient` no dataset), rosca com total no centro (`plugins.mpCenter`) e barras "você × equipe". Cor só por variável no SCSS; nada de CSS/JS inline.

## Regras de tempo (decisão do produto, 2026-10-25)

- **Não há prazo/SLA no painel**: ele mede e ordena, do maior para o menor, sempre mostrando a **atividade**.
- Atividade **aberta**: `completed = false`, `rejected = false`, `user_id` = usuário. O tempo conta desde a **ATT** (`productions.att_at`; se faltar, o despacho). A data de ATT é sempre exibida.
- **Pós-obra com informe** — `fiscalizacao`, `pagamento` (Medição) e `publicacao` (`MyPanelAnalytics::INFORM_FOLDERS`): o tempo conta desde o **`informed_at` do informe** da Nota (`work_reports`, não cancelado; o mais recente). Sem informe, cai na ATT.
- **RI** (`d5 = true`) só entra com o filtro "Incluir RI".
- Prazos de referência existentes (não usados no painel hoje): Publicação 2 dias; Fiscalização até o fim da Medição 8 dias (relatório pós-obra, `PostWorkProcessReportService::limits()`, depende do `sicode.ruleset`); RI 2 dias.

## Produtividade

- Período padrão: últimos 30 dias (atalhos 7 dias, mês atual, mês anterior, ou datas livres; máximo 366 dias). Comparado ao período anterior de mesma duração.
- Por atividade: notas, postes (`postes_u`), dias trabalhados, notas/dia, **média da equipe** (notas por pessoa em cada dia trabalhado, mesma atividade e período) e tempo médio.
- Sem meta fixa: a referência é a média da equipe. A média da equipe varre a base toda e fica em **cache de 10 min** (`MyPanelAnalytics::TEAM_CACHE_TTL`).
- Etapas (concluídas do período): fila = despacho→ATT; execução = ATT→conclusão menos `stopped` (pausas); parada = `stopped`.

## Excel

Botão **Exportar Excel** → `PersonalProductionsJob` (fila `exports`) → arquivo em `exports/users/{id}/…_meu_painel.xlsx` (disco local, exceção de exportação) + `SystemNotification` com o link. Abas: Resumo (cartões, filtros, índice com link) · Abertas · Abertas por atividade · Por situação · Produtividade · Etapas · Diário · Mensal · Concluídas. Visual compartilhado com o Excel do Painel analítico do Encerramento (`DashboardTableSheet`/`DashboardTheme`, opção `brand`). Datas são datas reais do Excel; tempos em dias.

## Contrato dos gráficos

`MyPanelCharts::all()` devolve a config de `daily`, `week` e `monthly`. A **quantidade e a ordem dos datasets são fixas** por gráfico para que `my-panel.js` atualize por índice (`Object.assign`, `push`, `splice`), nunca substituindo `chart.data.datasets` e nunca limpando o gráfico: sem dados, o aviso (`.mp-chart-empty`) fica por cima.

## Versão da gerência

O mesmo painel sobre a equipe toda (filtros por empresa, atividade e pessoa) está em Serviços → Relatórios → Painel da equipe: ver [../reports/PAINEL_DA_EQUIPE.md](../reports/PAINEL_DA_EQUIPE.md). Os helpers `MyPanelAnalytics::{informedSql,sinceSql,aggregateSql,group}`, `openByActivity/ageBuckets/openByStatus/stageRow` e `MyPanelCharts::options` são públicos para essa reutilização.
