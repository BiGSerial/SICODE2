<?php

namespace App\Http\Livewire\Responsible\Actions;

use App\Helpers\TextValidator;
use App\Models\Note;
use App\Models\Notetimeline;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Exists;
use Livewire\Component;
use Livewire\WithPagination; // Importe a trait WithPagination

class RejectProject extends Component
{
    use TextValidator;
    use WithPagination;

    protected $paginationTheme = 'bootstrap'; // Define o tema da paginação

    public $note;
    public $service;
    public $serviceList;
    public $production;
    public $category;
    public $details;
    public $decision;
    public $hasFile = false;

    protected $listeners = [
        'getInfoResponse',
        'hasFile',
        '9e2855529ed3d5bf67a254fe8061da6d' => 'saveReject',
        '9e2855529ed3d5bf67a254fe806sdsaw1212' => 'saveApproved',
        '9e2855529ed3d5bf67a254fe806sdsaw3333' => 'cancelReclaim',
        'clearAll',
        'filesFailed',
        'savedFiles' => 'filesSaved',
        'update_list' => '$refresh',
    ];

    // Define a query string
    protected $queryString = [
        'page', // Para manter a página na URL
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
                $q->orderBy('ordem');
            },
            'Approval.Reclaims', // Eager load the reclaims relationship directly
        ]);


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

        if ($this->note->approval->tacit) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOPS',
                'html'    => 'Não é mais possível tomar ação por esta OBRA. O Prazo definido para que seja analizado foi extrapolado.
                            O Sistema assumiu automáticamente a responsabilidade de aprovar ou reprovar esta Nota/Ov. Porém, está sendo computado em
                            em seu usuário como tácito.',
            ]);

            return;
        }

        if ($this->decision == 'REPROVADO') {
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



            // $result = $this->isValidText((string)$this->details);

            // if (!$result['valid']) {
            //     $reason = implode("<br>", $result['reasons']);
            //     $this->dispatchBrowserEvent('swal', [
            //         'position' => 'center',
            //         'icon'     => 'warning',
            //         'title'    => 'INSIRA UM TEXTO VÁLIDO',
            //         'html'    => 'O texto inserido não é válido. Verifique os seguintes pontos: <br>' . $reason,
            //     ]);

            //     return;
            // }


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

            return;
        }

        if ($this->decision == 'APROVADO') {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Aprovar Projeto',
                'msg'           => "Você está prestes a aprovar a Nota/Ov <strong>{$this->note->note}</strong>.
                    <p class='border border-1 rounded text-bg-secondary p-1 mt-2'>Uma vez aprovada, não será mais possível retornar a analise novamente.</p>

                    <p class='fw-bold'>Deseja prosseguir?</p>
                    ",
                'icon'          => 'question',
                'btnOktxt'      => 'Sim, Aprove!',
                'btnCanceltxt'  => 'Não, Cancele!',
                'action' => '9e2855529ed3d5bf67a254fe806sdsaw1212',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma Nota/Ov foi rejeitada.',
            ]);

            return;
        }
    }

    public function preCancelReclaims()
    {

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Cancelar Rejeição',
            'msg'           => "Você está prestes a cancelar a a rejeição da <strong>{$this->note->note}</strong> em {$this->category}.
                <p class='border border-1 rounded text-bg-secondary p-1 mt-2'>Ao cancelar a rejeição, a produção existente caso haja será marcada como cancelada e atividade completada.</p>

                <p class='fw-bold'>Deseja prosseguir?</p>
                ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Cancelar rejaição!',
            'btnCanceltxt'  => 'Não, manter a rejeição!',
            'action' => '9e2855529ed3d5bf67a254fe806sdsaw3333',
            'cancel_titulo' => 'Mantido!',
            'cancel_msg'    => 'Nenhuma rejeição foi cancelada.',
        ]);

    }


    public function cancelReclaim()
    {
        if ($this->note->approval->Reclaims()->exists()) {
            foreach ($this->note->approval->reclaims as $reclaim) {
                if (!$reclaim->completed) {
                    if ($production = $reclaim->production) {
                        $production->update([
                            'completed' => true,
                            'completed_at' => now(),
                            'confirmed' => true,
                            'confirmed_at' => now(),
                            'status' => 29,
                        ]);

                        $user = Auth()->User()->name;

                        Notetimeline::Create([
                            'note_id'    => $this->note->id,
                            'service_id' => $production->service_id,
                            'production_id' => $production->id,
                            'user_id'    => Auth()->User()->id,
                            'info'       => "Usuário {$user} cancelou a produção desta Nota/Ov.",
                            'status'     => 29,
                        ]);
                    }

                    $reclaim->update([
                        'completed' => true,
                        'completed_at' => now(),
                    ]);
                }
            }
        }
    }

    public function saveReject()
    {

        if ($this->note->approval->exists()) {


            if ($this->note->approval->tacit) {

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'OOOPS',
                    'html'    => 'Não é mais possível tomar ação por esta OBRA. O Prazo definido para que seja analizado foi extrapolado.
                                O Sistema assumiu automáticamente a responsabilidade de aprovar ou reprovar esta Nota/Ov. Porém, está sendo computado em
                                em seu usuário como tácito.',
                ]);

                return;
            }




            DB::beginTransaction();

            if (Reclaim::hasActiveForService($this->note->id, $this->service)) {
                DB::rollBack();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'RECLAIM JÁ EM ANDAMENTO',
                    'html'     => 'Já existe uma atividade de retorno interno em aberto para esta obra e serviço.',
                    'timer'    => 4000,
                ]);

                return;
            }

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
                    $this->emitTo('files.manager.create-gen-files', 'saveFiles', Reclaim::class, $reclaim->id);



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

    public function saveApproved()
    {

        try {
            $this->note->approval->update([

                'approved'     => true,
                'reason'      => 'APROVADO INDIVIDUALMENTE POR ' . auth()->user()->name,
                'approved_at'   => now(),
            ]);

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Erro ao aprovar Notas/Ov',
                'html'      => 'Erro: ' . $th->getMessage(),
                // 'timer'    => 2500,
            ]);

            DB::rollBack();

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Nota/Ov Aprovada',
            'timer'    => 2500,
        ]);

        $this->clearAll();
        $this->emitUp('refresh_list');
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
        $this->decision = null;

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
        $this->decision = null;

        $this->hasFile = false;



    }

    public function render()
    {

        // if ($this->note) {
        //     dd($this->note && $this->note->approval && $this->note->approval->reclaims);
        // }


        return view('livewire.responsible.actions.reject-project', [
            'retornoInternos' =>  $this->note?->approval?->reclaims()->orderBy('id', 'DESC')->paginate(1, ['*'], 'reclaims_page'), // Passe a instancia de paginação para view
        ]);
    }
    // Reseta a paginação quando o filtro é alterado
    public function updatingSearch()
    {
        $this->resetPage();
    }
}
