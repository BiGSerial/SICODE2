<?php

namespace App\Http\Livewire\Engineers\Counts\Analises;

use App\Models\Note;
use Livewire\Component;

class ToApprovalCount extends Component
{
    public function getCountProperty()
    {
        $query = Note::query();

        $query->where(function ($query) {
            $query->where(function ($qq) {
                $qq->whereIn('nstats', [46, 47, 48, 49, 50])
                ->whereNotIn('rubrica', ['Incoporação'])
                 ->Where('pze', '!=', 25)
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



        return $query->count();
    }

    public function render()
    {
        return view('livewire.engineers.counts.analises.to-approval-count', [
            'count' => $this->count
        ]);
    }
}
