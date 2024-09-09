<?php

namespace App\Http\Livewire\Dispatchs\Comission;

use App\Models\{Note, Production, Service, Wpa};
use Livewire\{Component, WithPagination};

class Transfer extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $last_update;

    public $notes;

    public $perPage = 50;

    public $transfer;

    public $production;

    public $dd = [];

    protected $listeners = [
        'refresh_list'         => '$refresh',
        'to_complete_transfer' => 'go_complete_transfer',
    ];

    public function verify_transfer(Production $production)
    {
        $this->transfer   = Wpa::where('production_id', $production->id)->first();
        $this->production = $production;

        if ($this->dd[$production->id] == $production->load('Wpas')->Wpas->last()->dd) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Confirmar Transferência',
                'msg'           => "Você deseja manter o mesmo número DD: <strong>{$production->load('Wpas')->Wpas->last()->dd}</strong> para a Nota <strong>{$production->load('Note')->Note->note}</strong>?",
                'icon'          => 'question',
                'btnOktxt'      => 'Sim, Mantenha o mesmo número!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'to_complete_transfer',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhuma Nota/OV foi transferida.',
            ]);
        } else {

            $wpas = Wpa::where('dd', $this->dd[$production->id])->first();

            if ($wpas) {

                if ($wpas->production_id) {
                    $user = $wpas->load('Production')->Production->stats >= 2 ? $wpas->load('Production.User')->Production->User->name : 'NÂO ATRIBUÍDO';
                    $note = $wpas->load('Note')->Note->note;

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'ERRO DE ASSOCIAÇÃO',
                        'msg'      => "A DD <strong>{$this->dd[$production->id]}</strong>, está relacionada NOTA/OV: {$note} atribuído à <strong>{$user}</strong>. Não foi possíve prosseguir com a associoação.",
                    ]);

                    return;
                }

                if (!$wpas->production_id) {

                    $note = $wpas->load('Note')->Note->note;

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'ERRO DE ASSOCIAÇÃO',
                        'msg'      => "A DD <strong>{$this->dd[$production->id]}</strong>, está relacionada NOTA/OV: {$note}, ainda não despachada. Verifique novamente, ou altere a DD da nota associada.",
                    ]);

                    return;
                }
            } else {
                $this->dispatchBrowserEvent('alertar', [
                    'title'         => 'Confirmar Transferência',
                    'msg'           => "Você deseja usar número DD: <strong>{$this->dd[$production->id]}</strong> para a Nota <strong>{$production->load('Note')->Note->note}</strong>?",
                    'icon'          => 'question',
                    'btnOktxt'      => 'Sim, Alterar a DD!',
                    'btnCanceltxt'  => 'Não, Cancele',
                    'action'        => 'to_complete_transfer',
                    'cancel_titulo' => 'Cancelado!',
                    'cancel_msg'    => 'Nenhuma nenhuma Nota/OV foi transferida.',
                ]);
            }

        }
    }

    public function go_complete_transfer()
    {
        if ($this->transfer) {
            if ($this->transfer->update(['dd' => $this->dd[$this->production->id]])) {
                $this->production->update(['block_wpa' => false]);

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Nota NOTA/OV Transferida!',
                    'timer'    => 2500,
                ]);

                $this->emit('refresh_list');

            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Não conseguimos transferir as DD, verifique novamente.',
                    'timer'    => 5000,
                ]);
            }
        }
    }

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;

        $lists = $this->lists;

        foreach ($lists as $prod) {
            $this->dd[$prod->id] = $prod->Wpas->last()->dd;
        }

    }

    public function getListsProperty()
    {

        return Production::where('block_wpa', true)->where('block', false)->with('Wpas', 'Transfer.From', 'Transfer.To', 'Service', 'Note')->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.dispatchs.comission.transfer', [
            'lists' => $this->lists,
        ]);
    }
}
