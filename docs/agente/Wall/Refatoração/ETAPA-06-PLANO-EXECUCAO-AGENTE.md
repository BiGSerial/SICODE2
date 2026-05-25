# ETAPA 06 - Plano de Execução para Agentes (Contexto Controlado)

## Objetivo

Orientar execução incremental por lotes pequenos, com baixa chance de exaustão de contexto.

## Regras de execução

1. Uma etapa por vez.
2. Um lote de alteração por vez (max 3 arquivos críticos por lote).
3. Sempre fechar lote com:
   - diff resumido
   - validação
   - riscos
   - próximos passos
4. Não avançar etapa sem critérios de aceite da etapa atual.

## Sequência recomendada de lotes

1. Lote A (contrato)
   - criar contrato v2 e adapters no resolver
2. Lote B (orquestrador)
   - delegação por módulo e padronização de payload
3. Lote C (módulo piloto)
   - extrair `project_review` no frontend modular
4. Lote D (demais módulos)
   - extrair `ads` e `complaints`
5. Lote E (configurador)
   - novo form por módulo e timers por componente
6. Lote F (migração)
   - script e limpeza de legado

## Template de prompt para agente (por lote)

```text
Objetivo do lote:
- <descrever objetivo específico>

Arquivos permitidos:
- <lista restrita>

Restrições:
- Não editar fora da lista.
- Não remover fallback legado sem feature flag.
- Manter compatibilidade de payload.

Entregáveis:
- Código
- Resumo do diff
- Checklist de validação executada
- Riscos remanescentes
```

## Checklist de validação por lote

1. Build/compilação da camada alterada.
2. Smoke test das rotas afetadas.
3. Payload com chaves esperadas.
4. Sem erro JS no fluxo principal.
5. Sem regressão de rotação/refresh.

## Critérios de parada

- Se ocorrer conflito de contrato entre backend/frontend, parar e consolidar contrato antes de continuar.
- Se um lote tocar mais de 3 arquivos críticos, quebrar o lote.

