<?php

namespace App\Http\Livewire\Partner\Actions;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Approveaction extends Component
{
    public $list;
    public $modal;
    public $comment;

    protected $listeners = [
        'teste' => 'testando'
    ];

    public function agree()
    {
        if (strlen(trim($this->comment)) <= 5) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Comentário Necessário',
                'html'      => 'As informações adicionais são necessárias para uma conclusão mais apurada e futuras referências.',
                'timer'    => 5000,
            ]);

            return;

        }

        $this->modal = "";

        $this->modal = [
            'info' => 'Você concorda com a com a decisão do responsável?',
            'action' => 'confirm',
        ];

        $this->dispatchBrowserEvent('showModal', [
            'id' => "confirm",
        ]);

    }

    public function desagree()
    {
        if (strlen(trim($this->comment)) <= 5) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Comentário Necessário',
                'html'      => 'As informações adicionais são necessárias para uma conclusão mais apurada e futuras referências.',
                'timer'    => 5000,
            ]);

            return;

        }



        $this->modal = "";

        $this->modal = [
            'info' => 'Você deseja manter seu posicionamento quanto ao responsável?',
            'action' => 'reject',
        ];

        $this->dispatchBrowserEvent('showModal', [
            'id' => "confirm",
        ]);
    }

    public function confirm()
    {
        if ($this->modal && $this->modal['action'] == 'confirm') {

            // Acrescenta decisão da Empreiteira a mensagem postada.
            $this->comment .= "\n\n >> EMPRESA PARCEIRA CONCORDA COM SEGUIMENTO PARA CONTRATAÇÃO. <<";


            if ($this->list->Viabilities->count()) {
                foreach ($this->list->Viabilities as $viability) {
                    DB::beginTransaction();

                    try {
                        // Atualize a viabilidade
                        $viability->update([
                            'approved' => true,
                            'rejected' => false,
                            'treplica' => true,
                            'status' => 6,
                        ]);

                        // Crie um novo comentário e associe-o à viabilidade
                        $viability->Comments()->create([
                            'user_id' => auth()->user()->id,
                            'message' => $this->comment ?? null,

                        ]);

                        DB::commit();

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'success',
                            'title'    => 'Contestação Aceita',
                            'html'      => 'Foi confirmado junto a contratante o parecer da viabilidade.',
                            'timer'    => 5000,
                        ]);

                        $this->emitUp('update_list');

                    } catch (\Throwable $th) {
                        DB::rollback();

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'danger',
                            'title'    => 'Erro',
                            'html'      => 'Ocorreu algum problema no sistema. Nenhuma alteração foi realiazada..',
                            'timer'    => 5000,
                        ]);

                    }
                }

                $this->closeall();
            }

        }

        if ($this->modal && $this->modal['action'] == 'reject') {

            // Acrescenta decisão da Empreiteira a mensagem postada.
            $this->comment .= "\n\n >> EMPRESA PARCEIRA MANTÉM A REJEIÇÃO DA VIABILIDADE TÉCNICA APRESENTADA. <<";

            if ($this->list->Viabilities->count()) {
                foreach ($this->list->Viabilities as $viability) {
                    DB::beginTransaction();

                    try {
                        // Atualize a viabilidade
                        $viability->update([
                            'approved' => false,
                            'treplica' => true,
                            'status' => 4,
                        ]);

                        // Crie um novo comentário e associe-o à viabilidade
                        $viability->Comments()->create([
                            'user_id' => auth()->user()->id,
                            'message' => $this->comment ?? null,

                        ]);

                        DB::commit();

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'success',
                            'title'    => 'Contestação Mantida',
                            'html'      => 'Foi confirmado junto a contratante o parecer da viabilidade.',
                            'timer'    => 5000,
                        ]);

                        $this->emitUp('update_list');

                    } catch (\Throwable $th) {
                        DB::rollback();

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'danger',
                            'title'    => 'Erro',
                            'html'      => 'Ocorreu algum problema no sistema. Nenhuma alteração foi realiazada..',
                            'timer'    => 5000,
                        ]);

                    }

                }

                $this->closeall();
            }

        }

    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->modal = '';
        $this->comment = "";
        $this->emitUp('update_list');

    }

    public function render()
    {
        return view('livewire.partner.actions.approveaction', [
            'modal' => $this->modal
        ]);
    }
}
