<?php

namespace App\Http\Livewire\Partner;

use App\Exports\Viability\HistoricReport;
use App\Helpers\TextFormatter;
use App\Http\Livewire\Partner\Concerns\{AuthorizesPartnerAccess, WithViabilityListFilters};
use App\Models\{File, Viability};
use App\Services\Files\FileStorageService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Livewire\{Component, WithPagination};
use ZipArchive;

class Histviab extends Component
{
    use AuthorizesPartnerAccess;
    use TextFormatter;
    use WithPagination;
    use WithViabilityListFilters;

    /** Colunas de data aceitas no filtro por período. */
    private const DATE_COLUMNS = ['sended_at', 'returned_at', 'completed_at'];

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public $files_selected = [];

    public $search = '';

    /** '' = Nota e OV | 1 = só notas | 2 = só OVs */
    public $typeNote = '';

    public $advanceSearch = '';

    public $multinotas = [];

    // search by date
    public $date_in;

    public $date_out;

    public $month;

    public $dateBy = 'sended_at';

    protected $queryString = [
        'search'   => ['except' => '', 'as' => 'buscar'],
        'page'     => ['except' => 1, 'as' => 'p'],
        'perPage'  => ['as' => 'pp'],
        'typeNote' => ['except' => '', 'as' => 'tipo'],
        'dateBy'   => ['except' => 'sended_at', 'as' => 'por'],
        'date_in'  => ['except' => '', 'as' => 'de'],
        'date_out' => ['except' => '', 'as' => 'ate'],
        'filters'  => ['except' => [], 'as' => 'f'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    public function mount()
    {
        $this->filters = $this->normalizeFilters($this->filters);
        $this->dateBy  = in_array($this->dateBy, self::DATE_COLUMNS, true) ? $this->dateBy : 'sended_at';
    }

    protected function viabilityFilterKeys(): array
    {
        return ['company', 'rubrica', 'region', 'regional', 'city'];
    }

    public function can(string $permission): bool
    {
        return \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), $permission);
    }

    public function updatedTypeNote()
    {
        $this->typeNote = in_array((string) $this->typeNote, ['1', '2'], true) ? (string) $this->typeNote : '';
        $this->resetPage();
    }

    public function updatedDateBy()
    {
        $this->dateBy = in_array($this->dateBy, self::DATE_COLUMNS, true) ? $this->dateBy : 'sended_at';
        $this->resetPage();
    }

    public function updatedDateIn()
    {
        $this->resetPage();
    }

    public function updatedDateOut()
    {
        $this->resetPage();
    }

    public function export_excel()
    {
        $this->authorizePartnerAccess('viability.export');

        return (new HistoricReport($this->lists->orderBy('sended_at')->get()))->download(date('YmdHis-') . 'HistViabExport.xlsx');
    }

    public function updatedPerPage()
    {
        $this->perPage = in_array((int) $this->perPage, [25, 50, 100, 250, 500], true) ? (int) $this->perPage : 50;
        $this->gotoPage(1);
    }

    public function downloadFile($id)
    {
        $this->authorizePartnerAccess('viability.view_files');

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

    public function buscarMultinotas()
    {
        if ($this->advanceSearch) {
            $this->search = '';
            $this->gotoPage(1);

            $this->multinotas = $this->formatTextToArray($this->advanceSearch);

            if (count($this->multinotas)) {
                $this->advanceSearch = '';
                $this->dispatchBrowserEvent('hideModal');
            }
        }
    }

    public function updatedSearch()
    {
        if (trim($this->search)) {
            $this->gotoPage(1);
            $this->multinotas    = [];
            $this->advanceSearch = '';
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

    /** Limpa busca, multinotas, tipo, período e filtros. */
    public function clearAllCriteria(): void
    {
        $this->search        = '';
        $this->multinotas    = [];
        $this->advanceSearch = '';
        $this->typeNote      = '';
        $this->month         = '';
        $this->date_in       = '';
        $this->date_out      = '';
        $this->dateBy        = 'sended_at';
        $this->clearListFilters();
    }

    /** Há algum critério além do padrão (para mostrar "Limpar filtros" e a mensagem de vazio). */
    private function hasHistoryCriteria(): bool
    {
        return $this->hasViabilityCriteria() || $this->multinotas || $this->typeNote !== '' || $this->date_in || $this->date_out;
    }

    public function updatedMonth()
    {
        if ($this->month) {
            $this->date_in  = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
            $this->date_out = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');
        } else {
            $this->date_in  = '';
            $this->date_out = '';
        }
    }

    public function getListsProperty()
    {
        $query = Viability::query();

        $this->applyPartnerCompanyScope($query);

        $this->applyPartnerBranchScopeToNoteRelation($query);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereRelation('Note', 'note', trim($this->search))
                    ->orWhereRelation('Note.Orders', 'ordem', trim($this->search));
            });
        }

        if ($this->multinotas) {
            $query->where(function ($q) {
                $q->whereRelation('Note', function ($q) {
                    $q->whereIn('note', $this->multinotas);
                })
                    ->orWhereRelation('Note.Orders', function ($q) {
                        $q->whereIn('ordem', $this->multinotas);
                    });
            });
        }

        $column = in_array($this->dateBy, self::DATE_COLUMNS, true) ? $this->dateBy : 'sended_at';

        if ($this->date_in || $this->date_out) {
            $query->where(function ($q) use ($column) {
                if ($this->date_in && !$this->date_out) {

                    $q->where($column, '>=', $this->date_in);

                } elseif (!$this->date_in && $this->date_out) {

                    $q->where($column, '<=', $this->date_out);

                } elseif ($this->date_in && $this->date_out) {

                    $q->whereBetween($column, [$this->date_in, $this->date_out]);
                }
            });
        }

        return $this->applyViabilityFilters($query)->orderBy('completed_at', 'DESC');
    }

    public function render()
    {
        $lists = $this->lists
            ->with([
                'Note:id,note,rubrica,lexp,type_note',
                'Note.Orders:id,note_id,ordem,statusSist',
                'Note.Files:id,note_id,service_id,file_name,ext,path',
                'Note.Files.service:uuid,service',
                'Note.Files.note:id,note',
                'Orders:id,note_id,ordem',
                'Justification',
                'Company:id,name',
            ])
            ->paginate($this->perPage);

        return view('livewire.partner.histviab', [
            'lists'        => $lists,
            'tacitFileIds' => $this->tacitFileIdsFor($lists),
            'hasCriteria'  => $this->hasHistoryCriteria(),
        ]);
    }
}
