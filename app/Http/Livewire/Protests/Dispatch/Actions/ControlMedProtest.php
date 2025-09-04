<?php

namespace App\Http\Livewire\Protests\Dispatch\Actions;

use App\Models\Comment;
use App\Models\MedProtest;
use App\Models\ProtestUser;
use App\Models\Service;
use App\Models\User;
use App\Models\UserAssignment;
use App\Notifications\SystemNotification;
use Livewire\Component;

class ControlMedProtest extends Component
{
    public $modProtest;
    public $notePage = 0;
    public $needsEvidence = 0;
    public $needsConfirmation = true;
    public $serviceId;
    public $selectedUser;
    public $userList;
    public $isEngineer = false;

    public $responsible;
    public $monitoring;

    public $deleteCommentId;
    public $comment = '';

    public $serviceList = [];
    public $selectedService = '';
    public $userSearch = '';

    public $usersTemporarilyAssigned = [];
    public $userAssignment;

    protected $listeners = [
        'openModProtestControl',
        'refreshComponent' => '$refresh',
        'removeUserAssigment152030' => 'confirmRemoveUserAssignment',
        'closeMeansure02110202' => 'closingMeasure',
    ];

    public function updatedServiceId($value)
    {

        if ($value === 'construction') {
            $this->userList = User::where('responsible', true)->whereNull('deleted_at')->orderBy('name')->get();
        } elseif ($value === 'maintenance') {
            $this->userList = User::where('engineer', true)->whereNull('deleted_at')->orderBy('name')->get();
        } elseif ($value === 'partner') {
            $this->userList = User::where('onlyparner', true)->whereNull('deleted_at')->orderBy('name')->get();
        } else {
            $this->userList = User::whereNull('deleted_at')
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



    public function updatedUserSearch()
    {
        $this->userList = User::when($this->serviceId, function ($q) {
            $q->whereRelation('ToServices', 'service_id', $this->serviceId);
        })->where('name', 'like', '%' . $this->userSearch . '%')->whereNull('deleted_at')->orderBy('name')->get();
    }

    public function mount()
    {
        $this->serviceList = Service::orderBy('service')->get();
        $this->userList = User::whereNull('deleted_at')->orderBy('name')->get();
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



            // Notificar usuários atribuídos, exceto o autor do comentário
            if ($recipients = $this->modProtest->Assignments()
            ->where('user_id', '!=', auth()->id())->get()) {



                foreach ($recipients as $recipient) {

                    if ($recipient->user) {
                        if ($recipient->User?->onlyparner) {
                            $link = route('protests.partner.view', $this->modProtest->id);
                        } else {
                            $link = route('protests.services.view', $this->modProtest->id);
                        }
                    } elseif ($recipient->monitoring) {
                        $link = route('protests.services.view_only', $this->modProtest->id);
                    } else {
                        $link = route('protests.dispatch.view', $this->modProtest->protest?->nota);
                    }

                    $recipient->User?->notify(new SystemNotification(
                        titulo: 'Novo comentário na Medida de Reclamação',
                        mensagem: 'O usuário '.auth()->user()->name.' comentou na medida da reclamação '.$this->modProtest->protest?->nota.'.',
                        link: $link, // ou outra rota que você tiver
                        status: 6,
                        extras: [
                            'med_protest_id' => $this->modProtest->id,
                            'commented_by'   => auth()->id(),
                        ]
                    ));
                }
            }



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

    // public function getFilteredUsersProperty()
    // {
    //     return User::query()
    //         ->when($this->selectedService, fn ($q) => $q->where('service_id', $this->selectedService))
    //         ->when($this->userSearch, fn ($q) => $q->where('name', 'like', '%' . $this->userSearch . '%'))
    //         ->get();
    // }



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
        $isUser = false;

        $existsUser = $this->modProtest->Assignments()
            ->where('user', true)
            ->exists();

        if (!$this->isEngineer && $existsUser) {
            $isUser = true;
        }


        if (empty($this->usersTemporarilyAssigned) && !$isUser) {
            $this->usersTemporarilyAssigned[] = [
                'id'   => $this->selectedUser,
                'name' => $this->userList->find($this->selectedUser)->name ?? 'Usuário Desconhecido',
                'isEngineer' => $this->isEngineer,
            ];

            $triggers = $this->userList->find($this->selectedUser)?->UserProtest?->triggers;

            if ($triggers) {
                foreach ($triggers as $trigger) {
                    $this->usersTemporarilyAssigned[] = [
                        'id'   => $trigger->User->id,
                        'name' => $trigger->User->name,
                        'isEngineer' => true,
                    ];
                }
            }

        } else {
            $userExists = collect($this->usersTemporarilyAssigned)->contains(function ($user) {
                return $user['id'] === $this->selectedUser;
            });

            $hasNonEngineerUser = collect($this->usersTemporarilyAssigned)->contains(function ($user) {

                return $user['isEngineer'] === false;
            });

            if (!$userExists && !$isUser && !($this->isEngineer === false && $hasNonEngineerUser)) {
                $this->usersTemporarilyAssigned[] = [
                    'id'   => $this->selectedUser,
                    'name' => $this->userList->find($this->selectedUser)->name ?? 'Usuário Desconhecido',
                    'isEngineer' => $this->isEngineer,
                ];

                $triggers = $this->userList->find($this->selectedUser)?->UserProtest?->triggers;

                if ($triggers) {
                    foreach ($triggers as $trigger) {
                        $triggerExists = collect($this->usersTemporarilyAssigned)->contains(function ($tempUser) use ($trigger) {
                            return $tempUser['id'] === $trigger->User->id;
                        });

                        if (!$triggerExists) {
                            $this->usersTemporarilyAssigned[] = [
                                'id'   => $trigger->User->id,
                                'name' => $trigger->User->name,
                                'isEngineer' => true,
                            ];
                        }
                    }
                }
            } else {
                if ($isUser) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'Apenas um usuário pode ser atribuído!',
                        'timer'    => 2500,
                    ]);

                    return;
                }
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


    public function removeUserAssignment(UserAssignment $userAssignment)
    {


        if ($userAssignment) {

            // dd($userAssignment);

            $this->userAssignment = $userAssignment->load('user');

            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Remover Usuario?',
                'msg'   => "
                    Você deseja remover o usuário <strong>{$this->userAssignment->User->name}</strong> da atribuição?</br></br>
                ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Remover!',
                'btnCanceltxt'  => 'Não, Cancele!',
                'action'        => 'removeUserAssigment152030',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhum comentário Removido.',

            ]);


        } else {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'Usuário não encontrado para remoção.',
            ]);
        }
    }

    public function confirmRemoveUserAssignment()
    {
        if ($this->userAssignment) {
            // Verificar se o usuário tem triggers e removê-los
            $triggers = $this->userAssignment->User?->UserProtest?->triggers;
            if ($triggers) {
                foreach ($triggers as $triggerUser) {
                    $triggerAssignment = $this->modProtest->Assignments()
                    ->where('user_id', $triggerUser->User->id)
                    ->first();

                    if ($triggerAssignment) {
                        $triggerAssignment->delete();
                    }
                }
            }

            $this->userAssignment->delete();

            $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Usuário removido com sucesso!',
            ]);

            $this->emitSelf('refreshComponent');

            $this->userAssignment = null;
        } else {
            $this->dispatchBrowserEvent('torrada', [
            'status'   => 'danger',
            'menssage' => 'Nenhum usuário selecionado para remoção.',
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
        // $this->modProtest->needsConf irmation = $this->needsConfirmation;
        $this->modProtest->save();

        if (!$this->modProtest->Assignments()->where('responsible', true)->exists()) {
            $this->modProtest->Assignments()->updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'assignable_id' => $this->modProtest->id,
                    'assignable_type' => MedProtest::class,
                ],
                [
                    'responsible' => true,
                    'started_at' => now(),
                ]
            );




        }

        foreach ($this->usersTemporarilyAssigned as $user) {

            $exists = $this->modProtest->Assignments()
                ->where('user', true)
                ->exists();

            if ($exists && !$user['isEngineer']) {
                continue;
            }

            $check = $this->modProtest->Assignments()->updateOrCreate(
                [
                    'user_id' => $user['id'],
                    'assignable_id' => $this->modProtest->id,
                    'assignable_type' => MedProtest::class,
                ],
                [
                    'monitoring' => $user['isEngineer'],
                    'user' => !$user['isEngineer'],
                    'started_at' => now(),
                ]
            );


            if ($check->wasRecentlyCreated) {
                $mensagek = [
                    'link' => '',
                    'message' => ''
                ];
                if ($check->user) {
                    if ($check->User?->onlyparner) {
                        $mensagek['link'] = route('protests.partner.view', $this->modProtest->id);
                        $mensagek['message'] = 'Você foi atribuído a uma nova medida da reclamação com responsável '.$this->modProtest->protest?->nota.'.';
                    } else {
                        $mensagek['link'] = route('protests.services.view', $this->modProtest->id);
                        $mensagek['message'] = 'Você foi atribuído a uma nova medida da reclamação com responsável '.$this->modProtest->protest?->nota.'.';
                    }
                } elseif ($check->monitoring) {
                    $mensagek['link'] = route('protests.services.view_only', $this->modProtest->id);
                    $mensagek['message'] = 'Você foi atribuído a uma nova medida da reclamação acompanhamento '.$this->modProtest->protest?->nota.'.';
                } else {
                    $mensagek['link'] = route('protests.dispatch.view', $this->modProtest->protest?->nota);
                }

                $check->User?->notify(new SystemNotification(
                    titulo: 'Nova Medida Atribuída',
                    mensagem: $mensagek['message'],
                    link: $mensagek['link'], // ou outra rota que você tiver
                    status: 7,
                    extras: [
                        'med_protest_id' => $this->modProtest->id,
                        'commented_by'   => auth()->id(),
                    ]
                ));
            }

        }

        $defaults = ProtestUser::where('default', true)->get();

        if ($defaults) {
            foreach ($defaults as $user) {
                $this->modProtest->Assignments()->updateOrCreate(
                    [
                        'user_id' => $user->user_id,
                        'assignable_id' => $this->modProtest->id,
                        'assignable_type' => MedProtest::class,
                    ],
                    [
                        'monitoring' => true,
                        'started_at' => now(),
                    ]
                );
            }
        }

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Medidas salvas com sucesso!',
        ]);

        $this->cancelChanges();
    }

    public function closeMeasure()
    {
        $this->dispatchBrowserEvent('alertar', [
                'title' => 'Encerrar Medida?',
                'msg'   => "
                    Você deseja encerrar a medida <strong>{$this->modProtest->id}</strong>?</br></br>
                ",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Encerrar!',
                'btnCanceltxt'  => 'Não, Cancele!',
                'action'        => 'closeMeansure02110202',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma medida encerrada.',

            ]);
    }

    public function closingMeasure()
    {
        $this->modProtest->update([
            'completed' => true,
            'completed_at' => now()
        ]);

        $this->modProtest->Assignments()->updateOrCreate(
            [
                'user_id' => auth()->id(),
                'assignable_id' => $this->modProtest->id,
                'assignable_type' => MedProtest::class,
            ],
            [
                'responsible' => true,
                'user' => true,
                'started_at' => now(),
            ]
        );


        foreach ($this->modProtest->load('assignments.user')->assignments as $user) {
            $user->update(
                [

                    'completed' => true,
                    'ended_at' => now(),
                ]
            );
        }

        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'Medidas Encerrada com sucesso!',
        ]);

        $this->cancelChanges();
    }

    public function openModProtestControl(MedProtest $modProtest)
    {
        $this->modProtest = $modProtest->load('protest', 'comments', 'assignments.user');

        if ($this->modProtest) {

            $this->notePage = 0;

            if ($this->modProtest->assignments->isEmpty()) {
                $this->modProtest->needsConfirmation = true;
            }
            # code...


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

        $this->emit('refreshComponent');
        $this->dispatchBrowserEvent('hideModal');
    }


    public function render()
    {
        return view('livewire.protests.dispatch.actions.control-med-protest');
    }
}
