# ETAPA 00 - Baseline e Segurança de Mudança

## Objetivo

Criar baseline funcional e técnico antes da refatoração.

## Entrada

- Estado atual da branch com `production-wall-v2` em produção.

## Saída

- Baseline documentado para comparação de regressão.
- Lista de endpoints e fluxos críticos mapeados.

## Tarefas

1. Registrar comportamento atual por módulo:
   - ADS
   - Análise de Projeto
   - Reclamações
2. Listar endpoints usados pelo wall:
   - payload completo da wall
   - payload por tela
   - payload por item/charts
   - endpoint dedicado do projeto (se mantido legado)
3. Capturar referência de tempos:
   - rotação de tela
   - rotação de serviço
   - refresh por componente
4. Congelar contrato de dados atual (chaves retornadas).

## Arquivos-alvo (somente leitura nesta etapa)

- `routes/api.php`
- `app/Services/Wall/WallDataOrchestrator.php`
- `resources/views/reports/production-wall-v2.blade.php`

## Critérios de aceite

- Existe um snapshot de payload por tipo de tela.
- Existe uma matriz de "funciona hoje" por módulo.
- Existe uma lista de dependências legadas a manter temporariamente.

## Riscos

- Iniciar refatoração sem baseline e perder referência de comportamento.

## Rollback

- Não aplicável (etapa de análise).

