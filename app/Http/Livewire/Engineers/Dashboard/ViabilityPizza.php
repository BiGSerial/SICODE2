<?php

namespace App\Http\Livewire\Engineers\Dashboard;

use App\Models\Viability;
use Livewire\Component;
use Carbon\Carbon;

class ViabilityPizza extends Component
{
    public $startDate;
    public $endDate;
    public $company_id;
    public $companies;


    public function mount()
    {
        $this->companies = auth()->user()->Companies;

    }

    public function updated()
    {
        $totalViabilityStats = $this->getTotalViabilityStats();
        $this->dispatchBrowserEvent('updateGraphXX3', [
            'labels' => $totalViabilityStats['labels'],
            'data' => $totalViabilityStats['data'],
        ]);
    }

    public function getTotalViabilityStats()
    {


        $total = $this->getListsProperty()->count();

        $completed = $this->getListsProperty()->where('completed', true)->count();

        $inProgress = $this->getListsProperty()->where('status', 1)->count();

        $tacitTrue = $this->getListsProperty()->where('completed', true)->where('tacit', true)->count();

        $tacitFalse = $this->getListsProperty()->where('completed', true)->where('approved', true)->where('tacit', false)->count() + $this->getListsProperty()->where('completed', false)->where('rejected', true)->where('tacit', false)->count();

        $totalViabilityStats = [
            'labels' => ["Em Viabilidade ($inProgress)", "Não Realizado ($tacitTrue)", "Realizados ($tacitFalse)"],
            'data' => [$inProgress, $tacitTrue, $tacitFalse],
        ];

        return $totalViabilityStats;
    }

    public function getListsProperty()
    {

        $query = Viability::query();


        if ($this->startDate) {
            $this->startDate = Carbon::parse($this->startDate)->startOfDay()->toDateTimeString();
        }

        if ($this->endDate) {
            $this->endDate = Carbon::parse($this->endDate)->endOfDay()->toDateTimeString();
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('sended_at', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->where('sended_at', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('sended_at', '<=', $this->endDate);
        } else {
            $this->startDate = now()->startOfMonth()->startOfDay()->toDateTimeString();
            $this->endDate = now()->endOfMonth()->endOfDay()->toDateTimeString();
            $query->whereBetween('sended_at', [$this->startDate, $this->endDate]);
        }



        $query->when($this->company_id, function ($query) {

            $query->where('company_id', $this->company_id);
        }, function ($query) {

            if (auth()->user()->Companies->isNotEmpty()) {
                $query->whereIn('company_id', auth()->user()->Companies->pluck('id')->toArray());
            }

        });


        // if ($this->company_id) {
        //     $query->whereIn('company_id', auth()->user()->Companies->pluck('id')->toArray());
        // }

        return $query;
    }



    public function render()
    {
        return view('livewire.engineers.dashboard.viability-pizza', [
            'totalViabilityStats' => $this->getTotalViabilityStats()
        ]);
    }
}
