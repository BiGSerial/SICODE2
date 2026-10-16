<?php

namespace App\Http\Livewire\Closure\Work\Concerns;

use App\Models\Closure\ClosureOrderState;
use App\Services\Closure\ClosureMetaClassifier;
use Illuminate\Database\Eloquent\Builder;

/**
 * Peças comuns das listas de Encerramento por Ordem (Minha atividade e Atividades disponíveis):
 * consulta com tipo P/M/F, filtros e feedback por toast.
 */
trait ClosureWorkListing
{
    public string $type = 'all';

    public string $status = 'all';

    public string $search = '';

    /** Consulta base por Ordem, já com a coluna `meta_type` (dinâmica; congelada se encerrada). */
    protected function baseListingQuery(): Builder
    {
        $query = ClosureOrderState::query()
            ->join('orders', 'orders.id', '=', 'closure_order_states.order_id')
            ->select('closure_order_states.*')
            ->with(['Order.Note', 'Order.Operations', 'CurrentHolderUser', 'CurrentHolderCompany']);

        $classifier = app(ClosureMetaClassifier::class);
        $classifier->withMetaType($query);

        if (in_array($this->type, ClosureMetaClassifier::TYPES, true)) {
            $classifier->whereMetaType($query, $this->type);
        }

        $term = trim($this->search);

        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $query->where(function (Builder $builder) use ($like) {
                $builder->where('orders.ordem', 'like', $like)
                    ->orWhereHas('Order.Note', fn (Builder $note) => $note->where('note', 'like', $like));
            });
        }

        return $query;
    }

    public function clearFilters(): void
    {
        $this->type   = 'all';
        $this->status = 'all';
        $this->search = '';
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
