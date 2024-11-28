<?php

namespace App\Http\Livewire\Reports;

use App\Models\Edp_depc\BaseOV;
use App\Models\File;
use App\Models\Note;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use ZipArchive;

class Search extends Component
{
    public $search;
    public $selectedFiles = [];
    public $historico;

    protected $queryString = [
        'search' => ['except' => '', 'as' => 's'],
    ];

    protected $listeners = [
        'update_list' => '$refresh',
    ];

    public function Search()
    {

    }

    public function loadHistorico()
    {
        $this->historico = BaseOV::where('OV', trim($this->search))->orderBy('dhStat', 'DESC')->get();
    }




    public function getBuscarProperty()
    {
        return Note::where(function ($q) {
            $q->where('note', trim($this->search))
                ->orWhereRelation('Orders', 'ordem', trim($this->search));
        })->with(['Productions' => function ($query) {
            $query->where('rejected', false);
        }])->first();
    }

    public function downloadFile(File $file)
    {
        if ($file) {

            if (Storage::fileExists($file->path)) {
                return Storage::download($file->path, explode('.', $file->file_name)[0].".".$file->ext);
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

    public function zipFiles()
    {
        if (!count($this->selectedFiles)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'NENHUM ARQUIVO SELECIONADO',
                'timer'    => 5000,
            ]);

            return;
        }

        if (count($this->selectedFiles)) {


            $files = File::WhereIn('id', $this->selectedFiles)->get();


            if ($files) {
                $zipFile = 'Arquivos-'.$this->buscar->note."-" . hash('crc32', time()) . '.zip';
                $zip     = new ZipArchive();
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                foreach ($files as $file) {
                    $content = Storage::get($file->path);
                    $zip->addFromString(explode('.', $file->file_name)[0] . '.' . $file->ext, $content);
                }

                $zip->close();

                $this->selectedFiles = [];

                return response()->download($zipFile)->deleteFileAfterSend(true);
            }
        }
    }

    public function render()
    {
        return view('livewire.reports.search', [
            'lists' => $this->buscar,
        ]);
    }
}
