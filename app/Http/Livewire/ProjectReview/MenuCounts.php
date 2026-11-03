<?php

namespace App\Http\Livewire\ProjectReview;

use App\Models\Production;
use Livewire\Component;

/**
 * Contadores do item "Lista para analisar" do menu lateral da Análise de Projetos: itens na análise (vermelho) e, entre eles, os que são
 * retorno para confirmar/analisar (âmbar). Declarado em `ProjectReviewNav` (`counts`); atualiza sozinho a cada 30 s.
 */
class MenuCounts extends Component
{
    public function render()
    {
        $base = Production::query()->where('status', Production::STATUS_IN_PROJECT_REVIEW);

        $returns = (clone $base)->where(function ($q) {
            $q->whereHas('ProjectReviewCycles', fn ($cycle) => $cycle->where('round_number', '>', 1))
                ->orWhereHas('ProjectReviewCycles', fn ($cycle) => $cycle->where('decision', 'REJECTED'))
                ->orWhereHas('Notetimelines', fn ($timeline) => $timeline->where('status', Production::STATUS_REJECTED_PROJECT_REVIEW));
        })->count();

        return view('livewire.project-review.menu-counts', [
            'pending' => (clone $base)->count(),
            'returns' => $returns,
        ]);
    }
}
