<?php

namespace App\Repositories;

use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;

class ApprovalsRepository
{
    /**
     * Retorna a consulta base para obter notas.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getBaseQuery(): Builder
    {
        $query = Note::query()->excludeCanceledFullDone();

        $query->where(function ($query) {
            $query->where(function ($qq) {
                $qq->whereIn('nstats', [46, 47, 48, 49, 50])
                ->whereNotIn('rubrica', ['Incoporação'])

                ->where('type_note', 2);
            })
            ->orWhere(function ($qq) {
                $qq->where('type_note', 1)
                ->where('centerjob', 'like', 'VIAB%')
                ->orWhere(function ($qq) {
                    $qq->orWhereNull('centerjob')
                    ->where('type_note', 1);
                });
            });
        })
        ->whereHas('Orders', function ($q) {
            $q->where('statusSist', 'not like', 'ENTE%')
                  ->where('statusSist', 'not like', 'ENCE%')
                  ->whereHas('Operations', function ($sq) {
                      $sq->where('operacao', '0010')
                         ->where('status', 'like', 'ABER%');
                  });
        })
        ->where(function ($q) {
            $q->whereDoesntHave('Approval')
            ->whereDoesntHave('Viabilities')
            ->whereDoesntHave('Waitings');
        })
        ->where(function ($q) {
            $q->where('txpriority', '!=', 'Emergente')
              ->orWhereNull('txpriority');

        })
        ->Where('pze', '!=', '25')
        ->with([
           'orders' => function ($q) {
               $q->where('statusSist', 'not like', 'ENT%')
                   ->where('statusSist', 'not like', 'ENC%')
                   ->orderBy('ordem');
           },
           'orders.operations' => function ($q) {
               $q->where('operacao', '0010');
           },
        ]);


        return $query;

    }
}
