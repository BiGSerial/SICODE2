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

        $this->form = $form->load([
            'Company',
            'Equipment',
            'Meeters',
            'Note.Files.Service',
            'Note.FiveNote.productions.Service:id,uuid,service',
            'Note.FiveNote.productions.User:id,name,email',
            'Orders',
            'FlowProductions' => function ($query) {
                $query->with([
                    'Production.Service:id,uuid,service',
                    'Production.User:id,name,email',
                    'Production.Company:id,name',
                    'LinkedBy:id,name,email',
                ])
                    ->orderBy('stage')
                    ->orderByDesc('is_current')
                    ->orderBy('linked_at')
                    ->orderBy('id');
            },
        ]);

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
