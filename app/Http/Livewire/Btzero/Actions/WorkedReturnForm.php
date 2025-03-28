<?php

namespace App\Http\Livewire\Btzero\Actions;

use App\Models\Equipment;
use App\Models\Production;
use App\Models\RamalReport;
use Livewire\Component;

class WorkedReturnForm extends Component
{
    public ?RamalReport $workReport = null;

    public $pag = 0;
    public bool $hasFile = false;


    protected $listeners = [
        'tefresh_form' => '$refresh',
        'toReturnWork',
        'hasFile',
        'savedFiles',
        'confirm_workform' => 'save',
        'closeAll'
    ];

    protected $rules = [
        // 'workReport.date' => 'required|date|before_or_equal:today',
        // 'workReport.equipment' => 'required|boolean',
        // 'workReport.changes' => 'required|boolean',
        'workReport.observation' => 'nullable|string|max:5000',
        // 'workReport.damage' => 'required|boolean',
        // 'workReport.description' => 'required_if:workReport.damage,1|nullable|string|min:10|max:5000',
        // 'workReport.connection' => 'required|boolean',
        // 'workReport.team' => 'required|string|max:255',
        // 'workReport.dd' => 'required|string|max:255',
        // 'workReport.responsible' => 'required|string|max:255',
        // 'workReport.informer' => 'required|string|max:255',

    ];

    public function hasFile(bool $hasFile)
    {
        $this->hasFile = $hasFile;
    }


    // Reasons Navigate if More than one.
    public function getPage()
    {
        if ($this->workReport->ReturnRamal) {
            $total = $this->workReport->ReturnRamal->count();

            return $this->pag = $total - 1;
        } else {
            return $this->pag = 0;
        }
    }

    public function nextPage()
    {
        if (($this->workReport->ReturnRamal->count() - 1) > $this->pag) {
            return $this->pag++;
        }
    }

    public function previousPage()
    {
        if ($this->pag == 0) {
            return;
        }

        return --$this->pag;
    }



    public function toReturnWork(RamalReport $workReport)
    {
        $this->workReport = $workReport;

        if ($this->workReport) {

            // $this->emitTo('files.partnersinform', 'cancel_files');

            $this->getPage();

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modalReturnWorked',
            ]);
        }
    }

    public function toSave()
    {
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'ATUALIZAÇÃO DE INFORME',
            'msg'           => "<p class='fw-bold'>Você deseja submeter novamente o INFORME de OBRAS?</p>
                                <div class='card'>
    <div class='card-body'>
        <p>Lembre-se, o informe poderá ser retornado novamente para revisão pelas partes responsáveis. A cada nova remessa, a data de envio do INFORME será alterada para esta nova data, como se fosse a primeira vez.</p>
        <p><strong>Tenha certeza de ter atendido as requisições</strong>. Todos os retornos e seus motivos são registrados e ficam à disposição do setor responsável.</p>
    </div>
</div> ",
            'icon'          => 'question',
            'btnOktxt'      => 'Sim, Submeta!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => 'confirm_workform',
            // 'chave'         => '',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Resposta foi Enviada.',
        ]);

        return;
    }



    public function save()
    {


        unset($this->workReport->BtzeroEquipment);

        $this->workReport->rejected = 0;
        $this->workReport->informed_at = date('Y-m-d H:i:s');

        $this->workReport->save();

        // if ($this->hasFile) {
        //     $this->emitTo('files.manager.create-gen-files', 'saveFiles');
        // } else {
        //     $this->closeAll();
        // }

        Production::where('note_id', $this->workReport->note_id)
            ->where('status', 20)
            ->update([
                'status' => 10,
                'att_at' => now(),
                'att_by' => auth()->user()->id,
            ]);


        $this->closeAll();

    }

    public function savedFiles()
    {
        // Revebe chamado pelo Component de Arquivos;
        $this->emitTo('files.manager.create-gen-files', 'cleanFiles');
        $this->closeAll();
    }



    public function closeAll()
    {
        $this->workReport = null;
        $this->emitUp('refresh_rejected');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.btzero.actions.worked-return-form');
    }
}
