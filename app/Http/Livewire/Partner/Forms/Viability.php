<?php

namespace App\Http\Livewire\Partner\Forms;

use App\Models\Edp_depc\City;
use App\Models\{File, Form, Note, Viability as ModelsViability};
use Illuminate\Support\Facades\{Crypt, DB};
use Illuminate\Validation\ValidationException;
use Livewire\{Component, WithFileUploads};

class Viability extends Component
{
    use WithFileUploads;

    public $data;

    public $cities;

    public $changes = '';

    public $result = [];

    // Files
    public $files = [];

    public $show_files = [];

    protected $queryString = [
        'changes' => ['except' => ''],
    ];

    protected $listeners = [
        'confirm_cancelForm' => 'cancelForm',
        'confirm_save_form'  => 'saveForm',
    ];

    public function mount($id)
    {
        try {

            $this->cities = City::orderBy('cidade')->get();

        } catch (\Throwable $th) {

            $this->cities = false;
        }

        $this->result['sizechange'] = 0;

        if ($id) {
            $this->data = Note::With(['Viabilities' => function ($query) {
                $query->where('approved', false)
                    ->where('tacit', false)
                    ->where('canceled', false)
                    ->with('Order');
            }])->find(Crypt::decrypt($id));
        }

    }

    public function updatedFiles()
    {

        try {
            $this->validate([
                'files.*' => 'mimes:pdf,jpeg,png',
            ]);
        } catch (ValidationException $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'TIPO DE ARQUIVO NÃO PERMITIDO',
                'html'     => '<div class="card bg-primary text-white"><div class="card-body">Somente são aceitos arquivos: <span class="fw-bold">.pdf, .jpp ou .png</span> </div></div>',

            ]);

            return;
        }

        if (count($this->files)) {

            $this->show_files = [];

            foreach ($this->files as $index => $file) {

                $skip_file = false;

                if (!$skip_file) {

                    if (count($this->files) > 1) {

                        $name = "CROQUI-{$this->data->note}-F" . str_pad($index + 1, 2, '0', STR_PAD_LEFT) . '_' . str_pad(count($this->files), 2, '0', STR_PAD_LEFT);
                    } else {
                        $name = "CROQUI-{$this->data->note}-F01_01";
                    }

                    $this->show_files[$index] = [
                        'id'       => $index,
                        'note_id'  => '',
                        'name'     => $name,
                        'old_name' => explode('.', $file->getClientOriginalName())[0],
                        'ext'      => $file->getClientOriginalExtension(),
                        'chk'      => false,
                    ];
                }
            }

        }
    }

    public function delete_file($id)
    {
        if (isset($this->show_files[$id])) {
            unset($this->files[$id]);
            unset($this->show_files[$id]);
        }

        $this->updatedFiles();
    }

    public function updatedChanges()
    {
        $this->result = [];
    }

    public function toCancelForm()
    {
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'CANCELAR FORMULÁRIO',
            'msg'           => 'Você deseja cancelar este formulário e voltar para a listagem de viabilidade?',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Cancele e volte!',
            'btnCanceltxt'  => 'Não, Desisto',
            'action'        => 'confirm_cancelForm',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'O Formulário foi cancelado com sucesso.',
        ]);
    }

    public function toSaveForm()
    {
        $this->dispatchBrowserEvent('alertar', [
            'title' => 'ENTREGAR ANALISE DE VIABILIDADE',
            'msg'   => "<p class='fw-bold'>Você deseja entregar esta análise de viabilidade?</p>
            <p class='text-center my-2'> 
                A entrega desta analise seguirá para avaliação do corpo responsável, dependendo do resultado deste.
            </p>
            ",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Entregar Analise',
            'btnCanceltxt'  => 'Não, Desisto',
            'action'        => 'confirm_save_form',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma analise foi salva.',
        ]);
    }

    public function saveForm()
    {
        // Verify mandatory fields
        if ($this->changes) {

            $block  = false;
            $campos = [];

            if ($this->changes === 'SIM') {

                if (!isset($this->result['reason_text']) || strlen(trim($this->result['reason_text'])) < 4) {
                    $block    = true;
                    $campos[] = 'Texto Detalhamento Vazio ou Insuficiente.';
                }

                if (!isset($this->result['reason']) || trim($this->result['reason']) == '') {
                    $block    = true;
                    $campos[] = 'Motivo de Alteração não informado.';
                }

                if (!count($this->files)) {
                    $block    = true;
                    $campos[] = 'Sem Croqui Anexado.';
                }

            }

            if (!isset($this->result['responsible']) || $this->result['responsible'] == '') {
                $block    = true;
                $campos[] = 'Sem Responsável Informado.';
            }

            if (!isset($this->result['viability_id']) || $this->result['viability_id'] == '') {
                $block    = true;
                $campos[] = 'Sem Viabilidade Indicada.';
            }

            if ($block) {

                $texto = '';

                foreach ($campos as $index => $value) {
                    $texto .= '<p class="fw-bold text-start my-0 py-0">' . ($index + 1) . ' - ' . $value . '</p>';
                }

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => ' Todos os campos obigatórios precisam ser preenchidos.',
                    'html'     => '<div class="card bg-primary text-white"><div class="card-body">' . $texto . '</div></div>',

                ]);

                return;

            } else {

                $formData = [];

                if ($this->result['viability_id'] === '0') {
                    if ($this->data->Viabilities->count() > 1) {
                        foreach ($this->data->Viabilities as $vibility) {
                            $formData[] = [
                                'viability_id' => $vibility->id,
                                'user_id'      => Auth()->User()->id,
                                'description'  => $this->result['reason_text'] ?? null,
                                'changes'      => $this->result['sizechange'] ?? null,
                                'responsible'  => $this->result['responsible'] ?? null,
                                'rejected'     => $this->changes == 'SIM' ? true : false,
                                'approved'     => $this->changes == 'NAO' ? true : false,
                            ];
                        }
                    }
                } else {
                    $formData[] = [
                        'viability_id' => $this->result['viability_id'] ?? null,
                        'user_id'      => Auth()->User()->id,
                        'description'  => $this->result['reason_text'] ?? null,
                        'changes'      => $this->result['sizechange'] ?? null,
                        'responsible'  => $this->result['responsible'] ?? null,
                        'rejected'     => $this->changes == 'SIM' ? true : false,
                        'approved'     => $this->changes == 'NAO' ? true : false,
                    ];
                }

                DB::beginTransaction();

                $erro = false;

                $files_id = [];

                if (count($this->show_files)) {

                    foreach ($this->show_files as $temp_file) {

                        $caminho = '';

                        if (isset($this->files[$temp_file['id']])) {

                            $caminho = $this->files[$temp_file['id']]->store('/arquivos/croqui');

                            if ($caminho) {

                                $file = File::create([
                                    'note_id'   => $this->data->id,
                                    'user_id'   => Auth()->User()->id,
                                    'file_name' => $temp_file['name'],
                                    'path'      => $caminho,
                                    'ext'       => $temp_file['ext'],
                                ]);

                                if (!$file) {
                                    $erro = true;
                                } else {
                                    $files_id[] = $file->id;
                                }

                            }

                        }

                    }
                }

                foreach ($formData as $data) {
                    $chk_form = Form::updateOrCreate(['viability_id' => $data['viability_id']], $data)->Files()->syncWithoutDetaching($files_id);

                    if (!$chk_form) {
                        $erro = true;
                    }

                    $chk_viability = ModelsViability::find($data['viability_id'])->update([
                        'returned_at' => date('Y-m-d H:i:s'),
                        'approved'    => $data['approved'] ? $data['approved'] : false,
                        'rejected'    => $data['rejected'] ? $data['rejected'] : false,
                    ]);

                    if (!$chk_viability) {
                        $erro = true;
                    }
                }

                if (!$erro) {
                    DB::commit();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'success',
                        'title'    => 'Viabilidade Entregue com Sucesso.',
                        'timer'    => 2500,
                    ]);

                    return redirect(route('partner.todo.viability'));

                } else {
                    DB::rollBack();

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'error',
                        'title'    => 'ERRO AO PROCESSAR.',
                        'html'     => 'Ocorreu algum erro, em alguma parte do processo. Nenhuma Alteração foi realizada, e nenhuma Viabilidade foi concluída.',
                        'timer'    => 5000,
                    ]);
                }

            }

        } else {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma informação de Viabilidade encontrado para finalizar.',
                'timer'    => 2500,
            ]);

            return;
        }
    }

    public function cancelForm()
    {
        return redirect(route('partner.todo.viability'));
    }

    public function render()
    {

        return view('livewire.partner.forms.viability', [
            'note' => $this->data,
        ])->layout('layouts.forms.viability');

    }
}
