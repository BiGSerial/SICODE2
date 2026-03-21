<?php

namespace App\Http\Livewire\Admin\User;

use App\Helpers\TextFormatter;
use App\Jobs\Reports\ExportUserListJob;
use App\Models\{Company, User};
use Livewire\{Component, WithPagination};

class Table extends Component
{
    use TextFormatter;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $users;

    public ?User $userCompany = null;

    public $perPage = 30;

    public $show_update = false;

    public $user_id;

    public $search;

    public $companies;

    public $company_s;

    public $selectAll;

    public $selected = [];

    public $selectedCompany;

    public $preText;

    public $multiSearch = [];

    public $searchBy = 'all';

    public $statusFilter = 'all';

    public $deletedFilter = 'active';

    public $roleFilter = '';

    public ?User $master = null;

    private array $allowedRoleFilters = [
        'superadm',
        'admin',
        'management',
        'engineer',
        'responsible',
        'operator',
        'user',
        'onlyparner',
        'analyst',
    ];

    protected $listeners = [
        'refresh_table_user' => '$refresh',
        'refresh_table_mass_user' => 'refreshAll',
    ];

    protected $queryString = [
        'search'          => ['except' => '', 'as' => 'buscar'],
        'page'            => ['except' => 1, 'as' => 'pag'],
        'selectedCompany' => ['except' => '', 'as' => 'empresa'],
        'searchBy'        => ['except' => 'all', 'as' => 'campo'],
        'statusFilter'    => ['except' => 'all', 'as' => 'status'],
        'deletedFilter'   => ['except' => 'active', 'as' => 'lixeira'],
        'roleFilter'      => ['except' => '', 'as' => 'perfil'],
    ];

    public function mount()
    {
        $this->master = User::first();

        if (!Auth()->User()->contract) {
            $this->companies = Company::orderBy('name')->get();
        } elseif (Auth()->User()->Companies->count()) {
            $this->userCompany = auth()->user();
            $this->companies = $this->userCompany->Companies()->get();
        } else {
            $this->companies = Company::where('id', Auth()->User()->company_id)->orderBy('name')->get();
        }

        $this->perPage = 30;
    }

    public function update_user($id)
    {
        $this->user_id = $id;
        $this->show_update = true;

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'update_modal',
        ]);
    }

    public function multiSearch()
    {
        if ($this->preText) {
            $this->search = '';
            $this->multiSearch = $this->formatTextToArray($this->preText);
            $this->gotoPage(1);
        }

        $this->dispatchBrowserEvent('hideModal');
    }

    public function updatedSearch()
    {
        if ($this->search) {
            $this->multiSearch = [];
            $this->preText = '';
        }

        $this->gotoPage(1);
    }

    public function updatedPerPage($value)
    {
        if ((int) $value !== 30) {
            $this->perPage = 30;
        }
    }

    public function updatedSearchBy()
    {
        $this->gotoPage(1);
    }

    public function updatedStatusFilter()
    {
        $this->gotoPage(1);
    }

    public function updatedDeletedFilter()
    {
        $this->gotoPage(1);
    }

    public function updatedRoleFilter()
    {
        $this->gotoPage(1);
    }

    public function updatedSelectedCompany()
    {
        $this->gotoPage(1);
    }

    public function refreshAll()
    {
        $this->selected = [];
        $this->emitSelf('refresh_table_user');
    }

    public function editInMass()
    {
        $this->emitTo('admin.user.actions.usuario-mass', 'alterUsers', $this->selected);
    }

    public function checkAllSelect($items)
    {
        $items = $items->pluck('id')->toArray();

        $this->selectAll = empty(array_diff($items, $this->selected));

        return $this->selectAll;
    }

    public function setSelectAll()
    {
        $idsToKeep = $this->user->pluck('id')->toArray();

        if ($this->selectAll) {
            foreach ($idsToKeep as $id) {
                if (!in_array($id, $this->selected)) {
                    $this->selected[] = $id;
                }
            }
        } else {
            $newSelected = [];

            foreach ($this->selected as $id) {
                if (!in_array($id, $idsToKeep)) {
                    $newSelected[] = $id;
                }
            }

            $this->selected = $newSelected;
        }
    }

    public function export_excel(): void
    {
        $params = [
            'search' => $this->search,
            'searchBy' => $this->searchBy,
            'selectedCompany' => $this->selectedCompany,
            'multiSearch' => $this->multiSearch,
            'statusFilter' => $this->statusFilter,
            'deletedFilter' => $this->deletedFilter,
            'roleFilter' => $this->roleFilter,
        ];

        ExportUserListJob::dispatch($params, (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Exportação iniciada',
            'html' => "<div class='card'><div class='card-body'><p>A lista de usuários está sendo gerada em fila.</p><p class='fw-bold'>Você será notificado quando o arquivo estiver pronto.</p></div></div>",
            'timer' => 4500,
        ]);
    }

    public function getUserProperty()
    {
        return User::query()
            ->when(
                Auth()->User()->contract,
                function ($q) {
                    if (Auth()->User()->Companies->count()) {
                        return $q->whereRelation('Employee.Contract.company', function ($sq) {
                            return $sq->whereIn('id', Auth()->User()->Companies->pluck('id'));
                        });
                    }

                    return $q->whereRelation('Employee.Contract.company', function ($sq) {
                        return $sq->whereIn('id', [Auth()->User()->Employee->Contract->company->id]);
                    });
                }
            )
            ->withTrashed()
            ->when($this->deletedFilter === 'active', function ($q) {
                return $q->whereNull('users.deleted_at');
            })
            ->when($this->deletedFilter === 'deleted', function ($q) {
                return $q->onlyTrashed();
            })
            ->when($this->search, function ($q, $s) {
                return $q->where(function ($searchQuery) use ($s) {
                    $term = trim((string) $s);
                    $like = '%'.$term.'%';

                    if ($this->searchBy === 'email') {
                        return $searchQuery->where('email', 'like', $like);
                    }

                    if ($this->searchBy === 'registration') {
                        return $searchQuery->where('Registration', 'like', $like);
                    }

                    if ($this->searchBy === 'id') {
                        return $searchQuery->where('id', 'like', $like);
                    }

                    return $searchQuery->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('Registration', 'like', $like)
                        ->orWhere('id', 'like', $like);
                });
            })
            ->when($this->selectedCompany, function ($q, $s) {
                return $q->whereRelation('Employee.Contract', 'company_id', $s);
            })
            ->when($this->multiSearch, function ($q) {
                return $q->where(function ($multiQuery) {
                    $multiQuery->whereIn('id', $this->multiSearch)
                        ->orWhereIn('email', $this->multiSearch)
                        ->orWhereIn('Registration', $this->multiSearch);
                });
            })
            ->when($this->statusFilter === 'online', function ($q) {
                return $q->whereRelation('Watchdog', 'watchdog', true);
            })
            ->when($this->statusFilter === 'offline', function ($q) {
                return $q->where(function ($offlineQuery) {
                    $offlineQuery->whereDoesntHave('Watchdog')
                        ->orWhereRelation('Watchdog', 'watchdog', false);
                });
            })
            ->when(in_array($this->roleFilter, $this->allowedRoleFilters, true), function ($q) {
                $role = $this->roleFilter;
                return $q->where($role, true);
            })
            ->with('Employee.Contract.Company', 'Watchdog', 'ToServices.Service')
            ->orderBy('name');
    }

    public function render()
    {
        return view('livewire.admin.user.table', [
            'users_l' => $this->user->paginate(30),
            'totalUsers' => $this->user->count(),
            'onlineUsers' => (clone $this->user)->whereRelation('Watchdog', 'watchdog', true)->count(),
        ]);
    }
}
