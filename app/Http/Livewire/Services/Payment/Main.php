<?php

namespace App\Http\Livewire\Services\Payment;

use App\Models\{Bancoupdate, Note, Notetimeline, Production, Service, User};
use Livewire\{Component, WithPagination};
use App\Services\Payment\NoteFilter;
use App\Helpers\TextFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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

    public $partialDate;

    //Botão de  nao atribuído.
    public $not_assigned = false;

    public $filter_d5 = false;

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

    public function filterD5()
    {
        $this->filter_d5 = !$this->filter_d5;
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

    public function to_accompany(Note $note)
    {
        $this->note = $note;

        // 1. Pegar a parcial mais recente
        $latestPartial = $note->partials()
                              ->orderByDesc('created_at')
                              ->first();

        // 2. Verificar se esta parcial atende aos critérios
        $this->partial = false;
        $this->partialDate = null;

        if ($latestPartial
            && $latestPartial->allow
            && $latestPartial->supervision
            && ! $latestPartial->payment
        ) {
            $this->partial     = true;
            $this->partialDate = $latestPartial->created_at;
        }

        // 3. Disparar o alerta (texto praticamente idêntico aos dois casos)
        $this->dispatchBrowserEvent('alertar', [
            'title'          => 'Atribuir Tarefa',
            'msg'            => "
            Você deseja atribuir a NOTA/OV "
                . ($this->partial ? "(PARCIAL) " : "")
                . "para você?</br></br>
            <div class='card card-light'>
              <div class='card-body'>
                <p><strong>NOTA/OV estará disponível em acompanhamento como
                sua tarefa e nenhum outro usuário poderá atribuir pra si.</p>
              </div>
            </div>
        ",
            'icon'           => 'warning',
            'btnOktxt'       => 'Sim, Atribua!',
            'btnCanceltxt'   => 'Não, Cancele!',
            'action'         => 'confirm_accompany',
            'cancel_titulo'  => 'Cancelado!',
            'cancel_msg'     => 'Nenhum serviço foi atribuído.',
        ]);
    }

    public function add_to_accompany()
    {
        // 1. Defina o "dt" que vamos usar: parcial ou data original da nota
        $dt = $this->partial
            ? $this->partialDate     // data de criação da parcial
            : $this->note->dt_status; // data padrão da nota

        // 2. Verificar duplicação: mesmo note_id, service_id e dhstats = $dt
        $exists = Production::where('note_id', $this->note->id)
            ->where('service_id', $this->service->uuid)
            ->where('dhstats', $dt)
            ->exists();

        if ($exists) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOOPS! NOTA/OV JÁ ATRIBUÍDA',
                'html'     => "<strong>{$this->note->note}</strong> já foi atribuída para esta mesma parcial em "
                               . \Carbon\Carbon::parse($dt)->format('d/m/Y H:i')
            ]);
            return;
        }

        // 3. Buscar usuário e criar produção normalmente
        $user = User::with('Employee.Contract')
                    ->find(Auth::id());

        $production = Production::create([
            'note_id'     => $this->note->id,
            'service_id'  => $this->service->uuid,
            'user_id'     => $user->id,
            'company_id'  => $user->Employee->Contract->company_id,
            'dispatch_by' => $user->id,
            'att_by'      => $user->id,
            'dt_note'     => $dt,
            'status_note' => $this->note->nstats,
            'dispatch_at' => now(),
            'att_at'      => now(),
            'status'      => 2,
            'dhstats'     => $dt,
            'partial'     => $this->partial,
        ]);

        if ($production) {
            Notetimeline::create([
                'note_id'      => $this->note->id,
                'service_id'   => $production->service_id,
                'user_id'      => $user->id,
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

    /**
     * Verifica se a nota tem produção associada, considerando regras específicas
     *
     * @param Note $note
     * @return Production|bool|null
     */
    public function hasProduction(Note $note)
    {
        // 1) Verifica se a nota tem WorkForm associado (flag booleana)
        $hasWorkForm = (bool) $note->WorkForm;

        // 2) Pega a última parcial criada na nota
        $lastPartial = $note->partials()
            ->orderByDesc('created_at')
            ->first();

        // 3) Pega a última produção (qualquer) para este serviço específico
        $lastProduction = $note->productions()
            ->where('service_id', $this->service->uuid)
            ->orderByDesc('created_at')
            ->first();

        // 4) Se existe WorkForm, ignora relação com parciais e segue regras específicas
        if ($hasWorkForm) {

            // 4.1) Se não existe produção, retorna false (não há produção a considerar)
            if (!$lastProduction) {
                return false;
            }

            // 4.2) Se a última produção NÃO é parcial
            if (!$lastProduction->partial) {
                // Se a data de status da nota é mais recente que a data da produção, retorna false (produção desatualizada)
                if ($note->dt_status > $lastProduction->dt_note) {
                    return false;
                }
                // Se a produção não está completa, retorna o objeto da última produção (bloqueia)
                if (!$lastProduction->completed) {
                    return $lastProduction;
                }
            }

            // 4.3) Se a última produção É parcial
            if ($lastProduction->partial) {
                // Se a parcial está completa, retorna false (nada a bloquear)
                if ($lastProduction->completed) {
                    return false;
                } else {
                    // Se a parcial não está completa, retorna o objeto da última produção (bloqueia)
                    return $lastProduction;
                }
            }
        }

        // 5) Se existe uma parcial e não existe WorkForm, segue regras específicas
        if ($lastPartial && !$hasWorkForm) {

            // 5.1) Se não existe produção, retorna false (não há produção a considerar)
            if (!$lastProduction) {
                return false;
            }

            // 5.2) Se a última produção é parcial e NÃO está completa, retorna o objeto (bloqueia)
            if ($lastProduction->partial && !$lastProduction->completed) {
                return $lastProduction;
            }

            // 5.3) Se a última produção é parcial E está completa
            if ($lastProduction->partial && $lastProduction->completed) {
                // Se a data da produção é anterior à parcial, retorna false (não há produção a considerar)
                if ($lastProduction->created_at < $lastPartial->created_at) {
                    return false;
                }
                // Caso contrário, retorna o objeto da produção (bloqueia)
                return $lastProduction;
            }
        }

        // 6) Caso não caia em nenhum cenário acima, retorna o objeto da última produção (pode ser null)
        return $lastProduction;
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
            $query->where(function ($q) {


                $q->whereDoesntHave('Productions', function ($q2) {
                    $q2->where('service_id', $this->service->uuid);

                })

                ->orWhereHas('Productions', function ($q2) {
                    $q2->where('service_id', $this->service->uuid)
                        ->where(function ($q3) {
                            $q3->whereHas('Note.Partials')
                                ->whereHas('Note.latestProduction', function ($q4) {
                                    $q4->where('partial', false);
                                });
                        });
                })

                ->whereDoesntHave('latestProduction', function ($q2) {
                    $q2->where('service_id', $this->service->uuid)
                        ->where('completed', false);
                });

            });
        }


        if ($this->multiSearch) {
            $query->when($this->multiSearch, function ($q) {
                $q->whereIn('note', $this->multiSearch)
                    ->orWhereRelation('Orders', function ($q) {
                        $q->whereIn('ordem', $this->multiSearch);
                    });
            });
        } else {
            $query->when($this->search, function ($q) {
                $q->where('note', 'like', '%' . $this->search . '%')
                    ->orWhereRelation('Orders', function ($q) {
                        $q->where('ordem', 'like', '%' . $this->search . '%');
                    });
            });
        }


        $query->when($this->typeNote, function ($q) {
            $q->where('type_note', $this->typeNote);
        })->when($this->filter_d5, function ($q) {
            $q->whereHas('FiveNote');
        })
        ->with(['WorkForm' => function ($q) {
            $q->orderBy('informed_at', 'asc');
        }, 'FiveNote']);

        // Realizando o join com `work_reports` e `orders` e somando `moaberto`
        $query->leftJoin('work_reports', 'notes.id', '=', 'work_reports.note_id')
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
            ->groupBy('note_id'),
            'latest_partials',
            'notes.id',
            '=',
            'latest_partials.note_id'
        )
        ->leftJoin('partials', 'latest_partials.latest_partial_id', '=', 'partials.id')
        ->select([
        'notes.id',
        'notes.note',
        'notes.lexp',
        'notes.mesalization',
        'notes.days_left',
        'notes.type_note',
        DB::raw('SUM(orders.moaberto) as total_moaberto'),
        // Aqui definimos o CASE para escolher a data certa:
        DB::raw("
            CASE
                WHEN work_reports.id IS NOT NULL
                     AND latest_operation_resps.latest_fimLancado IS NOT NULL
                    THEN latest_operation_resps.latest_fimLancado
                WHEN work_reports.id IS NULL
                     AND partials.supervision_at IS NOT NULL
                    THEN partials.supervision_at
                ELSE NULL
            END as fimLancado
        "),
        // Se você ainda quiser sinalizar existência de partials:
        DB::raw('CASE WHEN partials.id IS NOT NULL THEN 1 ELSE 0 END as has_partials'),
        ])
        ->groupBy([
            'notes.id',
            'notes.note',
            'notes.lexp',
            'notes.mesalization',
            'notes.days_left',
            'notes.type_note',
            // Como usamos agregação em orders e CASE, precisamos agrupar pelo CASE também:
            DB::raw("
                CASE
                    WHEN work_reports.id IS NOT NULL
                        AND latest_operation_resps.latest_fimLancado IS NOT NULL
                        THEN latest_operation_resps.latest_fimLancado
                    WHEN work_reports.id IS NULL
                        AND partials.supervision_at IS NOT NULL
                        THEN partials.supervision_at
                    ELSE NULL
                END
            "),
            DB::raw('CASE WHEN partials.id IS NOT NULL THEN 1 ELSE 0 END'),
        ])
        ->groupBy('notes.id', 'work_reports.created_at', 'notes.note', 'notes.lexp', 'notes.mesalization', 'notes.days_left', 'notes.type_note', 'fimLancado', 'has_partials')
        // ->orderBy('has_partials', 'desc')
        ->orderByRaw('CASE WHEN fimLancado IS NULL OR fimLancado = 0 THEN 1 ELSE 0 END')
        ->orderBy('fimLancado', 'ASC');
        // ->orderBy('total_moaberto', 'desc');

        // Debugando o resultado para checar a consulta
        // dd($query->paginate(5));

        return $query;
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
            'lists'  => $this->lists->paginate($this->perPage),
            'update' => Bancoupdate::OrderBy('created_at', 'DESC')->first(),
        ]);
    }
}
