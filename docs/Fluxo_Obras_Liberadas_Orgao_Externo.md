# Fluxo de Obras Liberadas para Orgao Externo

## Objetivo

Controlar projetos de Desenho que dependem de aprovacao de Orgao Externo antes de seguirem para analise/contratacao, evitando que obras ainda pendentes sejam tratadas indevidamente nas listas operacionais.

## Separacao dos dados

- `notes.doe`: indicador de negocio. Informa se a nota depende de Orgao Externo.
- `external_organ_releases`: controle operacional da fila Obras Liberadas. Registra a pendencia criada pelo encerramento do Desenho, a exportacao e a confirmacao automatica de saida para `20` ou `11`.

Essa separacao evita que uma marcacao manual antiga em `notes.doe` bloqueie listas sem uma pendencia operacional criada pelo fluxo.

## Entrada no fluxo

No encerramento do formulario de Desenho, o projetista deve preencher `Depende de Orgao Externo?`.
Somente OVs (`notes.type_note = 2`) entram na lista Obras Liberadas.

O Levantamento pode continuar atualizando `notes.doe`, mas nao cria pendencia em `external_organ_releases` e nao envia obra para a lista Obras Liberadas. A entrada operacional dessa lista nasce apenas no Desenho.
Notas EP (`notes.type_note = 1`) nao entram nesse fluxo operacional.

- `Nao`: grava `notes.doe = false` e nao cria pendencia operacional.
- `Sim`: grava `notes.doe = true` e cria/atualiza `external_organ_releases` para a combinacao `note_id + production_id`.

No Desenho, o campo e validado no componente Livewire antes da confirmacao de encerramento. Para notas que ja possuem `doe = true`, o formulario abre com `Sim` selecionado. Para `doe = false`, o usuario deve escolher explicitamente.

Quando um encerramento grava `Nao`, pendencias ainda nao exportadas e ainda nao liberadas da nota sao removidas. Pendencias ja exportadas permanecem como historico operacional.

## Lista Obras Liberadas

A lista exibe pendencias de `external_organ_releases` conforme a visao selecionada.

- `Novas`: pendencias sem `exported_at`, sem `released_at` e com a nota em `nstats` `47`, `48`, `49` ou `50`.
- `Exportadas aguardando 20/11`: pendencias com `exported_at` preenchido e `released_at` vazio.
- `Liberadas`: pendencias com `released_at` preenchido.
- `Todas pendentes`: pendencias com `released_at` vazio.

O badge do menu conta apenas as pendencias novas:

```text
released_at IS NULL
exported_at IS NULL
note.nstats IN (47, 48, 49, 50)
```

## Exportacao

A exportacao da lista usa job na fila `exports`.

Ao gerar o arquivo com sucesso, o job preenche:

- `exported_at = now()`
- `exported_by = usuario solicitante`

Exportar nao libera a obra e nao remove o bloqueio operacional.

## Bloqueio das listas

O bloqueio e aplicado por scope em `Note`.

Analise de Projeto e Responsavel:

```text
existe external_organ_releases pendente
released_at IS NULL
note.nstats IN (47, 48, 49, 50)
```

Contratacao:

```text
existe external_organ_releases pendente
released_at IS NULL
note.nstats IN (47, 48, 49, 50, 51)
```

O status `51` nao entra na fila Obras Liberadas; ele e usado apenas como protecao adicional na contratacao.

## Fechamento automatico

O comando `sicode:external-organ-releases:close` roda de hora em hora pelo scheduler.

Ele fecha pendencias quando a nota chega em `nstats` `20` ou `11`, preenchendo:

- `released_at = notes.dt_status`
- `release_dt_status = notes.dt_status`
- `release_detected_at = now()`
- `release_nstats = notes.nstats`
- `released_by = null`

`released_at` representa a data real do status na origem. `release_detected_at` representa quando o SICODE detectou e fechou a pendencia.

## Saida para o fluxo padrao de OE

Quando a base atualizar a nota para `20` ou `11`, ela deixa de atender ao bloqueio das listas de obra e passa a ficar disponivel no fluxo normal de Orgao Externo, em `A Protocolar`, respeitando as regras ja existentes do modulo.
