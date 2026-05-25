# Etapa 06 - Observabilidade, indicadores e BI operacional

## Objetivo

Transformar a operação do módulo em dados gerenciais confiáveis.

O SICODE precisa responder não apenas “qual demanda está aberta?”, mas também:

- onde está parada;
- há quanto tempo está parada;
- quem recebeu;
- quem ainda não respondeu;
- qual área mais atrasa;
- quais demandas vencem hoje;
- quais demandas já venceram;
- quais retornaram depois de encerradas;
- quais sumiram da origem;
- quais reapareceram.

## Observabilidade técnica

### Logs de importação

Cada importação deve gerar log com:

```txt
source_type
batch_id
started_at
finished_at
duration_seconds
total_rows
new_rows
updated_rows
unchanged_rows
missing_rows
failed_rows
status
error_message
```

### Logs de workflow

Cada ação relevante deve ter evento em `legal_demand_events`.

Eventos mínimos:

```txt
imported
updated_from_source
source_missing
source_returned
reopened_from_source
triage_started
priority_changed
sent_to_field
field_received
field_answered
returned_to_controller
returned_for_correction
controller_approved
internal_closed
external_closed
file_attached
file_visibility_changed
comment_added
assignment_cancelled
```

### Métricas operacionais

Criar consultas ou cards para:

```txt
total_abertas
total_vencidas
total_vencem_hoje
total_vencem_3_dias
total_sem_responsavel
total_aguardando_ponta
total_devolvidas_pela_ponta
total_em_revisao_controlador
total_prontas_para_encerrar_externo
total_encerradas_internamente_sem_encerramento_externo
total_reabertas
total_missing_source
```

## Dashboards recomendados

### Dashboard 1 - Visão geral

Cards:

- abertas;
- vencidas;
- vencem hoje;
- vencem em até 3 dias;
- sem responsável;
- aguardando ponta;
- em revisão;
- prontas para encerramento externo;
- encerradas no SICODE, mas não no externo.

### Dashboard 2 - Por origem

Tabela ou gráfico:

```txt
source_type | abertas | vencidas | tempo médio | reabertas | missing
```

### Dashboard 3 - Por área

```txt
target_area_name | abertas | vencidas | tempo médio de resposta | sem responsável
```

### Dashboard 4 - Por usuário da ponta

```txt
usuário | recebidas | pendentes | vencidas | tempo médio resposta
```

### Dashboard 5 - Gargalos

Métricas:

- tempo médio da importação até triagem;
- tempo médio da triagem até envio;
- tempo médio do envio até recebimento;
- tempo médio do recebimento até resposta;
- tempo médio da resposta até encerramento interno;
- tempo médio do encerramento interno até encerramento externo.

## SLA interno sugerido

Criar campos calculados ou consultas:

```txt
age_since_import
age_since_assignment
age_since_received
age_since_answered
days_until_source_due
is_overdue
is_due_today
is_due_soon
```

## Filtros úteis

```txt
source_type
company_name
law_firm_name
legal_responsible_name
origin_area_name
target_area_name
target_person_name
internal_status
source_presence_status
priority
risk_level
source_due_at
controller_user_id
current_assigned_user_id
```

## Alertas recomendados

Gerar alertas/notificações para:

- demanda nova crítica;
- vencimento hoje;
- vencimento em 3 dias;
- vencida;
- ponta não recebeu após X horas;
- ponta recebeu e não respondeu após X dias;
- encerrada internamente sem encerramento externo após X dias;
- demanda reapareceu na origem;
- demanda sumiu da origem;
- prazo externo alterado.

## Telas recomendadas

### Lista de demandas

Colunas:

```txt
Processo
Origem
Assunto
Prazo
Status SICODE
Status origem
Responsável atual
Área destino
Prioridade
Risco
Última atualização
```

### Detalhe da demanda

Blocos:

```txt
Resumo
Dados do processo
Dados da origem
Prazo e risco
Responsável atual
Tratativas
Arquivos
Comentários
Timeline
Snapshots da origem
Encerramento
```

### Timeline

Exibir eventos com:

```txt
data/hora
ator
evento
descrição
status anterior
status novo
metadata relevante
```

## Checklist da etapa

- [ ] Criar cards de visão geral.
- [ ] Criar filtros principais.
- [ ] Criar tela de lista.
- [ ] Criar tela de detalhe.
- [ ] Criar timeline.
- [ ] Criar visualização de snapshots.
- [ ] Criar painel por origem.
- [ ] Criar painel por área.
- [ ] Criar painel por usuário.
- [ ] Criar painel de gargalos.
- [ ] Criar alertas de prazo.
- [ ] Criar alertas de reaparecimento.
- [ ] Criar alertas de ausência na fonte.
- [ ] Criar indicadores de encerramento externo pendente.

## Observabilidade da etapa

O agente deve entregar evidências de:

- [ ] Cada card tem query rastreável.
- [ ] Cada métrica pode ser conferida por SQL.
- [ ] Dashboard não depende de status textual da origem para medir etapa interna.
- [ ] Eventos alimentam linha do tempo.
- [ ] Assignments alimentam tempos de ponta.
- [ ] Batches alimentam saúde da importação.
- [ ] Snapshots permitem comparar alterações da origem.
- [ ] Existe filtro para demandas `missing`.
- [ ] Existe filtro para demandas `reopened`.
- [ ] Existe filtro para demandas `closed_internal` sem `external_closed_at`.
