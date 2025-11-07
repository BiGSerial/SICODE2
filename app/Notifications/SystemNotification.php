<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $titulo;
    public $mensagem;
    public $link;
    public $status;
    public $extras;

    /**
     * Create a new notification instance.
     */
    public function __construct($titulo, $mensagem, $link = null, $status = null, $extras = [])
    {
        $this->titulo = $titulo;
        $this->mensagem = $mensagem;
        $this->link = $link;
        $this->status = $status;
        $this->extras = $extras;
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
     * Get the mail representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'titulo' => $this->titulo,
            'mensagem' => $this->mensagem,
            'link' => $this->link,
            'status' => $this->status,
            'extras' => $this->extras,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
