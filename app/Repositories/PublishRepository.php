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
    public function getBaseQuery(bool $all_services = false): Builder
    {
        $query = Note::query()->excludeCanceledFullDone();

        if (!$all_services) {
            $query->whereHas('Orders', function ($q) {
                $q->where(function ($sq) {
                    $sq->where(function ($s) {
                        $s->where('statusSist', 'LIKE', 'LIB%');
                        //   ->orWhere('statusSist', 'LIKE', 'ABER%');  // NOTE: Alteração no filtro solicitado pela Suelly em 24/09/2025
                    });
                })
                // ->whereHas('Operations', function ($sq) { // NOTE: Trecho comentado a pedido do Márcio Costalonga em 23/09/2024
                //     $sq->where('operacao', '0010')
                //        ->where('status', 'like', 'CONF%');
                // })
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

        return $query;

    }
}
