<?php

namespace App\Http\Livewire\Partner;

use App\Models\WorkReport;
use App\Models\ReturnWork;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class WorkedRejectedList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;
    public $search;
    public $multiSearch;
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
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function cleanAll()
    {
        $this->search = '';
        $this->multiSearch = '';
        $this->resetPage();
    }

    public function applyMultiSearch()
    {
        $terms = $this->parseSearchTerms($this->multiSearch);

        $this->search = implode(' ', $terms);
        $this->resetPage();
    }

    public function openRejectDetails(int $workReportId, ?int $startIndex = null)
    {
        $workReport = WorkReport::query()
            ->when(!Auth()->User()->superadm, function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                        ->orWhere('company_id', Auth()->user()->Company->id);
                });
            })
            ->where('rejected', true)
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
                'id' => $returnwork->id,
                'category' => $returnwork->category,
                'text_obs' => $returnwork->text_obs,
                'user_name' => $returnwork->User?->name,
                'created_at' => optional($returnwork->created_at)->format('d/m/Y H:i:s'),
                'created_human' => $returnwork->created_at ? $returnwork->created_at->diffForHumans() : null,
            ];
        })->all();

        $this->selectedRejectedWorkReportId = $workReport->id;
        $this->selectedRejectedNote = $workReport->Note?->note;
        $this->selectedRejectedCompany = $workReport->Company?->name;
        $this->selectedReturnworks = $returnworks;

        $maxIndex = max(0, count($returnworks) - 1);
        $targetIndex = is_null($startIndex) ? $maxIndex : $startIndex;
        $this->selectedReturnworkIndex = min(max(0, $targetIndex), $maxIndex);

        $this->dispatchBrowserEvent('showRejectDetailsModal');
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
        $query = WorkReport::query()
            ->when(!Auth()->User()->superadm, function ($q) {
                $q->where(function ($subQuery) {
                    $subQuery->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                        ->orWhere('company_id', Auth()->user()->Company->id);
                });
            });

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
        ->where('rejected', true)
        ->whereDoesntHave('Note', function ($q) {
            $q->whereIn('nstats', [55])
                ->orWhere(function ($q) {
                    $q->where('nstats', 99)
                        ->where('type_note', 1);
                });
        })
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

    private function parseSearchTerms(?string $value): array
    {
        if (!filled($value)) {
            return [];
        }

        return collect(preg_split('/[\s,;]+/', trim($value)))
            ->map(fn ($term) => trim($term))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function reinform(int $workReportId)
    {
        $workReport = WorkReport::when(!Auth()->User()->superadm, function ($q) {
            $q->where(function ($query) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
            });
        })
            ->where('rejected', true)
            ->findOrFail($workReportId);

        $token = Str::random(48);

        session()->put("partner_reinform_work_report.{$token}", [
            'work_report_id' => $workReport->id,
            'created_at' => now()->timestamp,
        ]);

        return redirect()->route('partner.report.reinformWorkreport', ['token' => $token]);
    }

    public function render()
    {
        return view('livewire.partner.worked-rejected-list', [
            'lists' => $this->lists
        ]);
    }
}
