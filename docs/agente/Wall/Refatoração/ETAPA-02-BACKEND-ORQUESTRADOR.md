# ETAPA 02 - Backend Orquestrador Puro

## Objetivo

Fazer o backend atuar como orquestrador puro de módulos, sem regras de apresentação embutidas fora dos serviços de módulo.

## Entrada

- Contrato da etapa 01 aprovado.

## Saída

- Orquestrador delegando por `module_key`.
- Serviços de módulo responsáveis por dados/componentes.

## Tarefas

1. Criar registry/factory de módulo no backend.
2. Evoluir `ScreenContextResolver` para resolver módulo via `screen_config.module_key` com fallback legado.
3. Padronizar respostas de componente por módulo:
   - `cards`
   - `module_dashboard` (nome interno por módulo)
   - componentes adicionais quando necessário
4. Remover caminho especial que bypassa orquestrador (quando aplicável) ou marcar como legado temporário.
5. Padronizar mensagens de erro e placeholders por módulo.

## Arquivos-alvo

- `app/Services/Wall/WallDataOrchestrator.php`
- `app/Services/Wall/Context/ScreenContextResolver.php`
- `app/Services/Wall/Screen/FixedChartScreenDataService.php`
- `app/Services/Wall/Fixed/*`
- `app/Http/Controllers/Api/Reports/*ProductionWallV2*.php`

## Critérios de aceite

- Nenhum controller de API precisa conhecer detalhe do módulo.
- Troca de módulo ocorre só via contrato/configuração.
- Endpoints antigos continuam respondendo (com adapter/fallback) até etapa 05.

## Riscos

- Acoplamento residual em endpoint dedicado.
- Divergência de estrutura entre módulos.

## Rollback

- Feature flag de leitura do contrato v2.
- Fallback para `screen_type/fixed_chart` legado.

