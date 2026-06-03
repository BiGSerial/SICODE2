<?php

namespace App\Http\Livewire\Legal\Controller;

use App\Models\Legal\LegalDemandSubdemand;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class SubdemandMonitor extends Component
{
    use WithPagination;

    public string $scope = 'open';
    public string $status = '';
    public string $area = '';
    public string $responsible = '';
    public string $type = '';
    public string $process = '';
    public int $perPage = 20;

    protected $queryString = [
        'scope' => ['except' => 'open'],
        'status' => ['except' => ''],
        'area' => ['except' => ''],
        'responsible' => ['except' => ''],
        'type' => ['except' => ''],
        'process' => ['except' => ''],
    ];

    public function updating($name, $value): void
    {
        if (in_array($name, ['scope', 'status', 'area', 'responsible', 'type', 'process'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $closedStatuses = ['concluida', 'encerrada_controlador'];

        $baseQuery = LegalDemandSubdemand::query()
            ->with(['assignedTo', 'demand.legalCase.adverseParties'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->area !== '', fn ($q) => $q->where('assigned_area_name', 'like', '%' . trim($this->area) . '%'))
            ->when($this->responsible !== '', fn ($q) => $q->where('assigned_to_user_id', $this->responsible))
            ->when($this->type !== '', fn ($q) => $q->whereHas('demand', fn ($d) => $d->where('source_type', $this->type)))
            ->when($this->process !== '', fn ($q) => $q->whereHas('demand', function ($d) {
                $term = trim($this->process);
                $like = '%' . $term . '%';
                $d->where('source_case_number', 'like', $like)
                    ->orWhereHas('legalCase.adverseParties', fn ($partyQuery) => $partyQuery->where('name', 'like', $like));
            }));

        $kpis = [
            'open' => (clone $baseQuery)->whereNotIn('status', $closedStatuses)->count(),
            'overdue' => (clone $baseQuery)->whereNotIn('status', $closedStatuses)->whereNotNull('deadline_at')->where('deadline_at', '<', now())->count(),
            'today' => (clone $baseQuery)->whereNotIn('status', $closedStatuses)->whereDate('deadline_at', now()->toDateString())->count(),
            'all' => (clone $baseQuery)->count(),
        ];

        $query = clone $baseQuery;

        if ($this->scope === 'open') {
            $query->whereNotIn('status', $closedStatuses);
        } elseif ($this->scope === 'overdue') {
            $query->whereNotIn('status', $closedStatuses)->whereNotNull('deadline_at')->where('deadline_at', '<', now());
        } elseif ($this->scope === 'today') {
            $query->whereNotIn('status', $closedStatuses)->whereDate('deadline_at', now()->toDateString());
        }

        $subdemands = $query
            ->orderByRaw("CASE WHEN status IN ('concluida','encerrada_controlador') THEN 1 ELSE 0 END ASC")
            ->orderByRaw("CASE WHEN deadline_at IS NULL THEN 1 ELSE 0 END ASC")
            ->orderBy('deadline_at')
            ->paginate($this->perPage);

        return view('livewire.legal.controller.subdemand-monitor', [
            'subdemands' => $subdemands,
            'responsibles' => User::query()->orderBy('name')->get(['id', 'name']),
            'kpis' => $kpis,
        ]);
    }
}
