<?php

namespace App\Http\Livewire\Files;

use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class Filepartners extends Component
{
    use WithFileUploads;

    public ?Note $note = null;
    // public ?Production $production = null;
    public $notNote = false;
    public $needFiles;

    public $uploadsfiles = [];
    public $files = [];

    protected $listeners = [
        'save_files' => 'save',
        'cancel_files' => 'cancel',
        'needFiles',
    ];

    public function mount($note, $needFiles = false)
    {
        $this->note = $note;
        $this->needFiles = $needFiles;

        if ($this->needFiles) {
            $this->emitUp('hasFile', false);
        }
    }

    public function needFiles(bool $value)
    {
        $this->needFiles = $value;
    }

    public function updatedUploadsFiles()
    {

        try {

            $this->validate([
                'uploadsfiles.*' => 'mimes:pdf,jpeg,png,xlx,xlsx',
            ]);

        } catch (ValidationException $e) {

            // dd($e);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'TIPO DE ARQUIVO NÃO PERMITIDO',
                'html'     => '<div class="card bg-primary text-white"><div class="card-body">
                    <p class="fw-bold">Existem arquivos com formatos não suportados, revise e tente novamente.</p>
                    Somente são aceitos arquivos: <span class="fw-bold">.pdf, .jpg, .png, .xls ou .xlsx</span>
                    </div></div>',

            ]);

            foreach ($this->uploadsfiles as $file) {
                $tempPath = $file->getRealPath();

                if ($tempPath && file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }

            return;
        }

        foreach ($this->uploadsfiles as $file) {

            // checa se nao está repetindo arquivo.
            $unique = array_filter($this->files, function ($origin) use ($file) {

                return $origin->getClientOriginalName() === $file->getClientOriginalName();

            });

            if (!$unique) {
                $this->files[] = $file;
            } else {
                // Ja remove o arquivo do temp caso existente.
                $tempPath = $file->getRealPath();

                if ($tempPath && file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }
        }

        // if (!empty($this->files) && $this->production->d5 && $this->needFiles) {
        //     $this->emitUp('hasFile', false);
        // } elseif (empty($this->files) && $this->production->d5 && $this->needFiles) {
        //     $this->emitUp('hasFile', true);
        // }

        $this->checkFiles();
    }

    public function checkFiles()
    {
        if (count($this->files) > 0) {

            $this->notNote = false;

            foreach ($this->files as $file) {

                $fileNameWithoutExtension = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);



                if (!strpos($fileNameWithoutExtension, $this->note->note) !== false) {

                    $this->notNote = true;
                }
            }
        } else {
            $this->notNote = false;

        }

        if (!empty($this->files) && $this->needFiles) {
            $this->emitUp('hasFile', true);
        } elseif (empty($this->files) && $this->needFiles) {
            $this->emitUp('hasFile', false);
        }

    }

    public function deleteFile($index)
    {
        if (isset($this->files[$index])) {
            $tempPath = $this->files[$index]->getRealPath();

            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }

            unset($this->files[$index]);
        }

        $this->checkFiles();
    }

    public function cancel()
    {
        if (count($this->files) > 0) {
            foreach ($this->files as $file) {
                $tempPath = $file->getRealPath();

                if ($tempPath && file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }

            $this->files = [];
            $this->notNote = false;

        }

        $this->checkFiles();
    }

    public function save()
    {
        // dd($this->files, $this->production, $this->note);



        if (count($this->files)) {


            foreach ($this->files as $index => $file) {

                $tempPath = $file->getRealPath();

                if ($tempPath && file_exists($tempPath)) {

                    $folhas = count($this->files);

                    if ($file->getClientOriginalExtension() == "pdf") {
                        $newName = "CROQUI_".$this->note->note."_F"
                        .str_pad(++$index, 2, '0', STR_PAD_LEFT)."-"
                        .str_pad($folhas, 2, '0', STR_PAD_LEFT);

                        $version = File::where('file_name', 'like', "%".$newName."%")->count();

                        $newName = $newName."_rev".$version.".".$file->getClientOriginalExtension();
                    } elseif ($file->getClientOriginalExtension() == "xls" || $file->getClientOriginalExtension() == "xlsx") {
                        $newName = "ADS_".$this->note->note."_F"
                        .str_pad(++$index, 2, '0', STR_PAD_LEFT)."-"
                        .str_pad($folhas, 2, '0', STR_PAD_LEFT);

                        $version = File::where('file_name', 'like', "%".$newName."%")->count();

                        $newName = $newName."_rev".$version.".".$file->getClientOriginalExtension();
                    } else {
                        $newName = "{$file->getClientOriginalExtension()}_".$this->note->note."_F"
                        .str_pad(++$index, 2, '0', STR_PAD_LEFT)."-"
                        .str_pad($folhas, 2, '0', STR_PAD_LEFT);

                        $version = File::where('file_name', 'like', "%".$newName."%")->count();

                        $newName = $newName."_rev".$version.".".$file->getClientOriginalExtension();
                    }


                    $caminho = "";

                    // dd($newName);

                    $caminho = $file->store('/arquivos/empreiteira');

                    if ($caminho) {


                        $check =  File::create([
                                'note_id'   => $this->note->id,
                                'user_id'   => Auth()->User()->id,
                                'service_id'   => null,
                                'file_name' => $newName,
                                'path'      => $caminho,
                                'ext'       => $file->getClientOriginalExtension(),
                            ]);

                    }

                }

            }

            $this->dispatchBrowserEvent('torrada', [
                'status'   => 'success',
                'menssage' => "Arquivos salvos com sucesso",
            ]);
        }


        // $this->emitUp('clean');
        // $this->emitUp('refresh_accomany');
        // $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.files.filepartners');
    }
}
