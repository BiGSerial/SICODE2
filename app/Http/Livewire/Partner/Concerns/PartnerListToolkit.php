<?php

namespace App\Http\Livewire\Partner\Concerns;

use App\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Peças comuns das listas de controle do portal da parceira (informes, parciais, equipamentos):
 * termos de busca, opções do filtro Empresa (escopo de permissão), Rubrica/Região/Município e arquivos de ADS tácita.
 * Requer `WithListFilters` e `AuthorizesPartnerAccess` no componente.
 */
trait PartnerListToolkit
{
    /** @return array<int, string> */
    protected function parseSearchTerms(?string $value): array
    {
        if (!filled($value)) {
            return [];
        }

        return collect(preg_split('/[\s,;]+/', trim($value)))
            ->map(fn ($term) => trim($term))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Empresas que o usuário enxerga (matriz e filiais liberadas), como opções do filtro. */
    protected function companyFilterOptions(): array
    {
        return $this->partnerCompanyFilterOptions()
            ->map(fn ($company) => ['v' => (string) $company->id, 'l' => (string) $company->name])
            ->values()
            ->all();
    }

    /** O filtro Empresa só reduz o escopo de permissão já aplicado por `applyPartnerCompanyScope` (nunca o amplia). */
    protected function applyCompanyFilter(Builder $query, string $column = 'company_id'): Builder
    {
        $ids = $this->filterValues('company');

        return $ids ? $query->whereIn($column, $ids) : $query;
    }

    /**
     * Rubrica / Região / Município das notas que têm informe (consulta limitada aos informes, não a todas as notas), em cache.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function reportLocationFilterOptions(): array
    {
        $cities = $this->rememberFilterOptions('cities', fn () => City::query()
            ->whereNotNull('cidade')
            ->where('cidade', '!=', '')
            ->orderBy('cidade')
            ->get(['cidade', 'regiao'])
            ->unique('cidade')
            ->map(fn ($c) => ['v' => (string) $c->cidade, 'l' => (string) $c->cidade, 'p' => ['region' => (string) $c->regiao]])
            ->values()
            ->all());

        return [
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => DB::table('work_reports as w')
                ->join('notes as n', 'n.id', '=', 'w.note_id')
                ->whereNotNull('n.rubrica')->where('n.rubrica', '!=', '')
                ->distinct()->orderBy('n.rubrica')->pluck('n.rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
            'region' => collect($cities)->pluck('p.region')->filter()->unique()->sort()->values()
                ->map(fn ($r) => ['v' => $r, 'l' => $r])->all(),
            'city' => $cities,
        ];
    }

    /**
     * Rubrica / Região / Município sobre a nota alcançada por `$noteRelation` ('Note' em WorkReport/Partial, 'WorkReport.Note' em Equipment).
     */
    protected function applyNoteLocationFilters(Builder $query, string $noteRelation = 'Note'): Builder
    {
        if ($rubricas = $this->filterValues('rubrica')) {
            $query->whereRelation($noteRelation, fn ($q) => $q->whereIn('rubrica', $rubricas));
        }

        if ($regions = $this->filterValues('region')) {
            $query->whereRelation($noteRelation . '.City', fn ($q) => $q->whereIn('regiao', $regions));
        }

        if ($cities = $this->filterValues('city')) {
            $query->whereRelation($noteRelation, fn ($q) => $q->whereIn('lexp', $cities));
        }

        return $query;
    }

    /**
     * Ids dos arquivos de ADS tácita entre os arquivos da página, numa consulta só (chave = id), para o `<x-files.select-download-list>`
     * não consultar por linha.
     *
     * @param iterable<int, int|string> $fileIds
     *
     * @return array<int|string, int>
     */
    protected function tacitFileIdsFor(iterable $fileIds): array
    {
        $ids = collect($fileIds)->unique()->values()->all();

        if (!$ids) {
            return [];
        }

        return DB::table('adsforms_files as af')
            ->join('adsforms as a', 'a.id', '=', 'af.adsform_id')
            ->whereIn('af.file_id', $ids)
            ->where('a.tacit', true)
            ->whereNotNull('a.work_report_id')
            ->pluck('af.file_id')
            ->flip()
            ->all();
    }
}
