# Fase 1 — Checklist de Implementação

> Módulo: Controle Operacional de Encerramento. Escopo completo em [PLANEJAMENTO_CONTROLE_OPERACIONAL_ENCERRAMENTO.md](PLANEJAMENTO_CONTROLE_OPERACIONAL_ENCERRAMENTO.md).
>
> **Objetivo da Fase 1:** competência mensal + meta congelada + passivo, todos **somente leitura**, mais uma primeira versão (parcial) do Detalhe da Ordem. Nenhuma assunção, nenhuma pendência, nenhuma automação ainda.
>
> **Status: implementação em andamento (2026-08-31).** Migrations já rodadas em DEV; falta rodar o backfill/congelamento "de verdade" (`--freeze`) e os testes automatizados.

---

## 0. Pré-requisitos (já resolvidos na Fase 0 — não reabrir)

- [x] Regra de encerramento: `orders.statusSist LIKE 'ENTE%' OR LIKE 'ENCE%'`.
- [x] Regra de entrada na meta: `orders.statusSist LIKE 'LIB%'` **E** `Operation(operacao='0020')` com `status LIKE 'CONF%'` **E** `fimReal IS NOT NULL`.
- [x] Mapa de tipos de Ordem: `200→OV`, `150/170/190→EP` (`180` não existe na base hoje).
- [x] Cancelamento: Ordem cancelada sai de meta/passivo ativo, mantém histórico.

---

## 1. Migrations — ✅ CONCLUÍDO (rodadas em DEV)

- [x] `2026_08_31_120000_create_closure_cycles_table.php`
- [x] `2026_08_31_120100_create_closure_targets_table.php`
- [x] `2026_08_31_120200_add_closure_permissions_to_users_table.php` (`closure_operator`, `closure_manager` — as duas colunas criadas juntas, seed `superadm=true` já recebe ambas)

## 2. Models — ✅ CONCLUÍDO

- [x] `App\Models\ClosureCycle` (com `periodKey()`/`currentPeriodKey()` para comparar competências)
- [x] `App\Models\ClosureTarget`
- [x] `App\Models\Order::ClosureTarget()` (hasOne)
- [x] `App\Models\Note::ClosureTargets()` (hasMany)

## 3. Congelamento da meta — ✅ CÓDIGO PRONTO, ⏳ FALTA RODAR `--freeze` DE VERDADE

- [x] `App\Services\Closure\ClosureTargetFreezer` — serviço central com a regra de elegibilidade e o método `freeze(year, month, commit, frozenBy)`, reutilizado pelos dois comandos abaixo.
- [x] `App\Console\Commands\Closure\FreezeTarget` (`closure:freeze-target {competencia?} {--freeze} {--by=}`) — uso **mensal recorrente**, congela UMA competência (padrão: mês corrente).
- [x] `App\Console\Commands\Closure\BackfillTargets` (`closure:backfill-targets {--freeze} {--by=} {--until=}`) — **achado durante a implementação, não previsto originalmente**: como cada congelamento mensal só olha o mês imediatamente anterior, Ordens elegíveis mais antigas (histórico pré-módulo) nunca entrariam em nenhuma meta. Este comando descobre **todos os meses históricos** com Ordens elegíveis pendentes (via `operations.fimReal`) e congela uma competência retroativa para cada um, respeitando a mensalização correta (fimReal no mês M → meta do mês M+1). Uso único, no dia em que o módulo entra em operação.
- [x] Validado em dry-run contra o banco de DEV (ver §9 — números corrigidos).
- [ ] **Rodar de verdade** (`--freeze`) em DEV: primeiro `closure:backfill-targets --freeze`, depois `closure:freeze-target 2026-09 --freeze`. **Aguardando confirmação explícita antes de gravar dados reais.**
- [ ] Registrar quem rodou (`--by=<user_id>`) quando for além do ambiente local.

## 4. Gates / permissões — ✅ CONCLUÍDO

- [x] `app/Providers/AuthServiceProvider.php`: gates `closure.manager` e `closure.view` (padrão idêntico ao bloco `legal.*`).
- [x] `app/Http/Livewire/Admin/User/Actions/Usuario.php` + blade: `closure_operator`/`closure_manager` no `$rules`, `LOCKABLE_PERMISSIONS` e checkboxes (bloco "Módulo Encerramento", mesmo padrão visual do bloco Jurídico).
- [ ] Conceder `closure_manager=true` para os usuários que vão validar a Fase 1 em DEV (além de `superadm`, que já recebeu via seed da migration).

## 5. Livewire — telas (somente leitura) — ✅ CONCLUÍDO

- [x] `App\Http\Livewire\Closure\Cycles\Overview` — meta do mês (total/encerradas/em aberto) + passivo acumulado.
- [x] `App\Http\Livewire\Closure\Cycles\Meta` — lista `ClosureTarget` da competência selecionada (dropdown), agrupada por Nota.
- [x] `App\Http\Livewire\Closure\Cycles\Passive` — `ClosureTarget` de competências anteriores ainda não encerradas, com aging.
- [x] `App\Http\Livewire\Closure\Orders\Detail` — v1 parcial: `statusSist`, situação (ENCERRADA/PASSIVO/NA META ATUAL/FORA DA META), competência original, aging. Nota explícita na tela avisando que responsável/localização/pendências são de fases futuras.
- [x] Gate check em cada componente via `abort_unless(auth()->user()->can(...), 403)` no `mount()`.

## 6. Controller + rotas — ✅ CONCLUÍDO

- [x] `App\Http\Controllers\ClosureController` (overview/meta/passive/orderDetail).
- [x] Rotas em `routes/web.php`, prefixo `/encerramento`, gates `closure.manager`/`closure.view` no middleware.

## 7. Menu — ⏳ PENDENTE (decisão não bloqueante)

- [ ] Ainda não alterado `resources/views/layouts/menu_itens.blade.php`. Aguardando confirmação explícita do usuário antes de mexer (arquivo compartilhado por todo o sistema) — por ora as telas só são acessíveis por URL direta (`/encerramento`).

## 8. Testes (Pest) — ⏳ PENDENTE

- [ ] `tests/Feature/Closure/ClosureTargetFreezerTest.php` (testar o serviço diretamente, não só via comando):
  - Ordem elegível (LIB + OP20 CONF + fimReal no mês) entra na meta.
  - Ordem com `statusSist` `ABER%`/`BLOQ%` **não** entra.
  - Ordem com OP20 `fimReal` preenchido mas status `CNPA%` (não `CONF%`) **não** entra.
  - Rodar `freeze()` 2x na mesma competência não duplica `ClosureTarget` (idempotência) e retorna `already_frozen=true` na segunda vez.
  - Ordem cancelada (`orders.canceled=true`) ou de Nota totalmente cancelada não entra.
- [ ] `tests/Feature/Closure/BackfillTargetsCommandTest.php` — descobre corretamente os meses pendentes e não reprocessa Ordens já com `ClosureTarget`.
- [ ] Teste de gate: usuário sem `closure_manager`/`closure_operator`/`admin`/`superadm` recebe 403 nas rotas do módulo.

## 9. Critérios de aceite (validado contra o banco de DEV em 2026-08-31)

> **Números corrigidos nesta etapa.** A Fase 0 havia estimado **1.216** Ordens elegíveis via uma consulta exploratória simples (sem excluir Ordens/Notas canceladas). Ao implementar o serviço de verdade — que aplica `orders.canceled=false` e `Note::excludeCanceledFullDone()`, como o plano sempre previu — o número correto é **1.103** (210 via backfill histórico + 893 na competência corrente). A diferença (113) são Ordens canceladas ou de Notas totalmente canceladas, corretamente excluídas.

- [x] Dry-run de `closure:freeze-target 2026-09`: **893** Ordens elegíveis (fimReal em agosto/2026).
- [x] Dry-run de `closure:backfill-targets`: descobre **16 competências históricas** (jun/2024 a ago/2026, cada uma com 1 a 151 Ordens), somando **210** Ordens.
- [ ] Após `--freeze` real: conferir que `closure_targets` tem exatamente 1.103 linhas e nenhuma Ordem `ABER%`/`BLOQ%` aparece.
- [ ] Rodar `closure:freeze-target 2026-09 --freeze` duas vezes seguidas não altera a contagem na segunda execução (retorna erro "já está congelada").
- [ ] Tela de Passivo mostra as Ordens das 16 competências históricas (após backfill), com aging correto.

## 10. Achados durante a implementação (não previstos no checklist original)

- **Comando de backfill** (`closure:backfill-targets`) — necessário porque o congelamento mensal, por natureza, só olha um mês para trás; sem ele, ~210 Ordens históricas nunca entrariam em nenhuma meta. Ver §3.
- **Recomendação de agendamento (para a Fase 6, quando o cron for automatizado): rodar o congelamento mensal no dia 2, não no dia 1.** Motivo levantado pelo usuário: o sync do SAP (`sicode:upd_baseOrder`/`upd_baseOperation`) pode ter um dia de atraso para refletir uma Ordem que fechou a OP20 no último dia do mês — rodar exatamente à meia-noite da virada arriscaria deixar essas Ordens de fora da competência certa. Registrado aqui para não se perder até a Fase 6.

## 11. Fora de escopo desta fase (não implementar agora)

- Assunção de Ordens, fila "Ordens Disponíveis", Minha Carteira (Fase 2).
- Localização (`current_location_*`), estados `WAITING`/`READY`/`CANCELLED` (Fase 3).
- Pendências, `closure_issues`, fluxo colaborador↔parceira (Fase 4).
- Job de detecção automática de `statusSist` no cron (Fase 6) — nesta fase, `statusSist` é sempre lido ao vivo de `orders`, nunca cacheado.
- Notificações (`SystemNotification`) — Fase 6.

---

## Ordem de execução restante

1. ~~Migrations~~ → ~~Models~~ → ~~Serviço + comandos (dry-run)~~ → ~~Gates + admin UI~~ → ~~Telas Livewire~~ → ~~Rotas/controller~~ (tudo já feito)
2. **Confirmar com o usuário e rodar `--freeze` de verdade** (backfill primeiro, depois a competência corrente) — próximo passo.
3. Testes automatizados (§8).
4. Menu (§7, só com confirmação).
