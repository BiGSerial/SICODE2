<?php

namespace App\Http\Livewire\Reports;

use App\Exports\ProductionExport;
use App\Models\Production;
use Carbon\Carbon;
use Livewire\Component;

class Productions extends Component
{
    public $lists;
    public $monthYear; // Usaremos uma única propriedade para mês e ano no formato 'Y-m'
    public $company;
    public $service;
    public $dt_init;
    public $dt_end;
    public $complete = false;
    public $cities;




    public function Export()
    {
        return (new ProductionExport())->reports($this->lists)->download(date('YmdHis-').'producao.xlsx');
    }

    public function Search()
    {
        $this->lists = null;

        if (!$this->monthYear && !$this->dt_init && !$this->dt_end) {
            return;
        }

        if (!$this->complete) {
            $column = 'completed_at';
        } else {
            $column = 'att_at';
        }

        $this->lists = $this->reports->load(['User', 'Company', 'Service', 'Note', 'Analise', 'Dispatcher.Employee.Contract.company'])
                ->when($this->monthYear, function ($q) use ($column) {
                    $startDate = Carbon::parse($this->monthYear)->startOfMonth();
                    $endDate = Carbon::parse($this->monthYear)->endOfMonth();

                    return $q->whereBetween($column, [$startDate, $endDate]);
                })
                ->when($this->service, function ($q) use ($column) {
                    return $q->where('service_id', $this->service);
                })
                ->when($this->dt_init, function ($q) use ($column) {
                    return $q->where($column, '>=', date('Y-m-d 0:00:00', strToTime($this->dt_init)));
                })
                ->when($this->dt_end, function ($q) use ($column) {
                    return $q->where($column, '<=', date('Y-m-d 23:59:59', strToTime($this->dt_end)));
                })
                ->when($this->company, function ($q) use ($column) {
                    return $q->where('company_id', $this->company);
                });





    }

    public function getReportsProperty()
    {
        $query = Production::query();

        if (!$this->complete) {
            $query->where('completed', true);
        }

        $query->where('rejected', false)
        ->when(Auth()->User()->contract, function ($q) {
            return $q->where('company_id', Auth()->User()->Employee->Contract->company_id);
        })
        ->orderBy('completed_at');


        return $query->get();
    }

    public function getMonthYearList()
    {
        $reports = $this->reports;

        $groupedReports = $reports->groupBy(function ($item) {
            return Carbon::parse($item->confirmed_at)->format('Y-m');
        });

        $formattedList = $groupedReports->map(function ($groupedItems, $key) {
            $date = Carbon::parse($key)->format('Y-m');
            $desc = Carbon::parse($key)->format('F Y');

            return ['date' => $date, 'desc' => $desc];
        });

        return collect($formattedList);
    }

    public function getCompanyList()
    {
        return Production::select('company_id')->with('Company')->groupBy('company_id')->get();
    }

    public function getServiceList()
    {
        return Production::select('service_id')->with('Service')->groupBy('Service_id')->get();
    }

    public function render()
    {
        return view('livewire.reports.productions', [
            'month_list' => $this->getMonthYearList(),
            'company_list' => $this->getCompanyList(),
            'service_list' => $this->getServiceList()
        ]);
    }
}
