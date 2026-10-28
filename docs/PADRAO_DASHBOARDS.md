# Padrão de telas analíticas (dashboards)

**Referência: a Home** ("Meu painel", `/home`) e a sua versão da gerência, **Serviços → Relatórios → Painel da equipe**. Toda tela analítica nova (relatório gerencial, dashboard, acompanhamento) parte delas. Não se inventa layout, CSS ou contrato de gráfico por tela.

Detalhes de cada referência: [home/MEU_PAINEL.md](home/MEU_PAINEL.md) e [reports/PAINEL_DA_EQUIPE.md](reports/PAINEL_DA_EQUIPE.md). Tokens, componentes `<x-closure.*>` e regras de JS/formulário: [encerramento/PADRAO_UI_ENCERRAMENTO.md](encerramento/PADRAO_UI_ENCERRAMENTO.md).

## 1. Camadas (uma responsabilidade por arquivo)

| Camada | Regra | Exemplo na Home |
|---|---|---|
| Componente Livewire | Só filtros e orquestração. Sem consulta pesada nem montagem de gráfico. | `Home\Dashboard\MyPanel` |
| Service de dados (`*Analytics`) | **Tudo** que a tela e o Excel mostram sai daqui, para tela e Excel nunca divergirem. | `MyPanelAnalytics` |
| Service de gráficos (`*Charts`) | Só a configuração Chart.js (dados). Quantidade e ordem dos datasets **fixas** por gráfico. | `MyPanelCharts` |
| JS | Cria e atualiza os gráficos **in-place**. Reaproveitar `resources/js/home/my-panel.js` (evento `my-panel:charts`). | `my-panel.js` |
| CSS | SCSS com tokens, dentro do wrapper `.closure-dash`. **Nada de CSS ou JS inline** e nada de prefixo de classe próprio por tela copiando estilos. | `_my-panel.scss` |
| Excel | Job na fila `exports` + `SystemNotification`, visual `DashboardTableSheet`/`DashboardTheme`. | `PersonalProductionsJob` |
| Testes | Pest para o service de dados e para o contrato dos gráficos. | `MyPanelTest` |

## 2. Anatomia da tela (nesta ordem)

1. **Hero** (`<header class="mp-hero">`): eyebrow, `<h1>`, subtítulo que explica a pergunta que a tela responde e as ações à direita (`Exportar Excel` como `btn btn-light btn-sm btn-dash`).
2. **Filtros** (`<section class="mp-filters filter-bar">`): selects `form-select-sm`, atalhos de período (`.mp-preset`), datas, e a nota "Período exibido" (`.mp-period-note`).
   - Filtros vão na **URL** (`queryString` com alias curto em português).
   - Filtros em cascata limpam o que deixou de existir no recorte.
3. **Estado vazio** (`<x-closure.empty-state>`) quando não há dados, com texto que diz o que fazer.
4. **Seções** com `<h2 class="mp-section-title">` (caixa alta):
   - **Painel executivo:** cartões `<x-home.hero-card tone=… icon=… label value note values labels>` com mini-gráfico, 4 por linha (`col-12 col-md-6 col-xl-3`).
   - **Painéis** (`.mp-panel`) com cabeçalho em degradê, gráficos e tabelas.
   - **Rankings/distribuições** em `<x-home.bar-list>` (barras CSS com "Ver como tabela").
5. **Carregamento:** `wire:loading.class="is-loading-soft"` na área de conteúdo. Se a consulta for pesada, entrega em partes com esqueleto (ver §5).

## 3. Cards, tabelas e ações

- Cartões na mesma linha usam a **mesma classe de coluna** e `h-100` no card interno.
- **Uma** tabela contínua com `colgroup` de larguras fixas (`table-layout: fixed`) e linhas de cabeçalho de grupo. Nunca uma tabela por grupo.
- Ação de linha é **botão/pill** com ícone (`.row-action`), nunca link de texto.
- Todo status/tipo exibido tem **label em PT-BR** vindo de enum (`label()`/`badgeClass()`), nunca a constante crua.

## 4. Gráficos (Chart.js)

- Canvas com `data-my-panel-chart="<nome>"`, `data-config="{{ json_encode($charts['<nome>']) }}"`, dentro de `wire:ignore`, com `role="img"` e `aria-label` descritivo.
- **Nunca** substituir `chart.data.datasets`: atualizar por índice (`Object.assign`), `push` para novos, `splice` para excedentes. Sem dados, mostrar o aviso (`.mp-chart-empty`) por cima e **preservar** o último estado.
- Degradê via `dataset.gradient = [de, para]`; rosca com total no centro via `options.plugins.mpCenter`.
- Gráfico de ranking mostra o top 10 (`TOP`); a tabela e o Excel trazem todos.
- `<x-grafico.apex>` é o componente antigo (carrega Chart.js de CDN e usa evento próprio). **Não usar em tela nova.**

## 5. Desempenho

- Filtro por período só em coluna **indexada**. Antes de usar uma tabela grande, medir; se faltar índice, criar migration aditiva.
- Cache curto por parte, filtro e dia (a Home usa 2 min; listas dos filtros 10 min).
- Tela pesada: entrega em partes (`PARTS` + `loadNext` + esqueleto `.tp-skel*`), para abrir na hora.
- Agregar no banco (`GROUP BY`); aplicar grupos em PHP, sem `JOIN` linha a linha.
- Indicador derivado de histórico (ex.: fases do status 27/28) mora em tabela derivada atualizada na escrita, não em window function a cada leitura.

## 6. Excel e apresentação

- **Toda tela analítica exporta.** O Excel traz as mesmas séries da tela (abas por bloco) e a aba `Controle Exportacao` (usuário, email, data/hora, tipo, filtros).
- Datas são datas reais do Excel; tempos em dias; percentuais numéricos.
- Relatório para apresentar à gerência = o Excel + a própria tela. Não depender de `window.print()` como entrega.

## 7. Regras de negócio na tela

- A tela declara na documentação (`docs/…`) **cada regra de cálculo**: o que entra, o que sai, de qual coluna sai o tempo, como o mês é atribuído. Decisão de produto tem data e vira texto na doc, como em MEU_PAINEL.md.
- Só 2026 em diante quando o dado histórico começar nesse ano (`OvStatusHistoryService::FLOOR`).
- ES e SP são deployments e bancos separados: a tela mostra a região do deployment, sem chave de região no conteúdo.

## 8. Checklist de PR para tela analítica

- [ ] Camadas separadas (componente, `*Analytics`, `*Charts`, JS, SCSS).
- [ ] Hero, filtros na URL, estado vazio, seções com `mp-section-title`.
- [ ] Cartões `<x-home.hero-card>` com mesma coluna e `h-100`.
- [ ] Uma tabela por bloco, com `colgroup`; ações como botão.
- [ ] Gráficos pelo contrato `data-my-panel-chart`, in-place, aviso de vazio por cima.
- [ ] Sem CSS/JS inline, sem `<x-grafico.apex>`.
- [ ] Textos e status em PT-BR.
- [ ] Excel por Job com `Controle Exportacao`.
- [ ] Consulta por período usa índice; tela pesada em partes.
- [ ] Regras de cálculo documentadas.
- [ ] Testes do service de dados e do contrato dos gráficos.
- [ ] Conferida no navegador (desktop e largura estreita) antes de dar como pronta.

## 9. Anti-padrões

- Card Bootstrap genérico sem cor, hero ou gráfico ("visual pobre").
- CSS próprio por tela (`pd-*`, `cxl-*`) repetindo o que `.closure-dash` já tem.
- Montar dados ou gráfico dentro do componente Livewire ou da view.
- Gráfico recriado ou limpo a cada atualização.
- Tela, Excel e gráfico calculando o mesmo número por caminhos diferentes.
- Dar a tela como pronta sem tê-la aberto no navegador.
