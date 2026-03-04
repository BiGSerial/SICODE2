<?php

namespace App\Http\Livewire\Components\FiveNote;

use App\Models\Company;
use App\Models\FiveNote;
use App\Models\Note;
use App\Services\D5\D5WorkflowService;
use App\Traits\WildcardFormmater;
use Livewire\Component;

class ManualCreate extends Component
{
    use WildcardFormmater;

    public ?FiveNote $five = null;
    public ?Note $note = null;
    public $search;
    public $companies;


    protected $listeners = [
        'openModal' => 'openModal',
        'refreshComponent' => '$refresh',
        '202cb962ac59075b964b07152d234b70' => 'save',
    ];


    protected $rules = [
        'five' => 'nullable|array',
        'five.note_d5' => 'required|numeric|unique:five_notes,note_d5',
        'five.loc_install' => 'required|string|max:255',
        'five.conjunto' => 'nullable|string|max:255',
        'five.pep' => 'nullable|string|max:255',
        'five.codify' => 'required|string|max:255',
        'five.sintoms' => 'nullable|string|max:255',
        'five.reason' => 'required|string|max:255',
        'five.description' => 'nullable|string',
        'five.company_id' => 'required|exists:companies,id',

    ];

    protected $messages = [
        'five.note_d5.required' => 'O campo Número da D5 é obrigatório.',
        'five.note_d5.unique' => 'O D5 informado já está em uso.',
        'five.note_d5.numeric' => 'O campo Número da D5 deve ser apenas números.',
        'five.loc_install.required' => 'O campo Local de Instalação é obrigatório.',
        'five.codify.required' => 'O campo Codificação é obrigatório.',
        'five.reason.required' => 'O campo Motivo D5 é obrigatório.',
        'five.company_id.required' => 'O campo Empresa é obrigatório.',
    ];

    public function mount()
    {
        $this->companies = Company::orderBy('name')->get();
    }

    public function searchNotes()
    {
        $this->validate([
            'search' => 'required|string|max:255',
        ]);
    }

    public function openModal()
    {
        $this->reset(['five', 'note', 'search']);

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'manualCreateFiveModal',
        ]);
    }

    public function getNotesProperty()
    {
        if ($this->search) {
            $search = $this->formatWithWildcard($this->search);
        } else {
            $search = (object)[
                'search' => '',
                'type'   => '',
            ];
        }

        return Note::where('note', $search->type, $search->search)
            ->orWhereRelation('Orders', 'ordem', $search->type, $search->search)
            ->with(['FiveNote'])->get();
    }

    public function saveD5()
    {
        $this->validate([

            'five.note_d5' => 'required|numeric|unique:five_notes,note_d5',
            'five.loc_install' => 'required|string|max:255',
            'five.conjunto' => 'nullable|string|max:255',
            'five.pep' => 'nullable|string|max:255',
            'five.codify' => 'required|string|max:255',
            'five.reason' => 'required|string|max:255',
            'five.description' => 'nullable|string',
            'five.company_id' => 'required|exists:companies,id',
        ]);




        $this->dispatchBrowserEvent('alertar', [
              'title'         => 'Criação Manual de D5',
              'msg'           => "
                  <div class='confirmation-details' style='text-align: left; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
                  <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px; border-radius: 8px 8px 0 0; margin-bottom: 16px;'>
                      <h4 style='margin: 0; font-size: 16px; font-weight: 600;'>📋 Confirmação dos Dados da D5</h4>
                  </div>
                  <div style='padding: 0 4px;'>
                      <div style='display: flex; align-items: center; margin-bottom: 12px; padding: 8px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #007bff;'>
                          <span style='color: #6c757d; min-width: 140px; font-size: 13px;'>Número da D5:</span>
                          <strong style='color: #007bff; font-size: 14px;'>{$this->five->note_d5}</strong>
                      </div>
                      <div style='display: flex; align-items: center; margin-bottom: 12px; padding: 8px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #28a745;'>
                          <span style='color: #6c757d; min-width: 140px; font-size: 13px;'>Local de Instalação:</span>
                          <strong style='color: #28a745; font-size: 14px;'>{$this->five->loc_install}</strong>
                      </div>
                      <div style='display: flex; align-items: center; margin-bottom: 12px; padding: 8px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #ffc107;'>
                          <span style='color: #6c757d; min-width: 140px; font-size: 13px;'>Conjunto:</span>
                          <strong style='color: #856404; font-size: 14px;'>" . ($this->five->conjunto ?? '<em style=\"color: #adb5bd;\">N/A</em>') . "</strong>
                      </div>
                      <div style='display: flex; align-items: center; margin-bottom: 12px; padding: 8px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #17a2b8;'>
                          <span style='color: #6c757d; min-width: 140px; font-size: 13px;'>PEP:</span>
                          <strong style='color: #17a2b8; font-size: 14px;'>" . ($this->five->pep ?? '<em style=\"color: #adb5bd;\">N/A</em>') . "</strong>
                      </div>
                      <div style='display: flex; align-items: center; margin-bottom: 12px; padding: 8px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #6f42c1;'>
                          <span style='color: #6c757d; min-width: 140px; font-size: 13px;'>Codificação:</span>
                          <strong style='color: #6f42c1; font-size: 14px;'>{$this->five->codify}</strong>
                      </div>
                      <div style='display: flex; align-items: center; margin-bottom: 12px; padding: 8px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #dc3545;'>
                          <span style='color: #6c757d; min-width: 140px; font-size: 13px;'>Motivo:</span>
                          <strong style='color: #dc3545; font-size: 14px;'>{$this->five->reason}</strong>
                      </div>
                      <div style='margin-bottom: 12px; padding: 8px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #6c757d;'>
                          <span style='color: #6c757d; font-size: 13px; display: block; margin-bottom: 4px;'>Descrição:</span>
                          <strong style='color: #495057; font-size: 14px;'>" . ($this->five->description ?? '<em style=\"color: #adb5bd;\">N/A</em>') . "</strong>
                      </div>
                      <div style='display: flex; align-items: center; padding: 12px; background: linear-gradient(135deg, #28a745, #20c997); color: white; border-radius: 6px; margin-top: 16px;'>
                          <span style='min-width: 140px; font-size: 13px; opacity: 0.9;'>📄 Nota:</span>
                          <strong style='font-size: 15px;'>{$this->note->note}</strong>
                      </div>
                  </div>
                  </div>
              ",
              'icon'          => 'warning',
              'btnOktxt'      => 'Sim, Despache!',
              'btnCanceltxt'  => 'Não, Cancele',
              'action'        => '202cb962ac59075b964b07152d234b70',
              'cancel_titulo' => 'Cancelado!',
              'cancel_msg'    => 'Nenhuma D5 foi criada.',
          ]);


    }

    public function save()
    {
        $this->five->note_id = $this->note->id;
        $this->five->save();

        app(D5WorkflowService::class)->onCreatedManual(
            $this->five,
            auth()->id(),
            null
        );


        $this->dispatchBrowserEvent('swal', [
          'position' => 'center',
          'icon'     => 'success',
          'title'    => 'Novo D5 criado com sucesso!',
          'html'     => 'Número da D5: <strong>' . $this->five->note_d5 . '</strong><br/>Nota: <strong>' . $this->note->note . '</strong>',
          'timer'    => 3000,
        ]);

        $this->emit('refreshComponent');


    }

    public function selectNote(Note $note)
    {
        $this->note = $note;
        $this->five = new FiveNote();
    }

    public function closeNote()
    {
        $this->note = null;
        $this->five = null;

        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view(
            'livewire.components.five-note.manual-create',
            [
                'availableNotes' => $this->notes,
            ]
        );
    }
}
