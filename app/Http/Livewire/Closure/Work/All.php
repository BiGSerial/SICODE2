<?php

namespace App\Http\Livewire\Closure\Work;

use App\Http\Livewire\Closure\Concerns\WithBulkSearch;
use App\Models\Closure\ClosureOrderState;
use App\Models\{Company, Order};
use App\Services\Closure\{ClosureDispatchService, ClosureMetaClassifier, ClosureOrderWorkflowService, ClosurePartnerSignalService, ClosureSettings};
use App\Services\Closure\Queries\ClosureDispatchListQuery;
use Livewire\{Component, WithPagination};

/**
 * "Lista Encerramento" do Serviço: TODAS as Ordens elegíveis (despachadas ou não), como na lista do Despacho.
 * Ver todas é livre; ASSUMIR é que depende do despacho: só quando ele liberou a auto-atribuição (ClosureSettings) e para os tipos
 * liberados. Ordem sem posse é despachada para o próprio usuário; Ordem na pilha da empresa dele é assumida (claim).
 */
class All extends Component
{
    use WithBulkSearch;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['closureAllAssumeConfirmed' => 'assumeConfirmed'];

    public string $search = '';

    public string $type = 'all';

    /** all | free (sem posse) | mine (atribuídas a mim) */
    public string $scope = 'all';

    public int $perPage = 25;

    /** @var array<int, int> */
    public array $selectedOrderIds = [];

    public array $receipt = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('closure.work'), 403);
        abort_unless(app(ClosureSettings::class)->selfAssignEnabled(), 403, 'O despacho não liberou a lista de todas as Ordens.');
    }

    public function render()
    {
        $overview = app(ClosureDispatchListQuery::class)->overview($this->filters(), $this->perPage, $this->page);
        $orders   = $overview['orders'];
        $stateIds = $orders->getCollection()->pluck('state_id')->filter();

        return view('livewire.closure.work.all', [
            'orders'         => $orders,
            'counts'         => $overview['counts'],
            'depositSignals' => app(ClosurePartnerSignalService::class)->activeByState($stateIds),
            'enabled'        => app(ClosureSettings::class)->selfAssignEnabled(),
            'allowedTypes'   => app(ClosureSettings::class)->selfAssignTypes(),
        ]);
    }

    /** [pode assumir, motivo quando não pode] para uma linha da lista. */
    public function assumability(object $row): array
    {
        $settings = app(ClosureSettings::class);
        $user     = auth()->user();

        if (!$settings->selfAssignEnabled()) {
            return [false, 'O despacho não liberou a auto-atribuição: as Ordens chegam até você por despacho.'];
        }

        if (!in_array(ClosureMetaClassifier::letter($row->meta_type), $settings->selfAssignTypes(), true)) {
            return [false, 'O despacho não liberou este tipo de Ordem (' . (ClosureMetaClassifier::letter($row->meta_type) ?? '—') . ') para auto-atribuição.'];
        }

        if ($row->state_id === null) {
            return $user->company_id
                ? [true, null]
                : [false, 'Seu usuário não tem empresa; peça ao despacho para atribuir a Ordem.'];
        }

        if ($row->assigned_user_id || $row->current_holder_user_id) {
            return [false, 'A Ordem já está com ' . ($row->assignee_name ?: 'outro responsável') . '.'];
        }

        if ($row->operational_status !== ClosureOrderWorkflowService::STATUS_AVAILABLE) {
            return [false, 'A Ordem está em um status que não permite assumir.'];
        }

        $mine = !$row->current_holder_company_id || (string) $row->current_holder_company_id === (string) $user->company_id;

        return $mine ? [true, null] : [false, 'A Ordem está na pilha de ' . ($row->holder_company_name ?: 'outra empresa') . '.'];
    }

    public function toggleOrder(int $orderId): void
    {
        $this->selectedOrderIds = in_array($orderId, $this->selectedOrderIds, true)
            ? array_values(array_diff($this->selectedOrderIds, [$orderId]))
            : array_values(array_merge($this->selectedOrderIds, [$orderId]));
    }

    public function askAssume(): void
    {
        $count = count($this->selectedOrderIds);

        if (!app(ClosureSettings::class)->selfAssignEnabled()) {
            $this->toast('Auto-atribuição desabilitada', 'O despacho não liberou a escolha de atividades.', 'warning');

            return;
        }

        if ($count === 0) {
            $this->toast('Nada selecionado', 'Marque ao menos uma Ordem para assumir.', 'warning');

            return;
        }

        $this->dispatchBrowserEvent('closure:confirm', [
            'title'       => 'Assumir Ordens',
            'html'        => "Você deseja assumir <strong>{$count}</strong> Ordem(ns)? Elas vão para a sua Minha atividade e nenhum outro usuário poderá pegá-las.",
            'confirmText' => 'Sim, assumir',
            'cancelText'  => 'Cancelar',
            'icon'        => 'warning',
            'action'      => 'closureAllAssumeConfirmed',
        ]);
    }

    /** Reautoriza cada Ordem no servidor (a lista pode estar velha) e executa uma a uma; resultado parcial. */
    public function assumeConfirmed(): void
    {
        $this->receipt = [];

        if (!app(ClosureSettings::class)->selfAssignEnabled()) {
            $this->toast('Auto-atribuição desabilitada', 'O despacho não liberou a escolha de atividades.', 'warning');

            return;
        }

        $user      = auth()->user();
        $successes = [];
        $refusals  = [];

        foreach (array_values(array_unique(array_map('intval', $this->selectedOrderIds))) as $orderId) {
            $order = Order::query()->find($orderId);
            $label = $order?->ordem ?? "#{$orderId}";
            // Releitura da linha atual (posse/tipo) para decidir com o estado de agora.
            $row = $order ? $this->currentRow($orderId) : null;

            if (!$row) {
                $refusals[] = ['order' => $label, 'reason' => 'A Ordem não está mais na lista.'];

                continue;
            }

            [$can, $why] = $this->assumability($row);

            if (!$can) {
                $refusals[] = ['order' => $label, 'reason' => $why];

                continue;
            }

            try {
                if ($row->state_id === null) {
                    $result = app(ClosureDispatchService::class)->dispatchOrders([$orderId], Company::query()->findOrFail($user->company_id), $user, $user);
                    $item   = collect($result->orders)->firstWhere('order_id', $orderId);

                    if (($item['status'] ?? null) === \App\Services\Closure\ClosureMultiDispatchResult::STATUS_REFUSED) {
                        throw new \RuntimeException($item['message'] ?? 'A Ordem não pôde ser assumida.');
                    }
                } else {
                    app(ClosureOrderWorkflowService::class)->claim(ClosureOrderState::query()->findOrFail($row->state_id), $user);
                }

                $successes[] = $label;
            } catch (\Throwable $exception) {
                $refusals[] = ['order' => $label, 'reason' => $exception->getMessage() ?: 'Não foi possível assumir.'];
            }
        }

        $this->receipt          = ['successes' => $successes, 'refusals' => $refusals];
        $this->selectedOrderIds = [];

        $ok   = count($successes);
        $fail = count($refusals);

        $this->toast(
            $fail === 0 ? 'Ordens assumidas' : 'Atribuição concluída com recusas',
            "{$ok} Ordem(ns) assumida(s)" . ($fail ? ", {$fail} recusada(s)" : '') . '. Elas estão em Minhas atividades.',
            $fail === 0 ? 'success' : ($ok === 0 ? 'error' : 'warning'),
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedScope(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search    = '';
        $this->type      = 'all';
        $this->scope     = 'all';
        $this->bulkTerms = [];
        $this->bulkText  = '';
        $this->resetPage();
    }

    public function exportList(): void
    {
        \App\Jobs\Closure\ExportClosureListJob::dispatch(static::class, $this->filters(), (string) auth()->id());

        $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'success', 'title' => 'Exportação iniciada', 'html' => '<p class="mb-0"><strong>Você será notificado quando o download estiver disponível.</strong></p>', 'timer' => 5000]);
    }

    /** Ordens e Notas que a lista devolve com os filtros atuais (base do resumo da busca em massa). */
    protected function bulkSearchedValues(): array
    {
        $query = app(ClosureDispatchListQuery::class);

        return collect($query->briefs($query->filteredIds($this->filters())))->flatMap(fn (array $brief) => [$brief['ordem'] ?? null, $brief['note'] ?? null])->filter()->all();
    }

    /** @return array<string, mixed> */
    public function filters(): array
    {
        return [
            'search'           => $this->search,
            'bulk'             => $this->bulkTerms,
            'types'            => $this->type === 'all' ? [] : [$this->type],
            'onlyUndispatched' => $this->scope === 'free',
            'assigneeId'       => $this->scope === 'mine' ? (string) auth()->id() : null,
        ];
    }

    private function currentRow(int $orderId): ?object
    {
        $query = app(ClosureDispatchListQuery::class);
        $set   = app(\App\Services\Closure\ClosureEligibleOrders::class)->all();

        if (!isset($set[$orderId])) {
            return null;
        }

        $page = $query->overview(['bulk' => [(string) Order::query()->whereKey($orderId)->value('ordem')]], 5, 1)['orders'];

        return $page->getCollection()->firstWhere('id', $orderId);
    }

    private function toast(string $title, string $text, string $icon = 'success'): void
    {
        $this->dispatchBrowserEvent('notification:toast', ['title' => $title, 'text' => $text, 'icon' => $icon]);
    }
}
