<?php

namespace App\Http\Livewire\Reports;

use App\Custom\Notestatus;
use App\Http\Livewire\Reports\Concerns\LoadsReturnInternDetails;
use App\Models\{Reclaim, Service, User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\{Component, WithPagination};

class OpenReturnInterns extends Component
{
    use WithPagination;
    use LoadsReturnInternDetails;

    protected $paginationTheme = 'bootstrap';

    public $search = '';

    public $origin = '';

    public $serviceId = '';

    public $productionState = '';

    public $productionUserId = '';

    public $age = '';

    public $perPage = 25;

    public $serviceOptions = [];

    public $productionUserOptions = [];

    protected $queryString = [
        'search'           => ['except' => '', 'as' => 'busca'],
        'origin'           => ['except' => '', 'as' => 'origem'],
        'serviceId'        => ['except' => '', 'as' => 'servico'],
        'productionState'  => ['except' => '', 'as' => 'producao'],
        'productionUserId' => ['except' => '', 'as' => 'responsavel'],
        'age'              => ['except' => '', 'as' => 'tempo'],
        'perPage'          => ['except' => 25, 'as' => 'por_pagina'],
        'page'             => ['except' => 1],
    ];

    public function mount(): void
    {
        $activeServiceIds = Reclaim::query()
            ->active()
            ->whereNotNull('service_id')
            ->distinct()
            ->pluck('service_id');

        $this->serviceOptions = Service::query()
            ->whereIn('uuid', $activeServiceIds)
            ->orderBy('service')
            ->get(['uuid', 'service']);

        $activeProductionUserIds = Reclaim::query()
            ->active()
            ->join('productions as p', 'p.id', '=', 'reclaims.production_id')
            ->whereNotNull('p.user_id')
            ->distinct()
            ->pluck('p.user_id');

        $this->productionUserOptions = User::query()
            ->whereIn('id', $activeProductionUserIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, [
            'search',
            'origin',
            'serviceId',
            'productionState',
            'productionUserId',
            'age',
            'perPage',
        ], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'origin',
            'serviceId',
            'productionState',
            'productionUserId',
            'age',
        ]);

        $this->resetPage();
    }

    protected function openReclaimsQuery(): Builder
    {
        return Reclaim::query()
            ->active()
            ->when(trim($this->search) !== '', function (Builder $query) {
                $search = trim($this->search);

                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->whereHas('Note', fn (Builder $note) => $note->where('note', 'like', "%{$search}%"))
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('Production.User', fn (Builder $user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('Production.Company', fn (Builder $company) => $company->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($this->serviceId !== '', fn (Builder $query) => $query->where('service_id', $this->serviceId))
            ->when($this->productionUserId !== '', function (Builder $query) {
                $query->whereHas('Production', fn (Builder $production) => $production->where('user_id', $this->productionUserId));
            })
            ->when($this->productionState === 'none', fn (Builder $query) => $query->whereNull('production_id'))
            ->when($this->productionState === 'unassigned', function (Builder $query) {
                $query->whereNotNull('production_id')
                    ->whereHas('Production', fn (Builder $production) => $production->whereNull('user_id'));
            })
            ->when($this->productionState === 'assigned', function (Builder $query) {
                $query->whereHas('Production', fn (Builder $production) => $production->whereNotNull('user_id'));
            })
            ->when($this->age === 'today', fn (Builder $query) => $query->where('reclaims.created_at', '>=', now()->startOfDay()))
            ->when($this->age === '1-3', function (Builder $query) {
                $query->whereBetween('reclaims.created_at', [
                    now()->subDays(3)->startOfDay(),
                    now()->subDay()->endOfDay(),
                ]);
            })
            ->when($this->age === '4-7', function (Builder $query) {
                $query->whereBetween('reclaims.created_at', [
                    now()->subDays(7)->startOfDay(),
                    now()->subDays(4)->endOfDay(),
                ]);
            })
            ->when($this->age === '8-15', function (Builder $query) {
                $query->whereBetween('reclaims.created_at', [
                    now()->subDays(15)->startOfDay(),
                    now()->subDays(8)->endOfDay(),
                ]);
            })
            ->when($this->age === '16+', fn (Builder $query) => $query->where('reclaims.created_at', '<', now()->subDays(15)->startOfDay()))
            ->when($this->origin !== '', function (Builder $query) {
                match ($this->origin) {
                    'viability' => $query->whereHas('Viabilities'),
                    'waiting'   => $query->whereHas('Waiting'),
                    'approval'  => $query->whereHas('Approvals'),
                    'external'  => $query->whereHas('Externals'),
                    'unknown'   => $query
                        ->whereDoesntHave('Viabilities')
                        ->whereDoesntHave('Waiting')
                        ->whereDoesntHave('Approvals')
                        ->whereDoesntHave('Externals'),
                    default => null,
                };
            });
    }

    protected function summary(): array
    {
        $query = $this->openReclaimsQuery();

        $oldest = (clone $query)->min('reclaims.created_at');

        return [
            'total'              => (clone $query)->count(),
            'without_production' => (clone $query)->whereNull('production_id')->count(),
            'unassigned'         => (clone $query)
                ->whereNotNull('production_id')
                ->whereHas('Production', fn (Builder $production) => $production->whereNull('user_id'))
                ->count(),
            'assigned' => (clone $query)
                ->whereHas('Production', fn (Builder $production) => $production->whereNotNull('user_id'))
                ->count(),
            'overdue' => (clone $query)->where('reclaims.created_at', '<', now()->subDays(7))->count(),
            'oldest'  => $oldest ? $this->humanDuration($oldest) : '—',
        ];
    }

    public function originMeta(Reclaim $reclaim): array
    {
        if ($reclaim->Viabilities->isNotEmpty()) {
            return [
                'label'     => 'Viabilidade',
                'reference' => $reclaim->Viabilities->first()->id,
                'icon'      => 'ri-route-line',
                'class'     => 'text-bg-primary',
            ];
        }

        if ($reclaim->Waiting) {
            return [
                'label'     => 'Contratação',
                'reference' => $reclaim->Waiting->id,
                'icon'      => 'ri-hand-coin-line',
                'class'     => 'text-bg-info',
            ];
        }

        if ($reclaim->Approvals->isNotEmpty()) {
            return [
                'label'     => 'Aprovação',
                'reference' => $reclaim->Approvals->first()->id,
                'icon'      => 'ri-checkbox-circle-line',
                'class'     => 'text-bg-warning',
            ];
        }

        if ($reclaim->Externals->isNotEmpty()) {
            return [
                'label'     => 'Órgão externo',
                'reference' => $reclaim->Externals->first()->id,
                'icon'      => 'ri-government-line',
                'class'     => 'text-bg-dark',
            ];
        }

        return [
            'label'     => 'Não identificada',
            'reference' => null,
            'icon'      => 'ri-question-line',
            'class'     => 'text-bg-secondary',
        ];
    }

    public function humanDuration($date): string
    {
        if (!$date) {
            return '—';
        }

        $start   = $date instanceof Carbon ? $date : Carbon::parse($date);
        $minutes = max(0, $start->diffInMinutes(now()));
        $days    = intdiv($minutes, 1440);
        $hours   = intdiv($minutes % 1440, 60);

        if ($days > 0) {
            return sprintf('%dd %02dh', $days, $hours);
        }

        if ($hours > 0) {
            return sprintf('%dh %02dmin', $hours, $minutes % 60);
        }

        return sprintf('%dmin', $minutes);
    }

    public function productionStatus($status): object
    {
        return Notestatus::status($status);
    }

    public function showReason(int $reclaimId): void
    {
        $this->loadReturnInternDetails($reclaimId, true);
    }

    public function render()
    {
        $reclaims = $this->openReclaimsQuery()
            ->with([
                'Note:id,note',
                'Service:uuid,service',
                'Production:id,user_id,company_id,status,att_at,dispatch_at,completed_at,created_at',
                'Production.User:id,name',
                'Production.Company:id,name',
                'Viabilities:id',
                'Waiting:id,reclaim_id',
                'Approvals:id',
                'Externals:id',
            ])
            ->orderBy('reclaims.created_at')
            ->paginate($this->perPage);

        return view('livewire.reports.open-return-interns', [
            'reclaims' => $reclaims,
            'summary'  => $this->summary(),
        ]);
    }
}
