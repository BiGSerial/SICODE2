<?php

namespace App\Http\Livewire\Components\Notify;

use App\Models\Notify;
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
        Auth::user()->unreadNotifications->markAsRead();
        // atualiza contagens na tela
        $this->resetPage($this->paginationName);
    }

    // Apagar UMA notificação
    public function delete($id)
    {

        $notification = Auth::user()->notifications()->find($id);
        dd($notification);
        if ($notification) {
            $notification->delete();
            $this->resetPage($this->paginationName);

            $this->emitSelf('refresh_list');

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
        Auth::user()->notifications()->delete();
        $this->resetPage($this->paginationName);
        $this->emitSelf('refresh_list');
    }

    // Marcar como lida e redirecionar/baixar (mantido e levemente robustecido)
    public function readed($id)
    {

        $notification = Auth::user()->notifications()->find($id);
        if (!$notification) {
            return;
        }

        $data = $notification->data;
        $notification->markAsRead();

        // baixar arquivo se status === 4 e há link de storage
        if (($data['status'] ?? null) === 4 && !empty($data['link'])) {
            $urlPath  = parse_url($data['link'], PHP_URL_PATH);
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
        }

        // senão, se há link, redireciona
        if (!empty($data['link'])) {
            return redirect($data['link']);
        }
    }

    public function render()
    {
        return view('livewire.components.notify.notifys', [
            'notifies'    => $this->notifies,     // topo (10 mais recentes)
            'allNotifies' => $this->allNotifies,  // paginado no modal
        ]);
    }
}
