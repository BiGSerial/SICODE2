# Padrão de listas operacionais (telas de trabalho)

**Referência: Despacho → Lista para Pagamento** (`App\Http\Livewire\Dispatchs\Payment\Main`, view `livewire/dispatchs/payment/main.blade.php`). Uma lista operacional é onde alguém **trabalha em cima de linhas** (atribuir, despachar, conferir), diferente de uma dashboard, que responde perguntas com gráficos ([PADRAO_DASHBOARDS.md](PADRAO_DASHBOARDS.md)). Visual, tokens e wrapper são os mesmos da Home; o que muda é a anatomia, feita para velocidade de leitura e de ação.

Toda lista operacional nova, e toda lista antiga que for mexida, segue este padrão. Selos, datas, valores, cor de linha, filtros e paginação **são os mesmos componentes em todas as telas**: tamanho, raio, peso e cor não se redefinem por tela.

Já no padrão: Despacho → Lista para Pagamento (`Dispatchs\Payment\Main`), a da parceira (`Dispatchs\Payment\PartnerMain`, valor = MOA aberto) e Serviços → Lista de Pagamento (`Services\Payment\Main`, ação "Assumir"). As três dividem filtros e "limpar tudo" no trait `Concerns\WithNoteListFilters` e o `PaymentListRowPresenter` (perfis `dispatch`, `partner`, `service`); Despacho e parceira dividem também o memo de bloqueio (`Payment\Concerns\HasPaymentListFilters`). O clique para copiar a nota é global (`resources/js/ops/copy.js`, `.copy-text` dentro de `.ops-list`).

## 1. Camadas

| Camada | Regra | Na Lista para Pagamento |
|---|---|---|
| Componente Livewire | Estado da tela (busca, tipo, atalhos, seleção), consulta paginada e ações. Sem HTML, sem regra de cor. | `Dispatchs\Payment\Main` |
| Filtros | Trait `WithListFilters`: o componente só declara os filtros e de onde vêm as opções. | `listFilterDefinitions()` / `listFilterOptions()` |
| Apresentador da linha | Transforma o model na linha pronta, já com tom semântico. A view só desenha. | `Services\Payment\PaymentListRowPresenter` |
| Regras de cor | Prazo e situação da linha vêm de classes únicas, nunca de `if` na view. | `Support\Ops\Deadline`, `Support\Ops\RowState` |
| Componentes Blade | `<x-ops.*>`: hero, busca, segmentado, atalho, filtros, chips, selo, data, valor, legenda, paginação, barra de seleção. | `resources/views/components/ops/` |
| JS | Alpine registrado em `resources/js/ops/` (`opsFilterPanel`, `opsSelection`). Sem `<script>` em Blade. | `filter-panel.js`, `selection.js` |
| CSS | Um SCSS, escopo `.ops-list`, tokens `--mp-*`. Sem `<style>` em Blade nem CSS por tela. | `resources/sass/ops/_ops.scss` |
| Excel | Job na fila `exports` com `SystemNotification`, lendo os mesmos filtros da tela. | `ExportDispatchPaymentJob` |

## 2. Anatomia (nesta ordem)

Wrapper obrigatório: `<div class="closure-dash my-panel ops-list" x-data="opsSelection()">`.

1. **Hero** `<x-ops.hero eyebrow title subtitle :updated-at>`: título em minúsculas com a primeira maiúscula, uma frase dizendo para que serve a tela. Ações globais no slot: `<x-ops.legend />` e `Exportar Excel` (`btn btn-light btn-sm btn-dash`).
2. **Ferramentas** `<section class="ops-toolbar">`, em linhas:
   - Linha 1: `<x-ops.search>` (sempre `wire:model.debounce.500ms`), `<x-ops.segmented>` para o tipo, `<x-ops.toggle>` para atalhos liga/desliga, "Limpar tudo" à direita quando houver critério.
   - Linha 2: `<x-ops.filters :payload="$this->listFilterPayload()" />`.
   - Linha 3 (só com filtro ativo): `<x-ops.chips :chips="$this->listFilterChips()" />`.
3. **Painel** `<section class="mp-panel">`: `mp-panel-head` (título, contagem de registros, "Atualizando…" via `wire:loading.delay`) → barra de carregamento → tabela → `<x-ops.pager>`.
4. **Estado vazio** `<x-closure.empty-state>` com texto que diz o que fazer; com critério ativo, botão "Limpar filtros".
5. **Barra de seleção** `<x-ops.selection-bar>` (dentro de `wire:ignore`), que só aparece com linhas marcadas. É o lugar das ações em lote ("Atribuir selecionadas", "Exportar selecionadas").

## 3. Tabela e cor da linha

- Uma tabela `.ops-table` com `colgroup` de larguras fixas (somar no máximo ~1.460 px para caber num monitor de 1920 sem rolagem lateral), sem altura máxima (a rolagem vertical é só a da página, nunca uma segunda barra dentro do painel; só há rolagem lateral se a tabela não couber), centralizada, texto de nome alinhado à esquerda (`is-left`).
- Primeira coluna: caixa de seleção com `data-ops-row` e `@change="recount()"`. Última coluna: **ação como botão** (`btn btn-primary btn-sm ops-action`, ícone + verbo), nunca ícone solto.
- Rodapé `tfoot` com totais da página quando houver valor somável.
- **Cor da linha = situação**, fonte única `RowState` (a legenda `<x-ops.legend>` lê dela). A linha ganha uma barra de acento à esquerda e um fundo suave (`ops-row-{estado}`); linha livre fica branca.

| Situação | Cor | Significado |
|---|---|---|
| `free` Livre | branca | Sem atividade aberta; pode ser atribuída |
| `assigned` Com usuário | azul | Atribuída a um usuário, em andamento |
| `stack` Na pilha | âmbar | Na pilha da empresa, ainda sem usuário |
| `done` Concluída | verde | Concluída há menos de 24 h; aguardando SAP |
| `late` SAP pendente | vermelha | Concluída há 24 h ou mais sem o SAP atualizar, ou informe rejeitado |

Outra lista com outra regra de situação **mapeia para estas cinco**; não cria cor de linha nova.

## 4. Selos, datas e valores (iguais em toda tela)

Tom semântico único: `green` (ok/concluído), `blue` (informativo/em andamento), `amber` (atenção), `red` (problema/vencido), `teal` (identidade/categoria), `muted` (neutro). Mesmos tons do `closure-dash` (`closure/_tones.scss`).

- **Selo** `<x-ops.badge tone label icon hint sm mono>`: 22 px de altura (`sm` = 18 px), pílula, 800. `mono` para códigos curtos (status de operação do SAP). Sempre `hint` explicando o que é.
- **Nota/OV** `<x-ops.note :note :kind :tone :hint :sub>`: número simples, ou dentro do selo com `kind="d5"` / `kind="ri"` (RI usa o mesmo selo do 1º ciclo da D5: azul; 2º ciclo verde; despachada cinza).
- **Data** `<x-ops.date :date :tone time hint>`: sempre `dd/mm/aaaa` (hora opcional em segunda linha). Tom `muted` = só informação, sem fundo; qualquer outro tom ganha fundo e cor. Sem data mostra "—".
- **Valor** `<x-ops.money :value signed>`: `R$ 1.234,56`, algarismos tabulares; `signed` pinta negativo de vermelho e positivo de verde (saldo).
- **Ausência** é sempre "—" (`.ops-empty`), nunca vazio, `---` ou `----`.

Cor por prazo vem de `Deadline`, nunca de `if` na view:

| Regra | Função | Faixas |
|---|---|---|
| Dias para vencer | `Deadline::remaining($dias, $avisoAte = 15)` | vencido vermelho; até `$avisoAte` dias âmbar; acima verde |
| Idade do que envelhece | `Deadline::age($dias, $okAte = 14, $avisoAte = 20)` | até 14 verde; até 20 âmbar; acima vermelho |

Prazos da Lista para Pagamento: **ADS** = idade (14/20); **Vencimento** = dias para vencer (15); **Fiscalizado** da Parcial = fiscalização + 5 dias, dias para vencer com aviso abaixo de 5 (o Informe Final mostra a data sem cor); Informe e Final OP20 são informativos. O texto do tooltip da data diz a regra.

## 5. Filtros centralizados (`WithListFilters`)

Nenhum filtro é componente Livewire à parte (o `components.filter.filter` legado custa um roundtrip por filtro, guarda estado em `$_SESSION` e não vai para a URL). No padrão:

```php
use WithListFilters;

protected $queryString = [/* … */ 'filters' => ['except' => [], 'as' => 'f']];

protected function listFilterDefinitions(): array
{
    return [
        'region'   => ['label' => 'Região'],
        'regional' => ['label' => 'Regional', 'depends' => ['region']],
    ];
}

protected function listFilterOptions(): array
{
    return [
        'region'   => $this->rememberFilterOptions('region', fn () => /* [['v' => 'Sul', 'l' => 'Sul']] */),
        'regional' => $this->rememberFilterOptions('regional', fn () => /* [['v' => 'X', 'l' => 'X', 'p' => ['region' => 'Sul']]] */),
    ];
}
```

- `$filters` (`key => [valores]`) vai para a URL (`?f[region][0]=Sul`), então a tela é compartilhável e sobrevive ao F5. Leia com `$this->filterValues('region')`.
- **Cascata genérica**: `depends` + `p` nas opções. A opção só aparece se, havendo pai marcado, o valor-pai dela estiver entre os marcados. O JS descarta o que saiu do escopo e o servidor revalida (`normalizeFilters`): valor ou chave desconhecidos nunca chegam à consulta.
- **Uma chamada só**: a seleção fica pendente no navegador (`opsFilterPanel`) e vai ao servidor ao fechar o menu (`applyListFilters`). O painel é `wire:ignore`; o servidor avisa a seleção final pelo evento `ops-filters` (chip removido, "limpar").
- Opções em cache de 10 min (`rememberFilterOptions`), por escopo (`listFilterCacheScope`).
- Aplicar filtro volta à página 1 e zera a seleção em lote.
- Para a consulta ler os filtros sem `$_SESSION`: `NoteFilter::filter($busca, $grupo, $this->filters)`. O Job de exportação continua recebendo os mesmos parâmetros.

## 6. Desempenho

- **Busca**: `debounce.500ms`. Sem o modificador, cada tecla vira uma requisição (o `bounce` antigo não existe no Livewire 2).
- **Trabalho por linha uma vez só**: avaliações caras (bloqueio, pilha da empresa) ficam em memo por requisição (`rowDispatchState`), zerado a cada render. Antes eram 3 avaliações por linha (tela, "selecionar todos", verificação do mestre).
- **Dados de cabeçalho em cache curto** (data da última atualização, `Bancoupdate`): 60 s.
- Carregamento percebido: após ~200 ms a tabela dá lugar ao esqueleto (`<x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none">` + `wire:loading.delay.class="d-none"` na tabela); respostas rápidas não piscam.
- Seleção em lote conta no navegador (`opsSelection`); só vai ao servidor quando se age.
- Filtro por coluna indexada; opção de filtro com contagem por opção só se for agregada numa consulta (não uma por opção).
- Meça antes de mexer: o Debugbar da stack Orca mostra as consultas; o alvo é **consultas por linha ≈ 0** (carregar relações por `with`/`load` depois da paginação).

## 7. Como migrar outra lista

1. Wrapper `closure-dash my-panel ops-list` e `x-ops.hero`; apague o `<style>` e o `list-shell-style`.
2. Troque os `@livewire('components.filter.filter')` pelo trait `WithListFilters` + `<x-ops.filters>`; leve `filters` para o `$queryString` e passe `$this->filters` para a consulta e para o Job do Excel.
3. Crie o apresentador da linha (ou leve o `@php` da view para uma classe) e devolva tons, não classes Bootstrap (`text-bg-*`, `table-danger`).
4. Mapeie a situação da linha para `RowState`; prazos para `Deadline`.
5. Troque selos, datas, valores e "sem dado" pelos componentes `<x-ops.*>`.
6. Troque `x-showselected`/botão de lote pela `<x-ops.selection-bar>`; troque a paginação por `<x-ops.pager>`.
7. Rode o Pest da tela, `npm run build` e confira no navegador (tela larga e estreita) antes de dar como pronta.

## 8. Checklist de PR

- [ ] Wrapper `closure-dash my-panel ops-list`; hero → ferramentas → painel → paginação.
- [ ] Sem `<style>`, `<script>`, `style=""` de layout nem classe `text-bg-*`/`table-*` na view.
- [ ] Filtros pelo `WithListFilters`, na URL; nenhum `@livewire('components.filter.filter')`.
- [ ] Busca com `debounce`; nenhuma requisição por tecla.
- [ ] Cor de linha só por `RowState`; cor de prazo só por `Deadline`.
- [ ] Selos, datas e valores só pelos `<x-ops.*>`; ausência é "—".
- [ ] Ação de linha é botão com verbo; ação em lote na barra de seleção.
- [ ] Estado vazio com texto e, se houver critério, "Limpar filtros".
- [ ] Excel por Job lendo os mesmos filtros.
- [ ] Sem N+1 por linha (medido); avaliações caras em memo.
- [ ] Testes (Pest) para as regras de cor/filtro novas e `OperationalListPatternTest` verde.
- [ ] Conferida no navegador, em tela larga e estreita.

## 9. Anti-padrões

- Cor de linha ou de prazo calculada na view (`@php if ($dias > 20) …`).
- Selo, data ou valor desenhados à mão (`<span class="badge text-bg-…">`, `number_format` solto).
- Filtro como componente Livewire próprio, estado em `$_SESSION`, ou um roundtrip por filtro.
- `wire:model` de busca sem `debounce`.
- Ícone solto como ação de linha.
- Tooltip com código interno (`production_assigned_to_user`) em vez de texto em português.
- Tabela sem `colgroup`, ou que obriga rolagem lateral num monitor de 1920.

## 10. Lista de controle (variante)

Lista de **acompanhamento** do que já foi despachado (ex.: Despacho → Controle de Pagamento, `Dispatchs\Payment\Stack`). Usa a mesma base (componentes `<x-ops.*>`, `WithListFilters`, esqueleto, paginação, seleção em lote), mas **não pode ser confundida com a lista operacional**, onde se despacha:

- **Wrapper** `closure-dash my-panel ops-list ops-control`; hero em índigo (`ops-hero--control`), eyebrow "Controle · {atividade}", selo "Lista de controle" e botão para a lista operacional da atividade.
- **Painel de situação** (`<x-ops.stat>`) logo abaixo do hero, discreto: contagem por status. Os cartões são filtros (`toggleStatus`) e contam no mesmo recorte da lista, sem o filtro de status. Analítico só aqui; nada de gráfico pesado.
- **Linhas sem cor de situação.** A cor de linha (`RowState`) é exclusiva da lista operacional. No controle a situação vive no selo de **Status** (`Tone::forProductionStatus`); a única marca de linha é **prioridade** (barra e fundo vermelhos suaves, `ops-row-priority`).
- **Comandos em dropdown** são o jeito do controle: "Ações em massa" (com a explicação de cada ação sob o nome, e "Forçar e ignorar a pausa") no painel e na barra de seleção, e o menu "⋮" por linha. Ação de linha é menu, não botão de despacho.
- **Filtros refletem a origem.** Clicar numa pessoa na Ocupação (menu lateral) dispara `filterUser`: o filtro "Usuário" fica marcado, aparece o chip e a URL (`f[user][0]=…`) muda. "Limpar filtros" (`clearAllCriteria`) zera filtros, busca, tipo e prazo; o chip individual remove um filtro só.
- **Uma consulta só** (`controlQuery()`) para lista, Excel e contagem dos cartões, para nunca divergirem. O Excel respeita os filtros (antes exportava tudo).
- **Sem subcomponente por linha.** Re-atribuir e Remover despacho são métodos do próprio componente (`askReattribute`/`confirmReattribute`, `askRemove`/`confirmRemove`), com a regra em `Services\Production\ProductionActions` (os subcomponentes `production.actions.*` das outras listas usam a mesma classe). Cada ação confere se a Production é visível naquele controle antes de agir e confirma por `chave` (hash do id) guardada em `pendingProductionId`.
- **Desempenho medido** (100 linhas, mesma base): de ~8,4 s e ~12.900 consultas para ~0,4 s e ~40 consultas. Causas: feriados lidos um a um para cada dia do prazo (`MeasurementDeadlinePolicy` agora lê o conjunto uma vez), 200 subcomponentes serializando a Production inteira, `Note` carregada por linha no escopo do informe, 2 consultas jurídicas por linha (agora 2 no total) e a mesma regra de `system_settings` lida por linha (memo por requisição web em `SystemSetting::getValue`).

## 11. Modais

Todo modal aberto a partir de uma lista segue o mesmo desenho (SCSS global `resources/sass/ops/_modal.scss`, porque os modais ficam fora do `.ops-list`):

- `<div class="modal fade ops-modal">` → `.modal-dialog.modal-dialog-centered` → `.modal-content.closure-dash.ops-list` (as classes do conteúdo liberam os selos `<x-ops.badge>` e os tons).
- **Cabeçalho** em degradê (mesmo do hero) com `<span class="modal-eyebrow">` (o assunto, ex.: "Histórico de status", "Definir prioridade") e o título (nota/ordem); fechar com `btn-close btn-close-white ms-auto`.
- **Corpo** sobre fundo claro: explicação em `.ops-modal-note`; informação em `.ops-modal-card` (cabeçalho no tom do assunto, corpo, `.ops-modal-meta` com data, pessoa e atividade); campos com `form-label` em caixa alta.
- **Rodapé** branco: "Fechar" (`btn-outline-secondary`) à esquerda da ação principal (`btn-primary`, ícone + verbo).
- Uma raiz só por componente Livewire: `<style>`/`<script>` dentro da `<div>` raiz, nunca fora (fora dela o modal abre vazio).
- Já no padrão: Histórico de status (`components.status.show-status`), Prioridade e Info da prioridade (`components.modal.priority`) e Busca em massa das listas.

## 12. Tokens (CSS centralizado)

- **Um arquivo de verdade:** `resources/sass/ops/_tokens.scss` guarda toda cor, degradê, sombra e raio dos componentes `ops-*` como variáveis `--ops-*` (escopo `.ops-list` e `.ops-modal`). `_ops.scss` (telas) e `_modal.scss` (modais) **não têm cor literal**: só `var(--ops-*)`. Mudar o visual de todas as listas e modais = editar o `_tokens.scss`.
- **Tons semânticos** (`green|blue|amber|red|teal|muted`) continuam em `closure/_tones.scss`; a cor da linha por situação é `--ops-row-*` (e `--ops-row-priority` no controle).
- **Regra de PR:** nada de `#hex`, `rgb()` ou `style="…"` de cor/cursor em view ou SCSS de lista. Precisa de uma cor nova? Crie o token em `_tokens.scss` com nome pelo papel (`--ops-warn-bg`), não pela cor. Utilitários prontos: `.ops-clickable` (cursor de mão em selo/valor que abre algo) e `.ops-row-actions` (dropdown da linha sem ser cortado).
- Único `style` aceito: o que carrega **dado** da view (largura de coluna do `colgroup`, `--w` do esqueleto).

## 13. Datas com prazo (regra para toda lista)

Data que antes pintava a **célula inteira** (`text-bg-*`, `table-*`, fundo por prazo) passa a ser sempre o selo `<x-ops.date>`, com o tom vindo de `Deadline` (`remaining` para "dias para vencer", `age` para idade) ou de uma regra da atividade escrita no apresentador/componente, nunca na view. A célula fica neutra; só o selo tem cor. Data informativa (sem prazo) usa o mesmo componente com tom `muted`. O texto do tooltip (`hint`) diz a regra.

### Data + dias nas telas de controle: `<x-ops.age>`

Em toda tela de **controle** (stack), a coluna que diz "há quanto tempo" (despachado, atribuído, informado) é o selo `<x-ops.age>`: a data em cima, os **dias corridos** embaixo ("N d") e o tom do prazo. Não se escreve `<x-ops.date ... :sub="$dias . ' d'">` à mão nem se calcula o tom na view.

| `rule` | O que mede | Verde | Âmbar | Vermelho |
|---|---|---|---|---|
| `dispatch` | Despachado há N dias | até 21 | até 30 | acima |
| `assigned` | Atribuído ao responsável atual há N dias | até 7 | até 9 | acima |
| `informed` | Informado há N dias | até 5 | até 8 | acima |

```blade
<td><x-ops.age :date="$row['dispatchAt']" rule="dispatch" /></td>
<td><x-ops.age :date="$row['attAt']" :days="$row['attDays']" rule="assigned" /></td>   {{-- :days = já calculado pelo apresentador --}}
```

- As faixas moram em `App\Support\Ops\ControlAge::RULES` (cor por `Deadline::age`); o tooltip diz a regra em português. Uma tela com faixa própria passa `:ok` e `:warn`; faixa nova que valha para várias telas entra em `ControlAge`, nunca em `@php`.
- Prazo que **vence** (dias que faltam) continua sendo `Deadline::remaining` com `<x-ops.date>` (ex.: Vencimento, Prazo da medição); `<x-ops.age>` é só para o que **envelhece**.
- Sem data aparece "—". O teste `ControlAgeTest` falha se uma coluna de despacho ou atribuição de um `stack` voltar a ficar sem cor.

### Acompanhamento de Serviços (a fila do usuário): `IsActivityAccompanyList`

O **Acompanhamento** (`services.accompany`) é a fila de trabalho do próprio usuário: as Notas/OVs que ele assumiu ("Assumir" na lista da atividade manda a nota para cá) e ainda não concluiu. Ele segue a anatomia das listas operacionais (hero → ferramentas → abas → painel → paginação) e tem o mesmo comportamento em todas as atividades "por nota": Análises (e Desenho), Pré-análise, Cadastro, Incorporação e Fluxo Reverso.

- **Uma base, um perfil por atividade.** O componente da atividade só usa `IsActivityAccompanyList`; tela, filtros e ações são os mesmos. O que é característica da atividade mora em `App\Services\Accompany\ActivityAccompanyProfile` (chave = `services.folder`): formulário e evento que o abre, limite de pausa, encerramento em massa (`analysis`, `preanalysis` ou `reverse`; Cadastro e Incorporação não encerram em massa), origem do prazo real e as colunas que só algumas têm (Grupo 2, "Em pausa"). Atividade nova = uma entrada no perfil, não uma tela copiada.
- **Linha pronta no apresentador.** `NoteAccompanyRowPresenter` devolve estado, tons e textos; a view só desenha. Coluna "Atribuído" = `<x-ops.age rule="assigned">` (§ acima).
- **Prazo real** (`App\Support\Ops\PrazoReal`): 30 dias de execução. Análises e Pré-análise contam os dias corridos desde a criação da nota; Cadastro, Incorporação e Reverso usam os dias que faltam do SAP (já sem os status não contabilizáveis). O selo (`<x-ops.prazo>`) mostra sempre a **data de vencimento** da nota e, embaixo, os dias que faltam ("N d") ou "N dias vencidos". Cor: vencido e até 5 dias vermelho, 6 a 9 âmbar, 10 ou mais verde. Regra geral: todo prazo em lista mostra data e dias (`<x-ops.date>` / `<x-ops.age>` / `<x-ops.prazo>`), nunca só o número.
- **Filtros** pelo `WithListFilters` (Rubrica e Município, na URL), opções só do que está na fila do usuário. Busca simples e em lote (`#buscar_multi`); a busca fica **sempre dentro do escopo do usuário** (um `OR` fora do grupo já vazou a fila de outros).
- **Visão administrativa:** só o superadm escolhe a fila de outro usuário (`user_s`, na URL como `usuario`); para os demais o parâmetro é ignorado. Iniciar e transferir só valem para a produção da fila que está na tela.
- **Ações** como botões com verbo: Iniciar (ou Continuar/Retomar, conforme o status) e Transferir. `wire:init="checkOpen"` continua abrindo a nota que já está em atividade. O limite de pausa avisa antes de iniciar outra.
- **Encerramento em massa** unificado em `ClosesAccompanyInBulk` (só encerra o que é do usuário da tela), com a barra de seleção `<x-ops.selection-bar>` e o modal no padrão `ops-modal`.
- **Modais legados** (formulário da atividade, pausa, transferência, histórico de status) continuam sendo os componentes de sempre, apenas com o cabeçalho no padrão.

### Acompanhamento de Pagamento: prazo de medição

`Services\Payment\Accompany\Main` segue o mesmo desenho do Acompanhamento por nota (hero → ferramentas → abas → painel), com filtros do `WithNoteListFilters` (Empreiteira, Rubrica, Região, Regional, Município) na URL e a linha pronta em `Services\Payment\PaymentAccompanyRowPresenter`. O prazo de medição é de **8 dias úteis** (sem sábado, domingo e feriado do estado, em ES e SP; `MeasurementDeadlinePolicy::businessDueAt`), contado:

- **Parcial:** da aprovação do engenheiro (`partials.decision_at`);
- **Final:** da entrega da ADS do informe (`tacit_delivered_at` na ADS tácita; senão `created_at` da ADS). ADS tácita ainda não entregue = sem prazo.

O selo mostra a data de vencimento e, embaixo, os dias úteis que faltam ("N d úteis") ou "N dias úteis vencidos". Cor: verde com mais de 4 dias úteis, âmbar nos últimos 4, vermelho vencido (a mesma faixa do Controle de Pagamento). O Controle de Pagamento continua com a regra própria (`payment.measurement_deadline`, 15 corridos em ES).

## 14. Situação da migração

Legenda: ✔ no padrão · — não existe · ✖ fora do padrão (a definir). Despacho = lista operacional (`Main`) e a da parceira (`PartnerMain`); Controle = `Stack` e `PartnerStack`; Serviços = lista do usuário.

| Atividade | Despacho (Main / Parceira) | Controle (Stack / Parceira) | Serviços | Accompany |
|---|---|---|---|---|
| Pagamento | ✔ / ✔ | ✔ / ✔ | ✔ | ✔ (`Services\Payment\Accompany\Main`; prazo de medição abaixo) |
| Publicação | ✔ / ✔ | ✔ / ✔ | ✔ (hoje sem uso na tela: o menu monta o accompany) | ✖ (3 abas, devoluções e BT Zero; ver pendências) |
| Fiscalização | ✔ / ✔ | ✔ / ✔ | ✔ | a definir |
| Levantamento | ✔ / ✔ | ✔ / ✔ (`Stack2` ✖: tela legada à parte) | ✔ | a definir |
| Desenho | ✔ / ✔ | ✔ / ✔ (menu "⋮" ainda monta `production.actions.*` uma vez por tela) | ✔ | ✔ (usa o de Análises) |
| Análises | ✔ / ✔ | ✔ / ✔ | ✔ | ✔ |
| Análises Pré | ✔ / ✔ | ✔ / ✔ | ✔ | ✔ |
| Comissionamento | ✔ / ✔ | ✔ / ✔ | ✔ | a definir |
| Fluxo Reverso | ✔ / ✔ | ✔ / ✔ | ✔ | ✔ |
| Default (Cadastro etc.) | ✔ / ✔ | ✔ / ✔ | — | a definir |
| Incorporação, Análise (inc), Cadastro, Entidade Externa | — | — | ✔ | ✔ Incorporação e Cadastro; Entidade Externa não expõe acompanhamento no menu |
| Encerramento | padrão próprio (`closure-dash`, ver `docs/encerramento/`) | | | |

### Outras telas no padrão de controle

- **Usuários da empresa terceira** (`/partner/admin/users`): `Partner\Admin\Users` (Livewire) no padrão de controle (`ops-control`): cartões-filtro (ativos, online, nunca acessaram, administradores, desativados), `WithListFilters` (empresa/filial, perfil, conexão), menu "⋮" por linha e barra de seleção para ações em massa. Escopo (matriz + filiais, só `onlyparner`), regras de proteção (ninguém desativa a si mesmo nem remove o próprio admin; reset só em ativo) e auditoria ficam em `App\Services\PartnerAccess\PartnerUserManager`, também usado pelo `PartnerAdminController`. Toda alteração de acesso passa por modal de confirmação que explica o efeito; desativar é reversível (rota `users.restore`). Ação em massa grava um único evento de auditoria (`bulk_updated_users`); ação em um usuário grava o evento dela. Testes: `tests/Feature/PartnerAdminUsersTest.php`.

Pendências conhecidas (dívida que as migrações revelaram):
- **Acompanhamento de Publicação** ainda no desenho antigo (filtros legados em `$_SESSION`, CSS inline, regra de negócio dentro do Blade). Tem três abas (Produção, Em espera, Transferências), escopo e equipamentos, devoluções (`return-work`, `return-ramal-work`) e o formulário do BT Zero.
- `services/*/accompany` de Comissionamento, Levantamento e Fiscalização apontam para componentes que não existem (`services.comission.accompany.main`, `services.survey.accompany.main`) e o menu não os expõe: rota morta; `Services\Analisesinc\Accompany\Main` e o acompanhamento de Entidade Externa também não têm acesso pelo menu.
- **Prazo "vencido":** a regra do padrão é vermelho (`Deadline::remaining`). Análises, Análises Pré, Comissionamento, Desenho, Fluxo Reverso e Default preservaram a regra antiga (vencido = cinza); Levantamento já usa vermelho. Decidir uma só.
- **Consultas lentas sem índice** (limite do banco, não da tela): `notes(nstats)` e `notes(type_note, nstats)` (Main de Desenho, Default, Análises Pré, Levantamento) e `count(*)` das regras de status (`RuleBuilder`).
- `Components\Transprod\Translist` faz um `UPDATE prodtransfers` por linha a cada render (Serviços de Comissionamento, Desenho e Levantamento).
- `ExportDispatchSupervisionJob` (Controle de Fiscalização) ignora status e localidade; `exportToExcel` do Controle de Comissionamento chama o job de Pagamento.
- `x-user.status-name` ainda emite `text-bg-secondary` para usuário inativo.
- `stack-mass-actions` de Pagamento tem o nome da lista fixo; cada atividade criou o seu parcial.
