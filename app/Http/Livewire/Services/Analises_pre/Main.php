<?php

namespace App\Http\Livewire\Services\Analises_pre;

use App\Custom\{Notestatus, RuleBuilder};
use App\Http\Livewire\Concerns\WithListFilters;
use App\Models\{Bancoupdate, Note, Notetimeline, Production, Service, User};
use App\Services\Dispatch\PreAnalysisListRowPresenter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\{Component, WithPagination};

/**
 * Serviços -> Lista para Análises Pré (auto-atribuição), no padrão de listas operacionais
 * (docs/PADRAO_LISTAS_OPERACIONAIS.md). A pessoa assume a Nota/OV para si; a regra de bloqueio não mudou.
 */
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
        'search'        => ['except' => '', 'as' => 'buscar'],
        'page'          => ['except' => 1, 'as' => 'p'],
        'perPage'       => ['as' => 'pp'],
        'not_assigned'  => ['except' => false, 'as' => 'livres'],
        'assigned_mmgd' => ['except' => false, 'as' => 'mmgd'],
        'filters'       => ['except' => [], 'as' => 'f'],
    ];

    /** Ids de todas as notas do filtro, lidos uma vez por requisição (seleção "todas do filtro"). */
    private ?array $filteredIdsMemo = null;

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('pre-analysis-service:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    // ---------- Filtros (WithListFilters): só Rubrica, como na tela anterior ----------

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

    /** "Limpar tudo": filtros, busca (simples e em massa) e atalhos. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters       = [];
        $this->search        = '';
        $this->multiSearch   = [];
        $this->advanceSearch = '';
        $this->not_assigned  = false;
        $this->assigned_mmgd = false;
        $this->afterListFiltersChanged();
    }

    public function clearSelection(): void
    {
        $this->selected  = [];
        $this->selectAll = false;
    }

    public function updatedSearch()
    {
        $this->multiSearch = [];
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
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
        $this->assigned_mmgd = !$this->assigned_mmgd;
        $this->resetPage();
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

    /** Limpa a busca em massa (e fecha o filtro por lista de notas). */
    public function clean()
    {
        $this->advanceSearch = '';
        $this->multiSearch   = [];
        $this->resetPage();
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

    /**
     * "Selecionar todas" marca quando todas as notas do filtro (todas as páginas) estão selecionadas. A lista de ids só é lida
     * quando há seleção suficiente para isso ser possível (antes eram duas leituras completas a cada resposta).
     */
    public function checkAllSelect($total = null)
    {
        if (!$total || count($this->selected) < $total) {
            return $this->selectAll = false;
        }

        $ids = $this->filteredIds();

        return $this->selectAll = $ids !== [] && empty(array_diff($ids, $this->selected));
    }

    private function filteredIds(): array
    {
        return $this->filteredIdsMemo ??= $this->baseQuery()->reorder()->pluck('id')->all();
    }

    public function filterStatus()
    {
        $this->not_assigned = !$this->not_assigned;
        $this->resetPage();
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

        $query->when($this->search, function ($q, $s) {
            return $q->where(function ($query) use ($s) {
                $query->where('note', 'like', '%' . $s . '%');
                // ->orWhere('material', 'like', '%' . $s . '%')
                // ->orWhere('numPedido', 'like', '%' . $s . '%');
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

        $query->when($this->filterValues('rubrica'), function ($q, $rubricas) {
            return $q->where(function ($query) use ($rubricas) {
                $query->whereIn('rubrica', $rubricas)
                    ->orWhereNull('rubrica');
            });
        })
            ->when($this->assigned_mmgd, function ($q) {
                return $q->whereIn('num_material', [21, 78, 79]);
            })
            ->orderBy('pze_parecer', 'DESC')
            ->orderBy('dt_created');

        return $query;
    }

    public function getListsProperty()
    {
        return $this->baseQuery()
            ->with('Productions.User:id,name')
            ->paginate($this->perPage);
    }

    public function render()
    {
        $this->filteredIdsMemo = null;

        $lists = $this->getListsProperty();
        $this->checkAllSelect($lists->total());

        $presenter = app(PreAnalysisListRowPresenter::class);
        $rows      = $lists->getCollection()->map(fn (Note $note) => $presenter->service($note, $this->service->uuid));

        return view('livewire.services.analises_pre.main', [
            'lists'  => $lists,
            'rows'   => $rows,
            'update' => Cache::remember('pre-analysis-service:bancoupdate', 60, fn () => Bancoupdate::orderByDesc('created_at')->first()),
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
