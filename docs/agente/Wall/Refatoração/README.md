# Refatoração do WALL V2

Este diretório descreve a refatoração para que o `production-wall-v2` seja apenas orquestrador e cada módulo tenha regras e view próprias.

## Objetivo

- Separar orquestração de renderização.
- Isolar lógica por módulo (`ads`, `project_review`, `complaints`, etc.).
- Evoluir o configurador para suportar topologia modular.
- Executar em etapas pequenas para evitar exaustão de contexto do agente.

## Etapas

1. [ETAPA-00-BASELINE](./ETAPA-00-BASELINE.md)
2. [ETAPA-01-CONTRATO-TOPOLOGIA](./ETAPA-01-CONTRATO-TOPOLOGIA.md)
3. [ETAPA-02-BACKEND-ORQUESTRADOR](./ETAPA-02-BACKEND-ORQUESTRADOR.md)
4. [ETAPA-03-FRONTEND-SHELL-MODULOS](./ETAPA-03-FRONTEND-SHELL-MODULOS.md)
5. [ETAPA-04-CONFIGURADOR-WALL](./ETAPA-04-CONFIGURADOR-WALL.md)
6. [ETAPA-05-MIGRACAO-COMPAT](./ETAPA-05-MIGRACAO-COMPAT.md)
7. [ETAPA-06-PLANO-EXECUCAO-AGENTE](./ETAPA-06-PLANO-EXECUCAO-AGENTE.md)

## Escopo atual (referência)

- Orquestração: `app/Services/Wall/WallDataOrchestrator.php`
- Resolver de contexto: `app/Services/Wall/Context/ScreenContextResolver.php`
- Serviços fixos:
  - `app/Services/Wall/Fixed/AdsFixedDashboardDataService.php`
  - `app/Services/Wall/Fixed/ProjectReviewFixedDashboardDataService.php`
  - `app/Services/Wall/Fixed/ComplaintsFixedDashboardDataService.php`
- Tela monolítica atual:
  - `resources/views/reports/production-wall-v2.blade.php`
- Configurador:
  - `app/Http/Controllers/Config/WallController.php`
  - `resources/views/config/wall/index.blade.php`

