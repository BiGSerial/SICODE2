<?php

namespace App\Http\Livewire\Components\Manualnote;

use App\Models\{Manualnote as ModelsManualnote, Note, Production, User};
use Livewire\Component;

class Manualnote extends Component
{
    public $manual_view = false;

    public $search;

    public $note;

    public $service;

    public $search_view = false;

    public $block = false;

    public $status;

    public $solicitante;

    public $setor;

    public $users;

    public $user;

    protected $listeners = [
        'confirm_entrance_manual' => 'go_getNote',
    ];

    public function mount($service)
    {
        $this->service = $service;
        $this->users = User::whereRelation('ToServices', 'service_id', $this->service)
                            ->orderBy('name')->get();
    }

    public function getNote()
    {
        if (!trim($this->search)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'SEM INFORMAÇÃO PARA BUSCAR',
                'html'     => 'Você não inseriu valor válido no campo de busca. Verifique e tente novamente.',
            ]);

            $this->search_view = false;
            $this->block       = true;

            return;
        }

        $this->search_view = false;
        $this->note        = Note::Where('note', $this->search)->first();

        // Verifica se a a mesma não se encontra em Atividade
        $production = Production::whereRelation('Note', 'note', trim($this->search))->where('service_id', $this->service)->with('User')->first();

        if ($production) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'NOTA/OV JÁ EM TRATAMENTO',
                'html'     => "A Nota/OV que você está tentando pegar, já se encotra em ATIVIDADE com <strong class='text-uppercase'>{$production->User->name}</strong>. Entre em contato e solicite uma transferência.",
            ]);

            $this->search_view = false;
            $this->block       = true;

            return;
        }

        $this->block       = false;
        $this->search_view = true;
    }

    public function to_getNote()
    {
        if (!$this->status) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'STATUS NÃO DEFINIDO',
                'html'     => 'Defina o STATUS que a NOTA/OV está atribuída no SAP neste momento.',
            ]);

            return;
        }

        if (!$this->user) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'USUÁRIO NÃO DEFINIDO',
                'html'     => 'Defina o Usuário para ser atribuído a NOTA/OV neste momento.',
            ]);

            return;
        }

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'ENTRADA MANUAL',
            'msg'   => "
            Você deseja atribuir a NOTA/OV {$this->search}?</br></br>
            <div class='card card-light'>
            <div class='card-body'>
            <p>Lembre-se que as atribuições manuais, depende da correta informação no sistem. Nos caso que a NOTA/OV vier e precisar aguardar informaões
            da BASE, a informação incorreta ocasionará incosistêcia no sistema, e não entrará em sua lista de produção.</p>
            </div>
            </div>
            ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Atribua!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_entrance_manual',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum serviço foi atribuído.',

        ]);

    }

    public function go_getNote()
    {
        $user = User::find($this->user);

        if ($this->note) {

            $check = ModelsManualnote::where('note', trim($this->search))
                ->where(function ($query) {
                    $query->whereDate('created_at', date('Y-m-d'))
                        ->orWhere('user_id', $this->user);
                })
                ->with('User')->first();

            if ($check) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'OOOOPS! NOTA/OV JÁ FOI ATRIBUIDA MANUALMENTE',
                    'html'     => "<strong>{$check->note}</strong> Está em Tratamento por <strong>{$check->User->name}</strong>",
                    'timer'    => 2500,
                ]);

                return;
            }

            $check = Production::where('note_id', $this->note->id)->where('service_id', $this->service)->with('User')->orderBy('id', 'DESC')->first();

            if ($check) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'OOOOPS! NOTA/OV JÁ EM TRATAMENTO',
                    'html'     => "<strong>{$check->note}</strong> Está em Tratamento por <strong>{$check->User->name}</strong>",
                    'timer'    => 2500,
                ]);

                return;
            }

            $this->note->nstats = $this->status;
            $this->note->save();



            $production = Production::Create([
                'note_id'     => $this->note->id,
                'service_id'  => $this->service,
                'user_id'     => $this->user,
                'company_id'  => $user->Employee->Contract->company_id,
                'dispatch_by' => auth()->User()->id,
                'att_by'      => auth()->User()->id,
                'dt_note'     => $this->note->dt_status,
                'status_note' => $this->note->nstats,
                'dispatch_at' => now(),
                'att_at'      => now(),
                'status'      => 2,
                'manual'      => true,
            ]);

            if ($production) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => "{$this->note->note} foi atribuído a você com sucesso.",
                    'timer'    => 2500,
                ]);

                $manual = ModelsManualnote::create([
                    'note'        => $this->note->note,
                    'status'      => $this->status,
                    'service_id'  => $this->service,
                    'user_id'     => $user->id,
                    'solicitante' => $this->solicitante,
                    'setor'       => $this->setor,
                    'finish_at'   => now(),
                    'confirmed'   => true,
                    'completed'   => true,
                ]);

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'success',
                    'title'    => 'Entrada efetuada com sucesso. Verifique sua lista de atrubuições',
                    'timer'    => 2500,
                ]);

            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => "Erro ao tentar atribuir {$this->note->note}.",
                    'timer'    => 2500,
                ]);
            }

        }

        if (!$this->note) {

            $check = ModelsManualnote::where('note', trim($this->search))
                ->where(function ($query) {
                    $query->whereDate('created_at', date('Y-m-d'))
                        ->orWhere('user_id', Auth()->User()->id);
                })
                ->with('User')->first();

            if ($check) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'OOOOPS! NOTA/OV JÁ FOI ATRIBUIDA MANUALMENTE',
                    'html'     => "<strong>{$check->note}</strong> Está em Tratamento por <strong>{$check->User->name}</strong>",
                    'timer'    => 2500,
                ]);

                return;
            }

            $manual = ModelsManualnote::create([
                'note'        => trim($this->search),
                'status'      => $this->status,
                'service_id'  => $this->service,
                'user_id'     => $user->id,
                'solicitante' => $this->solicitante ? $this->solicitante : null,
                'setor'       => $this->setor ? $this->setor : null,

            ]);

            if (!$manual) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => "Erro ao tentar atribuir {$this->note->note}.",
                    'timer'    => 2500,
                ]);

                return;
            }
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Entrada efetuada com sucesso.',
            'timer'    => 2500,
        ]);

        $this->clean();
    }

    public function clean()
    {
        $this->search      = '';
        $this->search_view = false;
        $this->block       = false;
        $this->note        = '';
        $this->user        = '';
        $this->status      = '';
        $this->solicitante = '';
        $this->setor       = '';

        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.components.manualnote.manualnote');
    }
}
