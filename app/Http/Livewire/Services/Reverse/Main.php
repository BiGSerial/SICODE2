<?php

namespace App\Http\Livewire\Services\Reverse;

use App\Custom\{Notestatus, RuleBuilder};
use App\Http\Livewire\Concerns\WithListFilters;
use App\Models\{Note, Notetimeline, Production, Service, User};
use App\Services\Reverse\ReverseListRowPresenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\{Component, WithPagination};

/**
 * Serviços · Fluxo Reverso: fila de auto-atribuição no padrão de listas operacionais (docs/PADRAO_LISTAS_OPERACIONAIS.md).
 */
class Main extends Component
{
    use WithPagination;
    use WithListFilters;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $note;

    public $last_update;

    // Atalho "Só livres".
    public $not_assigned = false;

    protected $listeners = [
        'refresh_service'   => '$refresh',
        'getCopy'           => 'copy',
        'confirm_accompany' => 'add_to_accompany',
    ];

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'buscar'],
        'page'         => ['except' => 1, 'as' => 'p'],
        'perPage'      => ['except' => 100, 'as' => 'pp'],
        'not_assigned' => ['except' => false, 'as' => 'livres'],
        'filters'      => ['except' => [], 'as' => 'f'],
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('reverse-service:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    // ---------- Filtros (WithListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'rubrica' => ['label' => 'Rubrica'],
        ];
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . $this->service->uuid;
    }

    protected function listFilterOptions(): array
    {
        return [
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => Note::query()
                ->where('nstats', $this->service->status)
                ->whereNotNull('rubrica')->where('rubrica', '!=', '')
                ->distinct()->orderBy('rubrica')->pluck('rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
        ];
    }

    /** "Limpar tudo": filtros, busca e atalho. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters      = [];
        $this->search       = '';
        $this->not_assigned = false;
        $this->afterListFiltersChanged();
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

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
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
        $user = User::find(Auth()->User()->id);

        $check = Production::where('note_id', $this->note->id)
        ->where('dt_note', $this->note->dt_status)
        ->where('service_id', $this->service->uuid)
        ->where(function ($q) {
            $q->where('completed_at', '>', Carbon::now()->subHours(24))
              ->orWhere('completed', false);
        })->first();

        if ($check) {
            $check->loadMissing(['User', 'Company', 'Service']);

            $name   = $check->User?->name ?? ($check->Company ? "{$check->Company->name} (sem usuário atribuído)" : 'Desconhecido');
            $status = Notestatus::status($check->status)->status;

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'OOOOPS! NOTA/OV TRATADA OU EM TRATAMENTO',
                'html'     => "<p><strong>{$this->note->note}</strong> está sinalizada como <strong>{$status}</strong> em <strong>{$check->Service->service}</strong> por <strong>{$name}</strong></p>
        <p class='text-bg-info p-2 mt-2'>Verifique com um gestor para atribuir manualmente esta NOTA/OV caso considere um erro atribuição.</p>",

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

    public function getListsProperty()
    {
        $query = Note::query()->excludeCanceledFullDone();

        RuleBuilder::applyRules($query, $this->service->Status);

        $query->when(trim((string) $this->search), function ($q, $s) {
            return $q->where(function ($query) use ($s) {
                $query->where('note', 'like', '%' . $s . '%')
                    ->orWhere('group2', 'like', '%' . $s . '%');
            });
        })->when($this->filterValues('rubrica'), function ($q, $rubricas) {
            return $q->where(function ($query) use ($rubricas) {
                $query->whereIn('rubrica', $rubricas)
                    ->orWhereNull('rubrica');
            });
        });

        if ($this->not_assigned) {
            $query->where(function ($q) {
                $q->doesntHave('Productions')
                    ->orWhereDoesntHave('Productions', function ($subquery) {
                        $subquery->where('service_id', $this->service->uuid)
                            ->where('confirmed', false);
                    });
            });
        }

        $query->whereNotIn('num_material', [21, 78, 79]);

        return $query->with(['Productions.User'])
            ->orderBy('days_left', 'ASC')
            ->paginate($this->perPage);
    }

    public function render()
    {
        $lists     = $this->lists;
        $user      = Auth()->User();
        $presenter = app(ReverseListRowPresenter::class);
        $seesNote  = (bool) ($user->management || $user->superadm);

        $rows = collect($lists->items())->map(fn (Note $note) => $presenter->serviceRow($note, $this->service->uuid, (int) $user->id, $seesNote));

        return view('livewire.services.reverse.main', [
            'lists'  => $lists,
            'rows'   => $rows,
            'update' => $this->last_update,
        ]);
    }
}
