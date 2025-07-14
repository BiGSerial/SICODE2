<?php

namespace App\Http\Livewire\Services\Oexterno\Actions\Protest;

use App\Models\Comment;
use App\Models\MedProtest;
use App\Models\Service;
use App\Models\User;
use App\Models\UserAssignment;
use Livewire\Component;

class ControlMedProtest extends Component
{
    public $modProtest;
    public $notePage = 0;
    public $needsEvidence = 0;
    public $needsConfirmation = 0;
    public $serviceId;
    public $selectedUser;
    public $userList;
    public $isEngineer = false;

    public $responsible;
    public $monitoring;

    public $deleteCommentId;
    public $comment = '';

    public $serviceList = [];

    public $usersTemporarilyAssigned = [];

    protected $listeners = [
        'openModProtestControl',
        'refreshComponent' => '$refresh',
    ];

    public function updatedServiceId($value)
    {

        if ($value === 'construction') {
            $this->userList = User::where('responsible', true)->orderBy('name')->get();
        } elseif ($value === 'maintenance') {
            $this->userList = User::where('engineer', true)->orderBy('name')->get();
        } else {
            $this->userList = User::whereRelation('ToServices', 'service_id', $value)
                ->orderBy('name')
                ->get();
        }

    }

    protected function rules()
    {
        return [
            'modProtest.needsEvidence' => 'boolean',
            'modProtest.needsConfirmation' => 'boolean',
            'modProtest.completed' => 'boolean',
            'modProtest.completed_at' => 'nullable|date',
            'needsEvidence' => 'boolean',
            'needsConfirmation' => 'boolean',
        ];
    }



    public function mount()
    {
        $this->serviceList = Service::orderBy('service')->get();
    }

    public function nextPage($noteList)
    {
        if ($this->notePage < count($noteList) - 1) {
            $this->notePage++;
        }
    }

    public function previousPage()
    {
        if ($this->notePage > 0) {
            $this->notePage--;
        }
    }


    public function addComment()
    {
        if (trim($this->comment) === '') {
            session()->flash('error', 'O comentário não pode estar vazio.');
            return;
        }

        try {

            $this->modProtest->Comments()->create([
                'message' => $this->comment,
                'user_id' => auth()->id(),
            ]);

            $this->comment = '';
            $this->emit('refreshComponent');

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Comentário adicionado com sucesso!',
            ]);

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('torrada', [
               'status'   => 'danger',
               'menssage' => 'Ooops.... ocorreu um erro ao adicionar o comentário: ',
            ]);
        }

    }

    public function removeComment()
    {
        if ($this->deleteCommentId) {
            $this->deleteCommentId->delete();
            $this->deleteCommentId = null;

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Comentário removido com sucesso!',
            ]);

            $this->emit('refreshComponent');
        }
    }

    public function deleteComment(Comment $comment)
    {
        $this->deleteCommentId = $comment;

        if ($this->deleteCommentId) {

            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Remover Comentário?',
                // 'msg'   => "
                // Você deseja atribuir a NOTA/OV para você?</br></br>
                // <div class='card card-light'>
                // <div class='card-body'>
                // <p><strong>NOTA/OV estará disponível em acompanhamento como
                // sua tarefa e nenhum outro usuário poderá atribuir pra si.</p>
                // </div>
                // </div>
                // ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Remover!',
                'btnCanceltxt'  => 'Não, Cancele!',
                'action'        => 'removeComment172030',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhum comentário Removido.',

            ]);
        }
    }

    public function addUserAssignment()
    {

        // dd($this->selectedUser, $this->isEngineer);

        if (empty($this->usersTemporarilyAssigned)) {
            $this->usersTemporarilyAssigned[] = [
                'id'   => $this->selectedUser,
                'name' => $this->userList->find($this->selectedUser)->name ?? 'Usuário Desconhecido',
                'isEngineer' => $this->isEngineer,
            ];
        } else {
            $userExists = collect($this->usersTemporarilyAssigned)->contains(function ($user) {
                return $user['id'] === $this->selectedUser;
            });

            if (!$userExists) {
                $this->usersTemporarilyAssigned[] = [
                    'id'   => $this->selectedUser,
                    'name' => $this->userList->find($this->selectedUser)->name ?? 'Usuário Desconhecido',
                    'isEngineer' => $this->isEngineer,
                ];
            }
        }
    }


    public function removeTempUserAssignment($userId)
    {
        $this->usersTemporarilyAssigned = collect($this->usersTemporarilyAssigned)
            ->reject(function ($user) use ($userId) {
                return $user['id'] === $userId;
            })->values()->all();
    }


    public function removeUserAssignment($userId)
    {
        $userAssignment = UserAssignment::where('assignable_id', $this->modProtest->id)
            ->where('assignable_type', MedProtest::class)
            ->where('user_id', $userId)
            ->first();

        if ($userAssignment) {
            $userAssignment->delete();
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Usuário removido com sucesso!',
            ]);
        } else {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Usuário não encontrado para remoção.',
            ]);
        }
    }


    public function saveMeasures()
    {
        // if (empty($this->usersTemporarilyAssigned)) {
        //     $this->dispatchBrowserEvent('torrada', [
        //         'status'   => 'warning',
        //         'menssage' => 'Nenhum usuário atribuído para salvar as medidas.',
        //     ]);
        //     return;
        // }

        // $this->modProtest->needsEvidence = $this->needsEvidence;
        // $this->modProtest->needsConfirmation = $this->needsConfirmation;
        $this->modProtest->save();

        $this->modProtest->Assignments()->updateOrCreate(
            [
                 'user_id' => auth()->id(),
                 'assignable_id' => $this->modProtest->id,
                 'assignable_type' => MedProtest::class,
             ],
            [
                 'responsible' => true,
             ]
        );

        foreach ($this->usersTemporarilyAssigned as $user) {
            $this->modProtest->Assignments()->updateOrCreate(
                [
                    'user_id' => $user['id'],
                    'assignable_id' => $this->modProtest->id,
                    'assignable_type' => MedProtest::class,
                ],
                [
                    'monitoring' => $user['isEngineer'],
                ]
            );
        }

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Medidas salvas com sucesso!',
        ]);

        $this->cancelChanges();
    }


    public function openModProtestControl(MedProtest $modProtest)
    {
        $this->modProtest = $modProtest->load('protest', 'comments');

        if ($this->modProtest) {

            $this->notePage = 0;


            $this->dispatchBrowserEvent('showModal', [
               'id' => 'controlModProtestModal',
            ]);
        }
    }

    public function cancelChanges()
    {
        $this->modProtest = null;
        $this->notePage = 0;
        $this->needsEvidence = 0;
        $this->needsConfirmation = 0;
        $this->serviceId = null;
        $this->selectedUser = null;
        $this->userList = [];
        $this->isEngineer = false;

        $this->responsible = null;
        $this->monitoring = null;

        $this->usersTemporarilyAssigned = [];

        $this->dispatchBrowserEvent('hideModal');
    }


    public function render()
    {
        return view('livewire.services.oexterno.actions.protest.control-med-protest');
    }
}
