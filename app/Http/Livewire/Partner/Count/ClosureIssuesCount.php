<?php

namespace App\Http\Livewire\Partner\Count;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\Closure\ClosureIssue;
use App\Services\PartnerAccess\PartnerAccessGate;
use Livewire\Component;

/**
 * Sinalizador do menu da parceira (Encerramento): pendências que esperam resposta dela (aguardando resposta ou devolvidas
 * para correção). `mode = dot` é a bolinha vermelha do item pai; `mode = badge` é o selo com a quantidade no link.
 * Mesmo escopo de empresa da lista de pendências (superadm vê todas, sem filtro de empresa).
 */
class ClosureIssuesCount extends Component
{
    use AuthorizesPartnerAccess;

    /** @var string dot|badge */
    public string $mode = 'badge';

    public function getCountProperty(): int
    {
        if (!PartnerAccessGate::allows(auth()->user(), 'closure.issues.list')) {
            return 0;
        }

        $query = ClosureIssue::query()->whereIn('status', [ClosureIssue::STATUS_REQUESTED, ClosureIssue::STATUS_RETURNED]);

        $this->applyPartnerCompanyScope($query, 'destination_company_id');

        return $query->count();
    }

    public function render()
    {
        return view('livewire.partner.count.closure-issues-count', ['count' => $this->count]);
    }
}
