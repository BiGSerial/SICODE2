<?php

namespace App\Services\Engineers;

use App\Models\{City, Company, Note, User, WorkReport};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\{Cache, DB};

/**
 * Consulta única da "Lista de informes" da Engenharia: a tela, os cartões de resumo e o Excel leem daqui, então nunca divergem.
 *
 * ADS do informe: a do próprio informe (`adsforms.work_report_id`), a da nota (legado, `adsforms.note_id`) ou o registro antigo (`old_ads_informs`).
 */
class WorkedListQuery
{
    public const ADS = [
        'com'      => 'Com ADS',
        'sem'      => 'Sem ADS',
        'tacita'   => 'ADS tácita',
        'atrasada' => 'ADS em atraso',
    ];

    public const SITUATIONS = [
        'informados' => 'Informados',
        'rejeitados' => 'Rejeitados',
        'todos'      => 'Todos',
    ];

    public const PRESETS = [
        'all'   => 'Todo o histórico',
        '7d'    => 'Últimos 7 dias',
        '30d'   => 'Últimos 30 dias',
        'month' => 'Mês atual',
        'prev'  => 'Mês anterior',
    ];

    /**
     * ADS existente para o informe, a nota (legado) ou registro antigo. Um EXISTS por coluna: com `OR` dentro de um só EXISTS o MySQL
     * deixa de usar os índices e a lista de 20 mil informes passa de um minuto.
     */
    private const HAS_ADS = '(EXISTS (SELECT 1 FROM adsforms a WHERE a.work_report_id = work_reports.id)'
        . ' OR EXISTS (SELECT 1 FROM adsforms a2 WHERE a2.note_id = work_reports.note_id)'
        . ' OR EXISTS (SELECT 1 FROM old_ads_informs o WHERE o.note_id = work_reports.note_id))';

    private const IS_TACIT = '(EXISTS (SELECT 1 FROM adsforms a WHERE a.work_report_id = work_reports.id AND a.tacit = 1)'
        . ' OR EXISTS (SELECT 1 FROM adsforms a2 WHERE a2.note_id = work_reports.note_id AND a2.tacit = 1))';

    /**
     * @param array<string, mixed> $f
     *
     * @return array<string, mixed>
     */
    public function normalize(array $f): array
    {
        $one = fn (string $key, array $allowed, string $default = '') => in_array($f[$key] ?? $default, $allowed, true) ? (string) ($f[$key] ?? $default) : $default;

        $from = $this->date($f['from'] ?? null)?->startOfDay();
        $to   = $this->date($f['to'] ?? null)?->endOfDay();

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [
            'from'     => $from,
            'to'       => $to,
            'search'   => trim((string) ($f['search'] ?? '')),
            'multi'    => array_values(array_filter((array) ($f['multi'] ?? []))),
            'company'  => ($f['company'] ?? null) ?: null,
            'rubrica'  => ($f['rubrica'] ?? null) ?: null,
            'city'     => ($f['city'] ?? null) ?: null,
            'ads'      => $one('ads', array_keys(self::ADS)),
            'changes'  => $one('changes', ['sim', 'nao']),
            'situacao' => $one('situacao', array_keys(self::SITUATIONS), 'informados'),
        ];
    }

    /** @return array{0: ?string, 1: ?string} intervalo (Y-m-d) de um atalho de período */
    public function presetRange(string $preset): array
    {
        $today = now();

        return match ($preset) {
            '7d'    => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
            '30d'   => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
            'month' => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
            'prev'  => [$today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            default => [null, null],
        };
    }

    /**
     * Informes do recorte, do mais recente para o mais antigo. Sem relações carregadas: use `withRelations()` para listar.
     *
     * @param array<string, mixed> $f normalize()
     */
    public function query(User $user, array $f): Builder
    {
        $q = WorkReport::query()->active();

        $this->applyAccess($q, $user);

        match ($f['situacao']) {
            'rejeitados' => $q->where('rejected', true),
            'todos'      => null,
            default      => $q->where('rejected', false),
        };

        if ($f['from']) {
            $q->where('informed_at', '>=', $f['from']);
        }

        if ($f['to']) {
            $q->where('informed_at', '<=', $f['to']);
        }

        if ($f['company']) {
            $q->where('company_id', $f['company']);
        }

        if ($f['rubrica']) {
            $q->whereRelation('Note', 'rubrica', $f['rubrica']);
        }

        if ($f['city']) {
            $q->whereRelation('Note', 'lexp', $f['city']);
        }

        if ($f['changes'] !== '') {
            $q->where('changes', $f['changes'] === 'sim');
        }

        if ($f['search'] !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $f['search']) . '%';

            $q->where(fn ($w) => $w->whereRelation('Note', 'note', 'like', $like)
                ->orWhereRelation('Orders', 'ordem', 'like', $like)
                ->orWhereRelation('Company', 'name', 'like', $like));
        }

        if ($f['multi']) {
            $q->where(fn ($w) => $w->whereRelation('Note', fn ($n) => $n->whereIn('note', $f['multi']))
                ->orWhereRelation('Orders', fn ($o) => $o->whereIn('ordem', $f['multi'])));
        }

        match ($f['ads']) {
            'com'      => $q->whereRaw(self::HAS_ADS),
            'sem'      => $q->whereRaw('NOT ' . self::HAS_ADS),
            'tacita'   => $q->whereRaw(self::IS_TACIT),
            'atrasada' => $q->whereRaw('NOT ' . self::HAS_ADS)->whereNotNull('ads_due_at')->where('ads_due_at', '<', now()),
            default    => null,
        };

        return $q->orderByRaw('COALESCE(work_reports.informed_at, work_reports.created_at) DESC')->orderByDesc('work_reports.id');
    }

    public function withRelations(Builder $query): Builder
    {
        return $query
            ->with([
                'Note:id,note,rubrica,lexp',
                'Note.Adsform',
                'Note.OldAds',
                'Company:id,name',
                'Orders:id,ordem',
                'Adsform',
            ])
            ->withCount(['Equipment', 'Returnwork']);
    }

    /**
     * Números dos cartões, no mesmo recorte da lista.
     *
     * @param array<string, mixed> $f normalize()
     *
     * @return array{total: int, with_ads: int, without_ads: int, tacit: int, overdue: int, changes: int, rejected: int}
     */
    public function summary(User $user, array $f): array
    {
        // Os sinais por informe são calculados uma vez na subconsulta e só então somados.
        $signals = $this->query($user, $f)->reorder()->toBase()->selectRaw(
            'work_reports.changes, work_reports.rejected, work_reports.ads_due_at,'
            . ' ' . self::HAS_ADS . ' as has_ads, ' . self::IS_TACIT . ' as is_tacit'
        );

        $row = DB::query()->fromSub($signals, 'w')->selectRaw(
            'COUNT(*) as total,'
            . ' COALESCE(SUM(has_ads), 0) as with_ads,'
            . ' COALESCE(SUM(is_tacit), 0) as tacit,'
            . ' COALESCE(SUM(has_ads = 0 AND ads_due_at IS NOT NULL AND ads_due_at < ?), 0) as overdue,'
            . ' COALESCE(SUM(changes), 0) as changes,'
            . ' COALESCE(SUM(rejected), 0) as rejected',
            [now()]
        )->first();

        return [
            'total'       => (int) $row->total,
            'with_ads'    => (int) $row->with_ads,
            'without_ads' => (int) $row->total - (int) $row->with_ads,
            'tacit'       => (int) $row->tacit,
            'overdue'     => (int) $row->overdue,
            'changes'     => (int) $row->changes,
            'rejected'    => (int) $row->rejected,
        ];
    }

    /**
     * Situação da ADS para exibir na linha.
     *
     * @return array{state: string, label: string, class: string, at: ?Carbon, due: ?Carbon}
     */
    public function adsOf(WorkReport $report): array
    {
        $ads = $report->Adsform ?? $report->Note?->Adsform;
        $due = $ads?->tacit_due_at ?: $report->ads_due_at;

        if ($ads) {
            $at = $ads->tacit ? $ads->tacit_delivered_at : $ads->created_at;

            return $ads->tacit
                ? ['state' => 'tacit', 'label' => 'Tácita', 'class' => 'text-bg-warning', 'at' => $at, 'due' => $due]
                : ['state' => 'arrived', 'label' => 'Chegou', 'class' => 'text-bg-success', 'at' => $at, 'due' => $due];
        }

        $old = $report->Note?->OldAds?->last();

        if ($old) {
            return ['state' => 'legacy', 'label' => 'Registro antigo', 'class' => 'text-bg-secondary', 'at' => $old->date, 'due' => null];
        }

        if ($due && $due->isPast()) {
            return ['state' => 'overdue', 'label' => 'Em atraso', 'class' => 'text-bg-danger', 'at' => null, 'due' => $due];
        }

        return ['state' => 'waiting', 'label' => $due ? 'Aguardando' : 'Sem prazo', 'class' => 'text-bg-light border', 'at' => null, 'due' => $due];
    }

    /**
     * Opções dos filtros dentro do que o usuário enxerga (listas cacheadas por 10 minutos).
     *
     * @return array{companies: \Illuminate\Support\Collection, rubricas: array<int, string>, cities: array<int, string>}
     */
    public function options(User $user): array
    {
        $scope = $user->superadm ? 'all' : $user->id;

        return [
            'companies' => Cache::remember("worked-list:companies:{$scope}", now()->addMinutes(10), function () use ($user) {
                $ids = $this->companyIds($user);

                return Company::query()->whereNull('deleted_at')
                    ->when($ids !== null, fn ($q) => $q->whereIn('id', $ids ?: ['0']))
                    ->orderBy('name')->get(['id', 'name']);
            }),
            'rubricas' => Cache::remember('worked-list:rubricas', now()->addMinutes(10), fn () => Note::query()
                ->whereIn('id', WorkReport::query()->select('note_id'))
                ->whereNotNull('rubrica')->where('rubrica', '<>', '')
                ->distinct()->orderBy('rubrica')->pluck('rubrica')->all()),
            'cities' => Cache::remember('worked-list:cities', now()->addMinutes(10), fn () => City::query()
                ->whereNotNull('cidade')->orderBy('cidade')->pluck('cidade')->unique()->values()->all()),
        ];
    }

    /** @return array<int, string>|null empresas visíveis (null = todas, para superadm) */
    private function companyIds(User $user): ?array
    {
        if ($user->superadm) {
            return null;
        }

        return $user->Companies->pluck('id')->push($user->Company?->id)->filter()->unique()->values()->all();
    }

    private function applyAccess(Builder $q, User $user): void
    {
        $ids = $this->companyIds($user);

        if ($ids !== null) {
            $q->whereIn('work_reports.company_id', $ids ?: ['0']);
        }
    }

    private function date(mixed $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
