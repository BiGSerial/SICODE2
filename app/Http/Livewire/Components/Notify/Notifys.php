<?php

namespace App\Http\Livewire\Components\Notify;

use App\Support\Notifications\UserNotificationData;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Notifys extends Component
{
    use WithPagination;

    public $total_notifies = 4;

    protected $paginationTheme = 'bootstrap';

    // IMPORTANTE: paginação com nome único para não conflitar
    protected string $paginationName = 'notifyPage';

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    // para o Blade: $notifies (topo do sino)
    public function getNotifiesProperty()
    {
        return Auth::user()->notifications()->latest()->limit(10)->get();
    }

    // lista completa paginada para o modal
    public function getAllNotifiesProperty()
    {
        // paginação com nome custom
        return Auth::user()
            ->notifications()
            ->latest()
            ->paginate(10, ['*'], $this->paginationName);
    }

    public function updating($name, $value)
    {
        // quando qualquer filtro mudar (se futuramente tiver), reseta página do modal
        if ($name !== $this->paginationName) {
            $this->resetPage($this->paginationName);
        }
    }

    // abrir modal
    public function openModal()
    {
        $this->dispatchBrowserEvent('notify:openModal');
    }

    // fechar modal (se quiser disparar de algum lugar)
    public function closeModal()
    {
        $this->dispatchBrowserEvent('notify:closeModal');
    }

    // Marcar todas como lidas
    public function recognize_all()
    {
        if ($user = Auth::user()) {
            $user->unreadNotifications->markAsRead();
        }
        // atualiza contagens na tela
        $this->resetPage($this->paginationName);
        $this->emit('refresh_list');
        $this->emitTo('components.notify.all-notifies', 'refresh_list');
    }

    // Apagar UMA notificação
    public function delete($id)
    {
        $id = trim((string) $id, " \t\n\r\0\x0B\"'");
        $user = Auth::user();
        if (!$user || $id === '') {
            return;
        }

        $notification = $user->notifications()->whereKey($id)->first()
            ?: DatabaseNotification::query()
                ->whereKey($id)
                ->where('notifiable_id', $user->getKey())
                ->where('notifiable_type', $user->getMorphClass())
                ->first();
      
        if ($notification) {
            $notification->delete();
            $this->resetPage($this->paginationName);
            $this->emit('refresh_list');
            $this->emitTo('components.notify.all-notifies', 'refresh_list');

            $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'NOTIFICAÇÃO APAGADA!',
                    'timer'    => 5000,
                ]);
            return;
        }
    }

    // Apagar TODAS
    public function delete_all()
    {
        if ($user = Auth::user()) {
            $user->notifications()->delete();
        }
        $this->resetPage($this->paginationName);
        $this->emit('refresh_list');
        $this->emitTo('components.notify.all-notifies', 'refresh_list');
    }

    // Marcar como lida e redirecionar/baixar (mantido e levemente robustecido)
    public function readed($id)
    {
        $id = trim((string) $id, " \t\n\r\0\x0B\"'");
        $user = Auth::user();
        if (!$user || $id === '') {
            return;
        }

        $notification = $user->notifications()->whereKey($id)->first()
            ?: DatabaseNotification::query()
                ->whereKey($id)
                ->where('notifiable_id', $user->getKey())
                ->where('notifiable_type', $user->getMorphClass())
                ->first();
        if (!$notification) {
            return;
        }

        $payload = UserNotificationData::fromArray($notification->data);
        $notification->markAsRead();

        // ação de download
        if ($payload->isDownloadAction() && $payload->downloadStoragePath()) {
            $filePath = $payload->downloadStoragePath();

            if (Storage::exists($filePath)) {
                return Storage::download($filePath, basename($filePath));
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ARQUIVO INEXISTENTE!',
                    'timer'    => 5000,
                ]);
                return;
            }
        }

        // ação de link
        if (!empty($payload->actionUrl())) {
            return redirect($payload->actionUrl());
        }

        $this->emit('refresh_list');
        $this->emitTo('components.notify.all-notifies', 'refresh_list');
    }

    public function markAsRead($id): void
    {
        $id = trim((string) $id, " \t\n\r\0\x0B\"'");
        $user = Auth::user();
        if (!$user || $id === '') {
            return;
        }

        $notification = $user->notifications()->whereKey($id)->first()
            ?: DatabaseNotification::query()
                ->whereKey($id)
                ->where('notifiable_id', $user->getKey())
                ->where('notifiable_type', $user->getMorphClass())
                ->first();

        if (!$notification) {
            return;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $this->emit('refresh_list');
        $this->emitTo('components.notify.all-notifies', 'refresh_list');
    }

    public function render()
    {
        return view('livewire.components.notify.notifys', [
            'notifies'    => $this->notifies,     // topo (10 mais recentes)
            'allNotifies' => $this->allNotifies,  // paginado no modal
        ]);
    }
}
