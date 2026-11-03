<?php

namespace App\Http\Livewire\Partner\FiveNote;

use App\Http\Livewire\Partner\FiveNote\Concerns\ListsFiveNotes;
use App\Models\FiveNote;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/**
 * Histórico de D5 concluídas da parceira: lista de controle, docs/PADRAO_LISTAS_OPERACIONAIS.md §10.
 */
class Historic extends Component
{
    use ListsFiveNotes;

    public $passiveFilter = 'current';

    protected $queryString = [
        'search'         => ['except' => ''],
        'passiveFilter'  => ['except' => 'current', 'as' => 'mostrar'],
        'month'          => ['except' => ''],
        'startDate'      => ['except' => ''],
        'endDate'        => ['except' => ''],
        'multipleSearch' => ['except' => []],
        'filters'        => ['except' => [], 'as' => 'f'],
    ];

    protected function baseQuery(): Builder
    {
        $query = FiveNote::query();

        $this->applyPartnerCompanyScope($query);

        return $query->where('visible_partner', true)->where('is_completed', true);
    }

    protected function exportKind(): string
    {
        return 'historic';
    }

    /** Situação da D5 na cadeia de medição: [rótulo, tom]. */
    private function stageOf(FiveNote $five): array
    {
        if (!$five->is_payed) {
            return ['Aguardando despacho da medição', 'blue'];
        }

        return match (true) {
            (bool) $five->is_archived      => ['Finalizada', 'green'],
            (bool) $five->is_supervisioned => ['Aguardando liberação da medição', 'amber'],
            (bool) $five->is_completed     => ['Aguardando fiscalização', 'teal'],
            (bool) $five->visible_partner  => ['Aguardando conclusão da parceira', 'blue'],
            default                        => ['—', 'muted'],
        };
    }

    public function render()
    {
        $fives = $this->fives
            ->with(['note:id,note', 'note.Orders:id,note_id,ordem', 'company:id,name'])
            ->orderBy('completed_at', 'desc')
            ->orderBy('id')
            ->paginate($this->perPage);

        $fives->getCollection()->each(function (FiveNote $five) {
            $five->setAttribute('list_order', $five->note?->Orders?->sortBy('ordem')->first()?->ordem);
            $five->setAttribute('list_stage', $this->stageOf($five));
        });

        return view('livewire.partner.five-note.historic', [
            'fives'       => $fives,
            'counts'      => $this->passiveCounts(),
            'hasCriteria' => $this->hasCriteria(),
        ]);
    }
}
