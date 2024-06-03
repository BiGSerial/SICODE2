<?php

namespace App\Http\Livewire\Construction\Responser\Actions;

use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use ZipArchive;

class Responserinfo extends Component
{
    public ?Note $note = null;
    public $selectedFiles = [];
    public $setDays;

    protected $listeners = [
        'getInfoResponse',
        'refreshDays' => '$refresh'
    ];

    public function getInfoResponse(Note $note)
    {
        $this->note = $note;

        if ($this->note) {
            $this->dispatchBrowserEvent('showModal', [
                'id' => 'responserInfo',
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

    public function addDays()
    {
        if ($this->setDays == 0) {
            return;
        }

        if (($this->note->Viabilities->last()->Days->sum('days') + $this->setDays) > 15 || ($this->note->Viabilities->last()->Days->sum('days') + $this->setDays) < 0) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'PRAZO INDISPONÌVEL',
                'msg'    => 'O PRAZO NAO PODE SER MAIOR QUE 15 DIAS, NEM MENOR QUE 0 DIAS.',
                'timer'    => 5000,
            ]);

            return;
        }

        try {
            foreach ($this->note->Viabilities->where('completed', false) as $viab) {
                $viab->Days()->create([
                    'days' => $this->setDays,
                    'user_id' => auth()->user()->id,
                ]);

                $viab->save();
            }
        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO',
                'html'    => 'Não conseguimos atualizar o prazo... nenhum dia foi adicionado.',
                'timer'    => 5000,
            ]);

            return;
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'NOVO PRAZO',
            'html'    => 'Foram alterado o prazo para entrega da viabilidade.',
            'timer'    => 2500,
        ]);

        $this->emitSelf('refreshDays');
        $this->emitUp('refresh_main');

        $this->note = $this->note->fresh();
        $this->setDays = 0;

    }

    public function render()
    {
        return view('livewire.construction.responser.actions.responserinfo', [
            'note' => $this->note,
        ]);
    }
}
