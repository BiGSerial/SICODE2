# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Frontend
```bash
npm run dev        # Vite dev server (hot reload)
npm run build      # Build assets for production
```

### Backend
```bash
php artisan serve                  # Local dev server
php artisan queue:work             # Process async jobs
php artisan queue:restart          # Restart workers after code changes
```

### Linting & Formatting
```bash
./vendor/bin/pint                  # Format all PHP (PSR-12 preset)
./vendor/bin/pint --dirty          # Format only changed files
```

### Tests
```bash
./vendor/bin/pest                  # Run all tests
./vendor/bin/pest tests/Feature/CancellationRequestsTest.php  # Single file
./vendor/bin/pest --filter "test name"  # Single test by name
```

### Deploy (WSL)
```bash
deploy-sicode2 qa fast             # QA deploy rápido (sem DB)
deploy-sicode2 qa fast database    # QA deploy + migrations
deploy-sicode2 prod full           # Produção completo
```

### Setup de novo dev
```bash
chmod +x scripts/bootstrap_new_dev.sh
./scripts/bootstrap_new_dev.sh     # Menu interativo; opção 1 para setup base
```
Após subir containers Docker: `http://localhost:8080`

### Testar em worktree do Orca (Docker efêmero)
O container `sicode2-app`/`sicode2-nginx` (porta 8000) monta o checkout da **develop** (`/home/will/code/SICODE2`), não o seu worktree. Para validar código de um worktree, suba a stack própria dele — nunca use `:8000` nem `docker exec sicode2-app` para isso.
```bash
scripts/orca-worktree-setup.sh      # sobe a stack, imprime a URL (localhost:81xx); 1ª vez leva alguns minutos
scripts/orca-worktree-teardown.sh   # derruba só este worktree (--purge apaga o .env.orca)
```
- Comandos dentro da stack: `DC="docker compose -f docker-compose.orca.yml --env-file .env.orca"`, depois `$DC exec -T app php artisan ...` e `$DC exec -T -e HOME=/tmp app php artisan tinker` (o `HOME=/tmp` é necessário).
- Browser do Orca: `orca-ide tab create --url http://localhost:<porta>`. O login de `localhost` é compartilhado entre portas.
- O setup grava `APP_URL`/`ASSET_URL` com a porta da stack e roda `npm run build` a cada execução (`ORCA_BUILD_ASSETS=0` pula). O banco é o MariaDB compartilhado do host: migrations não rodam sozinhas.
- Ao terminar a validação, rode o teardown.

---

## Arquitetura

### Stack
- **Backend:** Laravel 10, PHP 8.1+, Livewire 2
- **Frontend:** Vue 3, Bootstrap 5, Vite (bundle), SASS
- **Bancos:** MySQL (primário) + 2 conexões SQL Server (sincronização externa)
- **Fila:** driver `database`; jobs assíncronos para exports e sync

### Camadas principais

**Livewire (`app/Http/Livewire/`)** — ~457 componentes reativos que constituem a maior parte da UI. Cada componente é uma classe PHP com state público e uma view Blade correspondente. É o padrão principal para interatividade.

**Services (`app/Services/`)** — lógica de negócio por domínio. Subdivisões relevantes:
- `Wall/` — sistema de monitoramento em tela cheia (Wall V2); ver `docs/WALL_V2_Documentacao_Tecnica.md`
- `HiringStatus/Rules/` — padrão Strategy para status de contratação
- `Reports/` — lógica de exportações Excel/PDF

**Jobs (`app/Jobs/`)** — processamento assíncrono; padrão obrigatório para exportações Excel pesadas que notificam o usuário via `SystemNotification` ao concluir.

**Repositories (`app/Repositories/`)** — abstração de query para dados de produção (`PublishRepository`, `SupervisionRepository`, `SurveyRepository`).

**Models (`app/Models/`)** — 93 models Eloquent. Hierarquia organizacional gerenciada pela tabela `user_closure` via `HierarchyService`.

### Controle de acesso
RBAC baseado em hierarquia. Permissões verificadas via `can:gate_name` (middleware) ou `auth()->user()->can('gate_name')` (Blade/código). A tabela `user_closure` define a árvore organizacional usada pelo `HierarchyService`.

### Sistema de notificações
Sempre usar `UserNotificationData` em código novo (ver `docs/Notificacoes.md`):
```php
$user->notify(new SystemNotification(
    new UserNotificationData(title: '...', message: '...', status: 'success')
));
```
Não usar o construtor legado com 5 parâmetros soltos.

### Armazenamento de arquivos (Storage)
Toda operação física de storage (gravação, leitura, download/stream, delete, move, URL/preview) é centralizada em `app/Services/Files/`, para funcionar igualmente em disco local, Azure Blob e S3:
- `FileStorageService` — camada base física; opera sobre um model (`File`, ou uma instância transitória `new File(['path' => ..., 'disk' => ...])` para arquivos fora da tabela `files`). Métodos: `exists`, `get`, `stream`, `download`, `delete`, `move`, `mimeType`, `putUploadedAs`, `temporaryLocalCopy` (copia o conteúdo para um arquivo local temporário quando uma lib externa exige caminho físico, ex. `ZipArchive::addFile`; o chamador remove o temporário depois).
- `StorageContextResolver` — resolve disco e contexto SP/ES para novos arquivos (prefixos `sp/...` / `es/...`). Nunca hardcode esse prefixo em componentes.
- Services de domínio usam os dois acima e cuidam de banco/modelo: `FileUploadService` (tabela `files`), `EvidenceFileService` (`EvidenceFile`, usado por protestos/cancelamentos), `App\Services\Legal\LegalDemandUploadService` (`legal_demand_files`).

#### Regras obrigatórias
- Livewire components e Controllers **nunca** chamam `Storage::store/storeAs/delete/url/exists/download/path`, `storage_path()` ou assumem caminho físico local (`file_exists`, `ZipArchive::addFile($fullPath)`) para um arquivo gerenciado (anexo, foto, evidência, documento jurídico, revisão histórica). Chame o service de domínio, que delega ao `FileStorageService`.
- Compatibilidade legada é obrigatória: registros antigos com `disk = null` continuam resolvendo para o disco `public` (`$file->disk ?? 'public'`). Não reescreva caminhos antigos numa migration só para "padronizar".
- Exceção: exportações/relatórios em `app/Jobs/*Export*` (ou comandos equivalentes) geram output do sistema, não upload de usuário — usar `Storage::disk('local')` ali é aceitável e não deve ser migrado para os services de arquivo.
- Antes de criar um novo fluxo de upload (avatar, logo, planilha temporária, etc.), avalie se o arquivo deve virar um registro `File`/`EvidenceFile`/`LegalDemandFile` ou se é um caso à parte (ex.: coluna string simples tipo `users.avatar`/`companies.logo`) — nesse caso, ainda assim delegue a operação física ao `FileStorageService` (via model transitório) em vez de chamar `Storage::` direto no componente.
- Ao adicionar/mexer em fluxo de arquivo, rode busca por `Storage::`, `storeAs(`, `storage_path(` no arquivo tocado antes de considerar terminado.

### Telas analíticas (dashboards e relatórios gerenciais)
**A Home ("Meu painel") é a referência**, junto com a versão da gerência (Serviços → Relatórios → Painel da equipe) e o Dashboard de atividade (`docs/dashboards/DASHBOARD_ATIVIDADE.md`, Despacho → Dashboard). Antes de criar ou mexer em qualquer tela analítica, leia `docs/PADRAO_DASHBOARDS.md` (camadas, anatomia da tela, gráficos, desempenho, Excel e checklist de PR). Em resumo, é obrigatório:
- Camadas separadas: componente Livewire só com filtros; dados em `*Analytics` (tela e Excel leem o mesmo); configuração de gráficos em `*Charts`; JS e SCSS compartilhados (`.closure-dash`, `my-panel.js`). Sem CSS/JS inline e sem CSS próprio por tela.
- Layout: hero → filtros na URL → estado vazio → seções `mp-section-title` com `<x-home.hero-card>`, painéis e `<x-home.bar-list>`. Cartões da mesma linha com a mesma coluna e `h-100`; uma tabela por bloco com `colgroup`; ações como botão.
- Gráficos pelo contrato `data-my-panel-chart` (in-place, aviso de vazio por cima). **Não usar `<x-grafico.apex>` em tela nova.**
- **Respiro:** texto nunca fica colado na borda de card, painel ou tabela. Conteúdo solto dentro de `.mp-panel` (ex.: `<x-home.bar-list>`) vai em `<div class="mp-panel-body">`; tabelas usam `.mp-table` (primeira/última coluna já respeitam `--mp-edge`). Vale para qualquer tela nova ou alterada, não só dashboards.
- Toda tela exporta Excel por Job (`Controle Exportacao`), textos e status em PT-BR, regras de cálculo documentadas e conferência no navegador antes de dar como pronta.

### Listas operacionais (telas onde se trabalha em cima de linhas)
**A Lista para Pagamento do Despacho é a referência** (`Dispatchs\Payment\Main`); a variante de controle é `Dispatchs\Payment\Stack`. Antes de criar ou mexer em qualquer lista de despacho, serviço ou controle, leia `docs/PADRAO_LISTAS_OPERACIONAIS.md` (anatomia, tabelas de cor, filtros, modais §11, tokens §12, datas §13, situação da migração §14). Em resumo, é obrigatório:
- Wrapper `closure-dash my-panel ops-list` (controle: `ops-control`); hero → ferramentas (busca, tipo, atalhos, filtros, chips) → painel com a tabela → `<x-ops.pager>`; carregamento com `<x-ops.skeleton>`. Sem `<style>`/`<script>` na view.
- **CSS centralizado:** toda cor/degradê/sombra em `resources/sass/ops/_tokens.scss` (`--ops-*`); `_ops.scss` e `_modal.scss` só usam `var()`. Nada de `#hex`, `rgb()` ou `style=` de cor/cursor em view (há teste que barra).
- Filtros só pelo trait `WithListFilters` (via `WithNoteListFilters` para Notas/OVs) + `<x-ops.filters>` (na URL, cascata genérica, uma chamada ao fechar o menu). **Não usar `@livewire('components.filter.filter')` em tela nova ou alterada.**
- Selo, data, valor e nota só por `<x-ops.badge>`, `<x-ops.date>`, `<x-ops.money>`, `<x-ops.note>`; ausência é "—". Data com prazo é selo (`Deadline`), nunca célula colorida. D5 e RI = número da nota dentro do selo (azul 1º ciclo, verde 2º, cinza despachada). Cor de linha só por `RowState` (lista operacional); o controle não tem cor de situação (só prioridade).
- Busca com `wire:model.debounce.500ms`; avaliação cara por linha em memo por requisição; relações carregadas após a paginação; nenhum subcomponente Livewire por linha.
- Modais no padrão `.ops-modal` (cabeçalho em degradê, rodapé branco, uma raiz por componente Livewire).
- Ação de linha é botão com verbo (operacional) ou menu "⋮" (controle); ação em lote na `<x-ops.selection-bar>`. Conferir no navegador antes de dar como pronta. Accompany ainda não segue o padrão (a definir).

### Catálogos (categorias, subcategorias e motivos)
Todo cadastro de categoria/motivo que o admin mantém vive em **uma tela só** (Administração → Catálogos → Categorias e motivos, `Admin\Catalog\Manager`), descrito em `App\Support\Catalog\CatalogRegistry` e servido por `CatalogService`. Antes de criar tela de cadastro de categoria/motivo/tipo, leia `docs/admin/CATALOGOS.md`: catálogo novo é uma entrada no registro (sem tela, sem CSS). Não criar CRUD de categoria à mão; não ligar reordenação onde o consumidor ordena por nome.

### Menu superior (topbar)
Definição única em `App\Support\Menu\TopbarNav` (ordem do fluxo: Atividades · Ocorrências · Engenharia · Responsável · Jurídico · Consultas · Relatórios · Administração), renderizada por `<x-menu.dynamic-dropdown>` em `resources/views/layouts/menu_itens.blade.php` (só um `@foreach`). Estilo em `resources/sass/nav/_topbar.scss`, comportamento em `resources/js/nav/topbar.js`; nada de `<style>`/`<script>` no menu. Contadores só pelo `TopbarCounts` (`count => 'chave'`), nunca um componente Livewire por item. Cada assunto mora em um menu só. Ver `docs/MenuSuperior.md`.

### Menu lateral (padrão obrigatório)
Todo menu lateral, em qualquer área, segue `docs/PADRAO_MENU_LATERAL.md` (leia antes de criar ou mexer em menu). Em resumo, é obrigatório:
- Uma única definição por área, `App\Support\Menu\<Area>Nav extends SidebarNav` (módulos → grupos → itens, com ícone `ri-*`, `match` de rotas e `counts`). O menu lateral **e** o dropdown do topo leem dela; nunca duas listas.
- Markup só pelo componente `<x-nav.sidebar :nav="<Area>Nav::class" :route="$route" label="…" />`; o Livewire `<Area>\Menu` é fino (só entrega a rota). Proibido HTML de sidebar à mão, `onlySection`, listas de rotas em componente, CSS/JS inline ou estilo de menu por tela (estilo em `resources/sass/nav/_sidebar.scss`, comportamento em `resources/js/nav/sidebar.js`).
- Grupos são dropdowns com cabeçalho de **cor própria** (azul), distinto dos itens. Todos os itens do módulo ficam visíveis; textos em PT-BR; item ativo pela rota.
- Já no padrão: Engenharia (`EngineerNav`, referência), Responsável (`ResponsibleNav`) e as atividades de despacho (`DispatchNav`) e de serviço (`ServiceNav`, com `service` + `variant` no contexto) Reclamações (`ProtestNav`, com `variant`) e Administração (`AdminNav`; o topo sai de `AdminNav::topNodes()`). Os `menu.blade.php` de atividade são só uma chamada ao componente; não escreva HTML de sidebar neles.
- Menu novo ou migrado precisa de testes (`locate()`, rotas existentes, item ativo; o teste genérico varre todo `*Nav`) e de conferência no navegador.

### Wall V2 (monitoramento em tela cheia)
Arquitetura em camadas dentro de `app/Services/Wall/`:
- `WallDataOrchestrator` — ponto de entrada único para todas as telas
- `Context/ScreenContextResolver` — resolve o tipo de tela (`production_services` vs `fixed_chart`)
- `Screen/ProductionScreenDataService` — telas dinâmicas com cache de 45 s por item
- `Fixed/*DashboardDataService` — telas fixas (ADS, Project Review, Reclamações)
- `Support/CacheLockTrait` — stale-while-revalidate com lock distribuído

#### Padrão obrigatório para gráficos (Chart.js)
- Nunca substituir `chart.data.datasets` por um novo array (`chart.data.datasets = ...`).
- Sempre atualizar datasets in-place (ex.: `Object.assign` por índice, `push` para novos, `splice` para remover excedentes).
- Em estado "sem dados", mostrar overlay/estado vazio sem limpar datasets do chart.
- Não chamar update com `labels=[]` e `datasets=[]` apenas para "limpar visual"; preserve o último estado por baixo do overlay.
- Aplicar esse padrão em qualquer gráfico novo ou manutenção (incluindo dashboards fixos ADS/Project Review/Reclamações).

### Encerramento — caixas de pendência, RI e padrão de UI
Antes de mexer em pendências entre áreas, RI (Retorno Interno) ou em qualquer tela do Encerramento, leia `docs/encerramento/CAIXAS_PENDENCIAS_E_RI_CHECKLIST.md` (regras de migration, contrato do RI, fases e log de andamento — atualize-o a cada sessão) e `docs/encerramento/PADRAO_UI_ENCERRAMENTO.md` (tokens, componentes `<x-closure.*>`, JS e formulários centralizados; não criar CSS/JS inline novo).

### Pint (formatação)
Preset PSR-12 com regras adicionais: `=` e `=>` alinhados por coluna, imports agrupados, trailing comma obrigatória em multiline. Executar `./vendor/bin/pint --dirty` antes de commitar.
