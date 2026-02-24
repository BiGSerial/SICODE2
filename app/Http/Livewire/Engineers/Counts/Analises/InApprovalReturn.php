<?php

namespace App\Http\Livewire\Engineers\Counts\Analises;

use App\Models\Reclaim;
use App\Models\ViabilityApproval;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class InApprovalReturn extends Component
{
    public $engineer;

    public function mount($engineer = false)
    {
        $this->engineer = $engineer;
    }

    public function getCountProperty()
    {
        $query = ViabilityApproval::query();

        if (!$this->engineer) {
            $query->whereIn('user_id', auth()->user()->visibleUserIdsForWork());
        }

        $query->where('approved', false);

        // Subconsulta para obter o ID do reclaim mais recente para cada ViabilityApproval
        $latestReclaimIds = Reclaim::selectRaw('MAX(reclaims.id)')
            ->join('viability_approval_reclaim', 'reclaims.id', '=', 'viability_approval_reclaim.reclaim_id')
            ->whereColumn('viability_approval_reclaim.viability_approval_id', 'viability_approvals.id')
            ->groupBy('viability_approval_reclaim.viability_approval_id');

        $query->join('viability_approval_reclaim', 'viability_approvals.id', '=', 'viability_approval_reclaim.viability_approval_id')
              ->join('reclaims', function ($join) use ($latestReclaimIds) {
                  $join->on('reclaims.id', '=', DB::raw('(' . $latestReclaimIds->toSql() . ')'))
                       ->where('reclaims.completed', true);
                  $join->addBinding($latestReclaimIds->getBindings());
              });

        return $query->count();
    }

    public function render()
    {
        return view('livewire.engineers.counts.analises.in-approval-return', [
            'count' => $this->count
        ]);
    }
}
