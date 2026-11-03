<?php

namespace App\Http\Livewire\Dispatchs\Analises\Concerns;

use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\{Note, Production};
use App\Services\Dispatch\PartnerStackQuery;
use App\Support\SicodeRules;
use Illuminate\Support\Facades\DB;

/**
 * Peças compartilhadas pelas listas de Despacho de Análises (`Main` e `PartnerMain`) no padrão de listas operacionais
 * (docs/PADRAO_LISTAS_OPERACIONAIS.md): filtros (Material, Rubrica, Região, Regional, Município), "limpar tudo" e a
 * avaliação de bloqueio por linha em memo. Usa as propriedades públicas do componente que a inclui.
 */
trait HasAnalysisListFilters
{
    use ResolvesLocationCodes;
    use WithNoteListFilters;

    /** @var array<int, array> Memo de `rowDispatchState()` (zerado a cada render). */
    private array $dispatchStateMemo = [];

    /** @var array<int, int>|null IDs de empresas visíveis ao usuário de contrato (lidos uma vez). */
    private ?array $visibleCompanyIdsMemo = null;

    /** O filtro Material só existe na lista de Despacho (a da parceira nunca teve). */
    protected function hasMaterialFilter(): bool
    {
        return true;
    }

    protected function listFilterDefinitions(): array
    {
        return ($this->hasMaterialFilter() ? ['material' => ['label' => 'Material']] : []) + [
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterOptions(): array
    {
        $options = [];

        if ($this->hasMaterialFilter()) {
            $options['material'] = $this->rememberFilterOptions('material', fn () => Note::query()
                ->whereNotNull('material')->where('material', '!=', '')
                ->distinct()->orderBy('material')->pluck('material')
                ->map(fn ($m) => ['v' => (string) $m, 'l' => (string) $m])->all());
        }

        $options['rubrica'] = $this->rememberFilterOptions('rubrica', fn () => Note::query()
            ->whereNotNull('rubrica')->where('rubrica', '!=', '')
            ->distinct()->orderBy('rubrica')->pluck('rubrica')
            ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all());

        return $options + $this->locationFilterOptions();
    }

    /** "Limpar tudo": filtros, busca (simples e em massa) e atalhos. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters       = [];
        $this->search        = '';
        $this->multiSearch   = [];
        $this->advanceSearch = '';
        $this->not_assigned  = false;
        $this->mmgd          = false;
        $this->afterListFiltersChanged();
    }

    public function needBlock(Note $note): array
    {
        return $this->rowDispatchState($note)['eval'];
    }

    /**
     * Avaliação de bloqueio + pilha da empresa de uma linha, feita UMA vez por requisição (a tela, o "selecionar todos"
     * e a verificação do mestre liam isso 3x por linha).
     *
     * @return array{eval: array, stack: ?Production, canDispatch: bool}
     */
    public function rowDispatchState(Note $note): array
    {
        return $this->dispatchStateMemo[$note->id] ??= (function () use ($note) {
            $eval  = app(\App\Services\Design\BlockEvaluator::class)->evaluate($note, $this->service);
            $stack = $this->openCompanyStackFor($note);

            return [
                'eval'        => $eval,
                'stack'       => $stack,
                'canDispatch' => !$eval['block'] || (bool) ($eval['command'] ?? false) || (bool) $stack,
            ];
        })();
    }

    /**
     * Mesma regra de `SicodeRules::openCompanyStackProductionFor` (Production aberta, sem usuário, de empresa visível ao
     * contrato), lida das Productions já carregadas na página em vez de uma consulta por linha.
     */
    private function openCompanyStackFor(Note $note): ?Production
    {
        $user = Auth()->User();

        if (!$user->usesContractDispatch()) {
            return null;
        }

        $companyIds = $this->visibleCompanyIdsMemo ??= array_map('intval', SicodeRules::visibleCompanyIdsFor($user));

        if (!$note->relationLoaded('Productions')) {
            return app(PartnerStackQuery::class)->openFor($note, $this->service->uuid, $user);
        }

        return $note->Productions
            ->where('service_id', $this->service->uuid)
            ->whereNull('user_id')
            ->where('completed', false)
            ->where('confirmed', false)
            ->filter(fn ($p) => in_array((int) $p->company_id, $companyIds, true))
            ->sortByDesc('id')
            ->first();
    }

    private function canDispatchNote(Note $note): bool
    {
        return $this->rowDispatchState($note)['canDispatch'];
    }

    /** Notas da página com demanda jurídica vinculada (para só renderizar a etiqueta onde existe). */
    private function legalLinkedNoteIds(array $noteIds): array
    {
        if (!$noteIds) {
            return [];
        }

        $instructions = DB::table('legal_demand_note_instructions')->whereIn('note_id', $noteIds)->where('active', true)->pluck('note_id');
        $links        = DB::table('legal_demand_note_activity_links')->whereIn('note_id', $noteIds)->whereNull('unlinked_at')->pluck('note_id');

        return array_fill_keys($instructions->merge($links)->unique()->all(), true);
    }
}
