<?php

namespace App\Services\Quality;

use App\Models\{City, Note};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Filtros por dados da Nota (rubrica, região, regional, município, localização, grupos, status, datas), no mesmo
 * sentido dos filtros do despacho de serviços. Usados no Pool, nas filas e no histórico.
 */
class QualityNoteFilters
{
    /** parâmetro => rótulo (colunas de `notes` filtradas por IN). */
    public const COLUMNS = [
        'rubrica' => 'Rubrica',
        'nstats'  => 'Status da Nota',
        'lexp'    => 'Localização',
        'group1'  => 'Grupo 1',
        'group2'  => 'Grupo 2',
        'group5'  => 'Grupo 5',
    ];

    /** parâmetros derivados de `cities` (mapeiam para notes.nexp). */
    public const GEO = ['region' => 'Região', 'regional' => 'Regional', 'city' => 'Município'];

    public function apply(Builder $notes, array $input): Builder
    {
        foreach (array_keys(self::COLUMNS) as $key) {
            if ($values = $this->list($input[$key] ?? null)) {
                $notes->whereIn($key, $values);
            }
        }

        $cities = City::query();
        $geo    = false;

        if ($values = $this->list($input['region'] ?? null)) {
            $cities->whereIn('regiao', $values);
            $geo = true;
        }

        if ($values = $this->list($input['regional'] ?? null)) {
            $cities->whereIn('baseConstrucao', $values);
            $geo = true;
        }

        if ($values = $this->list($input['city'] ?? null)) {
            $cities->whereIn('rdMunicipio', $values);
            $geo = true;
        }

        if ($geo) {
            $notes->whereIn('nexp', $cities->pluck('rdMunicipio')->filter()->unique()->values()->all());
        }

        return $notes
            ->when($input['dt_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('dt_status', '>=', $date))
            ->when($input['dt_to'] ?? null, fn (Builder $q, $date) => $q->whereDate('dt_status', '<=', $date))
            ->when(is_numeric($input['days_left_max'] ?? null), fn (Builder $q) => $q->where('days_left', '<=', (int) $input['days_left_max']));
    }

    public function active(array $input): bool
    {
        foreach ([...array_keys(self::COLUMNS), ...array_keys(self::GEO)] as $key) {
            if ($this->list($input[$key] ?? null)) {
                return true;
            }
        }

        return !empty($input['dt_from']) || !empty($input['dt_to']) || is_numeric($input['days_left_max'] ?? null);
    }

    /** @return array<string, array<int, string>> opções dos filtros (cache curto). */
    public function options(): array
    {
        return Cache::remember('quality.note-filter-options', 600, function () {
            $options = [];

            foreach (array_keys(self::COLUMNS) as $column) {
                $options[$column] = Note::query()->where('type_note', 1)->whereNotNull($column)->where($column, '!=', '')->distinct()->orderBy($column)->limit(400)->pluck($column)->map(fn ($v) => (string) $v)->all();
            }
            $options['region']   = City::query()->whereNotNull('regiao')->distinct()->orderBy('regiao')->pluck('regiao')->all();
            $options['regional'] = City::query()->whereNotNull('baseConstrucao')->distinct()->orderBy('baseConstrucao')->pluck('baseConstrucao')->all();
            $options['city']     = City::query()->whereNotNull('rdMunicipio')->distinct()->orderBy('rdMunicipio')->limit(600)->pluck('rdMunicipio')->all();

            return $options;
        });
    }

    /** @return array<int, string> */
    private function list(mixed $value): array
    {
        return array_values(array_filter(array_map('strval', (array) $value), fn ($v) => $v !== ''));
    }
}
