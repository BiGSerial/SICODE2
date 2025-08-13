<?php

namespace App\Http\Livewire\Protests\Services;

use App\Models\MedProtest;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;
    public $search = '';
    public $dt_start;
    public $dt_end;
    public $month;

    protected $listeners = [
        'refreshComponent' => '$refresh',
    ];

    protected $queryString = [
        'perPage' => ['as' => 'pagina'],
    ];

    public function getListProperty()
    {
        return MedProtest::WhereHas('Assignments', function ($q) {
            $q
              ->where('user', true)
              ->where('completed', true);
        })->with('Protest', 'Assignments.user', 'Comments.user', 'Notes')->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.protests.services.history', [
            'list' => $this->list,
        ]);
    }
}
