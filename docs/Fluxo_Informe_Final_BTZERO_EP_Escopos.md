# Fluxo de Informe Final BTZERO EP por Escopo

## Objetivo

O fluxo de informe final de obras passa a suportar mais de um encerramento operacional para a mesma nota EP (`notes.type_note = 1`) quando o ruleset da unidade exigir essa separacao.

O caso inicial e SP, onde informes finais BTZERO EP podem conter ordens de rede e de ligacao no mesmo informe/base. ES continua no comportamento historico ate execucao planejada do retrofill e ativacao do ruleset.

## Ativacao por unidade

A ativacao nao deve ser feita por inferencia dinamica de UF em cada registro. O projeto usa ruleset por deployment/unidade:

- `config/sicode.php`
- `App\Support\SicodeRules`
- variavel `SICODE_RULESET`

Regra atual:

- `SICODE_RULESET=es`: `work_report.split_btzero_ep_final_flows = false`
- `SICODE_RULESET=sp`: `work_report.split_btzero_ep_final_flows = true`

Qualquer regra futura de unidade deve entrar em `SicodeRules` e em `config/sicode.php`, mantendo o comportamento historico como default.

## Escopos

Os escopos canonicos sao:

- `general`: comportamento historico, usado quando o split esta desligado ou a nota nao e EP.
- `network`: encerramento de rede.
- `connection`: encerramento de ligacao.

Regra operacional:

- Rede: `150`, `170`, `190`
- Ligacao: qualquer ordem que nao comece com `150`, `170` ou `190`

A ordem especifica de desligamento ainda nao esta consolidada. Por isso, somente os prefixos canonicos de rede classificam `network`; todo o restante em BTZERO EP com split ativo e tratado como `connection`.

A classificacao fica em `App\Services\WorkReports\WorkReportFinalScopeResolver`.

## Indicacao visual

O usuario nao deve precisar inferir o escopo olhando manualmente as ordens.

Implementado no Partner:

- busca/criacao de informe: mostra coluna `Escopo` na selecao da obra;
- formulario de informe: mostra `Escopo do informe final` com destaque conforme as ordens selecionadas;
- quando o formulario detecta `network` e `connection`, o Partner precisa selecionar `Rede`, `Ligacao` ou `Ambos`;
- ao selecionar `Rede`, o formulario mantem/preenche somente ordens `150`, `170`, `190`;
- ao selecionar `Ligacao`, o formulario mantem/preenche somente ordens fora dos prefixos de rede;
- ao selecionar `Ambos`, o formulario restaura todas as ordens elegiveis da nota;
- lista de obras informadas: mostra coluna `Escopo`;
- modal de detalhe do informe: mostra `Escopo` no resumo e tambem nos vinculos de producao.

Os badges sao derivados do resolvedor central e das ordens. Quando ha mais de um escopo, a selecao do Partner e persistida em `work_reports.selected_final_scopes` para que o sync materialize apenas o encerramento escolhido.

## Materializacao

O ciclo consolidado fica em `note_inform_flows`.

Campos adicionados:

- `final_scope`: `general`, `network` ou `connection`.
- `final_scope_resolution`: origem da classificacao, como `legacy_general`, `order_prefix_match`, `non_network_order_prefix` ou `no_matching_btzero_ep_order_prefix`.
- `final_scope_orders`: ordens que justificaram o escopo.
- `publication_required`: se o escopo deve passar por publicacao.
- `publication_policy`: `required`, `skipped_by_scope` ou `not_applicable_partial`.

Chaves:

- Legado/general: `flow_key = work_report:{id}`
- Rede: `flow_key = work_report:{id}:network`
- Ligacao: `flow_key = work_report:{id}:connection`

Isso permite que dois encerramentos do mesmo `work_report_id` existam sem ambiguidade.

Quando `work_reports.selected_final_scopes` estiver preenchido, o sincronizador usa essa escolha para filtrar os payloads detectados. Exemplos:

- `['network']`: materializa apenas Rede.
- `['connection']`: materializa apenas Ligacao.
- `['network', 'connection']`: materializa ambos.
- `null`: comportamento legado por deteccao automatica.

## Publicacao

Somente `network` libera publicacao no fluxo BTZERO EP separado.

Regra:

- `network`: `publication_required = true`, `publication_policy = required`
- `connection`: `publication_required = false`, `publication_policy = skipped_by_scope`
- `general`: preserva comportamento historico

Consultas de fila de publicacao devem filtrar explicitamente:

```sql
flow_type = 'final'
AND final_scope IN ('general', 'network')
AND publication_required = true
AND active = true
```

Nunca usar apenas existencia de informe final para liberar publicacao de BTZERO EP. Somente escopos com ordem de rede canonica (`150`, `170`, `190`) devem liberar o pessoal de publicacao.

Implementado:

- `App\Repositories\PublishRepository` bloqueia, no ruleset SP, EP sem ordem de rede no `WorkForm`.
- `App\Services\Publication\NoteFilter` aplica a mesma regra na tela de servico de publicacao.

## Fiscalizacao e medicao

Fiscalizacao e medicao devem trabalhar contra o escopo, nao apenas contra a nota ou o `work_report_id`.

Quando uma nota/informe tiver mais de um escopo aberto, a tela deve permitir selecionar:

- Rede
- Ligacao
- Rede + Ligacao

Persistencia recomendada:

- criar um vinculo por escopo em `work_report_flow_productions`;
- quando o usuario selecionar "Rede + Ligacao", gravar dois vinculos, um `network` e um `connection`;
- nao gravar um valor textual "ambos" como estado final.

Campos relevantes em `work_report_flow_productions`:

- `work_report_id`
- `production_id`
- `stage`: `fiscalization` ou `payment`
- `final_scope`
- `is_current`
- `linked_at`
- `linked_by`
- `source`
- `metadata`

A unicidade passa a considerar o escopo:

```text
work_report_id + production_id + stage + final_scope
```

Implementado para fiscalizacao:

- `resources/views/livewire/dispatchs/partials/dispatch-modal.blade.php` mostra o escopo por nota no modal compartilhado.
- a selecao de escopo no modal de despacho foi destacada com aviso de escolha exata para fiscalizacao;
- `resources/views/livewire/services/supervision/main.blade.php` mostra coluna `Escopo` na lista de atividades atribuidas;
- `resources/views/livewire/services/supervision/forms/jobform.blade.php` mostra `Escopo fiscalizado` no resumo do formulario de execucao;
- quando ha apenas um escopo, ele fica pre-selecionado;
- quando ha `network` e `connection`, o usuario precisa marcar um ou ambos;
- `App\Services\Dispatch\DispatchWorkflowService` grava um vinculo por escopo selecionado.

Implementado para medicao:

- `resources/views/livewire/dispatchs/payment/main.blade.php` mostra coluna `Escopo` na lista de despacho para medicao.
- `resources/views/livewire/dispatchs/payment/stack.blade.php` mostra coluna `Escopo` na pilha de medicao.
- `resources/views/livewire/dispatchs/payment/main.blade.php` mostra o seletor no modal de despacho de pagamento.
- `resources/views/livewire/dispatchs/payment/stack.blade.php` mostra o seletor no modal da pilha de pagamento.
- os modais de despacho de pagamento destacam a selecao quando ha mais de um escopo e exibem aviso de selecao exata.
- `resources/views/livewire/services/payment/main.blade.php` mostra coluna `Escopo` na lista do executor de medicao.
- `resources/views/livewire/services/payment/forms/jobform.blade.php` mostra `Escopo medido` no resumo do formulario de execucao.
- `App\Http\Livewire\Dispatchs\Payment\Main` e `Stack` validam que notas com mais de um escopo tenham selecao explicita.
- quando ha apenas um escopo disponivel, o vinculo e gravado nesse escopo.
- autoatribuicao em `App\Http\Livewire\Services\Payment\Main` bloqueia notas com mais de um escopo e orienta usar a tela de despacho, porque nao ha seletor nessa tela.

Implementado para publicacao:

- `resources/views/livewire/services/publication/main.blade.php` mostra coluna `Escopo` na fila de publicacao.
- `resources/views/livewire/services/publication/accompany/main.blade.php` mostra coluna `Escopo` no acompanhamento de publicacao.
- `resources/views/livewire/services/publication/forms/jobform.blade.php` mostra `Escopo` no resumo do formulario de execucao.
- badges de rede em publicacao exibem `Publicavel` para reforcar que ligacao nao segue esse canal.

## Inferencia legada

Para confiabilidade, o sync nao deve aplicar inferencia legada por data em escopos separados.

Regra:

- `general`: pode usar inferencia legada por janela do informe.
- `network`/`connection`: so considera producao se houver vinculo explicito no mesmo `final_scope`.

Isso evita o erro critico de uma mesma producao antiga ser automaticamente atribuida aos dois encerramentos quando rede e ligacao existem ao mesmo tempo.

## Retrofill

### SP

Fluxo recomendado:

1. Rodar migracoes.
2. Rodar `sicode:sync-note-inform-flows --flow_type=final --all --dry`.
3. Validar contagens por `final_scope`.
4. Validar casos sem ordem associada, pois eles permanecem como `general` por falta de evidencia para classificar.
5. Executar retrofill de vinculos por escopo somente quando houver regra objetiva para decidir se a producao pertence a rede, ligacao ou ambos.
6. Rodar sync final sem `--dry`.
7. Validar filas de publicacao, fiscalizacao e medicao.

### ES

ES nao deve ativar o split junto com o retrofill.

Fluxo recomendado:

1. Manter `SICODE_RULESET=es`.
2. Rodar relatorio de impacto usando o resolvedor em modo simulado.
3. Medir quantos informes seriam `network`, `connection`, ambos ou sem ordem para classificacao.
4. Corrigir dados ambiguos antes da ativacao.
5. Alterar ruleset/deployment somente depois da base saneada.
6. Rodar sync completo.

## Reversao

Toda reversao deve ser por escopo.

Casos:

- Reverter publicacao: permitido para `network` e `general`; nao se aplica a `connection`.
- Reverter fiscalizacao: inativar apenas o vinculo `work_report_flow_productions` do escopo selecionado.
- Reverter medicao: inativar apenas o vinculo de pagamento do escopo selecionado.
- Reverter informe inteiro: inativar todos os escopos ativos do `work_report_id`.

Nao apagar historico operacional. Usar:

- `is_current = false`
- `reversed_at`
- `reversed_by`
- `reverse_reason`

Depois de qualquer reversao, recalcular `note_inform_flows` apenas para a nota afetada.

Servico disponivel:

- `App\Services\WorkReports\WorkReportFlowProductionReversalService::reverseScope(...)`
- `App\Services\WorkReports\WorkReportFlowProductionReversalService::reverseProduction(...)`

As chamadas inativam apenas vinculos `is_current = true` do escopo informado e registram usuario/motivo/data. A rotina chamadora deve executar `sicode:sync-note-inform-flows --note_id={id}` depois da reversao.

## Invariantes obrigatorios

- `connection` nunca deve ter `publication_required = true`.
- `connection` nunca deve aparecer na pilha de publicacao.
- `network` e `connection` nao podem compartilhar producao por inferencia automatica.
- se fiscalizacao/medicao selecionar "Rede + Ligacao", devem existir dois vinculos explicitos.
- `flow_key` deve ser idempotente e estavel.
- regras por unidade devem passar por `SicodeRules`, nao por `SystemSetting` avulso.
- casos com ordem fora dos prefixos canonicos de rede devem ser tratados como `connection`.
- casos sem nenhuma ordem associada devem ficar visiveis para saneamento.

## Pontos de implementacao pendentes

- Criar comando/relatorio de impacto para o retrofill ES.
- Expandir sincronizacao SQL Server de `note_inform_flows` caso o destino precise receber os novos campos.
