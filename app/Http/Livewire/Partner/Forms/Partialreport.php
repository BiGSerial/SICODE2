<?php

namespace App\Http\Livewire\Partner\Forms;

use App\Custom\Partial\Ads;
use App\Custom\Partial\Rules;
use App\Models\Note;
use App\Models\Partial;
use Livewire\Component;
use Livewire\WithFileUploads;

class Partialreport extends Component
{
    use WithFileUploads;

    public $search;
    public $note;
    public $notes;
    public $partial;
    public $file;
    public $orders = [];

    public ?Ads $ads = null;

    public function mount()
    {
        $this->search = '';
        $this->note = null;
        $this->notes = null;
        $this->file = null;
    }

    public function search()
    {

        $this->note = null;
        $this->notes = null;
        $this->file = null;

        $this->notes = Note::where(function ($q) {
            $q->where('note', trim($this->search))
            ->orWhereRelation('Orders', 'ordem', trim($this->search));
        })
        ->with('Orders', 'WorkForm', 'Partials')->get();
    }

    public function getNote($id)
    {
        $this->note = Note::find($id);
    }

    public function processFile()
    {
        $path = $this->file->getRealPath();

        $this->ads = new Ads($path);

        if ($this->ads->exist()) {
            dd($this->ads);
        }
    }

    public function create()
    {


        $this->partial = new Partial();
        $this->partial->note_id = $this->note->id;
        $this->partial->file = $this->file->store('partials');
        $this->partial->save();

        $this->emit('partialCreated');
    }

    public function render()
    {
        return view('livewire.partner.forms.partialreport');
    }
}
