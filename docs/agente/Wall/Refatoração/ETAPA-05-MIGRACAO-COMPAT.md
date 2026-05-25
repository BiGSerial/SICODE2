# ETAPA 05 - Migração e Compatibilidade

## Objetivo

Migrar sem quebra de operação e remover legado de forma controlada.

## Entrada

- Backend, frontend e configurador no modelo modular.

## Saída

- Ambiente operando 100% em contrato v2.
- Legado removido com segurança.

## Estratégia

1. Dual-read
   - Ler v2 primeiro.
   - Fallback para legado (`screen_type`, `fixed_chart`, `ads_chart`).
2. Dual-write temporário
   - Salvar v2.
   - Opcionalmente preencher campos legados necessários.
3. Migração de dados
   - Script para preencher `screen_config.module_key` e `screen_config.timers`.
   - Registrar telas que não puderam ser convertidas automaticamente.
4. Observabilidade
   - logar uso de fallback legado por tela.
   - acompanhar erros por módulo.
5. Cutover
   - desligar fallback após janela estável.
   - remover código legado.

## Itens de migração de dados

- `screen_type=ads_chart` => `module_key=ads`
- `screen_type=fixed_chart + fixed_chart=project_review_dashboard` => `module_key=project_review`
- `screen_type=fixed_chart + fixed_chart=complaints_dashboard` => `module_key=complaints`
- `screen_type=production_services` => `module_key=production_services`

## Critérios de aceite

- 100% das telas válidas em v2.
- Sem uso de fallback por período definido (ex.: 7 dias).

## Riscos

- Configurações antigas inválidas para schema novo.

## Rollback

- Reativar dual-read.
- Reverter somente camada de resolução de contrato.

