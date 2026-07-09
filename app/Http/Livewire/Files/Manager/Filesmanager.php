<?php

namespace App\Http\Livewire\Files\Manager;

use App\Exports\Files\FilesList;
use App\Helpers\TextFormatter;
use App\Models\Company;
use App\Models\File;
use App\Models\Note;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Filesmanager extends Component
{
    use WithPagination;
    use TextFormatter;

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
    public $fileType = '';
    public $partnerFinalAdsOnly = false;
    public $massSearch = '';
    public $massSearchTerms = [];
    public $outputNamePattern = '';

    private const MAX_DOWNLOAD_SELECTION = 100;

    public $fileTypeOptions = [
        '' => 'Todos os tipos',
        'ads' => 'ADS',
        'projeto' => 'Projeto',
        'croqui' => 'Croqui',
        'inventario' => 'Inventario',
        'fotos' => 'Fotos',
        'outros' => 'Outros',
    ];

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
        $query = $this->lists;

        if (!$this->isSuperAdm()) {
            $query->whereDoesntHave('Adsforms', function ($q) {
                $q->where('tacit', true)
                    ->whereNotNull('work_report_id');
            });
        }

        $this->selectedFiles = $query
            ->limit(self::MAX_DOWNLOAD_SELECTION)
            ->pluck('id')
            ->toArray();
    }

    public function deselectAll()
    {
        $this->selectedFiles = [];
    }

    public function applyMassSearch(): void
    {
        $this->massSearchTerms = $this->massSearch
            ? $this->formatTextToArray($this->massSearch)
            : [];

        $this->selectedFiles = [];
        $this->resetPage();
    }

    public function clearExtractionFilters(): void
    {
        $this->search = '';
        $this->service = '';
        $this->companySelected = '';
        $this->rubricSelected = '';
        $this->fileType = '';
        $this->partnerFinalAdsOnly = false;
        $this->massSearch = '';
        $this->massSearchTerms = [];
        $this->noFile = false;
        $this->outputNamePattern = '';
        $this->selectedFiles = [];
        $this->resetPage();
    }

    public function appendOutputToken(string $token): void
    {
        $allowedTokens = ['<nota>', '<ordem>', '<sequencia>'];

        if (! in_array($token, $allowedTokens, true)) {
            return;
        }

        $this->outputNamePattern .= $token;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFileType(): void
    {
        $this->selectedFiles = [];
        $this->resetPage();
    }

    public function updatingService(): void
    {
        $this->selectedFiles = [];
        $this->resetPage();
    }

    public function updatingCompanySelected(): void
    {
        $this->selectedFiles = [];
        $this->resetPage();
    }

    public function updatingRubricSelected(): void
    {
        $this->selectedFiles = [];
        $this->resetPage();
    }

    public function updatingPartnerFinalAdsOnly(): void
    {
        $this->selectedFiles = [];
        $this->resetPage();
    }

    public function updatedSelectedFiles($value)
    {
        $selected = collect((array) $value)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($selected->isEmpty()) {
            $this->selectedFiles = [];
            return;
        }

        $restrictedIds = [];

        if (! $this->isSuperAdm()) {
            $restrictedIds = File::whereIn('id', $selected->all())
                ->whereHas('Adsforms', function ($q) {
                    $q->where('tacit', true)
                        ->whereNotNull('work_report_id');
                })
                ->pluck('id')
                ->all();
        }

        $this->selectedFiles = $selected
            ->reject(fn ($id) => in_array($id, $restrictedIds, true))
            ->take(self::MAX_DOWNLOAD_SELECTION)
            ->values()
            ->all();

        if ($selected->count() > self::MAX_DOWNLOAD_SELECTION) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Limite de 100 arquivos por download.',
                'timer'    => 3000,
            ]);
        }
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
            if ($file->isTacitAdsRestricted() && !auth()->user()?->superadm) {
                $this->dispatchBrowserEvent('swal', [
                    'position' => 'center',
                    'icon'     => 'warning',
                    'title'    => 'DOWNLOAD BLOQUEADO',
                    'html'     => 'Arquivo de ADS tácita disponível apenas para SUPERADM.',
                    'timer'    => 5000,
                ]);

                return;
            }

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

        if (count($this->selectedFiles) > self::MAX_DOWNLOAD_SELECTION) {
            $this->selectedFiles = array_slice($this->selectedFiles, 0, self::MAX_DOWNLOAD_SELECTION);

            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Foram considerados apenas os 100 primeiros arquivos.',
                'timer'    => 3500,
            ]);
        }

        $files = File::with(['Note.Orders', 'Service', 'Adsforms'])
            ->whereIn('id', $this->selectedFiles)
            ->get();

        if ($files->isEmpty()) {
            $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'error',
            'title'    => 'Arquivos não encontrados!',
            'timer'    => 3000,
            ]);
            return;
        }

        if (!auth()->user()?->superadm && $files->contains(fn (File $file) => $file->isTacitAdsRestricted())) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'DOWNLOAD ZIP BLOQUEADO',
                'html'     => 'O lote contém ADS tácita. Apenas SUPERADM pode baixar.',
                'timer'    => 5000,
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

            $usedNames = [];

            foreach ($files as $index => $file) {
                // Verificar se o arquivo existe no storage e localmente
                if (Storage::exists($file->path)) {
                    $fullPath = storage_path('app/' . $file->path);

                    // Verificar se o arquivo físico existe no sistema de arquivos
                    if (file_exists($fullPath) && is_readable($fullPath)) {
                        $fileName = $this->buildOutputFileName($file, $index + 1, $usedNames);
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
        ->with(['Note.Orders', 'Service', 'User.Company'])
        ->withExists([
            'Adsforms as has_tacit_ads_restriction' => function ($q) {
                $q->where('tacit', true)
                    ->whereNotNull('work_report_id');
            },
        ])
        ->when($searchTerm = trim((string) $this->search), function ($q) use ($searchTerm) {
            $q->where(function ($sq) use ($searchTerm) {
                $sq->where('file_name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('original_name', 'like', '%'.$searchTerm.'%')
                    ->orWhereRelation('Note', 'note', $searchTerm)
                    ->orWhereHas('Note.Orders', fn ($orderQuery) => $orderQuery->where('ordem', $searchTerm));
            });
        })
        ->when($this->currentMassSearchTerms(), function ($q, $terms) {
            $q->whereHas('Note', function ($sq) use ($terms) {
                $sq->whereIn('note', $terms)
                    ->orWhereHas('Orders', fn ($orderQuery) => $orderQuery->whereIn('ordem', $terms));
            });
        })
        ->when($this->service, function ($q) {
            $q->where('service_id', $this->service);
        })
        ->when($this->fileType, function ($q) {
            $this->applyFileTypeFilter($q);
        })
        ->when($this->partnerFinalAdsOnly, function ($q) {
            $q->where('path', 'like', 'arquivos/ADS_FINAL/%')
                ->whereHas('Adsforms');
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

    public function fileTypeLabel(File $file): string
    {
        return $this->fileTypeOptions[$this->classifyFile($file)] ?? 'Outros';
    }

    public function preferredOrder(File $file): string
    {
        return $this->resolvePreferredOrder($file->Note);
    }

    private function currentMassSearchTerms(): array
    {
        if ($this->massSearchTerms) {
            return $this->massSearchTerms;
        }

        return $this->massSearch
            ? $this->formatTextToArray($this->massSearch)
            : [];
    }

    private function applyFileTypeFilter($query): void
    {
        if ($this->fileType === 'outros') {
            $patterns = collect($this->fileTypePatterns())->flatten()->unique()->values();

            $query->where(function ($fileQuery) use ($patterns) {
                foreach ($patterns as $pattern) {
                    $fileQuery->where(function ($unknownTypeQuery) use ($pattern) {
                        $unknownTypeQuery->whereNull('file_name')
                            ->orWhere('file_name', 'not like', "%{$pattern}%");
                    })->where(function ($unknownTypeQuery) use ($pattern) {
                        $unknownTypeQuery->whereNull('original_name')
                            ->orWhere('original_name', 'not like', "%{$pattern}%");
                    })->where(function ($unknownTypeQuery) use ($pattern) {
                        $unknownTypeQuery->whereDoesntHave('Service')
                            ->orWhereRelation('Service', 'service', 'not like', "%{$pattern}%");
                    });
                }

                $fileQuery->where(function ($extensionQuery) {
                    $extensionQuery->whereNull('ext')
                        ->orWhereNotIn('ext', ['jpg', 'jpeg', 'png', 'webp', 'bmp']);
                });
            });

            return;
        }

        $patterns = $this->fileTypePatterns()[$this->fileType] ?? [];

        $query->where(function ($fileQuery) use ($patterns) {
            foreach ($patterns as $pattern) {
                $fileQuery->orWhere('file_name', 'like', "%{$pattern}%")
                    ->orWhere('original_name', 'like', "%{$pattern}%")
                    ->orWhereRelation('Service', 'service', 'like', "%{$pattern}%");
            }

            if ($this->fileType === 'fotos') {
                $fileQuery->orWhereIn('ext', ['jpg', 'jpeg', 'png', 'webp', 'bmp']);
            }
        });
    }

    private function fileTypePatterns(): array
    {
        return [
            'ads' => ['ads', 'adicional'],
            'projeto' => ['projeto', 'proj'],
            'croqui' => ['croqui'],
            'inventario' => ['inventario', 'inventário', 'invent'],
            'fotos' => ['foto', 'imagem', 'img'],
        ];
    }

    private function classifyFile(File $file): string
    {
        $name = Str::lower(Str::ascii(($file->original_name ?: '') . ' ' . ($file->file_name ?: '') . ' ' . ($file->Service?->service ?: '')));
        $extension = Str::lower($file->ext ?: pathinfo($file->file_name ?: '', PATHINFO_EXTENSION));

        foreach ($this->fileTypePatterns() as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($name, Str::lower(Str::ascii($pattern)))) {
                    return $type;
                }
            }
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'bmp'], true)) {
            return 'fotos';
        }

        return 'outros';
    }

    private function buildOutputFileName(File $file, int $sequence, array &$usedNames): string
    {
        $savedName = $file->file_name ?: 'arquivo';
        $extension = Str::lower($file->ext ?: pathinfo($file->file_name ?: '', PATHINFO_EXTENSION));
        $extension = $extension ? '.' . ltrim($extension, '.') : '';

        if (! trim((string) $this->outputNamePattern)) {
            $candidate = $this->ensureExtension($savedName, $extension);
            $suffix = 2;

            while (in_array($candidate, $usedNames, true)) {
                $name = pathinfo($candidate, PATHINFO_FILENAME);
                $candidate = $name . '-' . $suffix . $extension;
                $suffix++;
            }

            $usedNames[] = $candidate;

            return $candidate;
        }

        $replacements = [
            '<nota>' => $file->Note?->note ?: 'sem-nota',
            '<ordem>' => $this->resolvePreferredOrder($file->Note) ?: 'sem-ordem',
            '<sequencia>' => str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
        ];

        $name = str_replace(array_keys($replacements), array_values($replacements), $this->outputNamePattern);
        $name = $this->sanitizeOutputName($name) ?: 'arquivo-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
        $candidate = $name . $extension;
        $suffix = 2;

        while (in_array($candidate, $usedNames, true)) {
            $candidate = $name . '-' . $suffix . $extension;
            $suffix++;
        }

        $usedNames[] = $candidate;

        return $candidate;
    }

    private function ensureExtension(string $name, string $extension): string
    {
        if (! $extension) {
            return $this->sanitizeOutputName($name) ?: 'arquivo';
        }

        if (str_ends_with(Str::lower($name), $extension)) {
            return $this->sanitizeOutputName($name);
        }

        return ($this->sanitizeOutputName($name) ?: 'arquivo') . $extension;
    }

    private function sanitizeOutputName(string $name): string
    {
        $name = Str::ascii($name);
        $name = preg_replace('/[\\\\\/:*?"<>|]+/', '-', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name, '-_. ');
    }

    private function resolvePreferredOrder($note): string
    {
        if (! $note) {
            return '';
        }

        if (! $note->relationLoaded('Orders')) {
            $note->load('Orders');
        }

        $orders = $note->Orders->pluck('ordem')->filter()->map(fn ($order) => (string) $order);

        foreach (['170', '190', '150'] as $prefix) {
            $match = $orders->first(fn ($order) => str_starts_with($order, $prefix));

            if ($match) {
                return $match;
            }
        }

        return $orders->first() ?: '';
    }

    private function isSuperAdm(): bool
    {
        return (bool) auth()->user()?->superadm;
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
