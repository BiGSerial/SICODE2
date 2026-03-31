# Menu Superior (Topbar)

Este documento explica como o menu superior do SICODE2 funciona e como adicionar novos itens.

## Arquivos principais

- Montagem dos menus da topbar: `resources/views/layouts/menu_itens.blade.php`
- Componente base de dropdown: `resources/views/components/menu/dynamic-dropdown.blade.php`
- Render recursivo de nós (layout inline): `resources/views/components/menu/partials/dynamic-dropdown-node.blade.php`
- Menus especializados:
  - `resources/views/components/menu/activities-dropdown.blade.php`
  - `resources/views/components/menu/services-dropdown.blade.php`
  - `resources/views/components/menu/engineer-dropdown.blade.php`
  - `resources/views/components/menu/responsible-dropdown.blade.php`

## Como o menu é montado

O `menu_itens.blade.php` define os blocos da navegação superior (ex.: `ADMINISTRAÇÃO`, `RECLAMAÇÕES`, `ATIVIDADES`, `SERVIÇOS`) e chama `<x-menu.dynamic-dropdown ... />`.

O `dynamic-dropdown` aceita duas formas de estrutura:

1. `sections` (formato mais simples para menus em blocos)
2. `nodes` (formato recursivo, com grupos e subgrupos)

No layout atual da topbar, o padrão predominante é `layout="inline"`.

## Estrutura de dados suportada

### Item simples

```php
[
    'label' => 'OCORRÊNCIAS',
    'route' => 'occurrences.index', // ou 'href' => '...'
    'icon' => 'ri-alarm-warning-line',
    'can' => 'occ.access', // opcional (Gate/Policy)
]
```

### Grupo (submenu)

```php
[
    'kind' => 'group',
    'label' => 'RELATÓRIOS',
    'open' => 'side', // 'side' ou 'down'
    'nodes' => [
        ['label' => 'RELATÓRIO DE PRODUÇÃO', 'route' => 'reports.productions', 'icon' => 'ri-file-chart-line'],
    ],
]
```

### Header (somente com `nodes`)

```php
[
    'kind' => 'header',
    'label' => 'SEÇÃO',
]
```

## Campos úteis de item/nó

- `label`: texto exibido
- `route`: nome da rota Laravel (preferencial)
- `routeParams`: parâmetros da rota
- `href`: URL direta (fallback)
- `icon`: classe do ícone (RemixIcon/Bootstrap Icons)
- `iconClass`: cor/classe extra do ícone no layout inline
  - Exemplo usado para despacho: `text-danger`
- `can`: permissão avaliada com `auth()->user()->can(...)`
- `visible`: força ocultar/exibir (`false` oculta)
- `countComponent`, `countParams`, `countKey`: contador Livewire ao lado do item

## Regras de visibilidade

A visibilidade é tratada no `dynamic-dropdown`:

- Se `visible === false`, o nó não renderiza.
- Se existir `can` e o usuário não possuir permissão, o nó não renderiza.
- Grupos vazios (sem filhos visíveis) são removidos automaticamente.

## Como adicionar novos itens

### 1) Item direto em menu existente

Exemplo em `resources/views/layouts/menu_itens.blade.php`, dentro de `$protests_nodes`:

```php
[
    'kind' => 'group',
    'label' => 'SERVIÇO',
    'open' => 'side',
    'nodes' => [
        [
            'label' => 'NOVA TELA',
            'route' => 'protests.services.nova_rota',
            'icon' => 'ri-add-circle-line',
        ],
    ],
]
```

### 2) Item com permissão

```php
[
    'label' => 'CONFIG AVANÇADA',
    'route' => 'admin.advanced',
    'icon' => 'ri-settings-3-line',
    'can' => 'superadm',
]
```

### 3) Item com contador Livewire

```php
[
    'label' => 'RECLAMAÇÕES',
    'route' => 'protests.services.main',
    'icon' => 'ri-account-pin-box-fill',
    'countComponent' => 'components.count.protest.count-protests',
    'countKey' => 'menu_protests_count',
]
```

### 4) Ícone vermelho para itens de Despacho

Se o item for de despacho e precisar destacar em vermelho:

```php
[
    'label' => 'RECLAMAÇÕES',
    'route' => 'protests.dispatch.lists',
    'icon' => 'ri-account-pin-box-fill',
    'iconClass' => 'text-danger',
]
```

Observação: no renderer inline (`dynamic-dropdown-node.blade.php`), o sistema remove automaticamente classes `text-*` vindas em `icon` e aplica `iconClass` (ou `text-primary` por padrão).

## Quando usar `sections` vs `nodes`

- Use `sections` para menus simples com 1 nível de agrupamento.
- Use `nodes` quando precisar de estrutura recursiva (grupo dentro de grupo) e maior controle.

## Checklist antes de finalizar

1. A rota existe em `routes/*.php` e abre corretamente.
2. A permissão (`can`) está correta e consistente com policies/gates.
3. O item aparece para o perfil certo e some para quem não tem acesso.
4. Se houver contador Livewire, validar render e chave (`countKey`) única.
5. Para despacho, validar cor do ícone com `iconClass => text-danger`.
