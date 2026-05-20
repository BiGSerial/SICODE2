# ETAPA 03 - Frontend Shell + Módulos Independentes

## Objetivo

Transformar `production-wall-v2` em shell de orquestração e mover views/regras para módulos independentes.

## Entrada

- Backend com contrato modular estável (etapa 02).

## Saída

- Shell enxuto.
- Módulos de render independentes por domínio.

## Tarefas

1. Criar shell de wall:
   - Header global
   - Containers de tela/painel
   - Scheduler global
2. Extrair renderer por módulo:
   - `ads`
   - `project_review`
   - `complaints`
   - `production_services` (quando aplicável)
3. Substituir condicionais por `service_id` no render por registry:
   - `moduleRegistry[moduleKey].mount/update/unmount`
4. Centralizar timers:
   - um scheduler global com deadlines
   - sem múltiplos `setInterval` concorrentes por card
5. Garantir atualização assíncrona por componente com lock:
   - `inFlight` por componente
   - `AbortController` em troca de tela/serviço
6. Preservar comportamento de rotação e refresh já configurado.

## Estrutura sugerida

- `resources/js/wall-v2/shell/*`
- `resources/js/wall-v2/modules/ads/*`
- `resources/js/wall-v2/modules/project-review/*`
- `resources/js/wall-v2/modules/complaints/*`
- `resources/js/wall-v2/modules/production-services/*`

## Arquivos-alvo

- `resources/views/reports/production-wall-v2.blade.php` (reduzir para shell)
- novos JS/CSS por módulo

## Critérios de aceite

- `production-wall-v2` sem regras específicas de módulo.
- Cada módulo renderiza sem conhecer outro módulo.
- Troca de módulo sem vazamento de timer/listener.

## Riscos

- Regressão de UX por mudança de ciclo de render.
- Vazamento de listeners ao trocar módulos.

## Rollback

- Manter render legado atrás de flag até validação completa.

