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
    public ?int $selectedUserId = null;

    protected $queryString = [
        'page'           => ['except' => 1],
        'perPage'        => ['except' => 50],
        'search'         => ['except' => ''],
        'selectedUserId' => ['except' => null],
    ];

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedUserId(): void
    {
        $this->resetPage();
    }

    /**
     * Usuários sob a hierarquia do usuário logado (closure table user_closure)
     */
    protected function availableUsersQuery()
    {
        $userId = auth()->id();

        return User::query()
            ->join('user_closure as uc', 'uc.descendant_id', '=', 'users.id')
            ->where('uc.ancestor_id', $userId)
            ->where('uc.depth', '>', 0) // abaixo na hierarquia
            ->select('users.*')
            ->orderBy('users.name')
            ->distinct();
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
            ->open() // scopeOpen do modelo
            ->whereIn('owner_id', $subordinatesIds)
            ->with([
                'protest',
                'medProtest',
                'Comments' => function ($q) {
                    $q->latest();
                },
                'creator:id,name',
                'owner:id,name,email',
            ])
            ->when($this->selectedUserId, function ($q) {
                $q->where('owner_id', $this->selectedUserId);
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
        $this->reset(['search', 'selectedUserId', 'perPage']);
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
