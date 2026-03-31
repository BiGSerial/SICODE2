<?php

namespace App\Http\Livewire\Admin\Control;

use App\Enum\AdsRequestStatus;
use App\Models\AdsRequest;
use App\Models\SicodeSql\AdsRequest as SqlAdsRequest;
use Livewire\Component;
use Livewire\WithPagination;

class AdsMonitor extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $queueSearch = '';
    public string $doneSearch = '';
    public int $queuePerPage = 25;
    public int $donePerPage = 25;

    public function updatedQueueSearch(): void
    {
        $this->resetPage('queuePage');
    }

    public function updatedDoneSearch(): void
    {
        $this->resetPage('donePage');
    }

    public function updatedQueuePerPage(): void
    {
        $this->resetPage('queuePage');
    }

    public function updatedDonePerPage(): void
    {
        $this->resetPage('donePage');
    }

    public function getQueueRequestsProperty()
    {
        return AdsRequest::query()
            ->with(['note:id,note', 'company:id,name', 'requestedBy:id,name'])
            ->whereIn('status', [
                AdsRequestStatus::QUEUED->value,
                AdsRequestStatus::IN_PROGRESS->value,
                AdsRequestStatus::RETRY->value,
            ])
            ->when($this->queueSearch !== '', function ($q) {
                $search = trim($this->queueSearch);
                $q->whereHas('note', function ($sub) use ($search) {
                    $sub->where('note', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate($this->queuePerPage, ['*'], 'queuePage');
    }

    public function getDoneRequestsProperty()
    {
        return AdsRequest::query()
            ->with(['note:id,note', 'company:id,name', 'requestedBy:id,name'])
            ->where('status', AdsRequestStatus::DONE->value)
            ->when($this->doneSearch !== '', function ($q) {
                $search = trim($this->doneSearch);
                $q->whereHas('note', function ($sub) use ($search) {
                    $sub->where('note', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('completed_at')
            ->orderByDesc('updated_at')
            ->paginate($this->donePerPage, ['*'], 'donePage');
    }

    public function render()
    {
        $queueRequests = $this->queueRequests;
        $doneRequests = $this->doneRequests;

        $allIds = collect($queueRequests->items())
            ->pluck('id')
            ->merge(collect($doneRequests->items())->pluck('id'))
            ->filter()
            ->unique()
            ->values();

        $sqlStatusBySicodeId = $this->loadSqlStatusBySicodeIds($allIds);

        return view('livewire.admin.control.ads-monitor', [
            'queueRequests' => $queueRequests,
            'doneRequests' => $doneRequests,
            'sqlStatusBySicodeId' => $sqlStatusBySicodeId,
            'queueCount' => AdsRequest::query()->whereIn('status', [
                AdsRequestStatus::QUEUED->value,
                AdsRequestStatus::IN_PROGRESS->value,
                AdsRequestStatus::RETRY->value,
            ])->count(),
            'doneCount' => AdsRequest::query()->where('status', AdsRequestStatus::DONE->value)->count(),
            'lastUpdateAt' => now(),
        ]);
    }

    protected function loadSqlStatusBySicodeIds($ids)
    {
        $ids = collect($ids)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $rows = collect();
        foreach ($ids->chunk(1800) as $chunk) {
            $rows = $rows->merge(
                SqlAdsRequest::query()
                    ->whereIn('sicode_id', $chunk->all())
                    ->get(['id', 'sicode_id', 'status', 'url', 'completed_at', 'updated_at'])
            );
        }

        return $rows->keyBy('sicode_id');
    }
}
