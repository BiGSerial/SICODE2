# Plano de Integridade de Medicao e Pagamento

## Objetivo

Fazer com que cada Production de Medicao represente uma unica demanda identificavel, sem depender apenas de `note_id`. A demanda deve preservar seu Informe desde a lista ate o encerramento, inclusive em autoatribuicao, D5, reprocessamento e concorrencia.

## Decisoes de dominio

| Demanda | Identidade obrigatoria | Ciclo | Flags esperadas |
| --- | --- | --- | --- |
| Medicao parcial | `partial_id` | `partial` | `partial = true` |
| Medicao final normal | `work_report_id` | `cycle_1` | `partial = false`, `dfive = false` |
| Medicao final com D5 | `work_report_id` + D5 vinculada | `cycle_1` | `partial = false`, `dfive = true` |
| Retorno de D5 | `work_report_id` + `five_note_id` | `d5_return` | contexto D5 obrigatorio |

Exceção única: uma D5 de Ciclo 2 sem Informe pode ser despachada somente quando
`isPassive = true` e ela satisfaz integralmente a elegibilidade do retorno. Nesse
caso o contexto mantém obrigatoriamente o `five_note_id`; não é um despacho por Nota.

`partial` e `dfive` classificam a Production; nao sao sua identidade.

### Elegibilidade

- Ciclo 1 usa somente as Ordens do `WorkReport` correspondente e a regra central `WorkReportPaymentCandidateQuery`.
- ES: Ordem `LIB`, OP30 `CONF`, OP40 em `CNPA`/`JBFI LIB`/`LIB` e OP50 `LIB`.
- SP: Ordem `LIB`, OP30 `CONF` e OP50 em `LIB`/`CNPA`/`JBFI LIB`; OP40 nao participa.
- D5 existente nao substitui a regra de operacoes do Ciclo 1.
- Ciclo 2 exige simultaneamente `is_completed = true`, `is_supervisioned = true`, `is_archived = false` e `returned = false`.
- O Ciclo 1 consumido nao pode renascer apenas porque as operacoes continuam validas.
- D5 arquivada nao volta para a fila. Uma nova D5 deve ter identidade propria, independente da arquivada.

## Diagnostico registrado

- `Production` nao tem FK direta de Informe, Parcial, D5 ou ciclo.
- Finais usam `work_report_flow_productions`; Parciais e D5 usam `productionables`. Nenhum deles obriga contexto para novas Productions de Pagamento.
- Ha caminhos de criacao fora de `DispatchWorkflowService`: despacho legado, parceiro e autoatribuicao.
- Autoatribuicao e caminhos legados ainda criam por Nota/servico e associam contexto depois; parcial pode ficar sem pivot.
- Algumas pilhas e encerramentos usam `first()`, `last()` ou "ultimo parcial" da Nota, o que pode mover ou fechar o Informe errado.
- O retorno D5 atual nao verifica `returned = false` em todos os pontos.
- Nao existe unicidade de banco para uma demanda ativa, Ciclo 1 consumido ou retorno D5.
- A unicidade atual de `five_notes.work_report_id` e `d5_origin_key` impede nova D5 para o mesmo Informe, inclusive depois de arquivada.

## Arquitetura alvo

Criar uma camada unica para escrita de Medicao, por exemplo `MeasurementDispatchService`, apoiada por:

1. `MeasurementEligibilityService`: produz candidatos tipados (`partial`, `cycle_1`, `d5_return`) e nunca candidatos somente por Nota.
2. `MeasurementDemandContext`: contexto persistente por Production, contendo `production_id`, `work_report_id` ou `partial_id`, `five_note_id` quando aplicavel, `cycle`, chave de consumo e chave ativa.
3. `MeasurementDispatchService`: recebe IDs explicitos, trava a demanda, revalida no banco, impede duplicidade e cria Production + contexto + pivots na mesma transacao.
4. `MeasurementCompletionService`: encerra somente o Parcial/D5 presentes no contexto da Production.
5. `MeasurementIntegrityAudit`: comando somente leitura para diagnosticar dados legados antes de qualquer saneamento.

Nenhuma tela, job, controller ou comando pode criar Production de Pagamento diretamente depois da migracao. Todos delegam ao servico unico.

## Checklist de execucao

### Fase 0 — Baseline e auditoria sem escrita

- [ ] Confirmar UUIDs/nomes de Medicao/Pagamento e atividades de Liberacao de Carta no ambiente alvo.
- [ ] Criar `sicode:audit-payment-production-integrity`, somente leitura e com saida tabela/JSON/CSV.
- [ ] Listar Productions de Pagamento sem contexto de Informe.
- [ ] Listar Productions `partial = true` sem exatamente um `Partial` no pivot.
- [ ] Listar Productions `dfive = true` sem exatamente uma D5 no pivot.
- [ ] Listar vinculos Informe × Nota e D5 × Informe × Nota inconsistentes.
- [ ] Listar duplicidades abertas por Parcial, Informe/Ciclo 1 e D5/Retorno.
- [ ] Listar Ciclos 1 historicos repetidos e apontar IDs, usuarios, status e datas.
- [ ] Marcar registros inferidos por retrofill como incertos; nao corrigir nem apagar dados nesta fase.
- [ ] Executar a auditoria no ambiente autorizado e anexar o resultado ao ticket de saneamento.

### Fase 1 — Contexto persistente e integridade de banco

- [ ] Definir migration para `measurement_production_contexts` ou estrutura equivalente.
- [ ] Exigir exatamente um alvo: `work_report_id` XOR `partial_id`.
- [ ] Exigir `five_note_id` para `d5_return`.
- [ ] Manter FK de Production, Informe Final, Parcial e D5.
- [ ] Criar unicidade permanente para `work_report_id + cycle_1`.
- [ ] Criar unicidade permanente para `five_note_id + d5_return`.
- [ ] Criar chave unica de demanda ativa para Parcial, liberada somente quando a Production deixa de estar ativa.
- [ ] Definir formalmente o que e Production ativa a partir dos status atuais; nao assumir somente uma flag sem revisao.
- [ ] Atualizar o contexto quando Production for concluida, confirmada, revertida ou cancelada.
- [ ] Rever a regra de unicidade de D5 para permitir nova D5 apos arquivamento, preservando historico.

### Fase 2 — Candidatos e transporte de contexto

- [ ] Substituir listas baseadas em Nota por candidatos por Informe/Parcial/D5.
- [ ] Remover `GROUP BY note_id`, `DISTINCT note_id`, `latest partial` e `first()` usados como identidade de demanda.
- [ ] Transportar `note_id`, `work_report_id`/`partial_id`, `five_note_id` e `cycle` em toda selecao de tela.
- [ ] Validar no backend que Informe/Parcial pertence a Nota e D5 pertence ao Informe antes de qualquer despacho.
- [ ] Fazer Ciclo 1 consultar o consumo historico, alem da elegibilidade de operacoes.
- [ ] Fazer Ciclo 2 usar exatamente os quatro estados obrigatorios da D5.
- [ ] Manter D5 legada sem Informe separada e impedir que seja atribuida a um Informe por heuristica.

### Fase 3 — Escrita unica e concorrencia

- [ ] Migrar despacho manual para `MeasurementDispatchService`.
- [ ] Migrar despacho por parceiro e em lote para o mesmo servico.
- [ ] Migrar autoatribuicao para o mesmo servico.
- [ ] Migrar reatribuicao para receber `production_id` e validar seu contexto, sem buscar pela Nota.
- [ ] Usar `DB::transaction()` e `lockForUpdate()` no Informe/Parcial/D5/contexto antes de criar Production.
- [ ] Revalidar elegibilidade, ciclo consumido, estado da D5 e Production ativa imediatamente antes da escrita.
- [ ] Criar Production, contexto, vinculos de Informe/Parcial/D5 e eventos dentro da mesma transacao.
- [ ] Tratar violacao de indice unico como resultado idempotente ou erro de dominio legivel.
- [ ] Bloquear todos os caminhos diretos `Production::create`, `firstOrCreate` e `updateOrCreate` de Pagamento fora do servico unico.

### Fase 4 — Encerramento e D5

- [ ] Encerrar Parcial exclusivamente pelo `partial_id` do contexto.
- [ ] Encerrar Retorno D5 exclusivamente pelo `five_note_id` do contexto.
- [ ] Ao encerrar Retorno D5, gravar `is_archived = true` na D5 travada e vinculada.
- [ ] Nunca arquivar, pagar ou retornar uma D5 escolhida por `first()`/`last()` da Nota.
- [ ] Revisar a transicao de D5 retornada para garantir o tratamento explicito de `returned` antes de novo Ciclo 2.
- [ ] Atualizar status materializado do Informe somente pelo contexto efetivamente encerrado.

### Fase 5 — Testes obrigatorios

- [ ] P1–P6: Parcial, identidade obrigatoria, duas parciais da mesma Nota e concorrencia.
- [ ] C1–C5: Ciclo 1 sem/com D5, bloqueio ativo, consumo historico e Informes independentes.
- [ ] D1–D9: todos os estados da D5, retorno unico, arquivamento e nova D5 independente.
- [ ] Concorrencia: manual/manual, auto/auto e manual/auto para a mesma demanda.
- [ ] Integridade: Informe de outra Nota, D5 de outro Informe e perda de elegibilidade entre lista e clique.
- [ ] Regressao: Fiscalizacao, Publicacao, Levantamento, Desenho e Qualidade.
- [ ] Regressao: exportacao, acompanhamento, historico e reatribuicao de Pagamento.

### Fase 6 — Saneamento controlado

- [ ] Revisar manualmente cada grupo apontado pela auditoria.
- [ ] Definir Production valida e redundante com base em execucao, usuario, datas e logs.
- [x] Disponibilizar `sicode:deduplicate-payment-productions` em modo diagnóstico (padrão), com grupos contextualizados, Parcial, Ciclo 1 legado e D5 que exige revisão explícita do ciclo.
- [x] Exigir, para escrita, `--keep`, todos os `--remove`, `--cycle`, `--reason`, `--apply` e `--yes`; a ferramenta nunca escolhe nem apaga uma Production sozinha.
- [x] Restringir a remoção a Productions abertas, sem usuário/atribuição/evidência de execução, e gravar o JSON do saneamento em `storage/app/audits/payment-production-deduplication/`.
- [x] Corrigir o contexto persistente da Production mantida na mesma transação quando a identidade legada for inequívoca.
- [ ] Nunca apagar ou alterar Production historica automaticamente na primeira execucao.
- [ ] Reexecutar auditoria e anexar comparativo antes/depois.

#### Uso da ferramenta de saneamento

```bash
# somente leitura; deve ser executado primeiro
php artisan sicode:deduplicate-payment-productions

# detalha a D5, seu Informe, os quatro estados do retorno e cada Production;
# use antes de decidir se o grupo legado é Ciclo 1 ou Ciclo 2
php artisan sicode:deduplicate-payment-productions --inspect-d5=600

# plano geral somente leitura: sugere --keep apenas quando encontra uma única
# Production fechada para o mesmo Ciclo 1 e abertas equivalentes
php artisan sicode:deduplicate-payment-productions --plan

# exemplo: Parcial  — IDs revisados manualmente
php artisan sicode:deduplicate-payment-productions \
  --keep=100 --remove=101 --remove=102 --cycle=partial \
  --reason="Duplicidade criada por duas tentativas simultâneas" --apply --yes

# Caso revisado: mantém a Production finalizada e remove duplicadas abertas
# que já foram atribuídas. Use somente após conferir a D5 com --inspect-d5.
php artisan sicode:deduplicate-payment-productions \
  --keep=4430 --remove-others --cycle=cycle_1 \
  --force-assigned --reason="Duplicidade aberta de Ciclo 1 da D5 600" --apply
```

Para `cycle_1` e `d5_return`, a opção `--cycle` é obrigatória justamente porque
uma D5 pode fazer parte tanto do Ciclo 1 quanto do retorno. Duplicadas com
usuário, atribuição, conclusão, análise ou retorno D5 não são removidas pela
ferramenta: precisam de saneamento manual documentado.

## Criterios de aceite

- [ ] Toda nova Production de Pagamento possui contexto persistente de demanda antes do commit.
- [ ] Uma Production de um Informe nao bloqueia outro Informe da mesma Nota.
- [ ] Uma Production Parcial nao bloqueia outro Parcial da mesma Nota.
- [ ] Ciclo 1 ocorre no maximo uma vez por Informe.
- [ ] Retorno D5 ocorre no maximo uma vez por D5.
- [ ] D5 arquivada nao reaparece; D5 nova e analisada separadamente.
- [ ] Frontend nao e fonte de verdade; backend revalida todos os IDs e estados.
- [ ] A mesma demanda concorrente gera no maximo uma Production.
- [ ] Nenhuma query de fila reduz acidentalmente varios Informes a uma Nota.
