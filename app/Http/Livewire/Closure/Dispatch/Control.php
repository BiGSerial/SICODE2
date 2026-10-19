<?php

namespace App\Http\Livewire\Closure\Dispatch;

use App\Http\Livewire\Closure\Work\Index as WorkIndex;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Controle de Encerramento" (dispatch.stack): o que já foi despachado, de todos os usuários e
 * empresas. Reaproveita a lista/ações do Work\Index em modo gestor (atribuir, transferir, prioridade,
 * alterar status) e acrescenta filtros por empresa, responsável e prioridade. Exige closure.dispatch.
 */
class Control extends WorkIndex
{
    public ?string $filterCompany = null;

    public ?string $filterUser = null;

    public bool $onlyPriority = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.dispatch'), 403);
    }

    public function render()
    {
        return parent::render()->with('controlMode', true);
    }

    public function updatedFilterCompany(): void
    {
        $this->filterUser       = null;
        $this->selectedStateIds = [];
        $this->resetPage();
    }

    public function updatedFilterUser(): void
    {
        $this->resetPage();
        $this->selectedStateIds = [];
    }

    public function updatedOnlyPriority(): void
    {
        $this->resetPage();
        $this->selectedStateIds = [];
    }

    public function getControlUsersProperty()
    {
        return User::query()
            ->whereNotNull('company_id')
            ->when($this->filterCompany, fn ($q) => $q->where('company_id', $this->filterCompany))
            ->where(fn ($q) => $q->where('closure_operator', true)->orWhere('closure_manager', true)->orWhere('admin', true)->orWhere('superadm', true))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function visibleStatesQuery(): Builder
    {
        $query = parent::visibleStatesQuery();

        if ($this->filterCompany) {
            $query->where('closure_order_states.current_holder_company_id', $this->filterCompany);
        }

        if ($this->filterUser) {
            $query->where(function (Builder $own) {
                $own->where('closure_order_states.assigned_user_id', $this->filterUser)
                    ->orWhere('closure_order_states.current_holder_user_id', $this->filterUser);
            });
        }

        if ($this->onlyPriority) {
            $query->where('closure_order_states.priority', true);
        }

        return $query;
    }
}
