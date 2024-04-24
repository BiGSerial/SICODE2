<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Hiring extends Component
{
    public $list;
    public $services;
    public $service_s;
    public $comment;
    public $action;
    public $confirm_text;


    public function mount()
    {
        $this->services = Service::orderBy('service')->get();
    }

    public function go_action()
    {
        if (strLen(trim($this->comment)) <= 5) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'COMENTÁRIO OBRIGATÓRIO',
                'html'      => 'É preciso inserir uma breve informação para ação',
                'timer'    => 5000,
            ]);

            return;
        }

        if ($this->action == 1) {

            $this->confirm_text = [
                'action' => 'VIABILIZAR',
                'message' => "Deseja enviar {$this->list->note} novamente para viabilidade?",
            ];

            $this->dispatchBrowserEvent('showModal', [
                'id' => "confirm",
            ]);

        } elseif ($this->action == 2) {

            $this->confirm_text = [
                'action' => 'CONTRATAR',
                'message' => "Deseja contratar a obra {$this->list->note}?",
            ];

            $this->dispatchBrowserEvent('showModal', [
                'id' => "confirm",
            ]);

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'AÇÃO É NECESSÁRIO',
                'html'      => 'É preciso informar o tipo de ação a ser tomado para continuar com a atilvidade.',
                'timer'    => 5000,
            ]);

            return;
        }


    }

    public function confirm()
    {

        DB::beginTransaction();

        try {

            if ($this->action == 1) {

                // Update Viabilities with Viabilities Information Again (return)

                $block = false;
                $commentId = null;

                foreach ($this->list->Viabilities as $viab) {

                    $viab->update([
                        'sended_at'   => date('Y-m-d H:i:s'),
                        'completed' => false,
                        'hired' => false,
                        'rejected' => false,
                        'approved' => false,
                        'partner_ok' => false,
                        'partnerok_at' => null,
                        'status'      => 1,
                        'replica' => false,
                        'treplica' => false,
                        'returned_at' => null,
                    ]);

                    if (!$block) {

                        $commentId = $viab->Comments()->create([
                            'message' => $this->comment,
                            'user_id' => Auth()->User()->id,
                        ]);

                        !$block = true;

                    } else {

                        $viab->Comments()->attach($commentId);

                    }

                }



            } elseif ($this->action == 2) {

                // Update Viabilities with Hiring Information

                foreach ($this->list->Viabilities as $viab) {
                    $viability = $viab->update([
                        'user_id'     => Auth()->User()->id,
                        'status'      => 9,
                        'hired'       => true,
                        'hired_at'    => date('Y-m-d H:i:s'),
                        'completed'   => true,
                        'completed_at' => date('Y-m-d H:i:s'),

                    ]);
                }

            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'AÇÃO É NECESSÁRIO',
                    'html'      => 'É preciso informar o tipo de ação a ser tomado para continuar com a atilvidade.',
                    'timer'    => 5000,
                ]);

                return;
            }

        } catch (\Throwable $th) {
            DB::rollBack();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'ERRO AO PROCESSAR',
                'html'      => $th->getMessage(),
                'timer'    => 5000,
            ]);

            return;
        }

        DB::commit();

        $this->closeAll();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'SUCESSO',
            'html'      => "A Solicitação foi realizada com sucesso.",
            'timer'    => 5000,
        ]);

        return;

    }

    public function closeAll()
    {

        $this->comment = "";
        $this->action = "";
        $this->confirm_text = "";

        $this->emitUp('update_list');
    }

    public function render()
    {
        return view('livewire.construction.hiring.actions.hiring', [
            'services' => $this->services
        ]);
    }
}
