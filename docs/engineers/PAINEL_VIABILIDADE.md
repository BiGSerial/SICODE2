# Painel de viabilidade (Engenharia → Projetos → Viabilidade → Painel)

Rota `engineers.main` (`/engineers`). Segue o padrão de [PADRAO_DASHBOARDS.md](../PADRAO_DASHBOARDS.md): hero, filtros na URL, cartões, painéis, barras, gráficos `data-my-panel-chart` e Excel por Job. Substitui os três painéis antigos (Situação Viabilidade, Rejeição Motivos e Lista Produtividade).

## O que responde

1. **O que está com a Engenharia agora?** Cartão "Em viabilidade", as 10 mais antigas e a carga por engenheiro.
2. **Como o período fechou?** Realizadas, não realizadas (tácitas) com multa estimada, rejeições por motivo e retorno médio.
3. **Como cada empresa está entregando?** Uma tabela com entradas, realizadas, não realizadas, rejeitadas, fila de agora, % de realização e retorno médio.

## Regras de cálculo

| Indicador | Regra |
|---|---|
| Visão | Superadm vê tudo. Os demais veem as viabilidades dos usuários sob a sua gestão (`engineer_id` em `User::visibleUserIdsForWork()`), o mesmo recorte das listas e dos contadores do menu. |
| Em viabilidade (agora) | `canceled = 0`, `completed = 0`, `tacit = 0`, `rejected = 0` e `visible_partner = 0`. É o mesmo número do contador "Em viabilidade" do menu. Não depende do período. |
| Idade / fora do prazo | Dias corridos desde `sended_at`. Acima de 7 dias = fora do prazo base (a regra completa, com dias adicionais por justificativa, continua no Resumo da viabilidade). |
| Fechamento | `COALESCE(returned_at, completed_at)` de viabilidade `completed = 1`, dentro do período. |
| Realizada | Fechada e não tácita; ou tácita com justificativa concedida (`tacit_comments.granted`). |
| Não realizada | Fechada, tácita, sem justificativa ou com justificativa indeferida (`dismissed`). Multa estimada = 1% do valor (`viabilities.value`). |
| Entradas | `sended_at` dentro do período. |
| Rejeições | Formulários rejeitados (`forms.rejected = 1`) criados no período, agrupados por `reason`. |
| Retorno médio | Média de dias entre `sended_at` e `returned_at` das fechadas não tácitas que têm `returned_at`. |
| % realização | Realizadas ÷ (realizadas + não realizadas) no período. |

O filtro de período vale para entradas, fechamentos, rejeições e fluxo diário (máximo de 366 dias). A fila "Em viabilidade" é sempre a situação de agora. A lista de empresas do filtro tem cache de 10 minutos.

## Camadas

| Camada | Arquivo |
|---|---|
| Componente (só filtros) | `App\Http\Livewire\Engineers\ViabilityDashboard` |
| Dados (tela e Excel) | `App\Services\Engineers\ViabilityDashboardAnalytics` |
| Gráficos | `App\Services\Engineers\ViabilityDashboardCharts` (`flow`, `outcome`, `age`) |
| Excel | `App\Jobs\Engineers\ViabilityDashboardExportJob` + `App\Exports\Engineers\ViabilityDashboardExport` (Resumo, Em viabilidade, Por empresa, Motivos de rejeição, Fluxo diário, mais `Controle Exportacao`) |

Testes: `tests/Feature/PepBudgetWatchTest.php` (seção do painel de viabilidade).
