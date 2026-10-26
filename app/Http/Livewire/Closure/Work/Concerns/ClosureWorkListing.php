<?php

namespace App\Http\Livewire\Closure\Work\Concerns;

use App\Models\Closure\ClosureOrderState;
use App\Services\Closure\{ClosureEligibleOrders, ClosureMetaClassifier};
use Illuminate\Database\Eloquent\Builder;

/**
 * Peças comuns das listas de Encerramento por Ordem (Minha atividade e Atividades disponíveis):
 * consulta com tipo P/M/F, filtros e feedback por toast.
 */
trait ClosureWorkListing
{
    use \App\Http\Livewire\Closure\Concerns\WithBulkSearch;

    public string $type = 'all';

    public string $status = 'all';

    public string $search = '';

    /** @var array<int, string> Status usuário (2 primeiros tokens de orders.statusUser), como na lista do Despacho */
    public array $statusUsers = [];

    /** Consulta base por Ordem, já com as colunas `meta_type` (dinâmica; congelada se encerrada) e `competence` (YYYY-MM). */
    protected function baseListingQuery(): Builder
    {
        $query = ClosureOrderState::query()
            ->join('orders', 'orders.id', '=', 'closure_order_states.order_id')
            ->select('closure_order_states.*')
            ->with(['Order.Note', 'Order.Operations', 'CurrentHolderUser', 'CurrentHolderCompany']);

        $classifier = app(ClosureMetaClassifier::class);
        $classifier->withMetaType($query);
        $classifier->withCompetence($query);

        if (in_array($this->type, ClosureMetaClassifier::TYPES, true)) {
            $classifier->whereMetaType($query, $this->type);
        }

        $prefixes = collect($this->statusUsers)->map(fn ($v) => ClosureEligibleOrders::statusUserPrefix((string) $v))->filter()->unique()->values();

        if ($prefixes->isNotEmpty()) {
            $query->where(function (Builder $builder) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $builder->orWhere('orders.statusUser', $prefix)
                        ->orWhere('orders.statusUser', 'like', addcslashes($prefix, '%_\\') . ' %');
                }
            });
        }

        $term = trim($this->search);

        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $query->where(function (Builder $builder) use ($like) {
                $builder->where('orders.ordem', 'like', $like)
                    ->orWhereHas('Order.Note', fn (Builder $note) => $note->where('note', 'like', $like));
            });
        }

        if ($this->bulkTerms !== []) {
            $terms = $this->bulkTerms;
            $query->where(function (Builder $builder) use ($terms) {
                $builder->whereIn('orders.ordem', $terms)
                    ->orWhereHas('Order.Note', fn (Builder $note) => $note->whereIn('note', $terms));
            });
        }

        return $query;
    }

    public function clearFilters(): void
    {
        $this->type        = 'all';
        $this->status      = 'all';
        $this->search      = '';
        $this->statusUsers = [];
        $this->bulkTerms   = [];
        $this->bulkText    = '';
    }

    /** Ordens e Notas que a lista devolve com os filtros atuais (base do resumo da busca em massa). */
    protected function bulkSearchedValues(): array
    {
        return $this->exportQuery()->get()->flatMap(fn ($state) => [$state->Order?->ordem, $state->Order?->Note?->note])->filter()->all();
    }

    /** Lista completa (sem paginar) com os filtros atuais; base do export. */
    abstract public function exportQuery(): Builder;

    /**
     * Opções do filtro "Status usuário": prefixo de 2 tokens e quantas Ordens da lista (sem esse filtro) o têm.
     *
     * @return array<int, array{v: string, n: int}>
     */
    public function statusUserOptions(): array
    {
        $selected          = $this->statusUsers;
        $this->statusUsers = [];
        $base              = $this->exportQuery()->reorder()->toBase();
        $this->statusUsers = $selected;

        $base->columns            = null;
        $base->orders             = null;
        $base->bindings['select'] = [];

        return $base->selectRaw("trim(substring_index(trim(orders.statusUser), ' ', 2)) as pfx, count(*) as n")
            ->whereNotNull('orders.statusUser')
            ->groupBy('pfx')
            ->orderBy('pfx')
            ->get()
            ->filter(fn ($row) => $row->pfx !== '')
            ->map(fn ($row) => ['v' => (string) $row->pfx, 'n' => (int) $row->n])
            ->values()
            ->all();
    }

    /** Toast único por ação concluída (nunca chamar de render/poll). */
    protected function toast(string $title, string $text, string $icon = 'success'): void
    {
        $this->dispatchBrowserEvent('notification:toast', [
            'title' => $title,
            'text'  => $text !== '' ? $text : $title,
            'icon'  => $icon,
        ]);
    }

    public function metaBadge(?string $type): string
    {
        return ClosureMetaClassifier::badge($type);
    }
}
