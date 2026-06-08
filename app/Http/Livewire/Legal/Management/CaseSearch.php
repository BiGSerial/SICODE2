<?php

namespace App\Http\Livewire\Legal\Management;

use App\Models\Legal\LegalCase;
use App\Support\Legal\LegalPartyDocument;
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
        $query = LegalCase::query()->with(['adverseParties', 'demands' => function ($q) {
            $q->with(['controller', 'currentAssignee'])
                ->orderByRaw("CASE WHEN LOWER(COALESCE(process_status_at_import, '')) LIKE '%encerrad%' THEN 1 ELSE 0 END ASC")
                ->orderByRaw('ISNULL(source_due_at) ASC')
                ->orderBy('source_due_at', 'asc');
        }]);

        if ($this->search) {
            $s = "%{$this->search}%";
            $documentHash = $this->searchDocumentHash($this->search);
            $query->where(
                function ($q) use ($s, $documentHash) {
                    $q->where('case_number', 'like', $s)
                        ->orWhere('process_number', 'like', $s)
                        ->orWhere('company_name', 'like', $s)
                        ->orWhere('process_manager', 'like', $s)
                        ->orWhere('law_firm', 'like', $s)
                        ->orWhereHas(
                            'demands',
                            fn ($dq) => $dq
                            ->where('source_case_number', 'like', $s)
                            ->orWhere('source_subject', 'like', $s)
                        )
                        ->orWhereHas('adverseParties', fn ($partyQuery) => $partyQuery->where('name', 'like', $s));

                    if ($documentHash) {
                        $q->orWhereHas(
                            'adverseParties',
                            fn ($partyQuery) => $partyQuery->where('document_hash', $documentHash)
                        );
                    }
                }
            );
        }

        if ($this->sourceTypeFilter) {
            $query->whereHas('demands', fn ($q) => $q->where('source_type', $this->sourceTypeFilter));
        }

        if ($this->areaFilter) {
            $query->whereHas('demands', fn ($q) => $q->where('requesting_area_name', 'like', "%{$this->areaFilter}%"));
        }

        if ($this->regionalFilter) {
            $query->whereHas('demands', fn ($q) => $q->where('responsible_area_name', 'like', "%{$this->regionalFilter}%"));
        }

        return $query->orderByDesc('last_seen_at');
    }

    private function searchDocumentHash(string $search): ?string
    {
        $digits = LegalPartyDocument::digits($search);

        if (!in_array(strlen($digits), [11, 14], true) || !LegalPartyDocument::validate($digits)) {
            return null;
        }

        return LegalPartyDocument::hash($digits);
    }

    public function render()
    {
        $cases        = $this->baseQuery()->paginate($this->perPage);
        $selectedCase = null;

        if ($this->selectedCaseId) {
            $selectedCase = LegalCase::with([
                'notes',
                'adverseParties',
                'demands.controller',
                'demands.currentAssignee',
                'demands.events.actor',
            ])->find($this->selectedCaseId);
        }

        return view('livewire.legal.management.case-search', compact('cases', 'selectedCase'));
    }
}
