# Refatoracao Operacional Por WorkReport

## Principio

Fiscalizacao, Medicao e Publicacao devem operar tecnicamente por WorkReport. A Nota permanece como agregador visual e legado. Encerramento permanece por Ordem. D5 permanece em levantamento separado antes de qualquer mudanca estrutural.

Toda decisao desta refatoracao deve priorizar confiabilidade, integridade do dado, determinismo e desempenho. Nao corrigir granularidade funcional criando N+1, paginacao incorreta, inferencias ambiguas ou novas condicoes de corrida.

## Granularidade

| Fluxo | Unidade tecnica | Unidade visual | Observacao |
| --- | --- | --- | --- |
| Fiscalizacao | WorkReport | Nota + Escopo | Rede e Ligacao podem existir simultaneamente para a mesma Nota. |
| Medicao | WorkReport | Nota + Escopo | OP30/OP50 devem ser avaliadas nas Ordens do WorkReport. SP ignora OP40. |
| Publicacao | WorkReport | Nota + Escopo | Apenas escopos com publicacao aplicavel. Ligacao nao entra na lista. |
| Encerramento | Ordem | Ordem + Nota | Nao migrar para WorkReport sem nova decisao. |
| Parcial | Partial + order_partial | Nota + Ordens | Fluxo proprio; nao materializar em work_report_flow_productions. |
| D5 | Indefinido | Nota hoje | Nao alterar estrutura neste ciclo. Centralizar consumo e mapear impacto. |

## Regras Confirmadas

- A elegibilidade deve considerar todas as Ordens pertencentes ao WorkReport conforme a regra da etapa.
- Ordens de outro WorkReport da mesma Nota nunca podem satisfazer condicao da atividade.
- WorkReports diferentes da mesma Nota podem ter atividades abertas simultaneamente.
- Para novas atividades, uma Production deve representar exatamente um WorkReport naquela etapa.
- Acoes em lote podem selecionar varios WorkReports, mas devem criar ou alterar Productions independentes.
- Fallback legado so pode resolver automaticamente quando existir exatamente um WorkReport compativel.
- Quando houver ambiguidade, o sistema deve impedir alteracao operacional e registrar diagnostico.
- Historico novo de etapas por Informe deve registrar work_report_id sem remover note_id, service_id e production_id.
- Publicacao deve ser por WorkReport, mas nao por Ligacao.
- Parcial e final sao fluxos diferentes; parcial se resolve nas tabelas de parcial.
- Uma nova criacao de final deve ser bloqueada quando houver parcial ativa em andamento.
- Parcial pendente de aprovacao do engenheiro pode ser rejeitada/cancelada automaticamente pelo fluxo ja existente quando o final e informado.

## Restricoes De Implementacao

- Paginar pela unidade real da lista: WorkReport/candidato, nao Note expandida em PHP.
- Evitar N+1 em WorkReport -> Orders -> Operations -> Production -> ADS/D5.
- Preferir resolucao em lote para listas e exports.
- Nao usar DISTINCT para esconder duplicidade causada por join incorreto.
- Nao usar cache como primeira solucao para query mal estruturada.
- Proteger despacho com transacao e trava/constraint para atividade ativa por WorkReport + etapa + servico.
- Reforcar invariantes no banco quando viavel, com indice justificado por query e cardinalidade.
- Toda rotina de retrofill deve preservar parcial no proprio fluxo de parcial; parcial nao deve ser descartada nem convertida silenciosamente em final.

## Ordem De Implementacao

1. Base de dominio: constantes de stage, resolucao explicita por WorkReport, estados de fallback e testes.
2. Candidatos de Fiscalizacao por WorkReport, com paginacao correta e sem contaminacao entre escopos.
3. Despacho de Fiscalizacao por WorkReport, com trava por WorkReport/etapa/servico.
4. Candidatos e despacho de Medicao por WorkReport, preservando regra SP de OP30 + OP50.
5. Exports de Fiscalizacao e Medicao usando a mesma fonte de verdade das telas.
6. Publicacao por WorkReport aplicavel, excluindo Ligacao.
7. Historico com work_report_id nos novos eventos.
8. Levantamento D5 especifico antes de qualquer migracao estrutural.

## Criterio De Aceite Por Fase

- Nota com WorkReport Rede e WorkReport Ligacao nao pode sofrer contaminacao de Ordens.
- Duas atividades da mesma Nota em WorkReports diferentes podem coexistir.
- Duas atividades ativas do mesmo WorkReport/etapa/servico nao podem coexistir.
- Fallback ambiguo bloqueia alteracao.
- Listas paginam candidatos por WorkReport.
- Exports nao voltam a mapear Ordens pela Nota inteira.
- Testes automatizados cobrem caso de Nota com multiplos WorkReports.

## Registro De Andamento

Atualizado em 2026-09-11.

### Concluido / Validado

- Base operacional criada em `work_report_flow_productions`, com `stage`, `final_scope`, controle de vinculo atual e reversao.
- `WorkReportFlowProductionLinker` centraliza vinculos de Fiscalizacao, Medicao e Publicacao.
- `WorkReportStatusResolver` calcula o status atual do informe a partir dos vinculos operacionais, D5 e fallback por operacoes SAP.
- `WorkReportCurrentStatusRefresher` persiste `current_status_key`, `current_status_label`, `current_status_class` e `current_status_updated_at` em `work_reports`.
- Fiscalizacao ja permite selecao e despacho por WorkReport pelo modal compartilhado.
- Fiscalizacao bloqueia despacho ambiguo legado quando ha atividade aberta sem vinculo de informe.
- Fiscalizacao permite coexistencia de WorkReports diferentes da mesma Nota, separados por escopo.
- Medicao cria vinculos por WorkReport/escopo nos fluxos de despacho e autoatribuicao ja migrados.
- Escopos finais de BTZero EP estao materializados como Rede, Ligacao ou Geral, com regra de Publicacao nao aplicavel para Ligacao.
- Lista de despacho de Publicacao passou a avaliar elegibilidade pelas Ordens do WorkReport ativo, nao pela Nota inteira.
- Lista de despacho de Publicacao nao reabre por Ordem 150 posterior quando essa Ordem nao pertence ao informe publicavel.
- Lista de despacho de Publicacao permite coexistencia com Fiscalizacao aberta/simultanea do mesmo informe.
- Lista de despacho de Publicacao remove o informe pela OP20 confirmada nas Ordens associadas ao WorkReport, nao pela simples existencia de `Production`.
- Despacho em lote de Publicacao passou a abrir o modal compartilhado de despacho por informe, substituindo o modal legado da tela.
- Despacho de Publicacao passou a materializar vinculo operacional `stage = publication` para o WorkReport publicavel.
- Telas principais ja exibem badges de escopo e status atual do informe em pontos de parceiro, Fiscalizacao, Medicao e Publicacao.
- Testes especificos de linker, status e selecao de Fiscalizacao passaram no container `sicode2-app`.
- Testes especificos de Publicacao por WorkReport passaram no container `sicode2-app`.

Comando validado:

```bash
docker exec sicode2-app php artisan test tests/Feature/WorkReportFlowProductionLinkerTest.php tests/Unit/WorkReportStatusResolverTest.php tests/Feature/SupervisionDispatchWorkReportSelectionTest.php
docker exec sicode2-app php artisan test tests/Feature/PublicationWorkReportDispatchListTest.php tests/Feature/WorkReportFlowProductionLinkerTest.php
docker exec sicode2-app php artisan test tests/Feature/PublicationWorkReportDispatchListTest.php tests/Feature/WorkReportFlowProductionLinkerTest.php tests/Unit/WorkReportStatusResolverTest.php tests/Feature/SupervisionDispatchWorkReportSelectionTest.php
```

Resultados:

- Suite de linker/status/selecao de Fiscalizacao: 41 testes, 60 assertions.
- Suite focada em Publicacao por WorkReport + linker: 14 testes, 33 assertions.
- Suite combinada final: 48 testes, 71 assertions.

### Parcial / Em Atencao

- Publicacao possui lista de despacho e criacao inicial vinculadas ao WorkReport publicavel; ainda falta revisar pilha, acompanhamento, encerramento e export para garantir que todos usam o mesmo vinculo operacional.
- `note_inform_flows` continua existindo como camada consolidada/analitica e fonte materializada de escopos quando disponivel; nao deve ser confundida com o vinculo operacional ativo.
- Algumas rotas/telas de Medicao ainda mantem trechos legados de criacao/atribuicao de `Production`; confirmar caso a caso se todos chamam o linker antes de considerar a fase totalmente encerrada.
- Existem alteracoes locais em andamento no formulario de Fiscalizacao para regra de D5/conclusao. Elas nao fazem parte da refatoracao estrutural por WorkReport, mas impactam o encerramento operacional de Fiscalizacao.
- Execucao de testes pelo host falha por resolucao/conexao do banco (`host.docker.internal`/MySQL); executar a suite pelo container enquanto esse ambiente nao for ajustado.

### Etapas A Concluir

1. Completar Publicacao por WorkReport aplicavel.
   - Revisar pilha/atribuicao de Publicacao para usar e preservar o vinculo `stage = publication`.
   - Revisar encerramento de Publicacao para refrescar status do WorkReport vinculado.
   - Revisar export de Publicacao para usar as Ordens do WorkReport, nao da Nota inteira.
   - Impedir fallback ambiguo quando houver mais de um WorkReport compativel.

2. Revisar todos os fluxos de Medicao.
   - Confirmar que despacho principal, pilha, acompanhamento e autoatribuicao sempre criam vinculo operacional quando o fluxo for final.
   - Garantir que parcial continue fora de `work_report_flow_productions`.
   - Validar que OP30/OP50 sao avaliadas somente nas Ordens do WorkReport.

3. Revisar exports.
   - Fiscalizacao e Medicao devem usar a mesma fonte de verdade das telas.
   - Publicacao deve exportar por WorkReport aplicavel, nao por Nota expandida.

4. Completar historico por informe.
   - Novos eventos devem registrar `work_report_id` quando nascerem de Fiscalizacao, Medicao ou Publicacao por informe.
   - Manter `note_id`, `service_id` e `production_id` para compatibilidade e auditoria.

5. Consolidar invariantes de banco.
   - Avaliar indice/constraint para impedir duas atividades ativas do mesmo WorkReport + etapa + servico.
   - Preservar a possibilidade de atividades simultaneas para WorkReports diferentes da mesma Nota.

6. Levantamento D5.
   - Mapear todos os pontos que inferem D5 por Nota.
   - Definir se D5 permanece agregado por Nota ou se precisara de amarracao por WorkReport em uma fase futura.
   - Nao alterar estrutura D5 nesta fase sem nova decisao.

7. Retrofill e diagnostico.
   - Rodar ou revisar retrofill de vinculos para producoes finais existentes.
   - Gerar relatorio de casos ambiguos: multiplos WorkReports ativos, producoes finais sem vinculo, vinculos inativos/revertidos e publicacoes sem WorkReport.

8. Ampliar testes.
   - Cobrir Publicacao por WorkReport, incluindo exclusao de Ligacao.
   - Cobrir duplicidade ativa por WorkReport/etapa/servico.
   - Cobrir exports usando WorkReport como unidade real.
