<?php

namespace App\Services\Dispatchs;

use App\Custom\RuleBuilder;
use App\Models\Edp_depc\City;
use App\Models\Note;
use App\Models\Service;
use App\Models\User;
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;

class DesignDispatchMainQueryService
{
    public function build(Service $service, User $user, array $params = []): Builder
    {
        $query = Note::query()->excludeCanceledFullDone();

        if ($this->shouldBypassStatusFilter($params)) {
            SicodeRules::applyContractDispatchListVisibility($query, $user, $service->uuid);
        } else {
            SicodeRules::applyContractDispatchMainVisibility(
                $query,
                $user,
                $service->uuid,
                fn ($statusQuery) => RuleBuilder::applyRules($statusQuery, $service->Status)
            );
        }

        $multiSearch = array_values(array_filter((array) ($params['multiSearch'] ?? [])));

        if (count($multiSearch)) {
            $query->whereIn('note', $multiSearch);
        } else {
            $this->applyStandardFilters($query, $params, $service);
        }

        return $query->with(['Productions.User', 'Productions.Company'])
            ->orderBy('is45', 'DESC')
            ->orderBy('type_note', 'DESC')
            ->orderBy('days_left', 'ASC')
            ->orderBy('dt_status');
    }

    private function shouldBypassStatusFilter(array $params): bool
    {
        return (bool) ($params['bulkSearchAnyStatus'] ?? false)
            && count(array_filter((array) ($params['multiSearch'] ?? []))) > 0;
    }

    private function applyStandardFilters(Builder $query, array $params, Service $service): void
    {
        if (!empty($params['not_assigned'])) {
            $query->where(function ($q) use ($service) {
                $q->doesntHave('Productions')
                    ->orWhereDoesntHave('Productions', function ($subquery) use ($service) {
                        $subquery->where('service_id', $service->uuid)
                            ->where('confirmed', false);
                    });
            });
        }

        $filters = (array) ($params['filters'] ?? []);
        $group1 = $filters['group1'] ?? ($params['group1_s'] ?? []);
        $group2 = $filters['group2'] ?? ($params['group2_s'] ?? []);
        $group5 = $filters['group5'] ?? ($params['group5_s'] ?? []);
        $rubricas = $filters['rubrica'] ?? ($params['rubrica_s'] ?? []);
        $base = $this->municipioFilterValues($filters, $params);

        $query->when($params['search'] ?? null, function ($q, $s) {
            return $q->where(function ($query) use ($s) {
                $query->where('note', 'like', '%' . $s . '%')
                    ->orWhere('material', 'like', '%' . $s . '%')
                    ->orWhere('numPedido', 'like', '%' . $s . '%')
                    ->orWhere('group4', 'like', '%' . $s . '%')
                    ->orWhere('group5', 'like', '%' . $s . '%');
            });
        })->when($rubricas, function ($q) use ($rubricas) {
            return $q->where(function ($query) use ($rubricas) {
                $query->whereIn('rubrica', (array) $rubricas)
                    ->orWhereNull('rubrica');
            });
        })
            ->when($params['note_type'] ?? null, function ($q, $noteType) {
                return $q->where(function ($query) use ($noteType) {
                    $query->where('type_note', $noteType)
                        ->orWhereNull('type_note');
                });
            })
            ->when($group1, function ($q) use ($group1) {
                return $q->where(function ($query) use ($group1) {
                    return $query->whereIn('group1', (array) $group1)
                        ->orWhere('group1', '')
                        ->orWhere('group1', null);
                });
            })
            ->when($group2, function ($q) use ($group2) {
                return $q->where(function ($query) use ($group2) {
                    return $query->whereIn('group2', (array) $group2)
                        ->orWhere('group2', '')
                        ->orWhere('group2', null);
                });
            })
            ->when($group5, function ($q) use ($group5) {
                return $q->where(function ($query) use ($group5) {
                    return $query->whereIn('group5', (array) $group5)
                        ->orWhere('group5', '')
                        ->orWhere('group5', null);
                });
            })
            ->when($base, function ($q) use ($base) {
                return $q->where(function ($query) use ($base) {
                    return $query->whereIn('nexp', $base)
                        ->orWhere('nexp', '')
                        ->orWhere('nexp', null);
                });
            })
            ->when($params['only_27'] ?? false, function ($q) use ($service) {
                $q->where('days_left', '<=', 3)
                    ->whereHas(
                        'lastProduction',
                        fn ($r) => $r->where('service_id', $service->uuid)
                            ->where('confirmed', true)
                    );
            });
    }

    private function municipioFilterValues(array $filters, array $params): array
    {
        $cities = $filters['city'] ?? ($params['city_s'] ?? []);
        $regions = $filters['region'] ?? ($params['region_s'] ?? []);
        $districts = $filters['regional'] ?? ($filters['district'] ?? ($params['district_s'] ?? []));

        if (empty($cities) && empty($regions) && empty($districts)) {
            return [];
        }

        $query = City::query();

        if (!empty($cities)) {
            $query->whereIn('rdMunicipio', (array) $cities);
        }

        if (!empty($regions)) {
            $query->whereIn('regiao', (array) $regions);
        }

        if (!empty($districts)) {
            $query->whereIn('baseConstrucao', (array) $districts);
        }

        return $query->pluck('rdMunicipio')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
