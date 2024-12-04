<?php

namespace App\Http\Livewire\Btzero;

use App\Models\Note;
use Livewire\Component;
use Livewire\WithPagination;

class Listreports extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';



    public function getListsProperty()
    {
        return Note::whereHas('RamalForm')
        // ->where(function ($q) {
        //     $q->whereDoesntHave('WorkForm')
        //     ->orWhereHas('WorkForm', function ($query) {
        //         $query->where('created_at', '>=', now()->subDays(3));
        //     });
        // })
        ->with('RamalForm.Company', 'RamalForm.User', 'RamalForm.Orders', 'RamalForm.BtzeroEquipment')
        ->paginate(30);
    }


    public function render()
    {
        return view(
            'livewire.btzero.listreports',
            [
            'lists' => $this->lists
        ]
        );
    }
}
