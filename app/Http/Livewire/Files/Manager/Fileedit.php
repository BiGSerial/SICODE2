<?php

namespace App\Http\Livewire\Files\Manager;

use App\Models\File;
use App\Models\Note;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Fileedit extends Component
{
    use WithFileUploads;

    public ?File $file = null;
    public $services;
    public $newFile;
    public $noteNumber = '';


    protected $listeners = [
        'editFile',
        'deleteFile',
        'fileConfirmDelete',
    ];

    public function editFile(File $file)
    {
        $this->file = $file;
        $this->noteNumber = $this->file?->Note?->note ?? '';
        $this->resetErrorBag();


        if ($this->file) {


            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_edit_file',
            ]);
        }
    }

    public function deleteFile(File $file = null)
    {
        $this->file = $file;

        if ($this->file) {

            $this->dispatchBrowserEvent('alertar', [
                'title'         => 'Remover Arquivo',
                'msg'           => "Você deseja remover o arquivo <strong>{$this->file->file_name}</strong>? O arquivo não poderá ser recuperado no servidor.",
                'icon'          => 'warning',
                'btnOktxt'      => 'Sim, Remova!',
                'btnCanceltxt'  => 'Não, Cancele',
                'action'        => 'fileConfirmDelete',
                'cancel_titulo' => 'Cancelado!',
                'cancel_msg'    => 'Nenhuma nenhum usuário foi removido.',

            ]);
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ARQUIVO REMOVIDO OU INEXISTENTE',
                'timer'    => 3000,

            ]);
        }
    }

    public function fileConfirmDelete()
    {
        if (Storage::exists($this->file->path)) {
            Storage::delete($this->file->path);
        }

        try {
            $this->file->delete();
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'ARQUIVO REMOVIDO',
                'timer'    => 1500,

            ]);

            $this->closeAll();

        } catch (\Throwable $th) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => 'ERRO AO REMOVER ARQUIVO',
                'timer'    => 3000,

            ]);
        }

    }

    protected $rules = [
        'file.file_name' => 'required|string|max:255',
        'file.service_id' => 'nullable|exists:services,uuid',
        'newFile' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,bmp,pdf,doc,docx,odt,xls,xlsx,xlsm,ods,txt,rtf,ppt,pptx,dwg,dxf,dwf,rvt,rfa,skp|max:20480',
        'noteNumber' => 'nullable|string|max:255',
    ];

    public function mount()
    {
        $this->services = Service::orderBy('service')->get();
    }



    public function updateFile()
    {
        $this->validate();

        if (trim($this->noteNumber) !== '') {
            $note = Note::where('note', trim($this->noteNumber))->first();

            if (!$note) {
                $this->addError('noteNumber', 'Nota nao encontrada.');
                return;
            }

            $this->file->note_id = $note->id;
        }

        $baseName = pathinfo($this->file->file_name, PATHINFO_FILENAME);
        $this->file->file_name = mb_strtoupper($baseName);


        if ($this->newFile) {

            $directory = $this->file->path ? pathinfo($this->file->path, PATHINFO_DIRNAME) : '';
            $directory = $directory === '.' ? '' : trim($directory, '/');
            $extension = $this->newFile->getClientOriginalExtension();
            $storedName = $this->file->file_name.'.'.$extension;
            $path = $this->newFile->storeAs($directory, $storedName);

            if (Storage::exists($path)) {

                if ($this->file->path && Storage::exists($this->file->path) && $this->file->path !== $path) {
                    Storage::delete($this->file->path);
                }

                $this->file->path = $path;
                $this->file->ext = $extension;
                $this->file->suspicious = false;
                $this->file->original_name = $this->newFile->getClientOriginalName();
                $this->file->noexists = false;

            } else {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'ERRO AO SALVAR',
                    'html'     => '<div class="card bg-primary text-white"><div class="card-body">
                    <p class="fw-bold">Ocorreu um erro ao salvar o arquivo. Aparentemente não foi concluído o upload. Tente novamente. </p>

                    </div></div>',

                ]);

                return;
            }

        }

        $this->file->service_id = $this->file->service_id ?: null;
        $this->file->save();

        $this->emitUp('update_list');

        session()->flash('message', 'Arquivo atualizado com sucesso!');

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'ARQUIVO ATUALIZADO',
            'timer'    => 1500,

        ]);

        $this->closeAll();
    }


    public function closeAll()
    {
        $this->emitUp('update_list');
        $this->dispatchBrowserEvent('hideModal');
        $this->file = null;
        $this->newFile = '';
        $this->noteNumber = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.files.manager.fileedit');
    }
}
