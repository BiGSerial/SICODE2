<?php

namespace App\Http\Livewire\Services\Oexterno;

use App\Helpers\TextFormatter;
use App\Models\Reclaim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class WaitingReturn extends Component
{
    use WithPagination;
    use TextFormatter;

    public $perPage = 50;


    protected $paginationTheme = 'bootstrap';

    public function getListsProperty()
    {
        $query = Reclaim::query();

        $query->whereHas('externals', function ($q) {
            $q->where('status', 1);
        })->with('externals.entity', 'note', 'service', 'production', 'comments', 'subcategory.category')

            ->orderBy('created_at', 'asc');

        return $query;
    }

    public function getColor($days)
    {
        if ($days > 9) {
            return 'text-bg-danger';
        } elseif ($days < 3) {
            return 'text-bg-success';
        } else {
            return 'text-bg-warning';
        }
    }



    public function render()
    {
        return view('livewire.services.oexterno.waiting-return', [
            'lists' => $this->lists->paginate($this->perPage),
        ]);

    }
}
