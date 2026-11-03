<?php

namespace App\Http\Livewire\Dispatchs\Comission\Concerns;

use App\Custom\RuleBuilder;
use App\Helpers\TextFormatter;
use App\Http\Livewire\Concerns\WithNoteListFilters;
use App\Models\{Bancoupdate, City, Note, Production, Service, Wpa};
use App\Services\Comission\ComissionListRowPresenter;
use App\Support\SicodeRules;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB};
use Livewire\WithPagination;

/**
 * Lista operacional do Comissionamento (despacho e parceira), no padrão docs/PADRAO_LISTAS_OPERACIONAIS.md.
 * `Main` e `PartnerMain` só diferem na exportação; filtros, consulta, seleção e atribuição de DD ficam aqui.
 */
trait HasComissionList
{
    use TextFormatter;
    use WithNoteListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $service;

    public $perPage = 100;

    public $search;

    public $last_update;

    public $advanceSearch;

    public $multiSearch = [];

    public $selectall = false;

    public $selected = [];

    public $note_type = '';

    public $not_assigned = false;

    // Atribuição de DD em massa
    public $enter_dd;

    public $additionalData = [];

    public $additionalDataUpd = [];

    /** @var Collection|null Linhas da página já apresentadas (memo por requisição, zerado a cada render). */
    private ?Collection $rowsMemo = null;

    protected $listeners = [
        'refresh_dispatch' => '$refresh',
        'refresh_list'     => '$refresh',
        'getCopy'          => 'copy',
        'confirm_mass_dd'  => 'confirmed_mass_dd',
    ];

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'buscar'],
        'page'         => ['except' => 1, 'as' => 'p'],
        'perPage'      => ['as' => 'pp'],
        'note_type'    => ['except' => '', 'as' => 'tipo'],
        'not_assigned' => ['except' => false, 'as' => 'livres'],
        'filters'      => ['except' => [], 'as' => 'f'],
    ];

    public function mount($service)
    {
        $this->service     = Service::where('uuid', $service)->with('Status')->first();
        $this->last_update = Cache::remember('comission-dispatch:last-status-at', 60, fn () => optional(Note::orderByDesc('dt_status')->first())->dt_status);
    }

    // ---------- Filtros (WithListFilters) ----------

    protected function listFilterDefinitions(): array
    {
        return [
            'rubrica'  => ['label' => 'Rubrica'],
            'region'   => ['label' => 'Região'],
            'regional' => ['label' => 'Regional', 'depends' => ['region']],
            'city'     => ['label' => 'Município', 'depends' => ['region', 'regional']],
        ];
    }

    protected function listFilterCacheScope(): string
    {
        return static::class . $this->service->uuid;
    }

    protected function listFilterOptions(): array
    {
        return [
            'rubrica' => $this->rememberFilterOptions('rubrica', fn () => Note::query()
                ->where('nstats', $this->service->status)
                ->whereNotNull('rubrica')->where('rubrica', '!=', '')
                ->distinct()->orderBy('rubrica')->pluck('rubrica')
                ->map(fn ($r) => ['v' => (string) $r, 'l' => (string) $r])->all()),
        ] + $this->locationFilterOptions();
    }

    /** "Limpar tudo": filtros, busca (simples e em massa), tipo e atalho "Só livres". */
    public function clearAllCriteria(): void
    {
        $this->filters       = [];
        $this->search        = '';
        $this->multiSearch   = [];
        $this->advanceSearch = '';
        $this->not_assigned  = false;
        $this->note_type     = '';
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

    public function updatedSearch()
    {
        $this->multiSearch = [];
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedNoteType()
    {
        $this->resetPage();
    }

    public function filterStatus()
    {
        $this->not_assigned = !$this->not_assigned;
        $this->resetPage();
    }

    public function copy($msg)
    {
        $this->dispatchBrowserEvent('torrada', [
            'status'   => 'success',
            'menssage' => $msg,
        ]);
    }

    // ---------- Seleção ----------

    /** Mestre da página: se todas as linhas despacháveis já estão marcadas, desmarca; senão marca todas elas. */
    public function setSelectAll()
    {
        $selectedSet = array_fill_keys(array_map('strval', $this->selected), true);
        $eligible    = $this->rows()->filter(fn ($row) => $row['canDispatch'])->keys();
        $allChecked  = $eligible->isNotEmpty() && $eligible->every(fn ($key) => isset($selectedSet[(string) $key]));

        foreach ($eligible as $key) {
            if ($allChecked) {
                unset($selectedSet[(string) $key]);
            } else {
                $selectedSet[(string) $key] = true;
            }
        }

        $this->selected = array_map(strval(...), array_keys($selectedSet));
    }

    /** O mestre fica marcado quando todas as linhas despacháveis da página estão selecionadas. */
    protected function recomputeSelectAll(): void
    {
        $eligible = $this->rows()->filter(fn ($row) => $row['canDispatch'])->keys();
        $selected = array_map('strval', $this->selected);

        $this->selectall = $eligible->isNotEmpty() && $eligible->every(fn ($key) => in_array((string) $key, $selected, true));
    }

    /** Linhas da página apresentadas, por id da nota (uma vez por requisição). */
    protected function rows(): Collection
    {
        return $this->rowsMemo ??= (function () {
            $presenter = app(ComissionListRowPresenter::class);

            return $this->lists->getCollection()
                ->mapWithKeys(fn (Note $note) => [(string) $note->id => $presenter->present($note, $this->service->uuid)]);
        })();
    }

    // ---------- Despacho ----------

    public function get_single_note($note)
    {
        $this->selected = [$note];

        $this->go_att_mass();
    }

    public function go_att_mass()
    {
        $this->clean();

        if (!count($this->selected)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhuma nota foi selecionada para despacho!',
                'timer'    => 2500,
            ]);

            return;
        }

        $this->emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', array_values($this->selected));
    }

    public function closeall()
    {
        $this->dispatchBrowserEvent('hideModal');

        $this->selected       = [];
        $this->selectall      = false;
        $this->additionalData = [];

        $this->emit('refresh_dispatch');
    }

    public function clean()
    {
        $this->enter_dd       = '';
        $this->additionalData = [];
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->search      = '';
            $this->multiSearch = array_values($this->formatTextToArray($this->advanceSearch));
        } else {
            $this->multiSearch = [];
        }

        $this->resetPage();

        if (count($this->multiSearch)) {
            $this->selected  = [];
            $this->selectall = false;
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    /**
     * Atribuição de DD em Mmassa.
     *
     * Aqui foi montando uma arquitetura para que seja possível associar uma nota a uma DD, na tabela
     * WPAs. Assim sendo, quando carregar as DD para atribuição em massa, o mesmo ja seja inserido nas
     * correspondentes.
     */

    public function mass_modal()
    {
        if (!trim($this->enter_dd)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'warning',
                'title'    => 'Nenhum entrada para atribuição',
                'timer'    => 5000,
            ]);

            return;
        }

        $additionalData    = [];
        $additionalDataUpd = [];

        $linhas = explode("\n", trim($this->enter_dd));

        if ($linhas && count($linhas)) {
            $count = 0;
            $ok    = 0;

            foreach ($linhas as $linha) {

                if ($linha) {
                    $coluna = explode("\t", $linha);

                    if (!(count($coluna) > 1)) {
                        $coluna = explode(";", $linha);
                    }

                    if (!(count($coluna) > 1)) {
                        $coluna = explode(" ", $linha);
                    }

                    if (!(count($coluna) > 1)) {
                        $coluna = explode(",", $linha);
                    }

                    if (!(count($coluna) > 1)) {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'warning',
                            'title'    => "Gentileza separar os valores com alguma forma válida: ' ', ';', ','.",

                        ]);

                        return;
                    }

                    if (preg_match('/^[0-9]+$/', $coluna[0]) && preg_match('/^[0-9]+$/', $coluna[1])) {

                        $dd = Wpa::Where('dd', trim($coluna[1]))->first();

                        if ($dd && !$dd->production_id) {

                            $note = Note::find($dd->note_id);

                            if ($note->note != trim($coluna[0])) {
                                $this->dispatchBrowserEvent('swal', [
                                    'position' => 'center',
                                    'icon'     => 'warning',
                                    'title'    => "A DD {$dd->dd} já foi atribuída a Nota: {$note->note}. (Nenhuma Nota foi associada. Verifique novamente.)",

                                ]);

                                return;
                            }
                        } elseif ($dd && $dd->production_id) {

                            $production = Production::with('Note', 'User')->find($dd->production_id);

                            if ($production) {

                                $name_user = isset($production->User) ? $production->User->name : 'Desconhecido';

                                $this->dispatchBrowserEvent('swal', [
                                    'position' => 'center',
                                    'icon'     => 'warning',
                                    'title'    => "A DD {$dd->dd} já foi atribuída para a nota {$production->Note->note} despachada para {$name_user}",

                                ]);

                            } else {
                                $this->dispatchBrowserEvent('swal', [
                                    'position' => 'center',
                                    'icon'     => 'error',
                                    'title'    => "A DD {$dd->dd} parece que ja foi despachada anteriormente, porém não encontrei relação de produção. Informe ao ADM.",

                                ]);
                            }

                            return;
                        }

                        $dd   = Wpa::WhereRelation('Note', 'note', trim($coluna[0]))->whereNull('production_id')->first();
                        $note = Note::where('note', trim($coluna[0]))->first();

                        if (!$dd && $note) {

                            if (count($additionalData)) {
                                $dd_encontrada = array_search(trim($coluna[1]), array_column($additionalData, 'dd'));

                                if ($dd_encontrada !== false) {

                                    $this->dispatchBrowserEvent('swal', [
                                        'position' => 'center',
                                        'icon'     => 'warning',
                                        'title'    => "DD Duplicada",
                                        'html'     => "Você está inserindo a <strong>DD: {$coluna[1]}</strong> duplicada. <br> Revise novamente as DDs a serem cadastradas.<br> Nenhuma DD foi associada.",

                                    ]);

                                    return;

                                }
                            }

                            if (count($additionalDataUpd)) {
                                $dd_encontrada = array_search(trim($coluna[1]), array_column($additionalDataUpd, 'dd'));

                                if ($dd_encontrada !== false) {

                                    $this->dispatchBrowserEvent('swal', [
                                        'position' => 'center',
                                        'icon'     => 'warning',
                                        'title'    => "DD Duplicada",
                                        'html'     => "Você está inserindo a <strong>DD: {$coluna[1]}</strong> duplicada.  <br> Revise novamente as DDs a serem cadastradas.<br> <p><strong>Nenhuma DD foi associada.</strong></p>",

                                    ]);

                                    return;

                                }
                            }

                            $additionalData[] = [
                                'note_id' => $note->id,
                                'dd'      => trim($coluna[1]),
                            ];

                            $ok++;

                        }

                        if (($dd && $note) && ($dd->dd != trim($coluna[1]))) {

                            if (count($additionalData)) {
                                $dd_encontrada = array_search(trim($coluna[1]), array_column($additionalData, 'dd'));

                                if ($dd_encontrada !== false) {

                                    $this->dispatchBrowserEvent('swal', [
                                        'position' => 'center',
                                        'icon'     => 'warning',
                                        'title'    => "DD Duplicada",
                                        'html'     => "Você está inserindo a <strong>DD: {$coluna[1]}</strong> duplicada.  <br> Revise novamente as DDs a serem cadastradas.<br> <p><strong>Nenhuma DD foi associada.</strong></p>",

                                    ]);

                                    return;

                                }
                            }

                            if (count($additionalDataUpd)) {
                                $dd_encontrada = array_search(trim($coluna[1]), array_column($additionalDataUpd, 'dd'));

                                if ($dd_encontrada !== false) {

                                    $this->dispatchBrowserEvent('swal', [
                                        'position' => 'center',
                                        'icon'     => 'warning',
                                        'title'    => "DD Duplicada",
                                        'html'     => "Você está inserindo a <strong>DD: {$coluna[1]}</strong> duplicada.  <br> Revise novamente as DDs a serem cadastradas.<br> <p><strong>Nenhuma DD foi associada.</strong></p>",

                                    ]);

                                    return;

                                }
                            }

                            $additionalDataUpd[] = [
                                'id' => $dd->id,
                                'dd' => $coluna[1],
                            ];

                            $ok++;
                        }

                    } else {
                        $this->dispatchBrowserEvent('swal', [
                            'position' => 'center',
                            'icon'     => 'warning',
                            'title'    => "Existem Notas/OV ou DD com caracteres inválidos (SOMENTE NÚMEROS SÃO PERMITIDOS). Confira novamente.",

                        ]);

                        return;
                    }

                }

            }

            if (count($additionalData) || count($additionalDataUpd)) {

                $count = count($additionalData) + count($additionalDataUpd);

                $this->additionalData    = $additionalData;
                $this->additionalDataUpd = $additionalDataUpd;

                $this->dispatchBrowserEvent('alertar', [
                    'title'         => 'Confirmar Atribuir DD?',
                    'msg'           => "Você está prestes a atribuir {$count} de {$ok} notas DD, Deseja Continuar?",
                    'icon'          => 'info',
                    'btnOktxt'      => 'Sim, Continue!',
                    'btnCanceltxt'  => 'Não, Cancele',
                    'action'        => "confirm_mass_dd",
                    'cancel_titulo' => 'Cancelado!',
                    'cancel_msg'    => 'Nenhuma Nota Atribuída.',

                ]);
            }

        }
    }

    public function confirmed_mass_dd()
    {
        $count = count($this->additionalData);
        $error = 0;

        foreach ($this->additionalData as $wpa) {
            if (!Wpa::create($wpa)) {
                $error++;
            }
        }

        $countUpd = count($this->additionalDataUpd);
        $errorUpd = 0;

        foreach ($this->additionalDataUpd as $wpa) {
            $idToUpdate = $wpa['id'];
            unset($wpa['id']);

            if (!Wpa::where('id', $idToUpdate)->update($wpa)) {
                $errorUpd++;
            }
        }

        $totalErrors = $error + $errorUpd;
        $totalCount  = $count + $countUpd;

        if (!$totalErrors) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'success',
                'title'    => 'Notas DDs associadas com sucesso',
                'timer'    => 2500,
            ]);

            $this->closeall();
        } else {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => "OOPS!, Ocorreram {$totalErrors} de {$totalCount} ao associar as DD às Notas.",
                'timer'    => 8000,
            ]);
        }
    }

    // ---------- Consulta ----------

    /** Notas do Comissionamento visíveis para o usuário (contrato), com busca, filtros e atalhos aplicados. */
    protected function listQuery()
    {
        $query = Note::query()->excludeCanceledFullDone();

        SicodeRules::applyContractDispatchMainVisibility(
            $query,
            Auth()->User(),
            $this->service->uuid,
            fn ($statusQuery) => RuleBuilder::applyRules($statusQuery, $this->service->Status)
        );

        $term    = trim((string) $this->search);
        $rubrica = $this->filterValues('rubrica');
        $base    = $this->base;

        if ($term !== '') {
            $like = '%' . $term . '%';

            $query->where(function ($q) use ($like) {
                $q->where('note', 'like', $like)
                    ->orWhere('material', 'like', $like)
                    ->orWhere('numPedido', 'like', $like)
                    ->orWhere('group1', 'like', $like)
                    ->orWhere('group2', 'like', $like)
                    ->orWhere('group4', 'like', $like)
                    ->orWhere('group5', 'like', $like);
            });
        }

        if ($this->multiSearch) {
            $query->whereIn('note', $this->multiSearch);
        }

        $query->when($base, fn ($q) => $q->where(fn ($w) => $w->whereIn('nexp', $base)->orWhereNull('nexp')));
        $query->when($rubrica, fn ($q) => $q->where(fn ($w) => $w->whereIn('rubrica', $rubrica)->orWhereNull('rubrica')));
        $query->when($this->note_type, fn ($q) => $q->where(fn ($w) => $w->where('type_note', $this->note_type)->orWhereNull('type_note')));

        if ($this->not_assigned) {
            $query->where(function ($q) {
                $q->doesntHave('Productions')
                    ->orWhereDoesntHave('Productions', function ($subquery) {
                        $subquery->where('service_id', $this->service->uuid)
                            ->where('confirmed', false);
                    });
            });
        }

        return $query;
    }

    public function getListsProperty()
    {
        $uuid = $this->service->uuid;

        return $this->listQuery()
            ->with([
                'Productions' => fn ($q) => $q->where('service_id', $uuid)->with(['User:id,name', 'Company:id,name']),
                'Wpas',
            ])
            ->orderBy('type_note', 'DESC')
            ->orderBy('days_left')
            ->paginate($this->perPage);
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

    protected function listView(string $view)
    {
        $this->rowsMemo = null;

        $lists = $this->lists;
        $this->recomputeSelectAll();

        return view($view, [
            'lists'        => $lists,
            'rows'         => $this->rows(),
            'legalNoteIds' => $this->legalLinkedNoteIds($lists->pluck('id')->all()),
            'update'       => Cache::remember('comission-dispatch:bancoupdate', 60, fn () => Bancoupdate::orderByDesc('created_at')->first()),
        ]);
    }
}
