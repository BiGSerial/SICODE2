# Prazo de Projetos (gerência)

Serviços → Relatórios → **RELATÓRIO PRAZO DE PROJETOS** (`/reports/project-deadlines`, rota `reports.project_deadlines`, gate `can:management`). Mede a aderência de prazo das duas fases de projeto de cada OV: **levantamento** (status 27) e **desenho** (status 28). Segue o padrão de [../PADRAO_DASHBOARDS.md](../PADRAO_DASHBOARDS.md).

## Onde está o código

| Camada | Arquivo |
|---|---|
| Componente Livewire (filtros e orquestração) | `app/Http/Livewire/Reports/ProjectDeadlineDashboard.php` + `resources/views/livewire/reports/project-deadline-dashboard.blade.php` |
| Dados (tela e Excel) | `app/Services/Indicators/ProjectDeadlineAnalytics.php` (usa `OvAdherenceService`) |
| Fases derivadas | `app/Services/Notes/OvPhaseDurationService.php` → tabela `ov_phase_durations` |
| Histórico de status | `app/Services/Notes/OvStatusHistoryService.php` → tabela `historic_notes` |
| Excel (Job + export) | `app/Jobs/Reports/ProjectDeadlineExportJob.php`, `app/Exports/Reports/ProjectDeadline/ProjectDeadlineExport.php` |
| Testes | `tests/Feature/OvPhaseAdherenceTest.php`, `tests/Feature/OvStatusHistoryServiceTest.php` |

## Regras (decisão do produto, 2026-10-27)

- **SP ordena por data (regra `ov_phases.order_by_date`, `SicodeRules::ovPhasesOrderByDate()`, ligada por padrão só no ruleset `sp`):** a numeração (`idNum`) do SP saiu fora de sequência. Lá os eventos da OV são ordenados pela data (e `id_num` no empate), o último 27/28 é o mais recente nessa ordem e o término é o evento imediatamente acima. Pode ser ligada ou desligada por `sicode.rules.{ruleset}.ov_phases.order_by_date` (config ou `system_settings`).
- **Fase:** levantamento = evento de status **27** até o evento seguinte da mesma OV (`id_num` sequencial); desenho = status **28** até o evento seguinte.
- **Última passagem:** se a OV voltar ao 27 (ou ao 28), vale só a última.
- **Fase aberta** (sem evento seguinte) não entra: nem nas faixas, nem na aderência.
- **Dias corridos**, fracionados (diferença exata entre os eventos). A faixa 0–8 usa `dias <= 8`.
- **Prazo:** levantamento 8 dias; desenho 6 dias. Faixas: levantamento 0–8 / 9–15 / 16–30 / >30; desenho 0–6 / 7–15 / 16–30 / >30.
- **Mês** da fase = mês em que ela **termina** (data do evento seguinte).
- **Aderência** = % das fases concluídas dentro do prazo.
- **Só 2026** em diante (`OvStatusHistoryService::FLOOR`); o filtro não aceita data anterior.
- **Empresa executora** = empresa da última produção concluída do serviço de status 27/28 da nota (`ov_phase_durations.company_id`). Fase sem produção concluída fica como "Sem empresa".
- **Meta de referência do TMEP:** 17 dias (8 levantamento + 6 desenho + 3 PA); do status 2 até a saída do 28 o total esperado é 30 dias. O TMEP ainda não está na tela (falta definir CIP, Campo e PA).

## Como as fases são calculadas

`historic_notes` guarda um evento por status da BaseOV (`type_note = 2`; chave `note_id + id_num + num_stat`). `OvPhaseDurationService::rebuildForNotes()` deriva `ov_phase_durations` (uma linha por nota e fase) e é chamado ao fim de `OvStatusHistoryService::upsertFromSource`, ou seja, a cada gravação do histórico (`upd_baseov`, `LoteBaseOV`, botão "Carregar" do Search, `sicode:backfill_ov_history`). A empresa é buscada no recálculo; se a produção for concluída depois do fechamento da fase, a empresa só aparece no próximo recálculo da nota.

Para popular um ambiente: rodar a migration `ov_phase_durations` e `sicode:backfill_ov_history` (recalcula as fases a cada lote).

## Excel

Botão **Excel** → `ProjectDeadlineExportJob` (fila `exports`; recusa quem não tem `management`) → `exports/{AAAAMMDDHHMMSS}_{id}_prazo_projetos.xlsx` + `SystemNotification` com link assinado de 7 dias. Abas: Resumo (cartões, filtros e índice) · Levantamento · Desenho · Por empresa · Fases concluídas (lista completa, corta em 50.000 linhas e avisa). Mesmo visual (`DashboardTableSheet`/`DashboardTheme`) do Meu painel e do Painel da equipe; as abas de fase têm gráfico nativo de barras empilhadas. Datas são datas reais do Excel; tempos em dias.
