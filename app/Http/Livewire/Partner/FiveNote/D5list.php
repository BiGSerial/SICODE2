<?php

namespace App\Http\Livewire\Partner\FiveNote;

use App\Http\Livewire\Partner\FiveNote\Concerns\ListsFiveNotes;
use App\Models\FiveNote;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/**
 * D5 em espera de execução da parceira (e, na subclasse, as rejeitadas): lista de controle, docs/PADRAO_LISTAS_OPERACIONAIS.md §10.
 */
class D5list extends Component
{
    use ListsFiveNotes;

    public $passiveFilter = 'all';

    protected bool $onlyReturned = false;

    protected $queryString = [
        'search'         => ['except' => ''],
        'passiveFilter'  => ['except' => 'all', 'as' => 'mostrar'],
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

        return $query->where('visible_partner', true)
            ->where('is_completed', false)
            ->where('returned', $this->onlyReturned);
    }

    protected function exportKind(): string
    {
        return 'waiting';
    }

    public function render()
    {
        $fives = $this->fives
            ->with([
                'note:id,note',
                'note.WorkForm:id,note_id',
                'note.WorkForm.Orders',
                'company:id,name',
                'WorkReport:id,selected_final_scopes',
                'productions:id,service_id,note_id',
                'productions.WorkReportFlowProductions:id,work_report_id,production_id,stage,final_scope,is_current',
            ])
            ->orderBy('dispatch_at')
            ->orderBy('id')
            ->paginate($this->perPage);

        $fives->getCollection()->each(function (FiveNote $five) {
            $five->setAttribute('list_days', $this->dispatchDays($five));
            $five->setAttribute('list_order', $five->note?->WorkForm?->Orders?->sortBy('ordem')->first()?->ordem);
            $five->setAttribute('list_reports', $this->reportAssociations($five));
        });

        return view('livewire.partner.five-note.d5list', [
            'fives'       => $fives,
            'counts'      => $this->passiveCounts(),
            'hasCriteria' => $this->hasCriteria(),
            'returned'    => $this->onlyReturned,
        ]);
    }

    /**
     * Informes e escopos (Geral, Rede, Ligação) ligados à D5: os do informe da própria D5 e os dos fluxos atuais das produções, sem repetir.
     *
     * @return array<int, array{report: int|string, scope: string}>
     */
    private function reportAssociations(FiveNote $five): array
    {
        $associations = collect();

        if ($five->work_report_id) {
            foreach (collect($five->WorkReport?->selected_final_scopes ?? ['general'])->filter() as $scope) {
                $associations->push(['report' => $five->work_report_id, 'scope' => $scope]);
            }
        }

        foreach ($five->productions ?? [] as $production) {
            foreach ($production->WorkReportFlowProductions->where('is_current', true)->whereNotNull('work_report_id') as $flow) {
                $associations->push(['report' => $flow->work_report_id, 'scope' => $flow->final_scope ?: 'general']);
            }
        }

        return $associations->unique(fn ($item) => $item['report'] . '|' . $item['scope'])->values()->all();
    }
}
