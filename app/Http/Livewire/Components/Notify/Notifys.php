<?php

namespace App\Http\Livewire\Components\Notify;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Notifys extends Component
{
    public $total_notifies = 4;

    // Pega as 10 notificações mais recentes do usuário autenticado
    /**
 * @method \Illuminate\Notifications\DatabaseNotification[] notifications()
 * @method \Illuminate\Notifications\DatabaseNotification[] unreadNotifications()
 */
    public function getNotifiesProperty()
    {
        return Auth::user()->notifications()->latest()->limit(10)->get();
    }

    // Marcar todas como lidas
    public function recognize_all()
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    // Marcar notificação como lida e redirecionar/baixar arquivo se necessário
    /**
 * @method \Illuminate\Notifications\DatabaseNotification[] notifications()
 * @method \Illuminate\Notifications\DatabaseNotification[] unreadNotifications()
 */
    public function readed($id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if (!$notification) {
            return;
        }

        $data = $notification->data;
        $notification->markAsRead();



        // Exemplo: baixar arquivo se status === 4, senão redirecionar para link
        if (($data['status'] ?? null) === 4 && !empty($data['link'])) {
            // Extrair apenas o caminho do arquivo da URL completa
            $urlPath = parse_url($data['link'], PHP_URL_PATH);


            $filePath = str_replace('/storage/', '', $urlPath);


            if (Storage::exists($filePath)) {
                $fileName = basename($filePath);
                return response()->streamDownload(function () use ($filePath) {
                    echo Storage::get($filePath);
                }, $fileName);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ARQUIVO INEXISTENTE!',
                    'timer'    => 5000,
                ]);
                return;
            }
        } elseif (!empty($data['link'])) {
            return redirect($data['link']);
        }
    }

    public function render()
    {
        return view('livewire.components.notify.notifys', [
            'notifies' => $this->notifies,
        ]);
    }
}
