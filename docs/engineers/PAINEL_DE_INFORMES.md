# Painel de informes

Tela: **Engenharia → Informes → Painel de informes** (`/engineers/dashboards/final_inform_dashboard`). Segue [PADRAO_DASHBOARDS.md](../PADRAO_DASHBOARDS.md): hero, filtros, painel executivo, gráficos `data-my-panel-chart`, lista e Excel por Job. Substitui o antigo "Dashboard Informes de Conclusão" (componente `Engineers\Ads\Dashboard`, removido).

## Pergunta que a tela responde

Como estão os informes de conclusão de obra: **quantos chegaram**, **quantos foram devolvidos e por quê**, se a **ADS foi entregue no prazo** (e quanto de atraso e multa isso significa), qual **empreiteira** gera mais devolução ou atraso e **onde cada informe está hoje** no fluxo de encerramento.

## Camadas

| Camada | Arquivo |
|---|---|
| Componente Livewire (só filtros) | `app/Http/Livewire/Engineers/InformDashboard.php` |
| Dados (tela, lista e Excel leem daqui) | `app/Services/Engineers/InformDashboardAnalytics.php` |
| Gráficos (só dados, contrato do `my-panel.js`) | `app/Services/Engineers/InformDashboardCharts.php` |
| Excel (Job na fila `exports`) | `app/Jobs/Engineers/InformDashboardExportJob.php` + `app/Exports/Engineers/InformDashboardExport.php` |
| Regra de ADS (prazo, atraso, multa) | `AdsSituationService::enrichRow` e `Ads\AdsDeadlinePolicy` (as mesmas da tela "Situação das ADS") |
| Testes | `tests/Feature/Engineers/InformDashboardTest.php` |

## Filtros (alias em português na URL)

| Filtro | URL | Regra |
|---|---|---|
| Período | `periodo`, `de`, `ate` | Sobre a **data do informe** (`work_reports.informed_at`). Atalhos: 7, 30, 90 dias, mês atual, mês anterior; campo "Mês" preenche De/Até. Máximo de 186 dias (mantém o trecho mais recente e avisa). |
| Empreiteira | `empreiteira` | Empresas com contrato de obra. Usuário de contrato só vê as do seu contrato, na tela **e** no Excel. |
| Busca | `busca` | Nota, ordem ou nome da empreiteira. |
| Devolução | `devolucao` | `sim` (já devolvido), `nao` (nunca devolvido), `aguardando` (devolvido e ainda sem novo informe). |
| Reinforme | `reinforme` | `sim`/`nao` sobre `work_reports.retry`. |
| Situação atual | `situacao` | `work_reports.current_status_key` (etapa do informe no encerramento). |
| Situação da ADS | `ads` | Só **lista** e aba Registros; os indicadores usam o recorte completo (senão o % no prazo perderia o sentido). |

## Regras de cálculo

- **Universo**: informes (`work_reports`) com `informed_at` no período, sem os cancelados. Informe devolvido e ainda sem reenvio (`rejected`) conta como recebido, mas não tem ADS a cobrar (grupo "Devolvido, aguardando novo informe").
- **Situação da ADS** (um grupo por informe): *Entregue no prazo*, *Entregue em atraso*, *Vencida sem entrega*, *Em aberto, no prazo* (inclui os que vencem em até 3 dias), *Passivo (legado sem ADS)* e *Devolvido, aguardando novo informe*. Vem dos mesmos códigos da tela "Situação das ADS" (`com_entrega`, `entregue_atraso`, `vencida_sem_entrega`, `no_prazo`/`vencendo_3_dias`, `passivo`).
- **Prazo**: contado do informe pela regra da região (`SicodeRules::adsDeliveryDeadline*`: dias úteis ou corridos, vence às 23:59), com feriados e abonos; na ADS tácita vale `tacit_due_at`. A entrega é `adsforms.created_at` (ou `tacit_delivered_at` na tácita). Informe com mais de uma ADS usa a mais recente.
- **% no prazo** = no prazo ÷ (no prazo + em atraso + vencidas). Em aberto, passivo e devolvidos não entram.
- **Atraso / multa prevista**: dias de atraso e faixa da `AdsDeadlinePolicy` (0,5% ao dia até 10 dias, depois 10%). Médias só sobre os que atrasaram.
- **Tempo até a ADS** = entrega − informe, em dias (negativo vira 0: ADS anterior ao reinforme).
- **Devolução**: linha em `return_works` com o motivo. *Taxa de devolução* = informes com ao menos uma devolução (ou aguardando novo informe) ÷ informes recebidos. O gráfico de motivos conta devoluções (um informe pode ter várias). *Reinforme* = `retry`.
- **ADS tácita**: `adsforms.tacit`; "sem entrega" = tácita cuja ADS definitiva ainda não foi entregue.
- **Série**: por dia (até 93 dias) ou por mês, sempre pela data do informe.
- **Ordem da lista**: informe mais recente primeiro.

## Desempenho

Uma consulta traz o recorte (período no `informed_at`, junções leves) e o cálculo de prazo roda em memória; cache de 2 min por filtro. Medido na base de desenvolvimento: ~900 informes (1 mês) em ~0,1 s; 5 mil (6 meses) em poucos décimos. O que tornava lento era a `AdsDeadlinePolicy` consultar `system_settings` a cada dia de cada informe (milhares de consultas): as duas regras agora são lidas uma vez por instância e os abonos de dia não útil são carregados em lote (`AdsSituationService::preloadAdjustments`).

## Excel (Controle Exportacao)

Abas: Resumo · Empreiteiras · Devoluções · Situação atual · Evolução · Registros · Regras · **Controle Exportacao**. Registros = a lista da tela (inclui o filtro de situação da ADS). Notificação por `UserNotificationData` com link assinado (7 dias).

## Decisões

- 2026-10-30: tela refeita no padrão da Home. O período passou a ser pela **data do informe** (antes filtrava por `work_reports.date`, a data da obra, enquanto o gráfico usava `informed_at`). O prazo deixou de ser "mais de 6 dias de diferença" e passou a ser o prazo oficial da ADS (SicodeRules + feriados). A rosca "base antiga × base nova" saiu (não responde pergunta de gestão); a ADS da base antiga (`old_ads_informs`) só existe até abril/2025.
