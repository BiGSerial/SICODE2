# Dashboard de atividade (Despacho → Dashboard)

Rota `/dispatch/{atividade}/dashboard` (`Dispatchs\Dashboard`), a mesma tela para toda atividade. É a **segunda referência de dashboard** do sistema, junto da Home ("Meu painel"): segue o [PADRAO_DASHBOARDS.md](../PADRAO_DASHBOARDS.md) à risca. Responde: *como está a pilha da atividade, quem está com ela, o que está crítico e se as conclusões estão dentro do prazo.*

## Camadas

| Camada | Arquivo |
|---|---|
| Componente Livewire (só filtros e orquestração) | `app/Http/Livewire/Dispatchs/Dashboard.php` |
| Dados (tela, gráficos e Excel leem daqui) | `app/Services/Dispatch/Dashboard/ActivityDashboardAnalytics.php` |
| Gráficos (só dados, datasets fixos) | `app/Services/Dispatch/Dashboard/ActivityDashboardCharts.php` |
| View | `resources/views/livewire/dispatchs/dashboard.blade.php` |
| JS / CSS | `resources/js/home/my-panel.js` (`data-my-panel-chart`) e `.my-panel`; nada próprio da tela |
| Excel | `Jobs\Dispatchs\ActivityDashboardExportJob` + `Exports\Dispatchs\Dashboard\ActivityDashboardExport` (aba `Controle Exportacao`) |
| Testes | `tests/Feature/OpsActivityDashboardTest.php` |

## Anatomia

1. Hero (eyebrow "Dashboard · {atividade}", subtítulo com o recorte, "Lista para {atividade}", "Exportar Excel").
2. Filtros na URL: Tipo (OV/Nota/Ambos, `tipo`), Incluir Acompanhamento (`acomp`), período da aderência (presets + `de`/`ate`), limite da 1ª faixa (6 ou 8 dias, `aderencia`) e "Limpar filtros".
3. **Painel executivo**: Total na pilha · Em atribuição · Sem atribuição · Prazo vencido (`<x-home.hero-card>`).
4. **A pilha no tempo**: Tempo no status e Prazo real (barras empilhadas).
5. **Equipe e pilha crítica**: Quantidade por colaborador (barras horizontais) e tabela da pilha crítica (15 mais consumidas; a lista completa vai no Excel).
6. **Aderência ao prazo**: conclusões por faixa de dias e por mês, SATEL x Própria (EDP).

## Regras de cálculo (única fonte: `ActivityDashboardAnalytics`)

- **Pilha:** as notas que a lista de despacho da atividade enxerga (`RuleBuilder::applyRules` das regras de status + `excludeCanceledFullDone`), filtradas por tipo; a rubrica Acompanhamento fica fora, salvo pedido.
- **Em atribuição:** a nota tem Production aberta (`confirmed = 0`) na atividade. **Sem atribuição** = o resto.
- **Tempo no status:** dias desde `notes.dt_status`; 30 ou mais agrupam em "30+".
- **Prazo real:** `30 - notes.days_left` (dias consumidos). **Prazo vencido:** `days_left < 0`. Cor do prazo: vermelho com menos de 6 dias restantes, âmbar até 9, verde com 10 ou mais.
- **Prazo real por quem está com a nota:** séries fixas Na pilha · SATEL · Própria (EDP) · Outras empresas (pelo nome da empresa da Production aberta mais recente).
- **Por colaborador:** Productions abertas com usuário; idade desde ATT (senão despacho, senão criação): até 4, 5 a 8, mais de 8 dias; nota contada uma vez por usuário; os 12 com mais atribuições.
- **Aderência:** Productions concluídas no período, sem RI nem D5, só OV; dias entre `dt_note` e a conclusão; faixas 0–limite, limite+1–15, 16–30, 30+; por mês de conclusão.
- **Pilha grande demais:** acima de `PILE_MAX` (50 mil) a atividade não tem pilha própria (as regras de status deixam passar quase todas as notas): a tela avisa e mostra só a aderência.

## Pagamento, Publicação e Fiscalização (não olham para status)

Essas três atividades não têm pilha por regra de status: cada uma tem **repositório próprio** e o tempo conta **desde o informe**. `ReportPileDashboardAnalytics` (mesmo formato de retorno; `ActivityDashboardAnalytics::for($service)` escolhe a classe pela pasta da atividade) usa a mesma pilha da lista de despacho e o prazo **parametrizado** de cada uma. O cartão, os gráficos e a tabela mostram os textos do `profile()` da atividade.

| | Pilha (mesma da lista) | Data base | Prazo (parametrizado) | Cor |
|---|---|---|---|---|
| Pagamento (Medição) | `NoteFilter` do grupo `payments` | informe mais antigo válido; sem informe, fiscalização da parcial | `MeasurementDeadlinePolicy` (`payment.measurement_deadline`: dias, corridos ou úteis; ES 15 corridos, SP 8 úteis) | vencido vermelho · até 4 dias âmbar · acima verde |
| Publicação | `PublishRepository::getBaseQuery` | entrega do informe | `SicodeRules::publicationDeadlineDays()` (`publication.deadline`, padrão **2 dias**, corridos; `business_days` troca para úteis) | vencido vermelho · vence hoje âmbar · depois verde |
| Fiscalização | `SupervisionDispatchListQuery` (uma linha por informe, parcial ou D5 passiva) | informe do `work_report` | `work_reports.ads_due_at` (regra `ads.delivery_deadline`); informe com ADS entregue não tem mais prazo | vencido vermelho · hoje ou amanhã âmbar · depois verde |

- Unidade: **notas** (Pagamento, Publicação) ou **informes** (Fiscalização).
- Tempo desde o informe e prazo vão em dias de calendário; o gráfico de prazo agrupa em "Vencido", 0 a 30 dias, "30+" e "Sem prazo", por quem está com a nota (mesmas séries fixas).
- Novas regras de prazo entram em `SicodeRules` (+ `config/sicode.php`, sobrescrevíveis em `system_settings`); nunca como número na tela.

## Desempenho

- Resumo, tempo no status e prazo real saem de **uma** consulta agrupada; colaboradores leem as Productions abertas e conferem a nota por id; a pilha crítica é uma consulta com `LIMIT`. Antes eram 6 varreduras da regra de status, repetidas a cada troca de filtro (e duas vezes por render).
- Cache de 60 s por atividade e filtros; o teste de "pilha grande demais" fica 10 min.
- Medido (Análise, 483 notas): ~0,4 s para tudo; antes, vários segundos.

## Para reaproveitar

Outro dashboard operacional novo: crie `*Analytics` (dados) e `*Charts` (config no contrato do `my-panel.js`: datasets fixos, `gradient`, `mpBarLabels`), um componente fino com `my-panel:charts` nos filtros, e Job + Export com `Controle Exportacao`. Não use `<x-grafico.apex>`.
