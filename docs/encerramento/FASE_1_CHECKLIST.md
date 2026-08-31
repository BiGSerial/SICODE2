# Fase 1 — Checklist de Implementação

> Módulo: Controle Operacional de Encerramento. Escopo completo em [PLANEJAMENTO_CONTROLE_OPERACIONAL_ENCERRAMENTO.md](PLANEJAMENTO_CONTROLE_OPERACIONAL_ENCERRAMENTO.md).
>
> **Objetivo da Fase 1:** competência mensal + meta congelada + passivo, todos **somente leitura**, mais uma primeira versão (parcial) do Detalhe da Ordem. Nenhuma assunção, nenhuma pendência, nenhuma automação ainda.

---

## 0. Pré-requisitos (já resolvidos na Fase 0 — não reabrir)

- [x] Regra de encerramento: `orders.statusSist LIKE 'ENT%' OR LIKE 'ENC%'`.
- [x] Regra de entrada na meta: `orders.statusSist LIKE 'LIB%'` **E** `Operation(operacao='0020')` com `status LIKE 'CONF%'` **E** `fimReal IS NOT NULL`.
- [x] Mapa de tipos de Ordem: `200→OV`, `150/170/190→EP` (`180` não existe na base hoje).
- [x] Cancelamento: Ordem cancelada sai de meta/passivo ativo, mantém histórico.

---

## 1. Migrations

- [ ] `xxxx_xx_xx_create_closure_cycles_table.php`
  - `id`, `year` (smallint), `month` (tinyint), `label` (string, ex. `"2026-09"`), `status` (string, default `OPEN`), `frozen_at` (timestamp nullable), `frozen_by` (FK `users` nullable), timestamps.
  - Unique composto `(year, month)`.
- [ ] `xxxx_xx_xx_create_closure_targets_table.php`
  - `id`, `closure_cycle_id` (FK `closure_cycles`), `order_id` (FK `orders`, **unique**), `note_id` (FK `notes`, denormalizado só para leitura/agrupamento), `entry_rule` (string, ex. `'lib_op20_conf_fimreal_v1'` — versionar a regra), `entry_reference` (json — snapshot: id da Operation 0020, valor de `fimReal`, `statusSist` da Ordem no momento), `snapshot_status_sist` (string), `frozen_at` (timestamp), timestamps.
  - Índices: `order_id` (unique, já cobre passivo/meta lookup), `closure_cycle_id` (para listar meta por competência).
- [ ] `xxxx_xx_xx_add_closure_permissions_to_users_table.php`
  - `closure_manager` (boolean, default `false`, after algum campo de referência como `analyst`).
  - `closure_operator` (boolean, default `false`) — **decisão de escopo**: criar já nesta migration (mesmo padrão do Jurídico, que criou as 3 colunas de uma vez), mesmo que só passe a ser usada na Fase 2, para evitar uma segunda migration em `users` depois. Se preferir manter a Fase 1 estritamente mínima, mover esta coluna para a Fase 2 — decisão de quem implementa.
  - Seed: `UPDATE users SET closure_manager = true, closure_operator = true WHERE superadm = true` (mesmo padrão usado na migration do Jurídico).
- [ ] Rodar `php artisan migrate` local e conferir `php artisan migrate:status`.

## 2. Models

- [ ] `App\Models\ClosureCycle`
  - `$fillable`: `year, month, label, status, frozen_at, frozen_by`.
  - `$casts`: `frozen_at => datetime`.
  - Relação `Targets()` → `hasMany(ClosureTarget::class)`.
  - Constantes de status: `STATUS_OPEN = 'OPEN'`, `STATUS_FROZEN = 'FROZEN'`, `STATUS_ARCHIVED = 'ARCHIVED'`.
- [ ] `App\Models\ClosureTarget`
  - `$fillable`: `closure_cycle_id, order_id, note_id, entry_rule, entry_reference, snapshot_status_sist, frozen_at`.
  - `$casts`: `entry_reference => array`, `frozen_at => datetime`.
  - Relações: `Cycle()` → `belongsTo(ClosureCycle::class, 'closure_cycle_id')`; `Order()` → `belongsTo(Order::class)`; `Note()` → `belongsTo(Note::class)`.
- [ ] `App\Models\Order` — adicionar (sem alterar o resto do model):
  - `ClosureTarget()` → `hasOne(ClosureTarget::class)`.
- [ ] `App\Models\Note` — adicionar:
  - `ClosureTargets()` → `hasMany(ClosureTarget::class)` (via `note_id` denormalizado, só para telas agrupadas por Nota).

## 3. Comando de congelamento da meta

- [ ] `App\Console\Commands\Closure\FreezeTarget`
  - Assinatura: `closure:freeze-target {competencia?}` (formato `AAAA-MM`; sem argumento, infere o mês corrente).
  - Passos (ver §14 do plano):
    1. `firstOrCreate` do `ClosureCycle` do mês alvo (`status=OPEN` se novo).
    2. Query de elegibilidade: `Order::where('statusSist','like','LIB%')->whereHas('Operations', fn($q) => $q->where('operacao','0020')->where('status','like','CONF%')->whereNotNull('fimReal')->whereBetween('fimReal', [inícioMesReferência, fimMesReferência]))->where('canceled', false)->whereDoesntHave('ClosureTarget')`.
    3. Excluir Ordens de Nota totalmente cancelada (reaproveitar `Note::scopeExcludeCanceledFullDone` via `whereHas('Note', ...)` ou relação inversa).
    4. Para cada Ordem elegível: `ClosureTarget::create([...])` com snapshot (`entry_reference` = json com `operation_id`, `fim_real`, `status_sist_no_momento`).
    5. Ao final: `ClosureCycle::update(['status' => FROZEN, 'frozen_at' => now(), 'frozen_by' => ...])` — **só quando rodado com uma flag explícita de confirmação** (ex. `--freeze`), para permitir rodar em modo "dry-run" antes (listar quantas Ordens entrariam, sem congelar) — útil para conferência manual nas primeiras execuções.
  - Proteção de idempotência: `whereDoesntHave('ClosureTarget')` na query + `unique(order_id)` no banco garantem que rodar 2x não duplica.
  - Logar quantidade de Ordens processadas (padrão `RegistroJson` já usado em `sicode:upd_baseOrder`, se fizer sentido reaproveitar).
- [ ] Rodar manualmente contra o banco de DEV e conferir que o resultado bate com os números já validados nesta sessão (ver §5 "Critérios de aceite" abaixo).

## 4. Gates / permissões

- [ ] Em `app/Providers/AuthServiceProvider.php::boot()`, adicionar (mesmo bloco de padrão do Jurídico):
  ```php
  $isClosureManager  = fn (User $u) => $u->closure_manager || $u->superadm || $u->admin;
  $isClosureOperator = fn (User $u) => $u->closure_operator || $u->superadm || $u->admin;

  Gate::define('closure.manager', $isClosureManager);
  Gate::define('closure.view', fn (User $u) => $isClosureManager($u) || $isClosureOperator($u));
  ```
  (`closure.orders.claim` e demais gates de operador ficam para a Fase 2, mas a coluna `closure_operator` já existe desde a migration acima.)
- [ ] Em `app/Http/Livewire/Admin/User/Actions/Usuario.php` (+ blade correspondente): adicionar `closure_manager` e `closure_operator` ao `$fillable`/`$rules` e aos checkboxes da tela de edição de usuário, mesmo padrão de `legal_controller/legal_field/legal_manager`.
- [ ] Conceder `closure_manager=true` para os usuários que vão validar a Fase 1 em DEV (via seed manual ou tela de admin).

## 5. Livewire — telas (somente leitura)

- [ ] `App\Http\Livewire\Closure\Cycles\Overview` (Visão Geral parcial — §15.1, só os blocos que já fazem sentido nesta fase: contagem de meta e passivo; **sem** blocos de pendências/equipe/gargalos, que são de fases futuras).
- [ ] `App\Http\Livewire\Closure\Cycles\Meta` (Meta da Competência — §15.2): lista `ClosureTarget` da competência selecionada, agrupada visualmente por Nota (`Note::Orders()`), com seletor de competência.
- [ ] `App\Http\Livewire\Closure\Cycles\Passive` (Passivo — §15.3): `ClosureTarget` cujo `closure_cycle_id` é anterior à competência corrente **e** a Ordem ainda não está `ENTE%`/`ENCE%` — aging calculado a partir de `frozen_at`.
- [ ] `App\Http\Livewire\Closure\Orders\Detail` (Detalhe da Ordem, **v1 parcial** — §15.6): dado um `order_id`, mostrar:
  - `statusSist` atual (lido direto de `orders.statusSist`, sem cache);
  - se está em meta ou passivo (via `ClosureTarget` + comparação de competência);
  - competência original (`ClosureTarget.Cycle`);
  - aging (dias desde `ClosureTarget.frozen_at`);
  - **sem** responsável/localização/pendências ainda (isso é Fase 2/3/4 — deixar claro na tela, ex. com uma nota "Assunção ainda não disponível nesta fase").
- [ ] Cada componente protegido por `@can('closure.view')` (Detalhe) ou `@can('closure.manager')` (Overview/Meta/Passivo) na view ou `middleware('can:closure.manager')` na rota.

## 6. Controller + rotas

- [ ] `App\Http\Controllers\ClosureController` (ou reaproveitar padrão de `LegalController` — um controller por módulo, um método por tela, retornando uma view que embute o componente Livewire).
- [ ] Em `routes/web.php`, novo grupo (mesmo padrão do `juridico`):
  ```php
  Route::prefix('encerramento')->name('closure.')->middleware(['auth', 'can:closure.manager'])
      ->controller(\App\Http\Controllers\ClosureController::class)->group(function () {
      Route::get('/', 'overview')->name('overview');
      Route::get('/meta', 'meta')->name('meta');
      Route::get('/passivo', 'passive')->name('passive');
  });
  Route::get('/encerramento/ordem/{order}', [\App\Http\Controllers\ClosureController::class, 'orderDetail'])
      ->whereNumber('order')->middleware(['auth', 'can:closure.view'])->name('closure.order.detail');
  ```

## 7. Menu

- [ ] **Decisão pendente, não bloqueante**: o plano original (§21/§23, Fase 6) previa só adicionar item de menu quando o módulo estivesse navegável ponta a ponta. Dado o objetivo reafirmado do usuário ("fácil acesso para responder qualquer pergunta"), considerar adicionar já agora um item simples em `resources/views/layouts/menu_itens.blade.php` (seguindo `docs/MenuSuperior.md`), gated por `closure.manager`, apontando para `/encerramento`. Confirmar com o usuário antes de mexer no menu (arquivo compartilhado por todo o sistema).

## 8. Testes (Pest)

- [ ] `tests/Feature/Closure/FreezeTargetCommandTest.php`
  - Ordem elegível (LIB + OP20 CONF + fimReal no mês) entra na meta.
  - Ordem com `statusSist` `ABER`/`BLOQ` **não** entra.
  - Ordem com OP20 `fimReal` preenchido mas status `CNPA` (não `CONF`) **não** entra.
  - Rodar o comando 2x no mesmo mês não duplica `ClosureTarget` (idempotência).
  - Ordem cancelada (`orders.canceled=true`) não entra.
- [ ] `tests/Feature/Closure/MetaScreenTest.php` / `PassiveScreenTest.php` — asserts básicos de que a tela lista as Ordens certas, agrupadas por Nota.
- [ ] Teste de gate: usuário sem `closure_manager`/`admin`/`superadm` recebe 403 nas rotas do módulo.

## 9. Critérios de aceite (validar contra o banco de DEV)

Números já confirmados nesta sessão (2026-08-30) — usar como oráculo manual antes de considerar a Fase 1 pronta:

- [ ] Rodando o comando de congelamento contra os dados atuais, a quantidade de Ordens elegíveis para a meta bate com **1.216** (statusSist `LIB%` + OP20 `CONF%` + `fimReal` preenchido, ainda sem `ClosureTarget`).
- [ ] Nenhuma Ordem com `statusSist` `ABER%` ou `BLOQ%` aparece na meta.
- [ ] Rodar o comando duas vezes seguidas no mesmo mês não altera a contagem de `closure_targets` na segunda execução.
- [ ] Tela de Passivo, com uma competência antiga forçada em ambiente de teste, mostra só Ordens cujo `ClosureTarget.closure_cycle_id` é anterior à competência corrente e que ainda não fecharam.

## 10. Fora de escopo desta fase (não implementar agora)

- Assunção de Ordens, fila "Ordens Disponíveis", Minha Carteira (Fase 2).
- Localização (`current_location_*`), estados `WAITING`/`READY`/`CANCELLED` (Fase 3).
- Pendências, `closure_issues`, fluxo colaborador↔parceira (Fase 4).
- Job de detecção automática de `statusSist` no cron (Fase 6) — nesta fase, `statusSist` é sempre lido ao vivo de `orders`, nunca cacheado.
- Notificações (`SystemNotification`) — Fase 6.

---

## Ordem de execução sugerida

1. Migrations (§1) → 2. Models (§2) → 3. Comando de congelamento em modo dry-run (§3) → 4. Validar números contra DEV (§9, antes de seguir) → 5. Gates + admin UI (§4) → 6. Telas Livewire (§5) → 7. Rotas/controller (§6) → 8. Testes (§8) → 9. Menu (§7, só com confirmação) → 10. Rodar congelamento "de verdade" (`--freeze`) na competência corrente.
