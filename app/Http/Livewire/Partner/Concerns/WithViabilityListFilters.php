<?php

namespace App\Http\Livewire\Partner\Concerns;

use App\Http\Livewire\Concerns\WithListFilters;
use App\Models\{City, Company, Note};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Filtros das listas de viabilidade do portal da parceira (Empresa, Criticidade, Rubrica, Região, Regional, Município),
 * sobre o trait `WithListFilters`. Quem usa declara quais filtros mostra em `viabilityFilterKeys()` e aplica
 * `applyViabilityFilters()` na consulta; o escopo por empresa continua em `AuthorizesPartnerAccess::applyPartnerCompanyScope`
 * (o filtro Empresa só restringe ainda mais, nunca amplia).
 */
trait WithViabilityListFilters
{
    use WithListFilters;

    private ?Collection $viabilityCompaniesMemo = null;

    /** Chaves dos filtros desta tela, na ordem de exibição. */
    protected function viabilityFilterKeys(): array
    {
        return ['company', 'rubrica', 'region', 'city'];
    }

    private function viabilityCompanies(): Collection
    {
        return $this->viabilityCompaniesMemo ??= $this->partnerCompanyFilterOptions();
    }

    protected function listFilterDefinitions(): array
    {
        $all = [
            'company'    => ['label' => 'Empresa'],
            'txpriority' => ['label' => 'Criticidade'],
            'rubrica'    => ['label' => 'Rubrica'],
            'region'     => ['label' => 'Região'],
            'regional'   => ['label' => 'Regional', 'depends' => ['region']],
            'city'       => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];

        $keys = $this->viabilityFilterKeys();
        $defs = array_intersect_key($all, array_flip($keys));

        // A empresa só é filtro quando o usuário enxerga mais de uma.
        if (isset($defs['company']) && $this->viabilityCompanies()->count() < 2) {
            unset($defs['company']);
        }

        if (isset($defs['city'])) {
            $defs['city']['depends'] = array_values(array_intersect($defs['city']['depends'], $keys));
        }

        return $defs;
    }

    protected function listFilterOptions(): array
    {
        $keys    = array_keys($this->listFilterDefinitions());
        $options = [];

        foreach ($keys as $key) {
            $options[$key] = match ($key) {
                'company'    => $this->viabilityCompanies()->map(fn (Company $c) => ['v' => (string) $c->id, 'l' => $c->name . ($c->parent_id ? ' (filial)' : '')])->values()->all(),
                'txpriority' => $this->rememberFilterOptions('txpriority', fn () => $this->distinctNoteOptions('txpriority')),
                'rubrica'    => $this->rememberFilterOptions('rubrica', fn () => $this->distinctNoteOptions('rubrica')),
                'region'     => $this->rememberFilterOptions('region', fn () => City::query()->whereNotNull('regiao')->distinct()->orderBy('regiao')->pluck('regiao')
                    ->map(fn ($v) => ['v' => (string) $v, 'l' => (string) $v])->all()),
                'regional' => $this->rememberFilterOptions('regional', fn () => City::query()->whereNotNull('regional')->select('regional', 'regiao')->distinct()->orderBy('regional')->get()
                    ->map(fn ($c) => ['v' => (string) $c->regional, 'l' => (string) $c->regional, 'p' => ['region' => (string) $c->regiao]])->all()),
                'city' => $this->rememberFilterOptions('city', fn () => City::query()->whereNotNull('cidade')->select('cidade', 'regiao', 'regional')->orderBy('cidade')->get()->unique('cidade')
                    ->map(fn ($c) => ['v' => (string) $c->cidade, 'l' => (string) $c->cidade, 'p' => ['region' => (string) $c->regiao, 'regional' => (string) $c->regional]])->values()->all()),
                default => [],
            };
        }

        return $options;
    }

    private function distinctNoteOptions(string $column): array
    {
        return Note::query()->whereNotNull($column)->where($column, '!=', '')->distinct()->orderBy($column)->pluck($column)
            ->map(fn ($v) => ['v' => (string) $v, 'l' => (string) $v])->all();
    }

    protected function listFilterCacheTtl(): int
    {
        return 600;
    }

    /** Aplica os filtros marcados: Empresa na própria viabilidade, o resto na Nota (Região/Regional só quando não há Município). */
    protected function applyViabilityFilters(Builder $query, string $table = 'viabilities'): Builder
    {
        $company = $this->filterValues('company');

        if ($company) {
            $query->whereIn($table . '.company_id', $company);
        }

        $rubrica  = $this->filterValues('rubrica');
        $priority = $this->filterValues('txpriority');
        $city     = $this->filterValues('city');
        $region   = $city ? [] : $this->filterValues('region');
        $regional = $city ? [] : $this->filterValues('regional');
        $noteType = property_exists($this, 'typeNote') && in_array((string) $this->typeNote, ['1', '2'], true) ? (string) $this->typeNote : null;

        if ($rubrica || $priority || $city || $region || $regional || $noteType) {
            $query->whereHas('Note', function ($note) use ($rubrica, $priority, $city, $region, $regional, $noteType) {
                $note->when($rubrica, fn ($q) => $q->whereIn('notes.rubrica', $rubrica))
                    ->when($priority, fn ($q) => $q->whereIn('notes.txpriority', $priority))
                    ->when($city, fn ($q) => $q->whereIn('notes.lexp', $city))
                    ->when($noteType, fn ($q) => $q->where('notes.type_note', $noteType))
                    ->when($region || $regional, fn ($q) => $q->whereHas('City', fn ($c) => $c
                        ->when($region, fn ($x) => $x->whereIn('regiao', $region))
                        ->when($regional, fn ($x) => $x->whereIn('regional', $regional))));
            });
        }

        return $query;
    }

    /**
     * Ids (chave => 0) dos arquivos de ADS tácita entre os anexos das notas da página, em uma consulta só,
     * para o `<x-files.select-download-list>` não consultar uma vez por linha.
     */
    protected function tacitFileIdsFor($lists): array
    {
        $ids = collect($lists->items())
            ->flatMap(fn ($viability) => $viability->Note?->Files?->pluck('id') ?? [])
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return \Illuminate\Support\Facades\DB::table('adsforms_files as af')
            ->join('adsforms as a', 'a.id', '=', 'af.adsform_id')
            ->whereIn('af.file_id', $ids)
            ->where('a.tacit', true)
            ->whereNotNull('a.work_report_id')
            ->pluck('af.file_id')
            ->flip()
            ->all();
    }

    public function clearAllCriteria(): void
    {
        $this->search = '';
        $this->clearListFilters();
    }

    protected function hasViabilityCriteria(): bool
    {
        return $this->hasListFilters() || trim((string) $this->search) !== '';
    }
}
