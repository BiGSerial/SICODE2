<?php

namespace App\Http\Livewire\Engineers\Dashboard;

use App\Models\Company;
use App\Models\Viability;
use Livewire\Component;
use Carbon\Carbon;

class ViabilityPizza extends Component
{
    public $startDate;
    public $endDate;
    public $company_id;
    public $companies;


    protected $listeners = [
        'refreshViability' => '$refresh',
        'sendDtInterval' => 'setInterval',
        'sendCompany' => 'setCompany',
    ];



    public function mount()
    {
        $this->companies = !auth()->user()->superadm ? auth()->user()->Companies : Company::whereHas('Viabilies')->orderBy('name')->get();

        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');

    }

    public function upd()
    {
        $this->updated();
    }


    public function setInterval($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        $this->updated();
    }

    public function setCompany($company_id = null)
    {
        $this->company_id = $company_id;

        $this->updated();
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


        $baseQuery = $this->getListsProperty();

        $total = (clone $baseQuery)->count();

        $completed = (clone $baseQuery)->where('completed', true)->count();

        $inProgress = (clone $baseQuery)
            ->where('sended_at', '>=', Carbon::parse($this->startDate)->subDays(7)->startOfDay()->toDateTimeString())
            ->where('status', 1)
            ->count();

        $tacitTrue = (clone $baseQuery)->where('completed', true)->where('tacit', true)->count();

        $tacitFalse = (clone $baseQuery)->where('completed', true)
            ->where('approved', true)
            ->where('tacit', false)
            ->count() + (clone $baseQuery)->where('completed', false)
            ->where('rejected', true)
            ->where('tacit', false)
            ->count();

        $totalViabilityStats = [
            'labels' => ["Em Viabilidade ($inProgress)", "Não Realizado ($tacitTrue)", "Realizados ($tacitFalse)"],
            'data' => [$inProgress, $tacitTrue, $tacitFalse],
        ];



        return $totalViabilityStats;
    }

    public function getListsProperty()
    {

        $query = Viability::query();


        // if ($this->startDate) {
        //     $startDate = Carbon::parse($this->startDate)->startOfDay()->toDateTimeString();
        // }

        // if ($this->endDate) {
        //     $endDate = Carbon::parse($this->endDate)->endOfDay()->toDateTimeString();
        // }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('sended_at', [Carbon::parse($this->startDate)->startOfDay()->toDateTimeString(), Carbon::parse($this->endDate)->endOfDay()->toDateTimeString()]);
        } elseif ($this->startDate) {
            $query->where('sended_at', '>=', Carbon::parse($this->startDate)->startOfDay()->toDateTimeString());
        } elseif ($this->endDate) {
            $query->where('sended_at', '<=', Carbon::parse($this->endDate)->endOfDay()->toDateTimeString());
        } else {
            $startDate = now()->startOfMonth()->startOfDay()->toDateTimeString();
            $endDate = now()->endOfMonth()->endOfDay()->toDateTimeString();
            $query->whereBetween('sended_at', [$startDate, $endDate]);
        }



        $query->when($this->company_id, function ($query) {

            $query->where('company_id', $this->company_id);
        }, function ($query) {

            if (auth()->user()->Companies->isNotEmpty()) {
                $query->whereIn('company_id', auth()->user()->Companies->pluck('id')->toArray());
            }

        });




        return $query;
    }



    public function render()
    {
        return view('livewire.engineers.dashboard.viability-pizza', [
            'totalViabilityStats' => $this->getTotalViabilityStats()
        ]);
    }
}
