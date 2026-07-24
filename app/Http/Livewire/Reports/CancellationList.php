<?php

namespace App\Http\Livewire\Reports;

use App\Enum\{CancellationEngineerApprovalStatus, CancellationRequestScope, CancellationRequestStatus};
use App\Jobs\Reports\ExportCancellationListJob;
use App\Support\Reports\CancellationListQuery;
use Illuminate\Support\Facades\{Auth, DB};
use Livewire\{Component, WithPagination};

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
        'dateFrom'       => ['except' => '', 'as' => 'de'],
        'dateTo'         => ['except' => '', 'as' => 'ate'],
        'status'         => ['except' => '', 'as' => 'sts'],
        'scope'          => ['except' => '', 'as' => 'tipo'],
        'categoryId'     => ['except' => '', 'as' => 'cat'],
        'search'         => ['except' => '', 'as' => 'q'],
        'visibilityMode' => ['except' => 'HIERARCHY', 'as' => 'vis'],
    ];

    public function mount(): void
    {
        if ($this->dateFrom === '' || $this->dateTo === '') {
            $this->dateTo   = now()->toDateString();
            $this->dateFrom = now()->subDays(29)->toDateString();
        }

        if ((Auth::user()?->superadm || Auth::user()?->management) && !request()->has('vis')) {
            $this->visibilityMode = 'ALL';
        }
    }

    public function updating($name): void
    {
        if (in_array($name, ['dateFrom', 'dateTo', 'status', 'scope', 'categoryId', 'search', 'visibilityMode'], true)
            || str_starts_with($name, 'requesterIds')) {
            $this->resetPage();
        }
    }

    public function exportToExcel(): void
    {
        ExportCancellationListJob::dispatch(
            $this->filters(),
            $this->visibleRequesterIds(),
            (string) Auth::id(),
        );

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'success',
            'title' => 'Exportação iniciada. Você será notificado quando concluir.',
        ]);
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
            ->filter(fn ($id) => is_string($id) || is_int($id))
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function baseQuery()
    {
        return CancellationListQuery::build($this->filters(), $this->visibleRequesterIds());
    }

    /** @return array<string, mixed> */
    private function filters(): array
    {
        return [
            'dateFrom'     => $this->dateFrom,
            'dateTo'       => $this->dateTo,
            'status'       => $this->status,
            'scope'        => $this->scope,
            'categoryId'   => $this->categoryId,
            'requesterIds' => $this->selectedRequesterIds(),
            'searchTokens' => $this->parseTokens(),
        ];
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
            $statusEnum           = CancellationRequestStatus::tryFrom((string) ($item->status ?? ''));
            $scopeEnum            = CancellationRequestScope::tryFrom((string) ($item->scope ?? ''));
            $engineerApprovalEnum = CancellationEngineerApprovalStatus::tryFrom((string) ($item->engineer_approval_status ?? ''));

            $item->status_label       = $statusEnum?->label() ?? ((string) ($item->status ?? '-') ?: '-');
            $item->status_badge_class = $statusEnum?->badgeClass() ?? 'bg-secondary';

            $item->scope_label       = $scopeEnum?->label() ?? ((string) ($item->scope ?? '-') ?: '-');
            $item->scope_badge_class = $scopeEnum?->badgeClass() ?? 'bg-secondary';

            $requiresEngineerApproval      = (bool) ($item->requires_engineer_approval ?? false);
            $item->engineer_approval_label = $requiresEngineerApproval
                ? ($engineerApprovalEnum?->label() ?? 'Aguardando Engenheiro')
                : 'Não se aplica';
            $item->engineer_approval_badge_class = $requiresEngineerApproval
                ? ($engineerApprovalEnum?->badgeClass() ?? 'bg-warning text-dark')
                : 'bg-secondary';

            $item->waiting_label       = null;
            $item->waiting_badge_class = null;

            if ($statusEnum === CancellationRequestStatus::SUBMITTED && empty($item->assigned_at)) {
                $item->waiting_label       = 'Aguardando atribuição';
                $item->waiting_badge_class = 'bg-warning text-dark';
            } elseif ($requiresEngineerApproval && $engineerApprovalEnum === CancellationEngineerApprovalStatus::PENDING) {
                $item->waiting_label       = 'Aguardando engenheiro';
                $item->waiting_badge_class = 'bg-warning text-dark';
            } elseif ($statusEnum === CancellationRequestStatus::PAUSED) {
                $item->waiting_label       = 'Aguardando retomada';
                $item->waiting_badge_class = 'bg-info';
            }

            $item->exec_human  = $this->secondsToHuman(isset($item->exec_seconds) ? (int) $item->exec_seconds : null);
            $item->eng_human   = $this->secondsToHuman(isset($item->eng_seconds) ? (int) $item->eng_seconds : null);
            $item->close_human = $this->secondsToHuman(isset($item->close_seconds) ? (int) $item->close_seconds : null);
            $item->final_human = $this->secondsToHuman(isset($item->final_seconds) ? (int) $item->final_seconds : null);

            return $item;
        });

        $categories = DB::table('cancellation_categories')
            ->orderBy('name')
            ->pluck('name', 'id');

        $visibleRequesterIds = $this->visibleRequesterIds();
        $requesterOptions    = DB::table('users as u')
            ->join('cancellation_requests as cr', 'cr.requested_by', '=', 'u.id')
            ->when($visibleRequesterIds !== null, fn ($q) => $q->whereIn('u.id', $visibleRequesterIds))
            ->select('u.id', 'u.name')
            ->distinct()
            ->orderByRaw('LOWER(u.name)')
            ->get();

        return view('livewire.reports.cancellation-list', [
            'rows'              => $rows,
            'categories'        => $categories,
            'statusOptions'     => $this->statusOptions(),
            'scopeOptions'      => $this->scopeOptions(),
            'visibilityOptions' => $this->visibilityOptions(),
            'requesterOptions'  => $requesterOptions,
        ]);
    }
}
