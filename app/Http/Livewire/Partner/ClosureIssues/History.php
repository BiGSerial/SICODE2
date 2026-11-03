<?php

namespace App\Http\Livewire\Partner\ClosureIssues;

use App\Http\Livewire\Concerns\WithListFilters;
use App\Http\Livewire\Partner\Concerns\{AuthorizesPartnerAccess, PartnerListToolkit};
use App\Models\Closure\{ClosureIssue, ClosureIssueEvent};
use App\Services\Closure\ClosurePartnerExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\{Component, WithPagination};

/**
 * Histórico de interação da parceira com o Encerramento: em quais obras e pendências ela já respondeu, o que respondeu, quando,
 * com quantos anexos e o que aconteceu depois (aceita, devolvida, em validação). Sempre no escopo de empresa do usuário.
 */
class History extends Component
{
    use AuthorizesPartnerAccess;
    use PartnerListToolkit;
    use WithListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $status = '';

    public string $from = '';

    public string $to = '';

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'status'  => ['except' => '', 'as' => 'situacao'],
        'from'    => ['except' => '', 'as' => 'de'],
        'to'      => ['except' => '', 'as' => 'ate'],
        'filters' => ['except' => [], 'as' => 'f'],
        'page'    => ['except' => 1, 'as' => 'p'],
    ];

    public function mount(): void
    {
        $this->authorizePartnerAccess('closure.issues.list');

        $this->filters = $this->normalizeFilters($this->filters);
    }

    /** O filtro Empresa só existe para quem enxerga mais de uma empresa (superadm ou matriz com filiais). */
    protected function listFilterDefinitions(): array
    {
        return count($this->companyFilterOptionsCached()) > 1 ? ['company' => ['label' => 'Empresa']] : [];
    }

    protected function listFilterOptions(): array
    {
        return count($this->companyFilterOptionsCached()) > 1 ? ['company' => $this->companyFilterOptionsCached()] : [];
    }

    private ?array $companyOptionsMemo = null;

    private function companyFilterOptionsCached(): array
    {
        return $this->companyOptionsMemo ??= $this->rememberFilterOptions('company', fn () => $this->companyFilterOptions());
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . ':' . auth()->id();
    }

    protected function listFilterCacheTtl(): int
    {
        return 60;
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
        $this->clearListFilters();
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
                $this->applyCompanyFilter($issue, 'destination_company_id');

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
            'filters'              => $this->filters,
            'partnerCompanyFilter' => (string) $this->partnerCompanyFilter,
        ], (string) auth()->id());

        $this->dispatchBrowserEvent('closure:toast', ['message' => 'Exportação iniciada. Você será avisado quando o arquivo estiver pronto.', 'type' => 'info']);
    }

    /**
     * Totais do recorte filtrado numa leitura leve (4 colunas, sem relações): respostas, obras distintas, aceitas e devolvidas.
     * O resultado de cada resposta é o do próximo evento da pendência (mesma regra de ClosurePartnerExporter::outcomeOf).
     *
     * @return array{responses: int, notes: int, accepted: int, returned: int}
     */
    private function totals(Builder $query): array
    {
        $rows = (clone $query)->reorder()->toBase()->get([
            'closure_issue_events.id',
            DB::raw('(select o.note_id from closure_issues i join orders o on o.id = i.order_id where i.id = closure_issue_events.closure_issue_id) as note_id'),
            DB::raw('(select n.type from closure_issue_events n where n.closure_issue_id = closure_issue_events.closure_issue_id and n.id > closure_issue_events.id order by n.created_at, n.id limit 1) as next_type'),
        ]);

        return [
            'responses' => $rows->count(),
            'notes'     => $rows->pluck('note_id')->filter()->unique()->count(),
            'accepted'  => $rows->whereIn('next_type', ['RESOLVED', 'RELEASED_BY_OPERATOR'])->count(),
            'returned'  => $rows->where('next_type', 'RETURNED_FOR_CORRECTION')->count(),
        ];
    }

    public function render()
    {
        $this->authorizePartnerAccess('closure.issues.list');

        $query = $this->exportQuery();

        return view('livewire.partner.closure-issues.history', [
            'events'      => (clone $query)->paginate(25),
            'totals'      => $this->totals($query),
            'hasCriteria' => $this->hasListFilters() || $this->search !== '' || $this->status !== '' || $this->from !== '' || $this->to !== '' || (string) $this->partnerCompanyFilter !== '',
            'statuses'    => [ClosureIssue::STATUS_RESPONDED, ClosureIssue::STATUS_VALIDATING, ClosureIssue::STATUS_RETURNED, ClosureIssue::STATUS_RESOLVED, ClosureIssue::STATUS_REQUESTED],
        ]);
    }
}
