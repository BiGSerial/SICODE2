# Padrão de UI do Encerramento

> Fonte única para **telas, cores, formatos, formulários e reatividade** do módulo Encerramento. Toda tela, componente ou formulário novo (ou alterado) deve seguir este documento. Se algo não está aqui, **adicione aqui junto com o componente** — não crie o padrão só na tela.
> Plano e checklist: [CAIXAS_PENDENCIAS_E_RI_CHECKLIST.md](CAIXAS_PENDENCIAS_E_RI_CHECKLIST.md). Referências do projeto: [Filtros_Multi_Dropdown.md](../Filtros_Multi_Dropdown.md), [Notificacoes.md](../Notificacoes.md).

## 1. Princípios

1. **Intuitivo:** o usuário entende o que fazer olhando a tela. Uma ação primária por contexto; o resto é secundário.
2. **Interativo e reativo:** sem recarregar a página, sem perder filtros/seleção, com feedback imediato (carregando, sucesso, erro).
3. **Explicar o bloqueio:** nunca deixar o usuário clicar para receber erro. Ação indisponível fica desabilitada **com o motivo** (tooltip).
4. **Reaproveitar:** componente, token e script já existentes vêm primeiro. Duplicar estilo/JS numa tela é defeito.
5. **Acessível e responsivo:** funciona em teclado, tem `aria-label`, contraste adequado e não quebra no mobile (sem scroll horizontal de página).

## 2. Onde cada coisa mora (centralização)

| Camada | Local | Regra |
|---|---|---|
| Tokens, layout e componentes visuais (CSS) | `resources/sass/closure/` (`_tokens`, `_layout`, `_components`) — precedente `resources/sass/legal/` | Cor/espaço/raio **só** via token. Sem hex solto em Blade. |
| JS compartilhado | `resources/js/closure/` (`index.js`, `confirm.js`, `filter-panel.js`, `drawer.js`, `toast.js`) importado pelo `app.js` | Sem `<script>` inline novo. Registrar com guarda (`window.__closure…`) para não duplicar listeners. |
| Componentes Blade | `resources/views/components/closure/*.blade.php` (`<x-closure.nome />`) | Todo bloco repetido vira componente com `@props` documentado (§5). |
| Telas Livewire | `resources/views/livewire/closure/**` | Só compõem componentes; sem estilo próprio. |
| Estado/regra | Service em `app/Services/Closure/` | Componente Livewire orquestra; **não** concentra regra. |

**Arquivos de CSS** (`resources/sass/closure/`, índice em `_closure.scss`): `_tokens` (variáveis), `_tones` (cores semânticas `.tone-*`), `_typography`, `_layout`, `_tables`, `_badges`, `_buttons`, `_filters`, `_modals`, `_order-detail`, `_kpi`, `_inbox` (caixas/RI). Estilo novo vai no arquivo temático; a ordem dos `@import` preserva a cascata.

**Arquivos de JS** (`resources/js/closure/`, índice em `index.js`): `confirm.js` (`closure:confirm`), `toast.js` (`closure:toast`), `drawer.js` (`closure:drawer-open/close`), `filter-panel.js` (`closureFilterPanel`).

> **Transição:** `livewire/closure/_styles.blade.php` e `work/_confirm-script.blade.php` são **stubs depreciados** (CSS/JS já migrados). Ainda há JS inline a mover (lista no checklist, Fase 0): não copie esse padrão — novo JS entra em `resources/js/closure/`.

## 3. Tokens

Escopo: classe raiz `.closure-dash` (já usada nas telas). Valores atuais, a preservar:

| Token | Valor | Uso |
|---|---|---|
| `--dash-ink` | `#102033` | Texto principal, hero |
| `--dash-muted` | `#64748b` | Texto secundário, legendas |
| `--dash-line` | `#dbe4ea` | Bordas e divisórias |
| `--dash-teal` | `#155f67` | Marca / destaque primário |
| `--dash-green` | `#0f8a77` | Sucesso, ação positiva, botão primário |
| `--dash-blue` | `#263cc8` | Informação, links de contexto |
| `--dash-red` | `#e32c2c` | Erro, vencido, travado |
| `--dash-amber` | `#f7d200` | Atenção, em andamento longo |

**Cor tem significado fixo** (não use por gosto):

| Significado | Token | Exemplos |
|---|---|---|
| Positivo / concluído / ação principal | `--dash-green` | Responder, Resolvido, RI retornado |
| Informativo / neutro ativo | `--dash-blue` | Enviado, Em acompanhamento |
| Atenção / tempo longo | `--dash-amber` | Parado há muitos dias (se cor por tempo estiver ligada) |
| Problema / bloqueio | `--dash-red` | Travada por RI, cancelado, erro |
| Neutro / orientação | `--dash-muted` + fundo claro | Aba "Em acompanhamento", legendas |

Forma: cartões `border-radius: 8px`, borda `1px solid var(--dash-line)`, sombra `0 12px 30px rgba(16,32,51,.08)`; tipografia do hero e títulos como em `.dash-title` / `.dash-subtitle`. Ícones: **Remix Icon** (`ri-*`), como em `ClosureEventLabels`.

## 4. Layout padrão de tela

1. **Hero** (`.dash-hero`): título, subtítulo e a ação principal da tela.
2. **Cartões de contagem** (`<x-closure.stat-card>`): mesma classe de coluna, `h-100`, **clicáveis como filtro**.
3. **Barra de filtros** (`.table-card` + `filter-bar`): `multi-select` + busca + **chips** dos filtros ativos, com "Limpar tudo".
4. **Conteúdo:** **uma tabela única** com `<colgroup>` (nunca uma tabela por grupo). Ações como **botão**, nunca link.
5. **Painel lateral** (`<x-closure.drawer>`) para detalhe/ação rápida, sem sair da lista.
6. **Estados:** vazio explicativo (`empty-state`), carregando (`wire:loading`), erro com mensagem útil.

Duas abas de naturezas diferentes **não se misturam** e têm identidade distinta:
- **Para retorno / ação:** destaque, botão primário (verde), contagem em evidência.
- **Em acompanhamento / orientação:** tons neutros, selo fixo *"Só orientação · sai sozinha ao mudar de status"*, **nenhum botão de ação**.

## 5. Catálogo de componentes (`<x-closure.*>`)

> Ao criar/alterar um componente, atualize esta tabela. Props marcadas * são obrigatórias.

| Componente | Props principais | Para quê |
|---|---|---|
| `stat-card` | `label*`, `value*`, `tone`, `icon`, `active`, `wire:click` | Contagem clicável que filtra |
| `inbox-tabs` | `tabs*` (`key`,`label`,`count`,`tone`), `active*` | Abas com contagem e identidade por tipo |
| `filter-chips` | `filters*` (`key`,`label`,`value`), `clear` | Filtros ativos removíveis |
| `multi-select` *(existe)* | `label*`, `field*`, `options*` | Múltipla seleção com busca e contagem |
| `age-chip` | `since*` (data), `prefix`, `estimated` | "há N dias", com selo "desde o 1º registro" se estimado |
| `status-badge` | `status*`, `map` | Selo de status com cor semântica |
| `lock-badge` | `reason*`, `since` | "Travada · RI com o Desenho há N dias" + tooltip |
| `timeline` | `items*` (`label`,`at`,`by`,`duration`,`tone`) | Etapas com data e duração |
| `drawer` | `id*`, `title*`, slot | Painel lateral (offcanvas) |
| `empty-state` | `icon`, `title*`, `text` | Estado vazio explicativo |
| `form-field` | `label*`, `name*`, `error`, `hint`, `required` | Campo de formulário com rótulo/erro/ajuda |
| `action-button` | `label*`, `icon`, `tone`, `disabled`, `reason` | Botão com motivo no tooltip quando desabilitado |

| `signal` | `signal*` (de `ClosureSignal`) | Chip colorido da Ordem: azul atribuída · verde concluída · vermelho +24h sem baixa no SAP · amarelo na pilha sem atribuição |
| `signal-legend` | — | Legenda das cores (mesmos textos em toda tela) |
| `bulk-search` | `terms`, `open` | Botão + modal "Buscar em massa" (colar lista de Notas/Ordens). Exige o trait `WithBulkSearch` |
| `bulk-search-result` | `report` | Resumo "X de N encontradas" + não encontradas (copiar) |

**Utilitários de CSS** (`_utilities.scss`, no lugar de `style=""`): `pre-line`, `cell-wrap`, `table-min-lg`, `col-pct-5…40` (largura de coluna para `<colgroup>`). Cor de KPI: `kpi tone-teal|green|blue|amber|red|muted`.

## 6. Formulários

- Rótulo sempre visível (nunca só placeholder); obrigatório marcado; ajuda curta em `hint`.
- `wire:model.defer` por padrão (envia no submit). `wire:model.lazy`/`debounce` só para busca/filtro, com `wire:model.debounce.400ms`.
- Validação com `$rules` + mensagens em português próximas ao campo (`form-field` exibe `error`). Foco no primeiro erro.
- Submit: botão com `wire:loading.attr="disabled"` e `wire:loading.class`/spinner; nunca permitir duplo clique.
- Motivo/comentário: `textarea` com contador quando houver limite; obrigatório onde o service exigir (espelhar a regra do service na UI, mas **a regra vive no service**).
- Ação destrutiva ou em lote: confirmação via evento `closure:confirm` (`dispatchBrowserEvent('closure:confirm', ['title'=>…, 'html'=>…, 'confirmText'=>…, 'action'=>'evento-livewire'])`), com **resumo do impacto** ("vale para as N Ordens desta Nota", listando-as).
- Seleção múltipla: `multi-select` do módulo; seleção pendente no cliente (Alpine) e **uma chamada ao servidor** ao fechar.

## 7. Reatividade e JS

- **Livewire 2 + Alpine.** Estado de servidor no Livewire; estado só visual (aberto/fechado, busca local, seleção pendente) no Alpine.
- **Eventos com prefixo `closure:`** (ex.: `closure:confirm`, `closure:toast`, `closure:drawer-open`). Um listener por evento, registrado uma vez com guarda `window.__closure…`.
- **Feedback imediato:** `wire:loading` em botões e tabelas; toast de sucesso/erro via `toast.js`/`dispatchBrowserEvent`; mensagens persistentes com `session()->flash`.
- **Atualização automática:** `wire:poll` leve só onde o dado muda sozinho (caixas); intervalo ≥ 30s; nunca disparar notificação no poll.
- **Não perder contexto:** filtros e página no `$queryString`; reset de página (`resetPage`) ao mudar filtro; seleção preservada em `wire:key` estáveis.
- **Linhas:** `wire:key` estável por registro; evitar re-render de tabela inteira para ação de uma linha (componente de linha ou `wire:ignore` apropriado).
- **Gráficos (Chart.js):** atualizar datasets **in-place**; nunca `chart.data.datasets = …` (regra do `CLAUDE.md`).
- **Tempo:** datas `d/m/Y` e relativo "há N dias" (dias corridos); título/tooltip com data e hora completas.

## 8. Acessibilidade e responsivo

- Todo botão de ícone tem `aria-label`; tooltips acessíveis por foco, não só por hover.
- Navegação por teclado no `drawer`, abas e `multi-select` (Esc fecha; foco volta ao gatilho).
- Contraste mínimo AA; cor nunca é a única informação (sempre ícone ou texto junto).
- Mobile: tabela dentro de `.table-responsive`; cartões empilham; drawer em largura total; ações principais continuam alcançáveis.

## 9. Checklist de PR para qualquer tela do Encerramento

- [ ] Usa componentes `<x-closure.*>` existentes; o que repete 2+ vezes virou componente novo **e foi catalogado em §5**.
- [ ] Sem hex/`style=""`/`<style>`/`<script>` novo em Blade: tokens no SCSS, JS em `resources/js/closure/`.
- [ ] Cores seguem o significado fixo (§3).
- [ ] Layout segue §4 (hero → cartões → filtros/chips → tabela única com `colgroup` → drawer); ações são botões.
- [ ] Ação indisponível está desabilitada **com motivo**; ação em lote/destrutiva pede confirmação com impacto.
- [ ] Estados vazio, carregando e erro existem.
- [ ] Filtros em `$queryString`, `resetPage` ao filtrar, `wire:key` estáveis.
- [ ] Testada no navegador (desktop e mobile) na stack do worktree; sem scroll horizontal da página.
- [ ] Nenhuma chamada `Storage::`/`storage_path()` na tela; notificações via `ClosureNotifier`.
- [ ] **Isolamento:** a tela só mostra o que o público da rota pode ver (parceira nunca vê controle interno, nem o SUPERADM) e toda ação reconfere o acesso no servidor.
- [ ] **Exporta:** lista tem "Exportar Excel" (Job + aviso, no escopo do usuário); detalhe baixa na hora.
- [ ] `npm run build` e `./vendor/bin/pint --dirty` limpos.

## 10. Regras transversais

### Isolamento de visão
- **Cada rota tem UM público.** Tela da parceira (`/partner/...`) mostra só o que a empresa dela vê, **mesmo para o SUPERADM**, que apenas *observa* (aviso "Modo observador", somente leitura, uma empresa por vez via seletor). Tela interna nunca mostra a visão da parceira.
- Esconder o botão não basta: **toda ação Livewire reconfere o acesso no servidor** (`guardInternal()` e afins). Nunca confiar em propriedade pública do componente.
- Consulta escopada por empresa em todo lugar que a parceira lê (listas, histórico, exportação, contadores do menu).

### Toda lista exporta
- Toda tela com lista ou detalhe tem **"Exportar Excel"**. Lista: Job em `app/Jobs/Closure/*Export*` com os filtros que estavam na tela, **no mesmo escopo de acesso do usuário**, aviso por `SystemNotification` com o link. Detalhe de um registro: download imediato.
- Cabeçalhos e rótulos vêm de **uma fonte** (`ClosurePartnerExporter`, `ClosureListExporter`): a tela e o arquivo nunca divergem. O arquivo leva a aba "Controle Exportacao" (quem pediu, quando, filtros).
- O botão dispara `closure:toast` ("Exportação iniciada…"); `wire:loading` desabilita enquanto enfileira.

### Sinalizador de pendência no menu
- Bolinha vermelha (`.closure-menu-dot`) no item pai quando há algo a verificar; selo com a quantidade no link (`bg-danger` se > 0). Componente de contagem com `wire:poll.60s`, no padrão `Partner\Count\*`.

### Filtros e menus
- **Todo card com filtros leva `.filter-bar`** (`overflow: visible`): o `.table-card` padrão corta o que passa da borda, e os menus de seleção múltipla ficavam cortados. Nunca esconder overflow num contêiner que tem dropdown.
- **Toda lista de Ordens tem "Buscar em massa"** (`WithBulkSearch` + `ClosureBulkSearch`): correspondência exata com Nota ou Ordem, resumo do que não casou, chip removível, e vai na exportação.

### Padrão visual do Excel (painéis e listas analíticas)
- **Uma aba por bloco da tela**, mais as abas de detalhe; a aba **Resumo** vai primeiro, com os KPIs em cartões, os filtros aplicados e o **índice de abas com link**.
- Visual único (`App\Exports\Closure\Dashboard\DashboardTableSheet` + `DashboardTheme`, mesma paleta da tela): faixa de título e descrição, cabeçalho escuro congelado com filtro, zebra, **barras de dados** nas colunas de volume, **zero como "–"** (nunca vazio: usar `WithStrictNullComparison`), cor da aba por tipo de conteúdo, gráficos nativos quando ajudam a leitura e **linha colorida pelo sinal** (mesmas cores e regra de `ClosureSignal`).
- Dados do arquivo vêm de um serviço próprio (`ClosureDashboardReport`), nunca da view; o arquivo **não corta** o que a tela pagina/limita e respeita os filtros da tela.

### Sinalização por cor das Ordens
- **Uma regra só** (`ClosureSignal`), usada em toda lista de Ordens do Encerramento: nunca recalcular a cor na tela. A **linha inteira** é colorida (`<tr class="signal-row signal-{chave}">`, o mesmo padrão das outras atividades: `table-primary/success/danger/warning`), com a explicação no `title` da linha e `data-signal` (cor nunca é a única informação: legenda + tooltip). **Não repetir o sinal como selo na coluna de status** — ela mostra só o status da atividade. O componente `<x-closure.signal>` fica para telas sem linha colorida; a lista leva `<x-closure.signal-legend>`. A linha colorida precisa continuar **separável das vizinhas**: borda lateral na cor do sinal, divisória inferior mais escura e zebra sutil dentro da mesma cor.
- Ação que o estado da Ordem proíbe (ex.: despachar Ordem que já tem posse) fica **desabilitada com o motivo** e é recusada também no servidor.

### Telas de caixa e de configuração (Fase 4/5)
- A caixa vive **dentro da atividade** (menu do serviço), pelo partial `closure/partials/inbox-menu-item` e a view `closure/inbox`; o gate é `closure.inbox.{área}`. Aba de **visão** usa `inbox-note` fixo ("Só visão") e nunca traz botão de ação; aba de **ação** é a única com Responder/Devolver.
- Cartões-filtro (`<x-closure.stat-card>`) por status, tempo com `<x-closure.age-chip>`, vazio com `<x-closure.empty-state>` (explica o que fazer e quem pode configurar).
- Configuração em lista de marcação (`.map-list`/`.map-item` em `_inbox.scss`): item indisponível aparece desabilitado **com o motivo** (nome do dono), nunca escondido.

### Filtros com Alpine e menu de ações (2026-10-25)
- **Dropdown gerado por Alpine (`x-for`) dentro de componente Livewire leva `wire:ignore`.** Sem isso, ao remover um chip ou limpar filtros o Livewire reescreve as opções, elas perdem o handler e o filtro "morre" até recarregar a página (bug real do multi-select do Despacho; corrigido em `<x-closure.multi-select>`). O estado vem do `x-data` (`data-options`), nunca do HTML do bloco ignorado.
- **Menu de ações por linha** (`_control-actions`): agrupado por intenção com cabeçalhos (*Tratativa*, *Alterar status para*, *Passar adiante*, *Abrir*; no Controle, *Gerir*), ícone por ação, ação destrutiva em vermelho, posição fixa com `flip` e rolagem interna (`.closure-actions-menu`, máx. 72vh) — nunca passa da tela.
- **Toda caixa/lista com filtros** mostra todos: tipo, busca (+ em massa), status usuário, responsável, empresa e tempo no status quando fizer sentido, com *Limpar filtros* e *Exportar Excel*.

## 11. Anti-padrões

- Estilo ou script copiado de outra tela em vez de componente/token.
- Uma tabela por grupo; ação como link; botão que só avisa erro depois do clique.
- Aba de orientação com botão de ação (ou aba de ação sem destaque).
- Cor escolhida por gosto, sem significado.
- `wire:poll` curto, notificação disparada em render, estado de negócio guardado só no Alpine.
