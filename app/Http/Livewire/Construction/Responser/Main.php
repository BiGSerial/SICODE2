<?php

namespace App\Http\Livewire\Construction\Responser;

use App\Custom\Viabilitiesstatus;
use App\Models\Company;
use App\Models\HiringWaiting;
use App\Models\Note;
use Livewire\Component;
use Livewire\WithPagination;

class Main extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;
    public $search;
    public $company;
    public $filterStatus =  [
        'column' => null,
        'value' => null
    ];
    public $filterResponser = false;


    public function getCountHiringProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            return $q->where('hired', true)
                    ->whereYear('hired_at', date('Y'))
                    ->whereMonth('hired_at', date('m'))
                    ->when(Auth()->User()->engineer, function ($sq) {
                        $sq->where('engineer_id', Auth()->User()->id);
                    });


        })->count();
    }

    public function setFilterStatus($status)
    {
        if ($status == 'hired') {

            $this->filterResponser = false;



            if (!$this->filterStatus['column']) {
                $this->filterStatus = [
                    'column' => 'hired',
                    'value' => true
                ];
            } else {
                $this->filterStatus =  [
                    'column' => null,
                    'value' => null
                ];
            }



        } elseif ($status == 'completed') {

            $this->filterResponser = false;

            if (!$this->filterStatus['column']) {
                $this->filterStatus = [
                    'column' => 'completed',
                    'value' => false
                ];
            } else {
                $this->filterStatus =  [
                    'column' => null,
                    'value' => null
                ];
            }

        } else {
            $this->filterStatus =  [
                'column' => null,
                'value' => null
            ];
            if (!$this->filterResponser) {
                $this->filterResponser = true;
            } else {
                $this->filterResponser = false;
            }
        }


    }

    public function searching()
    {
        $this->gotoPage(1);
    }

    public function getEvolutionHiringProperty()
    {
        $actual = $this->countHiring;
        $past = Note::whereRelation('Viabilities', function ($q) {
            return $q->where('hired', true)
                    ->whereYear('hired_at', date('Y'))
                    ->whereMonth('hired_at', date('m') - 1)
                    ->when(Auth()->User()->engineer, function ($sq) {
                        $sq->where('engineer_id', Auth()->User()->id);
                    });
        })->count();

        if ($past != 0) {
            return round((($actual - $past) / $past) * 100, 2);
        } else {
            return $actual;
        }
    }

    public function getCountViabilityProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            return $q->where('completed', false)
                    ->whereYear('sended_at', date('Y'))
                    ->whereMonth('sended_at', date('m'))
                    ->when(Auth()->User()->engineer, function ($sq) {
                        $sq->where('engineer_id', Auth()->User()->id);
                    });


        })->count();
    }

    public function getEvolutionViabilityProperty()
    {
        $actual = $this->countViability;
        $past = Note::whereRelation('Viabilities', function ($q) {
            return $q->where('hired', true)
                    ->whereYear('hired_at', date('Y'))
                    ->whereMonth('hired_at', date('m') - 1)
                    ->when(Auth()->User()->engineer, function ($sq) {
                        $sq->where('engineer_id', Auth()->User()->id);
                    });
        })->count();

        if ($past != 0) {
            return round((($actual - $past) / $past) * 100, 2);
        } else {
            return $actual;
        }
    }

    public function getListHiringProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            return $q->whereYear('sended_at', date('Y'))
                    ->whereMonth('sended_at', date('m'))
                    ->when($this->company, function ($sq) {
                        $sq->where('company_id', $this->company);
                    })
                    ->when(Auth()->User()->engineer, function ($sq) {
                        $sq->where('engineer_id', Auth()->User()->id);
                    })
                    ->when($this->filterStatus['column'], function ($q) {
                        $q->where($this->filterStatus['column'], $this->filterStatus['value']);
                    })
                    ->when($this->filterResponser, function ($q) {
                        $q->where('status', 4);
                    });


        })
        ->with(['Viabilities' => function ($q) {
            return $q->whereYear('sended_at', date('Y'))
            ->whereMonth('sended_at', date('m'))
            ->when(Auth()->User()->engineer, function ($sq) {
                $sq->where('engineer_id', Auth()->User()->id);
            })->when($this->company, function ($sq) {
                $sq->where('company_id', $this->company);
            })
            ->when($this->filterStatus['column'], function ($q) {
                $q->where($this->filterStatus['column'], $this->filterStatus['value']);
            })
            ->when($this->filterResponser, function ($q) {
                $q->where('status', 4);
            });
            ;
        }])
        ->when(trim($this->search), function ($q) {
            $q->where(function ($sq) {
                $sq->where('note', 'like', "%".trim($this->search)."%")
                    ->orWhere('rubrica', 'like', "%".trim($this->search)."%")
                    ->orWhere('lexp', 'like', "%".trim($this->search)."%")
                    ->orWhereRelation('Orders', 'ordem', 'like', "%".trim($this->search)."%");
            });
        })


        ->when($this->filterResponser, function ($q) {
            $q->whereRelation('Viabilities', 'status', 4);
        })

        ->paginate($this->perPage);
    }

    public function getListResponsersProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            return $q->whereYear('sended_at', date('Y'))
                    ->whereMonth('sended_at', date('m'))
                    ->where('rejected', true)
                    ->where('completed', false)
                    ->when(Auth()->User()->engineer, function ($sq) {
                        $sq->where('engineer_id', Auth()->User()->id);
                    });

        })->get();
    }

    public function getCountResponsersProperty()
    {
        return Note::whereRelation('Viabilities', function ($q) {
            return $q->whereYear('sended_at', date('Y'))
                    ->whereMonth('sended_at', date('m'))
                    ->where('rejected', true)
                    ->when(Auth()->User()->engineer, function ($sq) {
                        $sq->where('engineer_id', Auth()->User()->id);
                    });


        })
        ->count();
    }

    public function getWaitingListsProperty()
    {
        $query = HiringWaiting::Query();
        $query->where('complete', false)
            ->when($this->search, function ($q) {
                $q->whereRelation('Note', 'note', 'like', "%".$this->search."%");
            })
            ->orderBy('created_at', 'DESC');



        return $query->get();

    }

    public function getCompaniesProperty()
    {
        return Company::orderBy('name')->get();
    }


    public function render()
    {
        return view('livewire.construction.responser.main', [
            'countHiring' => $this->countHiring,
            'countViability' => $this->countViability,
            'evolutionHiring' => $this->evolutionHiring,
            'evolutionViability' => $this->evolutionViability,
            'listResponsers' => $this->listResponsers,
            'countResponsers' => $this->countResponsers,
            'waitingLists' => $this->waitingLists,
            'companies' => $this->companies,
            'lists' => $this->listHiring,
        ]);
    }
}
