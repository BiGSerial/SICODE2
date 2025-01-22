<?php

namespace App\Http\Livewire\Partner\Show;

use App\Models\Partial;
use Livewire\Component;

class ShowPartialInfo extends Component
{
    public ?Partial $form = null;

    protected $listeners = [
        'show_form',
    ];

    public function show_form(Partial $form)
    {

        $this->form = $form->load(['Note', 'Company', 'User', 'Engineer', 'Supervisor', 'Payer']);

        // dd($this->form);

        if ($this->form) {



            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_partial_info',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.partner.show.show-partial-info');
    }
}
