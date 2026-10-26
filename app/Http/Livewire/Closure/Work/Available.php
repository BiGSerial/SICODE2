<?php

namespace App\Http\Livewire\Closure\Work;

use App\Http\Livewire\Closure\Work\Concerns\ClosureWorkListing;
use App\Models\Closure\ClosureOrderState;
use App\Services\Closure\{ClosureMetaClassifier, ClosureOrderBatchWorkflowService, ClosureOrderWorkflowService, ClosureSettings};
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/**
 * "Atividade Encerramento": lista para o usuário pegar Ordens disponíveis (auto-atribuição).
 * Só funciona com ClosureSettings::selfAssignEnabled() e para os tipos de selfAssignTypes().
 * Auto-atribuição não notifica ninguém.
 */
class Available extends Component
{
    use ClosureWorkListing;

    public array $selectedStateIds = [];

    public array $receipt = [];

    protected $listeners = ['closureAvailableClaimConfirmed' => 'claimConfirmed'];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.work'), 403);
    }

    public function render()
    {
        $enabled = app(ClosureSettings::class)->selfAssignEnabled();

        return view('livewire.closure.work.available', [
            'enabled'      => $enabled,
            'allowedTypes' => app(ClosureSettings::class)->selfAssignTypes(),
            'states'       => $enabled ? $this->availableQuery()->limit(ClosureOrderBatchWorkflowService::MAX_ITEMS)->get() : collect(),
        ]);
    }

    public function exportQuery(): Builder
    {
        return $this->availableQuery();
    }

    public function updatedStatusUsers(): void
    {
        $this->selectedStateIds = [];
    }

    public function exportList(): void
    {
        \App\Jobs\Closure\ExportClosureListJob::dispatch(static::class, ['type' => $this->type, 'status' => $this->status, 'search' => $this->search, 'statusUsers' => $this->statusUsers, 'bulkTerms' => $this->bulkTerms], (string) auth()->id());

        $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'success', 'title' => 'Exportação iniciada', 'html' => '<p class="mb-0"><strong>Você será notificado quando o download estiver disponível.</strong></p>', 'timer' => 5000]);
    }

    public function updatedType(): void
    {
        $this->selectedStateIds = [];
    }

    public function updatedSearch(): void
    {
        $this->selectedStateIds = [];
    }

    public function toggleState(int $stateId): void
    {
        $selected               = collect($this->selectedStateIds)->map(fn ($id) => (int) $id);
        $this->selectedStateIds = $selected->contains($stateId)
            ? $selected->reject(fn (int $id) => $id === $stateId)->values()->all()
            : $selected->push($stateId)->unique()->values()->all();
    }

    public function toggleVisibleStates(): void
    {
        $visible  = $this->availableQuery()->limit(ClosureOrderBatchWorkflowService::MAX_ITEMS)->pluck('closure_order_states.id')->map(fn ($id) => (int) $id);
        $selected = collect($this->selectedStateIds)->map(fn ($id) => (int) $id);

        $this->selectedStateIds = $visible->isNotEmpty() && $visible->every(fn (int $id) => $selected->contains($id))
            ? $selected->reject(fn (int $id) => $visible->contains($id))->values()->all()
            : $selected->merge($visible)->unique()->values()->all();
    }

    /** Abre a confirmação (SweetAlert) para assumir as selecionadas. */
    public function askClaim(): void
    {
        if (!app(ClosureSettings::class)->selfAssignEnabled()) {
            $this->toast('Auto-atribuição desabilitada', 'O despachante não liberou a escolha de atividades.', 'warning');

            return;
        }

        $count = count($this->selectedStateIds);

        if ($count === 0) {
            $this->toast('Nada selecionado', 'Selecione ao menos uma Ordem para assumir.', 'warning');

            return;
        }

        if ($count > ClosureOrderBatchWorkflowService::MAX_ITEMS) {
            $this->toast('Seleção grande demais', 'Assuma no máximo ' . ClosureOrderBatchWorkflowService::MAX_ITEMS . ' Ordens por vez.', 'warning');

            return;
        }

        $this->dispatchBrowserEvent('closure:confirm', [
            'title'       => 'Assumir Ordens',
            'html'        => "Você deseja assumir <strong>{$count}</strong> Ordem(ns)? Nenhum outro usuário poderá pegá-las.",
            'confirmText' => 'Sim, assumir',
            'cancelText'  => 'Cancelar',
            'icon'        => 'warning',
            'action'      => 'closureAvailableClaimConfirmed',
        ]);
    }

    /** Executa por Ordem (cada claim() roda na própria transação e trava a linha); resultado parcial. */
    public function claimConfirmed(): void
    {
        $this->receipt = [];

        if (!app(ClosureSettings::class)->selfAssignEnabled()) {
            $this->toast('Auto-atribuição desabilitada', 'O despachante não liberou a escolha de atividades.', 'warning');

            return;
        }

        $ids = collect($this->selectedStateIds)->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty() || $ids->count() > ClosureOrderBatchWorkflowService::MAX_ITEMS) {
            $this->toast('Seleção inválida', 'Selecione de 1 a ' . ClosureOrderBatchWorkflowService::MAX_ITEMS . ' Ordens.', 'warning');

            return;
        }

        $workflow  = app(ClosureOrderWorkflowService::class);
        $user      = auth()->user();
        $successes = [];
        $refusals  = [];

        foreach ($ids as $id) {
            $state = $this->availableQuery()->find($id);

            if (!$state) {
                $label      = ClosureOrderState::query()->with('Order')->find($id)?->Order?->ordem ?? "Item {$id}";
                $refusals[] = ['order' => $label, 'reason' => 'A Ordem não está mais disponível para auto-atribuição.'];

                continue;
            }

            $label = $state->Order?->ordem ?? "Item {$id}";

            try {
                $workflow->claim($state, $user);
                $successes[] = $label;
            } catch (\Throwable $exception) {
                $refusals[] = ['order' => $label, 'reason' => $exception->getMessage() ?: 'Não foi possível assumir.'];
            }
        }

        $this->receipt          = ['successes' => $successes, 'refusals' => $refusals];
        $this->selectedStateIds = [];
        $ok                     = count($successes);
        $fail                   = count($refusals);

        $this->toast(
            $fail === 0 ? 'Ordens assumidas' : 'Atribuição concluída com recusas',
            "{$ok} Ordem(ns) assumida(s)" . ($fail ? ", {$fail} recusada(s)" : '') . '. Elas estão em Minha atividade.',
            $fail === 0 ? 'success' : ($ok === 0 ? 'error' : 'warning'),
        );
    }

    /** Ordens AVAILABLE, sem responsável, da pilha da empresa do usuário (ou sem empresa), de tipo liberado. */
    private function availableQuery(): Builder
    {
        $user  = auth()->user();
        $types = app(ClosureSettings::class)->selfAssignTypes();
        $query = $this->baseListingQuery();

        app(ClosureMetaClassifier::class)->whereMetaType($query, $types);

        return $query
            ->where('closure_order_states.operational_status', ClosureOrderWorkflowService::STATUS_AVAILABLE)
            ->whereNull('closure_order_states.assigned_user_id')
            ->whereNull('closure_order_states.current_holder_user_id')
            ->where(function (Builder $company) use ($user) {
                $company->whereNull('closure_order_states.current_holder_company_id');

                if ($user->company_id) {
                    $company->orWhere('closure_order_states.current_holder_company_id', $user->company_id);
                }
            })
            ->orderByDesc('closure_order_states.priority')
            ->orderBy('closure_order_states.entered_current_status_at');
    }
}
