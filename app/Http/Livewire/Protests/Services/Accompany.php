<?php

namespace App\Http\Livewire\Protests\Services;

use App\Models\ProtestJob;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Accompany extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /** Filtros */
    public int $perPage = 50;
    public string $search = '';
    public ?string $selectedUserId = null;
    public bool $onlySelectedUser = false;

    protected $queryString = [
        'page'           => ['except' => 1],
        'perPage'        => ['except' => 50],
        'search'         => ['except' => ''],
        'selectedUserId' => ['except' => null],
        'onlySelectedUser' => ['except' => false],
    ];

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedUserId($value): void
    {
        if (empty($value)) {
            $this->onlySelectedUser = false;
        }

        $this->resetPage();
    }

    public function updatingOnlySelectedUser(): void
    {
        $this->resetPage();
    }

    /**
     * Usuários sob a hierarquia do usuário logado (closure table user_closure)
     */
    protected function availableUsersQuery()
    {
        $viewer = auth()->user();

        if (!$viewer) {
            return User::query()->whereRaw('1 = 0');
        }

        return $viewer
            ->descendantsQuery(
                includeSelf: true,
                includeDelegations: true,
                includeDelegatesTreesForPrincipal: true
            )
            ->orderBy('users.name')
            ->distinct();
    }

    /**
     * Retorna IDs do usuário selecionado + descendentes diretos/indiretos.
     */
    protected function descendantsOf(string $userId): array
    {
        $user = User::find($userId);

        if (!$user) {
            return [];
        }

        return $user
            ->descendantsQuery(
                includeSelf: true,
                includeDelegations: true,
                includeDelegatesTreesForPrincipal: true
            )
            ->pluck('users.id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Accessor Livewire: $this->availableUsers
     */
    public function getAvailableUsersProperty()
    {
        return $this->availableUsersQuery()->get();
    }

    /**
     * Query base: jobs em aberto da equipe (subordinados + opcionalmente o próprio)
     */
    protected function baseQuery()
    {
        // IDs da galera sob a hierarquia + opcionalmente o próprio gestor
        $subordinatesIds = $this->availableUsers
            ->pluck('id')
            ->push(auth()->id())
            ->unique()
            ->values()
            ->all();

        return ProtestJob::query()
            ->where(function ($q) {
                $q->whereRelation('medProtest', 'statusSist', 'MEDA')
                  ->orWhere(fn ($qq) => $qq->open());
            })
            ->whereIn('owner_id', $subordinatesIds)
            ->with([
                'protest.Notes',
                'medProtest' => function ($q) {
                    $q->with([
                        'Notes',
                        'Comments' => function ($cq) {
                            $cq->latest();
                        },
                    ]);
                },
                'Comments' => function ($q) {
                    $q->latest();
                },
                'creator:id,name',
                'owner:id,name,email',
            ])
            ->when($this->selectedUserId, function ($q) {
                $teamIds = $this->onlySelectedUser
                    ? [$this->selectedUserId]
                    : $this->descendantsOf($this->selectedUserId);

                $q->whereIn('owner_id', $teamIds);
            })
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';

                $q->where(function ($qq) use ($term) {
                    $qq->where('id', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhereHas('protest', function ($sub) use ($term) {
                            $sub->where('nota', 'like', $term)
                                ->orWhere('cidade', 'like', $term)
                                ->orWhere('txtGrpCodificacao', 'like', $term);
                        })
                        ->orWhereHas('owner', function ($sub) use ($term) {
                            $sub->where('name', 'like', $term);
                        });
                });
            })
            ->orderByDesc('priority')
            ->orderBy('sla_due_at')
            ->orderByDesc('sent_at');
    }

    /** Lista paginada */
    public function getListProperty()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedUserId', 'perPage', 'onlySelectedUser']);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.protests.services.accompany', [
            'list'           => $this->list,
            'availableUsers' => $this->availableUsers,
        ]);
    }
}
