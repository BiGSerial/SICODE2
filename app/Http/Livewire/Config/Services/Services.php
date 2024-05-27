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

    public $icon;

    public $icon_s;

    public $editName = [];

    public $chave = '';

    protected $listeners = [
        'refresh_service_list' => '$refresh',
    ];

    public function mount()
    {
        $directory = resource_path('views/services');

        $this->folders = array_map('basename', File::directories($directory));
    }

    public function edit_name_service(Service $service)
    {
        if ($this->chave && isset($this->editName[$this->chave])) {
            $this->editName[$this->chave] = false;
        }

        $this->update_service         = $service;
        $this->service_name           = $service->service;
        $this->status                 = $service->status;
        $this->icon_s                 = $service->icon;
        $this->chave                  = $service->id;
        $this->folder_s               = $service->folder;
        $this->editName[$service->id] = true;
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
            'status'  => $this->status,
            'folder'  => $this->folder_s,
            'icon'    => $this->icon_s,
        ])) {

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => 'Nome do serviço Atualizado com sucesso!',
            ]);

            $this->editName[$this->chave] = false;
            $this->service_name           = '';
            $this->status                 = '';
            $this->folder_s               = '';
            $this->icon_s                 = '';
            $this->update_service         = '';

            $this->emit('refresh_service_list');

        } else {
            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'danger',
                'menssage' => 'OOOOPS! Não consegui atualizar o nome... Sorry!',
            ]);

            $this->editName[$this->chave] = false;
            $this->service_name           = '';
            $this->status                 = '';
            $this->folder_s               = '';
            $this->icon_s                 = '';
            $this->update_service         = '';
            $this->emit('refresh_service_list');
        }
    }

    public function update_return(Service $service)
    {


        $check = $service->update(['canReturn' => !$service->canReturn]);

        if ($check) {

            $status = $service->canReturn ? ' >>> TRUE <<< ' : ' >>> FALSE <<<';

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => "SERVIÇO ".$service->service." ALTERADO PARA ". $status,
            ]);
        }

        $this->emitSelf('$refresh');


    }

    public function getServicesProperty()
    {
        return Service::with('contracts.company')->orderBy('service')->get();
    }

    public function render()
    {
        return view('livewire.config.services.services', [
            'services' => $this->services,
        ]);
    }
}
