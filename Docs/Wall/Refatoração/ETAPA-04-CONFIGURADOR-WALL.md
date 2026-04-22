# ETAPA 04 - Configurador Adaptado à Nova Topologia

## Objetivo

Evoluir o configurador para gerenciar módulos, contratos e timers por componente.

## Entrada

- Contrato v2 e módulos já funcionais.

## Saída

- Configurador orientado a `module_key` e schema dinâmico de `module_config`.
- Timers configuráveis por componente.

## Mudanças necessárias

1. Modelo de tela
   - trocar foco de `screen_type` para `module_key`.
   - manter `screen_type` como legado/compatibilidade temporária.
2. Formulário
   - selecionar módulo.
   - renderizar campos de `module_config` por schema.
   - configurar timers:
     - rotação de tela
     - rotação de serviço (se suportado)
     - refresh por componente
3. Validação backend
   - validar `module_key`.
   - validar `module_config` com schema do módulo.
   - validar `timers.components` por chave suportada.
4. Persistência
   - salvar em `screen_config` no formato v2.
   - manter bloco `legacy` enquanto houver compatibilidade.
5. UI de itens de serviço
   - exibir apenas quando módulo suporta lista de serviços.
   - ocultar para módulos fixos quando não aplicável.

## Arquivos-alvo

- `app/Http/Controllers/Config/WallController.php`
- `resources/views/config/wall/index.blade.php`
- (opcional) serviço dedicado de schema de módulo no backend

## Critérios de aceite

- Usuário consegue criar tela apenas escolhendo módulo.
- Configurador bloqueia combinações inválidas de campos.
- Configuração de timers por componente reflete no runtime.

## Riscos

- Misturar regras novas e antigas no mesmo form sem normalização.

## Rollback

- Adapter de payload no controller aceitando legado e v2.

