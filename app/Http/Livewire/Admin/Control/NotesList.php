<?php

namespace App\Http\Livewire\Admin\Control;

use App\Helpers\TextFormatter;
use App\Models\Note;
use App\Traits\WildcardFormmater;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class NotesList extends Component
{
    use WithPagination;
    use TextFormatter;
    use WildcardFormmater;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 100;
    public $search;
    public $advanceSearch;
    public $multiSearch = [];
    public $missingSearch = [];

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();

        if (!$this->search) {
            $this->multiSearch = [];
            $this->missingSearch = [];
            $this->advanceSearch = '';
        }
    }

    public function buscarMulti(): void
    {
        $this->search = '';
        $this->resetPage();
        $this->multiSearch = $this->formatTextToArray($this->advanceSearch ?? '');
    }

    public function clearBatch(): void
    {
        $this->multiSearch = [];
        $this->missingSearch = [];
        $this->advanceSearch = '';
        $this->resetPage();
    }

    private function baseQuery(): Builder
    {
        $base = Note::query();

        if ($this->search) {
            $search = $this->formatWithWildcard($this->search);
            $base->where(function ($query) use ($search) {
                $query->where('note', $search->type, $search->search)
                    ->orWhere('client', $search->type, $search->search)
                    ->orWhere('numPedido', $search->type, $search->search)
                    ->orWhere('pep', $search->type, $search->search)
                    ->orWhere('nexp', $search->type, $search->search)
                    ->orWhere('num_material', $search->type, $search->search)
                    ->orWhere('material', $search->type, $search->search);
            });
        }

        if (!empty($this->multiSearch)) {
            $values = $this->multiSearch;
            $base->whereIn('note', $values);
        }

        return $base->orderByDesc('created_at')->orderByDesc('id');
    }

    protected function computeMissing(array $values, Builder $base): array
    {
        $values = array_values(array_unique(array_filter($values, fn ($v) => $v !== '' && $v !== null)));

        if (empty($values)) {
            return [];
        }

        $matches = (clone $base)->select(['id', 'note'])->get();
        $found = $matches->pluck('note')->filter()->unique()->values()->all();

        return array_values(array_diff($values, $found));
    }

    public function getListsProperty()
    {
        $base = $this->baseQuery();

        if (!empty($this->multiSearch)) {
            $this->missingSearch = $this->computeMissing($this->multiSearch, $base);
        } else {
            $this->missingSearch = [];
        }

        return $base->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.control.notes-list', [
            'lists' => $this->lists,
            'missing' => $this->missingSearch,
        ]);
    }
}
