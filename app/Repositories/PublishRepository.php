<?php

namespace App\Repositories;

use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;

class PublishRepository
{
    /**
     * Retorna a consulta base para obter notas.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getBaseQuery(): Builder
    {
        return  Note::query()



        // Scope Local para Orders (Melhora a Legibilidade e Reusabilidade)
        ->whereHas('Orders', function ($q) {
            $q->where(function ($sq) {
                $sq->where(function ($s) {
                    $s->where('statusSist', 'LIKE', 'LIB%')
                      ->orWhere('statusSist', 'LIKE', 'ABER%');
                });
            })
            ->whereHas('Operations', function ($sq) {
                $sq->where('operacao', '0010')
                   ->where('status', 'like', 'CONF%');
            })
            ->whereHas('Operations', function ($sq) {
                $sq->where('operacao', '0020')
                   ->where(function ($s) {
                       $s->where('status', 'like', 'LIB%')
                         ->orWhere('status', 'like', 'CNPA%')
                         ->orWhere('status', 'like', 'JBFI LIB%');
                   });
            });
        });
    }
}
