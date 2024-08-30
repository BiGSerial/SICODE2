<?php

namespace App\Http\Livewire\Files\Manager;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Fileedit extends Component
{
    use WithFileUploads;

    public ?File $file = null;
    public $newFile;


    protected $listeners = [
        'editFile',
        'deleteFile',
        'fileConfirmDelete',
    ];

    public function editFile(File $file)
    {
        $this->file = $file;


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
        'newFile' => 'nullable|file|mimes:jpg,png,pdf,doc,docx,odt,xls,xlsx,xlsm,ods|max:10240',
    ];



    public function updateFile()
    {
        $this->validate();


        $this->file->file_name = mb_strtoupper($this->file->file_name);


        if ($this->newFile) {

            $path = $this->newFile->store("/".dirname($this->file->path));

            if (Storage::exists($path)) {
                if (Storage::exists($this->file->path)) {
                    Storage::delete($this->file->path);
                }

                $this->file->path = $path;
                $this->file->ext = $this->newFile->getClientOriginalExtension();
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

        $this->file->save();

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
    }

    public function render()
    {
        return view('livewire.files.manager.fileedit');
    }
}
