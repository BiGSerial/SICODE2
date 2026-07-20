<?php

namespace App\Http\Livewire\Services\Oexterno;

use App\Helpers\TextFormatter;
use App\Jobs\Services\ExportExternalOrganReleasedWorksJob;
use App\Models\ExternalOrganRelease;
use App\Models\Service;
use Livewire\Component;
use Livewire\WithPagination;

class ReleasedWorks extends Component
{
    use TextFormatter;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;
    public $search = '';
    public $advanceSearch = '';
    public $multisearch = [];
    public $tab = 'new';
    public $perPage = 100;

    protected $queryString = [
        'tab' => ['except' => 'new'],
        'search' => ['except' => '', 'as' => 'buscar'],
        'page' => ['except' => 1, 'as' => 'p'],
    ];

    public function mount($service): void
    {
        $this->service = Service::query()
            ->select(['id', 'uuid', 'service'])
            ->where('uuid', $service)
            ->with('Status')
            ->first();
    }

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->multisearch = [];
        $this->resetPage();
    }

    public function buscarMulti(): void
    {
        if ($this->advanceSearch) {
            $this->multisearch = $this->formatTextToArray($this->advanceSearch);
            $this->search = '';
            $this->advanceSearch = '';
            $this->resetPage();
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function cleanAll(): void
    {
        $this->search = '';
        $this->advanceSearch = '';
        $this->multisearch = [];
        $this->resetPage();
    }

    public function exportToExcel(): void
    {
        $ids = $this->baseQuery()
            ->whereNull('released_at')
            ->whereHas('note', function ($q) {
                $q->where('type_note', 2)
                    ->whereIn('nstats', ExternalOrganRelease::TRACKED_STATUSES);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!count($ids)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhuma obra disponível para exportar.',
                'timer' => 2800,
            ]);

            return;
        }

        ExportExternalOrganReleasedWorksJob::dispatch($ids, (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'Exportação iniciada',
            'html' => 'Você receberá uma notificação quando o arquivo estiver pronto.',
            'timer' => 3000,
        ]);
    }

    public function getReleasesProperty()
    {
        return $this->baseQuery()
            ->orderByRaw('exported_at IS NOT NULL')
            ->orderBy('created_at')
            ->paginate($this->perPage);
    }

    private function baseQuery()
    {
        $query = ExternalOrganRelease::query()
            ->with([
                'note:id,note,client,lexp,rubrica,nstats,dt_status,dt_created,numPedido,type_note,centerjob',
                'production:id,note_id,user_id,company_id,service_id,completed_at,status',
                'production.User:id,name',
                'production.Company:id,name',
                'production.Service:uuid,service',
                'exportedBy:id,name',
            ]);

        if ($this->tab === 'new') {
            $query->whereNull('released_at')
                ->whereNull('exported_at')
                ->whereHas('note', function ($q) {
                    $q->where('type_note', 2)
                        ->whereIn('nstats', ExternalOrganRelease::TRACKED_STATUSES);
                });
        } elseif ($this->tab === 'exported') {
            $query->whereNull('released_at')
                ->whereNotNull('exported_at')
                ->whereHas('note', function ($q) {
                    $q->where('type_note', 2);
                });
        } elseif ($this->tab === 'released') {
            $query->whereNotNull('released_at')
                ->whereHas('note', function ($q) {
                    $q->where('type_note', 2);
                });
        } else {
            $query->whereNull('released_at')
                ->whereHas('note', function ($q) {
                    $q->where('type_note', 2);
                });
        }

        if (filled(trim((string) $this->search))) {
            $term = '%' . trim((string) $this->search) . '%';
            $query->whereHas('note', function ($q) use ($term) {
                $q->where('note', 'like', $term)
                    ->orWhere('numPedido', 'like', $term)
                    ->orWhere('client', 'like', $term)
                    ->orWhere('lexp', 'like', $term);
            });
        }

        if (!empty($this->multisearch)) {
            $query->whereHas('note', function ($q) {
                $q->whereIn('note', $this->multisearch)
                    ->orWhereIn('numPedido', $this->multisearch);
            });
        }

        return $query;
    }

    public function render()
    {
        return view('livewire.services.oexterno.released-works', [
            'releases' => $this->releases,
        ]);
    }
}
