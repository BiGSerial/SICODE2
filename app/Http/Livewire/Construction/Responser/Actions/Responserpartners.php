<?php

namespace App\Http\Livewire\Construction\Responser\Actions;

use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use ZipArchive;

class Responserpartners extends Component
{
    public ?Note $note = null;
    public $selectedFiles = [];

    protected $listeners = [
        'getInfoResponse'
    ];

    public function getInfoResponse(Note $note)
    {
        $this->note = $note;

        if ($this->note) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'responserPartner',
            ]);
        }
    }
    

    public function downloadFile(File $file)
    {
        if ($file) {

            if (Storage::fileExists($file->path)) {
                return Storage::download($file->path, explode('.', $file->file_name)[0].".".$file->ext);
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

    public function zipFiles()
    {
        if(!count($this->selectedFiles)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'NENHUM ARQUIVO SELECIONADO',
                'timer'    => 5000,
            ]);

            return;
        }

        if(count($this->selectedFiles)) {


            $files = File::WhereIn('id', $this->selectedFiles)->get();


            if ($files) {
                $zipFile = 'Arquivos-'.$this->note->note."-" . hash('crc32', time()) . '.zip';
                $zip     = new ZipArchive();
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                foreach ($files as $file) {
                    $content = Storage::get($file->path);
                    $zip->addFromString(explode('.', $file->file_name)[0] . '.' . $file->ext, $content);
                }

                $zip->close();

                $this->selectedFiles = [];

                return response()->download($zipFile)->deleteFileAfterSend(true);
            }
        }
    }

    public function render()
    {
        return view('livewire.construction.responser.actions.responserpartners');
    }
}
