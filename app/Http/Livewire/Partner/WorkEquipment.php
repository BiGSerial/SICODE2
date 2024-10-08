<?php

namespace App\Http\Livewire\Partner;

use App\Models\Equipment;
use Livewire\Component;
use Livewire\WithPagination;

class WorkEquipment extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $search;

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];


    public function getListsProperty()
    {
        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            session_start();
        }

        $query = Equipment::query();


        if (!auth()->user()->superadm) {

            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->whereRelation('WorkReport', function ($q) {
                    $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
                });
            } else {
                $query->whereRelation('WorkReport', 'company_id', Auth()->user()->Company->id);
            }
        }

        if ($this->search) {
            $query->where(function ($query) {
                $query->Where('patrimony', 'like', '%' . $this->search . '%')
                    ->orWhereRelation('WorkReport.Note', function ($q) {
                        return $q->where('note', 'like', '%' . $this->search . '%')
                            ->orWhere('lexp', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('pole', 'like', '%' . $this->search . '%')
                    ->orWhere('installed', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('patrimony');
    }


    public function render()
    {
        return view('livewire.partner.work-equipment', [
            'equipments' => $this->lists->paginate($this->perPage),
        ]);
    }
}
