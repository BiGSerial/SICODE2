# Padrão obrigatório de menu lateral

**Regra:** todo menu lateral do SICODE2 (qualquer área: Engenharia, Responsável, atividades de Despacho e de Serviço, Parceiro, Jurídico, Encerramento…) é feito com este padrão. Não se escreve HTML de sidebar à mão, não se copia o menu de outra tela e não se cria CSS/JS de menu por área. A **Engenharia é a implementação de referência** (`App\Support\Menu\EngineerNav`, `Engineers\Menu`, [engineers/MENU_LATERAL.md](engineers/MENU_LATERAL.md)).

## 1. Peças (uma responsabilidade por arquivo)

| Camada | Arquivo | Regra |
|---|---|---|
| Definição | `App\Support\Menu\<Area>Nav extends SidebarNav` | **Única fonte** da navegação da área: módulos, grupos, itens, ícones, contadores, apelidos de rota. O menu lateral **e** o dropdown do topo leem daqui. |
| Base | `App\Support\Menu\SidebarNav` | `locate()` (módulo/grupo/item ativos pela rota), `entryRoute()`, `item()`, `matches()`. Não se reimplementa. |
| Componente | `<x-nav.sidebar :nav="<Area>Nav::class" :route="$route" label="…" />` | Único markup do menu. Itens: `<x-nav.item>`. |
| Livewire | `<Area>\Menu` | Fino: só entrega `request()->route()?->getName()` ao componente. Mantém `@livewire('<area>.menu')` nas views. |
| Estilo | `resources/sass/nav/_sidebar.scss` (`.snav-*`) | Tema, cores, responsivo. Ajuste visual vale para todas as áreas. |
| Comportamento | `resources/js/nav/sidebar.js` | Grupos lembrados, filtro, gaveta no celular. |

## 1.1 Quem já usa o padrão

| Área | Definição | Observação |
|---|---|---|
| Engenharia | `EngineerNav` | Módulos Projetos, Informes e Cancelamentos |
| Responsável | `ResponsibleNav` | Módulos Projetos e Informes (mesma organização da Engenharia) |
| Atividades de **despacho** | `DispatchNav` | Contexto: `service` + `variant`. Arquivos `dispatchs/**/menu.blade.php` |
| Atividades de **serviço** | `ServiceNav` | Contexto: `service` + `variant`. Arquivos `services/**/menu.blade.php` |
| **Administração** (admin + configurações + sistema) | `AdminNav` | Módulos Cadastros, Dados, Configurações e Sistema; o dropdown do topo sai de `AdminNav::topNodes()`. Arquivos `admin/**/menu`, `config/*menu` |
| **Reclamações** (protests) | `ProtestNav` | Contexto: `variant` (`dispatch`, `services`, `partner`). Arquivos `protest/*/menu.blade.php` |
| **Relatórios** | `ReportsNav` | Contexto: `variant` (`informes`, `cancelamentos`, `reclamacoes`, `notas-d5`, `analise-projetos`, `retorno-interno`). Arquivos `reports/*menu.blade.php`; a lista do dropdown do topo é `ReportsNav::topLinks()` (com o gate de cada relatório) |
| **Jurídico** | `LegalNav` | Um módulo, três grupos com gate (Controlador, Executante, Gestão); `LegalNav::topSections()` alimenta o dropdown do topo |
| **Qualidade** | `QualityNav` | Grupos pelo papel (contexto `mode`: `manager`, ou `member` com `n1`/`n2`). Abas e filas compartilham a rota: o item ativo vem da chave `rota:parâmetro` (`QualityNav::routeKey()`). Pílulas por `quality.menu-count`; o perfil de quem olha vai no slot do rodapé |
| **BT Zero** | `BtzeroNav` | `BtzeroNav::topItems()` alimenta o dropdown "SMC" do topo |
| **Contratação** | `HiringNav` | Contexto: `service` (a atividade aberta) |
| **Análise de projetos** | `ProjectReviewNav` | Contadores no componente `project-review.menu-counts` |
| **ADS** | `AdsNav` | `AdsNav::topItems()` alimenta o item no dropdown "BUSCAR" do topo |
| **Cancelamentos** (quem solicita) | `CancellationNav` | A aprovação é da Engenharia (`EngineerNav`) |
| **Portal da parceira** | `PartnerNav` | **Tema próprio** (`THEME = 'partner'`, ver §7). Permissões da parceira (`PartnerAccessGate`), não gates: o `can` guarda a chave (ex.: `viability.list`). Condição de perfil (engenheiro que só atua pela parceira) decide-se em `modules()`. Dropdown "BUSCAR" do topo: `PartnerNav::topItems()` |

Todas as áreas estão no padrão. A `home` e o `profile` têm só a coluna vazia do menu.

Menus de uma área só (um módulo, poucos grupos) montam módulo e grupos com o trait `Concerns\NavBuilders`; os de atividade usam `Concerns\ActivityNavHelpers`.

## 2. Como cada nível deve ser

- **Módulo** (`modules()`): uma fase ou assunto do trabalho da área (ex.: Projetos, Informes, Cancelamentos). Aparece no seletor do topo do lateral (2 a 4 módulos; com 1 só módulo o seletor some). Chaves: `label`, `icon`, `hint` (frase curta), `groups`.
- **Grupo**: um assunto dentro do módulo. É um dropdown (`<details>`) com **cabeçalho em tom azul próprio** (`.snav-group-head`: texto, ícone e seta azuis sobre um fundo azul suave), propositalmente diferente do verde dos itens e do módulo, para nunca ser confundido com um item. Grupo com um único item e sem necessidade de título usa `'flat' => true`.
- **Item**: um destino (tela). Chaves: `key` (única), `label`, `route`, `icon`, `match`, `counts`.
- **Contexto**: menu que depende de algo da tela (ex.: a atividade aberta) recebe `$context` (`modules(array $context)`); os parâmetros de rota do item vão em `params` (ex.: `['service' => $uuid]`). Menu de atividade usa `service` + `variant` (a pasta da atividade): `variant` preserva o desenho de cada atividade; o arquivo `menu.blade.php` da atividade é só uma chamada a `<x-nav.sidebar>`.
- **Permissão**: `can` (nome do gate) no item ou no grupo; sem permissão o item some, e grupo/módulo vazio some junto (com tudo oculto o menu mostra só a coluna vazia). Condição de perfil que não é gate (ex.: `contract`) decide-se ao montar `modules()`, nunca no Blade.
- **Item que abre em outra aba**: `'target' => '_blank'` nos extras do item (ganha o ícone de link externo).
- **Dropdown do topo gerado do `*Nav`**: o menu do topo de uma área com muitos itens pode sair do mesmo `*Nav` (ex.: `AdminNav::topNodes()`), em vez de uma lista escrita à mão.
- **Painel extra do rodapé**: vai no slot de `<x-nav.sidebar>` e é um bloco recolhível (`<details data-snav-persist="…">`, lembrado como os grupos). A **ocupação da atividade** é o componente padrão `<x-nav.occupation :rows :companies :online>` (usado por `Production\Users\Occupation` e `OccupationConstruction`): ranking por pessoa com barra relativa ao líder, N/OV, presença online (watchdog) e filtro por empresa; clicar na pessoa emite `filterUser`. Atualização por `wire:poll.15s`, nunca em intervalo menor. O menu e o painel rolam juntos na coluna: o painel extra nunca espreme os itens do menu.
- **Contador**: componente Livewire já existente, declarado em `counts` (`[nome, parâmetros]`); pílula vermelha quando há pendência. Grupo fechado com pendência mostra um ponto vermelho. Contador novo segue `wire:poll` com intervalo ≥ 10 s e `key()` único (o item cuida da chave).

## 3. Regras obrigatórias

1. **Fonte única.** O dropdown do topo da área usa a mesma `*Nav` (`topItems()` ou `modules()`); nunca uma lista paralela.
2. **Ativo pela rota.** O item ativo vem de `match` (nomes de rota e prefixos terminados em `.`). Proibido `onlySection`, listas de rotas dentro de componente ou `request()->is(...)` no menu.
3. **Rotas existentes e nomeadas.** Todo item aponta para uma rota com nome. Rota renomeada deixa o nome antigo como apelido em `match`.
4. **Textos em PT-BR**, só a primeira letra maiúscula nos itens ("Obras à validar"), sem erros ortográficos e sem caixa alta fixa (o CSS aplica caixa alta onde cabe).
5. **Ícones Remix (`ri-*`)**, um por item, com significado coerente. Não usar `bi-*` nem o mesmo ícone em todos os itens. O ícone do grupo e o do módulo representam o assunto, não uma ação.
6. **Agrupar por ciclo de trabalho**, não por quem pediu a tela. Itens que o usuário usa em sequência ficam no mesmo grupo; histórico fica no grupo do que ele registra; o que não pertence ao assunto não entra.
7. **Todos os itens do módulo ficam visíveis** (nada de mostrar "uma seção por vez"): tudo o que existe na área é alcançável pelo lateral.
8. **Sem CSS ou JS inline** e sem estilo de menu por tela. Mudança visual é no `_sidebar.scss`.
9. **Acessibilidade e responsivo** já vêm do componente (`aria-current`, foco visível, gaveta abaixo de 1200 px, Esc fecha, `prefers-reduced-motion`). Não removê-los.
10. **Permissões**: o middleware da rota é quem bloqueia. Item que só alguns perfis veem usa `can` (gate) no próprio `*Nav`, nunca `@can` espalhado no Blade.

## 4. Como criar o menu de uma área nova (ou migrar um antigo)

1. Crie `App\Support\Menu\<Area>Nav extends SidebarNav` com `modules()`; use `self::item(key, label, route, icon, match, counts)`.
2. Crie (ou reduza) o Livewire `<Area>\Menu` ao modelo fino da Engenharia e a view `<x-nav.sidebar :nav="<Area>Nav::class" :route="$route" label="Menu de <Área>" />`.
3. Aponte o dropdown do topo da área para a mesma definição (`topItems()` ou monte a partir de `modules()`).
4. Remova o HTML antigo do menu, os arrays de rotas e os contadores duplicados.
5. Escreva os testes (o teste genérico também confere que cada ícone `ri-*` existe no Remix do projeto) (modelo em `tests/Feature/PepBudgetWatchTest.php`): `locate()` por rota (com apelidos e prefixos), toda rota do menu existe e abre com o item ativo, e a estrutura válida. O teste genérico já varre todo `*Nav` (use `sampleContexts()` para devolver os contextos de exemplo do seu menu).
6. Confira no navegador: desktop, largura estreita (gaveta), grupos abrindo e lembrando, filtro de itens.

## 5. Checklist de PR (menu)

- [ ] Existe `*Nav extends SidebarNav` e o menu usa `<x-nav.sidebar>`.
- [ ] Topo e lateral leem a mesma definição.
- [ ] Item ativo por `match`; nenhuma lista de rotas fora do `*Nav`.
- [ ] Rótulos PT-BR, ícones `ri-*` coerentes, grupos por ciclo de trabalho.
- [ ] Nenhum HTML de sidebar escrito à mão, nenhum CSS/JS inline.
- [ ] Testes de localização, rotas e item ativo.
- [ ] Conferido no navegador (desktop e estreito).

## 6. Anti-padrões

- Copiar o `menu.blade.php` de outra área e editar.
- Mostrar só a seção da tela atual e esconder as demais.
- Itens duplicados em topo e lateral com nomes diferentes.
- Contador de outro item reaproveitado por engano (ex.: o de parciais no menu de D5).
- Mesmo ícone para todos os grupos, ícones `bi-*` misturados com `ri-*`.
- Dropdown com a mesma aparência de um item (o cabeçalho de grupo tem cor própria justamente para isso).

## 7. Temas (a parceira é diferente da empresa)

O menu da **empresa** é escuro, em verde-petróleo, com acento verde: é a identidade do sistema interno. O **portal da parceira** tem tema próprio, para quem entra nele saber que está em outro ambiente, e mais sóbrio: não deve chamar mais atenção que o trabalho.

- **Declarar o tema:** `public const THEME = 'partner'` no `*Nav`. O componente `<x-nav.sidebar>` troca o fundo verde padrão pela classe `snav--partner` (`snav--<tema>`). Sem `THEME`, vale o da empresa. Tema novo só com justificativa de produto, como este.
- **Onde vive:** `resources/sass/partner/_partner.scss` (menu `snav--partner` **e** o layout da parceira, `body.partner-shell`, em `layouts/company`). O tema sobrepõe os tokens `--snav-*` e os poucos seletores com cor fixa; nunca copia o menu nem cria markup próprio.
- **Princípios do tema da parceira:** composição **tom sobre tom em azul cobalto EDP**: o topo é o cobalto profundo e a coluna é o mesmo cobalto em véu claro, então os dois se leem como uma peça só. O brasão do portal é o único bloco cheio de cor da coluna; o cabeçalho de grupo usa o véu mais denso com texto cobalto profundo (continua diferente dos itens); o item ativo é um **cartão branco que salta do véu**, com a barra cobalto à esquerda. Texto em marine blue; vermelho só para pendência, sem sombra forte; **sem verde neon** (a marca do topo é branca + azul-gelo). Migalhas em cartão branco com filete cobalto. O topo em "visão de outro usuário" continua vermelho.
- **Cor só por token:** variáveis `--ptn-*` declaradas num bloco de tokens a partir das variáveis EDP de `_variables.scss`; fora dele, nada de `#hex` (o teste confere). Precisa de uma cor nova? Crie o token, com nome pelo papel.
- **Sem CSS inline no layout:** os popovers e o avatar que estavam em `<style>` no `layouts/company` agora são do tema.
- **Permissão própria:** uma área com outro modelo de permissão sobrescreve `SidebarNav::allows()` (a parceira usa `PartnerAccessGate`); `can` continua sendo a chave do grupo/item.
