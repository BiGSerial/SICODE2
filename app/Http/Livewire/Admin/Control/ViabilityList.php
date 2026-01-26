<?php

namespace App\Http\Livewire\Admin\Control;

use App\Helpers\TextFormatter;
use App\Models\Viability;
use App\Traits\WildcardFormmater;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ViabilityList extends Component
{
    use WithPagination;
    use TextFormatter;
    use WildcardFormmater;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 100;
    public $search;
    public $advanceSearch;
    public $multiSearch = [];
    public $deleteId;

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'confirmDeleteViability' => 'deleteViability',
    ];

    public function updatedSearch(): void
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

    private function baseQuery(): Builder
    {
        $base = Viability::query()->with(['Note', 'Company', 'User', 'Engineer', 'Orders']);

        if ($this->search) {
            $search = $this->formatWithWildcard($this->search);
            $base->where(function ($query) use ($search) {
                $query->where('id', $search->type, $search->search)
                    ->orWhereHas('Note', function ($q) use ($search) {
                        $q->where('note', $search->type, $search->search);
                    })
                    ->orWhereHas('Orders', function ($q) use ($search) {
                        $q->where('ordem', $search->type, $search->search);
                    });
            });
        }

        if (!empty($this->multiSearch)) {
            $values = $this->multiSearch;
            $base->where(function ($query) use ($values) {
                $query->whereIn('id', $values)
                    ->orWhereHas('Note', function ($q) use ($values) {
                        $q->whereIn('note', $values);
                    })
                    ->orWhereHas('Orders', function ($q) use ($values) {
                        $q->whereIn('ordem', $values);
                    });
            });
        }

        return $base->orderByDesc('created_at')->orderByDesc('id');
    }

    public function getListsProperty()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    public function requestDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Remover Viabilidade',
            'msg'           => 'Confirma remover este registro de viabilidade?',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Remova!',
            'btnCanceltxt'  => 'Nao, Cancele',
            'action'        => 'confirmDeleteViability',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhuma viabilidade foi removida.',
        ]);
    }

    public function deleteViability(): void
    {
        if (!$this->deleteId) {
            return;
        }

        $viability = Viability::find($this->deleteId);
        if (!$viability) {
            return;
        }

        $viability->Orders()->detach();
        $viability->Files()->detach();
        $viability->Comments()->detach();
        $viability->Reclaims()->detach();
        $viability->delete();

        $this->deleteId = null;
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Viabilidade removida',
            'timer'    => 2000,
        ]);

        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.control.viability-list', [
            'lists' => $this->lists,
        ]);
    }
}
