<?php

namespace App\Http\Livewire\Files\Manager;

use App\Models\File;
use App\Models\Production;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Componente Livewire para Gerenciamento de Arquivos de Produção
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
 * <livewire.files.manager.create-publication-files :production="$production" :need-files="true" />
 *
 * Onde:
 * - `:production="$production"` passa uma instância de `Production`.
 * - `:need-files="true"` (opcional) indica se arquivos são obrigatórios.
 */

class CreatePublicationFiles extends Component
{
    use WithFileUploads;

    public ?Production $production = null;
    public $needFiles;
    public $alertFile = false;
    public $files = [];
    public $tempFiles = [];
    public $uploadType;
    public $services;

    protected $listeners = [
        'saveFiles',
        'cleanFiles' => 'closeAll',
        'refreshComponent' => '$refresh',
    ];

    public function mount(Production $production, bool $needFiles = false)
    {
        $this->production = $production;
        $this->needFiles = $needFiles;
    }

    public $filesConfig = [
        'disk' => 'public',
        'path' => 'protest_attachments',
        'maxSize' => (10 * 1024),
        'allowedTypes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'],

    ];

    public function getFileIconClass($extension)
    {
        return match ($extension) {
            'pdf' => 'bg-danger text-white',
            'doc', 'docx' => 'bg-primary text-white',
            'xls', 'xlsx' => 'bg-success text-white',
            'jpg', 'jpeg', 'png' => 'bg-info text-white',
            'txt' => 'bg-secondary text-white',
            default => 'bg-dark text-white',
        };
    }

    public function getFileIcon($extension)
    {
        return match ($extension) {
            'pdf' => 'ri-file-pdf-fill',
            'doc', 'docx' => 'ri-file-word-fill',
            'xls', 'xlsx' => 'ri-file-excel-fill',
            'jpg', 'jpeg', 'png' => 'ri-image-fill',
            'txt' => 'ri-file-text-fill',
            default => 'ri-file-fill',
        };
    }

    public function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } else {
            return '0 bytes';
        }
    }

    public function clearAllFiles()
    {
        $this->tempFiles = [];
        $this->reset('files'); // Also clear any current files in the Livewire property
        $this->emitSelf('refreshComponent');
    }



    public function updatedFiles()
    {
        $this->validate();

        if (count($this->files)) {
            foreach ($this->files as $file) {

                // Bloqueio de arquivos não permitidos e limite de 10MB
                $allowedExtensions = [
                    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'tiff', 'webp',
                    'pdf', 'doc', 'docx', 'odt', 'xls', 'xlsx', 'xlsm', 'ods',
                    'dwg', 'dxf', 'dws', 'dwt', 'dgn', 'rvt', 'rfa', 'skp',
                ];
                $maxSizeBytes = 40 * 1024 * 1024; // 40MB

                if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'Arquivo não permitido: ' . $file->getClientOriginalName(),
                        'timer'    => 1500,
                    ]);
                    continue;
                }

                if ($file->getSize() > $maxSizeBytes) {
                    $this->dispatchBrowserEvent('swal', [
                        'position' => 'center',
                        'icon'     => 'warning',
                        'title'    => 'Tamanho excede 10MB: ' . $file->getClientOriginalName(),
                        'timer'    => 1500,
                    ]);
                    continue;
                }

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
                        'note_id'       => $this->production->Note->id,
                        'service_id'    => $this->production->service_id,
                        'user_id'       => Auth()->User()->id,
                        'uploadType'    => 'COMPARACAO',
                        'ext'           => $file->getClientOriginalExtension(),
                        'original_name' => $file->getClientOriginalName(),
                        'newName'       => null,
                        'suspicious'    => false,
                        'file'          => $file,
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

                if (strpos($temp_file['file']->getClientOriginalName(), $this->production->Note->note) === false) {
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
            unset($this->tempFiles[$index]);
            $this->tempFiles = array_values($this->tempFiles);
            $this->emitSelf('refreshComponent');
        }
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

        $this->production = null;
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
        $service = '';



        usort($temps, function ($a, $b) {
            $uploadTypeComparison = strcmp($a['uploadType'], $b['uploadType']);

            if ($uploadTypeComparison !== 0) {
                return $uploadTypeComparison;
            }

            return strcmp($a['original_name'], $b['original_name']);
        });



        foreach ($temps as $temp) {

            $agrupe[$temp['uploadType']][] = $temp;

            if ($temp['service_id'] !== $service) {
                $service = $temp['service_id'];
                $count = 0;
            }

            if ($temp['uploadType'] === $type) {
                $count++;
            }

        }

        foreach ($temps as &$temp) {

            if ($temp['uploadType'] !== $type_s || $temp['service_id'] !== $service) {
                $type_s = $temp['uploadType'];
                $service = $temp['service_id'];
                $item = 1;
            }

            if ($temp['uploadType'] === $type && !$temp['newName']) {
                $service_abrev = mb_strtoupper(substr($this->production->Service->service, 0, 4));
                $temp['newName'] = $type."_".$service_abrev."_".$this->production->Note->note."_N".str_pad($item, 2, '0', STR_PAD_LEFT)."-".str_pad($count, 2, '0', STR_PAD_LEFT);
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
            $this->emitUp('continue');
            return;
        }



        DB::beginTransaction();

        foreach ($this->tempFiles as $saveFile) {
            $rev = File::where('file_name', 'like', $saveFile['newName']."%")->count();

            $caminho = $saveFile['file']->storeAs('/arquivos/'. $saveFile['uploadType'], $saveFile['newName']."_Rev".$rev.'.'.$saveFile['ext']);

            if (Storage::exists($caminho)) {
                File::create([
                    'note_id' => $this->production->note->id,
                    'user_id' => Auth()->User()->id,
                    'service_id' => $saveFile['service_id'],
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

        'files.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,odt,xls,xlsx,xlsm,ods,dwg,dxf,dws,dwt,dgn,rvt,rfa,skp|max:41943', // 40MB
    ];



    public function render()
    {
        return view('livewire.files.manager.create-publication-files');
    }
}
