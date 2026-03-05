<?php

namespace App\Http\Livewire\Admin\Control;

use App\Helpers\TextFormatter;
use App\Models\WorkReport;
use App\Traits\WildcardFormmater;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class WorkReportList extends Component
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
    public $missingSearch = [];

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
        'confirmDeleteWorkReport' => 'deleteWorkReport',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();

        if (!$this->search) {
            $this->multiSearch = [];
            $this->advanceSearch = '';
            $this->missingSearch = [];
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
        $this->advanceSearch = '';
        $this->missingSearch = [];
        $this->resetPage();
    }

    private function baseQuery(): Builder
    {
        $base = WorkReport::query()->with(['Note', 'Company', 'User', 'Orders']);

        if ($this->search) {
            $search = $this->formatWithWildcard($this->search);
            $base->where(function ($query) use ($search) {
                $query->where('id', $search->type, $search->search)
                    ->orWhere('dd', $search->type, $search->search)
                    ->orWhere('informer', $search->type, $search->search)
                    ->orWhere('responsible', $search->type, $search->search)
                    ->orWhere('team', $search->type, $search->search)
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
                    ->orWhereIn('dd', $values)
                    ->orWhereIn('informer', $values)
                    ->orWhereIn('responsible', $values)
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

    protected function computeMissing(array $values, Builder $base): array
    {
        $values = array_values(array_unique(array_filter($values, fn ($v) => $v !== '' && $v !== null)));

        if (empty($values)) {
            return [];
        }

        $matches = (clone $base)->get();

        $found = $matches->flatMap(function ($item) {
            return array_filter([
                (string) $item->id,
                (string) ($item->dd ?? ''),
                (string) ($item->informer ?? ''),
                (string) ($item->responsible ?? ''),
                (string) ($item->Note->note ?? ''),
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

    public function requestDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->dispatchBrowserEvent('alertar', [
            'title'         => 'Remover Informe de Obra',
            'msg'           => 'Tem certeza que deseja remover este WorkReport? Esta acao nao podera ser desfeita.',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, Remova!',
            'btnCanceltxt'  => 'Nao, Cancele',
            'action'        => 'confirmDeleteWorkReport',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nenhum informe foi removido.',
        ]);
    }

    public function deleteWorkReport(): void
    {
        if (!$this->deleteId) {
            return;
        }

        $workReport = WorkReport::find($this->deleteId);
        if (!$workReport) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'WorkReport nao encontrado',
                'timer'    => 2500,
            ]);
            $this->deleteId = null;
            return;
        }

        try {
            DB::transaction(function () use ($workReport) {
                $workReport->Orders()->detach();
                $workReport->Equipment()->delete();
                $workReport->Meeters()->delete();
                $workReport->Returnwork()->delete();

                $ads = $workReport->Adsform()->first();
                if ($ads) {
                    $ads->Files()->detach();
                    $ads->delete();
                }

                $workReport->delete();
            });
        } catch (\Throwable $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Nao foi possivel remover',
                'text'     => 'O WorkReport nao foi removido. Verifique dependencias vinculadas e tente novamente.',
            ]);
            return;
        }

        $this->deleteId = null;
        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Informe removido',
            'timer'    => 2000,
        ]);

        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.control.work-report-list', [
            'lists' => $this->lists,
            'missing' => $this->missingSearch,
        ]);
    }
}
