# Consulta geral D5 (Engenharia)

Tela em **Relatórios → Consulta D5** (`/reports/consulta_d5`, rota `reports.consulta_d5`) e **Engenharia → D5 Aguardando Resolução**, as duas com o componente `Engineers\WaitingFiveNotes`. Segue o padrão de [PADRAO_DASHBOARDS.md](../PADRAO_DASHBOARDS.md): hero, filtros, cartões `<x-home.hero-card>`, painéis, `<x-home.bar-list>` e gráficos `data-my-panel-chart` (mesmo `my-panel.js`). Sobre a seleção das D5 e a relação com o relatório gerencial, ver [D5_Consulta_vs_Relatorio_Padronizacao.md](../D5_Consulta_vs_Relatorio_Padronizacao.md).

## Camadas

| Camada | Arquivo |
|---|---|
| Componente (filtros, lista paginada) | `app/Http/Livewire/Engineers/WaitingFiveNotes.php` |
| Números da tela | `app/Services/Engineers/D5ConsultAnalytics.php` |
| Gráficos (só dados) | `app/Services/Engineers/D5ConsultCharts.php` |
| Etapa da D5 | `app/Services/D5/D5StageResolver.php` |
| Testes | `tests/Feature/Engineers/D5ConsultAnalyticsTest.php`, `D5ConsultScreenTest.php` |

## Regras de cálculo

- **Base:** a mesma query da lista, com **todos os filtros menos a etapa** (`filteredQuery()`). Cartões e gráficos mostram as quatro etapas lado a lado; a etapa escolhida nos botões da lista filtra só a tabela.
- **Etapa** (Fornecedor, Fiscalização, Medição, Finalizado): vem do status do informe quando mapeado, senão dos flags da D5 (`D5StageResolver::applyFilter`). "Em andamento" = Fornecedor + Fiscalização + Medição.
- **Dias parada na etapa** (`TIMESTAMPDIFF(DAY, início, agora)`), com o início de cada etapa e a mesma queda de data da tabela:
  - Fornecedor: `dispatch_at`
  - Fiscalização: `completed_at`, senão `supervisioned_at`, senão `dispatch_at`
  - Medição: `supervisioned_at`, senão `completed_at`, `dispatch_at`, `created_at`
- **Régua:** até 3 dias = no prazo; 4 a 5 = **atenção**; mais de 5 = **atrasada** (`WARN_DAYS`/`LATE_DAYS`; a tabela usa as mesmas constantes).
- **Faixas do gráfico de idade:** 0-3, 4-5, 6-10, 11-20, 21-60, 61-180 e 181+ dias. A partir da terceira faixa a D5 conta como atrasada.
- **Mais antiga / média:** maior e média de dias parada, só das D5 em andamento.
- **Retorno da empreiteira:** média, em dias, entre `dispatch_at` e `completed_at` das D5 que já retornaram (qualquer etapa).
- **Fluxo mensal:** D5 despachadas (`dispatch_at`) e retornadas (`completed_at`) por mês, nos últimos 6 meses, dentro dos filtros.
- **Rankings** (empreiteira e motivo): D5 em andamento, top 10 na tela; "atrasadas" e "mais antiga" no detalhe. Motivo vazio aparece como "Sem motivo informado".
- **Cache:** 2 minutos por consulta (`D5ConsultAnalytics::CACHE_SECONDS`), chave com versão e hash do SQL e dos parâmetros.

## Lista

- Ordenada pelo retorno da empreiteira (mais antigo primeiro; sem retorno por último), depois pelo despacho.
- Tela e Excel seguem as mesmas regras de elegibilidade (`visible_partner` ou D5 criada na fiscalização). O Excel continua sendo o da lista (`ExportWaitingFiveNotesJob`).

## Pendências

- Excel com as abas dos blocos analíticos (resumo, etapas, empreiteiras, motivos, fluxo) e a aba `Controle Exportacao`, como em `PepAlertsExport`.
