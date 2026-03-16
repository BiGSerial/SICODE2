# Sistema de Notificações (SICODE)

Este documento define o PADRÃO OFICIAL para envio de notificações ao usuário.
Use este guia como regra de implementação e revisão de PR.

## Visão geral

O sistema usa `App\Notifications\SystemNotification` com canal `database`.

- Payload padrão (canônico): `App\Support\Notifications\UserNotificationData`
- Contrato: `App\Contracts\Notifications\UserNotificationContract`
- Render da central:
  - sino: `app/Http/Livewire/Components/Notify/Notifys.php`
  - modal: `app/Http/Livewire/Components/Notify/AllNotifies.php`

## Regra obrigatória

Para código novo, use sempre `UserNotificationData`.

- Obrigatório: `title`, `message`, `status`
- Opcional: `link`, `extras`
- Não recomendado em código novo: construtor legado com 5 parâmetros soltos

## Forma padrão (obrigatória para novos códigos)

Use `UserNotificationData` para padronizar título, mensagem, status, ação, ícone e URL.

```php
use App\Notifications\SystemNotification;
use App\Support\Notifications\UserNotificationData;

$user->notify(new SystemNotification(
    new UserNotificationData(
        title: 'Exportação concluída',
        message: 'Seu arquivo está pronto para download.',
        link: Storage::url($filePath),
        status: 'download' // também aceita inteiro
    )
));
```

## Forma legada (apenas compatibilidade)

Ainda funciona, mas use somente em manutenção de código antigo:

```php
use App\Notifications\SystemNotification;

$user->notify(new SystemNotification(
    'Exportação concluída',
    'Seu arquivo está pronto para download.',
    Storage::url($filePath),
    4,
    []
));
```

## Contrato de payload (canônico)

Campos:

- `title` string obrigatório
- `message` string obrigatório
- `link`
- `status`
- `extras`
- `action`:
  - `type`: `none | link | download`
  - `label`: texto do botão
  - `icon`: classe do ícone
  - `url`: URL final da ação

A central resolve a ação nesta ordem:

1. `download`: tenta baixar arquivo de URL local `/storage/...`
2. `link`: redireciona para rota/URL
3. `none`: apenas marca como lida

## Matriz de padrão (status x uso)

- `success` (`1`): operação concluída com sucesso
- `warning` (`2`): atenção, mas sem falha
- `info` (`3`): aviso informativo sem erro
- `download` (`4`): arquivo pronto para baixar
- `error`/`failed` (`5`): falha de operação
- `message` (`6`): comunicação genérica
- `assignment` (`7`): atribuição de atividade/tarefa
- `sla` (`8`): alerta de vencimento/SLA

## Padrão de conteúdo

- Título: curto, objetivo, no máximo 60 caracteres quando possível.
- Mensagem: explicar contexto e próximo passo em 1-2 frases.
- Link:
  - Use `route(...)` para navegação interna.
  - Use `Storage::url(...)` para download local.
- HTML na mensagem: evitar; só usar quando realmente necessário.

## Templates prontos (copiar e colar)

### Navegação para página

```php
$user->notify(new SystemNotification(
    new UserNotificationData(
        title: 'Solicitação atualizada',
        message: 'A solicitação #'.$request->id.' foi atualizada.',
        link: route('cancellations.show', ['request' => $request->id]),
        status: 'info'
    )
));
```

### Download de arquivo

```php
$user->notify(new SystemNotification(
    new UserNotificationData(
        title: 'Relatório pronto',
        message: 'O arquivo foi gerado e está disponível para download.',
        link: Storage::url($filePath),
        status: 'download'
    )
));
```

### Falha de processamento

```php
$user->notify(new SystemNotification(
    new UserNotificationData(
        title: 'Falha ao gerar relatório',
        message: 'A geração falhou após tentativas. Tente novamente mais tarde.',
        status: 'error'
    )
));
```

## Status aceitos

`status` aceita inteiro ou texto.

Mapeamento textual:

- `success`, `sucesso`, `ok` => `1`
- `warning`, `warn`, `atenção`, `atencao` => `2`
- `info`, `information`, `question`, `pergunta` => `3`
- `download` => `4`
- `failed`, `failure`, `error`, `erro`, `danger` => `5`
- `message`, `mensagem` => `6`
- `assignment`, `atribuicao`, `atribuição` => `7`
- `sla`, `deadline`, `vencimento` => `8`

## Checklist de PR (obrigatório)

Antes de subir PR com notificação:

1. Está usando `UserNotificationData`?
2. `status` está coerente com o tipo de evento?
3. Se for download, o link vem de `Storage::url(...)`?
4. Se for navegação, o link vem de `route(...)`?
5. Mensagem está objetiva e sem HTML desnecessário?
6. Testou leitura da notificação na central (abrir/baixar)?

## Boas práticas

- Prefira `UserNotificationData` em novos códigos.
- Use `status` coerente com a intenção visual da notificação.
- Para download, gere URL via `Storage::url(...)` para manter compatibilidade.
- Evite interpolar dados sensíveis na mensagem.
- Não enviar HTML perigoso na mensagem (a view renderiza mensagem com HTML).

## Troubleshooting

1. Notificação não aparece:
- Verificar se o usuário está correto e autenticável.
- Verificar fila/worker (`ShouldQueue`).

2. Erro em worker após mudanças:
- Reiniciar workers:
```bash
php artisan queue:restart
```

3. Download falha:
- Confirmar se arquivo existe no disco esperado.
- Confirmar se URL aponta para `/storage/...`.

## Referências de código

- `app/Notifications/SystemNotification.php`
- `app/Support/Notifications/UserNotificationData.php`
- `app/Contracts/Notifications/UserNotificationContract.php`
- `app/Http/Livewire/Components/Notify/Notifys.php`
- `app/Http/Livewire/Components/Notify/AllNotifies.php`
- `app/Helpers/NotifyStatus.php`
