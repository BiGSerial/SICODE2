<?php

namespace App\Http\Livewire\Responsible;

use App\Exports\Viability\ViabilitiesInProgressExport;
use App\Models\City;
use App\Models\{File, Note, Viability};
use App\Services\Files\FileStorageService;
use Illuminate\Support\Facades\Crypt;
use Livewire\{Component, WithPagination};
use ZipArchive;

class ViabList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $cities;

    public $files_selected = [];

    public $inActivity = [];

    public $search;

    // Filters
    private $filter_group = 'partner';

    private $filter;

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    public function mount()
    {
        $this->cities = City::orderBy('cidade')->get();
    }

    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function putInActvity($id)
    {

        if ($id) {

            foreach ((Note::find($id))->Viabilities as $viab) {
                $viab->update(['inActivity' => !$viab->inActivity]);
            }
        }
    }

    public function export_excel()
    {
        return (new ViabilitiesInProgressExport($this->lists))->download(date('Ymd_His') . '-EmViabilidadeExport.xlsx');
    }

    public function downloadFile($id)
    {

        if ($file = File::find($id)) {

            $storage = app(FileStorageService::class);

            if ($storage->exists($file)) {
                return $storage->download($file, $file->file_name);
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

    public function openForms($id)
    {
        if ($id) {

            return redirect()->route('forms.viability', ['id' => Crypt::encrypt($id)]);
        }
    }

    public function downloadZip()
    {
        if (count($this->files_selected)) {
            $files = File::find($this->files_selected);

            if ($files) {
                $zipFile = 'Arquivos-Lote-' . hash('crc32', time()) . '.zip';
                $zip     = new ZipArchive();
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                $storage    = app(FileStorageService::class);
                $tempCopies = [];

                foreach ($files as $file) {
                    $tempCopy = $storage->temporaryLocalCopy($file);

                    if (!$tempCopy) {
                        continue;
                    }

                    if (!$storage->matchesStoredChecksum($file, $tempCopy)) {
                        $zip->close();

                        foreach (array_merge($tempCopies, [$tempCopy]) as $copy) {
                            if (is_file($copy)) {
                                @unlink($copy);
                            }
                        }

                        if (file_exists($zipFile)) {
                            @unlink($zipFile);
                        }

                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'error',
                            'title'    => 'Checksum divergente!',
                            'html'     => 'O arquivo ' . e($file->original_name ?: $file->file_name) . ' não confere com o hash gravado no servidor.',
                            'timer'    => 5000,
                        ]);

                        return;
                    }

                    $zip->addFile($tempCopy, $file->file_name . '.' . $file->ext);
                    $tempCopies[] = $tempCopy;
                }

                $zip->close();

                foreach ($tempCopies as $tempCopy) {
                    if (is_file($tempCopy)) {
                        @unlink($tempCopy);
                    }
                }

                $this->files_selected = [];

                return response()->download($zipFile)->deleteFileAfterSend(true);
            }
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum Arquivo foi selecionado para Download',
                'timer'    => 5000,
            ]);

            return;
        }
    }

    public function getListsProperty()
    {

        if (!(session_status() == PHP_SESSION_ACTIVE)) {
            if (!session()->isStarted()) {
                session()->start();
            }
        }

        if (isset($_SESSION['filter'][$this->filter_group])) {
            $this->filter = $_SESSION['filter'][$this->filter_group];
        }

        $query = Viability::query();

        $query->where('canceled', false)
            ->where('completed', false)
            ->where('tacit', false)
            ->where('rejected', false)
            ->where('visible_partner', false);

        if (!auth()->user()->superadm) {

            // if (Auth()->user()->Companies->isNotEmpty()) {
            //     $query->where(function ($q) {
            //         $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
            //         ->orWhere('company_id', Auth()->user()->Company->id);
            //     });
            // } else {
            //     $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());
            // }

            $query->whereIn('engineer_id', auth()->user()->visibleUserIdsForWork());
        }

        $query->with(['Note', 'Files']);

        if ($this->search = trim($this->search)) {
            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', 'like', "%{$this->search}%")
                    ->orWhereRelation('Note.Orders', 'ordem', 'like', "%{$this->search}%");
            });
        }

        if (isset($this->filter['rubrica'])) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('rubrica', $this->filter['rubrica']);
            });
        }

        if (isset($this->filter['city'])) {
            $query->whereRelation('Note', function ($q) {
                $q->whereIn('lexp', $this->filter['city']);
            });
        }

        return $query->orderBy('sended_at', 'asc');

    }

    public function checkInActivity($item)
    {
        return $item->inActivity ?? false;
    }

    public function render()
    {
        return view('livewire.responsible.viab-list', [
            'lists'  => $this->lists->paginate($this->perPage),
            'cities' => $this->cities,
        ]);
    }
}
