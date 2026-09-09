<?php

namespace App\Http\Livewire\Components\Files;

use App\Models\File;
use App\Services\Files\FileStorageService;
use Livewire\Component;

class ShowFilesPool extends Component
{
    public $files;
    public string $activeTab = 'fm-tab-0';

    protected $listeners = ['setActiveTab'];

    public function setActiveTab($tabId)
    {
        $this->activeTab = $tabId;
    }

    public function mount($files)
    {
        $this->files = $files;
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {

            $storage = app(FileStorageService::class);

            if ($storage->exists($file)) {
                return $storage->download($file, $file->file_name);
            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'ARQUIVO INEXISTENTE!',
                    'timer'    => 5000,
                ]);

                return;
            }
        }
    }

    public function render()
    {
        return view('livewire.components.files.show-files-pool', [
            'files' => $this->files,
            'activeTab' => $this->activeTab,
        ]);
    }
}
