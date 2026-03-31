<?php

namespace App\Support\Notifications;

use App\Contracts\Notifications\UserNotificationContract;

class UserNotificationData implements UserNotificationContract
{
    public const ACTION_NONE = 'none';
    public const ACTION_LINK = 'link';
    public const ACTION_DOWNLOAD = 'download';

    private string $title = 'Notificação';
    private string $message = '';
    private ?string $link = null;
    private int $status = 3;
    private array $extras = [];
    private string $actionType = self::ACTION_NONE;
    private string $actionLabel = 'Marcar como lida';
    private string $actionIcon = 'bi bi-envelope-open';
    private ?string $actionUrl = null;

    public function __construct(
        string $title,
        string $message,
        ?string $link = null,
        int|string|null $status = null,
        array $extras = [],
        ?string $actionType = null,
        ?string $actionLabel = null,
        ?string $actionIcon = null,
        ?string $actionUrl = null
    ) {
        $this->title = trim($title) !== '' ? trim($title) : 'Notificação';
        $this->message = $message;
        $this->link = $link;
        $this->status = self::normalizeStatus($status);
        $this->extras = $extras;

        $resolvedActionType = $actionType ?: $this->resolveActionType();
        $this->actionType = in_array($resolvedActionType, [self::ACTION_NONE, self::ACTION_LINK, self::ACTION_DOWNLOAD], true)
            ? $resolvedActionType
            : self::ACTION_NONE;

        $this->actionUrl = $actionUrl ?: $this->link;
        $this->actionLabel = $actionLabel ?: $this->defaultActionLabel();
        $this->actionIcon = $actionIcon ?: $this->defaultActionIcon();
    }

    public static function fromLegacy(
        string $title,
        string $message,
        ?string $link = null,
        int|string|null $status = null,
        array $extras = []
    ): self {
        return new self($title, $message, $link, $status, $extras);
    }

    public static function fromArray(array $data): self
    {
        $extras = is_array($data['extras'] ?? null) ? $data['extras'] : [];
        $actionData = is_array($data['action'] ?? null) ? $data['action'] : [];

        return new self(
            title: (string) ($data['title'] ?? $data['titulo'] ?? 'Notificação'),
            message: (string) ($data['message'] ?? $data['mensagem'] ?? ''),
            link: isset($data['link']) ? (string) $data['link'] : null,
            status: $data['status'] ?? null,
            extras: $extras,
            actionType: isset($actionData['type']) ? (string) $actionData['type'] : ($extras['action_type'] ?? null),
            actionLabel: isset($actionData['label']) ? (string) $actionData['label'] : ($extras['action_label'] ?? null),
            actionIcon: isset($actionData['icon']) ? (string) $actionData['icon'] : ($extras['action_icon'] ?? null),
            actionUrl: isset($actionData['url']) ? (string) $actionData['url'] : ($extras['action_url'] ?? null)
        );
    }

    public static function normalizeStatus(int|string|null $status): int
    {
        if (is_int($status)) {
            return $status;
        }

        if (is_string($status)) {
            $value = strtolower(trim($status));
            if (is_numeric($value)) {
                return (int) $value;
            }

            return match ($value) {
                'success', 'sucesso', 'ok' => 1,
                'warning', 'warn', 'atenção', 'atencao' => 2,
                'info', 'information', 'pergunta', 'question' => 3,
                'download' => 4,
                'failed', 'failure', 'erro', 'error', 'danger' => 5,
                'message', 'mensagem' => 6,
                'assignment', 'atribuição', 'atribuicao' => 7,
                'sla', 'deadline', 'vencimento' => 8,
                default => 3,
            };
        }

        return 3;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function link(): ?string
    {
        return $this->link;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function extras(): array
    {
        return $this->extras;
    }

    public function actionType(): string
    {
        return $this->actionType;
    }

    public function actionLabel(): string
    {
        return $this->actionLabel;
    }

    public function actionIcon(): string
    {
        return $this->actionIcon;
    }

    public function actionUrl(): ?string
    {
        return $this->actionUrl;
    }

    public function isDownloadAction(): bool
    {
        return $this->actionType === self::ACTION_DOWNLOAD;
    }

    public function downloadStoragePath(): ?string
    {
        $url = $this->actionUrl();
        if (!$url) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '' || !str_contains($path, '/storage/')) {
            return null;
        }

        return ltrim(str_replace('/storage/', '', $path), '/');
    }

    public function toDatabase(): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'link' => $this->link(),
            'status' => $this->status(),
            'extras' => $this->extras(),
            'action' => [
                'type' => $this->actionType(),
                'label' => $this->actionLabel(),
                'icon' => $this->actionIcon(),
                'url' => $this->actionUrl(),
            ],

            // Compatibilidade retroativa de leitura
            'titulo' => $this->title(),
            'mensagem' => $this->message(),
        ];
    }

    private function resolveActionType(): string
    {
        if ($this->status === 4) {
            return self::ACTION_DOWNLOAD;
        }

        if ($this->status === 5 && $this->link && $this->downloadStoragePath()) {
            return self::ACTION_DOWNLOAD;
        }

        if ($this->link) {
            return $this->downloadStoragePath() ? self::ACTION_DOWNLOAD : self::ACTION_LINK;
        }

        return self::ACTION_NONE;
    }

    private function defaultActionLabel(): string
    {
        return match ($this->actionType) {
            self::ACTION_DOWNLOAD => 'Baixar',
            self::ACTION_LINK => 'Abrir',
            default => 'Marcar como lida',
        };
    }

    private function defaultActionIcon(): string
    {
        return match ($this->actionType) {
            self::ACTION_DOWNLOAD => 'ri-file-download-fill',
            self::ACTION_LINK => 'bi bi-box-arrow-up-right',
            default => 'bi bi-envelope-open',
        };
    }
}
