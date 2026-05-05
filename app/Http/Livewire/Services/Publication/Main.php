<?php

namespace App\Http\Livewire\Services\Publication;

use App\Custom\RuleBuilder;
use App\Models\{Bancoupdate, Note, Notetimeline, Production, Service, User};
use Livewire\{Component, WithPagination};
use App\Services\Publication\NoteFilter;
use Illuminate\Support\Facades\DB;

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

    public $notes_limits = 12;
    public $notes_limits_max = 21;

    //Botão de  nao atribuído.
    public $not_assigned = false;

    public $assigned_mmgd = false;

    public $btzeroform = true;


    protected $queryString = [
        'search' => ['except' => '', 'as' => 'buscar'],
    ];

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

    public function btzeroform()
    {
        $this->btzeroform = !$this->btzeroform;
    }

    public function to_accompany(Note $note)
    {
        // $qtd = Production::where('service_id', $this->service->uuid)->where('user_id', Auth()->User()->id)->where('completed', false)->count();

        // // dd($this->notes_limits >= $qtd);

        // if ($qtd >= $this->notes_limits) {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon'     => 'error',
        //         'title'    => 'OOOOPS! EXCESSO DE ATIVIDADE',
        //         'html'     => "<strong>LIMITE ATINGIDO</strong> Você possui muitas atividades pendentes para serem finalizadas. Encerre as atividades pendentes para poder adquirir novas atividades.",
        //     ]);

        //     return;
        // }

        $user_id = Auth()->user()->id;
        $service_uuid = $this->service->uuid;
        $initial_limit = $this->notes_limits; // Limite inicial de notas atribuídas
        $limit_max = $this->notes_limits_max - $this->notes_limits;
        // Contagem de notas em produção não finalizadas (status 2)
        $qtd_production = Production::where('service_id', $service_uuid)
            ->where('user_id', $user_id)
            ->where('completed', false)
            ->count();

        // Contagem de notas pausadas (status 4)
        $paused_count = Production::where('service_id', $service_uuid)
            ->where('user_id', $user_id)
            ->where('status', 4)
            ->count();

        $add = 0;

        if ($paused_count > 0) {
            $add = floor($paused_count / 3) + 1;



            if ($add >= $limit_max) {
                $add = $limit_max;
            }
        }

        if ($qtd_production >= ($initial_limit + $add)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOOPS! EXCESSO DE ATIVIDADE',
                'html'     => "<strong>LIMITE ATINGIDO</strong> Você possui muitas atividades pendentes para serem finalizadas. Encerre as atividades pendentes para poder adquirir novas atividades.",
            ]);

            return;
        }

        $this->note = $note;

        if (!$this->note->WorkForm && $this->note->RamalForm) {
            $this->dispatchBrowserEvent('alertar', [
                'title' => 'Atribuir Tarefa Parcial',
                'msg'   => "
                Você deseja atribuir a NOTA/OV para você?</br></br>
                <div class='card text-bg-danger'>
                <div class='card-body'>
                <p><strong>Esta Nota/OV, necessita de publicação imediata porém não se pode confirmar a 20. Ao finalizar, a mesma permanecerá na sua pilha com status de PUBLICADO até que o Informe de Conclusão esteja disponível para o encerramento total.</p>
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
        })->with('User', 'Company', 'Service')->first();

        if ($check) {
            $name = $check->User?->name ?? ($check->Company ? "{$check->Company->name} (sem usuário atribuído)" : 'Desconhecido');

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

    public function hasPublication(Note $note)
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
        // return $this->noteFilter->filter($this->search, $this->filter_group)
        //     ->join('work_reports', 'notes.id', '=', 'work_reports.note_id')
        //     ->select('notes.*', 'work_reports.created_at as wCreated_at')
        //     ->when($this->not_assigned, function ($q) {
        //         $q->whereDoesntHave('Productions', function ($q) {
        //             $q->where('service_id', $this->service->uuid)
        //                 ->whereNotNull('user_id');
        //         })->orWhereHas('Productions', function ($q) {
        //             $q->where('service_id', $this->service->uuid)
        //                 ->where(function ($q) {
        //                     $q->whereNull('user_id')
        //                         ->orWhere('user_id', '');
        //                 });
        //         });
        //     })
        //     ->orderBy('wCreated_at', 'ASC')
        //     ->paginate($this->perPage);

        return $this->noteFilter->filter($this->search, $this->filter_group, $this->btzeroform)
        // ->join('work_reports', 'notes.id', '=', 'work_reports.note_id')

        ->select(
            'notes.*',
            // 'work_reports.created_at as wCreated_at',
            // Adicionar a coluna 'prazo_final' com base no type_note e mesalization
            DB::raw("
                CASE
                    WHEN notes.type_note = 2 THEN DATE_ADD(CURDATE(), INTERVAL notes.days_left DAY)
                    WHEN notes.type_note = 1 THEN STR_TO_DATE(CONCAT('28/', SUBSTRING(notes.mesalization, 2, 2), '/', SUBSTRING(notes.mesalization, 5)), '%d/%m/%Y')
                    ELSE NULL
                END as prazo_final
            ")
        )
        ->when($this->not_assigned, function ($q) {
            $q->whereDoesntHave('Productions', function ($q) {
                $q->where('service_id', $this->service->uuid)
                    ->whereNotNull('user_id');
            })->orWhereHas('Productions', function ($q) {
                $q->where('service_id', $this->service->uuid)
                    ->where(function ($q) {
                        $q->whereNull('user_id')
                            ->orWhere('user_id', '');
                    });
            });
        })
        // Ordenar pela coluna 'prazo_final'
        ->orderByRaw('
            exists (
                select 1
                from ramal_reports
                where ramal_reports.note_id = notes.id
            )
            and not exists (
                select 1
                from work_reports
                where work_reports.note_id = notes.id
            ) desc
        ')
        ->orderBy('prazo_final', 'ASC')
        ->paginate($this->perPage);
    }



    public function render()
    {
        // $this->rubrica_l = Note::select('rubrica')->where('nstats', $this->service->status)->orderBy('rubrica')->groupBy('rubrica')->get();

        // dd(
        //     $this->lists
        // );


        return view('livewire.services.publication.main', [
            'lists'  => $this->lists,
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }
}
