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

### Menu superior (topbar)
Definido em `resources/views/layouts/menu_itens.blade.php` via arrays de nós passados ao componente `<x-menu.dynamic-dropdown />`. Para adicionar itens, editar esse arquivo seguindo o padrão `nodes`/`sections` (ver `docs/MenuSuperior.md`).

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

### Pint (formatação)
Preset PSR-12 com regras adicionais: `=` e `=>` alinhados por coluna, imports agrupados, trailing comma obrigatória em multiline. Executar `./vendor/bin/pint --dirty` antes de commitar.
