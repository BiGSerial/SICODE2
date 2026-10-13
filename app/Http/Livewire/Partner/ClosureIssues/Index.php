<?php

namespace App\Http\Livewire\Partner\ClosureIssues;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\Closure\ClosureIssue;
use App\Models\Company;
use App\Services\Closure\{ClosureIssueWorkflowException, ClosureIssueWorkflowService};
use App\Services\PartnerAccess\PartnerAccessGate;
use Livewire\{Component, WithPagination};

class Index extends Component
{
    use AuthorizesPartnerAccess;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $status = '';

    public string $categoryId = '';

    public string $search = '';

    public ?int $respondingIssueId = null;

    public string $responseMessage = '';

    protected $queryString = [
        'status'     => ['except' => ''],
        'categoryId' => ['except' => '', 'as' => 'categoria'],
        'search'     => ['except' => '', 'as' => 'buscar'],
        'page'       => ['except' => 1, 'as' => 'p'],
    ];

    protected $rules = [
        'responseMessage' => 'required|string|min:3|max:5000',
    ];

    public function mount(): void
    {
        $this->authorizePartnerAccess('closure.issues.list');
    }

    public function updated($property): void
    {
        if (in_array($property, ['status', 'categoryId', 'search', 'partnerCompanyFilter'], true)) {
            $this->resetPage();
        }
    }

    public function startResponse(int $issueId): void
    {
        $this->authorizePartnerAccess('closure.issues.respond');
        $issue = $this->visibleIssues()->whereKey($issueId)->firstOrFail();

        abort_unless(in_array($issue->status, [ClosureIssue::STATUS_REQUESTED, ClosureIssue::STATUS_RETURNED], true), 422);

        $this->respondingIssueId = $issue->id;
        $this->responseMessage   = '';
    }

    public function cancelResponse(): void
    {
        $this->respondingIssueId = null;
        $this->responseMessage   = '';
        $this->resetValidation();
    }

    public function submitResponse(): void
    {
        $this->authorizePartnerAccess('closure.issues.respond');
        $this->validate();

        $issue   = $this->visibleIssues()->whereKey($this->respondingIssueId)->firstOrFail();
        $company = $this->responseCompanyFor($issue);

        try {
            app(ClosureIssueWorkflowService::class)->respond($issue, $company, auth()->user(), $this->responseMessage);
        } catch (ClosureIssueWorkflowException $exception) {
            $this->addError('responseMessage', $exception->getMessage());

            return;
        }

        $this->cancelResponse();
        session()->flash('success', 'Resposta enviada e registrada no histórico da pendência.');
    }

    public function render()
    {
        $this->authorizePartnerAccess('closure.issues.list');

        $issues = $this->visibleIssues()
            ->with(['Order.Note', 'Category', 'Subcategory', 'Events.Actor'])
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->categoryId !== '', fn ($query) => $query->where('closure_issue_category_id', $this->categoryId))
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);
                $query->where(function ($query) use ($search) {
                    $query->whereHas('Order', fn ($order) => $order->where('ordem', 'like', "%{$search}%"))
                        ->orWhereHas('Order.Note', fn ($note) => $note->where('note', 'like', "%{$search}%"));
                });
            })
            ->latest('updated_at')
            ->paginate(25);

        $categories = $this->visibleIssues()
            ->with('Category:id,name,active')
            ->get(['closure_issue_category_id'])
            ->pluck('Category')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        return view('livewire.partner.closure-issues.index', compact('issues', 'categories'));
    }

    private function visibleIssues()
    {
        $query = ClosureIssue::query();
        $this->applyPartnerCompanyScope($query, 'destination_company_id');

        return $query;
    }

    private function responseCompanyFor(ClosureIssue $issue): Company
    {
        $user    = auth()->user();
        $visible = $user->superadm ? null : PartnerAccessGate::visibleCompanyIdsFor($user);

        return Company::query()
            ->when($visible !== null, fn ($query) => $query->whereIn('id', $visible))
            ->whereKey($issue->destination_company_id)
            ->firstOrFail();
    }
}
