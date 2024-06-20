<?php

namespace App\Http\Livewire\Services\Publication;

use App\Custom\RuleBuilder;
use App\Models\{Bancoupdate, Note, Notetimeline, Production, Service, User};
use Livewire\{Component, WithPagination};
use App\Services\Publication\NoteFilter;

class Main extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $rubrica_s = [];

    public $rubrica_l;

    public $note;

    public $last_update;

    //Botão de  nao atribuído.
    public $not_assigned = false;

    public $assigned_mmgd = false;

    // Filters
    private $filter_group = 'publication';

    protected $listeners = [
        'refresh_service'   => '$refresh',
        'refresh_list'      => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
    ];

    protected $noteFilter;

    public function boot(NoteFilter $noteFilter)
    {
        $this->noteFilter = $noteFilter;
    }


    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = (Note::OrderBy('dt_status', 'DESC')->first())->dt_status;

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        if (isset($_SESSION['filtro']['analise']['rubrica']) && $_SESSION['filtro']['analise']['rubrica']) {
            $this->rubrica_s = $_SESSION['filtro']['analise']['rubrica'];
        }
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function filterMMGD()
    {
        if ($this->assigned_mmgd) {
            $this->assigned_mmgd = false;
        } else {
            $this->assigned_mmgd = true;
        }
    }

    public function to_accompany(Note $note)
    {
        $this->note = $note;

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Atribuir Tarefa',
            'msg'   => "
            Você deseja atribuir a NOTA/OV para você?</br></br>
            <div class='card card-light'>
            <div class='card-body'>
            <p><strong>NOTA/OV estará disponível em acompanhamento como
            sua tarefa e nenhum outro usuário poderá atribuir pra si.</p>
            </div>
            </div>
            ",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Atribua!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_accompany',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum serviço foi atribuído.',

        ]);
    }

    public function add_to_accompany()
    {
        $user = User::with('Employee.Contract')->find(Auth()->User()->id);

        $check = Production::where('note_id', $this->note->id)->where(function ($q) {
            return $q->where('completed', false)
                ->Where('service_id', $this->service->uuid);
        })->with('User', 'Service')->first();

        if ($check) {
            $name = $check->User ? $check->User->name : 'Desconhecido';

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOOPS! NOTA/OV TRATADA OU EM TRATAMENTO',
                'html'     => "<strong>{$this->note->note}</strong> foi ou está em Tratamento em {$check->Service->service} por <strong>{$name}</strong>",

            ]);

            return;
        }

        $production = Production::Create([
            'note_id'     => $this->note->id,
            'service_id'  => $this->service->uuid,
            'user_id'     => $user->id,
            'company_id'  => $user->Employee->Contract->company_id,
            'dispatch_by' => $user->id,
            'att_by'      => $user->id,
            'dt_note'     => $this->note->dt_status,
            'status_note' => $this->note->nstats,
            'dispatch_at' => date('Y-m-d H:i:s'),
            'att_at'      => date('Y-m-d H:i:s'),
            'status'      => 2,
            'dhstats'     => $this->note->dt_status,
        ]);

        if ($production) {

            Notetimeline::Create([
                'note_id'      => $this->note->id,
                'service_id'   => $production->service_id,
                'user_id'      => Auth()->User()->id,
                'info'         => "Usuário {$user->name} atribuiu a Nota/OV.",
                'status'       => 2,
                'productionId' => $production->id,
            ]);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => "{$this->note->note} foi atribuído a você com sucesso.",
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





    public function filterStatus()
    {
        if ($this->not_assigned) {
            $this->not_assigned = false;
        } else {
            $this->not_assigned = true;
        }
    }

    public function getListsProperty()
    {

        // $query = Note::query();

        // // RuleBuilder::applyRules($query, $this->service->Status);

        // $query->whereHas('WorkForm')
        //     ->whereHas('Orders', function ($q) {
        //         $q->where('statusSist', 'LIKE', 'LIB%')
        //             ->whereHas('Operations', function ($sq) {
        //                 $sq->where('operacao', '0010')
        //                     ->where('status', 'like', 'CONF%');
        //             });
        //     });

        // $query->when($this->search, function ($q, $s) {
        //     return $q->where(function ($query) use ($s) {
        //         $query->where('note', 'like', '%' . $s . '%')
        //             ->orWhere('material', 'like', '%' . $s . '%')
        //             ->orWhere('numPedido', 'like', '%' . $s . '%')
        //             ->orWhere('group2', 'like', '%' . $s . '%');
        //     });
        // })->when($this->rubrica_s, function ($q) {
        //     return $q->where(function ($query) {
        //         $query->whereIn('rubrica', $this->rubrica_s)
        //             ->orWhereNull('rubrica');
        //     });
        // });

        // if ($this->not_assigned) {
        //     $query->where(function ($q) {
        //         $q->doesntHave('Productions')
        //             ->orWhereDoesntHave('Productions', function ($subquery) {
        //                 $subquery->where('service_id', $this->service->uuid)
        //                     ->where('confirmed', false);
        //             });
        //     });
        // }

        // if ($this->assigned_mmgd) {
        //     $query->where('material', 'like', '%MMGD%');
        // } else {
        //     $query->where('material', 'not like', '%MMGD%');
        // }

        // $query->with('Productions.User')
        //     ->orderBy('days_left', 'ASC');

        // return $query->paginate($this->perPage);

        return $this->noteFilter->filter($this->search,  $this->filter_group)->paginate($this->perPage);
    }

    public function render()
    {
        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        return view('livewire.services.publication.main', [
            'lists'  => $this->lists,
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }
}
