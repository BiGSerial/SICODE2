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

## 3. Congelamento da meta — ✅ CONCLUÍDO

- [x] `App\Services\Closure\ClosureTargetFreezer` — serviço central com a regra de elegibilidade e o método `freeze(year, month, commit, frozenBy, lock)`, reutilizado pelos dois comandos abaixo.
- [x] `App\Console\Commands\Closure\FreezeTarget` (`closure:freeze-target {competencia?} {--freeze} {--lock} {--by=}`) — uso **mensal recorrente**, grava/atualiza o snapshot de UMA competência (padrão: mês corrente). **Fluxo em dois passos (corrigido pelo usuário, ver §14.2 do plano):** rodar sem `--lock` no dia 1 (snapshot inicial, competência fica `OPEN`); rodar de novo às 0:00 do dia 2 para injetar Ordens sincronizadas com atraso (a query já ignora Ordens que já têm `closure_target`, então não duplica); só então rodar com `--lock` para travar (`FROZEN`) de vez.
- [x] `App\Console\Commands\Closure\BackfillTargets` (`closure:backfill-targets {--freeze} {--by=} {--until=}`) — **achado durante a implementação, não previsto originalmente**: como cada congelamento mensal só olha o mês imediatamente anterior, Ordens elegíveis mais antigas (histórico pré-módulo) nunca entrariam em nenhuma meta. Este comando descobre **todos os meses históricos** com Ordens elegíveis pendentes (via `operations.fimReal`) e congela uma competência retroativa para cada um, respeitando a mensalização correta (fimReal no mês M → meta do mês M+1). Sempre trava (`lock=true`) na mesma passada — não existe "dia 2" para uma competência de anos atrás. Uso único, no dia em que o módulo entra em operação.
- [x] Validado em dry-run contra o banco de DEV (ver §9 — números corrigidos).
- [x] **Rodado de verdade em DEV (2026-08-31, confirmado pelo usuário)**: `closure:backfill-targets --freeze` (210 Ordens, 16 competências, todas já `FROZEN`) seguido de `closure:freeze-target 2026-09 --freeze` (893 Ordens). Total: **1.103 Ordens em 17 competências**. *Nota: a competência 2026-09 foi travada nesta mesma rodada, antes de o fluxo de dois passos existir no código — é o comportamento equivalente a já rodar com `--lock`. O fluxo de dois passos (snapshot dia 1 / injeção+trava dia 2) vale a partir da próxima competência (2026-10 em diante).*
- [x] Idempotência confirmada: rodar `closure:freeze-target 2026-09 --freeze` de novo retorna "já está travada", sem alterar nada.
- [x] Mecanismo `--lock` validado em DEV com uma competência de teste (2026-10, criada e removida logo em seguida — 0 Ordens, sem impacto nos dados reais): sem `--lock` fica `OPEN`; com `--lock` vira `FROZEN`; rodar de novo depois é recusado.
- [ ] Registrar quem rodou (`--by=<user_id>`) quando for além do ambiente local (rodado sem `--by` em DEV).

## 3.1 Caminho de exceção — casos atípicos — ✅ CONCLUÍDO

**Achado não previsto no checklist original, decisão do usuário (2026-08-31):** mesmo depois de uma competência `FROZEN`, precisa existir uma condição paralela para inserir uma Ordem como caso atípico — não automática, só por solicitação superior sob justificativa.

- [x] Migration `2026_08_31_130000_add_exception_fields_to_closure_targets_table.php`: `is_exception`, `exception_reason`, `requested_by` (FK users), `authorized_by` (FK users), `authorized_at`.
- [x] `App\Models\ClosureTarget`: constante `ENTRY_RULE_EXCEPTION`, novos campos no `$fillable`/`$casts`, relações `RequestedBy()`/`AuthorizedBy()`.
- [x] `App\Services\Closure\ClosureExceptionService::registerException()` — bypassa a trava de `FROZEN` (não usa `ClosureTargetFreezer`, é um caminho deliberadamente separado do fluxo automático). Valida: Ordem não cancelada, Ordem sem `closure_target` prévio, justificativa obrigatória, `authorized_by` obrigatório.
- [x] `App\Console\Commands\Closure\AddException` (`closure:add-exception {order} {--cycle=} {--reason=} {--authorized-by=} {--requested-by=}`).
- [x] Selo "EXCEÇÃO" nas telas de Meta e Passivo (tooltip com a justificativa); detalhe completo (justificativa, quem autorizou/solicitou, quando) na tela de Detalhe da Ordem.
- [x] Validado em DEV: recusa sem `--cycle`/`--reason`/`--authorized-by`; recusa Ordem que já tem meta; **aceita e grava mesmo contra a competência 2026-09, já `FROZEN`** (registro de teste criado e removido em seguida, sem impacto nos dados reais).

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
  - Rodar `freeze()` 2x na mesma competência sem `$lock` injeta só as Ordens novas (não duplica as já existentes) e mantém `status=OPEN`.
  - Rodar `freeze()` com `$lock=true` muda `status` para `FROZEN`; uma chamada seguinte retorna `already_frozen=true` e não altera nada.
  - Ordem cancelada (`orders.canceled=true`) ou de Nota totalmente cancelada não entra.
- [ ] `tests/Feature/Closure/BackfillTargetsCommandTest.php` — descobre corretamente os meses pendentes, não reprocessa Ordens já com `ClosureTarget`, e sempre trava (`FROZEN`) cada competência processada.
- [ ] `tests/Feature/Closure/ClosureExceptionServiceTest.php`:
  - Registra a exceção com sucesso mesmo com a competência `FROZEN` (não altera o status da competência).
  - Recusa sem justificativa (`exception_reason` vazio).
  - Recusa sem `authorized_by`.
  - Recusa Ordem cancelada.
  - Recusa Ordem que já possui `closure_target` (de qualquer origem, automática ou exceção).
- [ ] Teste de gate: usuário sem `closure_manager`/`closure_operator`/`admin`/`superadm` recebe 403 nas rotas do módulo.

## 9. Critérios de aceite (validado contra o banco de DEV em 2026-08-31)

> **Números corrigidos nesta etapa.** A Fase 0 havia estimado **1.216** Ordens elegíveis via uma consulta exploratória simples (sem excluir Ordens/Notas canceladas). Ao implementar o serviço de verdade — que aplica `orders.canceled=false` e `Note::excludeCanceledFullDone()`, como o plano sempre previu — o número correto é **1.103** (210 via backfill histórico + 893 na competência corrente). A diferença (113) são Ordens canceladas ou de Notas totalmente canceladas, corretamente excluídas.

- [x] Dry-run de `closure:freeze-target 2026-09`: **893** Ordens elegíveis (fimReal em agosto/2026).
- [x] Dry-run de `closure:backfill-targets`: descobre **16 competências históricas** (jun/2024 a ago/2026, cada uma com 1 a 151 Ordens), somando **210** Ordens.
- [x] Após `--freeze` real: `closure_targets` tem exatamente **1.103** linhas, `closure_cycles` tem **17** linhas (todas `FROZEN`), nenhuma Ordem `ABER%`/`BLOQ%` aparece (`count=0` confirmado via tinker).
- [x] Rodar `closure:freeze-target 2026-09 --freeze` duas vezes seguidas não altera a contagem na segunda execução (retorna "já está congelada", confirmado).
- [x] Lógica das telas (Overview/Meta/Passive/Detail) validada via `tinker` reproduzindo as mesmas queries: `metaTotal=893`, `metaClosed=0`, `passiveTotal=210`, agrupamento por Nota funciona (765 grupos), `Detail` classifica corretamente uma Ordem de 2024-06 como `PASSIVO`.
- [ ] **Ainda não testado visualmente em navegador** (sem credenciais de login disponíveis neste ambiente) — as queries e a lógica de negócio estão confirmadas corretas via tinker, mas o layout/blade em si (CSS, interação do `wire:model` no dropdown de competência, etc.) precisa de uma verificação visual antes de considerar a Fase 1 100% pronta para uso real.

## 10. Achados durante a implementação (não previstos no checklist original)

- **Comando de backfill** (`closure:backfill-targets`) — necessário porque o congelamento mensal, por natureza, só olha um mês para trás; sem ele, ~210 Ordens históricas nunca entrariam em nenhuma meta. Ver §3. Sempre trava (`lock=true`) na mesma passada.
- **Fluxo em dois passos para o congelamento mensal (corrigido pelo usuário, ver §14.2 do plano principal)**: `closure:freeze-target` ganhou um parâmetro/flag `--lock`. Rodar **sem** `--lock` no dia 1 grava um snapshot e deixa a competência `OPEN`; rodar de novo (sem ou com `--lock`) às 0:00 do dia 2 **injeta** só as Ordens novas (sincronizadas com atraso pelo SAP) sem duplicar nada, e o `--lock` final trava a competência (`FROZEN`) definitivamente. Validado em DEV com uma competência de teste (2026-10, depois removida): sem `--lock` fica `OPEN`; com `--lock` vira `FROZEN`; rodar de novo depois é recusado.

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
