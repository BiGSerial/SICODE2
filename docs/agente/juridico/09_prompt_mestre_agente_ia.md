# Prompt mestre para agente de IA

Use este prompt para orientar um agente de IA responsável por implementar o módulo no SICODE.

---

Você é um agente de IA trabalhando em um sistema Laravel/Livewire chamado SICODE. Sua tarefa é implementar um módulo de Processos Comerciais/Jurídicos para centralizar demandas vindas de três fontes externas:

- Liminares;
- Sentenças/Cumprimentos;
- Subsídios.

## Objetivo

Criar uma estrutura interna no SICODE para importar, consolidar, tratar, auditar e monitorar essas demandas.

A solução deve permitir:

- identificar o processo jurídico de referência;
- criar demandas internas vinculadas ao processo;
- diferenciar a origem da demanda por `source_type`;
- preservar o payload bruto recebido;
- controlar envio para usuário/equipe da ponta;
- registrar recebimento, resposta, devolução e encerramento;
- anexar arquivos com visibilidade;
- separar encerramento interno de encerramento externo;
- produzir linha do tempo auditável;
- gerar métricas gerenciais.

## Decisão de arquitetura obrigatória

Não crie apenas uma tabela única gigante.

Implemente o núcleo abaixo:

```txt
legal_cases
  └── legal_demands
        ├── legal_demand_assignments
        ├── legal_demand_events
        ├── legal_demand_comments
        ├── legal_demand_files
        └── legal_source_snapshots

legal_import_batches
```

## Regras fundamentais

### 1. Processo jurídico não é demanda

O mesmo número de processo pode aparecer em várias origens e gerar várias demandas.

Portanto:

```txt
legal_cases = referência do processo
legal_demands = ciclos/demandas tratáveis
```

### 2. Não encerrar porque sumiu da origem

Se uma linha deixar de aparecer na fonte externa, marque:

```txt
source_presence_status = missing
missing_since = now()
```

Não altere automaticamente para encerrada.

### 3. Reaparecimento deve ser auditado

Se uma demanda marcada como `missing` voltar:

```txt
source_presence_status = returned ou present
missing_since = null
evento = source_returned
```

### 4. Encerramento interno e externo são diferentes

Encerramento no SICODE:

```txt
closed_at
closed_by
closure_reason
internal_status = closed_internal
```

Encerramento no programa externo:

```txt
external_closed_at
external_protocol
external_closure_note
internal_status = closed_external
```

### 5. Toda ação relevante deve gerar evento

Use `legal_demand_events`.

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

## Etapa 1 - Criar modelagem

Crie migrations, models e relações para:

```txt
legal_cases
legal_demands
legal_import_batches
legal_source_snapshots
legal_demand_assignments
legal_demand_events
legal_demand_files
legal_demand_comments
```

Entregue:

- migrations;
- models;
- relações Eloquent;
- enums ou constantes de status;
- factories para testes.

## Etapa 2 - Criar importadores

Crie services e commands para importar:

```bash
php artisan legal:import-liminares
php artisan legal:import-sentences
php artisan legal:import-subsidies
php artisan legal:import-all
```

Cada command deve aceitar:

```bash
--dry
--limit=
--since=
--force-snapshot
--no-missing-check
```

Crie:

```txt
LegalImportService
LegalSourceNormalizer
LegalDemandKeyGenerator
LegalDemandHashGenerator
LegalCaseUpserter
LegalDemandUpserter
LegalSnapshotRecorder
LegalDemandEventLogger
LegalMissingMarker
```

## Etapa 3 - Criar deduplicação

Crie `source_record_key`.

Sugestão:

```txt
hash(source_type + source_external_id + process_number_normalized + normalized_subject + source_started_at + source_redirected_at)
```

Crie `source_hash` para detectar alterações relevantes.

A importação deve ser idempotente.

## Etapa 4 - Criar workflow interno

Implemente ações/services para:

- iniciar triagem;
- enviar para ponta;
- marcar recebimento;
- responder;
- revisar;
- devolver para correção;
- encerrar internamente;
- encerrar externamente;
- reabrir.

Cada ação deve:

- validar permissão;
- validar transição;
- atualizar status;
- registrar evento;
- preservar histórico.

## Etapa 5 - Criar arquivos e comentários

Implemente:

- anexos por demanda;
- anexos por assignment;
- categorias;
- visibilidade;
- comentários internos;
- comentários de resposta;
- evento de arquivo anexado;
- evento de alteração de visibilidade.

Visibilidades:

```txt
controller_only
assigned_user_only
internal_all
legal_area
external_ready
```

Categorias:

```txt
legal_document
technical_evidence
field_return
controller_note
external_protocol
final_response
other
```

## Etapa 6 - Criar telas Livewire

Criar telas mobile-friendly e eficientes:

### Lista de demandas

Filtros:

```txt
source_type
company_name
law_firm_name
origin_area_name
target_area_name
internal_status
source_presence_status
priority
risk_level
source_due_at
controller_user_id
current_assigned_user_id
```

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

## Etapa 7 - Criar dashboards

Criar cards:

```txt
abertas
vencidas
vencem hoje
vencem em 3 dias
sem responsável
aguardando ponta
devolvidas pela ponta
em revisão
prontas para encerramento externo
encerradas internamente sem encerramento externo
reabertas
missing na origem
```

Criar painéis:

```txt
por origem
por área
por usuário
por gargalo
```

## Etapa 8 - Criar testes

Testes obrigatórios:

- importação idempotente;
- mudança de prazo gera snapshot;
- sumiço da origem não encerra;
- reaparecimento gera evento;
- mesmo processo pode ter várias demandas;
- envio para ponta cria assignment;
- resposta da ponta preserva histórico;
- encerramento interno separado de externo;
- visibilidade de arquivo bloqueia acesso indevido;
- dashboard de vencidas retorna corretamente.

## Checklist final do agente

Antes de concluir, valide:

- [ ] Migrations executam.
- [ ] Models possuem relações.
- [ ] Importadores rodam com `--dry`.
- [ ] Importação é idempotente.
- [ ] Snapshots são criados.
- [ ] Eventos são criados.
- [ ] Missing não encerra demanda.
- [ ] Reaparecimento é registrado.
- [ ] Workflow funciona.
- [ ] Arquivos possuem visibilidade.
- [ ] Encerramento interno/externo separado.
- [ ] Dashboards básicos funcionam.
- [ ] Testes principais passam.

## Formato da resposta do agente

Ao entregar a implementação, responda com:

```txt
1. Arquivos criados/alterados
2. Migrations criadas
3. Models criados
4. Services criados
5. Commands criados
6. Telas Livewire criadas
7. Testes criados
8. Como executar
9. Como validar
10. Pendências ou decisões que precisam de aprovação
```

Não omita pendências. Se algo ficar incompleto, informe claramente.
