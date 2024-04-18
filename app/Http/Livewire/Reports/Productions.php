<?php

namespace App\Http\Livewire\Reports;

use App\Exports\ProductionExport;
use App\Models\Production;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Productions extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $monthYear;
    public $company;
    public $service;
    public $dt_init;
    public $dt_end;
    public $complete = false;
    public $cities;
    public $search = false;

    protected $registers;

    protected $queryStrings = [
        'page'
    ];

    public function updatedCompany()
    {

        $this->gotoPage(1);
    }

    public function updatedService()
    {
        $this->gotoPage(1);
    }

    public function updatedMonthYear()
    {

        $this->dt_init = '';
        $this->dt_end = '';
        $this->gotoPage(1);

    }

    public function Export()
    {
        return (new ProductionExport())->reports($this->lists->limit(10000)->get())->download(date('YmdHis-').'producao.xlsx');
    }

    public function Search()
    {
        $this->search = true;
    }

    public function getListsProperty()
    {
        // if (!$this->monthYear && !$this->dt_init && !$this->dt_end) {
        //     $query = Production::query();
        //     $query->where('rejected', false);

        //     if (!$this->complete) {
        //         $query->where('completed', true);
        //     }

        //     if (auth()->user()->contract) {
        //         $query->where('company_id', auth()->user()->employee->contract->company_id);
        //     }

        //     $query->with('User', 'Company', 'Service', 'Note', 'Analise')
        //         ->orderBy('completed_at');

        //     return $query;
        // }

        $query = Production::query();
        $query->where('rejected', false);

        if (!$this->complete) {
            $column = 'completed_at';
            $query->where('completed', true);
        } else {
            $column = 'completed_at';
        }

        if (auth()->user()->contract) {
            $query->where('company_id', auth()->user()->employee->contract->company_id);
        }

        if ($this->monthYear) {
            $startDate = Carbon::parse($this->monthYear)->startOfMonth();
            $endDate = Carbon::parse($this->monthYear)->endOfMonth();
            $query->where(function ($q) use ($column, $startDate, $endDate) {
                if ($this->complete) {
                    $q->whereBetween($column, [$startDate, $endDate])
                        ->orWhere('completed', false);
                } else {
                    $q->whereBetween($column, [$startDate, $endDate]);
                }

            });

        }

        if ($this->service) {
            $query->where('service_id', $this->service);
        }

        if ($this->dt_init) {

            $query->where(function ($q) use ($column) {
                if ($this->complete) {
                    $q->where($column, '>=', date('Y-m-d 0:00:00', strtotime($this->dt_init)))
                        ->orWhere('completed', false);
                } else {
                    $q->where($column, '>=', date('Y-m-d 0:00:00', strtotime($this->dt_init)));
                }

            });

        }

        if ($this->dt_end) {
            $query->where(function ($q) use ($column) {
                if ($this->complete) {
                    $q->where($column, '<=', date('Y-m-d 23:59:59', strtotime($this->dt_end)))
                        ->orWhere('completed', false);
                } else {
                    $q->where($column, '<=', date('Y-m-d 23:59:59', strtotime($this->dt_end)));
                }

            });
        }

        if ($this->company) {
            $query->where('company_id', $this->company);
        }

        $query->with('User', 'Company', 'Service', 'Note', 'Analise')
            ->orderBy('completed_at');

        return $query;

    }

    public function getMonthYearList()
    {
        $reports = Production::select('confirmed_at')->get();

        $groupedReports = $reports->groupBy(function ($item) {
            return Carbon::parse($item->confirmed_at)->format('Y-m');
        });

        $formattedList = $groupedReports->map(function ($groupedItems, $key) {
            $date = Carbon::parse($key)->format('Y-m');
            $desc = Carbon::parse($key)->format('Y F');

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
        // Verifique se o botão Gerar foi clicado
        if ($this->search) {
            $this->search = false;

        }

        return view('livewire.reports.productions', [
            'month_list' => $this->getMonthYearList(),
            'company_list' => $this->getCompanyList(),
            'service_list' => $this->getServiceList(),
            'lists' => $this->lists->paginate(100),
        ]);
    }
}
