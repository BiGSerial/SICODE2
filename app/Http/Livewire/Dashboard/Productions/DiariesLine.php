<?php

namespace App\Http\Livewire\Dashboard\Productions;

use App\Models\Production;
use App\Models\Service;
use Livewire\Component;

class DiariesLine extends Component
{
    public $service;
    public $data;

    public function mount($service)
    {
        $this->service = $service;
        $this->sendDespatch();
    }


    public function getProductionsProperty()
    {
        $query = Production::query();

        $currentMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $query->selectRaw('DATE(completed_at) as date, COUNT(*) as count, SUM(postes_u) as total_postes_u')
              ->whereBetween('completed_at', [$currentMonth, $endOfMonth])
              ->groupBy('date');

        $previousMonthStart = $currentMonth->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $currentMonth->copy()->subMonth()->endOfMonth();

        $previousQuery = Production::selectRaw('DATE(completed_at) as date, COUNT(*) as count, SUM(postes_u) as total_postes_u')
                       ->whereBetween('completed_at', [$previousMonthStart, $previousMonthEnd])
                       ->groupBy('date');


        return [
            'currentMonth' => $query->get(),
            'previousMonth' => $previousQuery->get(),
        ];
    }

    public function sendDespatch()
    {


        $currentMonth = $this->getProductionsProperty()['currentMonth'];
        $previousMonth = $this->getProductionsProperty()['previousMonth'];

        $this->data = [
            'labels' =>  $currentMonth->pluck('date')->toArray(),
            'presentDataNotes' => $currentMonth->pluck('count')->toArray(),
            'presentDataPostes' => $currentMonth->pluck('total_postes_u')->map(function ($value) {
                return (int) $value;
            })->toArray(),
            'previousDataNotes' => $previousMonth->pluck('count')->toArray(),
            'previousDataPostes' => $previousMonth->pluck('total_postes_u')->map(function ($value) {
                return (int) $value;
            })->toArray(),
        ];



        $this->dispatchBrowserEvent('sendData001', $this->data);
    }



    public function render()
    {
        $this->sendDespatch();

        return view('livewire.dashboard.productions.diaries-line');
    }
}
