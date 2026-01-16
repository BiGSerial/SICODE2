<?php

namespace App\Http\Livewire\Reports\Concerns;

use App\Custom\Notestatus;
use App\Models\Company;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

trait ReturnInternFilters
{
    public $dt_in;
    public $dt_out;
    public $search = '';
    public array $originFilters = [];
    public array $serviceIds = [];
    public $category = '';
    public $dispatcherUserId = '';
    public $productionUserId = '';
    public $companyId = '';
    public $productionStatus = '';
    public $completedFilter = '';
    public $resolutionMin = '';
    public $resolutionMax = '';

    public array $originOptions = [
        ['value' => 'viability', 'label' => 'Viabilidade'],
        ['value' => 'waiting', 'label' => 'Contratacao'],
        ['value' => 'approval', 'label' => 'Aprovacao'],
        ['value' => 'external', 'label' => 'Orgao Externo'],
        ['value' => 'unknown', 'label' => 'Sem Origem'],
    ];

    public $serviceOptions = [];
    public $dispatcherOptions = [];
    public $productionUserOptions = [];
    public $companyOptions = [];
    public $statusOptions = [];

    public function mountReturnInternFilters(): void
    {
        $this->dt_in = $this->dt_in ?: now()->startOfMonth()->format('Y-m-d');
        $this->dt_out = $this->dt_out ?: now()->format('Y-m-d');

        if (Carbon::parse($this->dt_out)->greaterThan(now())) {
            $this->dt_out = now()->format('Y-m-d');
        }

        $this->serviceOptions = Service::query()
            ->orderBy('service')
            ->get(['uuid', 'service']);

        $dispatcherIds = DB::table('comment_reclaim as cr')
            ->join('comments as c', 'c.id', '=', 'cr.comment_id')
            ->whereNotNull('c.user_id')
            ->distinct()
            ->pluck('c.user_id')
            ->toArray();

        $productionIds = DB::table('reclaims as r')
            ->join('productions as p', 'p.id', '=', 'r.production_id')
            ->whereNotNull('p.user_id')
            ->distinct()
            ->pluck('p.user_id')
            ->toArray();

        $this->dispatcherOptions = User::query()
            ->whereIn('id', $dispatcherIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->productionUserOptions = User::query()
            ->whereIn('id', $productionIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->companyOptions = Company::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $statusOptions = [];
        for ($i = 0; $i <= 29; $i++) {
            $status = Notestatus::status($i);
            $statusOptions[] = [
                'value' => $i,
                'label' => $status->status ?? (string) $i,
            ];
        }
        $this->statusOptions = $statusOptions;
    }

    protected function getReturnInternDateRange(): array
    {
        $start = $this->dt_in ? Carbon::parse($this->dt_in)->startOfDay() : now()->startOfMonth();
        $end = $this->dt_out ? Carbon::parse($this->dt_out)->endOfDay() : now()->endOfDay();

        if ($end->greaterThan(now())) {
            $end = now()->endOfDay();
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    protected function baseReclaimQuery()
    {
        [$start, $end] = $this->getReturnInternDateRange();

        $query = \App\Models\Reclaim::query()
            ->whereBetween('reclaims.created_at', [$start, $end]);

        if ($this->search) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('Note', fn ($n) => $n->where('note', 'like', '%' . $search . '%'))
                    ->orWhere('category', 'like', '%' . $search . '%');
            });
        }

        if (!empty($this->serviceIds)) {
            $query->whereIn('service_id', $this->serviceIds);
        }

        if ($this->category) {
            $query->where('category', 'like', '%' . trim($this->category) . '%');
        }

        if ($this->dispatcherUserId) {
            $firstCommentSub = DB::table('comment_reclaim as cr')
                ->join('comments as c', 'c.id', '=', 'cr.comment_id')
                ->selectRaw('cr.reclaim_id')
                ->where('c.user_id', $this->dispatcherUserId)
                ->whereRaw(
                    'c.id = (SELECT c2.id FROM comment_reclaim cr2 JOIN comments c2 ON c2.id = cr2.comment_id WHERE cr2.reclaim_id = cr.reclaim_id ORDER BY c2.created_at ASC, c2.id ASC LIMIT 1)'
                );

            $query->whereIn('reclaims.id', $firstCommentSub);
        }

        if ($this->productionUserId) {
            $query->whereHas('Production', fn ($q) => $q->where('user_id', $this->productionUserId));
        }

        if ($this->companyId) {
            $query->whereHas('Production', fn ($q) => $q->where('company_id', $this->companyId));
        }

        if ($this->productionStatus !== '') {
            $query->whereHas('Production', fn ($q) => $q->where('status', $this->productionStatus));
        }

        if ($this->completedFilter === 'open') {
            $query->where('completed', false);
        }
        if ($this->completedFilter === 'closed') {
            $query->where('completed', true);
        }

        if (!empty($this->originFilters)) {
            $origins = $this->originFilters;
            $query->where(function ($q) use ($origins) {
                if (in_array('viability', $origins, true)) {
                    $q->orWhereHas('Viabilities');
                }
                if (in_array('waiting', $origins, true)) {
                    $q->orWhereHas('Waiting');
                }
                if (in_array('approval', $origins, true)) {
                    $q->orWhereHas('Approvals');
                }
                if (in_array('external', $origins, true)) {
                    $q->orWhereHas('Externals');
                }
                if (in_array('unknown', $origins, true)) {
                    $q->orWhere(function ($sub) {
                        $sub->whereDoesntHave('Viabilities')
                            ->whereDoesntHave('Waiting')
                            ->whereDoesntHave('Approvals')
                            ->whereDoesntHave('Externals');
                    });
                }
            });
        }

        if ($this->resolutionMin !== '' || $this->resolutionMax !== '') {
            $query->whereNotNull('completed_at');

            if ($this->resolutionMin !== '') {
                $query->whereRaw(
                    'TIMESTAMPDIFF(DAY, reclaims.created_at, reclaims.completed_at) >= ?',
                    [(int) $this->resolutionMin]
                );
            }

            if ($this->resolutionMax !== '') {
                $query->whereRaw(
                    'TIMESTAMPDIFF(DAY, reclaims.created_at, reclaims.completed_at) <= ?',
                    [(int) $this->resolutionMax]
                );
            }
        }

        return $query;
    }

    protected function baseIdSubquery()
    {
        return $this->baseReclaimQuery()->select('reclaims.id');
    }

    protected function secondsToHuman(?int $seconds): string
    {
        $seconds = (int) $seconds;

        if ($seconds <= 0) {
            return '0 min';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return sprintf('%dh %02dmin', $hours, $minutes);
        }

        return sprintf('%d min', $minutes);
    }
}
