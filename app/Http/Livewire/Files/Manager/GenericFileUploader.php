<?php

namespace App\Http\Livewire\Files\Manager;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use App\Models\File;
use App\Models\Note;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GenericFileUploader extends Component
{
    use WithFileUploads;

    public ?Note  $note           = null;
    public $parentModel;
    public string $relation;
    public ?string  $serviceId      = null;
    public array  $uploadTypes     = [];    // aqui virá sempre array de objects
    public string $uploadColValue;
    public array  $identifiers     = [];

    public string $selectedType    = '';
    public $files           = [];
    public $tempFiles       = [];
    public string $service       = '';

    protected $listeners = [
        'saveFiles',
        'prepareFileUpload' => 'prepare',
    ];

    protected $messages = [
        'files.*.file' => 'O arquivo deve ser um arquivo válido.',
        'files.*.max'  => 'O arquivo não pode ser maior que 20MB.',
    ];

    public function mount(
        ?Note $note = null,
        $parentModel,
        string $relation,
        array $uploadTypes,      // pode vir array de arrays ou array de objects
        string $column,
        ?string $serviceId = null,
        array $identifiers = []
    ) {
        $this->note           = $note;
        $this->parentModel    = $parentModel;
        $this->relation       = $relation;
        $this->serviceId      = $serviceId;
        $this->uploadColValue = $column;
        $this->identifiers    = $identifiers;
        $this->service        = mb_strtoupper(Service::where('uuid', $this->serviceId)->first()->service);



        // ——> Faz o cast **aqui**, uma única vez:
        $this->uploadTypes = array_map(
            fn ($item) => is_array($item) ? (object) $item : $item,
            $uploadTypes
        );
    }

    public function prepare(string $modelClass, int $modelId)
    {
        $this->parentModel = $modelClass::findOrFail($modelId);
    }

    public function updatedFiles()
    {
        $this->validate(['files.*' => 'file|max:20480']);

        foreach ($this->files as $file) {
            $this->tempFiles[] = [
                'file'          => $file,
                'ext'           => $file->getClientOriginalExtension(),
                'original_name' => $file->getClientOriginalName(),
                'uploadType'    => $this->selectedType,
            ];
        }

        $this->files = [];
    }

    private function makeFileName(array $temp, int $index): string
    {
        $typeSlug = Str::upper(Str::substr(Str::slug($temp['uploadType'], ''), 0, 10));
        $parts    = array_map(fn ($v) => Str::slug($v), $this->identifiers);

        return implode('_', array_merge(
            [$typeSlug],
            [Str::substr($this->service, 0, 4)],
            $parts,
            // ["F" . str_pad($index + 1, 2, '0', STR_PAD_LEFT)]
        ));
    }

    public function saveFiles()
    {
        if (empty($this->tempFiles)) {
            $this->emitUp('continue');
            return;
        }

        // determina note_id
        if ($this->note) {
            $note_id = $this->note->id;
        } elseif (isset($this->parentModel->note_id)) {
            $note_id = $this->parentModel->note_id;
        } elseif (method_exists($this->parentModel, 'note') && $this->parentModel->note) {
            $note_id = $this->parentModel->note->id;
        } else {
            $note_id = null;
        }

        DB::beginTransaction();

        foreach ($this->tempFiles as $i => $temp) {
            $name = $this->makeFileName($temp, $i);
            $rev  = File::where('file_name', 'like', $name . '%')->count();
            $rev  = str_pad($rev + 1, 3, '0', STR_PAD_LEFT);
            $path = $temp['file']
                         ->storeAs("arquivos/{$this->service}/{$temp['uploadType']}", "{$name}_N{$rev}.{$temp['ext']}");


            if (Storage::exists($path)) {
                try {
                    $file = File::create([
                        'note_id'       => $note_id,
                        'file_name'     => "{$name}_N{$rev}",
                        'original_name' => $temp['original_name'],
                        'path'          => $path,
                        'ext'           => $temp['ext'],
                        'service_id'    => $this->serviceId,
                        'suspicious'    => false,
                        'noexists'      => false,
                        ]);

                    $this->parentModel
                            ->{$this->relation}()
                            ->attach($file->id);

                } catch (\Throwable $th) {


                    DB::rollBack();
                    $this->emitUp('ErrorSaveFiles');

                    return;
                }
            }
        }

        DB::commit();

        $this->tempFiles    = [];
        $this->selectedType = '';
        $this->emitUp('savedFiles');
    }

    public function render()
    {
        // aqui você **não** precisa mexer em uploadTypes de novo, já estão todos objetos
        return view('livewire.files.manager.generic-file-uploader', [
            'uploadTypes' => $this->uploadTypes,
        ]);
    }
}
