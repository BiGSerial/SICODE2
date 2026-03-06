<?php

namespace App\Http\Livewire\Services\Payment;

use App\Models\{Bancoupdate, Note, Notetimeline, Production, Service, User};
use Livewire\{Component, WithPagination};
use App\Services\Payment\NoteFilter;
use App\Helpers\TextFormatter;
use App\Services\Payment\BlockEvaluator;
use App\Services\D5\D5WorkflowService;
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
        $this->note = $note->loadMissing([
            'WorkForm',
            'FiveNote',
            'Partials',
            'Productions' => fn ($q) => $q->where('service_id', $this->service->uuid)
                                        ->orderByDesc('created_at'),
        ]);

        // 1. Pegar a parcial mais recente
        $latestPartial = $note->partials?->sortByDesc('created_at')->first();

        // 2. Verificar se esta parcial atende aos critérios
        $this->partial = false;
        $this->partialDate = null;

        if (!$this->note->WorkForm) {
            if ($latestPartial
            && $latestPartial->allow
            && $latestPartial->supervision
            && ! $latestPartial->payment
            ) {
                $this->partial     = true;
                $this->partialDate = $latestPartial->created_at;
            }
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


        $fiveNote = $this->note->FiveNote ? true : false;


        $this->note->loadMissing([
            'WorkForm',
            'FiveNote',
            'Partials',
            'Productions' => fn ($q) => $q->where('service_id', $this->service->uuid)
                                        ->orderByDesc('created_at'),
        ]);

        $eval = app(BlockEvaluator::class)->evaluate($this->note, $this->service);

        // $exists = Production::where('note_id', $this->note->id)
        //     ->where('service_id', $this->service->uuid)
        //     ->where('dhstats', $dt)
        //     ->exists();

        if (!$eval['command']) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOOPS! NOTA/OV JÁ ATRIBUÍDA',
                'html'     => "<strong>{$this->note->note}</strong> já foi atribuída em "
                               . \Carbon\Carbon::parse($dt)->format('d/m/Y H:i') . " <br> <p>Motivo: {$eval['reason']}</p>",
            ]);
            return;
        }



        // 3. Buscar usuário e criar produção normalmente
        $user = User::with('Employee.Contract')
                    ->find(Auth::id());


        $data = [
            'note_id'     => $this->note->id,
            'service_id'  => $this->service->uuid,
            'user_id'     => $user->id,
            'company_id'  => $user->company_id,
            'dispatch_by' => $user->id,
            'att_by'      => $user->id,
            'dt_note'     => $dt,
            'status_note' => $this->note->nstats,
            'dispatch_at' => now(),
            'att_at'      => now(),
            'status'      => 2,
            'dhstats'     => $dt,
            'partial'     => (bool) $this->partial,
            'dfive'       => $fiveNote,
        ];

        $production = Production::firstOrCreate([
                'note_id'    => $this->note->id,
                'service_id' => $this->service->uuid,
                'user_id'    => $user->id,
                'completed'  => false,
            ], $data);

        if ($production) {
            Notetimeline::create([
                'note_id'      => $this->note->id,
                'service_id'   => $production->service_id,
                'user_id'      => $user->id,
                'info'         => "Usuário {$user->name} atribuiu a Nota/OV.",
                'status'       => 2,
                'productionId' => $production->id,
            ]);

            if ($this->note->FiveNote) {
                $this->note->FiveNote->productions()->syncWithoutDetaching([$production->id]);

                app(D5WorkflowService::class)->onProductionAssigned(
                    $this->note->FiveNote,
                    $production,
                    auth()->id(),
                    null
                );
            }

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
        $production = $note->Productions->where('service_id', $this->service->uuid)->last();

        if ($production) {
            return $production;
        } else {
            return false;
        }
    }

    public function needBlock(Note $note): array
    {
        $eval = app(BlockEvaluator::class)->evaluate($note, $this->service);
        // retorna estrutura pra view usar diretamente
        return $eval;
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
        $base = $this->noteFilter->filter($this->search, $this->filter_group)
            ->select([
                'notes.id',
                'notes.note',
                'notes.lexp',
                'notes.mesalization',
                'notes.days_left',
                'notes.type_note',
                'notes.nstats',
                'notes.dt_status',
                DB::raw('(SELECT COALESCE(SUM(o.moaberto),0) FROM orders o WHERE o.note_id = notes.id) AS total_moaberto'),
            ]);

        // latest_ops (MAX fimLancado)
        $latestOps = DB::table('operation_resps')
            ->select('note_id', DB::raw('MAX(fimLancado) AS latest_fimLancado'))
            ->groupBy('note_id');

        // latest_partials (ROW_NUMBER)
        $latestPartialBase = DB::table('partials as p')
            ->selectRaw("
            p.note_id,
            p.supervision_at,
            ROW_NUMBER() OVER (PARTITION BY p.note_id ORDER BY p.id DESC) AS rn
        ")
            ->where('p.allow', 1)
            ->where('p.deny', 0)
            ->where('p.supervision', 1);

        $latestPartials = DB::query()
            ->fromSub($latestPartialBase, 't')
            ->select('t.note_id', 't.supervision_at')
            ->where('t.rn', 1);

        // latest production por serviço (ROW_NUMBER)
        $latestProdBase = DB::table('productions as p')
            ->selectRaw("
            p.note_id,
            p.id            AS latest_prod_id,
            p.user_id       AS latest_user_id,
            p.completed     AS latest_completed,
            p.status        AS latest_status,
            p.partial       AS latest_partial,
            p.confirmed     AS latest_confirmed,
            p.dfive         AS latest_dfive,
            p.created_at    AS latest_created_at,
            p.completed_at  AS latest_completed_at,
            p.dhstats       AS latest_dhstats,
            p.dt_note       AS latest_dt_note,
            p.status_note   AS latest_status_note,
            ROW_NUMBER() OVER (PARTITION BY p.note_id ORDER BY p.created_at DESC, p.id DESC) AS rn
        ")
            ->where('p.service_id', $this->service->uuid);

        $latestProd = DB::query()
            ->fromSub($latestProdBase, 'u')
            ->select([
                'u.note_id',
                'u.latest_prod_id',
                'u.latest_user_id',
                'u.latest_completed',
                'u.latest_status',
                'u.latest_partial',
                'u.latest_confirmed',
                'u.latest_dfive',
                'u.latest_created_at',
                'u.latest_completed_at',
                'u.latest_dhstats',
                'u.latest_dt_note',
                'u.latest_status_note',
            ])
            ->where('u.rn', 1);

        // JOINs
        $base->leftJoinSub($latestOps, 'latest_ops', fn ($j) => $j->on('notes.id', '=', 'latest_ops.note_id'));
        $base->leftJoinSub($latestPartials, 'latest_partials', fn ($j) => $j->on('notes.id', '=', 'latest_partials.note_id'));
        $base->leftJoinSub($latestProd, 'lp', fn ($j) => $j->on('notes.id', '=', 'lp.note_id'));

        // fimLancado (WorkForm => latest_ops; senão => partial supervision_at)
        $base->addSelect(DB::raw("
        CASE
          WHEN EXISTS (SELECT 1 FROM work_reports wr WHERE wr.note_id = notes.id)
            THEN latest_ops.latest_fimLancado
          ELSE latest_partials.supervision_at
        END AS fimLancado
    "));

        // ===== BUCKET de ordenação =====
        // 0 = PARCIAL válida (sem WorkForm) -> vem primeiro
        // 1 = FiveNote prioritário (is_supervisioned=1, is_completed=1, is_archived=0)
        // 2 = FINAL (com WorkForm)
        // 3 = Demais
        $base->addSelect(DB::raw("
            CASE
            -- 0: PARCIAL válida (sem WorkForm)
            WHEN latest_partials.supervision_at IS NOT NULL
                AND NOT EXISTS (SELECT 1 FROM work_reports wr WHERE wr.note_id = notes.id)
                THEN 0

            -- 1: FiveNote prioritário
            WHEN EXISTS (
                SELECT 1 FROM five_notes as fn
                    WHERE fn.note_id = notes.id
                    AND fn.is_supervisioned = 1
                    AND fn.is_completed    = 1
                    AND fn.is_archived     = 0
            )
                THEN 1

            -- 2: FINAL (tem WorkForm)
            WHEN EXISTS (SELECT 1 FROM work_reports wr WHERE wr.note_id = notes.id)
                THEN 2

            -- 3: demais
            ELSE 3
            END AS sort_bucket
        "));

        // ----- Filtros da tela (mantém como já estava) -----
        if ($this->not_assigned && isset($this->service)) {
            $base->where(function ($q) {
                $q->whereNull('lp.latest_prod_id')
                  ->orWhereNull('lp.latest_user_id')
                  ->orWhere('lp.latest_user_id', 0);
            });
        }

        if (!empty($this->multiSearch)) {
            $ms = $this->multiSearch;
            $base->where(function ($q) use ($ms) {
                $q->whereIn('notes.note', $ms)
                  ->orWhereExists(function ($sq) use ($ms) {
                      $sq->select(DB::raw(1))
                         ->from('orders')
                         ->whereColumn('orders.note_id', 'notes.id')
                         ->whereIn('orders.ordem', $ms);
                  });
            });
        } elseif (!empty($this->search)) {
            $s = '%' . $this->search . '%';
            $base->where(function ($q) use ($s) {
                $q->where('notes.note', 'like', $s)
                  ->orWhereExists(function ($sq) use ($s) {
                      $sq->select(DB::raw(1))
                         ->from('orders')
                         ->whereColumn('orders.note_id', 'notes.id')
                         ->where('orders.ordem', 'like', $s);
                  });
            });
        }

        $base->when($this->typeNote, fn ($q) => $q->where('notes.type_note', $this->typeNote));
        $base->when($this->filter_d5, fn ($q) => $q->whereExists(function ($sq) {
            $sq->select(DB::raw(1))
               ->from('five_notes as fn')
               ->whereColumn('fn.note_id', 'notes.id');
        }));

        // ===== ORDEM FINAL =====
        // 1) parciais (0) → 2) finais (1) → 3) five (2) → 4) demais (3)
        // dentro de cada bucket, manter tua lógica: nulos por último e data crescente
        $base->orderBy('sort_bucket', 'ASC')
             ->orderByRaw('(fimLancado IS NULL) DESC')
             ->orderBy('fimLancado', 'ASC')
             ->orderBy('notes.id', 'ASC');

        // Paginar e carregar relações só dos itens da página
        $page = $base->paginate($this->perPage);

        $page->load([
            'WorkForm.Company',
            'WorkForm.Orders.Operations',
            // apenas a ÚLTIMA parcial válida
            'Partials',
            'Partials.Company',
            'Partials.Orders.Operations',
            'FiveNote',
            'Productions' => fn ($q) => $q->where('service_id', $this->service->uuid)
                                        ->with('User')
                                        ->orderByDesc('created_at'),
        ]);

        return $page;
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
