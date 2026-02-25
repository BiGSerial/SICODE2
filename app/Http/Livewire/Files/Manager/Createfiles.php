<?php

namespace App\Http\Livewire\Files\Manager;

use App\Models\File;
use App\Models\Note;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Createfiles extends Component
{
    use WithFileUploads;

    public ?Note $note = null;
    public $files = [];
    public $tempFiles = [];
    public $uploadType;
    public $service_id;
    public $services;

    protected $listeners = [
        'createFile',
    ];

    public function mount()
    {
        $this->services = Service::orderBy('service')->get();
    }

    public function updatedFiles()
    {
        $this->validate();

        if (count($this->files)) {
            foreach ($this->files as $file) {
                $this->tempFiles[] = [
                    'service_id' => $this->service_id,
                    'user_id' => Auth()->User()->id,
                    'uploadType' => $this->uploadType,
                    'ext' => $file->getClientOriginalExtension(),
                    'newName' => null,
                    'original_name' => $file->getClientOriginalName(),
                    'suspicious' => false,
                    'file' => $file,
                ];
            }
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
        $this->service_id = '';
        $this->files = [];
        $this->resetErrorBag();
        $this->emitUp('update_list');
        $this->dispatchBrowserEvent('hideModal');
    }


    public function createFile(Note $note)
    {
        $this->note = $note;


        if ($this->note) {


            $this->dispatchBrowserEvent('showModal', [
                'id' => 'modal_mass_upload',
            ]);
        }
    }


    private function rename(array &$temps, string $type)
    {
        $count = 0;
        $item = 1;
        $type_s = '';
        $service = '';

        foreach ($temps as $temp) {

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
                $service_abrev = mb_strtoupper(substr($this->services->firstWhere('uuid', $temp['service_id'])->service, 0, 4));
                $temp['newName'] = $type."_".$service_abrev."_".$this->note->note."_F".str_pad($item, 2, '0', STR_PAD_LEFT)."-".str_pad($count, 2, '0', STR_PAD_LEFT);
            }

            $item++;
        }

    }

    private function orderByOriginalName(array &$temps)
    {
        usort($temps, function ($a, $b) {
            return strcmp($a['original_name'], $b['original_name']);
        });
    }


    public function saveFiles()
    {
        if (count($this->tempFiles)) {

            if (count($this->tempFiles) > 1) {
                $this->orderByOriginalName($this->tempFiles);
            }


            foreach ($this->tempFiles as $tempFile) {

                $this->rename($this->tempFiles, $tempFile['uploadType']);
            }
        }

        DB::beginTransaction();

        foreach ($this->tempFiles as $saveFile) {
            $rev = File::where('file_name', 'like', $saveFile['newName']."%")->count();

            $caminho = $saveFile['file']->storeAs('/arquivos/'. $saveFile['uploadType'], $saveFile['newName']."_Rev".$rev.'.'.$saveFile['ext']);



            if (Storage::exists($caminho)) {
                File::create([
                    'note_id' => $this->note->id,
                    'user_id' => Auth()->User()->id,
                    'service_id' => $saveFile['service_id'],
                    'file_name' => $saveFile['newName']."_Rev".$rev,
                    'path' => $caminho,
                    'ext' => $saveFile['ext'],
                    'original_name' => $saveFile['original_name'],
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

        $this->closeAll();
    }



    protected $rules = [

        'files.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,odt,xls,xlsx,xlsm,ods|max:10240',
    ];



    public function render()
    {
        return view('livewire.files.manager.createfiles');
    }
}
