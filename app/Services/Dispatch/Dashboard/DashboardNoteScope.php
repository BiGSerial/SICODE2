<?php

namespace App\Services\Dispatch\Dashboard;

use App\Models\City;

/**
 * Filtros de recorte do Dashboard de atividade (Grupo1, Grupo2, Grupo5, Rubrica, Região, Regional, Município): os mesmos da Lista
 * para Desenho, com a mesma regra (nota sem o campo preenchido continua aparecendo), para o dashboard e a lista baterem.
 * Atua nas colunas de `notes`; vale para a pilha e para a aderência (que junta `productions` com `notes`).
 */
class DashboardNoteScope
{
    public const KEYS = ['group1', 'group2', 'group5', 'rubrica', 'region', 'regional', 'city'];

    /** Só chaves conhecidas, só listas de textos. @return array<string, array<int, string>> */
    public static function clean(array $raw): array
    {
        $clean = [];

        foreach (self::KEYS as $key) {
            $values = array_values(array_unique(array_map('strval', array_filter((array) ($raw[$key] ?? []), 'is_scalar'))));

            if ($values) {
                $clean[$key] = $values;
            }
        }

        return $clean;
    }

    /** @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query */
    public static function apply($query, array $scope): void
    {
        $scope = self::clean($scope);

        foreach (['group1', 'group2', 'group5'] as $column) {
            if (isset($scope[$column])) {
                $query->where(fn ($q) => $q->whereIn("notes.{$column}", $scope[$column])->orWhere("notes.{$column}", '')->orWhereNull("notes.{$column}"));
            }
        }

        if (isset($scope['rubrica'])) {
            $query->where(fn ($q) => $q->whereIn('notes.rubrica', $scope['rubrica'])->orWhereNull('notes.rubrica'));
        }

        $cities = self::cities($scope);

        if ($cities !== null) {
            $query->where(fn ($q) => $q->whereIn('notes.nexp', $cities)->orWhere('notes.nexp', '')->orWhereNull('notes.nexp'));
        }
    }

    /** Municípios (`rdMunicipio`) que sobram de Região/Regional/Município; `null` sem filtro geográfico. @return array<int, string>|null */
    private static function cities(array $scope): ?array
    {
        if (!isset($scope['region']) && !isset($scope['regional']) && !isset($scope['city'])) {
            return null;
        }

        return City::query()
            ->when(isset($scope['city']), fn ($q) => $q->whereIn('rdMunicipio', $scope['city']))
            ->when(isset($scope['region']), fn ($q) => $q->whereIn('regiao', $scope['region']))
            ->when(isset($scope['regional']), fn ($q) => $q->whereIn('baseConstrucao', $scope['regional']))
            ->pluck('rdMunicipio')->filter()->unique()->values()->all();
    }
}
