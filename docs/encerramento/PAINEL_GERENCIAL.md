# Painel analítico do Encerramento (visão da gestão)

Rota `/dispatch/{atividade}/dashboard` (Gestão do ciclo → Painel analítico), componente `Closure\Dispatch\Dashboard`. Segue o [PADRAO_DASHBOARDS.md](../PADRAO_DASHBOARDS.md). Responde à gestão: **quais são os principais problemas, quem os causa (por parceira e por atividade) e se estamos melhorando ou piorando em relação ao mês anterior.**

## Camadas

| Camada | Arquivo |
|---|---|
| Componente Livewire (só filtros e orquestração) | `app/Http/Livewire/Closure/Dispatch/Dashboard.php` |
| Dados gerenciais (tela e Excel leem daqui) | `app/Services/Closure/Analytics/ClosureManagementAnalytics.php` |
| Dados operacionais (matriz, obras críticas, prioridades) | `app/Services/Closure/Analytics/ClosureDispatcherAnalytics.php` |
| Gráficos (só dados, datasets fixos) | `app/Services/Closure/Analytics/ClosureManagementCharts.php` |
| View | `resources/views/livewire/closure/dispatch/dashboard.blade.php` |
| JS / CSS | `resources/js/home/my-panel.js` (`data-my-panel-chart`) e `.ops-matrix-cell`, `.ops-delta` em `resources/sass/ops/_ops.scss` |
| Excel | `Jobs\Closure\ExportClosureDashboardJob` + `ClosureDashboardReport` + `Exports\Closure\Dashboard\ClosureDashboardExport` (aba `Controle Exportacao`) |
| Índices | migration `2026_11_05_090000_add_management_dashboard_indexes_to_closure_tables` (aditiva) |
| Testes | `ClosureManagementAnalyticsTest`, `ClosureDispatchDashboardScreenTest`, `ClosureDashboardExportTest` |

## Anatomia

1. Hero e filtro de **período** (presets Mês atual / Mês anterior / 90 dias / Ano e datas; na URL: `de`, `ate`).
2. **Painel executivo**: Melhoramos ou pioramos? · Pendências em aberto · Resposta média · Devoluções para correção.
3. **Comparador mensal**: tabela de 10 indicadores × 6 meses com variação e veredito, gráfico Pendências por mês e os rankings "O que mais melhorou" / "O que mais piorou".
4. **Principais problemas**: ranking (top 10) e quadro "De onde vêm" (por origem).
5. **Problemas por parceira**: gráfico (top 10) e quadro completo.
6. **Problemas por atividade**: Parceiras, Medição, Fiscalização e Desenho (RI).
7. **Operação agora** (situação de hoje, filtros próprios: tipo, empresa, no status desde/até): cartões, volumetria por usuário, Ordens por status, matriz clicável, lista de Ordens, pedidos de prioridade, pendências por usuário e obras críticas.

## Regras de cálculo

- **Mês de cada número = data do próprio fato**: pendência aberta → `closure_issues.opened_at`; resolvida → `resolved_at`; resposta → `responded_at`; devolução para correção → evento `RETURNED_FOR_CORRECTION` de `closure_issue_events`; RI enviado → `closure_internal_returns.sent_at`; RI devolvido → `returned_at`; depósito bloqueado → `closure_partner_signals.created_at` (tipo `DEPOSIT_BLOCKED`); Ordem despachada/finalizada/encerrada no SAP → eventos `DISPATCHED`/`FINISHED`/`SAP_CLOSED` de `closure_order_events`.
- **Comparador**: seis meses terminando no mês de "até". O veredito compara o mês de referência com o anterior. Se o mês de referência é o atual, a comparação é **parcial** (a tela avisa).
- **Direção do indicador**: cada um é "mais é melhor" (finalizadas, encerradas, resolvidas), "menos é melhor" (pendências abertas, devoluções, resposta média, RI, depósitos) ou "sem direção" (despachadas: só diz "Subiu"/"Caiu"). Variação abaixo de **5%** é "Estável"; 0 nos dois meses é "Sem movimento" e não entra na contagem. O cartão "Melhoramos ou pioramos?" compara quantos indicadores melhoraram × pioraram.
- **Resposta média** = dias corridos entre `requested_at` e `responded_at`, das pendências respondidas no período (só quem tem as duas datas). Tempo do RI = `sent_at` → `returned_at`, dos devolvidos no período.
- **Retrabalho** = devoluções para correção do período ÷ retornos recebidos no período.
- **Principais problemas**: lista única, do maior para o menor, de motivos de pendência (categoria · subcategoria, origem = área de destino), motivos de RI (subcategoria/categoria do `Reclaim`; "Sem motivo informado" quando não há) e depósitos bloqueados (rótulo do depósito; mostra as Ordens afetadas). O gráfico/lista mostra 10; o Excel traz todos.
- **Por parceira**: pendências com `destination_company_id`. Recebidas e devoluções usam o período; "em aberto" e "aguardando" são de agora; "principal motivo" é a categoria mais frequente no período.
- **Por atividade**: pendência sem `destination_area` é da **Parceira** (padrão histórico); Medição e Fiscalização pelo `destination_area`; **Desenho** = RI. "Recebidas" comparadas ao período imediatamente anterior de **mesma duração** (menos é melhor). "Ordens na caixa" = Ordens hoje nos status mapeados da área (`ClosureInboxQuery::accompanyingCounts`).
- Tempos em dias corridos; só o que existe no banco (sem estimativas). ES e SP são deployments separados: a tela mostra a região do deployment.

## Desempenho

- Agregações em SQL (`GROUP BY` por mês), sem N+1; cache de 120 s por período.
- Índices novos (aditivos): `closure_order_events (type, created_at)`, `closure_issues (opened_at)`, `closure_issue_events (type, created_at)`, `closure_internal_returns (sent_at)`, `closure_partner_signals (created_at)`.

## Excel

Abas novas (logo após o Resumo): **Comparador mensal**, **Principais problemas**, **Por parceira**, **Por atividade**; seguem as de sempre (Volumetria, Por status, Matriz, Ordens ativas, Atividades por status, Parceiras, Pendências por usuário, Obras críticas, Prioridades). O Resumo lista o período usado.
