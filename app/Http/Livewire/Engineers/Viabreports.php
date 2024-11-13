<?php

namespace App\Http\Livewire\Engineers;

use App\Exports\Engineers\NotRealized;
use App\Models\Company;
use App\Models\Viability;
use Livewire\Component;
use Carbon\Carbon;
use Livewire\WithPagination;

class Viabreports extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $companies;
    public $company_id;
    public $dt_in;
    public $dt_out;
    public $perPage = 15;
    // public $resume = [];
    protected $queryStrings = [
        'company_id' => ['except' => ''],
        'dt_in' => ['except' => ''],
        'dt_out' => ['except' => ''],
    ];

    public function exportExcel()
    {
        $data = $this->getViabilityProperty()->where('tacit', true)->where('completed', true)
                ->where(function ($q) {
                    $q->whereDoesntHave('Justification')
                        ->orWhereHas('Justification', function ($query) {
                            $query->where('dismissed', true);
                        });
                })->get();


        return (new NotRealized($data))->download(date('Ymdhms').'_EngineersNoteOVNotRealized.xlsx');
    }

    public function updatedDtIn()
    {
        $this->emit('sendDtInterval', $this->dt_in, $this->dt_out);
    }

    public function updatedDtOut()
    {
        $this->emit('sendDtInterval', $this->dt_in, $this->dt_out);
    }

    public function updatedCompanyId()
    {
        $this->emit('sendCompany', $this->company_id);
    }

    public function __construct()
    {
        $this->companies = Company::Has('Viabilies')
        ->when(!auth()->user()->superadm, function ($query) {
            $query->whereIn('id', auth()->user()->Companies->pluck('id'));
        })->orderBy('name')->get();

        $this->dt_in = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dt_out = Carbon::now()->endOfMonth()->format('Y-m-d');
    }


    public function getResume()
    {
        $resume['realized'] = $this->getViabilityProperty()->where('tacit', false)->where(function ($q) {
            $q->where('rejected', true)->orWhere('approved', true);
        })->sum('value');
        // $resume['realized'] = $this->getViabilityProperty()->where('tacit', false)->where('approved', true)->sum('value');
        $resume['notRealized'] = $this->getViabilityProperty()->where('tacit', true)->where('completed', true)
        ->where(function ($q) {
            $q->whereDoesntHave('Justification')
                ->orWhereHas('Justification', function ($query) {
                    $query->where('dismissed', true);
                });
        })
        ->sum('value');

        $resume['penalty'] = $resume['notRealized'] * 0.01;

        return $resume;
    }

    public function getViabilityProperty()
    {
        $query = Viability::query();

        if (!$this->dt_in && !$this->dt_out) {
            $this->dt_in = date('Y-m-01 00:00:00');
            $this->dt_out = date('Y-m-t 23:59:59');

            $query->whereBetween('sended_at', [date('Y-m-01 0:00:00', strToTime($this->dt_in)), date('Y-m-t 23:59:59', strToTime($this->dt_out))]);
        } else {
            $query->whereBetween('sended_at', [date('Y-m-01 0:00:00', strToTime($this->dt_in)), date('Y-m-t 23:59:59', strToTime($this->dt_out))]);
        }

        if ($this->company_id) {
            $query->where('company_id', $this->company_id);
        }

        return $query;
    }

    public function render()
    {
        return view('livewire.engineers.viabreports', [
            'resume' => $this->getResume(),
            'realizeds' =>  $this->getViabilityProperty()->where('tacit', false)->where(function ($q) {
                $q->where('rejected', true)->orWhere('approved', true);
            })->paginate($this->perPage, ['*'], 'realizedPage'),
            'notRealizeds' => $this->getViabilityProperty()->where('tacit', true)->where('completed', true)
            ->where(function ($q) {
                $q->whereDoesntHave('Justification')
                    ->orWhereHas('Justification', function ($query) {
                        $query->where('dismissed', true);
                    });
            })->paginate($this->perPage, ['*'], 'notRealizedPage'),
        ]);
    }
}
