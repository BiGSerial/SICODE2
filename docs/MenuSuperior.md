# Menu superior (topbar)

Como o menu superior funciona e como mexer nele. O lateral tem o seu padrão em [PADRAO_MENU_LATERAL.md](PADRAO_MENU_LATERAL.md); os dois leem **a mesma definição** quando o assunto é o mesmo (`App\Support\Menu\*Nav`).

## Estrutura (em ordem de fluxo)

`ATIVIDADES · OCORRÊNCIAS · ENGENHARIA · RESPONSÁVEL · JURÍDICO · CONSULTAS · RELATÓRIOS · ADMINISTRAÇÃO`

| Menu | O que tem | Regra de organização |
|---|---|---|
| ATIVIDADES | Fases PROJETO · CONSTRUÇÃO · PÓS-OBRA; Qualidade e Análise de projeto no topo de Projeto | **Uma linha por atividade**, com o contador da fila e as ações rápidas (Despacho, Controle, Dashboard). Ordem do fluxo em `TopbarNav::FLOW_ORDER` |
| OCORRÊNCIAS | Reclamações (Despacho / Minha fila), Ocorrências, Cancelamento de notas | O que foge do fluxo normal |
| ENGENHARIA / RESPONSÁVEL | Viabilidade, Validação de projetos, Informes (+ Cancelamentos na Engenharia, Informe SMC para superadm) | Filas de aprovação por papel; mesma estrutura nos dois |
| JURÍDICO | Controlador · Executante · Gestão | `LegalNav` |
| CONSULTAS | Notas (Notas/OVs, Consulta D5, Busca avançada) · Informes (Informes, Rejeitados, ADS solicitadas, Equipamentos) · Contratação | Só leitura e localização |
| RELATÓRIOS | Gestão · Projeto · Execução · Atendimento | Gerencial (`management`) |
| ADMINISTRAÇÃO | Cadastros · Dados · Configurações · Sistema | `AdminNav` |

Cada assunto mora **em um lugar**. Antes havia um menu "SERVIÇOS" (que não era a fila das atividades) com Cancelamento, Ocorrências e Relatórios; agora Cancelamento e Ocorrências estão em OCORRÊNCIAS e os relatórios têm menu próprio.

## Arquivos

| Peça | Arquivo |
|---|---|
| Definição única dos menus | `app/Support/Menu/TopbarNav.php` (`menus($user)`) |
| Montagem | `resources/views/layouts/menu_itens.blade.php` (só um `@foreach`) |
| Dropdown | `resources/views/components/menu/dynamic-dropdown.blade.php` + `partials/dynamic-dropdown-node.blade.php` |
| Estilo (tokens `--tb-*`) | `resources/sass/nav/_topbar.scss` |
| Comportamento | `resources/js/nav/topbar.js` |
| Contadores | `app/Services/Menu/TopbarCounts.php` + Livewire `components.menu.topbar-counts` |
| Testes | `tests/Feature/TopbarMenuTest.php` |

## Como o dropdown funciona

- **Sem submenu lateral.** Grupos abrem **no próprio lugar** (acordeão): não são cortados pela borda da tela, funcionam no toque e no teclado. `open => true` no grupo abre por padrão.
- **Busca.** Menu com 9 ou mais itens ganha o campo "Filtrar…" no cabeçalho: filtra por nome e pelas ações da linha, abre os grupos com resultado e `Enter` abre o primeiro. O foco vai para a busca ao abrir.
- **Teclado.** `↑ ↓` percorrem links, ações e grupos; `← →` fecham/abrem o grupo; `Home/End`; `Esc` fecha.
- **Contadores em uma chamada.** Itens com `count => 'chave'` mostram um selo preenchido por **um** componente Livewire (`TopbarCounts`), depois que a página carrega e a cada 2 minutos. O gatilho do menu e o cabeçalho de cada grupo mostram a soma. O último valor fica em `sessionStorage` para o número não piscar ao navegar. Chaves: `service:{uuid}`, `protests`, `engineer.informes`, `engineer.cancellations`. Antes cada item era um componente com `wire:poll` próprio.
- **CSS e JS uma vez só** (antes cada dropdown repetia ~500 linhas de `<style>` e um `<script>`).

## Estrutura de dados

```php
// Item simples
['label' => 'OCORRÊNCIAS', 'route' => 'occurrences.index', 'icon' => 'ri-alarm-warning-line', 'can' => 'occ.access']

// Item com contador e ações rápidas (atividade)
[
    'label' => 'LEVANTAMENTO', 'route' => 'services.main', 'routeParams' => ['service' => $uuid], 'icon' => 'ri-…',
    'count' => 'service:' . $uuid,
    'actions' => [
        ['label' => 'Despacho', 'route' => 'dispatch.main', 'routeParams' => [...], 'icon' => 'ri-send-plane-line', 'tone' => 'danger'],
        ['label' => 'Controle', 'route' => 'dispatch.stack', 'routeParams' => [...], 'icon' => 'ri-radar-line', 'iconOnly' => true],
    ],
]

// Grupo (acordeão) e título de seção
['kind' => 'group', 'label' => 'PROJETO', 'open' => true, 'nodes' => [...]]
['kind' => 'header', 'label' => 'Notas']
```

Campos: `label`, `route` + `routeParams` (ou `href`), `icon`, `iconClass => 'is-danger'` (ícone vermelho de despacho), `can` (Gate/Policy), `visible` (`false` oculta), `count`, `actions`. O dropdown também aceita `sections` (um nível) além de `nodes`. Cabeçalho sem item visível depois dele e grupo sem filhos visíveis somem sozinhos; menu sem nada visível não aparece.

## Como adicionar

1. Item em menu existente: edite o método do menu em `TopbarNav` (ou o `*Nav` do módulo, se ele já for compartilhado com o lateral).
2. Menu novo: crie o método em `TopbarNav`, adicione em `menus()` na posição do fluxo e dê um `key` único.
3. Contador novo: some a chave em `TopbarCounts::for()` (uma consulta agrupada, nunca um componente por item) e use `count => 'chave'`.
4. Atividade nova: ela aparece sozinha na fase certa; para posicioná-la no fluxo, acrescente a pasta em `TopbarNav::FLOW_ORDER`.

## Checklist

1. A rota existe e abre. 2. `can` consistente com os gates. 3. Aparece para o perfil certo e some para os outros (teste renderizando como o perfil). 4. Sem `<style>`/`<script>` no menu. 5. Contador novo entra no `TopbarCounts`. 6. Conferido no navegador (largura estreita inclusive).
