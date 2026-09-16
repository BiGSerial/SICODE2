<?php

namespace App\Http\Livewire\Dispatchs;

use App\Models\Production;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Historic extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public int $perPage = 50;
    public string $search = '';
    public ?string $date_from = null;
    public ?string $date_to = null;
    public ?string $date_prod_s = null;
    public ?string $dispatcher_s = null;
    public string $dispatcher_search = '';
    public string $assigned_search = '';
    public ?string $assigned_s = null;
    public array $dispatcher_l = [];
    public array $assigned_l = [];

    public array $meses = [
        1  => 'Janeiro',
        2  => 'Fevereiro',
        3  => 'Março',
        4  => 'Abril',
        5  => 'Maio',
        6  => 'Junho',
        7  => 'Julho',
        8  => 'Agosto',
        9  => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'date_from' => ['except' => ''],
        'date_to' => ['except' => ''],
        'date_prod_s' => ['except' => ''],
        'dispatcher_s' => ['except' => ''],
        'assigned_s' => ['except' => ''],
    ];

    public function mount(string $service): void
    {
        $this->service = Service::where('uuid', $service)->firstOrFail();
    }

    public function updated($name): void
    {
        if (in_array($name, [
            'search',
            'date_from',
            'date_to',
            'date_prod_s',
            'dispatcher_s',
            'assigned_s',
            'perPage',
        ], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->date_from = null;
        $this->date_to = null;
        $this->date_prod_s = null;
        $this->dispatcher_s = null;
        $this->assigned_s = null;
        $this->dispatcher_search = '';
        $this->assigned_search = '';
        $this->resetPage();
    }

    public function getListsProperty()
    {
        $this->dispatcher_l = $this->buildDispatchersList()->all();
        $this->assigned_l = $this->buildAssignedUsersList()->all();

        return $this->baseQuery()
            ->with([
                'Note:id,note,rubrica,lexp,group1,material,nstats',
                'User:id,name',
                'Company:id,name',
                'Analise:id,production_id,conclusion',
            ])
            ->select([
                'productions.id',
                'productions.note_id',
                'productions.service_id',
                'productions.user_id',
                'productions.company_id',
                'productions.dispatch_by',
                'productions.dispatch_at',
                'productions.att_at',
                'productions.completed_at',
                'productions.completed',
                'productions.rejected',
                'productions.status',
                'productions.status_note',
                'productions.stopped',
                'productions.d5',
                'productions.transferred',
            ])
            ->orderByDesc('productions.dispatch_at')
            ->orderByDesc('productions.id')
            ->paginate($this->perPage);
    }

    public function getPeriodsProperty()
    {
        return $this->baseQuery(false)
            ->selectRaw('DATE_FORMAT(productions.dispatch_at, "%Y-%m") as mes_ano, COUNT(*) as total')
            ->groupBy('mes_ano')
            ->orderByDesc('mes_ano')
            ->get();
    }

    private function baseQuery(bool $withPeriodFilter = true)
    {
        $search = trim($this->search);
        $dispatcherId = $this->visibleDispatcherId();

        return Production::query()
            ->where('productions.service_id', $this->service->uuid)
            ->whereNotNull('productions.dispatch_at')
            ->where('productions.dispatch_by', $dispatcherId)
            ->when($this->assigned_s, fn ($q) => $q->where('productions.user_id', $this->assigned_s))
            ->when($withPeriodFilter && $this->date_prod_s, function ($q) {
                $q->whereRaw('DATE_FORMAT(productions.dispatch_at, "%Y-%m") = ?', [$this->date_prod_s]);
            })
            ->when($this->date_from, fn ($q) => $q->whereDate('productions.dispatch_at', '>=', $this->date_from))
            ->when($this->date_to, fn ($q) => $q->whereDate('productions.dispatch_at', '<=', $this->date_to))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function (Builder $nested) use ($search) {
                    $nested->whereRelation('Note', 'note', 'like', '%' . $search . '%')
                        ->orWhereRelation('Note', 'material', 'like', '%' . $search . '%')
                        ->orWhereRelation('Note', 'rubrica', 'like', '%' . $search . '%');
                });
            });
    }

    private function visibleDispatcherId(): string
    {
        if (auth()->user()?->can('superadm') && $this->dispatcher_s) {
            return (string) $this->dispatcher_s;
        }

        return (string) auth()->id();
    }

    private function buildDispatchersList()
    {
        if (!auth()->user()?->can('superadm')) {
            return collect();
        }

        return User::withTrashed()
            ->whereIn('id', function ($q) {
                $q->select('dispatch_by')
                    ->from('productions')
                    ->where('service_id', $this->service->uuid)
                    ->whereNotNull('dispatch_by')
                    ->whereNotNull('dispatch_at');
            })
            ->when($this->dispatcher_search, fn ($q) => $q->where('name', 'like', '%' . $this->dispatcher_search . '%'))
            ->select(['id', 'name'])
            ->orderBy('name')
            ->limit($this->dispatcher_search ? 300 : 80)
            ->get();
    }

    private function buildAssignedUsersList()
    {
        return User::withTrashed()
            ->whereIn('id', function ($q) {
                $q->select('user_id')
                    ->from('productions')
                    ->where('service_id', $this->service->uuid)
                    ->where('dispatch_by', $this->visibleDispatcherId())
                    ->whereNotNull('user_id')
                    ->whereNotNull('dispatch_at');
            })
            ->when($this->assigned_search, fn ($q) => $q->where('name', 'like', '%' . $this->assigned_search . '%'))
            ->select(['id', 'name'])
            ->orderBy('name')
            ->limit($this->assigned_search ? 300 : 80)
            ->get();
    }

    public function render()
    {
        return view('livewire.dispatchs.historic', [
            'lists' => $this->lists,
            'periods' => $this->periods,
        ]);
    }
}
