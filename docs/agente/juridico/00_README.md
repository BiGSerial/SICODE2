# SICODE - Módulo de Processos Comerciais/Jurídicos

Este pacote organiza as instruções para um agente de IA implementar, de forma incremental e observável, a entrada das três fontes atuais de processos no SICODE:

- Liminares;
- Sentenças / Cumprimentos;
- Subsídios.

A proposta não é apenas copiar três tabelas para dentro do SICODE. A solução separa:

1. **Processo jurídico de referência**;
2. **Demanda/ciclo tratável no SICODE**;
3. **Snapshot bruto da origem**;
4. **Tratativas internas com usuários da ponta**;
5. **Eventos de auditoria**;
6. **Arquivos/documentos com visibilidade controlada**;
7. **Métricas e observabilidade gerencial**.

## Objetivo principal

Criar um núcleo operacional único no SICODE para controlar processos vindos de fontes externas, permitindo que o controlador:

- veja quando a demanda entrou;
- classifique risco e prioridade;
- envie para usuário ou equipe da ponta;
- acompanhe recebimento, resposta, devolução e correção;
- anexe documentos e evidências;
- encerre internamente;
- registre encerramento no sistema externo;
- consulte histórico completo;
- produza indicadores gerenciais.

## Decisão arquitetural

A tabela unificada de operação será `legal_demands`.

Ela será diferenciada pela coluna `source_type`, com valores como:

```txt
liminar
sentence
subsidy
```

Porém, a origem bruta não deve ser descartada. Todo dado recebido deve ser preservado em snapshots, permitindo rastrear alterações, sumiço da fonte, reaparecimento e mudanças de prazo/responsável.

## Estrutura sugerida de documentos

Leia e execute nesta ordem:

1. [`01_etapa_01_dominio_modelagem.md`](01_etapa_01_dominio_modelagem.md)
2. [`02_etapa_02_migrations_indices.md`](02_etapa_02_migrations_indices.md)
3. [`03_etapa_03_importacao_normalizacao.md`](03_etapa_03_importacao_normalizacao.md)
4. [`04_etapa_04_workflow_tratativas.md`](04_etapa_04_workflow_tratativas.md)
5. [`05_etapa_05_arquivos_visibilidade.md`](05_etapa_05_arquivos_visibilidade.md)
6. [`06_etapa_06_observabilidade_bi.md`](06_etapa_06_observabilidade_bi.md)
7. [`07_etapa_07_testes_validacao.md`](07_etapa_07_testes_validacao.md)
8. [`08_checklist_geral_entrega.md`](08_checklist_geral_entrega.md)
9. [`09_prompt_mestre_agente_ia.md`](09_prompt_mestre_agente_ia.md)

## Regra de ouro

Nunca trate “não veio mais na origem” como encerramento automático.

O correto é:

```txt
não veio na origem = ausente na última leitura
```

O encerramento deve ser interno, explícito e auditável.

## Visão macro da modelagem

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

## Estados conceituais

### Processo jurídico

Representado por `legal_cases`.

É a referência-mãe. Um processo pode gerar várias demandas.

### Demanda

Representada por `legal_demands`.

É a unidade tratável no SICODE: liminar, subsídio, cumprimento, complemento, solicitação técnica etc.

### Tratativa

Representada por `legal_demand_assignments`.

É o envio da demanda para alguém resolver, responder, anexar evidência ou devolver.

### Evento

Representado por `legal_demand_events`.

É a linha do tempo auditável do módulo.

### Snapshot

Representado por `legal_source_snapshots`.

É a fotografia da origem externa no momento da importação.

## Entregável esperado

Ao final da implementação, o SICODE deve permitir responder:

- Quando entrou?
- De qual fonte veio?
- Qual processo-mãe?
- É liminar, sentença/cumprimento ou subsídio?
- Quem recebeu?
- Quem enviou?
- Para quem foi enviado?
- Quando a ponta recebeu?
- Quando a ponta respondeu?
- Quantas devoluções ocorreram?
- Quem encerrou?
- Quando encerrou internamente?
- Quando encerrou externamente?
- O processo reapareceu?
- O prazo venceu?
- Quais arquivos estão anexados?
- Quem pode ver cada arquivo?
- Qual etapa está gerando gargalo?

## Convenções recomendadas

- Usar UUID público nas telas.
- Usar `id` incremental interno nas relações.
- Preservar nomes vindos da origem em campos textuais.
- Usar `raw_payload` em JSON/JSONB para manter o registro original.
- Usar eventos para toda mudança relevante.
- Não sobrescrever histórico.
- Não excluir demandas importadas, salvo rotina administrativa excepcional e auditada.
