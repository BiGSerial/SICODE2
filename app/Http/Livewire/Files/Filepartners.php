<?php

namespace App\Http\Livewire\Files;

use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class Filepartners extends Component
{
    use WithFileUploads;

    public ?Note $note = null;
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
                'uploadsfiles.*' => 'mimes:pdf,jpeg,png,webp,xls,xlsx',
            ]);
        } catch (ValidationException $e) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'TIPO DE ARQUIVO NÃO PERMITIDO',
                'html' => '<div class="card bg-primary text-white"><div class="card-body">
                    <p class="fw-bold">Existem arquivos com formatos não suportados, revise e tente novamente.</p>
                    Somente são aceitos arquivos: <span class="fw-bold">.pdf, .jpg, .png, .webp, .xls ou .xlsx</span>
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
            $unique = array_filter($this->files, function ($origin) use ($file) {
                return $origin->getClientOriginalName() === $file->getClientOriginalName();
            });

            if (!$unique) {
                $this->files[] = $file;
            } else {
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
        $this->notNote = false;

        foreach ($this->files as $file) {
            $fileNameWithoutExtension = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            if (strpos($fileNameWithoutExtension, $this->note->note) === false) {
                $this->notNote = true;
                break;
            }
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
        if (count($this->files)) {
            $pdfCount = File::where('note_id', $this->note->id)->where('ext', 'pdf')->count();
            $xlsCount = File::where('note_id', $this->note->id)->whereIn('ext', ['xls', 'xlsx'])->count();
            $totalPdfCount = $pdfCount + count(array_filter($this->files, fn ($file) => $file->getClientOriginalExtension() == 'pdf'));
            $totalXlsCount = $xlsCount + count(array_filter($this->files, fn ($file) => in_array($file->getClientOriginalExtension(), ['xls', 'xlsx'])));

            DB::beginTransaction();

            foreach ($this->files as $file) {
                $tempPath = $file->getRealPath();
                if ($tempPath && file_exists($tempPath)) {
                    $newName = "";
                    $extension = $file->getClientOriginalExtension();
                    $folhas = ($extension == 'pdf') ? $totalPdfCount : $totalXlsCount;

                    if ($extension == "pdf") {
                        $newName = "CROQUI_VIAB_" . $this->note->note . "_F" . str_pad(++$pdfCount, 2, '0', STR_PAD_LEFT) . "_" . str_pad($folhas, 2, '0', STR_PAD_LEFT);
                    } elseif (in_array($extension, ["xls", "xlsx"])) {
                        $newName = "EXCEL_VIAB_" . $this->note->note . "_F" . str_pad(++$xlsCount, 2, '0', STR_PAD_LEFT) . "_" . str_pad($folhas, 2, '0', STR_PAD_LEFT);
                    } else {
                        $newName = strtoupper($extension) . "_VIAB_" . $this->note->note . "_F" . str_pad(count($this->files), 2, '0', STR_PAD_LEFT) . "_" . str_pad($folhas, 2, '0', STR_PAD_LEFT);
                    }

                    $version = File::where('file_name', 'like', "%" . $newName . "%")->count();
                    $newName = $newName . "_rev" . $version . "." . $extension;
                    $caminho = $file->store('/arquivos/partner');

                    if (Storage::exists($caminho)) {
                        File::create([
                            'note_id' => $this->note->id,
                            'user_id' => auth()->user()->id,
                            'service_id' => null,
                            'file_name' => $newName,
                            'path' => $caminho,
                            'ext' => $extension,
                        ]);
                    } else {

                        DB::rollBack();

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
            }

            DB::commit();

            $this->dispatchBrowserEvent('torrada', [
                'status' => 'success',
                'menssage' => "Arquivos salvos com sucesso",
            ]);
        }
    }

    public function render()
    {
        return view('livewire.files.filepartners');
    }
}
