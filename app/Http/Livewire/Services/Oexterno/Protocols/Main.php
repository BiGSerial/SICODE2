<?php

namespace App\Http\Livewire\Services\Oexterno\Protocols;

use App\Models\External;
use App\Models\File;
use App\Models\Note;
use App\Models\Protocol;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Main extends Component
{
    public ?Note $note = null;
    public $openExternalId;
    public $openExternalContactId;
    public $protocol;
    public $external;

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'setOpenExternal',
        'setOpenExternalContact',
        'confirmDeleteProtocol',
    ];

    public function mount()
    {
        $this->note = Note::where('note', request()->route('note'))
            ->with([
                // eager-load externals e, para cada external:
                'externals' => function ($q) {
                    $q->with([
                        'comments' => function ($q3) {
                            $q3->orderBy('created_at', 'desc');
                        },
                        'user',
                        // carrega protocolos já ordenados DESC
                        'protocols' => function ($q2) {
                            $q2->orderBy('created_at', 'desc');
                        },
                    ]);
                },
            ])
            ->first();

        if (!$this->note) {
            abort(404, 'Página não encontrada');
        }
    }

    public function setOpenExternal($id)
    {

        $this->openExternalId = $id;
    }

    public function setOpenExternalContact($id)
    {

        $this->openExternalContactId = $id;
    }

    public function deleteProtocol(External $external)
    {
        $this->external = $external;

        if ($this->external) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Remover Entidade Protocolar',
                'msg'   => "
                <p>Você deseja realmente remover a entidade protocolar {$this->external->entity->nick}?<br> Ao remover, todas as associações com exceção dos arquivos serão perdidos.</p>
                ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Atribua!',
                'btnCanceltxt'  => 'Não, Cancele!',
                'action'        => 'confirmDeleteProtocol',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma entidade protocolar foi excluída.',
            ]);
        }
    }

    public function confirmDeleteProtocol()
    {
        if ($this->external) {
            $this->external->delete();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Entidade protocolar removida com sucesso!',
                'timer'    => 5000,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao remover entidade protocolar!',
                'timer'    => 5000,
            ]);
        }
        $this->external = null;
        $this->emitSelf('refreshComponent');

    }

    public function downloadFile(File $file)
    {

        if ($file && Storage::exists($file->path)) {

            return Storage::download($file->path);

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ARQUIVO NÃO ENCONTRADO!',
                'timer'    => 5000,
            ]);
        }
    }



    public function render()
    {
        $this->note->refresh();

        return view('livewire.services.oexterno.protocols.main');
    }
}
