<?php

namespace App\Http\Livewire\Engineers\Dashboard;

use App\Models\Viability;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ViabilityProdList extends Component
{
    public $startDate;
    public $endDate;
    public $company_id;
    public $companies;


    public function mount()
    {
        $this->companies = auth()->user()->Companies;
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');

    }



    public function getListsProperty()
    {



        $query = Viability::query();


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


        if (!auth()->user()->superadm) {

            $query->whereIn('company_id', auth()->user()->Companies->pluck('id')->toArray());

        }

        $query
        ->with(['Company', 'Orders'])
        ->select(
            'viabilities.company_id',
            DB::raw('SUM(CASE WHEN viabilities.approved = true AND viabilities.tacit = false THEN 1 ELSE 0 END) as approved_completed'),
            DB::raw('SUM(CASE WHEN viabilities.rejected = true THEN 1 ELSE 0 END) as rejected_total'),
            DB::raw('SUM(CASE WHEN viabilities.tacit = true AND viabilities.completed = true THEN 1 ELSE 0 END) as tacit_total'),
            DB::raw('SUM(CASE WHEN viabilities.status = 1 THEN 1 ELSE 0 END) as status_1_total'),
            DB::raw('COUNT(viabilities.id) as total_records'),
        )
        ->orderBy('approved_completed', 'desc')
        ->groupBy('viabilities.company_id');



        return $query->get();
    }



    public function render()
    {
        return view('livewire.engineers.dashboard.viability-prod-list', [
            'lists' => $this->lists
        ]);
    }
}
