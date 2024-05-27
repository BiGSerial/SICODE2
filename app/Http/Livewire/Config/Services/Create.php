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

    public $project;

    public $construction;

    public $returnable;

    public $icon;

    protected $listeners = [
        'save_create_service' => 'create',
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
                'icon'     => 'warning',
                'title'    => 'Você precisa informar o nome do serviço a ser incluido',
                'timer'    => 2500,
            ]);

            return;
        }

        if (!$this->project && !$this->construction) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'SEM DEFINIÇÃO DE SERVIÇO',
                'html'     => 'A definição do Serviço <strong>(Projeto/Construção)</strong> precisa ser definido.',
                'timer'    => 2500,
            ]);

            return;
        }

        if (Service::create([
            'service'      => ucwords(mb_strtolower($this->service)),
            'status'       => $this->status,
            'folder'       => $this->folder_s,
            'project'      => $this->project ? true : false,
            'construction' => $this->construction ? true : false,
            'canReturn'    => $this->returnable ? true : false,
            'icon'         => $this->icon ?? $this->icon,
        ])) {

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Serviço Criado com Sucesso!',
                'timer'    => 2500,
            ]);

            $this->emit('refresh_service_list');
            $this->clean_all();

        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'Oooops! ocorreu algum erro ao tentar criar o serviço!',
                'timer'    => 2500,
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
