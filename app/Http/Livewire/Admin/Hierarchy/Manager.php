<?php

namespace App\Http\Livewire\Admin\Hierarchy;

use App\Models\User;
use App\Models\UserDelegation;
use App\Services\HierarchyService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Manager extends Component
{
    public $search = '';
    public $selectedUserId = null;

    // Delegação (modal simples)
    public $dlg_principal_id = null;
    public $dlg_delegate_id = null;
    public $dlg_from = null; // 'Y-m-d'
    public $dlg_to   = null; // 'Y-m-d'
    public $dlg_reason = '';

    protected $listeners = [
        'lwMoveUserUnder' => 'moveUserUnder',  // drag solto em outro usuário
        'lwSetAsRoot'     => 'setAsRoot',      // tornar raiz
        'lwSelectUser'    => 'selectUser',     // seleção
    ];

    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
    }

    /** mover $dragUserId para ser subordinado de $targetManagerId */
    public function moveUserUnder(string $dragUserId, string $targetManagerId)
    {
        if ($dragUserId === $targetManagerId) {
            return;
        }

        /** @var HierarchyService $svc */
        $svc = app(HierarchyService::class);
        try {
            $svc->moveSubtree($dragUserId, $targetManagerId);
            $this->dispatchBrowserEvent('toast', ['type' => 'success','msg' => 'Hierarquia atualizada.']);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('toast', ['type' => 'danger','msg' => $e->getMessage()]);
        }
    }

    /** tornar raiz (sem chefe) */
    public function setAsRoot(string $userId)
    {
        /** @var HierarchyService $svc */
        $svc = app(HierarchyService::class);
        try {
            $svc->moveSubtree($userId, null);
            $this->dispatchBrowserEvent('toast', ['type' => 'success','msg' => 'Usuário definido como raiz.']);
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('toast', ['type' => 'danger','msg' => $e->getMessage()]);
        }
    }

    /** abrir delegação (cobertura) */
    public function openDelegation()
    {
        $this->validate([
            'dlg_principal_id' => 'required|uuid|different:dlg_delegate_id',
            'dlg_delegate_id'  => 'required|uuid|different:dlg_principal_id',
            'dlg_from'         => 'required|date',
            'dlg_to'           => 'nullable|date|after_or_equal:dlg_from',
        ]);

        $from = $this->dlg_from . ' 00:00:00';
        $to   = $this->dlg_to ? ($this->dlg_to.' 23:59:59') : null;

        UserDelegation::updateOrCreate(
            [
                'principal_id' => $this->dlg_principal_id,
                'delegate_id'  => $this->dlg_delegate_id,
                'valid_from'   => $from,
            ],
            [
                'valid_to' => $to,
                'reason'   => $this->dlg_reason ?: 'Cobertura',
            ]
        );

        $this->reset(['dlg_principal_id','dlg_delegate_id','dlg_from','dlg_to','dlg_reason']);
        $this->dispatchBrowserEvent('hide-delegation-modal');
        $this->dispatchBrowserEvent('toast', ['type' => 'success','msg' => 'Delegação registrada.']);
    }

    /** encerrar uma delegação específica */
    public function endDelegation(string $delegationId)
    {
        /** @var UserDelegation $d */
        $d = UserDelegation::find($delegationId);
        if ($d && is_null($d->valid_to)) {
            $d->valid_to = now();
            $d->save();
            $this->dispatchBrowserEvent('toast', ['type' => 'success','msg' => 'Delegação encerrada.']);
        }
    }

    /** lista de usuários (busca) */
    public function getUsersProperty()
    {
        $q = User::query()->orderBy('name');
        $s = trim($this->search);
        if ($s !== '') {
            $terms = preg_split('/[\s,;\n\r]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
            $q->where(function ($w) use ($terms) {
                foreach ($terms as $t) {
                    $w->orWhere('name', 'like', "%{$t}%")
                      ->orWhere('email', 'like', "%{$t}%");
                }
            });
        }
        return $q->get(['id','name','email','manager_id']);
    }

    /** constrói tree (raizes e filhos) */
    public function getTreeProperty(): array
    {
        // pega todos e indexa por manager_id
        $users = $this->users->map(fn ($u) => (object)[
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'manager_id' => $u->manager_id
        ]);

        $byManager = [];
        foreach ($users as $u) {
            $byManager[$u->manager_id ?? 'ROOT'][] = $u;
        }

        $build = function ($parentId) use (&$build, &$byManager) {
            $children = $byManager[$parentId] ?? [];
            $nodes = [];
            foreach ($children as $c) {
                $nodes[] = [
                    'id'    => $c->id,
                    'name'  => $c->name,
                    'email' => $c->email,
                    'children' => $build($c->id),
                ];
            }
            return $nodes;
        };

        return $build('ROOT'); // várias raízes suportadas
    }

    public function render()
    {
        // delegações ativas (agora) – útil no painel lateral
        $delegations = UserDelegation::query()
            ->where('valid_from', '<=', now())
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->with(['principal:id,name,email','delegate:id,name,email'])
            ->orderByDesc('valid_from')
            ->limit(100)
            ->get();

        return view('livewire.admin.hierarchy.manager', [
            'tree'        => $this->tree,
            'users'       => $this->users,
            'delegations' => $delegations,
            'selected'    => $this->selectedUserId
                ? User::select('id', 'name', 'email', 'manager_id')->find($this->selectedUserId)
                : null,
        ]);
    }
}
