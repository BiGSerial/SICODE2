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
