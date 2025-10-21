<?php

namespace App\Http\Livewire\Admin\Hierarchy;

use App\Models\Company;
use App\Models\User;
use App\Models\UserDelegation;
use App\Services\HierarchyService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Board extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Buscas e filtros
    public string $leftSearch = '';
    public string $treeSearch = '';
    public ?string $companyFilter = '';

    // Seleção
    public ?string $selectedManagerId = null;
    public array $selectedCandidateIds = [];

    // Modal "Mover para..."
    public ?string $moveUserId = null;
    public string $moveTargetSearch = '';
    public ?string $moveTargetId = null;

    // Modal delegação
    public ?string $dlg_principal_id = null;
    public ?string $dlg_delegate_id  = null;
    public ?string $dlg_from = null;
    public ?string $dlg_to   = null;
    public string $dlg_reason = '';

    protected $queryString = [
        'leftSearch'       => ['except' => ''],
        'treeSearch'       => ['except' => ''],
        'selectedManagerId' => ['except' => ''],
        'companyFilter' => ['except' => ''],
    ];

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['leftSearch', 'companyFilter'])) {
            $this->resetPage('dir');
        }
    }

    /* -------- Esquerda: Lista de Usuários -------- */
    public function getDirectoryProperty()
    {
        $q = User::query()->select('id', 'name', 'email', 'manager_id', 'company_id')
            ->whereNull('deleted_at')
            ->with('company:id,name');

        if ($s = trim($this->leftSearch)) {
            $terms = preg_split('/[\s,;\n\r]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
            $q->where(function ($w) use ($terms) {
                foreach ($terms as $t) {
                    $w->orWhere('name', 'like', "%{$t}%")
                      ->orWhere('email', 'like', "%{$t}%");
                }
            });
        }

        if ($this->companyFilter) {
            $q->where('company_id', $this->companyFilter);
        }

        return $q->orderBy('name')->paginate(15, pageName: 'dir');
    }

    public function toggleCandidate(string $userId): void
    {
        if (in_array($userId, $this->selectedCandidateIds, true)) {
            $this->selectedCandidateIds = array_values(array_diff($this->selectedCandidateIds, [$userId]));
        } else {
            $this->selectedCandidateIds[] = $userId;
        }
    }

    public function clearCandidates(): void
    {
        $this->selectedCandidateIds = [];
    }

    public function assignCandidatesToManager(): void
    {
        if (!$this->selectedManagerId || empty($this->selectedCandidateIds)) {
            $this->dispatchBrowserEvent('toast', ['type' => 'warning','msg' => 'Selecione um gerente no organograma e pelo menos um candidato na lista.']);
            return;
        }
        $svc = app(HierarchyService::class);
        foreach ($this->selectedCandidateIds as $uid) {
            if ($uid === $this->selectedManagerId) {
                $this->dispatchBrowserEvent('toast', ['type' => 'warning','msg' => 'Não é possível atribuir um usuário a ele mesmo.']);
                continue;
            }
            try {
                $svc->moveSubtree($uid, $this->selectedManagerId);
            } catch (\InvalidArgumentException $e) {
                $this->dispatchBrowserEvent('toast', ['type' => 'error','msg' => 'Erro: ' . $e->getMessage()]);
            } catch (\Throwable $e) {
                $this->dispatchBrowserEvent('toast', ['type' => 'error','msg' => 'Ocorreu um erro ao atribuir: ' . $e->getMessage()]);
            }
        }
        $this->clearCandidates();
        $this->dispatchBrowserEvent('toast', ['type' => 'success','msg' => 'Atribuições concluídas.']);
        $this->emit('$refresh');
        $this->resetPage('dir');
    }

    /* -------- Centro: Organograma Focado / Geral -------- */
    public function selectManager(?string $userId): void
    {
        $this->selectedManagerId = $userId;
    }

    public function selectUserFromList(string $userId): void
    {
        $this->selectManager($userId);
        $this->dispatchBrowserEvent('hide-left-offcanvas');
    }

    public function setAsRoot(string $userId): void
    {
        try {
            app(HierarchyService::class)->moveSubtree($userId, null);
            $this->dispatchBrowserEvent('toast', ['type' => 'success','msg' => 'Definido como raiz.']);
            $this->emit('$refresh');
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('toast', ['type' => 'error','msg' => 'Erro: ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('toast', ['type' => 'error','msg' => 'Erro ao definir como raiz: ' . $e->getMessage()]);
        }
    }

    public function openMoveModal(string $userId): void
    {
        $this->moveUserId = $userId;
        $this->moveTargetSearch = '';
        $this->moveTargetId = null;
        $user = User::find($userId);
        $this->dispatchBrowserEvent('show-move-modal', ['userName' => $user->name ?? 'Usuário']);
    }

    public function confirmMove(): void
    {
        if (!$this->moveUserId) {
            return;
        }
        try {
            app(HierarchyService::class)->moveSubtree($this->moveUserId, $this->moveTargetId);
            $this->dispatchBrowserEvent('hide-move-modal');
            $this->dispatchBrowserEvent('toast', ['type' => 'success','msg' => 'Movido com sucesso.']);
            $this->emit('$refresh');
            if ($this->moveUserId === $this->selectedManagerId) {
                $this->selectedManagerId = $this->moveTargetId;
            }
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('toast', ['type' => 'error','msg' => 'Erro: ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('toast', ['type' => 'error','msg' => 'Erro ao mover: ' . $e->getMessage()]);
        }
    }

    public function getMoveTargetsProperty()
    {
        $q = User::query()->select('id', 'name', 'email')
            ->whereNull('deleted_at')
            ->where('id', '!=', $this->moveUserId);

        if ($this->moveUserId) {
            $descendantsOfMovingUser = DB::table('user_closure')
                                    ->where('ancestor_id', $this->moveUserId)
                                    ->pluck('descendant_id');
            $q->whereNotIn('id', $descendantsOfMovingUser);
        }

        if ($s = trim($this->moveTargetSearch)) {
            $q->where(function ($w) use ($s) {
                $w->orWhere('name', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%");
            });
        }
        return $q->orderBy('name')->limit(20)->get();
    }

    public function getFocusedHierarchyProperty(): array
    {
        if (!$this->selectedManagerId) {
            return [];
        }

        $focusedUser = User::query()->with('company:id,name')->whereNull('deleted_at')->find($this->selectedManagerId);
        if (!$focusedUser) {
            return [];
        }

        $allActiveUsers = User::query()->with('company:id,name')->select('id', 'name', 'email', 'manager_id', 'company_id')->whereNull('deleted_at')->get();

        $byManager = [];
        foreach ($allActiveUsers as $u) {
            $byManager[$u->manager_id ?? 'ROOT'][] = $u;
        }

        $buildSubtree = function ($parentId) use (&$buildSubtree, &$byManager) {
            $nodes = [];
            foreach ($byManager[$parentId] ?? [] as $u) {
                $nodes[] = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'company_name' => $u->company->name ?? null,
                    'children' => $buildSubtree($u->id),
                ];
            }
            return $nodes;
        };

        $hierarchy = [
            'manager' => null,
            'focusedUser' => null,
            'reportsTree' => [],
        ];

        if ($focusedUser->manager_id) {
            $manager = User::query()->with('company:id,name')->whereNull('deleted_at')->find($focusedUser->manager_id);
            if ($manager) {
                $hierarchy['manager'] = [
                    'id' => $manager->id,
                    'name' => $manager->name,
                    'email' => $manager->email,
                    'company_name' => $manager->company->name ?? null,
                ];
            }
        }

        $hierarchy['focusedUser'] = [
            'id' => $focusedUser->id,
            'name' => $focusedUser->name,
            'email' => $focusedUser->email,
            'company_name' => $focusedUser->company->name ?? null,
        ];

        $hierarchy['reportsTree'] = $buildSubtree($focusedUser->id);

        return $hierarchy;
    }

    public function getFullHierarchyProperty(): array
    {
        $q = User::query()->with('company:id,name')->select('id', 'name', 'email', 'manager_id', 'company_id')->whereNull('deleted_at');

        if ($this->companyFilter) {
            $q->where('company_id', $this->companyFilter);
        }

        $allActiveUsers = $q->get();

        $byManager = [];
        foreach ($allActiveUsers as $u) {
            $byManager[$u->manager_id ?? 'ROOT'][] = $u;
        }

        $buildTree = function ($parentId) use (&$buildTree, &$byManager) {
            $nodes = [];
            foreach ($byManager[$parentId] ?? [] as $u) {
                $nodes[] = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'company_name' => $u->company->name ?? null,
                    'children' => $buildTree($u->id),
                ];
            }
            return $nodes;
        };

        return $buildTree('ROOT');
    }

    public function getBreadcrumbProperty(): array
    {
        if (!$this->selectedManagerId) {
            return [];
        }
        return DB::table('user_closure as uc')
            ->join('users as u', 'u.id', '=', 'uc.ancestor_id')
            ->where('uc.descendant_id', $this->selectedManagerId)
            ->whereNull('u.deleted_at')
            ->orderBy('uc.depth')
            ->get(['u.id','u.name','u.email'])
            ->toArray();
    }

    /* -------- Direita: Delegações (Contextual) -------- */
    public function openDelegation(): void
    {
        if (!$this->selectedManagerId) {
            $this->dispatchBrowserEvent('toast', ['type' => 'warning','msg' => 'Selecione um usuário para criar uma delegação.']);
            return;
        }
        $this->dlg_principal_id = $this->selectedManagerId;
        $this->dlg_delegate_id = null;
        $this->dlg_from = now()->toDateString();
        $this->dlg_to = null;
        $this->dlg_reason = 'Férias';
        $this->dispatchBrowserEvent('show-delegation-modal');
    }

    public function saveDelegation(): void
    {
        $this->validate([
            'dlg_principal_id' => 'required|uuid|different:dlg_delegate_id',
            'dlg_delegate_id'  => 'required|uuid|different:dlg_principal_id',
            'dlg_from'         => 'required|date',
            'dlg_to'           => 'nullable|date|after_or_equal:dlg_from',
        ], [
            'dlg_principal_id.required' => 'O titular da delegação é obrigatório.',
            'dlg_principal_id.different' => 'O titular não pode ser o mesmo que o delegado.',
            'dlg_delegate_id.required'  => 'O delegado é obrigatório.',
            'dlg_delegate_id.different' => 'O delegado não pode ser o mesmo que o titular.',
            'dlg_from.required'         => 'A data de início é obrigatória.',
            'dlg_from.date'             => 'A data de início não é válida.',
            'dlg_to.date'               => 'A data de fim não é válida.',
            'dlg_to.after_or_equal'     => 'A data de fim deve ser igual ou posterior à data de início.',
        ]);

        UserDelegation::updateOrCreate(
            [
                'principal_id' => $this->dlg_principal_id,
                'delegate_id'  => $this->dlg_delegate_id,
                'valid_from'   => $this->dlg_from.' 00:00:00',
            ],
            [
                'valid_to' => $this->dlg_to ? $this->dlg_to.' 23:59:59' : null,
                'reason'   => $this->dlg_reason ?: 'Cobertura',
            ]
        );

        $this->dispatchBrowserEvent('hide-delegation-modal');
        $this->dispatchBrowserEvent('toast', ['type' => 'success','msg' => 'Delegação registrada.']);
        $this->emit('$refresh');
    }

    public function getActiveDelegationsProperty()
    {
        if (!$this->selectedManagerId) {
            return collect();
        }

        return UserDelegation::query()
            ->where(function ($q) {
                $q->where('principal_id', $this->selectedManagerId)
                  ->orWhere('delegate_id', $this->selectedManagerId);
            })
            ->where('valid_from', '<=', now())
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->with(['principal:id,name','delegate:id,name'])
            ->orderByDesc('valid_from')
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.hierarchy.board', [
            'directory'   => $this->directory,
            'focusedHierarchy' => $this->focusedHierarchy,
            'fullHierarchy' => $this->fullHierarchy,
            'breadcrumb'  => $this->breadcrumb,
            'moveTargets' => $this->moveTargets,
            'delegations' => $this->activeDelegations,
            'companies'   => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
