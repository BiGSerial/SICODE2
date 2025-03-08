<?php

namespace App\Http\Livewire\Engineers\Analises\Dashboard;

use App\Models\Company;
use App\Models\Form;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Pizza extends Component
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
        $this->companies = !auth()->user()->superadm ? auth()->user()->Companies : Company::orderBy('name')->get();

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
        $totalRejectstasts = $this->gettotalRejectstasts();
        $this->dispatchBrowserEvent('updateGraphXX4', [
            'labels' => $totalRejectstasts['labels'],
            'data' => $totalRejectstasts['data'],
        ]);
    }

    public function getTotalRejectstasts()
    {
        $values = $this->getListsProperty()
                ->where('rejected', 1)
                ->select('reason', DB::raw('count(*) as total'))
                ->groupBy('reason')
                ->orderBy('total', 'desc')
                ->get();



        $totalRejectstasts = [
            'labels' => $values->pluck('reason')->toArray(),
            'data' => $values->pluck('total')->toArray(),
        ];

        return $totalRejectstasts;
    }

    public function getListsProperty()
    {

        $query = Form::query();


        // if ($this->startDate) {
        //     $startDate = Carbon::parse($this->startDate)->startOfDay()->toDateTimeString();
        // }

        // if ($this->endDate) {
        //     $endDate = Carbon::parse($this->endDate)->endOfDay()->toDateTimeString();
        // }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [Carbon::parse($this->startDate)->startOfDay()->toDateTimeString(), Carbon::parse($this->endDate)->endOfDay()->toDateTimeString()]);
        } elseif ($this->startDate) {
            $query->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay()->toDateTimeString());
        } elseif ($this->endDate) {
            $query->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay()->toDateTimeString());
        } else {
            $startDate = now()->startOfMonth()->startOfDay()->toDateTimeString();
            $endDate = now()->endOfMonth()->endOfDay()->toDateTimeString();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }



        $query->when($this->company_id, function ($query) {

            $query->whereRelation('viability', 'company_id', $this->company_id);
        }, function ($query) {

            if (auth()->user()->Companies->isNotEmpty()) {
                $query->whereRelation('viability', function ($query) {
                    $query->whereIn('company_id', auth()->user()->Companies->pluck('id')->toArray());
                });
            }

        });




        return $query;
    }



    public function render()
    {
        return view('livewire.engineers.analises.dashboard.pizza', [
            'totalRejectstasts' => $this->getTotalRejectstasts()
        ]);
    }
}
