<?php

namespace App\Http\Livewire\Admin\Hierarchy;

use App\Models\{User, UserDelegation, UserObservation};
use App\Services\Hierarchy\HierarchyTreeService;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Organograma de usuários (Administração → Hierarquia). A árvore é montada no navegador (resources/js/hierarchy/org.js)
 * a partir de `admin.user.hierarchy.nodes`; este componente só guarda a seleção, o painel de detalhes e as gravações.
 * Docs: docs/admin/HIERARQUIA.md.
 */
class Org extends Component
{
    public ?string $selectedId = null;

    public string $pickSearch = '';

    /** Para que serve a busca dos modais: move | delegate | observe (muda quem fica fora da lista). */
    public string $pickMode = 'move';

    public string $tab = 'tree';

    // Mover (arrastar ou "Mover para…")
    public array $moveIds = [];

    public ?string $moveTargetId = null;

    public array $moveImpact = [];

    // Delegação ("quem cobre quem")
    public ?string $dlg_delegate_id = null;

    public ?string $dlg_from = null;

    public ?string $dlg_to = null;

    public string $dlg_reason = 'Férias';

    // Observação
    public ?string $obs_target_id = null;

    public string $obs_mode = 'subtree';

    public ?string $obs_from = null;

    public ?string $obs_to = null;

    public string $obs_reason = '';

    // Confirmação de finalizar/remover: ['kind' => 'delegation|observation', 'action' => 'finish|delete', 'id' => ...]
    public array $confirm = [];

    public ?string $flash = null;

    public string $flashType = 'success';

    protected $queryString = [
        'selectedId' => ['except' => '', 'as' => 'user'],
        'tab'        => ['except' => 'tree'],
    ];

    public function select(?string $userId): void
    {
        $this->selectedId = $userId ?: null;
        $this->flash      = null;
        $this->dispatchBrowserEvent('hier-selected', ['id' => $this->selectedId]);
    }

    /* ---------- Mover ---------- */

    /** Chamado pelo arrastar-e-soltar e pelo "Mover para…": só calcula o impacto; quem grava é confirmMove(). */
    public function prepareMove(array $userIds, ?string $targetId): void
    {
        $userIds = array_values(array_filter(array_map('strval', $userIds)));

        if (!$userIds) {
            return;
        }

        $service = app(HierarchyTreeService::class);
        $impacts = array_map(fn ($id) => $service->impact($id, $targetId), $userIds);
        $valid   = array_values(array_filter($impacts, fn ($i) => $i['ok']));

        if (!$valid) {
            $this->notify($impacts[0]['error'] ?? 'Não é possível mover.', 'danger');

            return;
        }

        $this->moveIds      = array_values(array_filter($userIds, fn ($id, $k) => $impacts[$k]['ok'], ARRAY_FILTER_USE_BOTH));
        $this->moveTargetId = $targetId;
        $this->moveImpact   = [
            'target'  => $valid[0]['target'],
            'items'   => $valid,
            'skipped' => count($impacts) - count($valid),
            'subtree' => array_sum(array_column($valid, 'subtree')),
        ];
        $this->pickSearch = '';
        $this->dispatchBrowserEvent('hier-modal', ['id' => 'hierMoveModal', 'show' => true]);
    }

    public function confirmMove(): void
    {
        $result = app(HierarchyTreeService::class)->move($this->moveIds, $this->moveTargetId);

        $this->dispatchBrowserEvent('hier-modal', ['id' => 'hierMoveModal', 'show' => false]);
        $this->dispatchBrowserEvent('hier-reload');

        if ($result['errors']) {
            $this->notify(implode(' ', $result['errors']), $result['moved'] ? 'warning' : 'danger');
        } else {
            $this->notify($result['moved'] === 1 ? 'Usuário realinhado.' : $result['moved'] . ' usuários realinhados.');
        }

        $this->moveIds      = $this->moveImpact = [];
        $this->moveTargetId = null;
    }

    public function openMovePicker(): void
    {
        $this->pickMode   = 'move';
        $this->pickSearch = '';
        $this->dispatchBrowserEvent('hier-modal', ['id' => 'hierPickModal', 'show' => true]);
    }

    public function pickManager(string $targetId): void
    {
        $this->dispatchBrowserEvent('hier-modal', ['id' => 'hierPickModal', 'show' => false]);

        if ($this->selectedId) {
            $this->prepareMove([$this->selectedId], $targetId);
        }
    }

    public function makeRoot(): void
    {
        if ($this->selectedId) {
            $this->prepareMove([$this->selectedId], null);
        }
    }

    /* ---------- Delegação ---------- */

    public function openDelegation(): void
    {
        if (!$this->selectedId) {
            return;
        }

        $this->resetValidation();
        $this->dlg_delegate_id = null;
        $this->dlg_from        = now()->toDateString();
        $this->dlg_to          = null;
        $this->dlg_reason      = 'Férias';
        $this->pickMode        = 'delegate';
        $this->pickSearch      = '';
        $this->dispatchBrowserEvent('hier-modal', ['id' => 'hierDelegationModal', 'show' => true]);
    }

    public function chooseDelegate(string $userId): void
    {
        $this->dlg_delegate_id = $userId;
        $this->pickSearch      = '';
    }

    public function saveDelegation(): void
    {
        $this->validate([
            'dlg_delegate_id' => ['required', 'exists:users,id', 'different:selectedId'],
            'dlg_from'        => ['required', 'date'],
            'dlg_to'          => ['nullable', 'date', 'after_or_equal:dlg_from'],
        ], [
            'dlg_delegate_id.required'  => 'Escolha quem vai cobrir.',
            'dlg_delegate_id.different' => 'Quem cobre não pode ser o próprio titular.',
            'dlg_from.required'         => 'Informe o início.',
            'dlg_to.after_or_equal'     => 'O fim deve ser igual ou posterior ao início.',
        ]);

        UserDelegation::updateOrCreate(
            ['principal_id' => (string) $this->selectedId, 'delegate_id' => (string) $this->dlg_delegate_id, 'valid_from' => $this->dlg_from . ' 00:00:00'],
            ['valid_to' => $this->dlg_to ? $this->dlg_to . ' 23:59:59' : null, 'reason' => $this->dlg_reason ?: 'Cobertura'],
        );

        $this->dispatchBrowserEvent('hier-modal', ['id' => 'hierDelegationModal', 'show' => false]);
        $this->dispatchBrowserEvent('hier-reload');
        $this->notify('Cobertura registrada.');
    }

    /* ---------- Observação ---------- */

    public function openObservation(): void
    {
        if (!$this->selectedId) {
            return;
        }

        $this->resetValidation();
        $this->obs_target_id = null;
        $this->obs_mode      = 'subtree';
        $this->obs_from      = now()->toDateString();
        $this->obs_to        = null;
        $this->obs_reason    = '';
        $this->pickMode      = 'observe';
        $this->pickSearch    = '';
        $this->dispatchBrowserEvent('hier-modal', ['id' => 'hierObservationModal', 'show' => true]);
    }

    public function chooseTarget(string $userId): void
    {
        $this->obs_target_id = $userId;
        $this->pickSearch    = '';
    }

    public function saveObservation(): void
    {
        $this->validate([
            'obs_target_id' => ['required', 'exists:users,id', 'different:selectedId'],
            'obs_mode'      => ['required', 'in:subtree,node_only'],
            'obs_from'      => ['required', 'date'],
            'obs_to'        => ['nullable', 'date', 'after_or_equal:obs_from'],
        ], [
            'obs_target_id.required'  => 'Escolha quem será observado.',
            'obs_target_id.different' => 'Ninguém observa a si mesmo.',
            'obs_from.required'       => 'Informe o início.',
            'obs_to.after_or_equal'   => 'O fim deve ser igual ou posterior ao início.',
        ]);

        UserObservation::updateOrCreate(
            ['observer_id' => (string) $this->selectedId, 'target_id' => (string) $this->obs_target_id, 'mode' => $this->obs_mode, 'valid_from' => $this->obs_from . ' 00:00:00'],
            ['valid_to' => $this->obs_to ? $this->obs_to . ' 23:59:59' : null, 'reason' => $this->obs_reason ?: 'Observação de equipe'],
        );

        $this->dispatchBrowserEvent('hier-modal', ['id' => 'hierObservationModal', 'show' => false]);
        $this->dispatchBrowserEvent('hier-reload');
        $this->notify('Observação registrada.');
    }

    /* ---------- Finalizar / remover ---------- */

    public function askConfirm(string $kind, string $action, string $id): void
    {
        $this->confirm = compact('kind', 'action', 'id');
        $this->dispatchBrowserEvent('hier-modal', ['id' => 'hierConfirmModal', 'show' => true]);
    }

    public function runConfirm(): void
    {
        $kind   = $this->confirm['kind'] ?? null;
        $action = $this->confirm['action'] ?? null;
        $model  = match ($kind) {
            'delegation'  => UserDelegation::find($this->confirm['id'] ?? null),
            'observation' => UserObservation::find($this->confirm['id'] ?? null),
            default       => null,
        };

        $this->dispatchBrowserEvent('hier-modal', ['id' => 'hierConfirmModal', 'show' => false]);
        $this->confirm = [];

        if (!$model) {
            $this->notify('Registro não encontrado.', 'warning');

            return;
        }

        if ($action === 'delete') {
            $model->delete();
        } else {
            $model->update(['valid_to' => now()->subSecond()]);
        }

        $this->dispatchBrowserEvent('hier-reload');
        $this->notify($action === 'delete' ? 'Registro removido.' : 'Registro finalizado.');
    }

    /* ---------- Dados da tela ---------- */

    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'coverage' ? 'coverage' : 'tree';
    }

    public function getCoverageProperty(): array
    {
        return $this->tab === 'coverage' ? app(HierarchyTreeService::class)->coverageOverview() : [];
    }

    public function getDetailProperty(): ?array
    {
        return $this->selectedId ? app(HierarchyTreeService::class)->detail($this->selectedId) : null;
    }

    /** Resultados da busca dentro dos modais (escolher gestor, quem cobre, quem observa). */
    public function getPickResultsProperty(): Collection
    {
        $term = trim($this->pickSearch);

        if (mb_strlen($term) < 2) {
            return collect();
        }

        $exclude = array_filter([$this->selectedId]);

        if ($this->pickMode === 'move' && $this->selectedId) {
            // "Mover para…": não oferecer a própria equipe como novo gestor.
            $exclude = array_merge($exclude, \Illuminate\Support\Facades\DB::table('user_closure')->where('ancestor_id', $this->selectedId)->pluck('descendant_id')->all());
        }

        return User::query()
            ->whereNotIn('id', $exclude)
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'email']);
    }

    public function getPickedProperty(): array
    {
        $ids = array_filter([$this->dlg_delegate_id, $this->obs_target_id]);

        return $ids ? User::query()->whereIn('id', $ids)->pluck('name', 'id')->all() : [];
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->flash     = $message;
        $this->flashType = $type;
    }

    public function render()
    {
        return view('livewire.admin.hierarchy.org', [
            'detail'      => $this->detail,
            'coverage'    => $this->coverage,
            'pickResults' => $this->pickResults,
            'picked'      => $this->picked,
        ]);
    }
}
