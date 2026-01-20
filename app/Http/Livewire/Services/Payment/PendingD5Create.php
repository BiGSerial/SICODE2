<?php

namespace App\Http\Livewire\Services\Payment;

use App\Helpers\TextFormatter;
use App\Jobs\ExportPendingD5CreateJob;
use App\Models\FiveNote;
use App\Traits\AppliesQueryFilters;
use App\Traits\WildcardFormmater;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class PendingD5Create extends Component
{
    use WithPagination;
    use TextFormatter;
    use WildcardFormmater;
    use AppliesQueryFilters;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;
    public $search;
    public $advanceSearch;
    public $multiSearch = [];

    public $filtersState = [];

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'filters.updated' => 'onFiltersUpdated',
        'filters.applied' => 'onFiltersUpdated',
    ];

    public function mount($service = null): void
    {
        $this->service = $service;
    }

    public function updatedSearch()
    {
        $this->resetPage();

        if (!$this->search) {
            $this->multiSearch = [];
            $this->advanceSearch = '';
        }
    }

    public function buscarMulti(): void
    {
        $this->search = '';
        $this->resetPage();
        $this->multiSearch = $this->formatTextToArray($this->advanceSearch ?? '');
    }

    public function onFiltersUpdated($payload = []): void
    {
        $this->filtersState = $payload ?: [];
        $this->resetPage();
    }

    private function returnFilterArray($key)
    {
        if (is_array($this->filtersState[$key] ?? null)) {
            return $this->filtersState[$key] ?? [];
        }

        return $this->filtersState[$key] ?? null;
    }

    private function baseQuery(): Builder
    {
        $base = FiveNote::query()
            ->where(function ($q) {
                $q->whereNull('note_d5')
                    ->orWhere('note_d5', '');
            })
            ->where(function ($q) {
                $q->whereNull('is_payed')
                    ->orWhere('is_payed', false);
            })
            ->whereNull('payed_at')
            ->where(function ($q) {
                $q->whereNull('is_archived')
                    ->orWhere('is_archived', false);
            })
            ->where(function ($q) {
                $q->whereNull('isPassive')
                    ->orWhere('isPassive', false);
            })
            ->where(function ($q) {
                $q->whereNull('returned')
                    ->orWhere('returned', false);
            });

        if ($this->search) {
            $search = $this->formatWithWildcard($this->search);

            $base->where(function ($query) use ($search) {
                $query->whereHas('note', function ($q) use ($search) {
                    $q->where('note', $search->type, $search->search);
                })
                    ->orWhereHas('note.Orders', function ($q) use ($search) {
                        $q->where('ordem', $search->type, $search->search);
                    })
                    ->orWhere('loc_install', $search->type, $search->search)
                    ->orWhere('pep', $search->type, $search->search)
                    ->orWhere('codify', $search->type, $search->search)
                    ->orWhere('reason', $search->type, $search->search);
            });
        }

        if (count($this->multiSearch) > 0) {
            $multi = $this->multiSearch;

            $base->where(function ($query) use ($multi) {
                $query->whereHas('note', function ($q) use ($multi) {
                    $q->whereIn('note', $multi);
                })
                    ->orWhereHas('note.Orders', function ($q) use ($multi) {
                        $q->whereIn('ordem', $multi);
                    })
                    ->orWhereIn('loc_install', $multi)
                    ->orWhereIn('pep', $multi)
                    ->orWhereIn('codify', $multi)
                    ->orWhereIn('reason', $multi);
            });
        }

        if ($this->returnFilterArray('company')) {
            $base->whereIn('company_id', $this->returnFilterArray('company'));
        }

        if ($this->returnFilterArray('type')) {
            $base->whereRelation('note', 'type_note', $this->returnFilterArray('type'));
        }

        if ($this->returnFilterArray('city')) {
            $base->whereRelation('note', function ($q) {
                $q->whereIn('nexp', $this->returnFilterArray('city'));
            });
        }

        if ($this->returnFilterArray('desired_between')) {
            $dateRange = $this->returnFilterArray('desired_between');
            if (isset($dateRange['start']) && isset($dateRange['end'])) {
                $base->whereBetween('dispatch_at', [$dateRange['start'], $dateRange['end']]);
            }
        }

        return $base->orderBy('dispatch_at')
            ->orderBy('id');
    }

    public function getListsProperty()
    {
        $page = $this->baseQuery()->paginate($this->perPage);

        $page->load(['note.Orders', 'note.WorkForm.Orders', 'company']);

        return $page;
    }

    public function exportExcel(): void
    {
        $userId = auth()->id();

        if (!$userId) {
            return;
        }

        ExportPendingD5CreateJob::dispatch($this->exportPayload(), $userId);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTACAO INICIADA',
            'text'     => 'Voce recebera uma notificacao quando estiver pronta.',
            'timer'    => 5000,
        ]);
    }

    protected function exportPayload(): array
    {
        return [
            'search'         => $this->search,
            'multipleSearch' => $this->multiSearch,
            'filters'        => $this->filtersState,
        ];
    }

    public function render()
    {
        return view('livewire.services.payment.pending-d5-create', [
            'lists' => $this->lists,
        ]);
    }
}
