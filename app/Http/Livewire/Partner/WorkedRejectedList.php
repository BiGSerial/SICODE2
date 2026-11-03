<?php

namespace App\Http\Livewire\Partner;

use App\Http\Livewire\Concerns\WithListFilters;
use App\Http\Livewire\Partner\Concerns\{AuthorizesPartnerAccess, PartnerListToolkit};
use App\Models\{ReturnWork, WorkReport};
use Illuminate\Support\Str;
use Livewire\{Component, WithPagination};

/**
 * Informes rejeitados (lista de controle do portal da parceira: docs/PADRAO_LISTAS_OPERACIONAIS.md §10).
 * Escopo por empresa vem de `applyPartnerCompanyScope`; o filtro Empresa só reduz esse escopo.
 */
class WorkedRejectedList extends Component
{
    use AuthorizesPartnerAccess;
    use PartnerListToolkit;
    use WithListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $search = '';

    public $multiSearch = '';

    public $selectedRejectedWorkReportId;

    public $selectedRejectedNote;

    public $selectedRejectedCompany;

    public $selectedReturnworks = [];

    public $selectedReturnworkIndex = 0;

    protected $listeners = [
        'refresh_rejected' => '$refresh',
    ];

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
        'filters' => ['except' => [], 'as' => 'f'],
    ];

    public function mount(): void
    {
        $this->perPage = in_array((int) $this->perPage, [25, 50, 100, 250, 500], true) ? (int) $this->perPage : 50;
        $this->filters = $this->normalizeFilters($this->filters);
    }

    protected function listFilterDefinitions(): array
    {
        return [
            'company' => ['label' => 'Empreiteira'],
            'rubrica' => ['label' => 'Rubrica'],
            'region'  => ['label' => 'Região'],
            'city'    => ['label' => 'Município', 'depends' => ['region']],
        ];
    }

    protected function listFilterOptions(): array
    {
        return ['company' => $this->companyFilterOptions()] + $this->reportLocationFilterOptions();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->perPage = in_array((int) $this->perPage, [25, 50, 100, 250, 500], true) ? (int) $this->perPage : 50;
        $this->resetPage();
    }

    /** "Limpar filtros": busca e filtros. Não mexe na quantidade por página. */
    public function clearAllCriteria()
    {
        $this->search      = '';
        $this->multiSearch = '';
        $this->clearListFilters();
    }

    public function applyMultiSearch()
    {
        $terms = $this->parseSearchTerms($this->multiSearch);

        $this->search = implode(' ', $terms);
        $this->resetPage();
    }

    public function openRejectDetails(int $workReportId, ?int $startIndex = null)
    {
        $query = WorkReport::query();

        $this->applyPartnerCompanyScope($query);

        $this->applyPartnerBranchScopeToNoteRelation($query);

        $workReport = $query
            ->pendingRejectedForPartner()
            ->with([
                'Note',
                'Company',
                'Returnwork' => function ($q) {
                    $q->with('User')->orderBy('created_at');
                },
            ])
            ->findOrFail($workReportId);

        $returnworks = $workReport->Returnwork->values()->map(function ($returnwork) {
            return [
                'id'            => $returnwork->id,
                'category'      => $returnwork->category,
                'text_obs'      => $returnwork->text_obs,
                'user_name'     => $returnwork->User?->name,
                'created_at'    => optional($returnwork->created_at)->format('d/m/Y H:i:s'),
                'created_human' => $returnwork->created_at ? $returnwork->created_at->diffForHumans() : null,
            ];
        })->all();

        $this->selectedRejectedWorkReportId = $workReport->id;
        $this->selectedRejectedNote         = $workReport->Note?->note;
        $this->selectedRejectedCompany      = $workReport->Company?->name;
        $this->selectedReturnworks          = $returnworks;

        $maxIndex                      = max(0, count($returnworks) - 1);
        $targetIndex                   = is_null($startIndex) ? $maxIndex : $startIndex;
        $this->selectedReturnworkIndex = min(max(0, $targetIndex), $maxIndex);

    }

    public function closeRejectDetails()
    {
        $this->reset(['selectedRejectedWorkReportId', 'selectedRejectedNote', 'selectedRejectedCompany', 'selectedReturnworks', 'selectedReturnworkIndex']);
    }

    public function nextRejectDetail()
    {
        $lastIndex = count($this->selectedReturnworks) - 1;

        if ($lastIndex < 0) {
            return;
        }

        $this->selectedReturnworkIndex = min($this->selectedReturnworkIndex + 1, $lastIndex);
    }

    public function previousRejectDetail()
    {
        $this->selectedReturnworkIndex = max($this->selectedReturnworkIndex - 1, 0);
    }

    public function goToRejectDetail(int $index)
    {
        $lastIndex = count($this->selectedReturnworks) - 1;

        if ($lastIndex < 0) {
            return;
        }

        $this->selectedReturnworkIndex = min(max(0, $index), $lastIndex);
    }

    public function getListsProperty()
    {
        $query = WorkReport::query();

        $this->applyPartnerCompanyScope($query);

        $this->applyPartnerBranchScopeToNoteRelation($query);

        $this->applyCompanyFilter($query);
        $this->applyNoteLocationFilters($query);

        $searchTerms = $this->parseSearchTerms($this->search);

        if (!empty($searchTerms)) {
            $query->where(function ($subQuery) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $subQuery->orWhereRelation('Note', 'note', 'like', "%{$term}%")
                        ->orWhereRelation('Note', 'numPedido', 'like', "%{$term}%")
                        ->orWhereRelation('Orders', 'ordem', 'like', "%{$term}%")
                        ->orWhereRelation('Returnwork', 'category', 'like', "%{$term}%")
                        ->orWhereRelation('Returnwork', 'text_obs', 'like', "%{$term}%")
                        ->orWhereRelation('Returnwork.User', 'name', 'like', "%{$term}%");
                }
            });
        }

        return $query->addSelect([
            'last_returned_at' => ReturnWork::select('created_at')
                ->whereColumn('work_report_id', 'work_reports.id')
                ->latest('created_at')
                ->limit(1),
        ])
        ->pendingRejectedForPartner()
        ->with([
            'Note',
            'Orders',
            'Company',
            'LatestReturnwork.User',
        ])
        ->orderByRaw('last_returned_at IS NULL')
        ->orderBy('last_returned_at')
        ->orderBy('work_reports.id')
        ->paginate($this->perPage);
    }

    public function reinform(int $workReportId)
    {
        $query = WorkReport::query();

        $this->applyPartnerCompanyScope($query);

        $this->applyPartnerBranchScopeToNoteRelation($query);

        $workReport = $query
            ->pendingRejectedForPartner()
            ->findOrFail($workReportId);

        $token = Str::random(48);

        session()->put("partner_reinform_work_report.{$token}", [
            'work_report_id' => $workReport->id,
            'created_at'     => now()->timestamp,
        ]);

        return redirect()->route('partner.report.reinformWorkreport', ['token' => $token]);
    }

    public function render()
    {
        return view('livewire.partner.worked-rejected-list', [
            'lists'       => $this->lists,
            'hasCriteria' => $this->hasListFilters() || trim((string) $this->search) !== '',
        ]);
    }
}
