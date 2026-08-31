# Fluxo de Despacho Compartilhado Sem DD

## Objetivo

Padronizar as etapas que nao possuem DD, como Desenho, Analise, Pre Analise e Fluxo Reverso, usando o mesmo fluxo robusto de despacho ja aplicado em Fiscalizacao, Levantamento e Pagamento.

O padrao operacional agora e: lista com o mesmo layout base, bloqueio centralizado, modal compartilhado e despacho pelo `DispatchWorkflowService`.

## Regra funcional

- O usuario pode despachar em massa ou individualmente pela mesma lista.
- O modal deve ser o componente compartilhado `dispatchs.shared.dispatch-modal`.
- Se a atividade nao exige DD, o modal nao exibe coluna nem campo de DD.
- Se a atividade exige DD, a exigencia continua sendo definida por regra central em `SicodeRules` e validada no backend.
- Se a atividade nao exige escopo final, o modal nao exibe selecao de escopo mesmo que a nota tenha informe final.
- A selecao de escopo final e restrita a Fiscalizacao e Pagamento, onde a producao precisa marcar exatamente Rede, Ligacao ou Ambos quando a nota tiver mais de um escopo disponivel.
- O comportamento de Pilha e Individual e o mesmo das demais telas:
  - usuario interno pode despachar para pilha da empresa ou atribuir direto para usuario;
  - usuario de contrato nao envia para pilha, apenas atribui itens ja abertos na pilha da empresa permitida.

## Bloqueios

O bloqueio exibido na lista deve bater com a validacao final do despacho.

Para Desenho, Analise, Pre Analise e Fluxo Reverso, a decisao usa `App\Services\Design\BlockEvaluator`:

- sem producao anterior: libera;
- producao anterior finalizada e confirmada em status/data diferente: libera novo ciclo;
- producao finalizada e confirmada no mesmo status/data: aparece como retorno liberavel;
- producao finalizada e ainda nao confirmada no mesmo status/data: bloqueia;
- producao em pilha sem usuario: bloqueia para usuario interno, mas aparece para usuario contrato da empresa como item atribuivel;
- producao individual aberta: bloqueia.

## Pontos de implementacao

- `DispatchContextResolver` deve declarar `requires_dd = false` para servicos sem regra explicita de DD.
- `DispatchContextResolver` deve aplicar o avaliador correto do servico em `can_dispatch`, para impedir bypass por chamada direta ao servico.
- `DispatchContextResolver` mapeia `pagamento` para `payment` e usa `App\Services\Payment\BlockEvaluator` nesse fluxo.
- A lista deve aplicar `SicodeRules::applyContractDispatchMainVisibility` para usuarios contrato.
- A lista deve considerar `SicodeRules::openCompanyStackProductionFor` antes de bloquear o despacho individual de usuario contrato.
- A abertura do modal deve emitir `openForNotes` para `dispatchs.shared.dispatch-modal`.
- Nao criar modais locais por servico quando o comportamento for o mesmo do despacho compartilhado.
- O layout comum fica em `resources/views/livewire/dispatchs/partials/list-shell-style.blade.php`; cada atividade preserva suas colunas e dados proprios.

## Garantias esperadas

- Sem ambiguidade visual: se nao existe DD na etapa, nao aparece campo de DD.
- Sem divergencia entre tela e backend: item bloqueado na lista tambem e bloqueado pelo `DispatchWorkflowService`.
- Mesmo comportamento para despacho em massa e individual.
- Mesmo comportamento para usuarios internos e contratos em todas as etapas padronizadas.
