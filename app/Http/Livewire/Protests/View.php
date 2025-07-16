<?php

namespace App\Http\Livewire\Protests;

use App\Models\MedProtest;
use Livewire\Component;

class View extends Component
{
    public $medProtest;

    public function mount($medProtestId)
    {
        $this->medProtest = MedProtest::with([
            'Protest',
            'Comments.User',
            'Notes',
            'Assignments.User'
        ])->findOrFail($medProtestId);

        if (!$this->medProtest) {
            abort(404, 'Medida de Reclamação não encontrada');
        }
    }

    public function render()
    {
        return view('livewire.protests.view', [
            'medProtest' => $this->medProtest,
        ]);
    }
}
