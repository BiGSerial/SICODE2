# API de importação manual de notas, ordens e operações

## Objetivo

Esta API permite inserir ou atualizar registros que normalmente seriam carregados pelos processos:

- `sicode:upd_baseEP`
- `sicode:upd_baseov`
- `sicode:upd_baseOrder`
- `sicode:upd_baseOperation`

O processamento é idempotente: reenviar a mesma nota, ordem ou operação atualiza o registro existente e não cria duplicidades.

## Endpoint e autenticação

```http
POST /api/v1/imports/manual-records
Authorization: Bearer {TOKEN_DA_APLICACAO}
Content-Type: application/json
Accept: application/json
```

Os tokens são cadastrados no SICODE em `Configuração > Tokens de API`. O token completo é exibido somente no momento da criação.

## Diferença entre NotaEP e NotaOV

| Tipo | Origem | Identificador de origem | `type_note` |
|---|---|---|---:|
| NotaEP | `tbld_usr_baseEP` | `nota` | `1` |
| NotaOV | `tbld_usr_baseOV` | `OV` | `2` |

No payload, informe somente um dos blocos: `notaEP` ou `notaOV`. O valor de `note` será gravado em `notes.note`. O campo `type_note` é definido automaticamente e não deve ser enviado.

## Relacionamentos

Uma nota pode possuir várias ordens, e cada ordem pode possuir várias operações:

```text
notes
  └── orders
        └── operations
```

- `notaEP.note` ou `notaOV.note` identifica a nota.
- `ordens[].ordem` identifica a ordem dentro da nota.
- `ordens[].operacoes[].operacao` identifica a operação dentro da ordem.
- `note_id` e `order_id` são gerados pelo SICODE e não devem ser enviados.

## Payload de NotaOV

```json
{
  "notaOV": {
    "note": "OV-123456",
    "created_by": "SISTEMA",
    "dt_created": "2026-09-17 08:00:00",
    "dt_status": "2026-09-17 08:30:00",
    "user": "USUARIO",
    "value": 1000.50,
    "currency": "BRL",
    "eq_venda": "EQ001",
    "numPedido": "PED001",
    "client": "CLIENTE",
    "nexp": "MUN001",
    "lexp": "Vitória",
    "nstats": "70",
    "status": "ABERTO",
    "pep": "PEP001",
    "days": 10,
    "transaction": "TRANSICAO",
    "validar_prazo": "S",
    "rubrica": "RUBRICA",
    "pze_tratado": 10,
    "days_stat": 3,
    "pze_parecer": "OK",
    "days_left": 7
  },
  "ordens": [
    {
      "ordem": "4000123456",
      "descricao": "Ordem principal",
      "statusSist": "LIB",
      "statusUser": "ABERTO",
      "cenPlan": "CP001",
      "cenTrab": "CT001",
      "dtEntrada": "2026-09-17 09:00:00",
      "operacoes": [
        {
          "operacao": "0010",
          "descOperacao": "Planejamento",
          "inicioPlanejado": "2026-09-17 10:00:00",
          "fimPlanejado": "2026-09-17 12:00:00",
          "inicioReal": null,
          "fimReal": null,
          "status": "LIB",
          "notaOv": "OV-123456",
          "cenPlan": "CP001",
          "cenTrab": "CT001",
          "txtCenTrab": "Descrição do centro de trabalho"
        }
      ]
    }
  ]
}
```

## Payload de NotaEP

```json
{
  "notaEP": {
    "note": "EP-123456",
    "created_by": "SISTEMA",
    "dt_created": "2026-09-17 08:00:00",
    "dt_status": "2026-09-17 08:30:00",
    "user": "USUARIO",
    "numPedido": "PED001",
    "pze": 10,
    "num_material": 123456,
    "material": "MATERIAL",
    "nexp": "MUN001",
    "lexp": "Vitória",
    "nstats": "70",
    "status": "ABERTO",
    "rubrica": "RUBRICA",
    "centerjob": "CENTRO001",
    "mesalization": "N",
    "txpriority": "NORMAL"
  },
  "ordens": []
}
```

## Campos aceitos

### NotaEP

```text
note, created_by, dt_created, dt_status, user, numPedido,
pze, num_material, material, nexp, lexp, nstats, status,
rubrica, centerjob, mesalization, txpriority
```

### NotaOV

```text
note, created_by, dt_created, dt_status, user, value, currency,
eq_venda, numPedido, client, group1, group2, group3, group4,
group5, pze, num_material, material, nexp, lexp, pep, nstats,
status, days, transaction, validar_prazo, rubrica, pze_tratado,
days_stat, pze_parecer, days_left
```

### Ordem

```text
ordem, descricao, locInstalacao, cenPlan, prioridade, statusSist,
statusUser, cenTrab, gpm, custPlanejado, custRealizado, modifPor,
pep, conjunto, denConjunto, dtEntrada
```

### Operação

```text
operacao, descOperacao, inicioPlanejado, fimPlanejado, inicioReal,
fimReal, status, notaOv, cenPlan, cenTrab, txtCenTrab
```

Campos extras devem ser rejeitados com HTTP `422`.

## Envio em lote

O corpo pode ser um array de registros:

```json
[
  {
    "notaEP": {"note": "EP-100000", "nstats": "70", "status": "ABERTO"},
    "ordens": [
      {"ordem": "4000000001", "operacoes": []}
    ]
  },
  {
    "notaOV": {"note": "OV-200000"},
    "ordens": [
      {
        "ordem": "4000000002",
        "operacoes": [{"operacao": "0010", "status": "LIB"}]
      },
      {"ordem": "4000000003", "operacoes": []}
    ]
  }
]
```

Também é aceito o formato `{ "data": [ ... ] }`.

## Datas, números e nulos

- Datas: preferencialmente `YYYY-MM-DD HH:mm:ss`.
- Valores monetários: número decimal, por exemplo `1000.50`.
- Campos sem valor: `null` ou omitidos.
- Nota, ordem e operação: enviar como string para preservar zeros à esquerda.

## Respostas

Sucesso — HTTP `200`:

```json
{
  "message": "Importação processada com sucesso.",
  "summary": {
    "records_received": 1,
    "records_created": 1,
    "records_updated": 0,
    "operations_created": 1,
    "operations_updated": 0,
    "items": []
  }
}
```

Token inválido ou ausente — HTTP `401`:

```json
{"message": "Bearer token inválido ou ausente."}
```

Payload inválido — HTTP `422`:

```json
{
  "message": "Payload inválido.",
  "error": "Registro 0: campos não permitidos."
}
```

## Auditoria

Cada chamada é registrada em `application_api_audits` com token, usuário responsável, endpoint, método, IP, agente, horário, status HTTP, hash do payload e quantidades criadas/atualizadas.

O payload completo não é armazenado na auditoria e o token nunca é armazenado em texto puro.

