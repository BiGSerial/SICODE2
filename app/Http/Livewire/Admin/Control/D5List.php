<?php

namespace App\Http\Livewire\Admin\Control;

use App\Helpers\TextFormatter;
use App\Models\FiveNote;
use App\Traits\WildcardFormmater;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class D5List extends Component
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
        $base = FiveNote::query()->with(['note', 'company']);

        if ($this->search) {
            $search = $this->formatWithWildcard($this->search);
            $base->where(function ($query) use ($search) {
                $query->where('note_d5', $search->type, $search->search)
                    ->orWhereHas('note', function ($q) use ($search) {
                        $q->where('note', $search->type, $search->search);
                    });
            });
        }

        if (!empty($this->multiSearch)) {
            $values = $this->multiSearch;
            $base->where(function ($query) use ($values) {
                $query->whereIn('note_d5', $values)
                    ->orWhereHas('note', function ($q) use ($values) {
                        $q->whereIn('note', $values);
                    });
            });
        }

        return $base->orderByDesc('created_at')->orderByDesc('id');
    }

    protected function computeMissing(array $values, Builder $base): array
    {
        $values = array_values(array_unique(array_filter($values, fn ($v) => $v !== '' && $v !== null)));

        if (empty($values)) {
            return [];
        }

        $probe = (clone $base)->select(['id', 'note_d5', 'note_id'])->with(['note:id,note']);
        $matches = $probe->get();
        $found = [];

        foreach ($matches as $item) {
            if ($item->note_d5 && in_array($item->note_d5, $values, true)) {
                $found[] = $item->note_d5;
            }
            if ($item->note?->note && in_array($item->note->note, $values, true)) {
                $found[] = $item->note->note;
            }
        }

        $found = array_values(array_unique($found));

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
        return view('livewire.admin.control.d5-list', [
            'lists' => $this->lists,
            'missing' => $this->missingSearch,
        ]);
    }
}
