<?php

namespace App\Http\Livewire\Services\Oexterno\Protocols;

use App\Helpers\SelectOptions;
use App\Models\External;
use App\Models\ExternalPoolpayment;
use App\Models\File;
use App\Models\Note;
use App\Models\Protocol;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Main extends Component
{
    public ?Note $note = null;

    public $openExternalContactId;
    public $protocol;
    public $external;
    public $paymentPoolId;
    public $poolPayment;
    public $openExternalId = null;
    public $modalStatusValue = null;
    public $currentExternal = null;
    public $activeMainTab = 'note-data-pane';
    public $activeModalTab = 'modal-protocols';

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'setOpenExternal',
        'setOpenExternalContact',
        'confirmDeleteProtocol',
        'confirmFinishEntity',
    ];

    protected $rules = [
        'currentExternal.status' => 'required|string|max:191',
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
                        'Reclaims',
                        'files',
                    ]);
                },
            ])
            ->first();

        if (!$this->note) {
            abort(404, 'Página não encontrada');
        }
    }

    public function setActiveMainTab($tab)
    {
        $this->activeMainTab = $tab;
    }

    public function setActiveModalTab($tab)
    {
        $this->activeModalTab = $tab;
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
                <p>Você deseja realmente remover a entidade protocolar {$this->external?->entity?->nick}?<br> Ao remover, todas as associações com exceção dos arquivos serão perdidos.</p>
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


    public function requestPayment(External $external)
    {
        if (!$external || $external->completed) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao solicitar pagamento!',
                'msg'      => 'Entidade protocolar inválida ou finalizada.',
                'timer'    => 5000,
            ]);
            return;
        }

        if (!trim($this->paymentPoolId)) {
            return;
        }


        $this->validate([
            'paymentPoolId' => 'required|integer|unique:external_poolpayments,pool_id',
        ], [
            'paymentPoolId.required' => 'O campo PoolId é obrigatório.',
            'paymentPoolId.integer'  => 'O campo PoolId deve ser um número inteiro.',
            'paymentPoolId.unique'   => 'Já existe um pedido de pagamento com este PoolId.',
        ]);

        $external->PoolPayments()->create([
            'pool_id' => $this->paymentPoolId,
            'status_pedido' => 'Novo Pedido',
            'user_id' => auth()->user()->id,
        ]);

        $external->Comments()->create([
            'user_id' => auth()->user()->id,
            'title' => 'PEDIDO DE PAGAMENTO',
            'comment' => "Pedido de pagamento solicitado com PoolId: {$this->paymentPoolId}",
        ]);

        $this->paymentPoolId = null;
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Pedido de pagamento registrado com sucesso!',
            'timer'    => 5000,
        ]);
        $this->emitSelf('refreshComponent');
    }

    public function deletePoolPayment(ExternalPoolpayment $pool)
    {
        $this->poolPayment = $pool;

        if ($this->poolPayment) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Remover Entidade Protocolar',
                'msg'   => "
                <p>Você deseja realmente remover a solicitaçao de pagamento {$this->poolPayment->pool_id}?<br> Ao remover, todas as associações com exceção dos arquivos serão perdidos.</p>
                ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Remova!',
                'btnCanceltxt'  => 'Não, Cancele!',
                'action'        => 'confirmDeletePoolPayment',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma solicitaçao foi excluída.',
            ]);
        }
    }


    public function saveModalChanges()
    {
        if (!$this->external) {
            return;
        }

        $this->external->save();

        $this->dispatchBrowserEvent('hideModal');
        $this->external = null;
        $this->emitSelf('refreshComponent');
    }

    public function openEntityModal(int $externalId): void
    {
        // Limpa estado do modal (importante para “zerar” ao trocar de entidade)
        $this->resetValidation();
        $this->reset(['modalStatusValue', 'paymentPoolId']);

        // Define a entidade a ser carregada
        // $this->openExternalId = $externalId;

        $this->currentExternal = $this->note->externals->firstWhere('id', $externalId);

        // (Opcional) se quiser já fechar qualquer flash state anterior, pode emitir eventos aqui
        // $this->dispatchBrowserEvent('modal-ready'); // se precisar
    }




    public function confirmDeletePoolPayment()
    {
        if ($this->poolPayment) {
            $this->poolPayment->delete();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Solicitação de pagamento removida com sucesso!',
                'timer'    => 5000,
            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao remover solicitação de pagamento!',
                'timer'    => 5000,
            ]);
        }
        $this->poolPayment = null;
        $this->emitSelf('refreshComponent');

    }





    public function toFinishEntity(External $external)
    {


        $this->external = $external->load('Entity');



        if ($this->external) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Finalizar Entidade',
                'msg'   => "
                <p>Você deseja realmente finalizar o protocolo para: <br> <strong>{$this->external->entity?->nick}</strong> em <strong>{$this->external->note->note}</strong>?</p><p> Ao finalizar, Não será mais possível alterar status mensagens ou anexar novas evidências.</p>
                ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Finalizar!',
                'btnCanceltxt'  => 'Não, Cancele!',
                'action'        => 'confirmFinishEntity',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma entidade protocolar foi excluída.',
            ]);
        }
    }

    public function confirmFinishEntity()
    {


        if ($this->external) {
            $this->external->status = 'FINALIZADO';
            $this->external->completed = true;
            $this->external->user_id = auth()->user()->id;
            $this->external->save();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Entidade protocolar finalizada com sucesso!',
                'timer'    => 5000,
            ]);

            $this->external->Comments()->create([
                'user_id' => auth()->user()->id,
                'title' => 'PROTOCOLO FINALIZADO',
                'comment' => 'Protocolo finalizado com sucesso!',
            ]);

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao finalizar entidade protocolar!',
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

    public function updateEntityStatus()
    {

        if ($this->currentExternal) {

            $this->validate([
                'currentExternal.status' => 'required|string|max:191',
            ], [
                'currentExternal.status.required' => 'O campo Status da Entidade é obrigatório.',
                'currentExternal.status.string'   => 'O campo Status da Entidade deve ser uma string.',
                'currentExternal.status.max'      => 'O campo Status da Entidade não deve exceder 191 caracteres.',
            ]);

            $this->currentExternal->save();

            $selectStatus = collect(SelectOptions::getProtocolReasons());

            $select = $selectStatus->firstWhere('value', $this->currentExternal->status)->reason ?? 'INDEFINIDO';


            if ($select) {
                $this->currentExternal->Comments()->create([
                    'user_id' => auth()->user()->id,
                    'title' => 'ATUALIZAÇÃO DE STATUS',
                    'comment' => "Status da entidade atualizado para: {$select}",
                ]);
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Status da entidade atualizado com sucesso!',
                'timer'    => 5000,
            ]);

            $this->reset(['modalStatusValue']);
            $this->emitSelf('refreshComponent');

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao atualizar status da entidade!',
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
