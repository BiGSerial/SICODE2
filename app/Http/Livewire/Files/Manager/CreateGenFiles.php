<?php

namespace App\Http\Livewire\Files\Manager;

use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Componente Livewire para Gerenciamento de Arquivos de Generico
 *
 * Este componente é responsável por gerenciar o upload e manipulação de arquivos
 * relacionados a uma produção específica. Permite adicionar, renomear, verificar
 * e remover arquivos antes de salvar definitivamente no sistema de armazenamento.
 *
 * Comandos Recebidos:
 * - `saveFiles`: Salva os arquivos temporários permanentemente.
 * - `cleanFiles`: Limpa os arquivos temporários e redefine o estado do componente.
 *
 * Métodos Públicos:
 * - `mount(Production $production, bool $needFiles = false)`: Inicializa o componente
 *   com a produção especificada e determina se arquivos são necessários.
 * - `updatedFiles()`: Valida e adiciona novos arquivos à lista temporária.
 * - `removeFile($index)`: Remove um arquivo específico da lista de arquivos temporários.
 * - `closeAll()`: Limpa todos os arquivos temporários e reinicializa o componente.
 * - `saveFiles()`: Salva os arquivos temporários no armazenamento e no banco de dados.
 *
 * Comandos Emitidos:
 * - `hasFile`: Informa se há arquivos presentes (true/false).
 * - `savedFiles`: Notifica que os arquivos foram salvos com sucesso.
 *
 * Declaração do Componente no Blade:
 * Para usar este componente em uma view Blade, inclua a seguinte linha:
 *
 * <livewire.files.manager.create-gen-files :production="$production" :need-files="true" />
 *
 * Onde:
 * - `:production="$production"` passa uma instância de `Production`.
 * - `:need-files="true"` (opcional) indica se arquivos são obrigatórios.
 */

class CreateGenFiles extends Component
{
    use WithFileUploads;

    public ?Note $note = null;
    public bool $alertFile = false;
    public string $service;
    public $files = [];
    public $tempFiles = [];
    public $uploadType;
    public $services;

    protected $listeners = [
        'saveFiles',
        'cleanFiles' => 'closeAll',
    ];

    public function mount(Note $note, string $service)
    {
        $this->note = $note;
        $this->service = $service;
    }

    public function updatedFiles()
    {
        $this->validate();

        if (count($this->files)) {
            foreach ($this->files as $file) {

                $exists = false;

                if (count($this->tempFiles)) {
                    foreach ($this->tempFiles as $temp_file) {
                        if ($temp_file['file']->getClientOriginalName() === $file->getClientOriginalName()) {
                            $exists = true;
                        }
                    }
                }

                if (!$exists) {
                    $this->tempFiles[] = [
                        'note_id' => $this->note->id,
                        'service_id' => null,
                        'user_id' => Auth()->User()->id,
                        'uploadType' => $this->uploadType,
                        'ext' => $file->getClientOriginalExtension(),
                        'original_name' => $file->getClientOriginalName(),
                        'newName' => null,
                        'suspicious' => false,
                        'file' => $file,
                    ];
                }
            }
        }

        $this->checkFilesExists();
    }

    public function checkFilesExists()
    {
        if (count($this->tempFiles)) {

            $this->alertFile = false;

            foreach ($this->tempFiles as &$temp_file) {

                if (strpos($temp_file['file']->getClientOriginalName(), $this->note->note) === false) {
                    $this->alertFile = true;
                    $temp_file['suspicious'] = true;
                }
            }


            $this->emitUp('hasFile', true);
        } else {
            $this->emitUp('hasFile', false);
        }
    }



    public function removeFile($index)
    {
        if (isset($this->tempFiles[$index])) {
            if ($this->tempFiles[$index]['file']->exists()) {
                $this->tempFiles[$index]['file']->delete();
            }
            unset($this->tempFiles[$index]);
        }

        $this->checkFilesExists();
    }


    public function closeAll()
    {
        if (count($this->tempFiles)) {
            foreach ($this->tempFiles as $index => $value) {
                $this->removeFile($index);
            }
        } else {
            $this->tempFiles = [];
        }

        $this->note = null;
        $this->uploadType = '';
        $this->files = [];
        $this->resetErrorBag();
        $this->emitUp('update_list');

    }


    // public function createFile(Note $note)
    // {
    //     $this->note = $note;


    //     if ($this->note) {


    //         $this->dispatchBrowserEvent('showModal', [
    //             'id' => 'modal_mass_upload',
    //         ]);
    //     }
    // }


    private function rename(array &$temps, string $type)
    {
        $count = 0;
        $item = 1;
        $type_s = '';


        foreach ($temps as $temp) {


            if ($temp['uploadType'] === $type) {
                $count++;
            }

        }

        foreach ($temps as &$temp) {

            if ($temp['uploadType'] !== $type_s) {
                $type_s = $temp['uploadType'];
                $item = 1;
            }

            if ($temp['uploadType'] === $type && !$temp['newName']) {
                $service_abrev = mb_strtoupper(substr($this->service, 0, 4));
                $temp['newName'] = $type."_".$service_abrev."_".$this->note->note."_F".str_pad($item, 2, '0', STR_PAD_LEFT)."-".str_pad($count, 2, '0', STR_PAD_LEFT);
            }

            $item++;
        }

    }


    public function saveFiles()
    {
        if (count($this->tempFiles)) {
            foreach ($this->tempFiles as $tempFile) {
                $this->rename($this->tempFiles, $tempFile['uploadType']);
            }
        } else {
            return;
        }

        DB::beginTransaction();

        foreach ($this->tempFiles as $saveFile) {
            $rev = File::where('file_name', 'like', $saveFile['newName']."%")->count();

            $caminho = $saveFile['file']->store('/arquivos/'. $saveFile['uploadType']);

            if (Storage::exists($caminho)) {
                File::create([
                    'note_id' => $this->note->id,
                    'user_id' => Auth()->User()->id,
                    'service_id' => null,
                    'file_name' => $saveFile['newName']."_Rev".$rev,
                    'original_name' => $saveFile['original_name'],
                    'path' => $caminho,
                    'ext' => $saveFile['ext'],
                    'suspicious' => $saveFile['suspicious'],
                    'noexists' => false,
                ]);
            } else {
                DB::rollback();

                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'ERRO AO SALVAR',
                    'html'     => '<div class="card bg-primary text-white"><div class="card-body">
                        <p class="fw-bold">Ocorreu um erro ao salvar um dos, ou o arquivo. Aparentemente não foi concluído o upload. Remova-o(os) da lista e tente novamente. </p>

                        </div></div>',

                ]);

                return;
            }
        }

        DB::commit();


        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'ARQUIVOS SALVOS COM SUCESSO',
            'timer'    => 1500,

        ]);

        $this->emitUp('savedFiles');

        $this->closeAll();
    }



    protected $rules = [

        'files.*' => 'nullable|file|mimes:jpg,png,pdf,doc,docx,odt,xls,xlsx,xlsm,ods|max:10240',
    ];



    public function render()
    {
        return view('livewire.files.manager.create-gen-files');
    }
}
