<?php

namespace App\Http\Livewire\Files\Manager;

use App\Models\File;
use App\Models\Service;
use Livewire\Component;
use Livewire\WithPagination;

class Filesmanager extends Component
{
    use WithPagination;

    public $search;

    public $perPage = 150;
    public $services;
    public $service;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->services = Service::whereIn('uuid', File::pluck('service_id')->unique())->get();
    }


    public function getListsProperty()
    {
        return File::when(trim($this->search), function ($q) {
            $q->where(function ($sq) {
                $sq->where('file_name', 'like', '%'.trim($this->search).'%')
                    ->orWhereRelation('Note', 'note', trim($this->search));
            });
        })
        ->when($this->service, function($q){
            $q->where('service_id', $this->service);
        })
        ->orderBy('file_name')->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.files.manager.filesmanager', [
            'lists' => $this->lists,
        ]);
    }
}
