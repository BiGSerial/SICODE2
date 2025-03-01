<?php

namespace App\Http\Livewire\Responsible\Actions;

use App\Helpers\TextValidator;
use App\Models\Note;
use App\Models\Service;
use App\Models\Production;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Exists;
use Livewire\Component;

class RejectProject extends Component
{
    use TextValidator;

    public $note;
    public $service;
    public $serviceList;
    public $production;
    public $category;
    public $details;
    public $hasFile = false;

    protected $listeners = [
        'getInfoResponse',
        'hasFile',
        '9e2855529ed3d5bf67a254fe8061da6d' => 'saveReject',
        'clearAll',
        'filesFailed',
        'filesSaved',
        'update_list' => '$refresh',

    ];

    public function hasFile($hasFile)
    {
        $this->hasFile = $hasFile;
    }

    public function getInfoResponse(Note $note)
    {
        $this->cleanAll();

        $this->note = $note->load([
            'orders' => function ($q) {
                $q->where('statusSist', 'not like', 'ENT%')
                    ->where('statusSist', 'not like', 'ENC%')
                    ->orderBy('ordem');
            }]);

        $this->serviceList = Service::where('canReturn', true)->orderBy('service')->get();

        if ($this->note) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'rejectProject',
            ]);
        }
    }

    public function updatedService($value)
    {
        if ($value) {
            $this->production = Production::where('service_id', $value)
            ->where('note_id', $this->note->id)
            ->where('completed', true)
            ->orderBy('completed_at', 'DESC')
            ->first();
        } else {
            $this->production = null;
        }
    }

    public function preReject()
    {

        if (!trim($this->category)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORME O TIPO DE REJEIÇÃO',
                'html'    => 'Informe o tipoa do motivo do retorno do projeto.',

            ]);

            return;
        }

        if (!trim($this->service)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INFORME O SERVIÇO',
                'html'    => 'Informe o serviço para devolver o projeto.',

            ]);

            return;
        }



        $result = $this->isValidText((string)$this->details);

        if (!$result['valid']) {
            $reason = implode("<br>", $result['reasons']);
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'INSIRA UM TEXTO VÁLIDO',
                'html'    => 'O texto inserido não é válido. Verifique os seguintes pontos: <br>' . $reason,
            ]);

            return;
        }


        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Confirmação de Rejeição',
            'msg'           => "Você está prestes a rejeitar a Nota/Ov <strong>{$this->note->note}</strong> para {$this->category}.
                <p class='border border-1 rounded text-bg-secondary p-1 mt-2'>Uma vez rejeitada, ela continuará aqui na sua pilha contando o tempo de atividade. Mantenha a atenção ao tempo de resolução da atividade.</p>

                <p class='fw-bold'>Deseja prosseguir?</p>
                ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Rejeitar!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action' => '9e2855529ed3d5bf67a254fe8061da6d',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma Nota/Ov foi rejeitada.',
        ]);
    }

    public function saveReject()
    {
        if ($this->note->approval->exists()) {
            DB::beginTransaction();

            $production = null;

            if ($this->production) {

                if ($this->production->User && !$this->production->User->trashed()) {

                    $production = Production::create([
                        'note_id' => $this->note->id,
                        'service_id' => $this->service,
                        'completed' => false,
                        'd5' => true,
                        'att_at' => now(),
                        'att_by' => auth()->id(),
                        'dispatch_at' => now(),
                        'dispatch_by' => auth()->id(),
                        'user_id' => $this->production->user_id,
                        'company_id' => $this->production->company_id,
                        'status' => 2,
                        'dt_note' => $this->note->dt_status,
                        'dhstats' => $this->note->dt_status,
                        'status_note' => $this->note->nstats,
                        'centroTrab' => $this->note->centerjob,
                    ]);
                }
            }

            $reclaim = $this->note->approval->reclaims()->create([
                'service_id' => $this->service,
                'note_id' => $this->note->id,
                'production_id' => $production ? $production->id : null,
                'category' => $this->category,

            ]);

            if ($reclaim) {

                $reclaim->comments()->create([
                    'user_id' => auth()->id(),
                    'message' => $this->details,
                ]);






                DB::commit();

                if ($this->hasFile) {
                    $this->emitTo('files.manager.create-gen-files', 'saveFiles');



                } else {

                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'success',
                        'title'    => 'Sucesso ao Rejeitar Nota/Ov',
                        'timer'    => 2500,
                    ]);


                    $this->clearAll();


                    $this->emitUp('refresh_list');
                }



            } else {
                DB::rollBack();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ERRO AO REJEITAR NOTA/OV',
                    'html'    => 'Ocorreu um erro na etapa de adicionar comentário ao retorno da Nota/Ov. Por favor, tente novamente.',
                ]);

                return;
            }

        } else {


            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO REJEITAR NOTA/OV',
                'html'    => 'Ocorreu um erro na etapa de adicionar o retorno da Nota/Ov. Por favor, tente novamente.',
            ]);

            return;
        }
    }


    public function filesSaved()
    {

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Sucesso ao Rejeitar Nota/Ov',
            'timer'    => 2500,
        ]);

        $this->emitUp('refresh_list');
        $this->dispatchBrowserEvent('hideModal');
    }


    public function clearAll()
    {

        $this->note = null;
        $this->service = null;
        $this->serviceList = null;
        $this->production = null;
        $this->category = null;
        $this->details = null;

        $this->hasFile = false;

        $this->dispatchBrowserEvent('hideModal');

        $this->emitUp('refresh_list');

    }

    public function cleanAll()
    {

        $this->note = null;
        $this->service = null;
        $this->serviceList = null;
        $this->production = null;
        $this->category = null;
        $this->details = null;

        $this->hasFile = false;



    }

    public function render()
    {
        return view('livewire.responsible.actions.reject-project');
    }
}
