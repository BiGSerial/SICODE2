# Checklist geral de entrega

## 1. Modelagem

- [ ] `legal_cases` criado.
- [ ] `legal_demands` criado.
- [ ] `legal_import_batches` criado.
- [ ] `legal_source_snapshots` criado.
- [ ] `legal_demand_assignments` criado.
- [ ] `legal_demand_events` criado.
- [ ] `legal_demand_files` criado.
- [ ] `legal_demand_comments` criado.
- [ ] Relações Eloquent criadas.
- [ ] Enums/status documentados.
- [ ] Regras de domínio documentadas.

## 2. Importação

- [ ] Importador de liminares criado.
- [ ] Importador de sentenças/cumprimentos criado.
- [ ] Importador de subsídios criado.
- [ ] Importador geral criado.
- [ ] Modo `--dry` criado.
- [ ] Normalização de processo criada.
- [ ] Normalização de texto criada.
- [ ] Parser de datas criado.
- [ ] `source_record_key` criado.
- [ ] `source_hash` criado.
- [ ] Snapshots criados.
- [ ] Batch de importação criado.
- [ ] Missing detectado.
- [ ] Reaparecimento detectado.
- [ ] Erros de linha registrados sem abortar todo batch.

## 3. Workflow

- [ ] Triagem criada.
- [ ] Envio para usuário criado.
- [ ] Envio para equipe criado.
- [ ] Recebimento criado.
- [ ] Resposta da ponta criada.
- [ ] Revisão do controlador criada.
- [ ] Devolução para correção criada.
- [ ] Encerramento interno criado.
- [ ] Encerramento externo criado.
- [ ] Reabertura criada.
- [ ] Permissões criadas.
- [ ] Notificações criadas, se aplicável.

## 4. Arquivos

- [ ] Vínculo de arquivos por demanda.
- [ ] Vínculo de arquivos por assignment.
- [ ] Categorias de arquivos.
- [ ] Visibilidade de arquivos.
- [ ] Evento de arquivo anexado.
- [ ] Evento de visibilidade alterada.
- [ ] Remoção lógica.
- [ ] Comentários com visibilidade.
- [ ] Documento final marcado como `external_ready`.

## 5. Observabilidade técnica

- [ ] Log estruturado de importação.
- [ ] Contador de novas.
- [ ] Contador de atualizadas.
- [ ] Contador de inalteradas.
- [ ] Contador de ausentes.
- [ ] Contador de falhas.
- [ ] Duração total da importação.
- [ ] Evento para toda transição.
- [ ] Timeline visual.
- [ ] Snapshots consultáveis.
- [ ] Erros por linha consultáveis.

## 6. BI operacional

- [ ] Card de abertas.
- [ ] Card de vencidas.
- [ ] Card de vencem hoje.
- [ ] Card de vencem em 3 dias.
- [ ] Card de sem responsável.
- [ ] Card de aguardando ponta.
- [ ] Card de devolvidas pela ponta.
- [ ] Card de em revisão.
- [ ] Card de encerradas internamente sem encerramento externo.
- [ ] Card de reabertas.
- [ ] Card de missing na origem.
- [ ] Painel por origem.
- [ ] Painel por área.
- [ ] Painel por usuário.
- [ ] Painel de gargalos.

## 7. Testes

- [ ] Teste de normalização de número do processo.
- [ ] Teste de parse de datas.
- [ ] Teste de importação idempotente.
- [ ] Teste de alteração de hash.
- [ ] Teste de snapshot.
- [ ] Teste de missing.
- [ ] Teste de reaparecimento.
- [ ] Teste de processo com múltiplas demandas.
- [ ] Teste de envio para ponta.
- [ ] Teste de resposta da ponta.
- [ ] Teste de devolução para correção.
- [ ] Teste de encerramento interno.
- [ ] Teste de encerramento externo.
- [ ] Teste de visibilidade de arquivos.
- [ ] Teste de dashboard crítico.

## 8. Critérios finais de aceite

- [ ] O SICODE consegue consolidar liminares, sentenças/cumprimentos e subsídios em uma operação única.
- [ ] O usuário consegue diferenciar a origem por `source_type`.
- [ ] O mesmo processo pode ter várias demandas.
- [ ] O sistema não duplica demanda em reimportação.
- [ ] O sistema não encerra demanda apenas porque sumiu da origem.
- [ ] O sistema registra reaparecimento.
- [ ] O controlador consegue enviar para ponta.
- [ ] A ponta consegue responder.
- [ ] O controlador consegue revisar e devolver.
- [ ] O controlador consegue encerrar internamente.
- [ ] O controlador consegue registrar encerramento externo.
- [ ] Arquivos possuem visibilidade.
- [ ] A timeline permite auditoria completa.
- [ ] Os dashboards mostram gargalos e vencimentos.
