<?php

namespace App\Http\Livewire\Partner;

use App\Exports\Partner\WorkInformsExport;
use App\Http\Livewire\Concerns\WithListFilters;
use App\Http\Livewire\Partner\Concerns\{AuthorizesPartnerAccess, PartnerListToolkit};
use App\Models\{File, WorkReport};
use App\Services\Files\FileStorageService;
use App\Services\WorkReports\WorkReportStatusResolver;
use App\Support\Ops\Tone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\{Component, WithPagination};

/**
 * Obras informadas (lista de controle do portal da parceira: docs/PADRAO_LISTAS_OPERACIONAIS.md §10).
 * Escopo por empresa vem de `applyPartnerCompanyScope`; o filtro Empresa só reduz esse escopo.
 */
class Workedlist extends Component
{
    use AuthorizesPartnerAccess;
    use PartnerListToolkit;
    use WithListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /** Situação do informe: chave do filtro => rótulo. */
    private const SITUATIONS = [
        'informed' => 'Informado',
        'rejected' => 'Rejeitado',
        'canceled' => 'Cancelado',
    ];

    public $perPage = 50;

    public $search = '';

    public $multiSearch = '';

    // Busca por data de informe
    public $month = '';

    public $date_in = '';

    public $date_out = '';

    protected $queryString = [
        'search'   => ['except' => '', 'as' => 'buscar'],
        'page'     => ['except' => 1, 'as' => 'p'],
        'perPage'  => ['as' => 'pp'],
        'month'    => ['except' => '', 'as' => 'mes_referencia'],
        'date_in'  => ['except' => '', 'as' => 'data_inicial'],
        'date_out' => ['except' => '', 'as' => 'data_final'],
        'filters'  => ['except' => [], 'as' => 'f'],
    ];

    protected $listeners = [
        'refresh_list' => '$refresh',
    ];

    public function mount(): void
    {
        $this->perPage = in_array((int) $this->perPage, [25, 50, 100, 250, 500], true) ? (int) $this->perPage : 50;
        $this->filters = $this->normalizeFilters($this->filters);
    }

    protected function listFilterDefinitions(): array
    {
        return [
            'company'   => ['label' => 'Empreiteira'],
            'situation' => ['label' => 'Situação'],
            'status'    => ['label' => 'Status atual'],
            'rubrica'   => ['label' => 'Rubrica'],
            'region'    => ['label' => 'Região'],
            'city'      => ['label' => 'Município', 'depends' => ['region']],
        ];
    }

    protected function listFilterOptions(): array
    {
        return [
            'company'   => $this->companyFilterOptions(),
            'situation' => collect(self::SITUATIONS)->map(fn ($label, $key) => ['v' => $key, 'l' => $label])->values()->all(),
            'status'    => collect($this->currentStatusOptions())->map(fn ($label, $key) => ['v' => $key, 'l' => $label])->values()->all(),
        ] + $this->reportLocationFilterOptions();
    }

    /** @return array<string, string> chave => rótulo do status atual do informe */
    private function currentStatusOptions(): array
    {
        return [
            'inform'                   => WorkReportStatusResolver::INFORM,
            'waiting_fiscalization'    => WorkReportStatusResolver::WAITING_FISCALIZATION,
            'fiscalization'            => WorkReportStatusResolver::FISCALIZATION,
            'waiting_payment'          => WorkReportStatusResolver::WAITING_PAYMENT,
            'payment'                  => 'Em Medição',
            'payment_finished'         => 'Medição Finalizada',
            'finalized'                => WorkReportStatusResolver::FINALIZED,
            'inconsistent_payment'     => WorkReportStatusResolver::INCONSISTENT_PAYMENT,
            'waiting_d5_dispatch'      => WorkReportStatusResolver::WAITING_D5_DISPATCH,
            'waiting_d5_resolution'    => WorkReportStatusResolver::WAITING_D5_RESOLUTION,
            'waiting_d5_fiscalization' => WorkReportStatusResolver::WAITING_D5_FISCALIZATION,
            'd5_fiscalization'         => WorkReportStatusResolver::D5_FISCALIZATION,
            'waiting_d5_payment'       => WorkReportStatusResolver::WAITING_D5_PAYMENT,
            'releasing_letter'         => WorkReportStatusResolver::RELEASING_LETTER,
        ];
    }

    public function exportToExcel()
    {
        return (new WorkInformsExport($this->lists))
            ->download(date('Ymd_his') . '-ListaObrasInformadas.xlsx');
    }

    public function updatedMonth(): void
    {
        if ($this->month) {
            $date           = Carbon::createFromFormat('Y-m', $this->month);
            $this->date_in  = $date->copy()->startOfMonth()->format('Y-m-d');
            $this->date_out = $date->copy()->endOfMonth()->format('Y-m-d');
        } else {
            $this->date_in  = '';
            $this->date_out = '';
        }

        $this->resetPage();
    }

    public function updatedDateIn(): void
    {
        $this->month = $this->date_in ? Carbon::parse($this->date_in)->format('Y-m') : '';
        $this->resetPage();
    }

    public function updatedDateOut(): void
    {
        $this->resetPage();
    }

    /** "Limpar filtros": busca, datas e filtros. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->search      = '';
        $this->multiSearch = '';
        $this->date_in     = '';
        $this->date_out    = '';
        $this->month       = '';
        $this->clearListFilters();
    }

    /** Cartões-resumo clicam como filtro rápido de situação (um por vez; clicar de novo desmarca). */
    public function quickSituation(string $situation): void
    {
        $current = $this->filterValues('situation');

        $this->applyListFilters(array_merge($this->filters, ['situation' => $current === [$situation] ? [] : [$situation]]));
    }

    public function applyMultiSearch(): void
    {
        $this->search = implode(' ', $this->parseSearchTerms($this->multiSearch));
        $this->resetPage();
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

    /** Tom semântico (`green|blue|amber|red|teal|muted`) da classe de cor guardada no informe (`text-bg-*`). */
    public function classTone(?string $class): string
    {
        return Tone::fromBootstrap($class ? str_replace('text-bg-', '', $class) : null);
    }

    public function downloadFile($id)
    {
        if ($file = File::find($id)) {
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
    }

    /** Informes no escopo de permissão da parceira, sem busca nem filtros (base da lista e dos cartões). */
    private function scopedQuery()
    {
        $query = WorkReport::query();

        $this->applyPartnerCompanyScope($query);

        return $this->applyPartnerBranchScopeToNoteRelation($query);
    }

    /** Mesma consulta da tela, do Excel e (sem o filtro de situação) dos cartões. */
    public function getListsProperty()
    {
        $query = $this->applyCompanyFilter($this->scopedQuery());

        if ($this->date_in) {
            $query->whereDate('informed_at', '>=', $this->date_in);
        }

        if ($this->date_out) {
            $query->whereDate('informed_at', '<=', $this->date_out);
        }

        if ($terms = $this->parseSearchTerms($this->search)) {
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhereRelation('Note', 'note', 'like', "%{$term}%")
                        ->orWhereRelation('Note', 'numPedido', 'like', "%{$term}%")
                        ->orWhereRelation('Orders', 'ordem', 'like', "%{$term}%");
                }
            });
        }

        $this->applyNoteLocationFilters($query);

        if ($status = $this->filterValues('status')) {
            $query->whereIn('current_status_key', $status);
        }

        if ($situation = $this->filterValues('situation')) {
            $query->where(function ($q) use ($situation) {
                foreach ($situation as $key) {
                    match ($key) {
                        'canceled' => $q->orWhere('canceled', true),
                        'rejected' => $q->orWhere(fn ($x) => $x->where('canceled', false)->where('rejected', true)),
                        'informed' => $q->orWhere(fn ($x) => $x->where('canceled', false)->where('rejected', false)),
                        default    => null,
                    };
                }
            });
        }

        return $query
            ->with([
                'Note.Files.service',
                'Note.OldAds' => fn ($q) => $q->orderBy('date', 'asc'),
                'Orders',
                'Equipment:id,work_report_id',
                'Company:id,name',
                'Adsform',
            ])
            ->orderByRaw('COALESCE(informed_at, created_at) DESC')
            ->orderByDesc('id');
    }

    /** Contagens do cabeçalho em uma só consulta agrupada (retrato do escopo da parceira; ignoram busca e filtros). Cache de 60 s. */
    private function counts(): array
    {
        $key = 'partner-worked-counts:' . auth()->id() . ':' . md5(json_encode($this->filterValues('company')));

        return Cache::remember($key, 60, function () {
            $row = $this->applyCompanyFilter($this->scopedQuery())
                ->selectRaw('SUM(canceled = 0 AND rejected = 0) as informed')
                ->selectRaw('SUM(canceled = 0 AND rejected = 1) as rejected')
                ->selectRaw('SUM(canceled = 1) as canceled')
                ->first();

            return [
                'informed' => (int) $row->informed,
                'rejected' => (int) $row->rejected,
                'canceled' => (int) $row->canceled,
            ];
        });
    }

    public function render()
    {
        $lists = $this->lists->paginate($this->perPage);

        // O componente de arquivos lê `$file->note`/`$file->service`: reaproveita a nota já carregada em vez de consultar por arquivo.
        $files = $lists->getCollection()->flatMap(function ($report) {
            $report->Note?->Files->each(fn ($file) => $file->setRelation('note', $report->Note));

            return $report->Note?->Files ?? collect();
        });

        return view('livewire.partner.workedlist', [
            'lists'       => $lists,
            'counts'      => $this->counts(),
            'tacitFiles'  => $this->tacitFileIdsFor($files->pluck('id')),
            'hasCriteria' => $this->hasListFilters() || trim((string) $this->search) !== '' || $this->date_in || $this->date_out,
        ]);
    }
}
