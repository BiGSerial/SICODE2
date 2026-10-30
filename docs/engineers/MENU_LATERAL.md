# Menu da Engenharia (lateral e topo)

> Implementação de referência do **padrão obrigatório** em [../PADRAO_MENU_LATERAL.md](../PADRAO_MENU_LATERAL.md). Regras, criação de menus novos e checklist estão lá; este arquivo descreve só o que é da Engenharia.

**Fonte única:** `App\Support\Menu\EngineerNav`. O menu lateral (`Engineers\Menu` → `<x-nav.sidebar>`) e o dropdown do topo (`components/menu/engineer-dropdown`) leem dela. Para criar, mover ou renomear um item, edite só esse arquivo.

## Organização (ciclo da obra)

| Módulo | Grupos | Itens |
|---|---|---|
| **Projetos** (antes da obra) | Viabilidade | Painel · Em viabilidade · Em tratativa · Retorno interno · Avaliação de justificativas · Histórico · Resumo |
| | Validação de projetos | Painel · Obras à validar · Em validação · Validados |
| **Informes** (obra informada) | Visão geral | Painel de informes |
| | Informes parciais | Aguardando aprovação · Histórico |
| | Informes finais | Lista de informes · Lançar informe |
| | ADS | Solicitações · Situação · Calendário |
| | Notas D5 | Aguardando resolução (o D5 nasce de um informe final) |
| | Orçamento (PEP) | Informes sem saldo |
| **Cancelamentos** | — | Aprovações · Histórico de aprovações · Notas em cancelamento |

Topo (4 itens): Viabilidade · Validação de projetos · Informes (contador = parciais aguardando + informes sem saldo no PEP) · Cancelamentos.

## Como o menu lateral funciona

- **Módulo e item ativos** vêm do nome da rota (`EngineerNav::locate`). Um item pode declarar `match` com apelidos (`engineers.validation`, `engineers.parciais`, `engineers.informes`, `engineers.d5`) ou prefixos terminados em ponto (`engineers.cancellations.`). Rota fora do menu cai no módulo Projetos, sem item ativo.
- **Seletor de módulos** no topo do lateral leva à primeira tela de cada módulo; o lateral mostra o módulo atual por inteiro (antes só uma seção aparecia e as demais ficavam inacessíveis).
- **Grupos** são `<details>` com cabeçalho em tom azul próprio (diferente dos itens): o grupo da tela atual abre; os demais lembram a última escolha do usuário (localStorage). Grupo fechado com pendência mostra um ponto vermelho.
- **Contadores** reaproveitam os componentes Livewire de contagem (`counts` de cada item); o visual de pílula é do SCSS.
- **Filtro** de itens no próprio menu (sem acento, sem diferenciar maiúsculas).
- **Celular/tablet (< 1200 px):** o botão do topo abre a gaveta sobre o conteúdo com fundo escurecido; clicar em um item, fora da gaveta ou apertar Esc fecha.
- Acessibilidade: `aria-current`, foco visível, navegação por teclado, `prefers-reduced-motion`.

## Arquivos

| Camada | Arquivo |
|---|---|
| Definição | `app/Support/Menu/EngineerNav.php` (estende `SidebarNav`) |
| Componente | `app/Http/Livewire/Engineers/Menu.php` + `resources/views/livewire/engineers/menu.blade.php` (usa `<x-nav.sidebar>`) |
| Topo | `resources/views/components/menu/engineer-dropdown.blade.php` |
| Estilo (compartilhado) | `resources/sass/nav/_sidebar.scss` |
| Comportamento (compartilhado) | `resources/js/nav/sidebar.js` |
| Contador do topo | `app/Http/Livewire/Engineers/Counts/InformesCount.php` |

## Pendências conhecidas

- As URLs ainda são as antigas (`/engineers/informes_parciais`, `/engineers/worked_list`…); só os nomes de menu mudaram. A reorganização das rotas para `/engineers/informes/*` (com redirecionamento das antigas) é a próxima etapa.
- "Histórico de informes finais" ainda não existe como tela própria (a "Lista de informes" é a lista atual de informes entregues).
- `engineers.viability_waiting` (obras aguardando despacho) continua fora do menu do engenheiro, como antes.
