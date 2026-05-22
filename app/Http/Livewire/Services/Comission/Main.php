<?php

namespace App\Http\Livewire\Services\Comission;

use App\Exports\ProductionServiceExport;
use App\Models\{Production, Service, User};
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

    public $limit_pause = 50;

    public $user_l;

    public $user_s;

    public $user_search;

    public $analise;

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

    public function visualizar()
    {

    }

    public function export_excel()
    {
        return (new ProductionServiceExport($this->lists->get()))->download(date('YmdHis-') . 'production_services.xlsx');
    }

    public function goTransferProd($prod_id)
    {
        $this->emit('transfer_production_lev', $prod_id);
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

            $this->emit('open_analise_lev', ['productionId' => $check->id, 'noteId' => $check->note_id]);

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
        $this->emit('open_analise_lev', $this->analise);
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
            $this->emit('open_analise_lev', $this->analise);
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'analise_form',
            ]);
        }
    }

    public function filter_save()
    {
        // session()->put('filtro', $this->rubrica_s);
        // if (!session()->isStarted()) { session()->start(); }
        // $_SESSION['filtro'] = $this->rubrica_s;
        $this->emit('refresh_service');

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

        return Production::with(['Note'])
            ->join('notes', 'productions.note_id', '=', 'notes.id')
            ->Where('service_id', $this->service->uuid)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            }, function ($q) {
                return $q->where('user_id', Auth()->user()->id);
            })
            ->where('completed', false)
            ->when($this->search, function ($q, $s) {
                return $q->whereRelation('Note', 'note', 'like', '%' . $s . '%')
                    ->orwhereRelation('Note', 'material', 'like', '%' . $s . '%');
            })
            ->with(['Note' => function ($query) {
                $query->orderBy('dt_status', 'asc');
            }])
            ->orderBy('priority', 'DESC')
            ->orderBy('notes.dt_created', 'asc')
            ->select('productions.*', 'notes.dt_created as note_dt_created');
    }

    public function render()
    {
        return view('livewire.services.comission.main', [
            'lists' => $this->lists->paginate($this->perPage),
        ]);
    }
}
