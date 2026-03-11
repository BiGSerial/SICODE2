<?php

namespace App\Http\Livewire\Reports;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CancellationList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $dateFrom = '';
    public string $dateTo = '';
    public string $status = '';
    public string $scope = '';
    public string $categoryId = '';
    public string $search = '';

    protected $queryString = [
        'dateFrom' => ['except' => '', 'as' => 'de'],
        'dateTo' => ['except' => '', 'as' => 'ate'],
        'status' => ['except' => '', 'as' => 'sts'],
        'scope' => ['except' => '', 'as' => 'tipo'],
        'categoryId' => ['except' => '', 'as' => 'cat'],
        'search' => ['except' => '', 'as' => 'q'],
    ];

    public function mount(): void
    {
        if ($this->dateFrom === '' || $this->dateTo === '') {
            $this->dateTo = now()->toDateString();
            $this->dateFrom = now()->subDays(29)->toDateString();
        }
    }

    public function updating($name): void
    {
        if (in_array($name, ['dateFrom', 'dateTo', 'status', 'scope', 'categoryId', 'search'], true)) {
            $this->resetPage();
        }
    }

    private function parseTokens(): array
    {
        return collect(preg_split('/[\s,;\n\r]+/', $this->search))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function baseQuery()
    {
        $tokens = $this->parseTokens();

        return DB::table('cancellation_requests as cr')
            ->leftJoin('notes as n', 'n.id', '=', 'cr.note_id')
            ->leftJoin('users as requester', 'requester.id', '=', 'cr.requested_by')
            ->leftJoin('users as assignee', 'assignee.id', '=', 'cr.assigned_to')
            ->leftJoin('users as engineer', 'engineer.id', '=', 'cr.engineer_approver_id')
            ->leftJoin('cancellation_categories as cc', 'cc.id', '=', 'cr.category_id')
            ->whereBetween(DB::raw('DATE(COALESCE(cr.submitted_at, cr.created_at))'), [$this->dateFrom, $this->dateTo])
            ->when($this->status !== '', fn ($q) => $q->where('cr.status', $this->status))
            ->when($this->scope !== '', fn ($q) => $q->where('cr.scope', $this->scope))
            ->when($this->categoryId !== '', fn ($q) => $q->where('cr.category_id', (int) $this->categoryId))
            ->when(count($tokens), function ($q) use ($tokens) {
                $q->where(function ($sub) use ($tokens) {
                    $sub->whereIn('n.note', $tokens)
                        ->orWhereIn('cr.id', collect($tokens)->filter(fn ($v) => ctype_digit((string) $v))->values()->all());
                });
            });
    }

    private function secondsToHuman(?int $seconds): string
    {
        if (!$seconds || $seconds <= 0) {
            return '-';
        }

        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . 'min';
        }

        return empty($parts) ? '< 1min' : implode(' ', $parts);
    }

    public function render()
    {
        $rows = $this->baseQuery()
            ->selectRaw('
                cr.id,
                n.note as note_number,
                cc.name as category_name,
                cr.scope,
                cr.status,
                requester.name as requester_name,
                assignee.name as assignee_name,
                engineer.name as engineer_name,
                COALESCE(cr.submitted_at, cr.created_at) as opened_at,
                cr.assigned_at,
                cr.engineer_approval_requested_at,
                cr.engineer_approval_decided_at,
                cr.closed_at,
                TIMESTAMPDIFF(SECOND, cr.assigned_at, cr.closed_at) as exec_seconds,
                TIMESTAMPDIFF(SECOND, cr.engineer_approval_requested_at, cr.engineer_approval_decided_at) as eng_seconds,
                TIMESTAMPDIFF(SECOND, cr.submitted_at, cr.closed_at) as close_seconds,
                TIMESTAMPDIFF(SECOND, cr.engineer_approval_decided_at, cr.closed_at) as final_seconds
            ')
            ->orderByDesc('opened_at')
            ->paginate(25);

        $rows->getCollection()->transform(function ($item) {
            $item->exec_human = $this->secondsToHuman(isset($item->exec_seconds) ? (int) $item->exec_seconds : null);
            $item->eng_human = $this->secondsToHuman(isset($item->eng_seconds) ? (int) $item->eng_seconds : null);
            $item->close_human = $this->secondsToHuman(isset($item->close_seconds) ? (int) $item->close_seconds : null);
            $item->final_human = $this->secondsToHuman(isset($item->final_seconds) ? (int) $item->final_seconds : null);
            return $item;
        });

        $categories = DB::table('cancellation_categories')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('livewire.reports.cancellation-list', [
            'rows' => $rows,
            'categories' => $categories,
        ]);
    }
}
