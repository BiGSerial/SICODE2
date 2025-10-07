<?php

namespace App\Services\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Closure;

class FilterRegistry
{
    public function __construct(
        protected array $config,
        protected array $state // valores atuais dos filtros
    ) {
    }

    public function getOptions(string $key): array
    {
        $def = $this->config[$key] ?? [];
        // Se já vier 'values' estático, só normalize:
        if (!empty($def['values'])) {
            return array_map(function ($row) {
                return [
                    'value' => $row['value'],
                    'label' => $row['label'],
                ];
            }, $def['values']);
        }

        // Caso via Model:
        $modelClass  = $def['model'] ?? null;
        $valueField  = $def['value_field'] ?? 'id';
        $labelField  = $def['label_field'] ?? 'name';
        if (!$modelClass || !class_exists($modelClass)) {
            return [];
        }

        // Hash de cache por config + estado relevante:
        $cacheKey = $this->makeCacheKey($key, $def);

        return Cache::remember($cacheKey, now()->addSeconds(20), function () use ($modelClass, $valueField, $labelField, $def) {
            /** @var Builder $q */
            $q = (new $modelClass())->newQuery()
                ->select([$valueField, $labelField])
                ->when(isset($def['query']) && $def['query'] instanceof Closure, function ($query) use ($def) {
                    return ($def['query'])($query, $this->state);
                })
                ->orderBy($labelField);

            // Evita trazer colunas desnecessárias:
            return $q->limit(2000)->get()
                ->map(fn ($r) => ['value' => $r->{$valueField}, 'label' => $r->{$labelField}])
                ->toArray();
        });
    }

    protected function makeCacheKey(string $key, array $def): string
    {
        // Inclui valores dos filtros dos quais depende para cache correto:
        $deps = Arr::get($def, 'depends_on', []);
        $depsState = [];
        foreach ((array) $deps as $d) {
            $depsState[$d] = $this->state[$d] ?? null;
        }

        return 'filter_opts:' . md5(json_encode([
            'k'     => $key,
            'deps'  => $depsState,
            // Opcionalmente inclua sinais da query (mas cuidado com closures):
            'qflag' => isset($def['query']),
        ]));
    }
}
