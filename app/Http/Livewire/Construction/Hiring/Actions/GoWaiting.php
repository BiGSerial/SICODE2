<?php

namespace App\Http\Livewire\Construction\Hiring\Actions;

use App\Http\Livewire\Reports\Productions;
use App\Models\HiringWaiting;
use App\Models\Note;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GoWaiting extends Component
{
    public $notes;
    public $services;
    public $service_s;
    public $comment;
    public $productions = [];
    public $category;


    protected $listeners = [
        'getNotes',
        'dd4e65b58b8cb56b272de9e138a7e1d8' => 'retonar',
        'closeAll',
    ];


    protected $rules = [
        'service_s' => 'required',
        'category' => 'required',
        'comment' => 'required|min:10',
    ];

    protected $messages = [
        'service_s.required' => 'O campo serviço é obrigatório.',
        'category.required' => 'O campo categoria é obrigatório.',
        'comment.required' => 'O campo comentário é obrigatório.',
        'comment.min' => 'O campo comentário deve ter no mínimo 10 caracteres.',
    ];

    public function mount()
    {
        $this->services = Service::where('canReturn', true)->orderBy('service')->get();
    }

    public function getNotes($notes_ids)
    {

        $this->notes = Note::whereIn('id', $notes_ids)->with('productions')->get();

        if ($this->notes) {

            foreach ($this->notes as $key => $note) {

                $this->productions[$key]['note'] = $note;
                $this->productions[$key]['production'] = null;

            }


            $this->dispatchBrowserEvent('showModal', [
                'id' => "return_modal",
            ]);
        }
    }

    public function updatedServiceS($value)
    {
        if (!$this->notes->isEmpty() && $value) {
            foreach ($this->notes as $key => $note) {

                $this->productions[$key]['production'] = Production::where('note_id', $note->id)
                            ->where('service_id', $value)
                            ->with('user')
                            ->orderBy('created_at', 'desc')
                            ->first();

            }
        } else {
            foreach ($this->notes as $key => $note) {

                $this->productions[$key]['production'] = null;

            }
        }

        // if ($this->productions) {
        //     dd($this->productions);
        // }

    }

    public function go_return()
    {
        $this->validate();

        $count = count($this->notes);
        $service = Service::where('uuid', $this->service_s)->first();

        $this->dispatchBrowserEvent('alertar', [
            'title'         => "RETORNAR PARA PROJETO",
            'msg'           => "
                <p>Deseja enviar as <span class='fw-bold'> $count </span> nota(s) para a categoria <span class='fw-bold'> $this->category </span> em  <span class='fw-bold'> $service->service </span>?</p>
                <div class='card'>
                    <div class='card-body text-left'>
                        <p class='fw-bold'>Comentário:<span class='fw-normal'>  $this->comment </span></p>
                    </div>
                </div>
            ",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Envie!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'dd4e65b58b8cb56b272de9e138a7e1d8',
            'confirm'       => 'Sim envie',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Obra foi Retornada!',

        ]);

        return;
    }

    public function retonar()
    {
        if (!count($this->productions)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'SEM REGISTRO',
                'html'     => 'Por algum motivo, os dados NÃO EXISTEM ou FORAM perdidos. Verifique novamente os dados e tente novamente.',
                'timer'    => 10000,
            ]);

            return;
        }

        DB::beginTransaction();

        try {

            $waiting = '';

            foreach ($this->productions as $production) {

                if ($production) {
                    if (Reclaim::hasActiveForService($production['note']['id'], $this->service_s)) {
                        continue;
                    }

                    $waiting = HiringWaiting::Create([
                        'note_id' => $production['note']['id'],
                        'service_id' => $this->service_s,
                        'category' => $this->category,
                        'comment' => $this->comment,
                        'user_id' => auth()->user()->id,
                    ]);

                    if ($waiting) {

                        $reclaim = Reclaim::Create(
                            [
                                'note_id' => $production['note']['id'],
                                'service_id' => $this->service_s,
                                'category' => $this->category,
                                'complete' => false,
                            ]
                        );

                        if ($reclaim) {
                            $waiting->update([
                                'reclaim_id' => $reclaim->id,
                            ]);

                            $reclaim->Comments()->Create([
                                'user_id' => auth()->user()->id,
                                'message' => $this->comment,
                                'restrict' => false,
                                'granted' => false,
                                'dismissed' => false,
                            ]);
                        }

                        if ($reclaim && !$reclaim->Production && $production['production']) {


                            $user = User::find($production['production']['user_id']);

                            if ($user) {

                                $prod = Production::create([
                                        'note_id' => $production['note']['id'],
                                        'service_id' => $this->service_s,
                                        'user_id' => $user->id,
                                        'company_id' => $user->Employee->Contract->company->id,
                                        'dispatch_by' => auth()->user()->id,
                                        'att_by' => auth()->user()->id,
                                        'dt_note' => $production['note']['dt_status'],
                                        'dispatch_at' => date('Y-m-d H:i:s'),
                                        'att_at' => date('Y-m-d H:i:s'),
                                        'status' => 2,
                                        'dt_status' => $production['note']['dt_status'],
                                        'd5'    => true,
                                        'status_note' => $production['note']['nstats'],
                                ]);

                                $reclaim->update([
                                    'production_id' => $prod->id,
                                ]);

                            }


                        }

                    }
                }

            }

            DB::commit();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'SUCESSO',
                'html'     => 'As notas foram retornadas para o projeto com sucesso.',
                'timer'    => 10000,
            ]);

            $this->emitTo('construction.hiring.main', 'cloesAll');
            $this->closeAll();


        } catch (\Throwable $th) {

            DB::rollBack();

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO',
                'html'     => 'Ocorreu um erro ao tentar retornar as notas para o projeto, tente novamente.<br><br>' . $th->getMessage(),

            ]);

            return;
        }
    }

    public function closeAll()
    {

        $this->notes = null;
        $this->service_s = null;
        $this->category = null;
        $this->comment = null;
        $this->productions = [];
        $this->resetErrorBag();
        $this->resetValidation();

        $this->emitUp('closeAll');
        $this->dispatchBrowserEvent('hideModal');
    }


    public function render()
    {
        return view('livewire.construction.hiring.actions.go-waiting');
    }
}
