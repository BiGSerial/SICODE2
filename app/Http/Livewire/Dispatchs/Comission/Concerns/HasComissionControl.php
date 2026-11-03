<?php

namespace App\Http\Livewire\Dispatchs\Comission\Concerns;

use App\Custom\WpaStatus;
use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Jobs\Dispatchs\ExportDispatchPaymentJob;
use App\Models\{City, Production, Service};
use App\Support\Ops\{Deadline, Tone};
use App\Traits\WildcardFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

/**
 * Lista de CONTROLE do Comissionamento (despacho e parceira), no padrão docs/PADRAO_LISTAS_OPERACIONAIS.md §10.
 * `Stack` e `PartnerStack` são idênticas; a única diferença é a view que a parceira renderiza.
 * Uma consulta só (`controlQuery()`) alimenta a lista e os cartões de status.
 */
trait HasComissionControl
{
    use TextFormatter;
    use WildcardFormatter;
    use WithNoteListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    /** UUID do serviço (usado nas consultas e nos subcomponentes). */
    public string $service;

    /** Modelo completo do serviço, para a view. */
    public Service $serviceInfo;

    public int $perPage = 50;

    public ?int $statusFilter = null;

    public string $search = '';

    public ?string $advanceSearch = null;

    public array $multiSearch = [];

    public string $note_type = '';

    /** Método (e não propriedade): o trait não redeclara `$queryString` do Livewire\Component. */
    protected function queryString(): array
    {
        return [
            'search'       => ['except' => '', 'as' => 'buscar'],
            'page'         => ['except' => 1, 'as' => 'p'],
            'perPage'      => ['as' => 'pp'],
            'note_type'    => ['except' => '', 'as' => 'tipo'],
            'statusFilter' => ['except' => null, 'as' => 'status'],
            'multiSearch'  => ['except' => []],
            'filters'      => ['except' => [], 'as' => 'f'],
        ];
    }

    /** Método (e não propriedade): o trait não redeclara `$listeners` do Livewire\Component. */
    protected function getListeners()
    {
        return array_merge(parent::getListeners(), [
            'resetFilters' => 'clearAllCriteria',
            'refresh_list' => '$refresh',
            'getCopy'      => 'copy',
        ]);
    }

    public function mount(string $service): void
    {
        $this->service     = $service;
        $this->serviceInfo = Service::where('uuid', $service)->with('Status')->firstOrFail();
    }

    // ---------- Filtros (WithListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . $this->service;
    }

    protected function listFilterOptions(): array
    {
        return $this->locationFilterOptions();
    }

    /** "Limpar filtros": filtros, busca (simples e em massa), tipo e cartão de status. */
    public function clearAllCriteria(): void
    {
        $this->filters       = [];
        $this->search        = '';
        $this->multiSearch   = [];
        $this->advanceSearch = null;
        $this->note_type     = '';
        $this->statusFilter  = null;
        $this->afterListFiltersChanged();
    }

    /** Códigos de município (`notes.nexp`) dos filtros Região/Regional/Município; vazio sem filtro de localidade. */
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

    // ---------- Estado da tela ----------

    public function updatedSearch(): void
    {
        if (!trim($this->search)) {
            $this->resetPage();

            return;
        }

        $this->statusFilter  = null;
        $this->multiSearch   = [];
        $this->advanceSearch = null;
        $this->resetPage();
    }

    public function updatedNoteType(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /** Cartão de status do painel: liga/desliga o status. */
    public function toggleStatus(int $status): void
    {
        $this->statusFilter = $this->statusFilter === $status ? null : $status;
        $this->resetPage();
    }

    public function buscarMulti(): void
    {
        if (!trim((string) $this->advanceSearch)) {
            return;
        }

        $this->statusFilter = null;
        $this->multiSearch  = array_values($this->formatTextToArray($this->advanceSearch ?? ''));

        if (count($this->multiSearch) > 0) {
            $this->search        = '';
            $this->advanceSearch = null;
            $this->resetPage();

            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function copy($msg): void
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    // ---------- Excel ----------

    public function exportToExcel(): void
    {
        ExportDispatchPaymentJob::dispatch([
            'service_uuid' => $this->service,
            'search'       => $this->search,
            'multiSearch'  => $this->multiSearch,
            'note_type'    => $this->note_type,
            'company_ids'  => null,
            'rubricas'     => null,
            'regions'      => $this->filterValues('region') ?: null,
            'regionals'    => $this->filterValues('regional') ?: null,
            'cities'       => $this->filterValues('city') ?: null,
        ], auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'icon'  => 'info',
            'title' => 'Exportação iniciada!',
            'text'  => 'Você será notificado quando o arquivo estiver pronto.',
        ]);
    }

    // ---------- Consulta ----------

    /**
     * Consulta única do controle (lista e cartões de status leem a mesma). `$withStatus = false` ignora o cartão de
     * status marcado, para os cartões contarem todos os status no mesmo recorte.
     */
    protected function controlQuery(bool $withStatus = true): Builder
    {
        $base = $this->base;

        return Production::query()
            ->where('productions.service_id', $this->service)
            ->where('productions.completed', false)
            ->leftJoin('notes as n', 'productions.note_id', '=', 'n.id')
            ->when(trim($this->search), function ($query) {
                $query->where(function ($q) {
                    $search = $this->formatWithWildcard($this->search);
                    $q->where('n.note', $search->type, $search->search)
                        ->orWhere('n.rubrica', $search->type, $search->search)
                        ->orWhere('n.lexp', $search->type, $search->search)
                        ->orWhere('productions.odi', $search->type, $search->search)
                        ->orWhere('productions.odd', $search->type, $search->search)
                        ->orWhere('productions.ods', $search->type, $search->search)
                        ->orWhereHas('user', fn ($sub) => $sub->where('name', $search->type, $search->search))
                        ->orWhereHas('note.orders', fn ($sub) => $sub->where('ordem', $search->type, $search->search));
                });
            })
            ->when(count($this->multiSearch) > 0, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('note', fn ($sub) => $sub->whereIn('note', $this->multiSearch)
                        ->orWhereIn('rubrica', $this->multiSearch)
                        ->orWhereIn('lexp', $this->multiSearch))
                        ->orWhereHas('user', fn ($sub) => $sub->whereIn('name', $this->multiSearch))
                        ->orWhereHas('note.orders', fn ($sub) => $sub->whereIn('ordem', $this->multiSearch));
                });
            })
            ->when($base, fn ($query) => $query->whereIn('n.nexp', $base))
            ->when($withStatus && $this->statusFilter, fn ($query) => $query->where('productions.status', $this->statusFilter))
            ->when($this->note_type !== '', fn ($query) => $query->where('n.type_note', $this->note_type));
    }

    public function getListsProperty()
    {
        return $this->controlQuery()
            ->addSelect('productions.*')
            ->addSelect(DB::raw('n.dt_created as dt_created'))
            ->with([
                'wpas:id,production_id,dd,execstats,ststusexec,completed_at',
                'user:id,name,deleted_at',
                'dispatcher:id,name',
                'note:id,note,dt_created,nstats,dt_status,rubrica,postes,lexp,type_note,mesalization,days_left,group2',
                'company:id,name',
            ])
            ->orderBy('priority', 'desc')
            ->orderBy('d5', 'desc')
            ->orderBy('dt_created', 'asc')
            ->orderBy('att_at', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($this->perPage);
    }

    /** Contagem por status (cartões), no mesmo recorte da lista mas sem o filtro de status. */
    public function getStatusCountsProperty(): array
    {
        return $this->controlQuery(false)
            ->select('productions.status as status')
            ->selectRaw('count(*) as total')
            ->groupBy('productions.status')
            ->orderBy('productions.status')
            ->pluck('total', 'status')
            ->all();
    }

    // ---------- Apresentação da linha ----------

    /**
     * Linha pronta: prazos e tons vêm daqui (regras da atividade, mesmos limites de antes), a view só desenha.
     *  - Prazo real: nota criada há mais de 30 dias vermelho, até 20 verde, entre os dois âmbar.
     *  - Em despacho: mais de 30 dias vermelho, até 21 verde, entre os dois âmbar.
     *  - Em atribuição: mais de 9 dias vermelho, até 7 verde, entre os dois âmbar.
     */
    protected function presentRow(Production $item): array
    {
        $today   = Carbon::today();
        $days    = fn ($date) => $date ? (int) Carbon::parse($date)->startOfDay()->diffInDays($today) : null;
        $created = $item->dt_created ? Carbon::parse($item->dt_created) : null;
        $age     = $days($created);
        $wpa     = $item->wpas?->last();

        return [
            'priority'       => (bool) $item->priority,
            'dispatcher'     => $this->shortName($item->dispatcher?->name),
            'dispatcherFull' => $item->dispatcher?->name,
            'wpa'            => $wpa ? $this->wpaCell($wpa) : null,
            'created'        => $created?->copy()->addDays(30),
            'createdAge'     => $age,
            'createdTone'    => $created ? Deadline::age($age, 20, 30) : 'muted',
            'attDays'        => $item->att_at ? (int) $item->att_at->diffInDays(Carbon::now()) : null,
            'dispatchAge'    => $days($item->dispatch_at),
            'dispatchTone'   => $item->dispatch_at ? Deadline::age($days($item->dispatch_at), 21, 30) : 'muted',
            'attAge'         => $days($item->att_at),
            'attTone'        => $item->att_at ? Deadline::age($days($item->att_at), 7, 9) : 'muted',
        ];
    }

    private function shortName(?string $name): string
    {
        if (!$name) {
            return 'Desconhecido';
        }

        $parts = explode(' ', trim($name));

        return $parts[0] . ' ' . end($parts);
    }

    /** Situação da DD no WPA: ícone, texto simples e tom. */
    private function wpaCell($wpa): array
    {
        $status = WpaStatus::status($wpa->dd, $wpa->execstats, $wpa->completed_at);
        $text   = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace('</p>', ' ', (string) $status->info))));

        return [
            'icon'  => $status->icon,
            'label' => $text !== '' ? $text : null,
            'tone'  => Tone::fromBootstrap(str_replace('text-bg-', '', (string) $status->bg_color)),
        ];
    }

    /** Notas da página com demanda jurídica vinculada: 2 consultas no total, em vez de 2 por linha. */
    protected function legalLinkedNoteIds(array $noteIds): array
    {
        if (!$noteIds) {
            return [];
        }

        $instructions = DB::table('legal_demand_note_instructions')->whereIn('note_id', $noteIds)->where('active', true)->pluck('note_id');
        $links        = DB::table('legal_demand_note_activity_links')->whereIn('note_id', $noteIds)->whereNull('unlinked_at')->pluck('note_id');

        return array_fill_keys($instructions->merge($links)->unique()->all(), true);
    }

    protected function controlView(string $view)
    {
        $lists = $this->lists;

        return view($view, [
            'lists'        => $lists,
            'rows'         => $lists->getCollection()->mapWithKeys(fn (Production $item) => [$item->id => $this->presentRow($item)]),
            'legalNoteIds' => $this->legalLinkedNoteIds($lists->pluck('note_id')->all()),
            'statusCounts' => $this->statusCounts,
            'serviceInfo'  => $this->serviceInfo,
            'service'      => $this->service,
        ]);
    }
}
