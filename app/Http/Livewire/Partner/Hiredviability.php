<?php

namespace App\Http\Livewire\Partner;

use App\Http\Livewire\Partner\Concerns\AuthorizesPartnerAccess;
use App\Models\City;
use App\Models\{File, Note};
use App\Services\Files\FileStorageService;
use Illuminate\Support\Facades\Crypt;
use Livewire\{Component, WithPagination};
use ZipArchive;

class Hiredviability extends Component
{
    use AuthorizesPartnerAccess;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $cities;

    public $files_selected = [];

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

    public function downloadFile($id)
    {
        $this->authorizePartnerAccess('viability.view_files');

        if ($file = File::find($id)) {

            $storage = app(FileStorageService::class);

            if ($storage->exists($file)) {
                return $storage->download($file, $file->file_name);
            }
        }
    }

    public function openForms($id)
    {
        $this->authorizePartnerAccess('viability.respond');

        if ($id) {

            return redirect()->route('forms.viability', ['id' => Crypt::encrypt($id)]);
        }
    }

    public function downloadZip()
    {
        $this->authorizePartnerAccess('viability.view_files');

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

        $query = Note::Query();

        $query->whereRelation('Viabilities', function ($q) {
            $q->where('tacit', false)
                ->where('canceled', false)
                ->where('hired', true)
                ->where('completed', false);

            $this->applyPartnerCompanyScope($q);

        })
            ->with(['Viabilities' => function ($query) {
                $query->where('tacit', false)
                ->where('canceled', false)
                ->where('hired', true)
                ->where('completed', false);
            }, 'Files']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->Where('note', 'like', "%$this->search%")
                    ->orWhereRelation('Orders', 'ordem', 'like', "%$this->search%");
            });
        }

        if (isset($this->filter['rubrica'])) {

            $query->whereIn('rubrica', $this->filter['rubrica']);
        }

        if (isset($this->filter['city'])) {

            $query->whereIn('lexp', $this->filter['city']);
        }

        $this->applyPartnerBranchScopeToNotes($query);

        return $query->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.partner.hiredviability', [
            'lists'  => $this->lists,
            'cities' => $this->cities,
        ]);
    }
}
