<?php

namespace App\Http\Livewire\Construction\Hiring;

use App\Models\Order;
use App\Models\Service;
use Livewire\Component;
use Livewire\WithPagination;

class Main extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';



    public $service;
    public array $selected = [];
    public $advanceSearch;
    public $search;

    public $perPage = 50;

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
    }

    public function getListsProperty()
    {
        return Order::whereHas('Operations', function ($query) {
            $query->where('operacao', '0010')
                  ->where(function ($query) {
                      $query->where('status', 'ABER')
                            ->orWhere('status', 'LIB');
                  });
        })
        ->orderBy('dtEntrada')
        ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.construction.hiring.main', [
            'lists' => $this->lists
        ]);
    }
}
