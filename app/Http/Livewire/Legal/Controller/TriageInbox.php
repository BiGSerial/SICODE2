<?php

namespace App\Http\Livewire\Legal\Controller;

use App\Models\Legal\{LegalDemand, LegalImportBatch};
use App\Services\Legal\LegalDemandWorkflowService;
use Livewire\Component;

class TriageInbox extends Component
{
    public string $search = '';

    public string $sourceTypeFilter = '';

    public string $originAreaFilter = '';

    public int    $loadedCount = 25;

    public ?int    $confirmIgnoreId = null;

    public string  $confirmIgnoreReason = '';

    protected $queryString = [
        'search'           => ['except' => ''],
        'sourceTypeFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('legal.demands.triage'), 403);
    }

    public function updatedSearch(): void
    {
        $this->loadedCount = 25;
    }

    public function loadMore(): void
    {
        $this->loadedCount += 25;
    }

    public function startTriage(int $demandId): void
    {
        $demand = LegalDemand::findOrFail($demandId);

        try {
            app(LegalDemandWorkflowService::class)->startTriage($demand, auth()->user());
            $this->dispatchBrowserEvent('swal', [
                'icon'  => 'success',
                'title' => 'Triagem iniciada',
                'html'  => 'Demanda movida para triagem. <a href="' . route('legal.demand.detail', $demand->uuid) . '">Ver demanda</a>',
                'timer' => 3000,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => 'Ação não permitida', 'html' => $e->getMessage()]);
        }
    }

    public function showIgnoreConfirm(int $demandId): void
    {
        $this->confirmIgnoreId     = $demandId;
        $this->confirmIgnoreReason = '';
    }

    public function cancelIgnore(): void
    {
        $this->confirmIgnoreId     = null;
        $this->confirmIgnoreReason = '';
    }

    public function confirmIgnore(): void
    {
        $this->validate(['confirmIgnoreReason' => 'required|min:5']);

        $demand = LegalDemand::findOrFail($this->confirmIgnoreId);

        $fromStatus = $demand->internal_status instanceof \BackedEnum
            ? $demand->internal_status->value
            : $demand->internal_status;

        $demand->update(['internal_status' => 'ignored']);
        $demand->events()->create([
            'event_type'  => 'ignored',
            'description' => 'Demanda ignorada na triagem. Motivo: ' . $this->confirmIgnoreReason,
            'actor_id'    => auth()->id(),
            'from_status' => $fromStatus,
            'to_status'   => 'ignored',
            'occurred_at' => now(),
        ]);

        $this->confirmIgnoreId     = null;
        $this->confirmIgnoreReason = '';

        $this->dispatchBrowserEvent('swal', ['icon' => 'success', 'title' => 'Demanda ignorada', 'timer' => 2000]);
    }

    private function query()
    {
        return LegalDemand::query()
            
            ->with(['legalCase', 'lastSeenImportBatch'])
            ->externallyActive()
            ->whereIn('internal_status', ['new_imported', 'triage'])
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(
                    fn ($q2) => $q2
                    ->where('source_case_number', 'like', $s)
                    ->orWhere('source_process_number', 'like', $s)
                    ->orWhere('source_subject', 'like', $s)
                );
            })
            ->when($this->sourceTypeFilter, fn ($q) => $q->where('source_type', $this->sourceTypeFilter))
            ->when($this->originAreaFilter, fn ($q) => $q->where('requesting_area_name', 'like', "%{$this->originAreaFilter}%"))
            ->orderByRaw('ISNULL(source_due_at) ASC')
            ->orderBy('source_due_at', 'asc');
    }

    public function render()
    {
        $demands    = $this->query()->limit($this->loadedCount)->get();
        $totalCount = $this->query()->count();

        $lastBatch = LegalImportBatch::latest('created_at')->first();

        return view('livewire.legal.controller.triage-inbox', [
            'demands'     => $demands,
            'totalCount'  => $totalCount,
            'lastBatch'   => $lastBatch,
        ]);
    }
}
