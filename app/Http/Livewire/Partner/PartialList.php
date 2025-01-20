<?php

namespace App\Http\Livewire\Partner;

use App\Models\Partial;
use Livewire\Component;

class PartialList extends Component
{
    public $search;
    public $perPage = 50;

    public $dt_in;
    public $dt_out;


    public function getListsProperty()
    {
        $query = Partial::query();

        if (!auth()->user()->superadm) {
            $query->where('company_id', auth()->user()->company_id);
        }

        if ($this->search) {
            $query->whereRelation('Note', 'note', 'like', '%' . $this->search . '%')
                    ->orWhereRelation('Notes.Orders', 'ordem', 'like', '%' . $this->search . '%');
        }


        if ($this->dt_in && !$this->dt_out) {
            $query->whereDate('created_at', '>=', $this->dt_in);
        } elseif ($this->dt_out && !$this->dt_in) {
            $query->whereDate('created_at', '<=', $this->dt_out);
        } elseif ($this->dt_in && $this->dt_out) {
            $query->whereBetween('created_at', [$this->dt_in, $this->dt_out]);
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->perPage);
    }


    public function render()
    {
        return view('livewire.partner.partial-list', [
            'lists' => $this->lists
        ]);
    }
}
