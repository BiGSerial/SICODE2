# Etapa 05 - Arquivos, documentos e visibilidade

## Objetivo

Permitir anexar documentos, evidências, respostas e arquivos estratégicos às demandas, com controle de visibilidade.

## Problema

Nem todo arquivo deve ser visto por todos.

Exemplos:

- decisão judicial pode ser interna;
- evidência técnica pode ser compartilhada com controlador;
- documento final pode ser marcado como pronto para envio externo;
- comentário estratégico pode ficar apenas para controladores;
- arquivo de resposta da ponta pode ser visível para equipe e controlador.

## Modelo recomendado

Usar tabela pivô `legal_demand_files`.

Campos:

```txt
id
legal_demand_id
assignment_id
file_id
uploaded_by_user_id
category
visibility
can_be_sent_external
is_evidence
is_final_response
created_at
updated_at
```

## Categorias de arquivo

```txt
legal_document
technical_evidence
field_return
controller_note
external_protocol
final_response
other
```

## Visibilidades

```txt
controller_only
assigned_user_only
internal_all
legal_area
external_ready
```

## Regras de visibilidade

### `controller_only`

Somente controladores e perfis autorizados podem ver.

Uso:

- estratégia interna;
- observação sensível;
- documento ainda não validado.

### `assigned_user_only`

Somente o usuário/equipe designado e controlador podem ver.

Uso:

- arquivo específico de uma tratativa;
- retorno parcial da ponta.

### `internal_all`

Usuários internos envolvidos podem ver.

Uso:

- documentos operacionais comuns;
- instruções gerais.

### `legal_area`

Visível para controlador e área jurídica, se existir integração/permissão.

Uso:

- resposta final;
- documento de suporte ao jurídico.

### `external_ready`

Arquivo pronto para ser usado no encerramento externo.

Uso:

- resposta consolidada;
- evidência final aprovada;
- protocolo.

## Regras de upload

Ao anexar arquivo:

1. Validar permissão.
2. Validar demanda não cancelada/encerrada, exceto se usuário tiver permissão administrativa.
3. Criar registro na tabela de arquivos original do SICODE, se aplicável.
4. Criar vínculo em `legal_demand_files`.
5. Registrar evento `file_attached`.
6. Registrar categoria e visibilidade.
7. Se for evidência, marcar `is_evidence = true`.
8. Se for documento final, marcar `is_final_response = true`.

## Regras de alteração de visibilidade

Alteração de visibilidade deve gerar evento.

Evento sugerido:

```txt
file_visibility_changed
```

Metadata:

```json
{
  "file_id": 123,
  "old_visibility": "controller_only",
  "new_visibility": "external_ready"
}
```

## Regras para exclusão

Preferencialmente não excluir fisicamente.

Usar uma das opções:

1. Soft delete no arquivo;
2. Campo `removed_at` na pivô;
3. Evento `file_removed`.

A exclusão física só deve acontecer por rotina administrativa controlada.

## Comentários

Criar `legal_demand_comments`.

Campos:

```txt
id
legal_demand_id
assignment_id
user_id
comment
visibility
created_at
updated_at
```

Visibilidades de comentário:

```txt
controller_only
assigned_user_only
internal_all
```

## Checklist da etapa

- [ ] Criar tabela `legal_demand_files`.
- [ ] Criar tabela `legal_demand_comments`.
- [ ] Criar categorias de arquivos.
- [ ] Criar níveis de visibilidade.
- [ ] Criar upload vinculado à demanda.
- [ ] Criar upload vinculado ao assignment.
- [ ] Criar alteração de visibilidade.
- [ ] Criar remoção lógica.
- [ ] Criar comentários internos.
- [ ] Criar comentários de resposta da ponta.
- [ ] Criar evento para arquivo anexado.
- [ ] Criar evento para visibilidade alterada.
- [ ] Criar evento para arquivo removido.
- [ ] Criar filtros por categoria/visibilidade.

## Observabilidade da etapa

O agente deve entregar:

- [ ] Histórico de arquivos por demanda.
- [ ] Histórico de arquivos por assignment.
- [ ] Log de anexos incluídos/removidos.
- [ ] Evento para alteração de visibilidade.
- [ ] Teste garantindo que usuário sem permissão não vê `controller_only`.
- [ ] Teste garantindo que documento `external_ready` aparece na área de encerramento externo.
- [ ] Teste garantindo que exclusão lógica não remove histórico.
- [ ] Contador de demandas sem evidência.
- [ ] Contador de demandas com resposta final pronta.
- [ ] Contador de arquivos por categoria.
