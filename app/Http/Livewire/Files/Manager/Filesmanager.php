<?php

namespace App\Http\Livewire\Files\Manager;

use App\Exports\Files\FilesList;
use App\Models\File;
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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function export_excel()
    {
        return (new FilesList($this->lists->get()))->download(date('YmdHis-') . 'exportFilesList.xlsx');
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
        ->orderBy('file_name');
    }

    public function render()
    {
        return view('livewire.files.manager.filesmanager', [
            'lists' => $this->lists->paginate($this->perPage),
        ]);
    }
}
