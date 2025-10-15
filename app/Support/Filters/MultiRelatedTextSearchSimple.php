<?php

namespace App\Support\Filters;

use Illuminate\Database\Eloquent\Builder;
use App\Support\Filters\Contracts\AppliesToQuery; // se não tiver, troque por uma interface sua ou remova
use App\Traits\WildcardFormatter;

class MultiRelatedTextSearchSimple implements AppliesToQuery
{
    use WildcardFormatter;

    /**
     * @param array<string, array<int, string>> $relations Ex.: ['Note'=>['note','lexp'], 'Entity.Type'=>['name']]
     * @param array<int, string> $localCols Ex.: ['externals.status']
     * @param int $minLen Tamanho mínimo para disparar o search
     */
    public function __construct(
        private array $relations,
        private array $localCols = [],
        private int $minLen = 2
    ) {
    }

    public function apply(Builder $query, $value): Builder
    {
        $term = trim((string) $value);
        if (mb_strlen($term) < $this->minLen) {
            return $query;
        }

        $fmt = $this->formatWithWildcard($term); // ->search, ->type ('=' | 'like')

        return $query->where(function (Builder $w) use ($fmt) {
            $first = true;

            // 1) Colunas locais
            foreach ($this->localCols as $col) {
                $first
                    ? $w->where($col, $fmt->type, $fmt->search)
                    : $w->orWhere($col, $fmt->type, $fmt->search);
                $first = false;
            }

            // 2) Relações (aceita notação com ponto: 'Entity.Type')
            foreach ($this->relations as $relation => $cols) {
                $method = $first ? 'whereHas' : 'orWhereHas';
                $w->{$method}($relation, function (Builder $rel) use ($cols, $fmt) {
                    $rel->where(function (Builder $rw) use ($cols, $fmt) {
                        foreach ((array) $cols as $i => $col) {
                            $rw->{$i ? 'orWhere' : 'where'}($col, $fmt->type, $fmt->search);
                        }
                    });
                });
                $first = false;
            }
        });
    }
}
