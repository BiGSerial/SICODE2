<?php

namespace App\Http\Livewire\Components\Notify;

use App\Services\Files\ExportStorage;
use App\Support\Notifications\UserNotificationData;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\{Auth, Storage};
use Livewire\{Component, WithPagination};

class AllNotifies extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected $pageName = 'notifyPage';

    public int $perPage = 12;

    public bool $onlyUnread = false;

    /** @var array<int, string> */
    public array $seenNotificationIds = [];

    protected $queryString = [
        'onlyUnread' => ['except' => false],
        'perPage'    => ['except' => 12],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'openNotifies' => 'openModal',
    ];

    public function openModal()
    {

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'notificationsModal',
        ]);
    }

    public function mount(): void
    {
        $user                      = Auth::user();
        $this->seenNotificationIds = $user
            ? $user->notifications()->latest()->limit(20)->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    /** Exibe um toast somente para notificações novas solicitadas como aviso imediato. */
    public function pollForToasts(): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $notifications    = $user->notifications()->latest()->limit(20)->get();
        $newNotifications = $notifications->filter(
            fn (DatabaseNotification $notification) => !in_array((string) $notification->id, $this->seenNotificationIds, true)
        )->sortBy('created_at');

        $this->seenNotificationIds = $notifications->pluck('id')->map(fn ($id) => (string) $id)->all();

        foreach ($newNotifications as $notification) {
            $payload = UserNotificationData::fromArray($notification->data);

            if (!($payload->extras()['toast'] ?? false)) {
                continue;
            }

            $this->dispatchBrowserEvent('notification:toast', [
                'title' => $payload->title(),
                'text'  => strip_tags($payload->message()),
                'icon'  => $payload->status() === UserNotificationData::normalizeStatus('error') ? 'error' : 'success',
                'url'   => $payload->actionUrl(),
            ]);
        }
    }

    protected function baseQuery()
    {

        $user = Auth::user();

        $query = DatabaseNotification::query();

        if (!$user) {
            // Evita exceptions se não autenticado
            return DatabaseNotification::query()->whereRaw('1=0');
        }

        return $user->notifications()
            ->when($this->onlyUnread, fn ($q) => $q->whereNull('read_at'))
            ->latest();
    }

    public function getNotifiesProperty()
    {
        return $this->baseQuery()->paginate($this->perPage, ['*'], $this->pageName);
    }

    public function getUnreadTotalProperty(): int
    {
        $user = Auth::user();

        return $user ? $user->unreadNotifications()->count() : 0;
    }

    public function updating($name, $value)
    {
        if (!in_array($name, [$this->pageName], true)) {
            $this->resetPage($this->pageName);
        }
    }

    public function recognize_all(): void
    {
        if ($user = Auth::user()) {
            $user->unreadNotifications->markAsRead();
        }
        $this->resetPage($this->pageName);
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Todas as notificações foram marcadas como lidas.',
            'timer'    => 2000,
        ]);

        $this->emit('refresh_list');
        $this->emitTo('components.notify.notifys', 'refresh_list');
    }

    public function delete($id): void
    {
        $id   = trim((string) $id, " \t\n\r\0\x0B\"'");
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

        $notification->delete();

        $this->resetPage($this->pageName);
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Notificação apagada!',
            'timer'    => 1500,
        ]);

        $this->emit('refresh_list');
        $this->emitTo('components.notify.notifys', 'refresh_list');
    }

    public function delete_all(): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $user->notifications()->delete();

        $this->resetPage($this->pageName);
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Todas as notificações foram apagadas!',
            'timer'    => 1800,
        ]);
        $this->dispatchBrowserEvent('hideModal', [
            'id' => 'notificationsModal',
        ]);

        $this->emit('refresh_list');
        $this->emitTo('components.notify.notifys', 'refresh_list');
    }

    public function open($id)
    {
        $id   = trim((string) $id, " \t\n\r\0\x0B\"'");
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

        $notification->markAsRead();

        $payload = UserNotificationData::fromArray($notification->data);

        if ($payload->isDownloadAction() && $payload->downloadStoragePath()) {
            $filePath = $payload->downloadStoragePath();
            $disk     = str_starts_with($filePath, 'exports/')
                ? app(ExportStorage::class)->disk()
                : Storage::disk('public');

            if ($disk->exists($filePath)) {
                return $disk->download($filePath, basename($filePath));
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Arquivo inexistente.',
                'timer'    => 2000,
            ]);

            return;
        }

        if (!empty($payload->actionUrl())) {
            return redirect($payload->actionUrl());
        }

        $this->emit('refresh_list');
        $this->emitTo('components.notify.notifys', 'refresh_list');
    }

    public function markAsRead($id): void
    {
        $id   = trim((string) $id, " \t\n\r\0\x0B\"'");
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
        $this->emitTo('components.notify.notifys', 'refresh_list');
    }

    public function render()
    {
        return view('livewire.components.notify.all-notifies', [
            'notifies'    => $this->notifies,
            'unreadTotal' => $this->unreadTotal,
        ]);
    }
}
