<?php

namespace App\Http\Livewire\Admin\Control;

use App\Models\{Company, Order, Partial, Production, User};
use Livewire\Component;

class PartialEdit extends Component
{
    public ?Partial $partial = null;

    public $companies = [];
    public $users = [];
    public $availableOrders = [];
    public $linkedOrders = [];
    public $availableProductions = [];
    public $linkedProductions = [];
    public $orderId;
    public $productionId;

    public ?string $decisionAt = null;
    public ?string $paymentAt = null;
    public ?string $supervisionAt = null;

    protected $listeners = [
        'getInfoResponse',
        'resetForm' => 'resetForm',
    ];

    protected function rules(): array
    {
        return [
            'partial.note_id' => ['required', 'integer'],
            'partial.company_id' => ['nullable', 'uuid'],
            'partial.user_id' => ['nullable', 'uuid'],
            'partial.responsible' => ['nullable', 'string', 'max:191'],
            'partial.value' => ['nullable', 'numeric'],
            'partial.observation' => ['nullable', 'string'],
            'partial.engineer_info' => ['nullable', 'string'],
            'partial.allow' => ['boolean'],
            'partial.deny' => ['boolean'],
            'partial.payment' => ['boolean'],
            'partial.supervision' => ['boolean'],
            'partial.complete' => ['boolean'],
            'partial.engineer_id' => ['nullable', 'uuid'],
            'partial.supervision_id' => ['nullable', 'uuid'],
            'partial.payment_id' => ['nullable', 'uuid'],
            'decisionAt' => ['nullable', 'date'],
            'paymentAt' => ['nullable', 'date'],
            'supervisionAt' => ['nullable', 'date'],
        ];
    }

    public function mount(): void
    {
        $this->companies = Company::orderBy('name')->get(['id', 'name']);
        $this->users = User::orderBy('name')->get(['id', 'name']);
    }

    public function getInfoResponse(Partial $partial): void
    {
        $this->resetForm(false);
        $this->partial = $partial->load([
            'Note',
            'company',
            'user',
            'engineer',
            'supervisor',
            'payer',
            'orders',
            'productions.service',
            'productions.user',
        ]);
        $this->decisionAt = $this->formatDateTimeLocal($this->partial->decision_at);
        $this->paymentAt = $this->formatDateTimeLocal($this->partial->payment_at);
        $this->supervisionAt = $this->formatDateTimeLocal($this->partial->supervision_at);
        $this->refreshOrders();
        $this->refreshProductionLists();

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'adminPartialModal',
        ]);
    }

    public function addOrder(?int $orderId = null): void
    {
        $orderId = $orderId ?: $this->orderId;

        if (!$this->partial || !$orderId) {
            return;
        }

        $order = Order::find($orderId);

        if (!$order || $order->note_id !== $this->partial->note_id) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Atividade nao pertence a mesma nota',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->partial->orders()->syncWithoutDetaching([$orderId]);
        $this->partial->load('orders');
        $this->refreshOrders();
        $this->orderId = null;
    }

    public function removeOrder(int $orderId): void
    {
        if (!$this->partial) {
            return;
        }

        $this->partial->orders()->detach($orderId);
        $this->partial->load('orders');
        $this->refreshOrders();
    }

    public function addProduction(?int $productionId = null): void
    {
        $productionId = $productionId ?: $this->productionId;

        if (!$this->partial || !$productionId) {
            return;
        }

        $production = Production::with(['service', 'user'])->find($productionId);

        if (!$production) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Producao nao encontrada',
                'timer'    => 2000,
            ]);

            return;
        }

        if ($production->note_id !== $this->partial->note_id) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Producao nao pertence a mesma nota',
                'timer'    => 2500,
            ]);

            return;
        }

        $production->partial = true;
        $production->partial_at = $production->partial_at ?: now();
        $production->save();

        $this->partial->productions()->syncWithoutDetaching([$production->id]);
        $this->partial->load('productions.service', 'productions.user');
        $this->refreshProductionLists();
        $this->productionId = null;

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Producao vinculada ao informe parcial',
            'timer'    => 2000,
        ]);
    }

    public function removeProduction(int $productionId): void
    {
        if (!$this->partial) {
            return;
        }

        $production = Production::with('partialInforms')->find($productionId);

        if (!$production) {
            return;
        }

        $this->partial->productions()->detach($productionId);
        $production->load('partialInforms');

        if ($production->partialInforms->isEmpty()) {
            $production->partial = false;
            $production->partial_at = null;
            $production->save();
        }

        $this->partial->load('productions.service', 'productions.user');
        $this->refreshProductionLists();
    }

    public function toggleProductionFlag(int $productionId, string $flag): void
    {
        if (!$this->partial || !in_array($flag, ['partial', 'completed', 'confirmed'], true)) {
            return;
        }

        $production = $this->partial->productions()->where('productions.id', $productionId)->first();

        if (!$production) {
            return;
        }

        $production->{$flag} = !$production->{$flag};

        if ($flag === 'partial') {
            $production->partial_at = $production->partial ? ($production->partial_at ?: now()) : null;
        }

        if ($flag === 'completed') {
            $production->completed_at = $production->completed ? ($production->completed_at ?: now()) : null;
        }

        if ($flag === 'confirmed') {
            $production->confirmed_at = $production->confirmed ? ($production->confirmed_at ?: now()) : null;
        }

        $production->save();
        $this->partial->load('productions.service', 'productions.user');
        $this->refreshProductionLists();
    }

    public function reject(): void
    {
        if (!$this->partial) {
            return;
        }

        $this->partial->allow = false;
        $this->partial->deny = true;
        $this->partial->complete = false;
        $this->decisionAt = now()->format('Y-m-d\TH:i');
    }

    public function approve(): void
    {
        if (!$this->partial) {
            return;
        }

        $this->partial->allow = true;
        $this->partial->deny = false;
        $this->decisionAt = now()->format('Y-m-d\TH:i');
    }

    public function save(): void
    {
        if (!$this->partial) {
            return;
        }

        $this->validate();

        $this->partial->decision_at = $this->normalizeDateTime($this->decisionAt);
        $this->partial->payment_at = $this->normalizeDateTime($this->paymentAt);
        $this->partial->supervision_at = $this->normalizeDateTime($this->supervisionAt);
        $this->partial->save();

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Informe parcial atualizado',
            'timer'    => 2500,
        ]);

        $this->dispatchBrowserEvent('hideModal');
        $this->resetForm(false);
        $this->emitUp('refresh_list');
    }

    public function resetForm(bool $refresh = true): void
    {
        $this->resetErrorBag();
        $this->partial = null;
        $this->availableOrders = [];
        $this->linkedOrders = [];
        $this->availableProductions = [];
        $this->linkedProductions = [];
        $this->orderId = null;
        $this->productionId = null;
        $this->decisionAt = null;
        $this->paymentAt = null;
        $this->supervisionAt = null;

        if ($refresh) {
            $this->emitUp('refresh_list');
        }
    }

    private function refreshOrders(): void
    {
        if (!$this->partial?->note_id) {
            $this->availableOrders = [];
            $this->linkedOrders = [];

            return;
        }

        $orders = Order::where('note_id', $this->partial->note_id)->orderBy('ordem')->get();
        $linkedIds = $this->partial->orders->pluck('id')->all();

        $this->linkedOrders = $orders->whereIn('id', $linkedIds)->values()->all();
        $this->availableOrders = $orders->whereNotIn('id', $linkedIds)->values()->all();
    }

    private function refreshProductionLists(): void
    {
        if (!$this->partial?->note_id) {
            $this->availableProductions = [];
            $this->linkedProductions = [];

            return;
        }

        $all = Production::with(['service', 'user', 'partialInforms'])
            ->where('note_id', $this->partial->note_id)
            ->orderByDesc('created_at')
            ->get();

        $linkedIds = $this->partial->productions->pluck('id')->all();

        $this->linkedProductions = $all->whereIn('id', $linkedIds)->values()->all();
        $this->availableProductions = $all->whereNotIn('id', $linkedIds)->values()->all();
    }

    private function formatDateTimeLocal($value): ?string
    {
        return $value ? $value->format('Y-m-d\TH:i') : null;
    }

    private function normalizeDateTime(?string $value): ?string
    {
        return $value ?: null;
    }

    public function render()
    {
        return view('livewire.admin.control.partial-edit');
    }
}
