<?php

namespace App\Http\Livewire\Components\Workform;

use App\Models\{CancellationRequest, Note, User, WorkReport};
use Livewire\Component;

class CanceledInfo extends Component
{
    public ?int $workReportId = null;

    public array $items = [];

    protected $listeners = [
        'openCanceledInfo',
    ];

    public function openCanceledInfo(int $workReportId): void
    {
        $this->workReportId = $workReportId;
        $this->items        = $this->loadItems($workReportId);

        $this->dispatchBrowserEvent('showModal', [
            'id' => 'workCanceledInfoModal',
        ]);
    }

    private function loadItems(int $workReportId): array
    {
        $reports = WorkReport::query()
            ->whereKey($workReportId)
            ->where('canceled', true)
            ->with('Orders:id,ordem')
            ->orderBy('canceled_at')
            ->get();

        $users = User::withTrashed()
            ->whereIn('id', $reports->pluck('canceled_by')->filter()->unique())
            ->pluck('name', 'id');

        $requests = CancellationRequest::query()
            ->where('note_id', $reports->first()?->note_id)
            ->where('status', CancellationRequest::STATUS_DONE)
            ->with(['Category:id,name', 'Requester:id,name', 'Assignee:id,name', 'WorkReports:id'])
            ->latest('closed_at')
            ->get();

        return $reports->map(function (WorkReport $report) use ($users, $requests) {
            // Solicitação que citou o informe; senão, a última concluída da nota (escopo nota/ordens).
            $request = $requests->first(fn ($r) => $r->WorkReports->contains('id', $report->id))
                ?? $requests->first();

            return [
                'id'           => $report->id,
                'orders'       => $report->Orders->pluck('ordem')->all(),
                'canceled_at'  => $report->canceled_at?->format('d/m/Y H:i'),
                'canceled_by'  => $users[$report->canceled_by] ?? null,
                'request_id'   => $request?->id,
                'category'     => $request?->Category?->name,
                'reason'       => $request?->description,
                'requested_by' => $request?->Requester?->name,
                'closure_note' => $request?->closure_note,
            ];
        })->all();
    }

    public function render()
    {
        return view('livewire.components.workform.canceled-info', [
            'note' => $this->workReportId ? Note::select('id', 'note')->find(WorkReport::whereKey($this->workReportId)->value('note_id')) : null,
        ]);
    }
}
