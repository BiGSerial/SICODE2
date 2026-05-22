<?php

namespace App\Http\Livewire\Partner\Forms;

use App\Models\Note;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkReport;
use App\Models\Company;
use App\Services\Partner\BlockEvaluator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Workreports extends Component
{
    public bool $requireFilesForSubmit = true;
    public bool $canSelectCompany = false;
    public $companies = [];

    public ?Note $note = null;
    public ?WorkReport $workReport = null;
    public $preNote;
    public $notes;
    public $search;
    public $s_order;
    public bool $hasFiles = false;
    public bool $hasAsbuilt = false;
    public bool $hasPendingAsbuilt = false;
    public bool $hasEvidenceFile = false;
    public bool $showAsbuiltMissingFeedback = false;
    public $hasPartial;
    public $acceptance_meta_json;


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
        'responsible' => null,
        'acceptance_accepted' => false,
        'acceptance_name' => null,
        'asbuilt_confirmation' => false,
    ];

    public $temp_orders = [];
    public $temp_equipment = [];
    public $temp_meeters = [];

    protected $listeners = [
        'confirm_informe',
        'send_informe',
        'hasFile',
        'hasAsbuilt',
        'hasPendingAsbuilt',
        'hasEvidenceFile',
        'savedFiles',
    ];

    protected $rules = [
        'form.company_id' => 'nullable',
        'form.date' => 'required|date|before_or_equal:today',
        'form.equipment' => 'required|boolean',
        'form.changes' => 'required|boolean',
        'form.observation' => 'nullable|string|max:5000',
        'form.damage' => 'required|boolean',
        'form.description' => 'required_if:form.damage,1|nullable|string|min:10|max:5000',
        'form.connection' => 'required|boolean',
        'form.team' => 'required|string|max:255',
        'form.dd' => 'required|string|max:255',
        'form.responsible' => 'required|string|max:255',
        'form.informer' => 'required|string|max:255',
        'form.acceptance_accepted' => 'accepted',
        'form.acceptance_name' => 'required|string|max:255',
        'form.asbuilt_confirmation' => 'nullable|boolean',

    ];

    public function mount()
    {
        $this->loadCompanies();
    }

    public function messages()
    {
        return [
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
            'form.acceptance_accepted.accepted' => 'Você precisa aceitar o termo de responsabilidade do informe.',
            'form.acceptance_name.required' => 'Informe o nome completo para o aceite do termo.',
            'form.acceptance_name.string' => 'O campo [Nome do Aceite] deve ser uma string.',
            'form.asbuilt_confirmation.accepted' => 'Confirme que o ASBUILT anexado corresponde à informação declarada sobre alteração de projeto.',
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

    public function hasAsbuilt(bool $hasAsbuilt)
    {
        $this->hasAsbuilt = $hasAsbuilt;

        if ($hasAsbuilt) {
            $this->showAsbuiltMissingFeedback = false;
        }
    }

    public function hasPendingAsbuilt(bool $hasPendingAsbuilt)
    {
        $this->hasPendingAsbuilt = $hasPendingAsbuilt;
    }

    public function hasEvidenceFile(bool $hasEvidenceFile)
    {
        $this->hasEvidenceFile = $hasEvidenceFile;
    }

    public function savedFiles()
    {
        // Revebe chamado pelo Component de Arquivos;
        $this->emitTo('files.manager.create-gen-files', 'cleanFiles');
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Informe entregue com sucesso',
        ]);
        $this->note = null;
        $this->cleanAll();
        $this->initForm();

    }

    public function search()
    {
        if (trim($this->search)) {


            // NOTE: Implementar restriçao para evitar enviar Informe de Obra Final sem STATUS contrataçao.
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

    public function submit()
    {
        if (!$this->canInformNote($this->note)) {
            return;
        }

        if ($this->canSelectCompany && empty($this->form['company_id'])) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Empreiteira obrigatória',
                'html'     => 'Selecione a empreiteira responsável por este informe.',
            ]);
            return;
        }

        if ($this->requiresAsbuiltForSubmit() && !$this->hasAsbuilt) {
            $this->showMissingAsbuiltFeedback();
            return;
        }

        if ($this->requireFilesForSubmit && !$this->hasEvidenceFile) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Arquivos Obrigatórios',
                'html'     => 'Desde do dia <strong>01/05/2025</strong>, tornou-se obrigatório o anexo de imagens evidenciando a obra, incluindo os ativos cadastrados. Favor anexar os arquivos antes de prosseguir.',
            ]);
            return;
        }

        if ($this->requiresAsbuiltConfirmation() && !$this->asBool($this->form['asbuilt_confirmation'] ?? false)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Confirmação do ASBUILT obrigatória',
                'html'     => 'Confirme que o ASBUILT anexado corresponde à informação declarada sobre alteração ou não alteração do projeto.',
            ]);
            return;
        }


        try {

            $this->validate();


            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'CONFIRMAR CONCLUSÂO OBRA ' . $this->note->note,
                'msg'           => '
                    <div class="card">
                        <div class="card-body text-start">
                           <p>Você está preste a confirmar a obra ' . $this->note->note . '. Reforçamos que a confirmação PARCIAL da obra poderá acarretar atrasos, incluindo qualquer recursos oriundo em depedência deste informa.</p>
                           <p>Você também confirmou que o ASBUILT anexado corresponde à informação declarada sobre alteração de projeto.</p>
                           <h4>Gostaria realmente de confirmar a conclusão desta OBRA?</h4>
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
        if (!$this->canInformNote($this->note)) {
            return;
        }

        $this->form['note_id'] = $this->note->id;
        if ($this->canSelectCompany) {
            $this->form['company_id'] = $this->form['company_id'] ?: null;
        } else {
            $this->form['company_id'] = Auth()->User()->Employee->Contract->company->id;
        }
        $this->form['user_id'] = Auth()->User()->id;
        $this->form['informed_at'] = date('Y-m-d H:i:s');
        $this->form['acceptance_at'] = date('Y-m-d H:i:s');
        $this->form['acceptance_meta'] = $this->buildAcceptanceMeta();

        if ($this->form['equipment'] == true && empty($this->temp_equipment)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => 'Não foram relacionados os Equipementos Instalados ou Removidos',
            ]);

            return;
        }

        if ($this->form['damage'] == true && !trim($this->form['description'])) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => 'O Detalhamento dos Danos Causados é Obrigatório.',
            ]);

            return;
        }

        if ($this->form['equipment'] == true && empty($this->temp_equipment)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => 'Os equipamentos Instalados/Desinstalados é obrigatório sua informação.',
            ]);

            return;
        }

        // dd($this->form['changes'], $this->hasFiles);

        if ($this->requiresAsbuiltForSubmit() && !$this->hasAsbuilt) {
            $this->showMissingAsbuiltFeedback();
            return;
        }

        if ($this->requireFilesForSubmit && !$this->hasEvidenceFile) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Arquivos Obrigatórios',
                'html'     => 'Desde do dia <strong>01/05/2025</strong>, tornou-se obrigatório o anexo de imagens evidenciando a obra, incluindo os ativos cadastrados. Favor anexar os arquivos antes de prosseguir.',
            ]);

            return;
        }

        if ($this->requiresAsbuiltConfirmation() && !$this->asBool($this->form['asbuilt_confirmation'] ?? false)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => 'Confirme que o ASBUILT anexado corresponde à informação declarada sobre alteração ou não alteração do projeto.',
            ]);

            return;
        }

        if ($this->meeters == true && empty($this->temp_meeters)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Erros de Validação',
                'html'     => 'É obrigatório a informação dos medidores instalados.',
            ]);

            return;
        }

        DB::beginTransaction();

        try {

            $form = WorkReport::updateOrCreate(
                ['note_id' => $this->form['note_id'], 'canceled' => false],
                $this->form
            );

            if ($form) {

                $form->informed_at = date('Y-m-d H:i:s');
                $form->save();

                if (!empty($this->temp_orders)) {

                    $ordersId = [];

                    foreach ($this->temp_orders as $order) {
                        $ordersId[] = $order['id'];
                    }

                    $form->Orders()->sync($ordersId);
                }

                if ($form->equipment && !empty($this->temp_equipment)) {
                    foreach ($this->temp_equipment as $equipment) {
                        $form->Equipment()->create($equipment);
                    }
                }

                if (!empty($this->temp_meeters)) {
                    foreach ($this->temp_meeters as $meeter) {
                        $form->Meeters()->create($meeter);
                    }
                }


                if ($this->hasPartial) {

                    $user = User::first();

                    $this->hasPartial->update([
                        'complete' => true,
                        'allow' => false,
                        'deny' => true,
                        'engineer_id' => $user->id,
                        'decision_at' => now(),
                        'engineer_info' => 'Parcial cancelada automaticamente devido a entrada do informe final. (System Info)',
                    ]);
                }

                DB::commit();

                if ($this->hasFiles) {
                    $this->workReport = $form;
                    $this->emitTo('files.manager.create-gen-files', 'setWorkReportId', $form->id);

                    // Emite comando SAVE para o componente Laravel.
                    $this->emitTo('files.manager.create-gen-files', 'saveFiles');

                    return;
                } else {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'success',
                        'title'    => 'Informe entregue com sucesso',
                    ]);
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
                if (!isset($value) || $value == '') {
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

    private function getPositionPartial($note)
    {
        $partialOpen = $note->Partials()->where('complete', false)->first();

        if ($partialOpen) {
            if ($partialOpen->allow) {
                return 'EM FISCALIZÇÃO';
            }

            if ($partialOpen->supervision) {
                return 'EM PAGAMENTO';
            }

            if ($partialOpen->deny) {
                return false;
            }
        }

        return 'APROVAÇÂO DO ENGENHEIRO';
    }

    public function toConfirmWork(Note $note)
    {
        $this->preNote = $note;

        if (!$this->canInformNote($this->preNote)) {
            return;
        }


        $eval = (new BlockEvaluator())->evaluate($this->preNote);

        if (!$eval->command) {
            $productionInfo = '';
            if (isset($eval->production) && $eval->production) {
                $userName = $eval->production->user->name ?? 'Não informado';
                $serviceName = $eval->production->service->service ?? 'Não informado';
                $productionInfo = "<div class='alert alert-info'>
                <strong>Resposável:</strong><br>
                Usuário: {$userName}<br>
                Serviço: {$serviceName}
            </div>";
            }

            $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'OBRA BLOQUEADA',
            'html'     => "<div class='card'>
                <div class='card-body'>
                    <h5 class='card-title'>Obra: <strong>{$this->preNote->note}</strong></h5>
                    <div class='alert alert-danger'>
                    <strong>Motivo do bloqueio:</strong><br>
                    {$eval->reason}
                    </div>
                    {$productionInfo}
                </div>
                </div>",
            ]);

            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'INFORMAR OBRA ' . $note->note,
            'msg'           => '
                <div class="card">
                    <div class="card-body text-start">
                        <h5 class="mb-3">Nota/OV: <strong>' . $note->note . '</strong></h5>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Esta obra foi selecionada para informar conclusão.
                        </div>
                        
                        <div class="alert alert-warning">
                            <strong>Atenção!</strong>
                            <ul class="mb-0 mt-2">
                                <li>Este canal é para obras <strong>100% concluídas</strong>.</li>
                                <li>Confirmações parciais, faltantes ou conflitantes poderão ser rejeitadas.</li>
                                <li>Em caso de rejeição, será necessário retorno para acertos.</li>
                            </ul>
                        </div>
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
        $this->hasPartial = "";
        $this->workReport = null;
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
        $this->hasFiles = false;
        $this->hasAsbuilt = false;
        $this->hasPendingAsbuilt = false;
        $this->hasEvidenceFile = false;
        $this->showAsbuiltMissingFeedback = false;
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
            'responsible' => null,
            'acceptance_accepted' => false,
            'acceptance_name' => null,
            'asbuilt_confirmation' => false,
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

    protected function buildAcceptanceMeta(): array
    {
        $meta = [];

        if (is_string($this->acceptance_meta_json) && trim($this->acceptance_meta_json) !== '') {
            $decoded = json_decode($this->acceptance_meta_json, true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        $meta['server_ip'] = request()->ip();
        $meta['server_host'] = request()->getHost();
        $meta['server_user_agent'] = request()->userAgent();
        $meta['captured_at'] = now()->toDateTimeString();
        $meta['asbuilt_confirmation'] = [
            'confirmed' => $this->requiresAsbuiltConfirmation() && $this->asBool($this->form['asbuilt_confirmation'] ?? false),
            'has_asbuilt' => $this->hasAsbuilt,
            'project_changes_declared' => $this->normalizeNullableBool($this->form['changes'] ?? null),
            'message' => 'Usuário confirmou visualmente que o ASBUILT anexado corresponde à informação declarada sobre alteração ou não alteração do projeto.',
        ];
        $meta['app_user'] = [
            'id' => auth()->id(),
            'name' => auth()->user()?->name,
            'email' => auth()->user()?->email,
        ];

        return $meta;
    }


    public function render()
    {
        return view('livewire.partner.forms.workreports');
    }

    protected function loadCompanies(): void
    {
        if (!$this->canSelectCompany) {
            $this->companies = [];
            return;
        }

        $this->companies = Company::query()
            ->orderByRaw('LOWER(name)')
            ->get(['id', 'name']);
    }

    protected function canInformNote(?Note $note): bool
    {
        if (!$note) {
            return false;
        }

        $freshNote = Note::query()->find($note->id);
        if (!$freshNote || $freshNote->canceled) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'OBRA CANCELADA',
                'html'     => 'Não é permitido informar obra para uma nota cancelada.',
            ]);
            return false;
        }

        return true;
    }

    protected function requiresAsbuiltForSubmit(): bool
    {
        return $this->requireFilesForSubmit;
    }

    protected function showMissingAsbuiltFeedback(): void
    {
        $this->showAsbuiltMissingFeedback = true;

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'warning',
            'title'    => 'ASBUILT obrigatório',
            'html'     => '<div class="text-start">
                <div class="alert alert-danger py-2 mb-3">
                    <strong>Você não anexou o ASBUILT.</strong>
                </div>
                <div class="border rounded p-3 mb-3 bg-light">
                    <p class="mb-2">No campo <strong>Tipo de Envio</strong>, selecione <strong>ASBUILT</strong> e anexe o arquivo de acordo com a informação declarada em <strong>Houve Alterações no projeto?</strong>.</p>
                    <ul class="mb-0 ps-3">
                        <li class="mb-2"><strong>Se houve alteração:</strong> anexe o ASBUILT com as alterações executadas.</li>
                        <li><strong>Se não houve alteração:</strong> o executor declara, sob sua responsabilidade, que a obra foi executada conforme o projeto original, devendo anexar o projeto seguido da informação <strong>executado conforme projeto</strong> registrada no ASBUILT.</li>
                    </ul>
                </div>
                <div class="alert alert-warning py-2 mb-0">
                    Informações divergentes da execução realizada em campo poderão acarretar retrabalho, reprovação no encerramento da obra e aplicação das tratativas e sanções cabíveis.
                </div>
            </div>',
        ]);
    }

    protected function requiresAsbuiltConfirmation(): bool
    {
        return $this->requiresAsbuiltForSubmit() && $this->hasAsbuilt;
    }

    public function shouldShowAsbuiltConfirmation(): bool
    {
        return $this->requiresAsbuiltConfirmation();
    }

    protected function asBool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected function normalizeNullableBool($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->asBool($value);
    }
}
