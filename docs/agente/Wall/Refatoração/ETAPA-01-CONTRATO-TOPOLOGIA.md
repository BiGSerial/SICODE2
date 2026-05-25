# ETAPA 01 - Contrato da Nova Topologia

## Objetivo

Definir contrato único de módulo para backend, frontend e configurador.

## Entrada

- Baseline da etapa 00.

## Saída

- Contrato `screen_config` v2 definido.
- Contrato de módulo (dados e ciclo de vida) definido.

## Proposta de contrato

## `wall_screens.screen_config` (v2)

```json
{
  "module_key": "project_review",
  "module_config": {},
  "timers": {
    "screen_rotation_seconds": 180,
    "service_rotation_seconds": 120,
    "components": {
      "cards": { "refresh_seconds": 30 },
      "project_review_dashboard": { "refresh_seconds": 60 }
    }
  },
  "legacy": {
    "screen_type": "fixed_chart",
    "fixed_chart": "project_review_dashboard"
  }
}
```

## Registry de módulos (backend)

- `ads`
- `project_review`
- `complaints`
- `production_services`

Cada módulo deve expor:

- `module_key`
- `supports_service_rotation` (bool)
- `default_timers`
- `component_keys`
- schema de configuração (`module_config_schema`)

## Ciclo de vida de módulo (frontend)

- `mount(container, context)`
- `update(componentKey, data)`
- `unmount()`

## Tarefas

1. Definir enum lógico de módulos (sem quebrar legado).
2. Definir map legado -> novo contrato:
   - `ads_chart` => `module_key=ads`
   - `fixed_chart + project_review_dashboard` => `module_key=project_review`
   - `fixed_chart + complaints_dashboard` => `module_key=complaints`
   - `production_services` => `module_key=production_services`
3. Definir chaves padrão de `component_keys` por módulo.

## Arquivos-alvo

- `app/Services/Wall/Context/ScreenContextResolver.php`
- `app/Services/Wall/Contracts/WallScreenDataService.php`
- `app/Services/Wall/WallDataOrchestrator.php`
- `app/Http/Controllers/Config/WallController.php`

## Critérios de aceite

- Existe contrato v2 claro e validável.
- Existe regra explícita de compatibilidade com campos legados.

## Riscos

- Refatorar sem contrato formal e espalhar lógica ad hoc.

## Rollback

- Manter suporte de leitura total ao legado até etapa 05.

