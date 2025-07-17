<?php

namespace App\Http\Livewire\Files\Manager;

use App\Exports\Files\FilesList;
use App\Models\Company;
use App\Models\File;
use App\Models\Note;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Filesmanager extends Component
{
    use WithPagination;

    public $search;

    public $perPage = 150;
    public $services;
    public $service;
    public $noFile = false;
    public $companies;
    public $companySelected;
    public $rubrics;
    public $rubricSelected;
    public $selectedFiles = [];

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'update_list' => '$refresh',
    ];

    protected $queryString = [
        'search'   => ['except' => '', 'as' => 'buscar'],
        'page'     => ['except' => 1, 'as' => 'p'],
        'perPage'  => ['as' => 'pp'],
    ];

    public function mount()
    {
        $this->services = Service::whereIn('uuid', File::pluck('service_id')->unique())->get();

    }


    public function selectAll()
    {
        $this->selectedFiles = $this->lists->pluck('id')->toArray();
    }

    public function deselectAll()
    {
        $this->selectedFiles = [];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function export_excel()
    {
        return (new FilesList($this->lists->get()))->download(date('YmdHis-') . 'exportFilesList.xlsx');
    }

    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return number_format($bytes, 2, ',', '.') . ' ' . $units[$unitIndex];
    }




    public function checkFilesExists()
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => 'INICIANDO CHECAGEM...',
        ]);

        $noExists = 0;

        File::chunk(500, function ($files) use (&$noExists) {
            foreach ($files as $file) {
                if (!Storage::exists($file->path) && !$file->noexists) {
                    $file->noexists = true;
                    $file->save();
                    $noExists++;
                } elseif (Storage::exists($file->path) && !$file->noexists) {
                    $file->noexists = false;
                    $file->save();
                }
            }
        });


        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'CHECAGEM COMPLETA',
            'html'     => '<div class="card">
                                <div class="card-body text-start">
                                    <p>Foram encontrados:' . $noExists . ' registros sem arquivos novos.</p>
                                    <p>Total sem Arquivos:' . File::where('noexists', true)->count() . '.</p>
                                     <p>Total de Arquivos registrado:' . File::count() . '.</p>
                                </div>
                         </div>',
        ]);

    }

    public function downloadFile(File $file)
    {
        if ($file) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
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


    public function downloadZip()
    {
        if (empty($this->selectedFiles)) {
            $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'warning',
            'title'    => 'Nenhum arquivo selecionado!',
            'timer'    => 3000,
            ]);
            return;
        }

        $files = File::whereIn('id', $this->selectedFiles)->get();

        if ($files->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'Arquivos não encontrados!',
            'timer'    => 3000,
            ]);
            return;
        }

        $zip = new \ZipArchive();
        $zipFileName = 'arquivos_' . date('YmdHis') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Criar diretório temp se não existir
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $addedFiles = 0;

            foreach ($files as $file) {
                // Verificar se o arquivo existe no storage e localmente
                if (Storage::exists($file->path)) {
                    $fullPath = storage_path('app/' . $file->path);

                    // Verificar se o arquivo físico existe no sistema de arquivos
                    if (file_exists($fullPath) && is_readable($fullPath)) {
                        $fileName = $file->file_name;
                        // Adicionar extensão se não estiver presente
                        if ($file->ext && !str_ends_with($fileName, '.' . $file->ext)) {
                            $fileName .= '.' . $file->ext;
                        }
                        $zip->addFile($fullPath, $fileName);
                        $addedFiles++;
                    }
                }
            }

            $zip->close();

            if ($addedFiles > 0) {
                // Verificar se o ZIP foi criado com sucesso antes de fazer download
                if (file_exists($zipPath)) {
                    return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
                } else {
                    $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Erro ao gerar arquivo ZIP!',
                    'timer'    => 3000,
                    ]);
                }
            } else {
                // Verificar se o arquivo ZIP existe antes de tentar removê-lo
                if (file_exists($zipPath)) {
                    unlink($zipPath);
                }
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'error',
                    'title'    => 'Nenhum arquivo válido encontrado!',
                    'timer'    => 3000,
                ]);
            }
        } else {
            $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'Erro ao criar arquivo ZIP!',
            'timer'    => 3000,
            ]);
        }
    }



    public function getListsProperty()
    {
        return File::when($this->noFile, function ($q) {
            $q->where('noexists', true);
        })
        ->when(trim($this->search), function ($q) {
            $q->where(function ($sq) {
                $sq->where('file_name', 'like', '%'.trim($this->search).'%')
                    ->orWhereRelation('Note', 'note', trim($this->search));
            });
        })
        ->when($this->service, function ($q) {
            $q->where('service_id', $this->service);
        })
        ->when($this->companySelected, function ($q) {
            $q->whereHas('User', function ($sq) {
                $sq->where('company_id', $this->companySelected);
            });
        })
         ->when($this->rubricSelected, function ($q) {
             $q->whereHas('Note', function ($sq) {
                 $sq->where('rubrica', $this->rubricSelected);
             });
         })
        ->orderBy('file_name');
    }




    public function render()
    {
        $this->companies = Company::whereHas('Users.Files')->orderBy('name')->get();
        $this->rubrics = Note::select('rubrica')->whereNotNull('rubrica')
            ->distinct()
            ->orderBy('rubrica')
            ->get();

        return view('livewire.files.manager.filesmanager', [
            'lists' => $this->lists->paginate($this->perPage),
        ]);
    }
}
