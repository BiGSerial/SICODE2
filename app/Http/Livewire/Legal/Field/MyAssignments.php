<?php

namespace App\Http\Livewire\Legal\Field;

use App\Models\Legal\LegalDemandAssignment;
use App\Services\Legal\LegalDemandWorkflowService;
use Livewire\{Component, WithPagination};

class MyAssignments extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $tab = 'pending';

    public string $search = '';

    public string $sortBy = 'due_at';

    protected $queryString = [
        'tab'    => ['except' => 'pending'],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('legal.demands.answer'), 403);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmReceipt(int $assignmentId): void
    {
        $assignment = LegalDemandAssignment::where('id', $assignmentId)
            ->where('to_user_id', auth()->id())
            ->firstOrFail();

        try {
            app(LegalDemandWorkflowService::class)->receiveInField($assignment, auth()->user());
            $this->dispatchBrowserEvent('swal', [
                'icon'  => 'success',
                'title' => 'Recebimento confirmado',
                'html'  => 'Você pode agora trabalhar na resposta.',
                'timer' => 2500,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->dispatchBrowserEvent('swal', ['icon' => 'error', 'title' => 'Erro', 'html' => $e->getMessage()]);
        }
    }

    private function baseQuery()
    {
        $query = LegalDemandAssignment::query()
            ->where('to_user_id', auth()->id())
            ->with(['legalDemand.legalCase', 'sentBy'])
            ->whereNotIn('status', ['cancelled', 'closed']);

        match ($this->tab) {
            'pending'     => $query->whereIn('status', ['sent', 'received']),
            'in_progress' => $query->where('status', 'in_progress'),
            'correction'  => $query->where('status', 'returned_for_correction'),
            'answered'    => $query->whereIn('status', ['answered', 'returned_to_controller']),
            default       => null,
        };

        if ($this->search) {
            $s = "%{$this->search}%";
            $query->whereHas(
                'legalDemand',
                fn ($q) => $q
                ->where('source_case_number', 'like', $s)
                ->orWhere('subject', 'like', $s)
            );
        }

        return $query->orderByRaw("CASE
                WHEN status = 'returned_for_correction' THEN 1
                WHEN status IN ('sent') THEN 2
                ELSE 3
            END")
            ->orderByRaw("ISNULL(legal_demands.source_due_at, '9999-12-31') ASC")
            ->join('legal_demands', 'legal_demands.id', '=', 'legal_demand_assignments.legal_demand_id');
    }

    private function kpis(): array
    {
        $base = LegalDemandAssignment::where('to_user_id', auth()->id())
            ->whereNotIn('status', ['cancelled', 'closed']);

        return [
            'pending'     => (clone $base)->whereIn('status', ['sent', 'received'])->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'correction'  => (clone $base)->where('status', 'returned_for_correction')->count(),
        ];
    }

    public function render()
    {
        $assignments = $this->baseQuery()->paginate(20);
        $kpis        = $this->kpis();

        return view('livewire.legal.field.my-assignments', compact('assignments', 'kpis'));
    }
}
