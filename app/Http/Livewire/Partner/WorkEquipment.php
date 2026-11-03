<?php

namespace App\Http\Livewire\Partner;

use App\Helpers\SelectOptions;
use App\Http\Livewire\Concerns\WithListFilters;
use App\Http\Livewire\Partner\Concerns\{AuthorizesPartnerAccess, PartnerListToolkit};
use App\Jobs\Partner\ExportDeclaredEquipmentJob;
use App\Services\Partner\DeclaredEquipmentQueryService;
use App\Services\PartnerAccess\PartnerAccessGate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\{Component, WithPagination};

/**
 * Equipamentos declarados nos informes (lista de controle do portal da parceira: docs/PADRAO_LISTAS_OPERACIONAIS.md §10).
 * A consulta (e o escopo por empresa) fica em `DeclaredEquipmentQueryService`, a mesma da exportação.
 */
class WorkEquipment extends Component
{
    use AuthorizesPartnerAccess;
    use PartnerListToolkit;
    use WithListFilters;
    use WithPagination;

    private const EXCEL_EXPORT_LIMIT = 10000;

    protected $paginationTheme = 'bootstrap';

    public $perPage = 50;

    public int $excelExportLimit = self::EXCEL_EXPORT_LIMIT;

    public $search = '';

    public $date_in = '';

    public $date_out = '';

    public $month = '';

    protected $queryString = [
        'search'   => ['except' => '', 'as' => 'buscar'],
        'page'     => ['except' => 1, 'as' => 'p'],
        'perPage'  => ['as' => 'pp'],
        'date_in'  => ['except' => '', 'as' => 'data_inicial'],
        'date_out' => ['except' => '', 'as' => 'data_final'],
        'month'    => ['except' => '', 'as' => 'mes'],
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
            'company'  => ['label' => 'Empreiteira'],
            'movement' => ['label' => 'Movimento'],
            'type'     => ['label' => 'Equipamento'],
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'city'     => ['label' => 'Município', 'depends' => ['region']],
        ];
    }

    protected function listFilterOptions(): array
    {
        return [
            'company'  => $this->companyFilterOptions(),
            'movement' => [
                ['v' => '1', 'l' => 'Instalação'],
                ['v' => '0', 'l' => 'Desinstalação'],
            ],
            'type' => collect(SelectOptions::getEquipmentOptions())->map(fn ($e) => ['v' => (string) $e->nick, 'l' => (string) $e->info])->all(),
        ] + $this->reportLocationFilterOptions();
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

    public function updatedMonth(): void
    {
        if ($this->month) {
            $this->date_in  = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
            $this->date_out = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');
        } else {
            $this->date_in  = '';
            $this->date_out = '';
        }

        $this->resetPage();
    }

    public function updatedDateIn(): void
    {
        $this->resetPage();
    }

    public function updatedDateOut(): void
    {
        $this->resetPage();
    }

    /** Cartões-resumo clicam como filtro rápido de movimento (um por vez; clicar de novo desmarca). */
    public function quickMovement(string $movement): void
    {
        $current = $this->filterValues('movement');

        $this->applyListFilters(array_merge($this->filters, ['movement' => $current === [$movement] ? [] : [$movement]]));
    }

    /** "Limpar filtros": busca, datas e filtros. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->search   = '';
        $this->date_in  = '';
        $this->date_out = '';
        $this->month    = '';
        $this->clearListFilters();
    }

    public function exportFile(string $format): void
    {
        $this->authorizePartnerAccess('conclusion_reports.export');

        $format = $format === ExportDeclaredEquipmentJob::FORMAT_XLSX
            ? ExportDeclaredEquipmentJob::FORMAT_XLSX
            : ExportDeclaredEquipmentJob::FORMAT_CSV;

        $total = $this->lists->count();

        if ($format === ExportDeclaredEquipmentJob::FORMAT_XLSX && $total > self::EXCEL_EXPORT_LIMIT) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Volume alto para Excel',
                'html'     => "Esta consulta tem {$total} registros. Para mais de " . self::EXCEL_EXPORT_LIMIT . ' registros, use CSV.',
                'timer'    => 6000,
            ]);

            return;
        }

        ExportDeclaredEquipmentJob::dispatch($this->exportParams(), (string) auth()->id(), $format);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Exportacao em andamento',
            'html'     => 'Voce sera notificado quando o arquivo estiver pronto.',
            'timer'    => 4000,
        ]);
    }

    public function getListsProperty()
    {
        return app(DeclaredEquipmentQueryService::class)->build($this->exportParams(), auth()->user());
    }

    /** Parâmetros da consulta e do Job de exportação (mesmas chaves, para a tela e o arquivo nunca divergirem). */
    private function exportParams(): array
    {
        return [
            'search'     => $this->search,
            'equipTypes' => $this->filterValues('type'),
            'moviments'  => $this->filterValues('movement'),
            'companyIds' => $this->filterValues('company'),
            'date_in'    => $this->date_in,
            'date_out'   => $this->date_out,
            'filters'    => [
                'rubrica' => $this->filterValues('rubrica'),
                'region'  => $this->filterValues('region'),
                'city'    => $this->filterValues('city'),
            ],
        ];
    }

    /** Contagens do cabeçalho (instalações x desinstalações) numa só consulta, no recorte atual sem o filtro de movimento. */
    private function counts(): array
    {
        $params              = $this->exportParams();
        $params['moviments'] = [];

        return Cache::remember('partner-equipment-counts:' . auth()->id() . ':' . md5(json_encode($params)), 60, function () use ($params) {
            $row = app(DeclaredEquipmentQueryService::class)->build($params, auth()->user())
                ->reorder()
                ->selectRaw('COALESCE(SUM(equipment.installed = 1), 0) as installed')
                ->selectRaw('COALESCE(SUM(equipment.installed = 0), 0) as removed')
                ->first();

            return ['installed' => (int) $row->installed, 'removed' => (int) $row->removed];
        });
    }

    public function render()
    {
        $equipments = $this->lists
            ->with(['WorkReport.Note', 'WorkReport.Company:id,name'])
            ->paginate($this->perPage);

        return view('livewire.partner.work-equipment', [
            'equipments'  => $equipments,
            'counts'      => $this->counts(),
            'hasCriteria' => $this->hasListFilters() || trim((string) $this->search) !== '' || $this->date_in || $this->date_out,
            'canExport'   => PartnerAccessGate::allows(auth()->user(), 'conclusion_reports.export'),
        ]);
    }
}
