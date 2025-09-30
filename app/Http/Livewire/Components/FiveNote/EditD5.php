<?php

namespace App\Http\Livewire\Components\FiveNote;

use App\Models\Company;
use App\Models\FiveNote;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EditD5 extends Component
{
    public ?FiveNote $five = null;
    public $note;
    public $companies;
    public $resend = false;

    protected $listeners = [
        'getInfoResponse',
        'samuca23212Encerrar' => 'save',
    ];

    public function mount()
    {
        $this->companies = Company::orderBy('name')->get();
    }

    protected function rules()
    {
        return [
            'five.note_d5'         => ['nullable','string','max:191'],
            'five.note_id'         => ['nullable','integer'],
            'five.loc_install'     => ['nullable','string','max:191'],
            'five.conjunto'        => ['nullable','string','max:191'],
            'five.pep'             => ['nullable','string','max:191'],
            'five.e_pep'           => ['nullable','string','max:191'],
            'five.codify'          => ['nullable','string','max:191'],
            'five.sintoms'         => ['nullable','string'],
            'five.reason'          => ['nullable','string','max:191'],
            'five.description'     => ['nullable','string'],
            'five.name'            => ['nullable','string','max:191'],
            'five.dispatch_at'     => ['nullable','date'],
            'five.visible_partner' => ['boolean'],
            'five.is_completed'    => ['boolean'],
            'five.completed_at'    => ['nullable','date'],
            'five.is_supervisioned' => ['boolean'],
            'five.supervisioned_at' => ['nullable','date'],
            'five.is_payed'        => ['boolean'],
            'five.payed_at'        => ['nullable','date'],
            'five.is_archived'     => ['boolean'],
            'five.company_id'     => ['nullable','string','exists:companies,id'],
        ];
    }

    public function getInfoResponse(FiveNote $five)
    {
        $this->five = $five;

        if ($this->five) {

            if (!$this->five->loc_install) {

                $this->five->loc_install = $this->five->Note->WorkForm?->Orders->sortBy('ordem')->first()?->loc_install ?? null;
            }

            $this->note = $this->five->Note;


            $this->dispatchBrowserEvent('showModal', [
                'id' => 'editFiveModal',
            ]);
        }
    }

    public function toSave()
    {



        if ($this->resend) {
            $send = "<div style='padding: 15px; background: linear-gradient(135deg, #dc3545, #ff4d4d); color: white; border-radius: 6px; margin-bottom: 15px; border: 2px solid #721c24;'>
                        <h5 style='margin-top: 0; text-align: center; font-weight: 700;'>⚠️ ATENÇÃO: REENVIO DE D5 ⚠️</h5>
                        <p style='text-align: center; margin-bottom: 5px;'>Você selecionou <strong>REENVIAR</strong> esta D5!</p>
                        <p style='text-align: justify; font-weight: 500;'>Esta ação <span style='text-decoration: underline; font-weight: 700;'>removerá todas as Produções Pendentes</span> e retornará o item para a pilha da empreiteira selecionada.</p>
                    </div>
                  
                       ";
        } else {
            $send = "<div style='padding: 15px; background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; border-radius: 6px; margin-bottom: 15px; border: 2px solid #084298;'>
                        <h5 style='margin-top: 0; text-align: center; font-weight: 700;'>ℹ️ ALTERAÇÃO DE D5 ℹ️</h5>
                        <p style='text-align: center; margin-bottom: 5px;'>Você está apenas atualizando esta D5.</p>
                        <p style='text-align: justify; font-weight: 500;'>As informações originais serão substituídas pelas novas informações inseridas.</p>
                    </div>
                   ";
        }

        $this->dispatchBrowserEvent('alertar', [
              'title'         => 'Deseja alterar a D5',
              'msg'           => "
                  <div class='confirmation-details' style='text-align: left; font-family: system-ui, sans-serif;'>
                      <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px; border-radius: 8px; margin-bottom: 20px; text-align: center;'>
                          <h4 style='margin: 0; font-size: 18px; font-weight: 600;'>📋 Confirmação dos Dados da D5</h4>
                      </div>
                      <div style='display: grid; gap: 12px;'>
                          <div style='display: flex; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #007bff;'>
                              <span style='color: #6c757d; font-weight: 500;'>Número da D5:</span>
                              <strong style='color: #000;'>{$this->five->note_d5}</strong>
                          </div>
                          <div style='display: flex; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #fd7e14;'>
                              <span style='color: #6c757d; font-weight: 500;'>Empresa:</span>
                              <strong style='color: #000;'>" . ($this->five->company->name ?? 'N/A') . "</strong>
                          </div>
                          <div style='display: flex; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #28a745;'>
                              <span style='color: #6c757d; font-weight: 500;'>Local:</span>
                              <strong style='color: #000;'>{$this->five->loc_install}</strong>
                          </div>
                          <div style='display: flex; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #ffc107;'>
                              <span style='color: #6c757d; font-weight: 500;'>Conjunto:</span>
                              <strong style='color: #000;'>" . ($this->five->conjunto ?? 'N/A') . "</strong>
                          </div>
                          <div style='display: flex; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #17a2b8;'>
                              <span style='color: #6c757d; font-weight: 500;'>PEP:</span>
                              <strong style='color: #000;'>" . ($this->five->pep ?? 'N/A') . "</strong>
                          </div>
                          <div style='display: flex; justify-content: space-between; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #6f42c1;'>
                              <span style='color: #6c757d; font-weight: 500;'>Codificação:</span>
                              <strong style='color: #000;'>{$this->five->codify}</strong>
                          </div>
                          <div style='padding: 12px; background: linear-gradient(135deg, #28a745, #20c997); color: white; border-radius: 6px; text-align: center;'>
                              <span style='font-weight: 500; opacity: 0.9;'>📄 Nota: </span>
                              <strong style='color: #fff;'>{$this->note->note}</strong>
                          </div>
                          {$send}
                      </div>
                  </div>
              ",
              'icon'          => 'warning',
              'btnOktxt'      => 'Sim, Altere!',
              'btnCanceltxt'  => 'Não, Cancele',
              'action'        => 'samuca23212Encerrar',
              'cancel_titulo' => 'Cancelado!',
              'cancel_msg'    => 'Nenhuma D5 foi Alterada.',
          ]);
    }

    public function save()
    {



        DB::beginTransaction();

        try {

            $this->validate();

            $this->five->save();

            // Feedback e fechamento do modal (Bootstrap)
            $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'D5 Alterado com sucesso!',
            'html'     => 'Número da D5: <strong>' . $this->five->note_d5 . '</strong><br/>Nota: <strong>' . $this->note->note . '</strong>',
            'timer'    => 3000,
            ]);

            DB::commit();

            $this->closeAll();

        } catch (\Throwable $th) {

            DB::rollBack();

            $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'Erro ao alterar D5!',
            'html'     => "
                <div class='error-card' style='text-align: left; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
                    <div style='background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 12px; border-radius: 8px 8px 0 0; margin-bottom: 16px;'>
                        <h4 style='margin: 0; font-size: 16px; font-weight: 600;'>❌ Erro ao Alterar D5</h4>
                    </div>
                    <div style='padding: 0 4px;'>
                        <div style='display: flex; align-items: center; margin-bottom: 12px; padding: 8px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #007bff;'>
                            <span style='color: #6c757d; min-width: 140px; font-size: 13px;'>Número da D5:</span>
                            <strong style='color: #007bff; font-size: 14px;'>{$this->five->note_d5}</strong>
                        </div>
                        <div style='display: flex; align-items: center; margin-bottom: 16px; padding: 8px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #28a745;'>
                            <span style='color: #6c757d; min-width: 140px; font-size: 13px;'>Nota:</span>
                            <strong style='color: #28a745; font-size: 14px;'>{$this->note->note}</strong>
                        </div>
                        <div style='padding: 12px; background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-radius: 6px; border-left: 4px solid #721c24;'>
                            <span style='font-size: 13px; display: block; margin-bottom: 4px; opacity: 0.9;'>🚨 Mensagem de Erro:</span>
                            <strong style='font-size: 14px;'>{$th->getMessage()}</strong>
                        </div>
                    </div>
                </div>
            ",

            ]);
            //throw $th;
        }

    }

    public function closeAll()
    {
        $this->dispatchBrowserEvent('hideModal');
        $this->resetErrorBag();
        $this->five = null;
        $this->resend = false;
        $this->emitUp('refresh_list');

    }



    public function render()
    {
        return view('livewire.components.five-note.edit-d5');
    }
}
