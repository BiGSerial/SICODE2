<?php

namespace App\Http\Livewire\Services\Reverse\Accompany;

use App\Models\{Note, Production, Service, User};
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $rubrica_s = [];

    public $rubrica_l;

    public $limit_pause = 1000;

    public $analise;

    public $user_l;

    public $user_s;

    public $user_search;

    public $production;

    public $note;

    protected $listeners = [
        'refresh_accomany'   => '$refresh',
        'getCopy'            => 'copy',
        'confirm_getAnalise' => 'go_to_analise',
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
    }

    public function goTransferProd($prod_id)
    {
        $this->emit('transfer_production', $prod_id);
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function checkOpen()
    {

        $check = Production::Where('service_id', $this->service->uuid)->where('user_id', Auth()->User()->id)->where('status', 3)->first();

        if ($check) {

            $this->emit('open_analise_analise', ['productionId' => $check->id, 'noteId' => $check->note_id]);

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'analise_form',
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'info',
                'title'    => 'NOTA AINDA EM ATIVIDADE',
                'html'     => "Para iniciar uma nova OV/NOTA, esta precisa ser ENCERRADA ou PAUSADA. \n
                    <p class='text-bg-light mt-2 p-2'>
                        É importante salientar que existe um limite para interromper notas. Uma vez atingido esse limite, essas notas deverão ter uma destinação
                        adequada.
                    </p>
                ",
            ]);

        }

    }

    public function go_to_analise()
    {
        $this->emit('open_analise_analise', $this->analise);
        $this->dispatchBrowserEvent('showModal', [
            'id' => 'analise_form',
        ]);
    }

    public function getAnalise($production, $note)
    {
        $this->analise = ['productionId' => $production, 'noteId' => $note];

        if ($this->limit_pause === Production::Where('status', 4)->Where('service_id', $this->service->uuid)->Where('user_id', Auth()->User()->id)->count() && (Production::find($production))->status != 4) {
            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'AVISO DE LIMITE DE PAUSA',
                'msg'           => "Você ja atingiu o limite de pausa neste serviço, ao iniciar esta nota, você não poderá colocar esta NOTA/OV em espera. \n Tem certeza que deseja continuar?",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Continue!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'confirm_getAnalise',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Ação Cancelada.',

            ]);
        } else {
            $this->emit('open_analise_analise', $this->analise);
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'analise_form',
            ]);
        }
    }

    public function filter_save()
    {

        // if (!(session_status() == PHP_SESSION_ACTIVE)) {
        //     if (!session()->isStarted()) { session()->start(); }
        // }
        // session()->put('filtro', $this->rubrica_s);
        // if (!session()->isStarted()) { session()->start(); }
        // $_SESSION['filtro'] = $this->rubrica_s;
        $this->emit('refresh_service');

    }

    public function visualizar()
    {

    }

    public function filter_clean()
    {
        $this->rubrica_s = [];

        // if (!session()->isStarted()) { session()->start(); }
        // if (isset($_SESSION['filtro'])) {
        //     unset($_SESSION['filtro']);
        // }

        $this->emit('refresh_service');
    }

    public function getListsProperty()
    {
        $this->user_l = User::when($this->user_search, function ($q) {
            return $q->where('name', 'like', '%' . $this->user_search . '%');
        })->orderBy('name')->get();

        return Production::Where('productions.service_id', $this->service->uuid)
            ->join('notes as n', 'productions.note_id', '=', 'n.id')
            ->when($this->user_s, function ($q) {
                return $q->where('productions.user_id', $this->user_s);
            }, function ($q) {
                return $q->where('productions.user_id', Auth()->user()->id);
            })
            ->where('productions.completed', false)
            ->when($this->search, function ($q, $s) {
                return $q->where(function ($query) use ($s) {
                    $query->where('n.note', 'like', '%' . $s . '%')
                      ->orWhere('n.material', 'like', '%' . $s . '%');
                });
            })
            ->select('productions.*')
            ->orderBy('n.type_note', 'desc')
            ->orderBy('n.days_left', 'asc')
            ->orderBy('productions.id', 'asc')
            ->with('note', 'user')
            ->paginate($this->perPage);
    }

    public function render()
    {
        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        return view('livewire.services.reverse.accompany.main', [
            'lists' => $this->lists,
        ]);
    }
}
