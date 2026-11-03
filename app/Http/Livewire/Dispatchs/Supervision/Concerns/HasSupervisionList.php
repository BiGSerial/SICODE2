<?php

namespace App\Http\Livewire\Dispatchs\Supervision\Concerns;

use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Http\Livewire\Services\Concerns\BuildsLegalNoteTags;
use App\Models\{City, Note};
use Illuminate\Support\Facades\Cache;

/**
 * Peças comuns das listas de Despacho da Fiscalização (`Main` e `PartnerMain`) no padrão de listas operacionais
 * (docs/PADRAO_LISTAS_OPERACIONAIS.md): filtros da tela (Rubrica, Região, Regional, Município) e a tradução deles
 * para o formato que `SupervisionDispatchListQuery` já lê (`filter['rubrica']`, `filter['city']`), sem `$_SESSION`.
 */
trait HasSupervisionList
{
    use WithNoteListFilters;
    use BuildsLegalNoteTags;

    protected function listFilterDefinitions(): array
    {
        return [
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterOptions(): array
    {
        return [
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => Note::query()
                ->whereNotNull('rubrica')->where('rubrica', '!=', '')
                ->distinct()->orderBy('rubrica')->pluck('rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
        ] + $this->locationFilterOptions();
    }

    /** As opções não dependem da atividade nem da parceira: a lista operacional e a da parceira dividem o cache. */
    protected function listFilterCacheScope(): string
    {
        return 'supervision-dispatch';
    }

    /**
     * Filtros da tela no formato da consulta (`SupervisionDispatchListQuery` / `ExportSupervisionList`):
     * `rubrica` => [..], `city` => [códigos rdMunicipio]. Região/Regional/Município viram a lista de municípios.
     */
    protected function listQueryFilter(): array
    {
        $filter  = [];
        $rubrica = $this->filterValues('rubrica');

        if ($rubrica) {
            $filter['rubrica'] = $rubrica;
        }

        $codes = $this->locationCityCodes();

        if ($codes !== null) {
            $filter['city'] = $codes;
        }

        return $filter;
    }

    /** Códigos `rdMunicipio` dos filtros de localidade; `null` quando nenhum está marcado. */
    protected function locationCityCodes(): ?array
    {
        $region   = $this->filterValues('region');
        $regional = $this->filterValues('regional');
        $city     = $this->filterValues('city');

        if (!$region && !$regional && !$city) {
            return null;
        }

        return City::query()
            ->when($region, fn ($q) => $q->whereIn('regiao', $region))
            ->when($regional, fn ($q) => $q->whereIn('baseConstrucao', $regional))
            ->when($city, fn ($q) => $q->whereIn('rdMunicipio', $city))
            ->pluck('rdMunicipio')->filter()->unique()->values()->all();
    }

    /** "Limpar tudo": filtros, busca (simples e em massa), tipo, atalhos e "buscar em qualquer status". */
    public function clearAllCriteria(): void
    {
        $this->filters             = [];
        $this->search              = '';
        $this->multiSearch         = [];
        $this->advanceSearch       = '';
        $this->not_assigned        = false;
        $this->filter_d5           = false;
        $this->typeNote            = '';
        $this->bulkSearchAnyStatus = false;
        $this->afterListFiltersChanged();
    }

    /**
     * Relações que a linha lê e a consulta da lista não traz (evita uma consulta por linha). Chamar logo após paginar.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, Note> $notes
     */
    protected function preloadRowRelations($notes): void
    {
        $notes->loadMissing(['Adsform', 'Partials.Orders', 'WorkForms.Note:id,type_note']);
    }

    /**
     * Linhas cuja `WorkForm` não foi definida pelo componente (D5 passiva, busca em qualquer status) a carregam preguiçosamente
     * no avaliador e na linha; aqui ela vem de uma vez só para a página. Chamar depois de definir as relações das linhas.
     */
    protected function preloadLazyWorkForms($notes): void
    {
        $lazy = $notes->filter(fn (Note $note) => !$note->relationLoaded('WorkForm'));

        if ($lazy->isNotEmpty()) {
            (new \Illuminate\Database\Eloquent\Collection($lazy->all()))->load(['WorkForm.Orders:id,ordem', 'WorkForm.Adsform', 'WorkForm.Note:id,type_note']);
        }
    }

    /** @var array<int, int>|null Empresas visíveis do usuário (a regra faz 3 consultas; a lista a lia por linha). */
    private ?array $visibleCompanyIdsMemo = null;

    /** @return array<int, int> */
    protected function visibleCompanyIds(): array
    {
        return $this->visibleCompanyIdsMemo ??= array_map('intval', \App\Support\SicodeRules::visibleCompanyIdsFor(Auth()->User()));
    }

    /** Data da última atualização vinda do SAP (cabeçalho): cache curto, não muda a cada render. */
    protected function lastSapUpdate()
    {
        return Cache::remember('supervision-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }
}
