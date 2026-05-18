<?php

namespace App\Http\Livewire\Reports;

use App\Exports\ProductionExport;
use App\Exports\Reports\viabilityexport;
use App\Exports\Reports\viabilityQueryExport;
use App\Models\Production;
use App\Models\Viability;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Viabilities extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $multi_search_input = '';
    public $multi_search_terms = [];

    public $column = 'sended_at';
    public $dt_init;
    public $dt_end;

    public $all = false;

    public function updated($name)
    {
        if (in_array($name, ['search', 'column', 'dt_init', 'dt_end'], true)) {
            $this->resetPage();
        }
    }

    public function applyMultiSearch()
    {
        $terms = preg_split('/[\s,;\n\r\t]+/', (string) $this->multi_search_input);
        $terms = collect($terms)->map(fn ($term) => trim((string) $term))
            ->filter()
            ->unique()
            ->take(300)
            ->values()
            ->all();

        $this->multi_search_terms = $terms;
        if (count($terms) > 0) {
            $this->search = implode(', ', $terms);
        }
        $this->resetPage();
    }

    public function clearMultiSearch()
    {
        $this->multi_search_input = '';
        $this->multi_search_terms = [];
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->multi_search_input = '';
        $this->multi_search_terms = [];
        $this->column = 'sended_at';
        $this->dt_init = null;
        $this->dt_end = null;
        $this->resetPage();
    }

    public function Export()
    {
        // return (new viabilityexport($this->lists->limit(5000)->get()))->download(date('YmdHis-') . 'exportViabilityHiring.xlsx');
        return (new viabilityQueryExport($this->lists->with('Company', 'User', 'Note', 'Engineer')))->download(date('YmdHis-') . 'exportViabilityHiring.xlsx');
    }

    public function getListsProperty()
    {
        $query =  Viability::Query();
        $searchTerms = $this->buildSearchTerms();

        if ($this->column && ($this->dt_init || $this->dt_end)) {

            if ($this->dt_init && !$this->dt_end) {
                $query->whereDate($this->column, '>=', $this->dt_init);
            }

            if (!$this->dt_init && $this->dt_end) {
                $query->whereDate($this->column, '<=', $this->dt_end);
            }

            if ($this->dt_init && $this->dt_end) {
                $query->whereBetween($this->column, [$this->dt_init, $this->dt_end]);
            }
        }

        if (count($searchTerms) > 0) {
            $query->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $like = '%' . $term . '%';
                    $q->orWhereHas('Note', function ($nq) use ($like) {
                        $nq->where('note', 'like', $like)
                            ->orWhere('material', 'like', $like);
                    })->orWhereHas('Orders', function ($oq) use ($like) {
                        $oq->where('ordem', 'like', $like);
                    });
                }
            });
        }

        return $query;

    }

    private function buildSearchTerms(): array
    {
        $inlineTerms = preg_split('/[\s,;\n\r\t]+/', (string) $this->search);
        $inlineTerms = collect($inlineTerms)->map(fn ($term) => trim((string) $term))->filter();

        return $inlineTerms
            ->merge(collect($this->multi_search_terms ?? [])->map(fn ($term) => trim((string) $term)))
            ->filter()
            ->unique()
            ->take(300)
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.reports.viabilities', [
            'lists' => $this->lists->paginate(100),
        ]);
    }
}
