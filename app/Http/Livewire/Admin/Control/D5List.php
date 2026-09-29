<?php

namespace App\Http\Livewire\Admin\Control;

use App\Helpers\TextFormatter;
use App\Models\FiveNote;
use App\Traits\WildcardFormmater;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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
    public $deleteId;

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'confirmDeleteD5' => 'deleteD5',
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
        $base = FiveNote::query()->with(["note", "company", "WorkReport:id,selected_final_scopes", "productions:id,note_id", "productions.WorkReportFlowProductions:id,work_report_id,production_id,stage,final_scope,is_current"]);

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

    public function requestDelete(int $id): void
    {
        $this->deleteId = $id;

        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Remover D5',
            'msg'           => 'Tem certeza que deseja remover esta D5? Evidencias, eventos e vinculos com producoes serao removidos/desassociados.',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, remover',
            'btnCanceltxt'  => 'Nao, cancelar',
            'action'        => 'confirmDeleteD5',
            'cancel_titulo' => 'Cancelado',
            'cancel_msg'    => 'Nenhuma D5 foi removida.',
        ]);
    }

    public function deleteD5(): void
    {
        if (!$this->deleteId) {
            return;
        }

        $five = FiveNote::with(['productions', 'EvidenceFiles', 'timelineEvents', 'Comments'])->find($this->deleteId);

        if (!$five) {
            $this->deleteId = null;
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'D5 nao encontrada',
                'timer'    => 2500,
            ]);

            return;
        }

        try {
            DB::transaction(function () use ($five) {
                foreach ($five->productions as $production) {
                    $production->forceFill([
                        'dfive' => false,
                        'd5'    => false,
                    ])->save();
                }

                $five->productions()->detach();
                $five->EvidenceFiles()->delete();
                $five->timelineEvents()->delete();
                $five->Comments()->delete();
                $five->delete();
            });
        } catch (\Throwable) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Nao foi possivel remover a D5',
                'text'     => 'Verifique dependencias vinculadas e tente novamente.',
            ]);

            return;
        }

        $this->deleteId = null;
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'D5 removida',
            'timer'    => 2000,
        ]);

        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.control.d5-list', [
            'lists' => $this->lists,
            'missing' => $this->missingSearch,
        ]);
    }
}
