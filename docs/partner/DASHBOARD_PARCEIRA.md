# Dashboard da Parceira (`/partner`)

Segue [PADRAO_DASHBOARDS.md](../PADRAO_DASHBOARDS.md): hero, filtros na URL, seções `mp-section-title`, cartões `<x-home.hero-card>`, painéis `mp-panel` e gráficos `data-my-panel-chart` (Chart.js in-place via `my-panel.js`). Sem CSS/JS inline e sem `<x-grafico.apex>`.

## Camadas
| Camada | Arquivo |
|---|---|
| Componente (filtros, orquestra) | `App\Http\Livewire\Partner\Main` (filtros `mes`, `de`, `ate` na URL; empresa pelo filtro do portal) |
| Gráficos (só configuração) | `App\Services\Partner\PartnerDashboardCharts` (6 gráficos, datasets fixos) |
| View | `resources/views/livewire/partner/main.blade.php` |
| Valores sobre as barras | plugin `mpBarLabels` do `my-panel.js` (`outside` em barras, inclusive horizontais; `segments`/`totals` em pilhas) |
| Testes | `tests/Feature/PartnerDashboardChartsTest.php` |

Ao mudar um filtro, `toUpdateGraphs()` recalcula e envia o evento `my-panel:charts`; os gráficos são atualizados por índice, nunca recriados.

## Gráficos
`aging` (atraso de ADS por faixa), `due` (vencimentos dos próximos dias, ADS x viabilidade), `informs` (informes finais por mês do ano), `viabilityAge`, `d5Age` (D5 em espera x rejeitados por idade) e `reasons` (motivos de rejeição).

## Regras
- Período (mês/datas): informes, ADS/parciais e motivos de rejeição. Pendências e prazos mostram a situação de hoje.
- Prazos sempre com data e dias (`<x-ops.date>`): vencimento + "N d" ou "N dias vencidos".
- Valores de ADS são informativos (aviso legal em `DashboardLegalNotices`).
- Pendência: o Excel de resumo e o CSV de pendências continuam como exportações diretas (migrar para Job é pendência).
