<?php

namespace App\Http\Livewire\Services\Publication;

use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\{Bancoupdate, Company, Note, Notetimeline, Production, Service, User, WorkReportFlowProduction};
use App\Services\Publication\{NoteFilter, PublicationListRowPresenter};
use Illuminate\Support\Facades\{Cache, DB};
use Livewire\{Component, WithPagination};

/**
 * Serviços → Lista de Publicação (auto-atribuição: o usuário assume a Nota/OV para si).
 * Lista operacional no padrão docs/PADRAO_LISTAS_OPERACIONAIS.md.
 */
class Main extends Component
{
    use WithPagination;
    use WithNoteListFilters {
        listFilterDefinitions as protected noteListFilterDefinitions;
        listFilterOptions as protected noteListFilterOptions;
    }
    use \App\Http\Livewire\Services\Concerns\BuildsCanceledWorkReportTags;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $note;

    public $last_update;

    public $notes_limits = 12;

    public $notes_limits_max = 21;

    // Atalho "Só livres".
    public $not_assigned = false;

    // Filtros
    private $filter_group = 'publication';

    protected $listeners = [
        'refresh_service'   => '$refresh',
        'refresh_list'      => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
    ];

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'buscar'],
        'page'         => ['except' => 1, 'as' => 'p'],
        'perPage'      => ['as' => 'pp'],
        'not_assigned' => ['except' => false, 'as' => 'livres'],
        'filters'      => ['except' => [], 'as' => 'f'],
    ];

    protected $noteFilter;

    /** @var array<int, int> production_id => work_report_id (vínculo de Publicação em vigor), lido uma vez por requisição. */
    private ?array $publicationLinks = null;

    public function boot(NoteFilter $noteFilter)
    {
        $this->noteFilter = $noteFilter;
    }

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('publication-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    // ---------- Filtros ----------

    /** Empreiteira, Região, Regional e Município (esta lista nunca teve filtro de Rubrica). */
    protected function listFilterDefinitions(): array
    {
        return collect($this->noteListFilterDefinitions())->except('rubrica')->all();
    }

    protected function listFilterOptions(): array
    {
        return [
            // Só empreiteiras que têm informe.
            'company' => $this->rememberFilterOptions('company-with-reports', fn () => Company::query()
                ->whereExists(fn ($q) => $q->selectRaw('1')->from('work_reports')->whereColumn('work_reports.company_id', 'companies.id'))
                ->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['v' => (string) $c->id, 'l' => (string) $c->name])->all()),
        ] + $this->locationFilterOptions();
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . $this->service->uuid;
    }

    public function updatedSearch()
    {
        $this->gotoPage(1);
    }

    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function filterStatus()
    {
        $this->not_assigned = !$this->not_assigned;
        $this->gotoPage(1);
    }

    /** "Limpar tudo": filtros, busca e atalho. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters      = [];
        $this->search       = '';
        $this->not_assigned = false;
        $this->afterListFiltersChanged();
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
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

        $user_id       = Auth()->user()->id;
        $service_uuid  = $this->service->uuid;
        $initial_limit = $this->notes_limits; // Limite inicial de notas atribuídas
        $limit_max     = $this->notes_limits_max - $this->notes_limits;
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
        $user = User::find(Auth()->User()->id);

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
            'company_id'  => $user->company_id,
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
                'note_id'       => $this->note->id,
                'service_id'    => $production->service_id,
                'user_id'       => Auth()->User()->id,
                'info'          => "Usuário {$user->name} atribuiu a Nota/OV.",
                'status'        => 2,
                'production_id' => $production->id,
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
     * Production de Publicação em vigor da Nota nesta atividade. A produção pertence ao informe a que está ligada; uma
     * ligada a outro informe da nota não é exibida aqui. Sem nenhum vínculo (legado/Ramal) vale a última da atividade.
     */
    public function hasPublication(Note $note)
    {
        $productions  = $note->relationLoaded('Productions') ? $note->Productions->where('service_id', $this->service->uuid) : $note->Productions()->where('service_id', $this->service->uuid)->get();
        $workReportId = $note->WorkForm?->id;

        if ($workReportId && $productions->isNotEmpty()) {
            $links = $this->publicationLinks;

            $productions = $productions->filter(fn ($production) => !isset($links[$production->id])
                || (int) $links[$production->id] === (int) $workReportId);
        }

        return $productions->last() ?: false;
    }

    /** Consulta da lista: Notas elegíveis para Publicação (mesma do Wall) mais busca, "só livres" e ordenação por prazo. */
    public function listQuery()
    {
        $uuid   = $this->service->uuid;
        $term   = $this->search;
        $filter = $this->noteFilter->filter($this->filter_group, true, $this->explicitFilters());

        return $filter
            ->when($term, fn ($q) => $q->where(function ($w) use ($term) {
                $like = '%' . $term . '%';
                $w->where('note', 'like', $like)
                    ->orWhere('material', 'like', $like)
                    ->orWhere('numPedido', 'like', $like)
                    ->orWhere('group2', 'like', $like);
            }))
            ->when($this->not_assigned, function ($q) use ($uuid) {
                $q->where(function ($w) use ($uuid) {
                    $w->whereDoesntHave('Productions', fn ($p) => $p->where('service_id', $uuid)->whereNotNull('user_id'))
                        ->orWhereHas('Productions', fn ($p) => $p->where('service_id', $uuid)
                            ->where(fn ($u) => $u->whereNull('user_id')->orWhere('user_id', '')));
                });
            })
            ->with([
                'Productions' => fn ($q) => $q->where('service_id', $uuid)->with('User:id,name'),
                'WorkForm.Company:id,name,deleted_at',
                'WorkForm.Note:id,type_note',
                'WorkForm.Orders',
                'RamalForm.Company:id,name,deleted_at',
            ])
            ->select(
                'notes.*',
                DB::raw("
                    CASE
                        WHEN notes.type_note = 2 THEN DATE_ADD(CURDATE(), INTERVAL notes.days_left DAY)
                        WHEN notes.type_note = 1 THEN STR_TO_DATE(CONCAT('28/', SUBSTRING(notes.mesalization, 2, 2), '/', SUBSTRING(notes.mesalization, 5)), '%d/%m/%Y')
                        ELSE NULL
                    END as prazo_final
                ")
            )
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
            ->orderBy('prazo_final', 'ASC');
    }

    /** Filtros no formato do `NoteFilter` (company/rubrica/region/regional/city). */
    private function explicitFilters(): array
    {
        return $this->filters;
    }

    public function getListsProperty()
    {
        return $this->listQuery()->paginate($this->perPage);
    }

    public function render()
    {
        $lists = $this->lists;

        // Vínculo Production -> informe numa consulta só (antes era uma por linha).
        $productionIds = $this->serviceProductionIds($lists->items(), $this->service->uuid);

        $this->publicationLinks = $productionIds
            ? WorkReportFlowProduction::query()
                ->whereIn('production_id', $productionIds)
                ->where('stage', WorkReportFlowProduction::STAGE_PUBLICATION)
                ->where('is_current', true)
                ->pluck('work_report_id', 'production_id')
                ->all()
            : [];

        $presenter = app(PublicationListRowPresenter::class);
        $user      = Auth()->User();
        $canSeeAll = $user->management || $user->superadm;

        $rows = collect($lists->items())->map(function (Note $list) use ($presenter, $user, $canSeeAll) {
            $production = $this->hasPublication($list) ?: null;
            $row        = $presenter->presentWith($list, $production, true);

            // Só gestão, superadm ou o próprio responsável enxergam o número da nota.
            $row['noteVisible'] = $canSeeAll || ($row['block'] && $production && $production->user_id === $user->id);

            return $row;
        })->values();

        return view('livewire.services.publication.main', [
            'lists'                => $lists,
            'rows'                 => $rows,
            'canceledByProduction' => $this->buildCanceledWorkReportByProductionIds($productionIds),
            'update'               => Cache::remember('publication-dispatch:has-bancoupdate', 60, fn () => Bancoupdate::query()->exists()),
        ]);
    }
}
