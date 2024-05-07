<?php

namespace App\Http\Livewire\Files;

use App\Models\Note;
use App\Models\Production;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class Fileservices extends Component
{
    use WithFileUploads;

    public ?Note $note = null;
    public ?Production $production = null;
    public $notNote = false;

    public $uploadsfiles = [];
    public $files = [];

    protected $listeners = [
        'save_files' => 'save',
        'cancel_files' => 'cancel'
    ];

    public function mount($note, $production)
    {
        $this->note = $note;
        $this->production = $production;
    }

    public function updatedUploadsFiles()
    {

        try {

            $this->validate([
                'uploadsfiles.*' => 'mimes:pdf,jpeg,png',
            ]);

        } catch (ValidationException $e) {

            // dd($e);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'TIPO DE ARQUIVO NÃO PERMITIDO',
                'html'     => '<div class="card bg-primary text-white"><div class="card-body">
                    <p class="fw-bold">Existem arquivos com formatos não suportados, revise e tente novamente.</p>
                    Somente são aceitos arquivos: <span class="fw-bold">.pdf, .jpg ou .png</span>
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
    }

    public function save()
    {
        // dd($this->files, $this->production, $this->note);

        if (count($this->files)) {

            foreach ($this->files as $file) {

                $tempPath = $file->getRealPath();

                if ($tempPath && file_exists($tempPath)) {

                    $caminho = $file->store('/arquivos/projeto');

                    if ($caminho) {

                        $this->production->Files()->create([
                            'note_id'   => $this->production->note_id,
                            'user_id'   => Auth()->User()->id,
                            'service_id'   => $this->production->service_id,
                            'file_name' => $file->getClientOriginalName(),
                            'path'      => $caminho,
                            'ext'       => $file->getClientOriginalExtension(),
                        ]);

                    }

                }

            }
        }


        $this->emitUp('clean');
        $this->emitUp('refresh_accomany');
        $this->dispatchBrowserEvent('hideModal');
    }

    public function render()
    {
        return view('livewire.files.fileservices');
    }
}
