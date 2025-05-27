<?php

namespace App\Http\Livewire\Btzero;

use App\Exports\SMC\Smcexport;
use App\Exports\SMC\SmcListExport;
use App\Models\Company;
use App\Models\Note;
use Livewire\Component;
use Livewire\WithPagination;

class Listreports extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $selected;
    public $search;
    public $companies;
    public $company;

    protected $queryString = [
            'search' => ['except' => ''],
            'company' => ['except' => ''],
        ];

    public function mount()
    {
        $this->companies = Company::orderBy('name')->get();
    }

    public function export_excel()
    {
        return (new Smcexport($this->getListsProperty()->with('Note', 'Company, BtzeroEquipment', 'ReturnRamal')))->download(date('ymdhis').'-smc.xlsx');
    }


    public function getListsProperty()
    {
        return Note::whereHas('RamalForm', function ($q) {
            $q->when($this->company, function ($sq) {
                $sq->where('company_id', $this->company);
            });
        })
        ->where(function ($q) {
            $q->where(function ($sq) {
                $sq->where(function ($innerQ) {
                    $innerQ->whereDoesntHave('Productions', function ($query) {
                        $query->whereRelation('Service', function ($sq) {
                            $sq->where('service', 'Publicação');
                        })
                        ->where('completed', true)
                        ->where('completed_at', '<', now()->subDays(7));
                    });
                })->whereHas('WorkForm');
            })
            ->orWhereDoesntHave('WorkForm');
        })
        ->when($this->search, function ($q) {
            $q->where('note', 'like', '%'.$this->search.'%')
                ->orWhereRelation('Orders', 'ordem', 'like', '%'.$this->search.'%');
        })
        ->join('ramal_reports', 'notes.id', '=', 'ramal_reports.note_id')
        ->select('notes.*', 'ramal_reports.created_at as date_smc')
        ->with(['productions' => function ($query) {
            $query->whereHas('Service', function ($q) {
                $q->where('service', 'Publicação');
            })->latest();
        }])
        ->orderBy('date_smc');

    }

    public function getListsPropertyBKP()
    {
        return Note::whereHas('RamalForm', function ($q) {
            $q->when($this->company, function ($sq) {
                $sq->where('company_id', $this->company);
            });
        })
            ->where(function ($q) {
                // Exclui registros que possuem Productions com 'completed = true' e 'completed_at > 7 dias'
                $q->whereDoesntHave('Productions', function ($query) {
                    $query->whereHas('Service', function ($sq) {
                        $sq->where('service', 'Produção');
                    })
                    ->where('completed', true)
                    ->where('completed_at', '<', now()->subDays(7));
                })
                // Inclui apenas registros que atendem as condições específicas
                ->orWhereHas('Productions', function ($query) {
                    $query
                    ->where(function ($subQuery) {
                        $subQuery->where('completed_at', '>=', now()->subDays(7))
                                    ->where('completed', false);
                    })
                    ->whereHas('Service', function ($sq) {
                        $sq->where('service', 'Produção');
                    });
                });
            })
            ->join('ramal_reports', 'notes.id', '=', 'ramal_reports.note_id')
            ->select('notes.*', 'ramal_reports.created_at as date_smc')
            ->when($this->search, function ($q) {
                $q->where('note', 'like', '%'.$this->search.'%')
                    ->orWhereRelation('Orders', 'ordem', 'like', '%'.$this->search.'%');
            })
            ->orderBy('date_smc')
            ->with([
                'RamalForm.Company',
                'RamalForm.User',
                'RamalForm.Orders',
                'RamalForm.BtzeroEquipment',
                'productions' => function ($query) {
                    $query->whereHas('Service', function ($q) {
                        $q->where('service', 'Publicação');
                    })->latest();
                }
            ])
            ->paginate(30);
    }




    public function selectNote($id)
    {
        if ($this->selected == $id) {
            $this->selected = '';
            $this->emitTo('btzero.dashboard.list-production-btzero', 'selectNote', $this->selected);
        } else {
            $this->selected = $id;
            $this->emitTo('btzero.dashboard.list-production-btzero', 'selectNote', $this->selected);
        }

    }


    public function render()
    {
        return view(
            'livewire.btzero.listreports',
            [
            'lists' => $this->lists->paginate(30)
            ]
        );
    }
}
