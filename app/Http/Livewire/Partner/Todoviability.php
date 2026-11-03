<?php

namespace App\Http\Livewire\Partner;

use App\Exports\Partner\ExportViabilityToExcel;
use App\Http\Livewire\Partner\Concerns\{AuthorizesPartnerAccess, WithViabilityListFilters};
use App\Models\{File, Viability};
use App\Services\Files\FileStorageService;
use App\Services\PartnerAccess\PartnerAccessGate;
use Illuminate\Support\Facades\Crypt;
use Livewire\{Component, WithPagination};
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class Todoviability extends Component
{
    use AuthorizesPartnerAccess;
    use WithPagination;
    use WithViabilityListFilters;

    protected $paginationTheme = 'bootstrap';

    /** @var int */
    public $perPage = 50;

    /** @var array<int> */
    public $files_selected = [];

    /** @var string|null */
    public $search = '';

    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1,  'as' => 'p'],
        'perPage' => ['as' => 'pp'],
        'filters' => ['except' => [], 'as' => 'f'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    public function mount(): void
    {
        $this->filters = $this->normalizeFilters($this->filters);
    }

    protected function viabilityFilterKeys(): array
    {
        return ['company', 'txpriority', 'rubrica', 'region', 'city'];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array((int) $this->perPage, [25, 50, 100, 250, 500], true) ? (int) $this->perPage : 50;
        $this->resetPage();
    }

    public function can(string $permission): bool
    {
        return PartnerAccessGate::allows(auth()->user(), $permission);
    }

    /**
     * Exporta o EXCEL da listagem atual (sem paginação)
     */
    public function export_excel()
    {
        $this->authorizePartnerAccess('viability.export');

        $rows = $this->listsQuery()->get();

        $fileName = now()->format('YmdHis') . '-exportViabilityPartner.xlsx';

        return Excel::download(new ExportViabilityToExcel($rows), $fileName);
    }

    /**
     * Download individual de arquivo
     */
    public function downloadFile(int $id)
    {
        $this->authorizePartnerAccess('viability.view_files');

        $file = File::find($id);

        if (!$file) {
            return;
        }

        $storage = app(FileStorageService::class);

        if ($storage->exists($file)) {
            return $storage->download($file, $file->file_name);
        }

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'ARQUIVO INEXISTENTE!',
            'timer'    => 5000,
        ]);
    }

    /**
     * Abre checklist FTVEO
     */
    public function openForms(int $id)
    {
        if ($id) {
            return redirect()->route('forms.viability', ['id' => Crypt::encrypt($id)]);
        }
    }

    /**
     * Download ZIP com arquivos selecionados
     */
    public function downloadZip()
    {
        $this->authorizePartnerAccess('viability.view_files');

        if (!count($this->files_selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum Arquivo foi selecionado para Download',
                'timer'    => 5000,
            ]);

            return;
        }

        $files = File::find($this->files_selected);

        if (!$files || !$files->count()) {
            return;
        }

        $zipFile = 'Arquivos-Lote-' . hash('crc32', microtime(true)) . '.zip';

        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $storage    = app(FileStorageService::class);
        $tempCopies = [];

        foreach ($files as $file) {
            if ($storage->exists($file)) {
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

                // usa nome.ext pra ficar igual à visualização
                $zip->addFile($tempCopy, $file->file_name . '.' . $file->ext);
                $tempCopies[] = $tempCopy;
            }
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

    /**
     * Alterna inActivity direto na viabilidade
     */
    public function putInActivity(int $id): void
    {
        $this->authorizePartnerAccess('viability.respond');

        $query = Viability::query()->whereKey($id);
        $this->applyPartnerCompanyScope($query);

        if ($viab = $query->first()) {
            $viab->inActivity = !$viab->inActivity;
            $viab->save();
        }
    }

    /**
     * Helper pra checkbox
     */
    public function checkInActivity($item): bool
    {
        return (bool)($item->inActivity ?? false);
    }

    /**
     * Monta a query base SEM paginação e SEM orderBy/select final.
     * Aqui vai:
     *  - regras de visibilidade
     *  - eager loading do que a view usa
     *  - busca textual
     *  - filtros (WithViabilityListFilters)
     */
    protected function baseQuery()
    {
        $query = Viability::query()
            ->where('viabilities.canceled', false)
            ->where('viabilities.completed', false)
            ->where('viabilities.tacit', false)
            ->where('viabilities.rejected', false)
            ->where('viabilities.visible_partner', false); // regra original mantida

        $this->applyPartnerCompanyScope($query, 'viabilities.company_id');

        $this->applyPartnerBranchScopeToNoteRelation($query);

        // Carrega só o que a view e a exportação usam (dt_vencimento evita uma consulta por OV no "Prazo Obra").
        $query->with([
            'Note:id,client,material,rubrica,txpriority,lexp,nexp,note,is45,days_left,mesalization,type_note,dt_vencimento',
            'Note.Orders:id,note_id,ordem,statusSist',
            'Note.Files:id,note_id,service_id,file_name,ext,path',
            'Note.Files.service:uuid,service',
            'Note.Files.note:id,note',
            'Company:id,name',
            'Files:id,note_id,file_name,ext,path',
            'comments:id,user_id,message,created_at',
            'comments.User:id,name,email',
            'Form:id,viability_id,reason,changes,responsible,description',
            'Engineer:id,name,email',
            'Note.City:rdMunicipio,regiao,cidade',
        ]);

        // Busca rápida nota / OV (ordem)
        if (filled($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->whereHas('Note', fn ($qq) => $qq->where('note', 'like', "%{$s}%"))
                  ->orWhereHas('Note.Orders', fn ($qq) => $qq->where('ordem', 'like', "%{$s}%"));
            });
        }

        return $this->applyViabilityFilters($query);
    }

    /**
     * Aplica ordenação e select final (espelha a tabela Blade)
     * NÃO pagina aqui.
     */
    protected function listsQuery()
    {
        return $this->baseQuery()
            ->leftJoin('notes', 'notes.id', '=', 'viabilities.note_id')
            ->orderByDesc('notes.is45')
            ->orderBy('viabilities.sended_at', 'asc')
            ->orderBy('viabilities.id', 'asc')
            ->select('viabilities.*')
            ->withSum('Days as days_sum', 'days');
    }

    public function render()
    {
        $lists = $this->listsQuery()->paginate($this->perPage);

        return view('livewire.partner.todoviability', [
            'lists'        => $lists,
            'tacitFileIds' => $this->tacitFileIdsFor($lists),
            'hasCriteria'  => $this->hasViabilityCriteria(),
        ]);
    }
}
