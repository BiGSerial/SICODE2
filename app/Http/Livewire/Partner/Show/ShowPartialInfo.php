<?php

namespace App\Http\Livewire\Partner\Show;

use App\Models\Partial;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ShowPartialInfo extends Component
{
    public ?Partial $form = null;

    protected $listeners = [
        'show_form',
    ];

    public function show_form(Partial $form)
    {

        $this->form = $form->load(['Note.Orders', 'Orders', 'Company', 'User', 'Engineer', 'Supervisor', 'Payer', 'Files.Service']);

        // dd($this->form);

        if ($this->form) {



            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_partial_info',
            ]);
        }
    }

    public function downloadFile(File $file)
    {
        return Storage::download($file->path, $file->stored_name);
    }

    public function render()
    {
        return view('livewire.partner.show.show-partial-info');
    }
}
