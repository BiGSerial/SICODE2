<?php

namespace App\Http\Livewire\Btzero\Forms;

use App\Models\Company;
use App\Models\Note;
use App\Models\Order;
use App\Models\RamalReport;
use App\Models\WorkReport;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Workreports extends Component
{
    public ?Note $note = null;
    public $preNote;
    public $notes;
    public $search;
    public $s_order;
    public bool $hasFiles = false;

    public $companies;
    public $company;

    public $equipment;
    public $meeters;
    public $model_equipment = [
        'type' => null,
        'patrimony' => null,
        'fases' => null,
        'pole' => null,
        'installed' => false,
    ];

    public $model_meeter = [
        'number' => null,
        'borne' => null,
        'fases' => null,
    ];

    public $form = [
        'note_id' => null,
        'company_id' => null,
        'user_id' => null,
        'date' => null,
        'equipment' => null,
        'connection' => null,
        'changes' => null,
        'observation' => null,
        'damage' => null,
        'description' => '',
        'team' => null,
        'responsible' => null
    ];

    public $temp_orders = [];
    public $temp_equipment = [];
    public $temp_meeters = [];

    protected $listeners = [
        'confirm_informe',
        'send_informe',
        'hasFile',
        'savedFiles',
    ];

    protected $rules = [
        'company' => 'required',
        'form.date' => 'nullable|date|before_or_equal:today',
        'form.equipment' => 'required|boolean',
        // 'form.changes' => 'boolean',
        'form.observation' => 'nullable|string|max:5000',
        // 'form.damage' => 'nullable|boolean',
        'form.description' => 'nullable|string|min:10|max:5000',
        'form.connection' => 'nullable|boolean',
        'form.team' => 'nullable|string|max:255',
        'form.dd' => 'nullable|string|max:255',
        'form.responsible' => 'nullable|string|max:255',
        'form.informer' => 'nullable|string|max:255',

    ];

    public function messages()
    {
        return [
            'company.required' => 'O campo [Empresa] é obrigatório.',
            'form.date.required' => 'O campo [data de conclusão] é obrigatório.',
            'form.date.date' => 'O campo [data de conclusão] deve ser uma data válida.',
            'form.date.before_or_equal' => 'O campo data de conclusão deve ser uma data anterior ou igual a hoje.',
            'form.equipment.required' => 'O campo [Equipamento Instalados] é obrigatório.',
            'form.equipment.boolean' => 'O campo [Equipamento Instalados] deve ser verdadeiro ou falso.',
            'form.changes.required' => 'O campo [Houveram Mudanças] é obrigatório.',
            'form.changes.boolean' => 'O campo [Houveram Mudanças] deve ser verdadeiro ou falso.',
            'form.observation.string' => 'O campo [Observações] deve ser uma string.',
            'form.damage.required' => 'O campo [Houve Dano] é obrigatório.',
            'form.damage.boolean' => 'O campo [Houve Dano] deve ser verdadeiro ou falso.',
            'form.description.required_if' => 'A descrição do DANO é obrigatório quado for informado a existência de DANO.',
            'form.description.string' => 'O campo [Descrição] deve ser uma string.',
            'form.connection.required' => 'O campo [Houve Ligação] é obrigatório.',
            'form.connection.boolean' => 'O campo [Houve Ligação] deve ser verdadeiro ou falso.',
            'form.meeters.required' => 'O campo [Medidores Istalados] é obrigatório.',
            'form.meeters.boolean' => 'O campo [Medidores Istalados] deve ser verdadeiro ou falso.',
            'form.dd.required' => 'O campo [Numero da DD] é obrigatório.',
            'form.dd.string' => 'O campo [Numero da DD] deve ser uma string.',
            'form.team.required' => 'O campo [Nome da Equipe] é obrigatório.',
            'form.team.string' => 'O campo [Nome da Equipe] deve ser uma string.',
            'form.responsible.required' => 'O campo [Encarregado Responsável] é obrigatório.',
            'form.responsible.string' => 'O campo [Encarregado Responsável] deve ser uma string.',
            'form.informer.required' => 'O campo [Informante Responsável] é obrigatório.',
            'form.informer.string' => 'O campo [Informante Responsável] deve ser uma string.',
        ];
    }

    /**
     * Recebe evento oriundo de um componente Livewire informando que existe arquivo carregado.
     * @param [bool] $hasFile
     */
    public function hasFile(bool $hasFile)
    {
        $this->hasFiles = $hasFile;
    }

    public function mount()
    {
        $this->companies = Company::orderBy('name')->get();
    }

    public function savedFiles()
    {
        // Revebe chamado pelo Component de Arquivos;
        $this->emitTo('files.manager.create-gen-files', 'cleanFiles');
        $this->note = null;
        $this->cleanAll();
        $this->initForm();

    }

    public function search()
    {
        if (trim($this->search)) {

            $this->notes = Note::where('note', trim($this->search))
                            ->orWhereRelation('Orders', 'ordem', trim($this->search))
                            ->with('WorkForm', 'RamalForm')
                            ->orderBy('note')
                            ->get();

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

    public function submit()
    {

        try {

            $this->validate();


            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'CONFIRMAR INFORME DE PATRIMONIO SMC ' . $this->note->note,
                'msg'           => '
                    <div class="card">
                        <div class="card-body text-start">
                           <p>Você está preste a confirmar os patrimônios para a obra ' . $this->note->note . '. Este procedimento liberará a etapa de Publicação. Porém a mesma não terá confirmação da 20 até o informe de conclusão da Obra.</p>
                           <h4>Gostaria realmente de confirmar o informe de patrimônios para esta OBRA?</h4>
                        </div>
                    </div>
                ',
                'icon'          => 'warning',
                'btnOktxt'      => 'Confirmar',
                'btnCanceltxt'  => 'Cancelar',
                'action'        => 'send_informe',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Cancelado a Confirmação do Formulário.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            $html = '<ul>';
            foreach ($errors as $error) {
                $html .= '<li>' . $error . '</li>';
            }
            $html .= '</ul>';

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => '<div class="card"><div class="card-body text-start">' . $html . '</div></div>',
            ]);

            // $this->resetErrorBag();
        }
    }

    public function send_informe()
    {
        $this->form['note_id'] = $this->note->id;
        $this->form['company_id'] = Auth()->User()->Employee->Contract->company->id;
        $this->form['user_id'] = Auth()->User()->id;
        $this->form['informed_at'] = date('Y-m-d H:i:s');

        if ($this->form['equipment'] == true && empty($this->temp_equipment)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => 'Não foram relacionados os Equipementos Instalados ou Removidos',
            ]);

            return;
        }

        if (!$this->company) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => 'É Obrigatório informar a Empresa Responsável pela Obra.',
            ]);

            return;
        }

        // if ($this->form['damage'] == true && !trim($this->form['description'])) {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon'     => 'warning',
        //         'title'    => 'Erros de Validação',
        //         'html'     => 'O Detalhamento dos Danos Causados é Obrigatório.',
        //     ]);

        //     return;
        // }

        // if ($this->form['equipment'] == true && empty($this->temp_equipment)) {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon'     => 'warning',
        //         'title'    => 'Erros de Validação',
        //         'html'     => 'Os equipamentos Instalados/Desinstalados é obrigatório sua informação.',
        //     ]);

        //     return;
        // }

        // dd($this->form['changes'], $this->hasFiles);

        // if ($this->form['changes'] == true && !$this->hasFiles) {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon'     => 'warning',
        //         'title'    => 'Erros de Validação',
        //         'html'     => 'É obrigatório anexar o AsBuilt da Obra Executada. (apenas PDF)',
        //     ]);

        //     return;
        // }

        // if ($this->meeters == true && empty($this->temp_meeters)) {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon'     => 'warning',
        //         'title'    => 'Erros de Validação',
        //         'html'     => 'É obrigatório a informação dos medidores instalados.',
        //     ]);

        //     return;
        // }

        DB::beginTransaction();

        try {

            $form = RamalReport::updateOrCreate(
                ['note_id' => $this->form['note_id']],
                [
                    'user_id' => Auth()->User()->id,
                    'company_id' => $this->company,
                    'equipment' => $this->form['equipment'],
                    'connection' => $this->form['connection'],
                    'changes' => $this->form['changes'],
                    'observation' => $this->form['observation'],
                    'informed_at' => date('Y-m-d H:i:s'),
                ]
            );

            if ($form) {

                if (!empty($this->temp_orders)) {

                    $ordersId = [];

                    foreach ($this->temp_orders as $order) {
                        $ordersId[] = $order['id'];
                    }

                    $form->Orders()->sync($ordersId);
                }

                if ($form->equipment && !empty($this->temp_equipment)) {
                    foreach ($this->temp_equipment as $equipment) {
                        $form->BtzeroEquipment()->create($equipment);
                    }
                }

                // if (!empty($this->temp_meeters)) {
                //     foreach ($this->temp_meeters as $meeter) {
                //         $form->Meeters()->create($meeter);
                //     }
                // }

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Informe Entregue com Sucesso',
                ]);



                DB::commit();

                if ($this->hasFiles) {

                    // Emite comando SAVE para o componente Laravel.
                    $this->emitTo('files.manager.create-gen-files', 'saveFiles');

                    return;
                } else {
                    $this->note = null;
                    $this->cleanAll();
                    $this->initForm();
                }

                // return;


            }
        } catch (\Throwable $th) {

            DB::rollback();

            dd($th->getMessage());
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Ocorreu algum erro ao enviar o form. Verifique e tente mais tarde.',
            ]);
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

        if (!empty($this->model_equipment)) {
            foreach ($this->model_equipment as $key => $value) {
                if ((!isset($value) || $value == '') && $key != 'pole') {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'Erro de Validação Equipamentos',
                        'html'     => 'Todos os campos são obrigatórios.',
                    ]);

                    return;
                }
            }
        }

        if (empty($this->temp_equipment)) {


            $this->temp_equipment[] = array_map('trim', $this->model_equipment);
        } else {
            $add = true;

            $this->model_equipment = array_map('trim', $this->model_equipment);
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

    public function addMeeters()
    {

        // dd($this->model_equipment);

        if (empty($this->temp_meeters)) {

            $this->temp_meeters[] = array_map('trim', $this->model_meeter);
        } else {
            $add = true;
            $this->model_meeter = array_map('trim', $this->model_meeter);
            foreach ($this->temp_meeters as $equip) {
                if ($equip['number'] == $this->model_meeter['number']) {
                    $add = false;
                    break;
                }
            }

            if ($add) {
                $this->temp_meeters[] = $this->model_meeter;
            }
        }
    }

    public function remMeeters($index)
    {
        if (isset($this->temp_meeters[$index])) {
            unset($this->temp_meeters[$index]);
        }
    }

    public function confirm_informe()
    {
        $this->note = $this->preNote;


        $filteredOrders = $this->note->Orders->filter(function ($order) {
            return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
        });


        if (count($filteredOrders)) {
            foreach ($filteredOrders as $order) {
                $this->temp_orders[$order->id] = ['id' => $order->id, 'ordem' => $order->ordem];
            }
        }

        // if ($this->note->type_note == 2 && $this->note->Orders->count() > 1) {

        //     if ($filteredOrders->count() == 1) {
        //         foreach ($filteredOrders as $order) {
        //             $this->temp_orders[$order->id] = ['id' => $order->id, 'ordem' => $order->ordem];
        //         }
        //     }
        // } elseif ($this->note->type_note == 1) {

        //     foreach ($filteredOrders as $order) {
        //         $this->temp_orders[$order->id] = ['id' => $order->id, 'ordem' => $order->ordem];
        //     }
        // }
    }

    public function toConfirmWork(Note $note)
    {
        if ($note->RamalForm) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOOPS!',
                'html'     => '<div class="card text-bg-danger"><div class="card-body text-center">Não é possível informar novamente essa obra.</div></div>',
            ]);

            return;
        }

        $this->preNote = $note;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'INFORMAR EQUIPAMENTOS PARA ' . $note->note,
            'msg'           => '
                <div class="card">
                    <div class="card-body text-start">
                       <p> Você selecionou a Nota/OV ' . $note->note . ' para informar. </p>
                        <p>É importante lembrar que este informe de equipamentos irá liberar a etapa de publicação.</p>
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

    public function calcelForm()
    {

        $this->initForm();
        $this->cleanAll();
        $this->note = null;
    }

    public function initForm()
    {

        $this->s_order = '';
        $this->equipment = '';
        $this->temp_orders = [];
        $this->temp_equipment = [];
        $this->form = [
            'note_id' => null,
            'company_id' => null,
            'user_id' => null,
            'date' => null,
            'equipment' => null,
            'connection' => null,
            'changes' => null,
            'observation' => null,
            'damage' => null,
            'description' => null,
            'team' => null,
            'responsible' => null
        ];
        $this->model_equipment = [
            'type' => null,
            'patrimony' => null,
            'fases' => null,
            'pole' => null,
            'installed' => null,
        ];

        $this->model_meeter = [
            'number' => null,
            'borne' => null,
            'fases' => null,
        ];


    }


    public function render()
    {
        return view('livewire.btzero.forms.workreports');
    }
}
