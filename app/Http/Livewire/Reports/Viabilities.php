<?php

namespace App\Http\Livewire\Reports;

use App\Exports\ProductionExport;
use App\Exports\Reports\viabilityexport;
use App\Exports\Reports\viabilityQueryExport;
use App\Models\Production;
use App\Models\Viability;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Viabilities extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $column = 'sended_at';
    public $dt_init;
    public $dt_end;

    public $all = false;

    public function Export()
    {
        // return (new viabilityexport($this->lists->limit(5000)->get()))->download(date('YmdHis-') . 'exportViabilityHiring.xlsx');
        return (new viabilityQueryExport($this->getListsProperty()->with('Company', 'User', 'Note', 'Engineer')->get()->toArray()))->download(date('YmdHis-') . 'exportViabilityHiring.xlsx');
    }

    public function getListsProperty()
    {
        $query =  Viability::Query();

        if ($this->column && ($this->dt_init || $this->dt_end)) {

            if ($this->dt_init && !$this->dt_end) {
                $query->whereDate($this->column, '>=', $this->dt_init);
            }

            if (!$this->dt_init && $this->dt_end) {
                $query->whereDate($this->column, '<=', $this->dt_end);
            }

            if ($this->dt_init && $this->dt_end) {
                $query->whereBetween($this->column, [$this->dt_init, $this->dt_end]);
            }
        }

        return $query;

    }

    public function render()
    {
        return view('livewire.reports.viabilities', [
            'lists' => $this->lists->paginate(100),
        ]);
    }
}
