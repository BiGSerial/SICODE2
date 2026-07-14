# Inventário técnico factual — identidade, empresas, contratos, atividades e autorização do SICODE legado

Data do levantamento: 2026-07-13. Escopo: código, configurações, rotas, Models, migrations e views presentes neste repositório. Este documento não propõe arquitetura nem presume um contexto operacional de São Paulo.

## 1. Resumo executivo factual

- A identidade local é `users.id` (UUID). O login web principal usa `email` e `password`, guard `web`, sessão e provider Eloquent `App\Models\User` (`config/auth.php`, `App\Http\Livewire\Auth\Login::login`). A API também autentica por email/senha e emite token Sanctum (`App\Http\Controllers\Api\AuthController::login`).
- `Registration` existe, mas não é identificador de autenticação. Não foi encontrado login por matrícula, nome ou username.
- Não há coluna de ativo/inativo. O bloqueio prático de usuário é o soft delete (`users.deleted_at`); o provider Eloquent não encontra registros eliminados normalmente.
- Autorização combina Gates sobre flags booleanas, Policies em dois recursos, middleware próprio por service e muitas filtragens inline. `superadm` normalmente recebe bypass nos Gates.
- Há três representações de vínculo organizacional/operacional: `users.company_id` (N:1), `company_user` (N:N) e `employees` (um registro esperado pelo Model, ligando usuário, contrato e service). O código usa as três; elas não são automaticamente sincronizadas.
- Empresa tem soft delete, mas não possui status ativo. Contrato não tem soft delete nem status: possui somente `date_end`, além dos booleanos `service` e `construction`. Não foi encontrada validação executável de vigência fora das telas de cadastro/edição.
- `services` é simultaneamente catálogo de atividades/módulos, parâmetro de rota, vínculo contratual e capacidade individual de serviço/despacho. Os relacionamentos contratuais e individuais são independentes no banco.
- Não existe campo ES/SP no usuário. `uf` pertence ao endereço da empresa, e `regional`/região aparece em cidades e outros domínios, não como origem organizacional de `users`. Após autenticação não foi encontrada ramificação por ES/SP.
- `users.id` é fortemente acoplado ao domínio por dezenas de FKs e colunas UUID sem constraint, incluindo produção, viabilidade, arquivos, comentários, auditoria, jurídico, cancelamentos, hierarquia e delegação.

## 2. Fluxo atual de autenticação

### Web

1. `GET /` em `routes/web.php` devolve `resources/views/auth/login.blade.php` quando `Auth::check()` é falso. `Auth::routes()` registra as rotas Laravel UI, inclusive `login`, `logout` e password reset.
2. A view contém o Livewire `App\Http\Livewire\Auth\Login` (view `resources/views/livewire/auth/login.blade.php`). `Login::rules` exige email válido e senha.
3. `Login::login` monta somente `email`/`password` e chama `Auth::attempt($credentials, $remember)`. Logo, o identificador comprovado é `users.email`; `Registration` não entra na tentativa.
4. O guard padrão é `web`, driver `session`, provider `users`; o provider é `eloquent` com Model `App\Models\User` (`config/auth.php`).
5. A comparação da credencial ocorre pelo provider/guard Laravel. O Model faz cast `password => hashed`; criações e alterações também chamam `Hash::make`. Não foi encontrado `Hash::check` em fluxo web ativo (há apenas exemplo comentado em `routes/api.php`). O algoritmo configurável está em `config/hashing.php`; não há validador próprio no login.
6. Em sucesso, `session()->regenerate()` evita reutilização do ID. Se `first_pass` for verdadeiro, tenta redirecionar para `login.show.change`; se `onlyparner`, para `partner.main.viability`; caso contrário, para URL intended ou `/home`. A rota nomeada `login.show.change` só foi encontrada no bloco comentado de `routes/web.php`; portanto a troca obrigatória não está comprovadamente alcançável pela configuração de rotas ativa.
7. Rotas protegidas usam `auth`, cujo alias é `App\Http\Middleware\Authenticate`. Além de autenticar, `Authenticate::handle` redireciona usuário `onlyparner` para o módulo partner, salvo lista explícita de rotas.
8. Logout web é o `logout` registrado por `Auth::routes()` e executado pelo trait `AuthenticatesUsers` de `LoginController`. O controller aplica `guest`, exceto em logout. Não há logout próprio ativo; as rotas de `CustomAuthController` estão comentadas.

`remember_token` é criado pela migration inicial, ocultado no Model e usado pelo segundo parâmetro de `Auth::attempt`. O driver de sessão é `SESSION_DRIVER`, com fallback `file`, lifetime padrão 120 minutos, em `config/session.php`.

### Implementações coexistentes

- `App\Http\Controllers\Auth\LoginController` usa o trait Laravel UI, mas a tela observada executa o Livewire acima.
- `App\Http\Controllers\CustomAuthController::login` repete email/senha, `Auth::attempt`, regeneração e redirecionamentos; suas rotas estão comentadas em `routes/web.php`.
- `POST /api/v1/auth/login` chama `Api\AuthController::login`, valida email/senha, usa `Auth::attempt` e `User::createToken('sicode-token')`. `GET /api/v1/me` e `POST /api/v1/auth/logout` usam `auth:sanctum`; logout remove apenas o token atual.

### Senha e recuperação

- `App\Http\Livewire\Password\Change::change_password` exige dois valores iguais, sem espaços e comprimento mínimo efetivo de 6 caracteres, salva `Hash::make(...)`, marca `first_pass=false` e redireciona a `home`. O componente existe, mas sua rota esperada não foi encontrada ativa.
- A criação legada em `Admin\User\Create::save` define senha fixa `123456`; `first_pass` permanece no default `true`. A ação mais nova `Admin\User\Actions\Usuario` também prepara senha temporária e primeiro acesso.
- `Admin\User\Update::reset_pass` pretende voltar a `123456` e `first_pass=true`, mas contém `dd(...)` antes da gravação; portanto, no código analisado o reset é interrompido.
- `Auth::routes()` e os controllers `ForgotPasswordController`/`ResetPasswordController` existem, e `config/auth.php` aponta para `password_reset_tokens`. Porém a migration presente é `2014_10_12_100000_create_password_resets_table.php`, que cria `password_resets`; há divergência nominal. AMBIGUIDADE: sem inspecionar o banco implantado não é possível afirmar que o reset por email funciona.
- Não foram encontrados Form Requests, Actions ou Services próprios no fluxo web principal.

## 3. Estrutura relevante de `users`

Fonte: migration inicial e migrations incrementais listadas abaixo; Model `App\Models\User`.

| Coluna | Definição / constraint | Cast / mass assignment | Uso factual observado |
|---|---|---|---|
| `id` | UUID, PK | gerado por `HasUuids` | identidade local e alvo de muitas relações |
| `manager_id` | UUID nullable, FK `users.id`, `nullOnDelete`, índice | fillable | hierarquia de usuários |
| `avatar` | text nullable | fillable | perfil/avatar |
| `name` | string, não nullable | fillable | nome exibido e editável |
| `Registration` | string nullable; sem unique | fillable | matrícula/cadastro, busca/listagem; não autentica |
| `email` | string, não nullable, unique | fillable | login web/API e edição |
| `email_verified_at` | timestamp nullable | datetime | estrutura Laravel; não foi observado `verified` nas rotas de negócio |
| `password` | string, não nullable | hidden, cast `hashed`, fillable | segredo de autenticação |
| `remember_token` | string(100) nullable | hidden | remember-me do guard web |
| `superadm`, `admin`, `management`, `operator`, `user`, `contract`, `bypassprod`, `engineer`, `btzero`, `analyst`, `legal_controller`, `legal_field`, `legal_manager` | boolean, default false | boolean, fillable | flags consultadas por Gate ou regras inline |
| `first_pass` | boolean, default true | boolean, fillable | força redirecionamento para troca de senha |
| `onlyparner` | boolean nullable, default 0 | boolean, fillable | restringe navegação ao conjunto partner |
| `company_id` | UUID nullable, FK `companies.id`, `nullOnDelete` | fillable | empresa direta do usuário; filtros operacionais |
| `responsible`, `can_dispatch` | boolean nullable, default false | boolean, fillable | Gate/responsável e permissão global consultada em partes do despacho |
| `permission_locks` | JSON nullable | array, fillable | bloqueios por flag manipulados na manutenção administrativa; não é role |
| `created_at`, `updated_at`, `deleted_at` | timestamps; `deleted_at` nullable | `SoftDeletes` | ciclo de vida; não há coluna status/active |

`User::booted` converte permissões booleanas nulas para `false` ao salvar. Não foram encontradas colunas `profile`, `level`, `type`, `unit`, `regional`, `state` ou `distributor` em `users`.

## 4. Matriz de flags e regras de autorização

Esta matriz consolida as regras executáveis encontradas; ocorrências repetidas de um mesmo Gate em rotas semelhantes foram agrupadas.

| Campo ou condição | Arquivo | Classe/Método | Ação protegida | Comportamento observado |
|---|---|---|---|---|
| `superadm` | `app/Providers/AuthServiceProvider.php` | `boot` | todos os Gates simples | bypass para flags registradas; acesso wall/config/system |
| `admin` ou `superadm` | `routes/web.php`; provider | grupo `/admin`, `/config` | usuários, empresas, contratos e configuração | Gate `admin`; superadm também passa |
| `management` ou `superadm` | `routes/web.php` | rotas monitor/reports/dashboards | monitorar e visualizar relatórios | bloqueio via `can:management` |
| `engineer` ou `superadm` | `routes/web.php` | grupo `/engineers` | telas de engenharia | Gate `engineer` |
| `responsible` ou `superadm` | `routes/web.php` | grupo `/responsible` | validação, viabilidade, informes | Gate `responsible` |
| `analyst` ou `superadm` | `routes/web.php` | grupo `/project-review` | fila e análise de projeto | Gate `analyst` |
| `admin|management|contract|superadm` | provider e rotas reports | `projectReviewReports` | dashboards/histórico de análise | acesso agregado |
| `onlyparner` | `Authenticate::handle`; `Login::login` | middleware/login | navegação pós-login | redireciona e restringe às rotas partner/files permitidas |
| service individual ou `superadm` | `CheckServiceOrDispatchPermission::handle` | middleware | `/services/{service}`, `/construction/{service}`, `/dispatch/{service}` | exige linha `service_users`; usa `service` ou `dispatch` conforme prefixo |
| `contract` | vários Livewire, ex. `Production\Users\Occupation::render` e `ProjectReview\History` | queries | escopo de dados | restringe por empresa/empresas vinculadas em várias telas; aplicação não é global |
| `bypassprod` | busca ampla em `app/Http/Livewire` | condicionais inline de produção | concluir/avançar produção | ignora alguns bloqueios operacionais; aplicação localizada |
| `can_dispatch` | provider e componentes de despacho | Gate/inline | despacho | Gate global existe, além do `service_users.dispatch`; não são equivalentes |
| `legal_controller` (+ admin/superadm) | provider | Gates `legal.demands.*` | triagem, atribuição, revisão, fechamento, reabertura | perfil controlador jurídico |
| `legal_field` (+ admin/superadm) | provider | `legal.demands.answer` | responder demanda | executante jurídico |
| `legal_manager` (+ admin/superadm) | provider | `legal.manager`, `legal.reports` | gestão/relatórios jurídicos | gestor jurídico |
| controller/field/manager | provider | `legal.demands.view` | leitura jurídica | união das três condições |
| Policies | `CancellationRequestPolicy`, `CancellationCategoryPolicy` | métodos da Policy | cancelamentos/categorias | autorização por usuário/recurso; registradas no provider |
| `Gate::allows('superadm')` | `Config\System\ScheduleMonitor` | ações de execução | executar/monitorar agenda | `abort_unless(...,403)` dentro do componente |
| empresa direta/pivot | `Responsible\Workedlist`, `ViabHist`, `WaitingFiveNotes` | queries | visualizar dados de empresa | superadm ignora; demais filtram `Companies` e/ou `Company` |

Flags `operator`, `user` e `btzero` têm Gates definidos; menus usam `operator` para itens de despacho e `user` para itens de execução (`resources/views/components/menu/activities-dropdown.blade.php`). Parte das rotas `btzero` tem apenas `auth`, logo a visibilidade de menu/flag não deve ser confundida com proteção uniforme de backend.

## 5. Modelo atual usuário/empresa

- `users.company_id → companies.id` é nullable e `nullOnDelete`; `User::Company` é `belongsTo(...)->withTrashed()`. Cardinalidade física: zero ou uma empresa direta por usuário.
- `company_user` possui FKs para empresa e usuário com cascade delete, sem unique composto. `User::Companies`/`Company::Users` formam N:N e incluem soft-deleted. O esquema admite múltiplas empresas e até duplicatas da mesma dupla.
- `employees` liga `user_id`, `contract_id` e `service_id`. `User::Employee` é `hasOne`, embora o banco não imponha unique em `employees.user_id`; tecnicamente podem existir várias linhas, mas Eloquent consome uma.
- A manutenção nova (`Admin\User\Actions\Usuario`) preenche `company_id`, pivot de empresas, services e contrato/employee. A manutenção antiga (`Admin\User\Create/Update`) cria/atualiza `Employee`, mas não grava `users.company_id`.
- Não há histórico dedicado de troca de empresa. Alterar `company_id`, sincronizar pivot ou atualizar `Employee` substitui estado corrente conforme o fluxo usado.
- `companies` possui UUID, `name`, `email`, `telephone`, paths de imagens, timestamps e `deleted_at`; endereço está em `andresscompanies` (`street`, `city`, `uf`, `complement`). Não existe coluna active/status nem distinção interna/contratada.
- Empresa pode ser soft-deleted por `Admin\Company\Delete::delete`, que também soft-deleta endereços. Usuários com `company_id` conservam o valor enquanto for soft delete; relações `withTrashed` ainda a resolvem. Se houver delete físico, a FK torna `company_id` nulo.
- Usuário sem empresa é permitido pelo banco. Alguns componentes presumem `Auth()->user()->Company->id` ou `Employee->Contract`, portanto podem falhar ou perder escopo; login não bloqueia.
- Não há evidência de que empresa determine automaticamente contexto geográfico operacional.

## 6. Modelo atual empresa/contrato

`companies 1:N contracts` é comprovado por FK e `Company::contracts`. `contracts` contém `id` bigint, `company_id` obrigatório/cascade delete, `number` nullable sem unique, `service` boolean, `construction` boolean, `date_end` nullable e timestamps. Não há `date_start`, status, suspensão, encerramento, prorrogação ou aditivo.

- Uma empresa pode ter vários contratos, inclusive vários simultâneos; não há unique ou regra de exclusividade.
- Contrato sem empresa é impedido pela FK não nullable.
- `Admin\Company\Contract\Create::save` exige empresa, número, data final e pelo menos um dos tipos; `Update::update` repete essas verificações. São verificações manuais de interface, não constraints equivalentes no banco.
- `Delete::delete` faz hard delete; cascades removem `employees` e `service_contract_rules` relacionados.
- A busca por `contracts.date_end`/`date_end` contratual encontrou apenas cadastro, edição e exibição. Não foi encontrada comparação com `now()`, Scheduled Command, Job ou rotina de expiração.
- Portanto, vencimento não impede login, criação, despacho ou execução por regra observada; `date_end` não produz efeito automático no código analisado.

## 7. Modelo atual contrato/atividade

- `service_contract_rules` é a pivot `contracts N:N services`, com `posts`, `qtd`, `days`, `dispatch` e timestamps. `Contract::services` e `Service::Contracts` materializam a relação.
- `Config\Services\Addrules::add_rules` impede duplicidade na interface por `exists()`, mas a migration não cria unique `(service_id, contract_id)`.
- Empresa executora elegível para uma produção é consultada por `Company::whereRelation('contracts.services', uuid do service)` em `Production\Actions\NewProduction` e `ToAssign`.
- A seleção de usuário então combina `service_users.service_id` e `users.company_id`. Isso é validação de query naquele fluxo, não regra global nem constraint cruzada.
- Não existe tabela `contract_activities`; a atividade contratual observada é o próprio `services` via pivot. `employees.service_id` também associa um service ao vínculo usuário/contrato.
- Não foi encontrada validação de vigência contratual ao criar, atribuir, despachar ou executar atividade.

## 8. Análise factual de `services`

`services` possui `id` bigint, `uuid` unique nullable (preenchido na migration e gerado no Model), `service`, `status` integer nullable, `folder`, `project`, `construction`, `icon`, `canReturn` e timestamps. Não há seeder de catálogo encontrado; os registros são administráveis por `Config\Services\Create/Update/Delete/Services`, logo os nomes efetivos dependem do banco.

Usos comprovados:

- atividade/tipo de negócio: `productions.service_id`, `viabilities` e componentes específicos como cadastro, desenho, levantamento, análise, publicação, fiscalização/supervisão, pagamento, obra externa e incorporação;
- módulo/chave de navegação: `{service}` nas rotas de services/construction/dispatch e `folder`/ícone em menus;
- capacidade do usuário: `service_users(user_id, service_id, service, dispatch)`;
- escopo contratual: `service_contract_rules(contract_id, service_id, posts, qtd, days, dispatch)`;
- filtro de tela e relatório: queries por UUID de service em múltiplos Livewire/exports;
- configuração de comportamento: `project`, `construction`, `canReturn`, statuses auxiliares.

Relacionamentos factuais:

| Relação | Implementação | Observação |
|---|---|---|
| usuário → service | `service_users`; também `employees.service_id` | duas representações, sem sincronização automática global |
| empresa → service | indireta por `companies → contracts → service_contract_rules → services` | não existe pivot direta empresa/service |
| contrato → service/atividade | `service_contract_rules` | vínculo N:N |
| usuário → contrato | `employees` | Model declara hasOne, banco permite N |
| contrato → atividade separada | não encontrada | service desempenha esse papel |

Um usuário pode possuir `service_users` que o contrato/empresa não possui: o banco não contém constraint cruzada que impeça. A tela nova limita `serviceList` a `$contract->services`, mas gravações diretas, dados antigos ou outros fluxos podem divergir.

## 9. Fluxo de criação de usuário

### Entrada e autorização

`GET /admin/user/list`, rota `admin.user.list`, usa `auth` e `can:admin`, chama `AdminController::user_list` e monta componentes administrativos. Não há Form Request. O backend Livewire herda a proteção da página, mas seus métodos não repetem Gate próprio.

### Fluxo legado `Admin\User\Create::save`

1. Campos da view: nome, email, matrícula (`registration`), flags `superadm`, `admin`, `management`, `engineer`, `operator`, `user`, `contract`, `onlyparner`, empresa, contrato e service.
2. Validação manual exige apenas email presente e nome não vazio; não aplica regra `email`, unique, exists, empresa ativa ou vigência.
3. Se o criador tem flag `contract`, força a mesma flag no novo usuário.
4. Cria `User` com senha `123456` hasheada. Não envia ativação; `first_pass` default true força troca no próximo login.
5. Se contrato e service foram escolhidos, cria `Employee`; empresa é inferida pelo contrato. Usuário pode ser criado sem empresa, contrato, employee ou service.

### Fluxo novo `Admin\User\Actions\Usuario::newUser/save`

O componente expõe nome, email, `Registration`, `company_id`, contrato, múltiplos services com flags service/dispatch, múltiplas empresas e todas as flags listadas em `LOCKABLE_PERMISSIONS`. Suas rules exigem empresa e contrato existentes, email válido e booleanos. A lista de services vem de `Contract::services`, e grava `ServiceUser`; também administra `permission_locks` e senha temporária. Não há validação de `contracts.company_id == users.company_id` como constraint de banco, nem de `date_end`.

## 10. Fluxo de edição e manutenção de usuário

- Edição antiga: `Admin\User\Update::update` altera nome, email, matrícula e flags; atualiza/cria `Employee` quando contrato/service existem. Não remove Employee se forem limpos, não sincroniza `company_id`/pivot e não valida vigência.
- Edição nova: `Admin\User\Actions\Usuario` carrega usuário, preenche `company_id` a partir de Employee se estiver vazio, gerencia contrato, services, empresas N:N, flags e locks. Troca substitui valores correntes; não há histórico organizacional.
- Perfil: `Home\Profile` valida email unique ignorando o próprio usuário e altera nome/email/avatar. `HomeController::profile`/componente devem ser considerados em conjunto; o componente recebe usuário e não representa autorização administrativa.
- Senha própria: `Password\Change` descrito na seção 2. Reset administrativo antigo está interrompido por `dd`.
- Exclusão/restauração: `Admin\User\Delete::delete/undelete` usa `SoftDeletes`; restauração existe. Soft delete não apaga relações por cascade porque não é delete físico.
- Bloqueio/inativação: não existe flag active. Soft delete é o mecanismo observado para retirar usuário do provider normal.
- Efeitos automáticos de troca de empresa sobre services, flags ou permissões não foram encontrados; dependem do componente/ação usado.

## 11. Situação técnica dos usuários oriundos de São Paulo

- Nenhuma coluna de origem ES/SP foi encontrada em `users`, no Model ou nas telas de usuário.
- `Registration` é texto livre e não comprova origem. `company_id` e `company_user` representam vínculos, não contexto geográfico.
- `andresscompanies.uf` localiza endereço da empresa; `cities.regional/regiao` localiza cidades. Nenhuma dessas colunas participa de `Login::login` nem dos Gates.
- A única string de infraestrutura `/es/` encontrada no componente administrativo compõe instrução de acesso e não classifica usuário.
- Não foram encontradas condicionais hardcoded ES versus SP ligadas ao usuário.
- Assim, no código analisado, usuários oriundos de SP são tecnicamente indistinguíveis após login salvo pelos mesmos dados/flags/vínculos que qualquer usuário. O código não permite concluir a origem organizacional e não contém contexto operacional SP.

## 12. Dependências de `users.id`

Matriz priorizada; “sim” significa constraint explícita encontrada em migration. “não” indica coluna criada sem constraint explícita naquela migration.

| Tabela | Coluna | FK física | Model/Relacionamento / significado observado |
|---|---|---:|---|
| `productions` | `user_id` | não (declaração defeituosa `reference`) | executor/atribuído; `User::Productions` |
| `viabilities` | `user_id` | sim | usuário da viabilidade; `User::Approvals` é relação separada |
| `viability_approvals` | `user_id` | não | aprovador/usuário da aprovação |
| `employees` | `user_id` | sim | vínculo usuário–contrato–service |
| `service_users` | `user_id` | sim | capacidades service/dispatch |
| `company_user` | `user_id` | sim | vínculo N:N com empresas |
| `work_reports` | `user_id` | não | autor/executor do informe; `canceled_by` posterior tem FK |
| `comments`, `external_comments`, `tacit_comments` | `user_id` | não | autor de comentário |
| `notifies` | `user_id` | declaração `reference` | destinatário/usuário da notificação |
| `activeusers` | `user_id` | sim | watchdog/estado online |
| `files`, `notetimelines`, `audits`, `priorities` | `user_id` | não | autoria/rastreio histórico |
| `forms`, `manualconfirms`, `return_works`, `d5_returns` | `user_id` | sim | autoria/executor de formulários e retornos |
| `externals`, `external_comments`, `adsforms`, `partials`, `daysviabs`, `ramal_reports` | `user_id` | não | autoria/operador |
| `evidence_files`, `technical_reports`, `read_receipts` | `user_id` | sim | arquivo/relatório/leitura do usuário |
| `user_assignments` | `user_id` | sim | atribuições polimórficas |
| `protest_users`, `protest_user_triggers` | `user_id` | sim | cadeia de usuários de protesto |
| `protest_jobs` | `created_by`, `owner_id`, `closed_by` | sim | criador, responsável e encerrador |
| `cancellation_requests` | `requested_by`, `assigned_to`, `closed_by` | sim | solicitante, atribuído e encerrador |
| `cancellation_request_events` | `actor_id` | sim | autor do evento |
| `ads_requests` | usuário solicitante | sim | solicitação ADS |
| `ads_request_default_users` | `user_id`, `created_by` | sim | destinatário padrão e cadastrador |
| `user_closure`, `user_delegations`, `users.manager_id` | vários UUIDs | sim | hierarquia, ancestralidade e delegação |
| `project_review_*` | `user_id`, `submitted_by`, `decided_by` | sim | rascunho, anexo, submissão e decisão |
| `timeline_events` | `actor_user_id`, `owner_user_id` | sim | ator e proprietário |
| `legal_demands` e tabelas jurídicas | controller, assigned, closed, actor, target, from/to, uploaded/removed/linked/created/updated | sim, `nullOnDelete` | autoria, atribuição, movimentação e auditoria jurídica |
| `walls`, `wall_screens`, `system_settings` | `created_by`, `updated_by` | sim | auditoria de configuração |

Há ainda UUIDs sem FK em `return_ramals.user_id`, `manualnotes.user_id` (declaração `reference`), `tacit_comments.responsible_id` e outros legados. Isso confirma acoplamento sem integridade uniforme.

## 13. Uso atual de Gate, Policy e Middleware

- Gates simples: `superadm`, `admin`, `management`, `engineer`, `operator`, `user`, `responsible`, `btzero`, `can_dispatch`, `analyst`. Todos aceitam a flag correspondente ou `superadm`.
- Gates compostos: `viewLogViewer`, `projectReviewReports` e o conjunto `legal.*` descrito na seção 4.
- Policies registradas: `CancellationRequestPolicy` e `CancellationCategoryPolicy`. Não foi encontrado método `Gate::before` nem `Policy::before`; o bypass superadm é repetido nos Gates.
- Alias `auth` aponta para middleware próprio `Authenticate`; `can` é o middleware Laravel; `admin` aponta para `AdminMiddleware`, que na prática exige `superadm` e redireciona `/`. O alias `admin` não foi encontrado como proteção principal das rotas administrativas atuais.
- `check.service.dispatch` aponta para `CheckServiceOrDispatchPermission` e aborta 403 quando falta linha/flag individual; superadm passa.
- A autorização não é majoritariamente Policy: há uso significativo de Gates em rotas, mas também grande volume de condições inline em Livewire, Controllers e Blades.

## 14. Regras hardcoded encontradas

- Senha inicial/reset: literal `123456` em componentes de criação/manutenção de usuário.
- `onlyparner`: lista fixa de prefixos/nomes liberados em `Authenticate::isAllowedOnlyPartnerRoute`.
- Prefixos `services`, `construction`, `dispatch` determinam qual booleano de `service_users` é testado no middleware.
- `LOCKABLE_PERMISSIONS` e `BOOLEAN_PERMISSIONS` são arrays estáticos de flags em `Admin\User\Actions\Usuario` e `User`.
- IDs opacos usados como nomes de eventos Livewire em `Production\Actions\NewProduction`; são acoplamento de UI, não IDs de autorização.
- Buscas por comparações diretas de email/ID de usuário ligadas a autorização não produziram regra central relevante no escopo analisado. IDs de service aparecem em filtros e configurações operacionais; como o catálogo não está em seeder, não se atribuiu significado sem banco.

## 15. Pontos de acoplamento

1. Flags booleanas diretamente em `users` são lidas por Gate, middleware, Blade e query.
2. `users.id` é chave operacional e de auditoria em muitos domínios.
3. Empresa aparece em `users.company_id`, `company_user` e indiretamente em `employees.contract_id`.
4. Service aparece com chave bigint e UUID; relações antigas e novas usam chaves diferentes.
5. Contrato/service individual é validado sobretudo pela interface, não por constraints cruzadas.
6. Soft-deleted users/companies são explicitamente carregados em algumas relações, preservando autoria, mas não de forma uniforme.
7. Componentes presumem relações opcionais (`Company`, `Employee.Contract`), embora o banco permita ausência.

## 16. Ambiguidades não comprovadas pelo código

- AMBIGUIDADE: qual das telas antiga/nova de manutenção é a única efetivamente usada em produção; ambas permanecem no repositório.
- AMBIGUIDADE: estado real do schema de password reset devido a `password_resets` versus configuração `password_reset_tokens`.
- AMBIGUIDADE: como o primeiro acesso chega atualmente à troca de senha, pois `login.show.change` aparece apenas em rotas comentadas.
- AMBIGUIDADE: conteúdo atual do catálogo `services`; não há seeder factual e não foi usada uma base operacional como fonte deste documento.
- AMBIGUIDADE: existência de duplicatas em `company_user`, `employees` ou `service_contract_rules`; o schema permite algumas delas.
- AMBIGUIDADE: sem dados não se mede quantos usuários têm `company_id`, pivot, Employee ou combinações divergentes.
- AMBIGUIDADE: o significado de origem organizacional não pode ser derivado de empresa, matrícula ou endereço.
- AMBIGUIDADE: regras apenas visuais em Blade podem ocultar controles sem proteger chamadas Livewire; cada ação sensível exige auditoria individual para afirmar proteção completa.

## 17. Perguntas de negócio que o código não consegue responder

1. Qual vínculo é canônico hoje: `users.company_id`, `company_user` ou `employees → contract`?
2. O que identifica formalmente um usuário oriundo de SP, se essa informação não está em `users`?
3. Uma pessoa pode representar simultaneamente várias empresas e contratos, ou isso é apenas capacidade técnica acidental?
4. O término de contrato deveria bloquear quais operações e em que instante?
5. Os booleanos de contrato `service`/`construction` têm precedência sobre `service_contract_rules`?
6. `operator`, `user`, `can_dispatch` e `service_users.dispatch/service` devem ser cumulativos ou alternativos?
7. Qual é a fonte correta quando service individual diverge do service contratado?
8. Soft delete representa inativação temporária, desligamento ou exclusão lógica definitiva?
9. Quais registros atuais são inconsistentes entre as três formas de vínculo empresa/usuário?
