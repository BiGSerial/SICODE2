<?php

namespace App\Http\Livewire\Config\Services;

use App\Models\Service;
use Illuminate\Support\Facades\File;
use Livewire\Component;

class Create extends Component
{
    public $service;
    public $status;
    public $folders;
    public $folder_s;


    protected $listeners = [
        'save_create_service' => 'create'
    ];

    public function mount()
    {
        $directory = resource_path('views/services');
        // dd($directory);
        $this->folders = array_map('basename', File::directories($directory));
    }

    public function create()
    {
        if (!trim($this->service)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Você precisa informar o nome do serviço a ser incluido',
                'timer' => 2500,
            ]);
        }

        if (Service::create([
            'service' => ucwords(mb_strtolower($this->service)),
            'status' => $this->status,
            'folder' => $this->folder_s
            ])) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'success',
                'title' => 'Serviço Criado com Sucesso!',
                'timer' => 2500,
            ]);

            $this->emit('refresh_service_list');
            $this->clean_all();

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'error',
                'title' => 'Oooops! ocorreu algum erro ao tentar criar o serviço!',
                'timer' => 2500,
            ]);
        }
    }

    public function clean_all()
    {
        $this->service = '';

        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.config.services.create');
    }
}
