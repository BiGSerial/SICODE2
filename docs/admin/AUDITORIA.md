# Auditoria de notas

Tela: Administração → Dados → Auditoria de notas (`Admin\Audits\NoteAudit`, rota `admin.audits.notes`). É uma lista de controle no padrão de [PADRAO_LISTAS_OPERACIONAIS.md](../PADRAO_LISTAS_OPERACIONAIS.md) (§10).

## Por que era lenta

`audits` guarda o snapshot completo do model em JSON (`before`/`after`). Só havia o índice da PK, então cada abertura da tela lia a tabela inteira (1,25 mi de linhas, ~2 GB): ~7 s para ordenar por data, ~10 s para filtrar por nota dentro do JSON e ~7 s para listar os modelos.

## Como é agora

- **Colunas indexadas extraídas do JSON** (`App\Services\Audit\AuditPayload::context`): `note_id`, `service_id`, `record_id`, `changes_count`, `changed_fields` (`,status,user_id,`). Preenchidas na gravação (`Audit::creating`, vale para qualquer escritor: observer, `HierarchyService`…) e, para o histórico, pelo comando abaixo. **O JSON continua sendo a fonte de verdade**; a tela só lê o JSON das linhas da página.
- **Índices** (`created_at`, e `created_at` combinado com nota, atividade, registro, usuário e modelo). Toda consulta da tela usa só essas colunas.
- **Período padrão: 7 dias** (Hoje · 7 dias · 30 dias · Tudo · Período). Busca por **nota/OV** ou **ID do registro** ignora o período e olha todo o histórico.
- **O que mudou** aparece na própria linha (`Status: Atribuído (2) → Em Andamento (3)`); o modal mostra a diferença completa. A diferença compara pelo texto normalizado: `5` x `"5"`, `0` x `false` e `null` x `""` não contam como mudança (antes inflavam o "Mudanças").
- Filtros pelo `WithListFilters` (na URL): Ação, Modelo, Atividade, Usuário e **Campo alterado** (usa `changed_fields`).
- Rótulos (usuário, empresa, atividade, nota) resolvidos em lote para a página (`AuditPresenter`).

## Implantação (ordem importa)

1. `php artisan migrate` — adiciona 5 colunas e 6 índices numa tabela de ~2 GB; o MySQL faz isso online, mas leva alguns minutos. Fora do pico.
2. `php artisan audits:backfill-context` — preenche nota/atividade/registro do histórico por SQL em lotes (~5 min para 1,25 mi). Retomável.
3. `php artisan audits:backfill-context --changes` — opcional e mais lento (PHP): preenche `changes_count`/`changed_fields` do histórico para o filtro "Campo alterado" valer também nos registros antigos. Auditorias novas já nascem completas.

Até o passo 2, o botão de histórico das listas (`Audits\Info`, que agora busca por `record_id` + `service_id`) e a busca por nota não encontram os registros antigos.

## Medição (300 mil linhas reais, banco de teste)

| Consulta | Antes | Depois |
|---|---|---|
| Abrir a tela (7 dias, 50 linhas) | ~11 s | 8 ms |
| Busca por nota | ~16 s | 2 ms |
| Tudo: página + total | ~11 s | 58 ms |
| Opções de modelo | ~7 s (a cada abertura) | 0,4 s, em cache de 10 min |
