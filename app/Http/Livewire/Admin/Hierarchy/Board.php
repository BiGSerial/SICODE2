<?php

namespace App\Http\Livewire\Admin\Hierarchy;

use App\Models\Company;
use App\Models\User;
use App\Models\UserDelegation;
use App\Models\UserObservation;
use App\Services\HierarchyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
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

    public ?string $delegationId = null;

    // Modal observação
    public ?string $obs_observer_id = null;
    public ?string $obs_target_id = null;
    public string $obs_mode = 'subtree';
    public ?string $obs_from = null;
    public ?string $obs_to = null;
    public string $obs_reason = '';
    public string $obsTargetSearch = '';

    public ?string $observationId = null;

    protected $queryString = [
        'leftSearch'        => ['except' => ''],
        'treeSearch'        => ['except' => ''],
        'selectedManagerId' => ['except' => ''],
        'companyFilter'     => ['except' => ''],
    ];

    protected $listeners = [
        '000_finalizeDelegation' => 'finalizeDelegation',
        '000_removeDelegation'   => 'deleteDelegation',
        '000_finalizeObservation' => 'finalizeObservation',
        '000_removeObservation'   => 'deleteObservation',
    ];

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['leftSearch', 'companyFilter'], true)) {
            $this->resetPage('dir');
        }
    }

    /* -------- Esquerda: Lista de Usuários -------- */
    public function getDirectoryProperty()
    {
        $q = User::query()
            ->select('id', 'name', 'email', 'manager_id', 'company_id')
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

    public function setAsRootSelected(): void
    {
        if (!$this->selectedManagerId) {
            $this->dispatchBrowserEvent('toast', ['type' => 'warning', 'msg' => 'Nenhum usuário focado para tornar raiz.']);
            return;
        }

        try {
            app(\App\Services\HierarchyService::class)->moveSubtree($this->selectedManagerId, null);

            // feedback + refresh
            $this->dispatchBrowserEvent('toast', ['type' => 'success', 'msg' => 'Usuário definido como raiz.']);
            $this->emit('$refresh');

            // mantém o foco no mesmo usuário (agora raiz); nada a mudar em $selectedManagerId

        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('toast', ['type' => 'error', 'msg' => 'Erro: ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('toast', ['type' => 'error', 'msg' => 'Erro ao tornar raiz: ' . $e->getMessage()]);
        }
    }


    public function openMoveModal(string $userId): void
    {
        $this->moveUserId = $userId;
        $this->moveTargetSearch = '';
        $this->moveTargetId = null;

        $user = User::find($userId);
        $this->dispatchBrowserEvent('show-move-modal', [
            'userName' => $user?->name ?? 'Usuário'
        ]);
    }

    public function confirmMove(): void
    {
        // Garantias básicas de entrada
        if (!$this->moveUserId) {
            $this->dispatchBrowserEvent('toast', ['type' => 'warning','msg' => 'Usuário a mover não definido. Abra o modal novamente.']);
            return;
        }

        if (!$this->moveTargetId) {
            $this->dispatchBrowserEvent('toast', ['type' => 'warning','msg' => 'Selecione o novo gerente antes de mover.']);
            return;
        }

        if ($this->moveUserId === $this->moveTargetId) {
            $this->dispatchBrowserEvent('toast', ['type' => 'warning','msg' => 'Não é possível mover para si mesmo.']);
            return;
        }

        try {
            app(\App\Services\HierarchyService::class)->moveSubtree($this->moveUserId, $this->moveTargetId);

            // Fecha modal + feedback
            $this->dispatchBrowserEvent('hide-move-modal');
            $this->dispatchBrowserEvent('toast', ['type' => 'success','msg' => 'Movido com sucesso.']);
            $this->emit('$refresh');

            // Se o usuário movido era o focado, atualiza o foco para o novo gerente
            if ($this->moveUserId === $this->selectedManagerId) {
                $this->selectedManagerId = $this->moveTargetId;
            }

            // Limpa estado do modal
            $this->moveUserId = null;
            $this->moveTargetId = null;
            $this->moveTargetSearch = '';

        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('toast', ['type' => 'error','msg' => 'Erro: ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('toast', ['type' => 'error','msg' => 'Erro ao mover: ' . $e->getMessage()]);
        }
    }


    public function getMoveTargetsProperty()
    {
        $q = User::query()
            ->select('id', 'name', 'email')
            ->whereNull('deleted_at');

        if ($this->moveUserId) {
            $q->where('id', '!=', $this->moveUserId);

            // Evita mover para descendente do usuário que está sendo movido
            $descendants = DB::table('user_closure')
                ->where('ancestor_id', $this->moveUserId)
                ->pluck('descendant_id');

            if ($descendants->isNotEmpty()) {
                $q->whereNotIn('id', $descendants);
            }
        }

        if ($s = trim($this->moveTargetSearch)) {
            $terms = preg_split('/[\s,;\n\r]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
            $q->where(function ($w) use ($terms) {
                foreach ($terms as $t) {
                    $w->orWhere('name', 'like', "%{$t}%")
                      ->orWhere('email', 'like', "%{$t}%");
                }
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

        $allActiveUsers = User::query()
            ->with('company:id,name')
            ->select('id', 'name', 'email', 'manager_id', 'company_id')
            ->whereNull('deleted_at')
            ->get();

        // índice de delegações ativas por titular
        $delegIdx = $this->activeDelegationsIndex;
        $observationCounts = $this->activeObservationCounts;

        $byManager = [];
        foreach ($allActiveUsers as $u) {
            $byManager[$u->manager_id ?? 'ROOT'][] = $u;
        }

        $buildSubtree = function ($parentId) use (&$buildSubtree, &$byManager, $delegIdx, $allActiveUsers, $observationCounts) {
            $nodes = [];
            foreach ($byManager[$parentId] ?? [] as $u) {
                // overlay de delegação (se o "u" estiver delegando sua função)
                $deleg = $delegIdx[(string)$u->id] ?? null;
                $delegateUser = $deleg ? $allActiveUsers->firstWhere('id', $deleg['delegate_id']) : null;

                $displayName  = $delegateUser->name  ?? $u->name;
                $displayEmail = $delegateUser->email ?? $u->email;

                $nodes[] = [
                    'id'           => $u->id,             // mantém a identidade do nó como o TITULAR
                    'name'         => $displayName,       // mostra o delegado ocupando a função
                    'email'        => $displayEmail,
                    'company_name' => $u->company->name ?? null,
                    'observing_count' => (int) ($observationCounts[(string) $u->id] ?? 0),
                    'delegation'   => ($deleg && $delegateUser) ? [
                        'principal' => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email],
                        'delegate'  => ['id' => $delegateUser->id, 'name' => $delegateUser->name, 'email' => $delegateUser->email],
                        'reason'    => $deleg['reason'],
                        'valid_to'  => $deleg['valid_to'],
                    ] : null,
                    'children'     => $buildSubtree($u->id),
                ];
            }
            return $nodes;
        };

        $hierarchy = [
            'manager'      => null,
            'focusedUser'  => null,
            'reportsTree'  => [],
        ];

        if ($focusedUser->manager_id) {
            $manager = User::query()->with('company:id,name')->whereNull('deleted_at')->find($focusedUser->manager_id);
            if ($manager) {
                // overlay no gerente também (se ele estiver delegando)
                $delegM = $delegIdx[(string)$manager->id] ?? null;
                $delegateM = $delegM ? $allActiveUsers->firstWhere('id', $delegM['delegate_id']) : null;

                $hierarchy['manager'] = [
                    'id'           => $manager->id,
                    'name'         => $delegateM->name  ?? $manager->name,
                    'email'        => $delegateM->email ?? $manager->email,
                    'company_name' => $manager->company->name ?? null,
                    'observing_count' => (int) ($observationCounts[(string) $manager->id] ?? 0),
                    'delegation'   => ($delegM && $delegateM) ? [
                        'principal' => ['id' => $manager->id, 'name' => $manager->name, 'email' => $manager->email],
                        'delegate'  => ['id' => $delegateM->id, 'name' => $delegateM->name, 'email' => $delegateM->email],
                        'reason'    => $delegM['reason'],
                        'valid_to'  => $delegM['valid_to'],
                    ] : null,
                ];
            }
        }

        // overlay no focado (se ele estiver delegando sua função)
        $delegF = $delegIdx[(string)$focusedUser->id] ?? null;
        $delegateF = $delegF ? $allActiveUsers->firstWhere('id', $delegF['delegate_id']) : null;

        $hierarchy['focusedUser'] = [
            'id'           => $focusedUser->id,
            'name'         => $delegateF->name  ?? $focusedUser->name,
            'email'        => $delegateF->email ?? $focusedUser->email,
            'company_name' => $focusedUser->company->name ?? null,
            'observing_count' => (int) ($observationCounts[(string) $focusedUser->id] ?? 0),
            'delegation'   => ($delegF && $delegateF) ? [
                'principal' => ['id' => $focusedUser->id, 'name' => $focusedUser->name, 'email' => $focusedUser->email],
                'delegate'  => ['id' => $delegateF->id, 'name' => $delegateF->name, 'email' => $delegateF->email],
                'reason'    => $delegF['reason'],
                'valid_to'  => $delegF['valid_to'],
            ] : null,
        ];

        $hierarchy['reportsTree'] = $buildSubtree($focusedUser->id);

        return $hierarchy;
    }


    public function getFullHierarchyProperty(): array
    {
        $q = User::query()
            ->with('company:id,name')
            ->select('id', 'name', 'email', 'manager_id', 'company_id')
            ->whereNull('deleted_at');

        if ($this->companyFilter) {
            $q->where('company_id', $this->companyFilter);
        }

        $allActiveUsers = $q->get();
        $delegIdx = $this->activeDelegationsIndex;
        $observationCounts = $this->activeObservationCounts;

        $byManager = [];
        foreach ($allActiveUsers as $u) {
            $byManager[$u->manager_id ?? 'ROOT'][] = $u;
        }

        $buildTree = function ($parentId) use (&$buildTree, &$byManager, $delegIdx, $allActiveUsers, $observationCounts) {
            $nodes = [];
            foreach ($byManager[$parentId] ?? [] as $u) {
                $deleg = $delegIdx[(string)$u->id] ?? null;
                $delegateUser = $deleg ? $allActiveUsers->firstWhere('id', $deleg['delegate_id']) : null;

                $displayName  = $delegateUser->name  ?? $u->name;
                $displayEmail = $delegateUser->email ?? $u->email;

                $nodes[] = [
                    'id'           => $u->id,
                    'name'         => $displayName,
                    'email'        => $displayEmail,
                    'company_name' => $u->company->name ?? null,
                    'observing_count' => (int) ($observationCounts[(string) $u->id] ?? 0),
                    'delegation'   => ($deleg && $delegateUser) ? [
                        'principal' => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email],
                        'delegate'  => ['id' => $delegateUser->id, 'name' => $delegateUser->name, 'email' => $delegateUser->email],
                        'reason'    => $deleg['reason'],
                        'valid_to'  => $deleg['valid_to'],
                    ] : null,
                    'children'     => $buildTree($u->id),
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
            $this->dispatchBrowserEvent('toast', [
                'type' => 'warning',
                'msg'  => 'Selecione um usuário para criar uma delegação.'
            ]);
            return;
        }

        $this->dlg_principal_id = $this->selectedManagerId;
        $this->dlg_delegate_id  = null;
        $this->dlg_from         = now()->toDateString();
        $this->dlg_to           = null;
        $this->dlg_reason       = 'Férias';

        // compatível com qualquer setup
        $this->dispatchBrowserEvent('show-delegation-modal');
        // $this->dispatch('show-delegation-modal'); // Livewire v3
    }

    public function saveDelegation(): void
    {
        // limpa mensagens antigas, se houver
        $this->resetValidation();

        $this->validate([
            'dlg_principal_id' => ['required', 'exists:users,id', 'different:dlg_delegate_id'],
            'dlg_delegate_id'  => ['required', 'exists:users,id', 'different:dlg_principal_id'],
            'dlg_from'         => ['required', 'date'],
            'dlg_to'           => ['nullable', 'date', 'after_or_equal:dlg_from'],
        ], [
            'dlg_principal_id.required' => 'O titular da delegação é obrigatório.',
            'dlg_principal_id.different' => 'O titular não pode ser o mesmo que o delegado.',
            'dlg_principal_id.exists'   => 'Titular inválido.',
            'dlg_delegate_id.required'  => 'O delegado é obrigatório.',
            'dlg_delegate_id.different' => 'O delegado não pode ser o mesmo que o titular.',
            'dlg_delegate_id.exists'    => 'Delegado inválido.',
            'dlg_from.required'         => 'A data de início é obrigatória.',
            'dlg_from.date'             => 'A data de início não é válida.',
            'dlg_to.date'               => 'A data de fim não é válida.',
            'dlg_to.after_or_equal'     => 'A data de fim deve ser igual ou posterior à data de início.',
        ]);

        // grava/atualiza
        \App\Models\UserDelegation::updateOrCreate(
            [
                'principal_id' => (string) $this->dlg_principal_id,
                'delegate_id'  => (string) $this->dlg_delegate_id,
                'valid_from'   => $this->dlg_from . ' 00:00:00',
            ],
            [
                'valid_to' => $this->dlg_to ? ($this->dlg_to . ' 23:59:59') : null,
                'reason'   => $this->dlg_reason ?: 'Cobertura',
            ]
        );

        $this->dispatchBrowserEvent('hide-delegation-modal');
        $this->dispatchBrowserEvent('toast', ['type' => 'success', 'msg' => 'Delegação registrada.']);
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

    public function getActiveDelegationsIndexProperty(): array
    {
        // Indexa delegações ativas por principal_id
        $rows = UserDelegation::query()
            ->where('valid_from', '<=', now())
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->get(['principal_id', 'delegate_id', 'reason', 'valid_from', 'valid_to']);

        $map = [];
        foreach ($rows as $r) {
            $map[(string)$r->principal_id] = [
                'delegate_id' => (string)$r->delegate_id,
                'reason'      => $r->reason,
                'valid_from'  => $r->valid_from,
                'valid_to'    => $r->valid_to,
            ];
        }
        return $map;
    }

    public function getActiveObservationCountsProperty(): array
    {
        return UserObservation::query()
            ->active()
            ->select('observer_id', DB::raw('COUNT(*) as total'))
            ->groupBy('observer_id')
            ->pluck('total', 'observer_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    public function getActiveObservationsProperty(): Collection
    {
        if (!$this->selectedManagerId) {
            return collect();
        }

        return UserObservation::query()
            ->where('observer_id', $this->selectedManagerId)
            ->active()
            ->with('target:id,name,email')
            ->orderByDesc('valid_from')
            ->get();
    }

    public function openObservation(): void
    {
        if (!$this->selectedManagerId) {
            $this->dispatchBrowserEvent('toast', [
                'type' => 'warning',
                'msg' => 'Selecione um usuário para criar uma observação.',
            ]);
            return;
        }

        $this->obs_observer_id = $this->selectedManagerId;
        $this->obs_target_id = null;
        $this->obs_mode = 'subtree';
        $this->obs_from = now()->toDateString();
        $this->obs_to = null;
        $this->obs_reason = '';
        $this->obsTargetSearch = '';

        $this->dispatchBrowserEvent('show-observation-modal');
    }

    public function saveObservation(): void
    {
        $this->resetValidation();

        $this->validate([
            'obs_observer_id' => ['required', 'exists:users,id', 'different:obs_target_id'],
            'obs_target_id' => ['required', 'exists:users,id', 'different:obs_observer_id'],
            'obs_mode' => ['required', 'in:subtree,node_only'],
            'obs_from' => ['required', 'date'],
            'obs_to' => ['nullable', 'date', 'after_or_equal:obs_from'],
        ], [
            'obs_observer_id.required' => 'O usuário observador é obrigatório.',
            'obs_observer_id.different' => 'O observador não pode observar a si mesmo.',
            'obs_target_id.required' => 'O usuário alvo é obrigatório.',
            'obs_target_id.different' => 'O alvo não pode ser o próprio observador.',
            'obs_mode.required' => 'O modo de observação é obrigatório.',
            'obs_mode.in' => 'Modo de observação inválido.',
            'obs_from.required' => 'A data de início é obrigatória.',
            'obs_to.after_or_equal' => 'A data de fim deve ser igual ou posterior à data de início.',
        ]);

        UserObservation::updateOrCreate(
            [
                'observer_id' => (string) $this->obs_observer_id,
                'target_id' => (string) $this->obs_target_id,
                'mode' => $this->obs_mode,
                'valid_from' => $this->obs_from . ' 00:00:00',
            ],
            [
                'valid_to' => $this->obs_to ? ($this->obs_to . ' 23:59:59') : null,
                'reason' => $this->obs_reason ?: 'Observação de equipe',
            ]
        );

        $this->dispatchBrowserEvent('hide-observation-modal');
        $this->dispatchBrowserEvent('toast', ['type' => 'success', 'msg' => 'Observação registrada.']);
        $this->emit('$refresh');
    }

    public function getObservationTargetsProperty()
    {
        $q = User::query()
            ->select('id', 'name', 'email')
            ->whereNull('deleted_at');

        if ($this->obs_observer_id) {
            $q->where('id', '!=', $this->obs_observer_id);
        }

        if ($s = trim($this->obsTargetSearch)) {
            $terms = preg_split('/[\s,;\n\r]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
            $q->where(function ($w) use ($terms) {
                foreach ($terms as $t) {
                    $w->orWhere('name', 'like', "%{$t}%")
                        ->orWhere('email', 'like', "%{$t}%");
                }
            });
        }

        return $q->orderBy('name')->limit(30)->get();
    }

    public function toFinalizeObservation(string $observationId): void
    {
        $this->observationId = $observationId;

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Finalizar Observação',
            'msg' => 'Você deseja finalizar esta observação agora?',
            'icon' => 'warning',
            'btnOktxt' => 'Sim, Finalizar!',
            'btnCanceltxt' => 'Não, Cancele',
            'action' => '000_finalizeObservation',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg' => 'Nenhuma observação foi finalizada.',
        ]);
    }

    public function finalizeObservation(): void
    {
        if (!$this->observationId) {
            return;
        }

        try {
            $obs = UserObservation::findOrFail($this->observationId);

            if ($obs->valid_to && $obs->valid_to->lt(now())) {
                $this->dispatchBrowserEvent('toast', [
                    'type' => 'info',
                    'msg' => 'Esta observação já está finalizada.',
                ]);
                return;
            }

            $obs->valid_to = now()->subSecond();
            $obs->save();

            $this->dispatchBrowserEvent('toast', [
                'type' => 'success',
                'msg' => 'Observação finalizada.',
            ]);
            $this->emit('$refresh');
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('toast', [
                'type' => 'error',
                'msg' => 'Não foi possível finalizar a observação: ' . $e->getMessage(),
            ]);
        }

        $this->observationId = null;
    }

    public function toDeleteObservation(string $observationId): void
    {
        $this->observationId = $observationId;

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'Remover Observação',
            'msg' => 'Você deseja remover esta observação definitivamente?',
            'icon' => 'warning',
            'btnOktxt' => 'Sim, Remover!',
            'btnCanceltxt' => 'Não, Cancele',
            'action' => '000_removeObservation',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg' => 'Nenhuma observação foi removida.',
        ]);
    }

    public function deleteObservation(): void
    {
        if (!$this->observationId) {
            return;
        }

        try {
            $obs = UserObservation::findOrFail($this->observationId);
            $obs->delete();

            $this->dispatchBrowserEvent('toast', [
                'type' => 'success',
                'msg' => 'Observação removida.',
            ]);
            $this->emit('$refresh');
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('toast', [
                'type' => 'error',
                'msg' => 'Não foi possível remover a observação: ' . $e->getMessage(),
            ]);
        }

        $this->observationId = null;
    }

    public function toFinalizeDelegation(string $delegationId): void
    {
        $this->delegationId = $delegationId;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Finalizar Delegação',
            'msg'           => "Você deseja finalizar a Delegação do Usuário?</strong>?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Finalize!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => '000_finalizeDelegation',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma delegação foi finalizada.',
        ]);
    }

    public function finalizeDelegation(): void
    {
        if (!$this->delegationId) {
            return;
        }

        try {
            $d = \App\Models\UserDelegation::findOrFail($this->delegationId);

            // Se já possui fim e já está inativa, apenas avisa
            if ($d->valid_to && $d->valid_to->lt(now())) {
                $this->dispatchBrowserEvent('toast', [
                    'type' => 'info',
                    'msg'  => 'Esta delegação já está finalizada.'
                ]);
                return;
            }

            // Define o fim para "agora - 1s" para não permanecer ativa (já que o filtro usa >= now())
            $d->valid_to = now()->subSecond();
            $d->save();

            $this->dispatchBrowserEvent('toast', [
                'type' => 'success',
                'msg'  => 'Delegação finalizada.'
            ]);
            $this->emit('$refresh');
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('toast', [
                'type' => 'error',
                'msg'  => 'Não foi possível finalizar a delegação: ' . $e->getMessage()
            ]);
        }

        $this->delegationId = null;
    }

    public function toDeleteDelegation(string $delegationId): void
    {
        $this->delegationId = $delegationId;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Remover Delegação',
            'msg'           => "Você deseja remover a Delegação do Usuário?</strong>?",
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Remova!',
            'btnCanceltxt'  => 'Não, Cancele',
            'action'        => '000_removeDelegation',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma delegação foi finalizada.',
        ]);
    }

    public function deleteDelegation(): void
    {
        if (!$this->delegationId) {
            return;
        }

        try {
            $d = \App\Models\UserDelegation::findOrFail($this->delegationId);
            $d->delete();

            $this->dispatchBrowserEvent('toast', [
                'type' => 'success',
                'msg'  => 'Delegação removida.'
            ]);
            $this->emit('$refresh');
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('toast', [
                'type' => 'error',
                'msg'  => 'Não foi possível remover a delegação: ' . $e->getMessage()
            ]);
        }

        $this->delegationId = null;
    }

    public function render()
    {
        return view('livewire.admin.hierarchy.board', [
            'directory'         => $this->directory,
            'focusedHierarchy'  => $this->focusedHierarchy,
            'fullHierarchy'     => $this->fullHierarchy,
            'breadcrumb'        => $this->breadcrumb,
            'moveTargets'       => $this->moveTargets,
            'observationTargets'=> $this->observationTargets,
            'delegations'       => $this->activeDelegations,
            'observations'      => $this->activeObservations,
            'companies'         => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
