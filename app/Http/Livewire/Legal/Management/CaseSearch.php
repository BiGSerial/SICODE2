<?php

namespace App\Http\Livewire\Legal\Management;

use App\Models\Legal\LegalCase;
use Livewire\{Component, WithPagination};

class CaseSearch extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $statusFilter = '';

    public string $sourceTypeFilter = '';

    public string $areaFilter = '';

    public string $regionalFilter = '';

    public string $periodFilter = '';

    public ?string $periodFrom = null;

    public ?string $periodTo = null;

    public ?int $selectedCaseId = null;

    public string $caseTab = 'demands';

    public int $perPage = 20;

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('legal.manager'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->selectedCaseId = null;
    }

    public function selectCase(int $caseId): void
    {
        $this->selectedCaseId = ($this->selectedCaseId === $caseId) ? null : $caseId;
        $this->caseTab        = 'demands';
    }

    private function baseQuery()
    {
        $query = LegalCase::query()->with(['demands' => function ($q) {
            $q->with(['controller', 'currentAssignee'])->orderBy('source_due_at');
        }]);

        if ($this->search) {
            $s = "%{$this->search}%";
            $query->where(
                fn ($q) => $q
                ->where('case_number', 'like', $s)
                ->orWhere('process_number', 'like', $s)
                ->orWhere('process_number_core', 'like', $s)
                ->orWhere('company_name', 'like', $s)
                ->orWhere('legal_responsible_name', 'like', $s)
                ->orWhere('law_firm_name', 'like', $s)
                ->orWhereHas(
                    'demands',
                    fn ($dq) => $dq
                    ->where('source_case_number', 'like', $s)
                    ->orWhere('opposing_party', 'like', $s)
                    ->orWhere('subject', 'like', $s)
                )
            );
        }

        if ($this->sourceTypeFilter) {
            $query->whereHas('demands', fn ($q) => $q->where('source_type', $this->sourceTypeFilter));
        }

        if ($this->areaFilter) {
            $query->whereHas('demands', fn ($q) => $q->where('origin_area_name', 'like', "%{$this->areaFilter}%"));
        }

        return $query->orderByDesc('last_seen_at');
    }

    public function render()
    {
        $cases        = $this->baseQuery()->paginate($this->perPage);
        $selectedCase = null;

        if ($this->selectedCaseId) {
            $selectedCase = LegalCase::with([
                'demands.controller',
                'demands.currentAssignee',
                'demands.events.actor',
            ])->find($this->selectedCaseId);
        }

        return view('livewire.legal.management.case-search', compact('cases', 'selectedCase'));
    }
}
