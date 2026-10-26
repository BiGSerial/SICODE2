<?php

namespace App\Http\Livewire\Partner\ClosureIssues;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\Closure\{ClosureIssue, ClosureIssueEvent};
use App\Services\Closure\ClosurePartnerExporter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\{Component, WithPagination};

/**
 * Histórico de interação da parceira com o Encerramento: em quais obras e pendências ela já respondeu, o que respondeu, quando,
 * com quantos anexos e o que aconteceu depois (aceita, devolvida, em validação). Sempre no escopo de empresa do usuário.
 */
class History extends Component
{
    use AuthorizesPartnerAccess;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $status = '';

    public string $from = '';

    public string $to = '';

    protected $queryString = [
        'search' => ['except' => '', 'as' => 'buscar'],
        'status' => ['except' => '', 'as' => 'situacao'],
        'from'   => ['except' => '', 'as' => 'de'],
        'to'     => ['except' => '', 'as' => 'ate'],
        'page'   => ['except' => 1, 'as' => 'p'],
    ];

    public function mount(): void
    {
        $this->authorizePartnerAccess('closure.issues.list');
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status', 'from', 'to', 'partnerCompanyFilter'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'from', 'to', 'partnerCompanyFilter');
        $this->resetPage();
    }

    public function removeFilter(string $kind): void
    {
        match ($kind) {
            'search'  => $this->search = '',
            'status'  => $this->status = '',
            'period'  => $this->reset('from', 'to'),
            'company' => $this->partnerCompanyFilter = '',
            default   => null,
        };
        $this->resetPage();
    }

    /** Respostas da parceira (evento RESPONDED de origem PARTNER) das pendências da empresa, com os filtros da tela. */
    public function exportQuery(): Builder
    {
        $this->authorizePartnerAccess('closure.issues.list');

        $search = trim($this->search);

        return ClosureIssueEvent::query()
            ->where('type', 'RESPONDED')
            ->where('origin', 'PARTNER')
            ->whereHas('Issue', function (Builder $issue) use ($search) {
                $this->applyPartnerCompanyScope($issue, 'destination_company_id');

                $issue->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
                    ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                        ->whereHas('Order', fn ($order) => $order->where('ordem', 'like', "%{$search}%"))
                        ->orWhereHas('Order.Note', fn ($note) => $note->where('note', 'like', "%{$search}%"))));
            })
            ->when($this->from !== '', fn ($q) => $q->where('created_at', '>=', $this->from . ' 00:00:00'))
            ->when($this->to !== '', fn ($q) => $q->where('created_at', '<=', $this->to . ' 23:59:59'))
            ->with(['Actor:id,name', 'Issue.Order.Note', 'Issue.Category', 'Issue.Subcategory', 'Issue.Events'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function exportToExcel(): void
    {
        $this->authorizePartnerAccess('closure.issues.list');

        \App\Jobs\Closure\ExportPartnerClosureListJob::dispatch(static::class, [
            'search'               => $this->search,
            'status'               => $this->status,
            'from'                 => $this->from,
            'to'                   => $this->to,
            'partnerCompanyFilter' => (string) $this->partnerCompanyFilter,
        ], (string) auth()->id());

        $this->dispatchBrowserEvent('closure:toast', ['message' => 'Exportação iniciada. Você será avisado quando o arquivo estiver pronto.', 'type' => 'info']);
    }

    public function render()
    {
        $this->authorizePartnerAccess('closure.issues.list');

        $all  = $this->exportQuery();
        $rows = (clone $all)->get();

        $totals = [
            'responses' => $rows->count(),
            'notes'     => $rows->map(fn ($event) => $event->Issue?->Order?->note_id)->filter()->unique()->count(),
            'accepted'  => $rows->filter(fn ($event) => ClosurePartnerExporter::outcomeOf($event) === 'Aceita')->count(),
            'returned'  => $rows->filter(fn ($event) => ClosurePartnerExporter::outcomeOf($event) === 'Devolvida para correção')->count(),
        ];

        $chips = array_values(array_filter([
            $this->search !== '' ? ['search', 'Busca: ' . $this->search] : null,
            $this->status !== '' ? ['status', 'Situação: ' . \App\Services\Closure\ClosureEventLabels::issueStatusLabel($this->status)] : null,
            ($this->from !== '' || $this->to !== '') ? ['period', 'Período: ' . ($this->from ?: '…') . ' a ' . ($this->to ?: '…')] : null,
            $this->partnerCompanyFilter !== '' ? ['company', 'Empresa: ' . ($this->partnerCompanyFilterOptions()->firstWhere('id', $this->partnerCompanyFilter)?->name ?? '—')] : null,
        ]));

        return view('livewire.partner.closure-issues.history', [
            'events'    => $all->paginate(25),
            'totals'    => $totals,
            'chips'     => $chips,
            'statuses'  => [ClosureIssue::STATUS_RESPONDED, ClosureIssue::STATUS_VALIDATING, ClosureIssue::STATUS_RETURNED, ClosureIssue::STATUS_RESOLVED, ClosureIssue::STATUS_REQUESTED],
            'companies' => auth()->user()?->superadm || $this->partnerCompanyFilterOptions()->count() > 1 ? $this->partnerCompanyFilterOptions() : collect(),
        ]);
    }
}
