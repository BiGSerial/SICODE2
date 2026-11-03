<?php

namespace App\Http\Livewire\Services\Analises;

use App\Custom\{Notestatus, RuleBuilder};
use App\Http\Livewire\Concerns\WithListFilters;
use App\Models\{Bancoupdate, Note, Notetimeline, Production, Service, User};
use App\Services\Analises\AnalysisListRowPresenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\{Component, WithPagination};

class Main extends Component
{
    use WithListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $note;

    public $last_update;

    public $advanceSearch;

    public $multiSearch = [];

    public $selectAll = false;

    public $selected = [];

    //Botão de  nao atribuído.
    public $not_assigned = false;

    public $assigned_mmgd = false;

    protected $listeners = [
        'refresh_service'        => '$refresh',
        'getCopy'                => 'copy',
        'confirm_accompany'      => 'add_to_accompany',
        'confirm_accompany_mass' => 'add_to_accompany_mass',
    ];

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'buscar'],
        'page'         => ['except' => 1, 'as' => 'p'],
        'perPage'      => ['as' => 'pp'],
        'not_assigned' => ['except' => false, 'as' => 'livres'],
        'filters'      => ['except' => [], 'as' => 'f'],
    ];

    /** @var array<int, int>|null Ids de todo o recorte filtrado (uma consulta por requisição). */
    private ?array $filteredIdsMemo = null;

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('analises-service:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    protected function listFilterDefinitions(): array
    {
        return ['rubrica' => ['label' => 'Rubrica']];
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

    public function updatedSearch()
    {
        $this->multiSearch = [];
        $this->gotoPage(1);
    }

    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    /** "Limpar tudo": filtros, busca (simples e em massa) e atalho. */
    public function clearAllCriteria(): void
    {
        $this->filters       = [];
        $this->search        = '';
        $this->multiSearch   = [];
        $this->advanceSearch = '';
        $this->not_assigned  = false;
        $this->afterListFiltersChanged();
    }

    public function clearSelection(): void
    {
        $this->selected  = [];
        $this->selectAll = false;
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
        $user   = User::find(Auth()->User()->id);
        $result = $this->assignNoteToCurrentUser($this->note, $user);

        if ($result['ok']) {
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
                'title'    => 'OOOOPS! NOTA/OV TRATADA OU EM TRATAMENTO',
                'html'     => $result['message'],
            ]);
        }
    }

    public function go_att_mass()
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota selecionada',
                'timer'    => 2500,
            ]);

            return;
        }

        $count = count($this->selected);
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Atribuir em massa',
            'msg'           => "Você deseja atribuir <strong>{$count}</strong> Nota(s)/OV(s) para você?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Atribua!',
            'btnCanceltxt'  => 'Não, Cancele!',
            'action'        => 'confirm_accompany_mass',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum serviço foi atribuído.',
        ]);
    }

    public function add_to_accompany_mass()
    {
        $user  = User::find(Auth()->User()->id);
        $notes = Note::whereIn('id', $this->selected)->get();

        $success = 0;
        $errors  = [];

        foreach ($notes as $note) {
            $result = $this->assignNoteToCurrentUser($note, $user);

            if ($result['ok']) {
                $success++;
            } else {
                $errors[] = "{$note->note}: {$result['plain']}";
            }
        }

        $this->selected  = [];
        $this->selectAll = false;
        $this->emit('refresh_service');

        if (count($errors)) {
            $msg = implode('<br>', array_slice($errors, 0, 10));

            if (count($errors) > 10) {
                $msg .= '<br>...';
            }

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => $success ? 'warning' : 'error',
                'title'    => "Atribuição concluída ({$success} sucesso, " . count($errors) . ' falha)',
                'html'     => $msg,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => "Atribuição em massa concluída ({$success})",
            'timer'    => 2500,
        ]);
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->gotoPage(1);
            $this->search      = '';
            $this->multiSearch = preg_split('/[\s,;]+/', trim($this->advanceSearch), -1, PREG_SPLIT_NO_EMPTY);
            $this->multiSearch = array_map('trim', $this->multiSearch);
            $this->dispatchBrowserEvent('hideModal');
        } else {
            $this->multiSearch = [];
        }
    }

    /** Ids de todo o recorte filtrado (todas as páginas), numa consulta só por requisição. */
    private function filteredIds(): array
    {
        return $this->filteredIdsMemo ??= $this->baseQuery()->pluck('id')->toArray();
    }

    public function setSelectAllFiltered()
    {
        $ids = $this->filteredIds();

        if ($this->selectAll) {
            foreach ($ids as $id) {
                if (!in_array($id, $this->selected)) {
                    $this->selected[] = $id;
                }
            }
        } else {
            $this->selected = array_values(array_diff($this->selected, $ids));
        }
    }

    public function checkAllSelect()
    {
        $ids = $this->filteredIds();

        if (!count($ids)) {
            $this->selectAll = false;

            return false;
        }

        $this->selectAll = empty(array_diff($ids, $this->selected));

        return $this->selectAll;
    }

    public function filterStatus()
    {
        $this->not_assigned = !$this->not_assigned;
        $this->gotoPage(1);
    }

    private function baseQuery()
    {
        $query = Note::query()->excludeCanceledFullDone();

        RuleBuilder::applyRules($query, $this->service->Status);

        if (count($this->multiSearch)) {
            $query->where(function ($q) {
                $q->whereIn('note', $this->multiSearch)
                    ->orWhereIn('numPedido', $this->multiSearch);
            });
        }

        $rubrica = $this->filterValues('rubrica');

        $query->when($this->search, function ($q, $s) {
            return $q->where(function ($query) use ($s) {
                $query->where('note', 'like', '%' . $s . '%')
                    ->orWhere('group2', 'like', '%' . $s . '%');
            });
        })->when($rubrica, function ($q) use ($rubrica) {
            return $q->where(function ($query) use ($rubrica) {
                $query->whereIn('rubrica', $rubrica)
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

        $query->with(['Productions' => fn ($q) => $q->where('service_id', $this->service->uuid)->with('User:id,name')])
            ->orderBy('dt_created', 'ASC');

        return $query;

    }

    public function getListsProperty()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    public function render()
    {
        $this->filteredIdsMemo = null;

        $lists     = $this->lists;
        $user      = Auth()->User();
        $canSee    = $user->management || $user->superadm;
        $presenter = app(AnalysisListRowPresenter::class);

        return view('livewire.services.analises.main', [
            'lists'  => $lists,
            'rows'   => $lists->getCollection()->map(fn (Note $note) => $presenter->presentSelfAssign($note, $this->service->uuid, $canSee)),
            'update' => Cache::remember('analises-service:bancoupdate', 60, fn () => Bancoupdate::orderByDesc('created_at')->first()),
        ]);
    }

    private function assignNoteToCurrentUser(Note $note, User $user): array
    {
        $check = Production::where('note_id', $note->id)
            ->where('dt_note', $note->dt_status)
            ->where('service_id', $this->service->uuid)
            ->where(function ($q) {
                $q->where('completed_at', '>', Carbon::now()->subHours(24))
                    ->orWhere('completed', false);
            })->first();

        if ($check) {
            $check->loadMissing(['User', 'Company', 'Service']);

            $name   = $check->User?->name ?? ($check->Company ? "{$check->Company->name} (sem usuário atribuído)" : 'Desconhecido');
            $status = Notestatus::status($check->status)->status;

            return [
                'ok'      => false,
                'plain'   => "já está {$status} por {$name}",
                'message' => "<p><strong>{$note->note}</strong> está sinalizada como <strong>{$status}</strong> em <strong>{$check->Service->service}</strong> por <strong>{$name}</strong></p>
                    <p class='text-bg-info p-2 mt-2'>Verifique com um gestor para atribuir manualmente esta NOTA/OV caso considere um erro atribuição.</p>",
            ];
        }

        $production = Production::Create([
            'note_id'     => $note->id,
            'service_id'  => $this->service->uuid,
            'user_id'     => $user->id,
            'company_id'  => $user->company_id,
            'dispatch_by' => $user->id,
            'att_by'      => $user->id,
            'dt_note'     => $note->dt_status,
            'status_note' => $note->nstats,
            'dispatch_at' => date('Y-m-d H:i:s'),
            'att_at'      => date('Y-m-d H:i:s'),
            'status'      => 2,
            'dhstats'     => $note->dt_status,
        ]);

        if (!$production) {
            return [
                'ok'      => false,
                'plain'   => 'erro ao criar produção',
                'message' => "Erro ao tentar atribuir {$note->note}.",
            ];
        }

        Notetimeline::Create([
            'note_id'       => $note->id,
            'service_id'    => $production->service_id,
            'user_id'       => Auth()->User()->id,
            'info'          => "Usuário {$user->name} atribuiu a Nota/OV.",
            'status'        => 2,
            'production_id' => $production->id,
        ]);

        return ['ok' => true, 'plain' => '', 'message' => ''];
    }
}
