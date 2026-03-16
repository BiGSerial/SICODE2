<?php

namespace App\Http\Livewire\Reports;

use App\Enum\CancellationEngineerApprovalStatus;
use App\Enum\CancellationRequestScope;
use App\Enum\CancellationRequestStatus;
use Illuminate\Support\Facades\Auth;
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
    public string $visibilityMode = 'HIERARCHY';
    public array $requesterIds = [];

    protected $queryString = [
        'dateFrom' => ['except' => '', 'as' => 'de'],
        'dateTo' => ['except' => '', 'as' => 'ate'],
        'status' => ['except' => '', 'as' => 'sts'],
        'scope' => ['except' => '', 'as' => 'tipo'],
        'categoryId' => ['except' => '', 'as' => 'cat'],
        'search' => ['except' => '', 'as' => 'q'],
        'visibilityMode' => ['except' => 'HIERARCHY', 'as' => 'vis'],
    ];

    public function mount(): void
    {
        if ($this->dateFrom === '' || $this->dateTo === '') {
            $this->dateTo = now()->toDateString();
            $this->dateFrom = now()->subDays(29)->toDateString();
        }

        if ((Auth::user()?->superadm || Auth::user()?->management) && !request()->has('vis')) {
            $this->visibilityMode = 'ALL';
        }
    }

    public function updating($name): void
    {
        if (in_array($name, ['dateFrom', 'dateTo', 'status', 'scope', 'categoryId', 'search', 'visibilityMode', 'requesterIds'], true)) {
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

    private function visibleRequesterIds(): ?array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        if ($this->visibilityMode === 'ALL') {
            return null;
        }

        if ($this->visibilityMode === 'SUCCESSION') {
            return $user->descendantsQuery(
                includeSelf: true,
                includeDelegations: false,
                includeDelegatesTreesForPrincipal: true
            )->pluck('users.id')->unique()->values()->all();
        }

        return $user->descendantsQuery(
            includeSelf: true,
            includeDelegations: true,
            includeDelegatesTreesForPrincipal: false
        )->pluck('users.id')->unique()->values()->all();
    }

    private function selectedRequesterIds(): array
    {
        return collect($this->requesterIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    private function baseQuery()
    {
        $tokens = $this->parseTokens();
        $visibleRequesterIds = $this->visibleRequesterIds();
        $selectedRequesterIds = $this->selectedRequesterIds();

        return DB::table('cancellation_requests as cr')
            ->leftJoin('notes as n', 'n.id', '=', 'cr.note_id')
            ->leftJoin('users as requester', 'requester.id', '=', 'cr.requested_by')
            ->leftJoin('users as assignee', 'assignee.id', '=', 'cr.assigned_to')
            ->leftJoin('users as engineer', 'engineer.id', '=', 'cr.engineer_approver_id')
            ->leftJoin('cancellation_categories as cc', 'cc.id', '=', 'cr.category_id')
            ->whereBetween(DB::raw('DATE(COALESCE(cr.submitted_at, cr.created_at))'), [$this->dateFrom, $this->dateTo])
            ->when($visibleRequesterIds !== null, fn ($q) => $q->whereIn('cr.requested_by', $visibleRequesterIds))
            ->when(count($selectedRequesterIds), fn ($q) => $q->whereIn('cr.requested_by', $selectedRequesterIds))
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

    private function statusOptions(): array
    {
        return collect(CancellationRequestStatus::cases())
            ->map(fn (CancellationRequestStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();
    }

    private function scopeOptions(): array
    {
        return collect(CancellationRequestScope::cases())
            ->map(fn (CancellationRequestScope $scope) => [
                'value' => $scope->value,
                'label' => $scope->label(),
            ])
            ->values()
            ->all();
    }

    private function visibilityOptions(): array
    {
        return [
            ['value' => 'ALL', 'label' => 'Tudo'],
            ['value' => 'HIERARCHY', 'label' => 'Minha hierarquia'],
            ['value' => 'SUCCESSION', 'label' => 'Linha de sucessão'],
        ];
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
                cr.requires_engineer_approval,
                cr.engineer_approval_status,
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
            $statusEnum = CancellationRequestStatus::tryFrom((string) ($item->status ?? ''));
            $scopeEnum = CancellationRequestScope::tryFrom((string) ($item->scope ?? ''));
            $engineerApprovalEnum = CancellationEngineerApprovalStatus::tryFrom((string) ($item->engineer_approval_status ?? ''));

            $item->status_label = $statusEnum?->label() ?? ((string) ($item->status ?? '-') ?: '-');
            $item->status_badge_class = $statusEnum?->badgeClass() ?? 'bg-secondary';

            $item->scope_label = $scopeEnum?->label() ?? ((string) ($item->scope ?? '-') ?: '-');
            $item->scope_badge_class = $scopeEnum?->badgeClass() ?? 'bg-secondary';

            $requiresEngineerApproval = (bool) ($item->requires_engineer_approval ?? false);
            $item->engineer_approval_label = $requiresEngineerApproval
                ? ($engineerApprovalEnum?->label() ?? 'Aguardando Engenheiro')
                : 'Não se aplica';
            $item->engineer_approval_badge_class = $requiresEngineerApproval
                ? ($engineerApprovalEnum?->badgeClass() ?? 'bg-warning text-dark')
                : 'bg-secondary';

            $item->waiting_label = null;
            $item->waiting_badge_class = null;
            if ($statusEnum === CancellationRequestStatus::SUBMITTED && empty($item->assigned_at)) {
                $item->waiting_label = 'Aguardando atribuição';
                $item->waiting_badge_class = 'bg-warning text-dark';
            } elseif ($requiresEngineerApproval && $engineerApprovalEnum === CancellationEngineerApprovalStatus::PENDING) {
                $item->waiting_label = 'Aguardando engenheiro';
                $item->waiting_badge_class = 'bg-warning text-dark';
            } elseif ($statusEnum === CancellationRequestStatus::PAUSED) {
                $item->waiting_label = 'Aguardando retomada';
                $item->waiting_badge_class = 'bg-info';
            }

            $item->exec_human = $this->secondsToHuman(isset($item->exec_seconds) ? (int) $item->exec_seconds : null);
            $item->eng_human = $this->secondsToHuman(isset($item->eng_seconds) ? (int) $item->eng_seconds : null);
            $item->close_human = $this->secondsToHuman(isset($item->close_seconds) ? (int) $item->close_seconds : null);
            $item->final_human = $this->secondsToHuman(isset($item->final_seconds) ? (int) $item->final_seconds : null);
            return $item;
        });

        $categories = DB::table('cancellation_categories')
            ->orderBy('name')
            ->pluck('name', 'id');

        $visibleRequesterIds = $this->visibleRequesterIds();
        $requesterOptions = DB::table('users as u')
            ->join('cancellation_requests as cr', 'cr.requested_by', '=', 'u.id')
            ->when($visibleRequesterIds !== null, fn ($q) => $q->whereIn('u.id', $visibleRequesterIds))
            ->select('u.id', 'u.name')
            ->distinct()
            ->orderByRaw('LOWER(u.name)')
            ->get();

        return view('livewire.reports.cancellation-list', [
            'rows' => $rows,
            'categories' => $categories,
            'statusOptions' => $this->statusOptions(),
            'scopeOptions' => $this->scopeOptions(),
            'visibilityOptions' => $this->visibilityOptions(),
            'requesterOptions' => $requesterOptions,
        ]);
    }
}
