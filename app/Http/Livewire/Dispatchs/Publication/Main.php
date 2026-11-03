<?php

namespace App\Http\Livewire\Dispatchs\Publication;

use App\Exports\Dispatchs\PublicationExportList;
use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\{Bancoupdate, City, Note, Service};
use App\Repositories\PublishRepository;
use App\Services\Publication\PublicationListRowPresenter;
use App\Services\WorkReports\WorkReportFinalScopeResolver;
use App\Support\SicodeRules;
use App\Traits\WildcardFormmater;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{Cache, DB};
use Livewire\{Component, WithPagination};

/**
 * Despacho → Lista para Publicação. Lista operacional no padrão docs/PADRAO_LISTAS_OPERACIONAIS.md.
 * `PartnerMain` (parceira) herda tudo e só muda a exportação (fila), a view e as linhas fora do escopo de publicação.
 */
class Main extends Component
{
    use WithPagination;
    use WithNoteListFilters;
    use TextFormatter;
    use WildcardFormmater;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $last_update;

    public $advanceSearch;

    public $multiSearch = [];

    public $selectall = false;

    public $selected = [];

    /** Tipo de nota: '1' Nota, '2' OV, '' ambos. */
    public $typeNote = '';

    public $not_assigned = false;

    /** Busca em massa em qualquer status (ignora a exigência de informe liberado). */
    public $all_services = false;

    protected $listeners = [
        'refresh_dispatch' => '$refresh',
        'refresh_list'     => '$refresh',
        'getCopy'          => 'copy',
    ];

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'buscar'],
        'page'         => ['except' => 1, 'as' => 'p'],
        'perPage'      => ['as' => 'pp'],
        'typeNote'     => ['except' => '', 'as' => 'tipo'],
        'not_assigned' => ['except' => false, 'as' => 'livres'],
        'filters'      => ['except' => [], 'as' => 'f'],
    ];

    private $publishRepository;

    /** Página já paginada e apresentada, calculada uma vez por requisição (tela, "selecionar todos" e checagem do mestre leem a mesma). */
    private ?array $pageMemo = null;

    public function boot(PublishRepository $publishRepository)
    {
        $this->publishRepository = $publishRepository;
    }

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('publication-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    // ---------- Estado e atalhos ----------

    public function updatedSearch()
    {
        $this->multiSearch  = [];
        $this->all_services = false;
        $this->gotoPage(1);
    }

    public function updatedPerPage()
    {
        $this->gotoPage(1);
    }

    public function updatedTypeNote()
    {
        $this->clearSelection();
        $this->gotoPage(1);
    }

    public function filterStatus()
    {
        $this->not_assigned = !$this->not_assigned;
        $this->clearSelection();
        $this->gotoPage(1);
    }

    /** "Limpar tudo": filtros, busca (simples e em massa), tipo e atalhos. Não mexe na quantidade por página. */
    public function clearAllCriteria(): void
    {
        $this->filters       = [];
        $this->search        = '';
        $this->multiSearch   = [];
        $this->advanceSearch = '';
        $this->all_services  = false;
        $this->not_assigned  = false;
        $this->typeNote      = '';
        $this->afterListFiltersChanged();
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    // ---------- Seleção ----------

    /** Marca/desmarca as linhas selecionáveis (sem atividade aberta) da página atual. */
    public function updatedSelectall($val)
    {
        $keys = collect($this->pageState()['rows'])->where('canSelect', true)->pluck('key')->all();

        if ($val) {
            $this->selected = array_values(array_unique(array_merge(array_map('strval', $this->selected), $keys)));
        } else {
            $this->selected = array_values(array_diff(array_map('strval', $this->selected), $keys));
        }
    }

    // ---------- Busca em massa ----------

    public function buscarMulti()
    {
        $this->gotoPage(1);

        $this->search        = '';
        $this->multiSearch   = array_values($this->formatTextToArray((string) $this->advanceSearch));
        $this->advanceSearch = '';

        $this->clearSelection();
        $this->dispatchBrowserEvent('hideModal');
    }

    public function clean()
    {
        $this->multiSearch   = [];
        $this->advanceSearch = '';
        $this->all_services  = false;
        $this->search        = '';
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->selected      = [];
        $this->advanceSearch = '';
        $this->search        = '';
        $this->gotoPage(1);

        $this->emit('refresh_dispatch');
    }

    // ---------- Despacho (modal compartilhado) ----------

    public function get_single_note($note)
    {
        $this->selected = [(string) $note];

        $this->go_att_mass();
    }

    public function go_att_mass()
    {
        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        $bulkAnyStatus = (bool) $this->effectiveAnyStatus();

        $payload = collect($this->selected)->map(function ($key) use ($bulkAnyStatus) {
            [$noteId, $workReportId] = array_pad(explode(':', (string) $key, 3), 3, null);

            return [
                'note_id'         => (int) $noteId,
                'work_report_id'  => $workReportId ? (int) $workReportId : null,
                'bulk_any_status' => $bulkAnyStatus,
            ];
        })->filter(fn ($item) => $item['note_id'] > 0)->values()->all();

        $this->emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', $payload);
    }

    // ---------- Exportação ----------

    public function export_excel()
    {
        if (!count($this->selected)) {
            return (new PublicationExportList($this->getListsProperty(), $this->service))->download(date('YmdHis-') . 'PublicationExportList.xlsx');
        }

        return (new PublicationExportList($this->getListsProperty()->whereIn('notes.id', $this->selectedNoteIds()), $this->service))->download(date('YmdHis-') . 'PublicationExportListSelected.xlsx');
    }

    // ---------- Filtros ----------

    /** Códigos de município (`notes.nexp`/`lexp`) dos filtros Região/Regional/Município; vazio sem filtro de localidade. */
    public function getBaseProperty(): array
    {
        $region = $this->filterValues('region');
        $local  = $this->filterValues('regional');
        $city   = $this->filterValues('city');

        if (!$region && !$local && !$city) {
            return [];
        }

        return City::query()
            ->when($region, fn ($q) => $q->whereIn('regiao', $region))
            ->when($local, fn ($q) => $q->whereIn('baseConstrucao', $local))
            ->when($city, fn ($q) => $q->whereIn('rdMunicipio', $city))
            ->pluck('rdMunicipio')->filter()->unique()->values()->all();
    }

    private function effectiveAnyStatus(): bool
    {
        return (bool) $this->all_services && count($this->multiSearch) > 0;
    }

    /**
     * Consulta única da lista e do Excel (e do Excel da parceira, que roda em fila e chama esta propriedade).
     * Devolve o Builder; a página é paginada e apresentada em `pageState()`.
     */
    public function getListsProperty()
    {
        $anyStatus = $this->effectiveAnyStatus();
        $uuid      = $this->service->uuid;
        $rubricas  = $this->filterValues('rubrica');
        $companies = $this->filterValues('company');
        $base      = $this->base;

        $query = $this->publishRepository->getBaseQuery($anyStatus, $uuid);

        // Usuários contratados só podem visualizar obras das empresas vinculadas a eles.
        $query = SicodeRules::applyContractDispatchListVisibility($query, Auth()->user(), $uuid);

        if (!$anyStatus) {
            $query->whereHas('WorkForms', fn ($sq) => $sq->where('rejected', false));
        }

        $query->when($rubricas, fn ($q) => $q->where(fn ($w) => $w->whereIn('rubrica', $rubricas)->orWhereNull('rubrica')))
            ->when($base, fn ($q) => $q->where(fn ($w) => $w->whereIn('nexp', $base)->orWhereIn('lexp', $base)->orWhereNull('lexp')))
            ->when($companies, fn ($q) => $q->whereHas('WorkForms', fn ($w) => $w->whereIn('company_id', $companies)))
            ->when($this->typeNote, fn ($q) => $q->where('notes.type_note', $this->typeNote));

        // Busca em massa (notas ou ordens)
        if ($this->multiSearch) {
            $terms = $this->multiSearch;
            $query->where(fn ($q) => $q->whereIn('note', $terms)
                ->orWhereHas('WorkForm.Orders', fn ($o) => $o->whereIn('ordem', $terms)));
        }

        // Pesquisa geral
        if ($this->search && !$this->multiSearch) {
            $search = $this->formatWithWildcard($this->search);

            $query->where(fn ($q) => $q->where('note', $search->type, $search->search)
                ->orWhere('material', $search->type, $search->search)
                ->orWhere('numPedido', $search->type, $search->search)
                ->orWhere('group2', $search->type, $search->search));
        }

        // Só livres: sem atividade com usuário (ou com atividade ainda sem usuário). Agrupado, para não "vazar" os demais filtros.
        $query->when($this->not_assigned, fn ($q) => $q->where(function ($w) use ($uuid) {
            $w->whereDoesntHave('Productions', fn ($p) => $p->where('service_id', $uuid)->whereNotNull('user_id'))
                ->orWhereHas('Productions', fn ($p) => $p->where('service_id', $uuid)
                    ->where(fn ($u) => $u->whereNull('user_id')->orWhere('user_id', '')));
        }));

        $query->with([
            'Productions' => fn ($q) => $q->where('service_id', $uuid)->select([
                'id', 'note_id', 'service_id', 'user_id', 'company_id',
                'completed', 'confirmed', 'status', 'created_at',
            ])->with(['User:id,name', 'Company:id,name']),
            'WorkForms' => fn ($q) => $q->where('canceled', false)->select([
                'id', 'note_id', 'company_id', 'date', 'informed_at',
                'created_at', 'rejected', 'canceled', 'selected_final_scopes',
            ])->with([
                'Note:id,type_note',
                'Company:id,name,deleted_at',
                'Orders:id,note_id,ordem,statusSist',
                'Orders.Operations:id,order_id,operacao,status',
                'FlowProductions:id,work_report_id,production_id,stage,is_current',
                'FlowProductions.Production:id,note_id,service_id,user_id,company_id,completed,confirmed,status,created_at',
                'FlowProductions.Production.User:id,name',
                'FiveNote',
            ]),
            'RamalForm.Company',
        ])
            ->select([
                'notes.*',
                DB::raw("
                    CASE
                        WHEN notes.type_note = 2 THEN DATE_ADD(CURDATE(), INTERVAL notes.days_left DAY)
                        WHEN notes.type_note = 1 THEN STR_TO_DATE(CONCAT('28/', SUBSTRING(notes.mesalization, 2, 2), '/', SUBSTRING(notes.mesalization, 5)), '%d/%m/%Y')
                        ELSE NULL
                    END as prazo_final
                "),
            ]);

        $query->orderByRaw('
            exists (
                select 1
                from ramal_reports
                where ramal_reports.note_id = notes.id
            )
            and not exists (
                select 1
                from work_reports
                where work_reports.note_id = notes.id
            ) desc
        ')
            ->orderBy('is45', 'DESC')
            ->orderBy('prazo_final', 'ASC');

        return $query;
    }

    // ---------- Linhas ----------

    /** Uma linha por informe de Publicação liberado (a mesma Nota pode ter vários informes). */
    private function expandPublicationRows($notes)
    {
        return $notes->flatMap(function (Note $note) {
            $reports  = $note->relationLoaded('WorkForms') ? $note->WorkForms : collect();
            $eligible = $reports->filter(function ($report) {
                $orders = $report->relationLoaded('Orders') ? $report->Orders : collect();

                return $report->rejected === false && $orders->contains(function ($order) {
                    return str_starts_with(strtoupper((string) $order->statusSist), 'LIB')
                        && $order->Operations->contains(fn ($operation) => (string) $operation->operacao === '0020'
                            && (str_starts_with(strtoupper((string) $operation->status), 'LIB')
                                || str_starts_with(strtoupper((string) $operation->status), 'CNPA')
                                || str_starts_with(strtoupper((string) $operation->status), 'JBFI LIB')));
                }) && collect($report->finalScopePayloads())->pluck('scope')->contains(fn ($scope) => app(WorkReportFinalScopeResolver::class)->publicationRequired($scope));
            })->values();

            if ($eligible->isEmpty()) {
                return $this->ineligibleRows($note);
            }

            return $eligible->map(function ($report) use ($note) {
                $row = clone $note;
                $row->setRelation('WorkForm', $report);
                $row->setRelation('WorkForms', collect([$report]));
                $row->setAttribute('publication_context_key', $note->id . ':' . $report->id);
                $row->setAttribute('dispatch_work_report_id', (int) $report->id);

                return $row;
            });
        })->values();
    }

    /** Nota sem informe com Publicação exigida: a lista do despacho não mostra (a parceira mostra, sem informe). */
    protected function ineligibleRows(Note $note): array
    {
        return [];
    }

    /** @return array{lists: LengthAwarePaginator, rows: array<int, array>} */
    private function pageState(): array
    {
        return $this->pageMemo ??= (function () {
            $lists = $this->getListsProperty()->paginate($this->perPage);
            $lists->setCollection($this->expandPublicationRows($lists->getCollection()));

            $presenter = app(PublicationListRowPresenter::class);
            $rows      = $lists->getCollection()->map(fn (Note $list) => $presenter->present($list, $this->service->uuid))->values()->all();

            return ['lists' => $lists, 'rows' => $rows];
        })();
    }

    /** Notas da página com demanda jurídica vinculada: 2 consultas no total, em vez de 2 por linha. */
    private function legalLinkedNoteIds(array $noteIds): array
    {
        if (!$noteIds) {
            return [];
        }

        $instructions = DB::table('legal_demand_note_instructions')->whereIn('note_id', $noteIds)->where('active', true)->pluck('note_id');
        $links        = DB::table('legal_demand_note_activity_links')->whereIn('note_id', $noteIds)->whereNull('unlinked_at')->pluck('note_id');

        return array_fill_keys($instructions->merge($links)->unique()->all(), true);
    }

    private function selectedNoteIds(): array
    {
        return collect($this->selected)
            ->map(fn ($key) => (int) explode(':', (string) $key, 2)[0])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function viewName(): string
    {
        return 'livewire.dispatchs.publication.main';
    }

    protected function heroSubtitle(): string
    {
        return 'Notas e OVs com informe liberado para publicação. Marque as linhas, escolha o responsável e acompanhe os prazos pela cor.';
    }

    public function render()
    {
        ['lists' => $lists, 'rows' => $rows] = $this->pageState();

        $selectable = collect($rows)->where('canSelect', true)->pluck('key')->all();
        $selected   = array_map('strval', $this->selected);

        $this->selectall = $selectable !== [] && array_diff($selectable, $selected) === [];

        return view($this->viewName(), [
            'lists'        => $lists,
            'rows'         => collect($rows),
            'legalNoteIds' => $this->legalLinkedNoteIds(collect($rows)->pluck('noteId')->unique()->all()),
            'update'       => Cache::remember('publication-dispatch:has-bancoupdate', 60, fn () => Bancoupdate::query()->exists()),
            'subtitle'     => $this->heroSubtitle(),
        ]);
    }
}
