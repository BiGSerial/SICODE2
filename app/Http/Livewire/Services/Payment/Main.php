<?php

namespace App\Http\Livewire\Services\Payment;

use App\Models\{Bancoupdate, Note, Notetimeline, Production, Service, User};
use Livewire\{Component, WithPagination};
use App\Services\Payment\NoteFilter;
use App\Helpers\TextFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Main extends Component
{
    use WithPagination;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $advanceSearch;

    public $multiSearch = [];

    public $rubrica_s = [];

    public $rubrica_l;

    public $note;

    public $last_update;

    public $typeNote;

    public $partial = false;
    public $partials;

    //Botão de  nao atribuído.
    public $not_assigned = false;

    public $assigned_mmgd = false;

    public $count = [
        'total'    => 0,
        'partials' => 0,
    ];
    // Filters
    private $filter_group = 'payments';

    protected $listeners = [
        'refresh_service'   => '$refresh',
        'refresh_list'      => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
    ];

    protected $queryString = [
        'search' => ['except' => '', 'as' => 'buscar'],
        'typeNote' => ['except' => '', 'as' => 'tipo'],
        'partials' => ['except' => false, 'as' => 'parciais'],
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


    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->multiSearch = $this->formatTextToArray($this->advanceSearch);
            $this->dispatchBrowserEvent('hideModal');
        } else {
            $this->multiSearch = [];
        }
    }

    public function filterMMGD()
    {
        if ($this->assigned_mmgd) {
            $this->assigned_mmgd = false;
        } else {
            $this->assigned_mmgd = true;
        }
    }

    public function to_accompany(Note $note, bool $partial)
    {




        $this->note = $note;
        $this->partial = $partial;

        // if ($partial = $this->note->Partials && !$this->note->WorkForm ? $this->note->Partials->last() : null) {
        //     if ($partial && $partial->allow && $partial->supervision && !$partial->payment) {
        //         $this->partial = true;
        //     }
        // } else {
        //     $this->partial = false;
        // }




        if ($this->partial) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Atribuir Tarefa',
                'msg'   => "
                Você deseja atribuir a NOTA/OV (PARCIAL) para você?</br></br>
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

        } else {
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
            'partial'     => $this->partial,
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

    public function hasProduction(Note $note)
    {
        $production = $note->Productions->where('service_id', $this->service->uuid)->last();

        if ($production) {
            return $production;
        } else {
            return false;
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
        $query = $this->noteFilter->filter($this->search, $this->filter_group);

        if ($this->not_assigned && isset($this->service)) {
            $query->whereDoesntHave('Productions', function ($sq) {
                $sq->where('service_id', $this->service->uuid);
            });
        }

        $query->when($this->multiSearch, function ($q) {
            $q->whereIn('note', $this->multiSearch)
            ->orWhereRelation('Orders', function ($q) {
                $q->whereIn('ordem', $this->multiSearch);
            });
        })
        ->when($this->typeNote, function ($q) {
            $q->where('type_note', $this->typeNote);
        })

        ->with(['WorkForm' => function ($q) {
            $q->orderBy('informed_at', 'asc');
        }]);

        // Realizando o join com `work_reports` e `orders` e somando `moaberto`
        $query->leftjoin('work_reports', 'notes.id', '=', 'work_reports.note_id')
        ->leftJoin('orders', 'notes.id', '=', 'orders.note_id')
        ->leftJoinSub(
            DB::table('operation_resps')
            ->select('note_id', DB::raw('MAX(fimLancado) as latest_fimLancado'))
            ->groupBy('note_id'),
            'latest_operation_resps',
            'notes.id',
            '=',
            'latest_operation_resps.note_id'
        )
        ->leftJoinSub(
            DB::table('partials')
            ->select('note_id', DB::raw('MAX(id) as latest_partial_id'))
            ->where('allow', true)
            ->where('deny', false)
            ->where('supervision', true)
            ->where('payment', false)
            ->groupBy('note_id'),
            'latest_partials',
            'notes.id',
            '=',
            'latest_partials.note_id'
        )
        ->leftJoin('partials', 'latest_partials.latest_partial_id', '=', 'partials.id')
        ->select(
            'notes.id',
            'notes.note',
            'notes.lexp',
            'notes.mesalization',
            'notes.days_left',
            'notes.type_note',
            'work_reports.created_at as wCreated_at',
            DB::raw('SUM(orders.moaberto) as total_moaberto'),
            'latest_operation_resps.latest_fimLancado as fimLancado',
            DB::raw('CASE WHEN partials.id IS NOT NULL THEN 1 ELSE 0 END as has_partials'),
        )
        ->groupBy('notes.id', 'work_reports.created_at', 'notes.note', 'notes.lexp', 'notes.mesalization', 'notes.days_left', 'notes.type_note', 'fimLancado', 'has_partials')
        ->orderBy('has_partials', 'desc')
        ->orderByRaw('CASE WHEN fimLancado IS NULL OR fimLancado = 0 THEN 1 ELSE 0 END')
        ->orderBy('fimLancado', 'asc')
        ->orderBy('total_moaberto', 'desc');

        // Debugando o resultado para checar a consulta
        // dd($query->paginate(5));

        return $query->paginate($this->perPage);

    }

    // Rules Days Left
    public function deadline(Note $note)
    {
        $days = 10;
        $date_forms = $note->WorkForm ? $note->WorkForm->informed_at : null;

        if ($date_forms) {

            $deadline_date = Carbon::parse($date_forms)->addDays($days);

            return Carbon::now()->diffInDays($deadline_date, false);
        } else {
            return 0;
        }
    }

    public function render()
    {
        $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        return view('livewire.services.payment.main', [
            'lists'  => $this->lists,
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }
}
