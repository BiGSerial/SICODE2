<?php

namespace App\Http\Livewire\Partner\Forms;

use App\Models\Note;
use App\Models\Order;
use Livewire\Component;

class Workreports extends Component
{
    public ?Note $note = null;
    public $preNote;
    public $notes;
    public $search;
    public $s_order;


    public $equipment;
    public $model_equipment = [
        'type' => '',
        'patrimony' => '',
        'fases' => '',
        'pole' => '',
        'installed' => false,
    ];

    public $form = [
        'note_id' => '',
        'company_id' => '',
        'user_id' => '',
        'date' => '',
        'equipment' => false,
        'connection' => false,
        'changes' => false,
        'observation' => '',
        'damage' => false,
        'description' => '',
        'team' => '',
        'responsible' => ''
    ];

    public $temp_orders = [];
    public $temp_equipment = [];

    protected $listeners = [
        'confirm_informe'
    ];

    public function search()
    {
        if (trim($this->search)) {

            $this->notes = Note::where('note', trim($this->search))->orWhereRelation('Orders', 'ordem', trim($this->search))->orderBy('note')->get();

            if (!$this->notes->count()) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => ' NENHUMA OBRA ENCONTRADA.',
                    'html'     => '<div class="card"><div class="card-body text-start">
                                    Não encontramos nenhuma OBRA com os dados informados. Verifique o numero digitado e tente novamente. Se acaso acreditar que possa se tratar de um erro, gentileza entrar em contato com o setor responsável.
                                </div></div>',

                ]);

                return;
            }
        }
    }

    public function addOrders()
    {
        if ($order = Order::find($this->s_order)) {
            $this->temp_orders[$order->id] = ['id' => $order->id, 'ordem' => $order->ordem];
        }
    }

    public function remOrders($index)
    {
        if (isset($this->temp_orders[$index])) {
            unset($this->temp_orders[$index]);
        }
    }

    public function addEquipment()
    {

        // dd($this->model_equipment);

        if (empty($this->temp_equipment)) {

            $this->temp_equipment[] = $this->model_equipment;
        } else {
            $add = true;

            foreach ($this->temp_equipment as $equip) {
                if ($equip['type'] == $this->model_equipment['type'] && $equip['patrimony'] == $this->model_equipment['patrimony']) {
                    $add = false;
                    break;
                }
            }

            if ($add) {
                $this->temp_equipment[] = $this->model_equipment;
            }
        }
    }

    public function remEquipment($index)
    {
        if (isset($this->temp_equipment[$index])) {
            unset($this->temp_equipment[$index]);
        }
    }

    public function confirm_informe()
    {
        $this->note = $this->preNote;
    }

    public function toConfirmWork(Note $note)
    {
        $this->preNote = $note;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'INFORMAR OBRA ' . $note->note,
            'msg'           => '
                <div class="card">
                    <div class="card-body text-start">
                       <p> Você selecionou a Nota/OV ' . $note->note . ' para informar a conclusão de obra. </p>
                        <p>É importante frisar que este canal é para informações de obras 100% concluídas. Confirmações parciais, faltantes ou conflitantes, poderá acarretar a rejeição do informe, necessitando retorno para acertos.</p>
                    </div>
                </div>
            ',
            'icon'          => 'warning',
            'btnOktxt'      => 'Continuar com Informe',
            'btnCanceltxt'  => 'Cancelar Informe',
            'action'        => 'confirm_informe',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'O Formulário foi cancelado com sucesso.',
        ]);
    }

    public function cleanAll()
    {
        $this->preNote = "";
        $this->search = "";
        $this->notes = "";
    }

    public function initForm()
    {

        $this->s_order = '';
        $this->equipment = '';
        $this->temp_orders = [];
        $this->temp_equipment = [];
        $this->form = [
            'note_id' => '',
            'company_id' => '',
            'user_id' => '', 'date' => '',
            'equipment' => false, 'connection' => false,
            'changes' => '', 'observation' => '',
            'damage' => false, 'description' => '',
            'team' => '',
            'responsible' => ''
        ];
        $this->model_equipment = [
            'type' => '',
            'patrimony' => '',
            'fases' => '',
            'pole' => '',
            'installed' => false,
        ];
    }

    public function render()
    {
        return view('livewire.partner.forms.workreports');
    }
}
