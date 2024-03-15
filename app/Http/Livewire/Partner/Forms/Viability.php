<?php

namespace App\Http\Livewire\Partner\Forms;

use App\Models\Edp_depc\City;
use App\Models\Note;
use Illuminate\Support\Facades\Crypt;
use Livewire\Component;

class Viability extends Component
{
    public $data;
    public $cities;
    public $changes;
    public $result = [];

    protected $queryString = [
        'changes' => ['except' => '']
    ];

    protected $listeners = [
        'confirm_cancelForm' => 'cancelForm'
    ];


    public function mount($id)
    {
        try {

            $this->cities = City::orderBy('cidade')->get();

        } catch (\Throwable $th) {

            $this->cities = false;
        }

        if ($id) {
            $this->data = Note::With(['Viabilities' => function ($query) {
                $query->where('approved', false)
                        ->where('tacit', false)
                        ->where('canceled', false)
                        ->with('Order');
            }])->find(Crypt::decrypt($id));
        }

    }

    public function toCancelForm()
    {
        $this->dispatchBrowserEvent('alertar', [
            'title' =>  'CANCELAR FORMULÁRIO',
            'msg' => "Você deseja cancelar este formulário e voltar para a listagem de viabilidade?",
            'icon' => 'warning',
            'btnOktxt' => 'Sim, Cancele e volte!',
            'btnCanceltxt' => 'Não, Desisto',
            'action' => "confirm_cancelForm",
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg' => 'O Formulário foi cancelado com sucesso.',
        ]);
    }

    public function cancelForm()
    {
        return redirect(route('partner.todo.viability'));
    }

    public function render()
    {



        return view('livewire.partner.forms.viability', [
            'note' => $this->data
        ])->layout('layouts.forms.viability');

    }
}
