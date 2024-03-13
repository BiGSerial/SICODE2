<?php

namespace App\Http\Livewire\Partner;

use App\Models\Edp_depc\City;
use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use ZipArchive;

class Todoviability extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;
    public $cities;
    public $files_selected = [];

    public $search;

    protected $queryString = [
        'search' => ['except' => '', 'as' => 'buscar'],
        'page' => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
        ];



    public function mount()
    {
        $this->cities = City::orderBy('cidade')->get();
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)->first()) {

            if (Storage::disk('local')->exists($file->path)) {
                return Storage::download($file->path, $file->file_name);
            }
        }
    }

    public function downloadZip()
    {
        if (count($this->files_selected)) {
            $files = File::find($this->files_selected);

            if ($files) {
                $zipFile = "Arquivos-Lote-".hash('crc32', time()).".zip";
                $zip = new ZipArchive();
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                foreach ($files as $file) {
                    $content = Storage::get($file->path);
                    $zip->addFromString($file->file_name.".".$file->ext, $content);
                }

                $zip->close();


                $this->files_selected = [];

                return response()->download($zipFile)->deleteFileAfterSend(true);
            }
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon' => 'warning',
                'title' => 'Nenhum Arquivo foi selecionado para Download',
                'timer' => 5000,
            ]);

            return;
        }
    }

    public function getListsProperty()
    {
        $query = Note::Query();

        $query->whereRelation('Viabilities', function ($q) {
            $q->where('approved', false)
                ->where('tacit', false)
                ->where('canceled', false);
        })
            ->with(['Viabilities' => function ($query) {
                $query->where('approved', false)
                        ->where('tacit', false)
                        ->where('canceled', false)
                        ->with('Order');
            }, 'Files']);


        return $query->paginate($this->perPage);
    }


    public function render()
    {
        return view('livewire.partner.todoviability', [
            'lists' => $this->lists,
            'cities' => $this->cities
        ]);
    }
}
