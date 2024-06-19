<?php

namespace App\Http\Livewire\Partner\Show;

use App\Models\WorkReport;
use Livewire\Component;

class ShowWorkForm extends Component
{
    public ?WorkReport $form = null;

    protected $listeners = [
        'show_form',
    ];

    public function show_form(WorkReport $form)
    {

        $this->form = $form->load(['Equipment', 'Meeters']);

        // dd($this->form);

        if ($this->form) {



            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_form_work',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.partner.show.show-work-form');
    }
}
