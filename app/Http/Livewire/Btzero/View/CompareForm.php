<?php

namespace App\Http\Livewire\Btzero\View;

use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class CompareForm extends Component
{
    public ?Note $note = null;

    protected $listeners = ['showCompareForm'];

    public function showCompareForm(Note $note)
    {


        $this->note = $note;

        if ($this->note) {
            // dd($note);

            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_compareForm',
            ]);
        }
    }



    public function render()
    {
        return view('livewire.btzero.view.compare-form');
    }
}
