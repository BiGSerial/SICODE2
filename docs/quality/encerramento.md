# Encerramento

Não há integração/alteração de status no SAP na Qualidade: **o status final do SAP não é necessário** e foi removido (gateway, configuração, tela e aba).

Aprovação final do N2 no **2º ciclo**, numa única transação:
1. Registra a aprovação da etapa N2 (evento `APPROVED`).
2. Encerra a atividade do usuário: `status = 5`, `completed = true`, `completed_at` = data do último envio ao N1, `status_note`, fechamento de `Reclaim` abertos e linha na timeline da Nota (evento `PRODUCTION_CLOSED`).
3. Conclui o processo: `state = COMPLETED`, `completed_by/at` (evento `COMPLETED`). **Não volta mais.**

Se algo falhar, nada é aplicado (rollback) e o N2 pode repetir a aprovação.

## Legado

Os estados `CLOSING` e `SAP_FAILED`, os eventos `SAP_*` e a tabela `quality_sap_requests` permanecem apenas para processos criados antes desta remoção. Esses processos aparecem como **"Encerramento pendente"** e o N2 os conclui com **"Concluir encerramento"** (`QualityWorkflowService::retryClosing`), que executa os passos 2 e 3 acima. O N1 os vê como "Aguardando N2".
