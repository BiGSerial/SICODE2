<?php

namespace App\Http\Livewire\Reports;

use App\Http\Livewire\Reports\Concerns\ReturnInternFilters;
use App\Jobs\Reports\ExportInternalReclaimsJob;
use Livewire\Component;
use Livewire\WithPagination;

class ReturnInternList extends Component
{
    use WithPagination;
    use ReturnInternFilters;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 25;

    protected $queryString = [
        'dt_in' => ['except' => '', 'as' => 'de'],
        'dt_out' => ['except' => '', 'as' => 'ate'],
        'perPage' => ['except' => 25, 'as' => 'pp'],
        'search' => ['except' => '', 'as' => 'busca'],
        'originFilters' => ['except' => [], 'as' => 'origem'],
        'serviceIds' => ['except' => [], 'as' => 'srv'],
        'category' => ['except' => '', 'as' => 'cat'],
        'dispatcherUserId' => ['except' => '', 'as' => 'disp'],
        'productionUserId' => ['except' => '', 'as' => 'prod'],
        'companyId' => ['except' => '', 'as' => 'emp'],
        'productionStatus' => ['except' => '', 'as' => 'sts'],
        'completedFilter' => ['except' => '', 'as' => 'cmp'],
        'resolutionMin' => ['except' => '', 'as' => 'rmin'],
        'resolutionMax' => ['except' => '', 'as' => 'rmax'],
        'page' => ['except' => 1],
    ];

    public function updated($propertyName)
    {
        $paginationSensitive = [
            'dt_in',
            'dt_out',
            'perPage',
            'search',
            'originFilters',
            'serviceIds',
            'category',
            'dispatcherUserId',
            'productionUserId',
            'companyId',
            'productionStatus',
            'completedFilter',
            'resolutionMin',
            'resolutionMax',
        ];

        $isOriginNested = str_starts_with($propertyName, 'originFilters.');
        $isServiceNested = str_starts_with($propertyName, 'serviceIds.');

        if ($isOriginNested || $isServiceNested || in_array($propertyName, $paginationSensitive, true)) {
            $this->resetPage();
        }
    }

    public function exportReport(): void
    {
        $params = [
            'dt_in' => $this->dt_in,
            'dt_out' => $this->dt_out,
            'search' => $this->search,
            'originFilters' => $this->originFilters,
            'serviceIds' => $this->serviceIds,
            'category' => $this->category,
            'dispatcherUserId' => $this->dispatcherUserId,
            'productionUserId' => $this->productionUserId,
            'companyId' => $this->companyId,
            'productionStatus' => $this->productionStatus,
            'completedFilter' => $this->completedFilter,
            'resolutionMin' => $this->resolutionMin,
            'resolutionMax' => $this->resolutionMax,
        ];

        ExportInternalReclaimsJob::dispatch($params, (string) auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon' => 'success',
            'title' => 'EXPORTACAO EM ANDAMENTO',
            'html' => "<div class='card'><div class='card-body'><p>Seu arquivo esta sendo gerado.</p><p class='fw-bold'>Voce sera notificado quando estiver pronto.</p></div></div>",
            'timer' => 5000,
        ]);
    }

    protected function listQuery()
    {
        return $this->baseReclaimQuery()
            ->with([
                'Note:id,note',
                'Service:uuid,service',
                'Comments' => fn ($q) => $q->orderBy('comments.created_at'),
                'Comments.User:id,name',
                'Production.User:id,name',
                'Production.Company:id,name',
                'Viabilities',
                'Waiting',
                'Approvals',
                'Externals',
            ])
            ->orderByDesc('reclaims.created_at');
    }

    public function render()
    {
        return view('livewire.reports.return-intern-list', [
            'lists' => $this->listQuery()->paginate($this->perPage),
        ]);
    }
}
