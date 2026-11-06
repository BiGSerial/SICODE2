# Relatório de Produção

Tela: **Serviços → Relatórios → Relatório de Produção** (`/reports/productions`, permissão `management`). Segue [PADRAO_DASHBOARDS.md](../PADRAO_DASHBOARDS.md): hero, filtros na URL, painel executivo, gráficos `data-my-panel-chart`, lista e Excel por Job.

## Pergunta que a tela responde

O que a equipe produziu, **do mais novo para o mais antigo**: quantas atividades, de quem, em qual serviço e empresa, com qual conclusão da análise, e quais dependem de **órgão externo** (DOE) ou de **meio ambiente** (MA).

## Camadas

| Camada | Arquivo |
|---|---|
| Componente Livewire (só filtros) | `app/Http/Livewire/Reports/Productions.php` |
| Dados (tela, lista e Excel leem daqui) | `app/Services/Reports/Production/ProductionReportAnalytics.php` |
| Gráficos (só dados, contrato do `my-panel.js`) | `app/Services/Reports/Production/ProductionReportCharts.php` |
| Excel (Job na fila `exports`) | `app/Jobs/Reports/ProductionReportExportJob.php` + `app/Exports/Reports/Production/*` |
| Testes | `tests/Feature/Reports/ProductionReportTest.php` |

## Filtros (todos na URL, alias em português)

| Filtro | URL | Regra |
|---|---|---|
| Período | `periodo`, `de`, `ate` | Sobre a **data de conclusão** (`completed_at`). Atalhos: 7 dias, 30 dias (padrão), mês atual, mês anterior, todo o período. O campo "Mês" preenche De/Até. |
| Atividades | `servicos` | Várias. `productions.service_id`. |
| Empresa / Pessoa | `empresa`, `usuario` | Usuário de contrato só enxerga as empresas do seu contrato (`SicodeRules::visibleCompanyIdsFor`), na tela **e** no Excel. |
| Conclusões | `conclusoes` | Várias; vem de `analises.conclusion`. "Sem conclusão" = sem análise ou conclusão vazia. |
| Órgão externo | `orgao` | `sim`/`nao` sobre `notes.doe`. |
| Meio ambiente | `ma` | `sim`/`nao` sobre `productions.ma`. |
| Contabilização | `contabilizado` | `sim`/`nao` sobre `productions.confirmed`. |
| Incluir em aberto | `abertas` | Por padrão só concluídas. Ligado, **todas** as abertas entram, em qualquer período. |
| Incluir RI | `ri` | Por padrão `d5 = false`. Produções rejeitadas nunca entram. |
| Busca | `busca` | Nota, Ordem, Material, Grupo 2 e Grupo 5; `*` ou `%` viram curinga. A **busca múltipla** (modal) é uma lista exata de Notas, Materiais ou Ordens. |

## Regras de cálculo

- **Registro** = uma produção. O número do cartão "Registros" é o total da mesma consulta da lista.
- **DOE** vem da Nota (campo definido na análise do levantamento); **MA** vem da produção (definido ao concluir a análise). O gráfico de rosca separa cada registro em **um único grupo**: só órgão externo, só meio ambiente, os dois ou nenhum.
- **Postes/ativos** = postes × (EO + projeto inicial); sem EO/projeto, só os postes (mesma regra da lista).
- **Tempo médio** = média de (fim − início `att_at`) das concluídas que têm início. **Parada média** considera só quem teve parada.
- **Série do gráfico**: concluídas por dia (período de até 93 dias) ou por mês; com as parcelas DOE e MA.
- **Ordem da lista**: `completed_at` decrescente, depois `id` decrescente (é a ordem do índice `idx_prod_completed_completed_at`). As abertas, sem data de fim, vêm por último.

## Desempenho

Medido na base de desenvolvimento (~390 mil produções, buffer pool de 128 MB): um mês (~13 mil registros) leva ~0,3–0,9 s por consulta; um ano chega a ~10 s por varredura. Por isso:

- A lista abre na hora; os indicadores chegam **em partes** (`PARTS` + `loadNext` + esqueleto): `summary` (cartões, rosca e série, numa só varredura), `breakdown` (atividade × empresa × pessoa numa varredura, agregada em PHP) e `conclusions`.
- **Indicadores e gráficos só para períodos de até 93 dias** (`SCREEN_MAX_DAYS`). Acima disso (ou "todo o período") a tela mostra um aviso e a lista pagina **sem contagem total**; o **Excel cobre qualquer período**.
- Joins com `notes`/`analises` só entram quando um filtro ou agregação precisa deles; a lista carrega Nota e Análise em lote.
- Cache de 2 min por parte, filtro e dia; listas dos filtros 10 min.

## Excel (Controle Exportacao)

Abas: Resumo · Conclusões · Atividades · Pessoas · Empresas · Evolução · Registros · LayoutAntigo · Regras · **Controle Exportacao** (usuário, e-mail, data/hora, tipo e filtros). A aba **Registros** é a lista completa, lida em blocos, na mesma ordem da tela. A aba **LayoutAntigo** traz a mesma lista nas 59 colunas do layout anterior (mesma ordem e nomes, da conclusão mais antiga para a mais nova), a mesma de `ProductionLegacyLayoutExportJob`. Notificação por `UserNotificationData` com link assinado (7 dias).

## Decisões

- 2026-10-30: tela refeita no padrão da Home. Substitui os jobs `ExportProductionJob`, `ExportProductionListJob` e `ExportProductionReportJob` (removidos). Ordenação passou de "mais antigo primeiro" para "mais novo primeiro".
- 2026-10-30: a análise conclui com `conclusion` em texto livre vindo de `SelectOptions`; a lista de conclusões do filtro é a dos valores que existem na base (sem vazio e sem `0`).
