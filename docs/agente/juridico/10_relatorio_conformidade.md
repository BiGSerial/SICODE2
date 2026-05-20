# Relatório de Conformidade - Módulo Jurídico

Data: 2026-05-14  
Base: `docs/agente/juridico/08_checklist_geral_entrega.md`

## Legenda

- `OK`: implementado e validado.
- `PARCIAL`: implementado parcialmente ou sem camada de UI/notificação.
- `PENDENTE`: não implementado.

## 1. Modelagem

- `OK` `legal_cases` criado.
- `OK` `legal_demands` criado.
- `OK` `legal_import_batches` criado.
- `OK` `legal_source_snapshots` criado.
- `OK` `legal_demand_assignments` criado.
- `OK` `legal_demand_events` criado.
- `OK` `legal_demand_files` criado.
- `OK` `legal_demand_comments` criado.
- `OK` Relações Eloquent criadas.
- `OK` Enums/status documentados em código (`app/Enum/Legal*`).
- `PARCIAL` Regras de domínio documentadas: documentação-base existe, mas faltam notas de arquitetura dentro da aplicação (ex.: ADR interna).

## 2. Importação

- `OK` Importador de liminares criado.
- `OK` Importador de sentenças/cumprimentos criado.
- `OK` Importador de subsídios criado.
- `OK` Importador geral criado.
- `OK` Modo `--dry` criado.
- `OK` Normalização de processo criada.
- `OK` Normalização de texto criada.
- `OK` Parser de datas criado.
- `OK` `source_record_key` criado.
- `OK` `source_hash` criado.
- `OK` Snapshots criados.
- `OK` Batch de importação criado.
- `OK` Missing detectado.
- `OK` Reaparecimento detectado.
- `OK` Erros de linha registrados sem abortar todo batch.

## 3. Workflow

- `OK` Triagem criada.
- `OK` Envio para usuário criado.
- `OK` Envio para equipe criado.
- `OK` Recebimento criado.
- `OK` Resposta da ponta criada.
- `OK` Revisão do controlador criada.
- `OK` Devolução para correção criada.
- `OK` Encerramento interno criado.
- `OK` Encerramento externo criado.
- `OK` Reabertura criada.
- `PARCIAL` Permissões criadas: validação por `can()` implementada nos services, mas sem catálogo/seed central de permissões.
- `PENDENTE` Notificações criadas, se aplicável.

## 4. Arquivos

- `OK` Vínculo de arquivos por demanda.
- `OK` Vínculo de arquivos por assignment.
- `OK` Categorias de arquivos.
- `OK` Visibilidade de arquivos.
- `OK` Evento de arquivo anexado.
- `OK` Evento de visibilidade alterada.
- `OK` Remoção lógica.
- `PARCIAL` Comentários com visibilidade: comentário existe, porém regra de visibilidade de comentário não foi aplicada em service dedicado.
- `OK` Documento final marcado como `external_ready`.

## 5. Observabilidade técnica

- `OK` Log estruturado de importação.
- `OK` Contador de novas.
- `OK` Contador de atualizadas.
- `OK` Contador de inalteradas.
- `OK` Contador de ausentes.
- `OK` Contador de falhas.
- `OK` Duração total da importação.
- `OK` Evento para toda transição principal implementada.
- `PENDENTE` Timeline visual.
- `PARCIAL` Snapshots consultáveis: persistidos e relacionamentos criados; falta tela/consulta dedicada.
- `PARCIAL` Erros por linha consultáveis: registrados no batch/error_message, sem tela/listagem dedicada.

## 6. BI operacional

- `OK` Card de abertas.
- `OK` Card de vencidas.
- `OK` Card de vencem hoje.
- `OK` Card de vencem em 3 dias.
- `OK` Card de sem responsável.
- `OK` Card de aguardando ponta.
- `OK` Card de devolvidas pela ponta.
- `OK` Card de em revisão.
- `OK` Card de encerradas internamente sem encerramento externo.
- `OK` Card de reabertas.
- `OK` Card de missing na origem.
- `OK` Painel por origem (query backend).
- `OK` Painel por área (query backend).
- `OK` Painel por usuário (query backend).
- `OK` Painel de gargalos (query backend).

## 7. Testes

- `OK` Teste de normalização de número do processo.
- `OK` Teste de parse de datas.
- `OK` Teste de importação idempotente.
- `OK` Teste de alteração de hash.
- `OK` Teste de snapshot.
- `OK` Teste de missing.
- `OK` Teste de reaparecimento.
- `OK` Teste de processo com múltiplas demandas.
- `PARCIAL` Teste de envio para ponta (coberto indiretamente por reenvio; falta caso explícito de primeiro envio + evento completo).
- `OK` Teste de resposta da ponta (bloqueio em encerrada e regras do workflow cobertas).
- `PARCIAL` Teste de devolução para correção (fluxo implementado, sem teste dedicado).
- `OK` Teste de encerramento interno.
- `PARCIAL` Teste de encerramento externo (fluxo implementado, sem teste dedicado específico).
- `OK` Teste de visibilidade de arquivos.
- `OK` Teste de dashboard crítico.

## 8. Critérios finais de aceite

- `OK` O SICODE consegue consolidar liminares, sentenças/cumprimentos e subsídios em uma operação única.
- `OK` O usuário consegue diferenciar a origem por `source_type`.
- `OK` O mesmo processo pode ter várias demandas.
- `OK` O sistema não duplica demanda em reimportação.
- `OK` O sistema não encerra demanda apenas porque sumiu da origem.
- `OK` O sistema registra reaparecimento.
- `OK` O controlador consegue enviar para ponta.
- `OK` A ponta consegue responder.
- `OK` O controlador consegue revisar e devolver.
- `OK` O controlador consegue encerrar internamente.
- `OK` O controlador consegue registrar encerramento externo.
- `OK` Arquivos possuem visibilidade.
- `PARCIAL` A timeline permite auditoria completa (backend de eventos pronto; falta tela de timeline).
- `PARCIAL` Os dashboards mostram gargalos e vencimentos (queries/command prontos; falta tela final de dashboard jurídico).

## Resumo executivo

- Itens `OK`: núcleo funcional completo (modelagem, importação, workflow, arquivos, observabilidade backend e suíte principal de testes).
- Principais pendências: camada de UI (timeline/lista/detalhe/dashboard), notificações, e formalização central de permissões.
