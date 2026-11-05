<?php

namespace App\Http\Livewire\Admin\Audits;

use App\Http\Livewire\Concerns\WithListFilters;
use App\Models\{Audit, Note, Service, User};
use App\Services\Audit\AuditPresenter;
use Illuminate\Database\Eloquent\Builder;
use Livewire\{Component, WithPagination};

/**
 * Auditoria de notas (lista de controle: docs/PADRAO_LISTAS_OPERACIONAIS.md; detalhes em docs/admin/AUDITORIA.md).
 * Filtra pelas colunas indexadas de `audits` (nota, atividade, registro, usuário, modelo, data), nunca pelo JSON; o JSON só é lido
 * para as linhas da página.
 */
class NoteAudit extends Component
{
    use WithListFilters;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public const PERIODS = ['today' => 'Hoje', '7d' => '7 dias', '30d' => '30 dias', 'all' => 'Tudo', 'custom' => 'Período'];

    public string $note = '';

    public string $record = '';

    public string $period = '7d';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public int $perPage = 50;

    /** Auditoria aberta no modal. */
    public ?int $detailId = null;

    protected $queryString = [
        'note'     => ['except' => '', 'as' => 'nota'],
        'record'   => ['except' => '', 'as' => 'registro'],
        'period'   => ['except' => '7d', 'as' => 'periodo'],
        'dateFrom' => ['except' => null, 'as' => 'inicio'],
        'dateTo'   => ['except' => null, 'as' => 'fim'],
        'perPage'  => ['except' => 50, 'as' => 'pp'],
        'filters'  => ['except' => [], 'as' => 'f'],
    ];

    public function mount(): void
    {
        $this->period  = array_key_exists($this->period, self::PERIODS) ? $this->period : '7d';
        $this->filters = $this->normalizeFilters($this->filters);
    }

    protected function listFilterDefinitions(): array
    {
        return [
            'action'  => ['label' => 'Ação'],
            'model'   => ['label' => 'Modelo'],
            'service' => ['label' => 'Atividade'],
            'user'    => ['label' => 'Usuário'],
            'field'   => ['label' => 'Campo alterado'],
        ];
    }

    protected function listFilterOptions(): array
    {
        return [
            'action' => collect(AuditPresenter::ACTIONS)->map(fn ($a, $key) => ['v' => $key, 'l' => $a[0]])->values()->all(),
            'model'  => $this->rememberFilterOptions('model', fn () => Audit::query()->whereNotNull('model_class')->distinct()->orderBy('model_class')->pluck('model_class')
                ->map(fn ($class) => ['v' => $class, 'l' => AuditPresenter::MODELS[$class] ?? class_basename($class)])->all()),
            'service' => $this->rememberFilterOptions('service', fn () => Service::orderBy('service')->get(['uuid', 'service'])
                ->map(fn ($s) => ['v' => (string) $s->uuid, 'l' => $s->service])->all()),
            'user' => $this->rememberFilterOptions('user', fn () => User::withTrashed()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($u) => ['v' => (string) $u->id, 'l' => $u->name])->all()),
            'field' => collect(AuditPresenter::FIELDS)->map(fn ($label, $key) => ['v' => $key, 'l' => $label])->sortBy('l')->values()->all(),
        ];
    }

    public function updatingNote(): void
    {
        $this->resetPage();
    }

    public function updatingRecord(): void
    {
        $this->resetPage();
    }

    public function updatedPeriod(): void
    {
        $this->period = array_key_exists($this->period, self::PERIODS) ? $this->period : '7d';
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array($this->perPage, [25, 50, 100, 250], true) ? $this->perPage : 50;
        $this->resetPage();
    }

    public function periodLabel(): string
    {
        return match ($this->period) {
            'today'  => 'hoje',
            'custom' => 'período escolhido',
            default  => 'últimos ' . self::PERIODS[$this->period],
        };
    }

    /** Tem algum critério além do período padrão? (alimenta "Limpar filtros" e o estado vazio) */
    public function hasCriteria(): bool
    {
        return $this->note !== '' || $this->record !== '' || $this->filters !== [] || $this->period !== '7d';
    }

    public function clearAllCriteria(): void
    {
        $this->reset(['note', 'record', 'dateFrom', 'dateTo']);
        $this->period = '7d';
        $this->clearListFilters();
    }

    public function openDetails(int $auditId): void
    {
        $this->detailId = $auditId;
    }

    public function closeDetails(): void
    {
        $this->detailId = null;
    }

    public function render()
    {
        $presenter = new AuditPresenter();
        $audits    = $this->query()->with('User:id,name')->paginate($this->perPage);
        $detail    = $this->detailId && ($audit = Audit::with('User:id,name')->find($this->detailId))
            ? ['audit' => $audit] + $presenter->detail($audit)
            : null;

        return view('livewire.admin.audits.note-audit', [
            'rows'     => $presenter->rows($audits->getCollection()),
            'audits'   => $audits,
            'detail'   => $detail,
            'noteMiss' => $this->note !== '' && $this->noteIds() === [],
            'periodOn' => $this->note === '' && $this->record === '',
        ]);
    }

    /** Números de nota (ou OV) que casam com a busca. Memo por requisição: a consulta e o aviso leem a mesma lista. */
    private ?array $noteIdsMemo = null;

    private function noteIds(): array
    {
        if ($this->note === '') {
            return [];
        }

        return $this->noteIdsMemo ??= Note::query()
            ->where('note', trim($this->note))
            ->orWhereHas('Orders', fn ($orders) => $orders->where('ordem', trim($this->note)))
            ->limit(50)
            ->pluck('id')
            ->all();
    }

    /** Uma consulta só, só em colunas indexadas. Busca por nota/registro vale para todo o histórico (ignora o período). */
    protected function query(): Builder
    {
        $query = Audit::query()->orderByDesc('created_at')->orderByDesc('id');

        if ($this->note !== '') {
            $query->whereIn('note_id', $this->noteIds() ?: [0]);
        }

        if ($this->record !== '') {
            $query->where('record_id', trim($this->record));
        }

        if ($this->note === '' && $this->record === '') {
            [$from, $to] = $this->range();

            $from && $query->where('created_at', '>=', $from);
            $to && $query->where('created_at', '<=', $to);
        }

        foreach (['action' => 'action', 'model' => 'model_class', 'service' => 'service_id', 'user' => 'user_id'] as $key => $column) {
            if ($values = $this->filterValues($key)) {
                $query->whereIn($column, $values);
            }
        }

        if ($fields = $this->filterValues('field')) {
            $query->where(function (Builder $q) use ($fields) {
                foreach ($fields as $field) {
                    $q->orWhere('changed_fields', 'like', '%,' . addcslashes($field, '%_\\') . ',%');
                }
            });
        }

        return $query;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function range(): array
    {
        return match ($this->period) {
            'today'  => [now()->startOfDay()->toDateTimeString(), null],
            '7d'     => [now()->subDays(7)->startOfDay()->toDateTimeString(), null],
            '30d'    => [now()->subDays(30)->startOfDay()->toDateTimeString(), null],
            'custom' => [
                $this->dateFrom ? $this->dateFrom . ' 00:00:00' : null,
                $this->dateTo ? $this->dateTo . ' 23:59:59' : null,
            ],
            default => [null, null],
        };
    }
}
