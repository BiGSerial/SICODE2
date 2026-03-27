<?php

namespace App\Notifications;

use App\Contracts\Notifications\UserNotificationContract;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private UserNotificationContract $payload;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        UserNotificationContract|string $titulo,
        ?string $mensagem = null,
        ?string $link = null,
        int|string|null $status = null,
        array $extras = []
    )
    {
        if ($titulo instanceof UserNotificationContract) {
            $this->payload = $titulo;
            return;
        }

        $this->payload = UserNotificationData::fromLegacy(
            $titulo,
            (string) $mensagem,
            $link,
            $status,
            $extras
        );
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Conexão dedicada para notificação em fila.
     */
    public function viaConnections(): array
    {
        return [
            'database' => config('queue.notifications.connection', config('queue.default')),
        ];
    }

    /**
     * Fila dedicada para notificação em fila.
     */
    public function viaQueues(): array
    {
        return [
            'database' => config('queue.notifications.queue', 'messages'),
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return $this->resolvePayload()->toDatabase();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->resolvePayload()->toDatabase();
    }

    private function resolvePayload(): UserNotificationContract
    {
        if (isset($this->payload) && $this->payload instanceof UserNotificationContract) {
            return $this->payload;
        }

        // Fallback para notificações antigas desserializadas da fila sem o campo payload.
        $this->payload = UserNotificationData::fromLegacy(
            'Notificação',
            '',
            null,
            3,
            []
        );

        return $this->payload;
    }
}
