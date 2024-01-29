<?php

namespace App\Http\Livewire\Config\Services;

use App\Models\Service;
use Illuminate\Support\Facades\File;
use Livewire\Component;

class Services extends Component
{
    public $service_name;
    public $update_service;
    public $status;
    public $folders;
    public $folder_s;

    public $editName = false;

    protected $listeners = [
        'refresh_service_list' => '$refresh'
    ];

    public function mount()
    {
        $directory = resource_path('views/services');

        $this->folders = array_map('basename', File::directories($directory));
    }

    public function edit_name_service(Service $service)
    {
        $this->update_service = $service;
        $this->service_name = $service->service;
        $this->status = $service->status;

        $this->folder_s = $service->folder;
        $this->editName = true;
    }


    /**
     * Send Service ID to Addrules
     *
     * @param [type] $service_id
     * @return void
     */
    public function addRule($service_id)
    {
        $this->emit('open_add_rules', $service_id);
    }

    public function addStatus($service_id)
    {
        $this->emit('open_add_status', $service_id);
    }

    public function update_name()
    {
        if ($this->update_service->update([
            'service' => ucwords(mb_strtolower($this->service_name)),
            'status' => $this->status,
            'folder' => $this->folder_s
            ])) {

            $this->dispatchBrowserEvent('torrada', [
                'status' => 'success',
                'menssage' => 'Nome do serviço Atualizado com sucesso!',
            ]);

            $this->editName = false;
            $this->service_name = "";
            $this->status = "";
            $this->folder_s = "";

            $this->emit('refresh_service_list');

        } else {
            $this->dispatchBrowserEvent('torrada', [
                'status' => 'danger',
                'menssage' => 'OOOOPS! Não consegui atualizar o nome... Sorry!',
            ]);

            $this->editName = false;
            $this->service_name = "";
            $this->status = "";
            $this->folder_s = "";
            $this->emit('refresh_service_list');
        }
    }

    public function getServicesProperty()
    {
        return Service::with('contracts.company')->orderBy('service')->get();
    }

    public function render()
    {
        return view('livewire.config.services.services', [
            'services' => $this->services
        ]);
    }
}
