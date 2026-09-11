<?php

namespace App\Http\Livewire\Admin\Control;

use App\Helpers\TextFormatter;
use App\Models\Partial;
use App\Traits\WildcardFormmater;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PartialList extends Component
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
    public $deleteId;

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'confirmDeletePartial' => 'deletePartial',
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
        $base = Partial::query()->with(['Note', 'company', 'user', 'engineer', 'supervisor', 'payer', 'orders']);

        if ($this->search) {
            $search = $this->formatWithWildcard($this->search);
            $base->where(function ($query) use ($search) {
                $query->where('id', $search->type, $search->search)
                    ->orWhere('responsible', $search->type, $search->search)
                    ->orWhereHas('Note', fn ($q) => $q->where('note', $search->type, $search->search))
                    ->orWhereHas('orders', fn ($q) => $q->where('ordem', $search->type, $search->search));
            });
        }

        if (!empty($this->multiSearch)) {
            $values = $this->multiSearch;
            $base->where(function ($query) use ($values) {
                $query->whereIn('id', $values)
                    ->orWhereIn('responsible', $values)
                    ->orWhereHas('Note', fn ($q) => $q->whereIn('note', $values))
                    ->orWhereHas('orders', fn ($q) => $q->whereIn('ordem', $values));
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

        $found = (clone $base)->get()->flatMap(function ($item) {
            return array_filter([
                (string) $item->id,
                (string) ($item->responsible ?? ''),
                (string) ($item->Note->note ?? ''),
                ...$item->orders->pluck('ordem')->map(fn ($order) => (string) $order)->all(),
            ]);
        })->filter()->unique()->values()->all();

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

    public function approve(int $id): void
    {
        $partial = Partial::find($id);

        if (!$partial) {
            return;
        }

        $partial->forceFill([
            'allow' => true,
            'deny' => false,
            'decision_at' => now(),
            'engineer_id' => auth()->id(),
        ])->save();

        $this->emit('refresh_list');
    }

    public function reject(int $id): void
    {
        $partial = Partial::find($id);

        if (!$partial) {
            return;
        }

        $partial->forceFill([
            'allow' => false,
            'deny' => true,
            'complete' => false,
            'decision_at' => now(),
            'engineer_id' => auth()->id(),
        ])->save();

        $this->emit('refresh_list');
    }

    public function requestDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Remover Informe Parcial',
            'msg'           => 'Tem certeza que deseja remover este informe parcial? Vinculos com atividades e arquivos serao desassociados.',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, remover',
            'btnCanceltxt'  => 'Nao, cancelar',
            'action'        => 'confirmDeletePartial',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg'    => 'Nenhum informe parcial foi removido.',
        ]);
    }

    public function deletePartial(): void
    {
        if (!$this->deleteId) {
            return;
        }

        $partial = Partial::with(['orders', 'files', 'productions.partialInforms'])->find($this->deleteId);

        if (!$partial) {
            $this->deleteId = null;
            return;
        }

        DB::transaction(function () use ($partial) {
            $productions = $partial->productions;

            $partial->orders()->detach();
            $partial->files()->detach();
            $partial->productions()->detach();

            foreach ($productions as $production) {
                $production->load('partialInforms');

                if ($production->partialInforms->isEmpty()) {
                    $production->forceFill([
                        'partial' => false,
                        'partial_at' => null,
                    ])->save();
                }
            }

            $partial->delete();
        });

        $this->deleteId = null;
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Informe parcial removido',
            'timer'    => 2000,
        ]);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.control.partial-list', [
            'lists' => $this->lists,
            'missing' => $this->missingSearch,
        ]);
    }
}
