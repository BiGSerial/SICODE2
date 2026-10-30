# Informes sem saldo no PEP (Engenharia)

Tela de controle em **Engenharia → Informes sem saldo PEP** (`/engineers/informes_sem_saldo_pep`, rota `engineers.pep_alerts`). Segue o padrão de [PADRAO_DASHBOARDS.md](../PADRAO_DASHBOARDS.md): hero, filtros na URL, cartões `<x-home.hero-card>`, painéis, `<x-home.bar-list>`, gráficos `data-my-panel-chart` e Excel por Job.

## Como um informe entra na lista

O bloqueio por saldo (`work_report.blocks_without_budget`) está **desligado**: o parceiro envia normalmente. Com `work_report.monitors_budget` ligado (hoje só no ES), `PepBudgetWatchService` registra o informe em `pep_balance_alerts` logo depois de gravar:

- Informe **final** (`Partner\Forms\Workreports`) e **parcial** (`Partner\Forms\Partialreport`).
- Motivo `no_balance`: algum PEP terminado em `-2` das ordens abertas com `disponivel <= 0`.
- Motivo `ads_exceeds`: ADS (valor da parcial) maior que o saldo somado dos PEPs.
- Não entram: obra `type_note = 2`, ordem encerrada (ENT/ENC), obra sem PEP ou PEP que não veio na sincronização (não há saldo para afirmar).
- A região é a base de construção da obra (`notes.nexp → cities.baseConstrucao`), gravada no momento da entrada.
- Cada informe entra uma vez só (`unique informe_type + informe_id`). Falha ao registrar só gera log; nunca impede o envio.

## Quem vê e quem é avisado

- Só usuários com perfil **engenheiro** (`users.engineer`). Cada um vê o que é da sua **região** (`user_regions`) **ou** das suas **empresas responsáveis** (`company_user`).
- Engenheiro sem região nem empresa cadastrada vê tudo (como em Cancelamentos), mas **não** recebe aviso. Superadm vê tudo.
- Ao entrar um item novo, os engenheiros da região/empresa recebem `SystemNotification` com link para a tela.
- Quem não enxerga um item também não consegue tratá-lo.

## Saídas da fila

| Saída | Regra |
|---|---|
| Informe removido | Final com `canceled = true`; parcial com `deny = true` ou apagada. Some da fila na hora (cálculo na leitura). |
| Tratado | O engenheiro clica em "Tratado" (observação opcional). Vai para o histórico com quem, quando e a observação. |
| Saída automática | O saldo passa a cobrir o pedido e o engenheiro não age por mais de **48h**: vai para o histórico com `handled_via = auto`. Se o saldo voltar a faltar, a contagem zera. |

A reavaliação roda em `sicode:pep-alerts-reconcile` (06:30 e 12:30, logo após o `sicode:sync-peps`). Os 48h contam a partir da primeira reavaliação que viu o saldo cobrindo (`covered_since`).

## Regras de cálculo da tela

- **Pendentes / valor / idade** são a situação de agora; não dependem do período.
- **Período (De/Até)** vale para entradas (`created_at`), tratados (`handled_at`), fluxo diário e histórico. Máximo de 120 dias.
- **Valor (ADS)**: parcial = `partials.value`; final = `adsforms.amount`. Final sem ADS não soma valor e aparece como "—".
- **Idade**: dias corridos desde a entrada na lista (nos tratados, até o tratamento). Faixas: 0-2, 3-7, 8-15, 16+ dias.
- **Status do informe**: final = `current_status_label` do WorkReport; parcial = Aguardando aprovação / Em fiscalização / Em medição / Paga / Rejeitada.
- Ranking por região e por empreiteira mostra o top 10; o Excel traz todos.

## Camadas

| Camada | Arquivo |
|---|---|
| Componente (só filtros e ações) | `App\Http\Livewire\Engineers\PepAlerts` |
| Dados (tela e Excel) | `App\Services\Engineers\PepAlertsAnalytics` |
| Gráficos | `App\Services\Engineers\PepAlertsCharts` (`flow`, `reason`, `age`) |
| Registro e regras de saída | `App\Services\Partner\PepBudgetWatchService` |
| Excel | `App\Jobs\Engineers\PepAlertsExportJob` + `App\Exports\Engineers\PepAlertsExport` (abas: Resumo, Pendentes, Tratados, Por região, Por empresa, Fluxo diário, mais `Controle Exportacao`) |
| Modal da parcial | `Engineers\Actions\CheckPartial` mostra o saldo do PEP só para o engenheiro (lido no servidor) |

Testes: `tests/Feature/PepBudgetWatchTest.php`.
