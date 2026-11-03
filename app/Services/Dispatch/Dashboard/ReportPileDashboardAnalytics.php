<?php

namespace App\Services\Dispatch\Dashboard;

use App\Models\{Adsform, Service};
use App\Repositories\PublishRepository;
use App\Services\Payment\{MeasurementDeadlinePolicy, NoteFilter};
use App\Services\Publication\PublicationDeadlinePolicy;
use App\Services\Supervision\SupervisionDispatchListQuery;
use App\Support\SicodeRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard das atividades que NÃO olham para status: Pagamento (Medição), Publicação e Fiscalização. A pilha é a mesma da lista de
 * despacho de cada uma (repositório próprio), o tempo conta desde o INFORME e o prazo vem da regra parametrizada de cada atividade:
 *
 *  - Pagamento: prazo da medição (`MeasurementDeadlinePolicy`; `payment.measurement_deadline`: dias, corridos ou úteis) sobre a data do
 *    informe (ou da fiscalização da parcial). Vencido = vermelho; vencendo (até 4 dias) = âmbar; no prazo = verde.
 *  - Publicação: prazo para publicar após a entrega do informe (`SicodeRules::publicationDeadlineDays()`, 2 dias por padrão).
 *    Vencido = vermelho; vence hoje = âmbar; depois = verde.
 *  - Fiscalização: prazo de entrega da ADS do informe (`work_reports.ads_due_at`, regra `ads.delivery_deadline`). Informe com ADS já
 *    entregue não tem mais prazo. Vencido = vermelho; vence hoje ou amanhã = âmbar; depois = verde.
 *
 * A unidade é a nota (Pagamento, Publicação) ou o informe/linha da lista (Fiscalização). Mesmo formato de retorno do pai.
 */
class ReportPileDashboardAnalytics extends ActivityDashboardAnalytics
{
    private const FOLDERS = ['pagamento' => 'payment', 'publicacao' => 'publication', 'fiscalizacao' => 'supervision'];

    public static function supports(Service $service): bool
    {
        return isset(self::FOLDERS[(string) $service->folder]);
    }

    private function kind(Service $service): string
    {
        return self::FOLDERS[(string) $service->folder] ?? 'payment';
    }

    public function profile(?Service $service = null): array
    {
        $kind = $service ? $this->kind($service) : 'payment';

        $days = fn (int $n, bool $business) => $n . ' ' . ($n === 1 ? 'dia' : 'dias') . ' ' . ($business ? 'úteis' : 'corridos');

        $deadline = match ($kind) {
            'publication' => [
                'title' => 'Prazo de publicação',
                'sub'   => 'Dias para vencer o prazo de publicar, por quem está com a nota',
                'axis'  => 'Dias para vencer',
                'rule'  => 'Prazo: ' . $days(SicodeRules::publicationDeadlineDays(), SicodeRules::publicationDeadlineUsesBusinessDays()) . ' após a entrega do informe. Vermelho: vencido; âmbar: vence hoje; verde: depois.',
            ],
            'supervision' => [
                'title' => 'Prazo de entrega da ADS',
                'sub'   => 'Dias para vencer a entrega da ADS do informe, por quem está com ele',
                'axis'  => 'Dias para vencer',
                'rule'  => 'Prazo: ' . $days(SicodeRules::adsDeliveryDeadlineDays(), SicodeRules::adsDeliveryDeadlineUsesBusinessDays()) . ' após o informe. Informe com ADS entregue não tem mais prazo. Vermelho: vencido; âmbar: vence hoje ou amanhã; verde: depois.',
            ],
            default => [
                'title' => 'Prazo da medição',
                'sub'   => 'Dias para vencer o prazo da medição, por quem está com a nota',
                'axis'  => 'Dias para vencer',
                'rule'  => 'Prazo: ' . $days(SicodeRules::paymentMeasurementDeadlineDays(), SicodeRules::paymentMeasurementDeadlineUsesBusinessDays()) . ' após o informe. Vermelho: vencido; âmbar: até 4 dias; verde: acima.',
            ],
        };

        return [
            'key'      => $kind,
            'unit'     => $kind === 'supervision' ? 'informes' : 'notas',
            'time'     => ['avg' => 'Média desde o informe', 'title' => 'Tempo desde o informe', 'sub' => 'Quantos ' . ($kind === 'supervision' ? 'informes' : 'notas') . ' há por dias desde o informe, em atribuição e na pilha', 'axis' => 'Dias desde o informe', 'column' => 'Desde o informe', 'basis' => 'Dias desde a entrega do informe'],
            'deadline' => $deadline,
            'pile'     => 'a mesma pilha da lista de despacho (' . ['payment' => 'notas liberadas para medição', 'publication' => 'notas liberadas para publicação', 'supervision' => 'informes, parciais e D5 para fiscalizar'][$kind] . ').',
        ];
    }

    /** A pilha de cada atividade vem do seu repositório; filtros de tipo e rubrica são comuns. */
    protected function stack(Service $service, array $f): Builder
    {
        $query = match ($this->kind($service)) {
            'publication' => app(PublishRepository::class)->getBaseQuery(false, $service->uuid),
            'supervision' => app(SupervisionDispatchListQuery::class)->build(['serviceUuid' => $service->uuid, 'user' => null]),
            default       => app(NoteFilter::class)->filter(null, 'payments', []),
        };

        if ($f['type'] !== '') {
            $query->where('notes.type_note', (int) $f['type']);
        }

        if (!$f['tracking']) {
            $query->whereRaw("LOWER(TRIM(COALESCE(notes.rubrica, ''))) <> ?", ['acompanhamento']);
        }

        // A lista ordena por colunas calculadas que não existem fora dela: o dashboard só agrega.
        return $query->withoutEagerLoads()->reorder();
    }

    protected function isTooLarge(Service $service, array $f): bool
    {
        return false;
    }

    protected function aggregatePile(Service $service, array $f, ?int $criticalLimit): array
    {
        $rows = $this->pileRows($service, $f);
        $open = DB::table('productions as p')
            ->leftJoin('users', 'users.id', '=', 'p.user_id')
            ->leftJoin('companies', 'companies.id', '=', 'p.company_id')
            ->where('p.service_id', $service->uuid)
            ->where('p.confirmed', false)
            ->whereIn('p.note_id', collect($rows)->pluck('note_id')->unique()->all())
            ->orderByDesc('p.created_at')
            ->get(['p.note_id', 'users.name as user_name', DB::raw('LOWER(COALESCE(companies.name, \'\')) as company')])
            ->groupBy('note_id');

        $today  = now()->startOfDay();
        $total  = $assigned = $overdue = $sum = $count = $oldest = 0;
        $age    = [];
        $due    = [];
        $detail = [];

        foreach ($rows as $row) {
            $production = $open->get($row['note_id'])?->first();
            $isAssigned = $production !== null;
            $days       = $row['informed_at'] ? max(0, (int) $row['informed_at']->copy()->startOfDay()->diffInDays($today)) : null;

            $total++;
            $assigned += $isAssigned ? 1 : 0;
            $overdue += $row['remaining'] !== null && $row['remaining'] < 0 ? 1 : 0;

            if ($days !== null) {
                $sum += $days;
                $count++;
                $oldest = max($oldest, $days);
            }

            $label                            = $days === null ? 'Sem data' : ($days >= 30 ? '30+' : (string) $days);
            $age[$label][$isAssigned ? 1 : 0] = ($age[$label][$isAssigned ? 1 : 0] ?? 0) + 1;

            $series = match (true) {
                !$isAssigned                                         => 'Na pilha',
                str_contains((string) $production->company, 'satel') => 'SATEL',
                str_contains((string) $production->company, 'edp')   => 'Própria (EDP)',
                default                                              => 'Outras empresas',
            };

            $bucket                = $row['remaining'] === null ? 'Sem prazo' : ($row['remaining'] < 0 ? 'Vencido' : ($row['remaining'] > 30 ? '30+' : (string) $row['remaining']));
            $due[$bucket][$series] = ($due[$bucket][$series] ?? 0) + 1;

            $detail[] = [
                'id'             => (int) $row['note_id'],
                'note'           => $row['note'],
                'kind'           => $row['type_note'] === 2 ? 'OV' : 'Nota',
                'rubrica'        => $row['rubrica'],
                'city'           => $row['city'],
                'days_left'      => $row['remaining'],
                'deadline_used'  => null,
                'deadline_label' => $this->remainingLabel($row['remaining'], $row['delivered']),
                'deadline_tone'  => $row['tone'],
                'status_days'    => $days,
                'status_at'      => $row['informed_at'],
                'assigned'       => $isAssigned,
                'assigned_user'  => $production->user_name ?? null,
            ];
        }

        usort($detail, fn ($a, $b) => [$a['days_left'] === null ? 1 : 0, (int) $a['days_left'], (string) $a['status_at']] <=> [$b['days_left'] === null ? 1 : 0, (int) $b['days_left'], (string) $b['status_at']]);

        return [
            'summary' => [
                'total'              => $total,
                'assigned'           => $assigned,
                'unassigned'         => max(0, $total - $assigned),
                'overdue'            => $overdue,
                'avg_status_days'    => $count ? round($sum / $count, 1) : 0.0,
                'oldest_status_days' => $oldest,
            ],
            'status_age' => $this->statusAgeSeries($age),
            'deadline'   => $this->dueSeries($due),
            'users'      => $this->userSeries($this->userCounts($service, $f)),
            'critical'   => $criticalLimit ? array_slice($detail, 0, $criticalLimit) : $detail,
        ];
    }

    /**
     * Uma linha por nota (Pagamento, Publicação) ou por informe/linha da lista (Fiscalização), já com a data do informe e o prazo.
     *
     * @return array<int, array<string, mixed>>
     */
    private function pileRows(Service $service, array $f): array
    {
        $kind = $this->kind($service);
        $base = $this->stack($service, $f);

        if ($kind === 'supervision') {
            $lines     = (clone $base)->select('notes.id as note_id', 'notes.note', 'notes.type_note', 'notes.rubrica', 'notes.lexp as city', 'dispatch_rows.work_report_id as wr_id', 'work_reports.informed_at as wr_informed', 'work_reports.created_at as wr_created', 'work_reports.ads_due_at as ads_due', 'dispatch_rows.row_created_at as row_at')->get();
            $delivered = DB::table((new Adsform())->getTable())->whereIn('work_report_id', $lines->pluck('wr_id')->filter()->all())->pluck('work_report_id')->flip();

            return $lines->map(function ($l) use ($delivered) {
                $informed = $l->wr_informed ?: ($l->wr_created ?: $l->row_at);
                $isDone   = $l->wr_id && $delivered->has($l->wr_id);
                $due      = !$isDone && $l->ads_due ? Carbon::parse($l->ads_due) : null;
                $left     = $due ? (int) now()->startOfDay()->diffInDays($due->copy()->startOfDay(), false) : null;

                return $this->row($l, $informed, $left, $isDone, $left === null ? 'muted' : ($left < 0 ? 'red' : ($left <= 1 ? 'amber' : 'green')));
            })->all();
        }

        $notes = (clone $base)->select('notes.id as note_id', 'notes.note', 'notes.type_note', 'notes.rubrica', 'notes.lexp as city')->get();
        $ids   = $notes->pluck('note_id')->all();

        // Data do informe: o informe mais antigo ainda válido; sem informe (Pagamento), a fiscalização da parcial.
        $informed = DB::table('work_reports')->whereIn('note_id', $ids)->where('rejected', false)->where('canceled', false)
            ->selectRaw('note_id, MIN(COALESCE(informed_at, created_at)) as at')->groupBy('note_id')->pluck('at', 'note_id');
        $partial = $kind === 'payment'
            ? DB::table('partials')->whereIn('note_id', $ids)->where('allow', 1)->where('deny', 0)->where('supervision', 1)->where('payment', 0)
                ->selectRaw('note_id, MIN(supervision_at) as at')->groupBy('note_id')->pluck('at', 'note_id')
            : collect();

        $measure     = app(MeasurementDeadlinePolicy::class);
        $publication = app(PublicationDeadlinePolicy::class);

        return $notes->map(function ($n) use ($informed, $partial, $kind, $measure, $publication) {
            $at = $informed[$n->note_id] ?? ($partial[$n->note_id] ?? null);
            $at = $at ? Carbon::parse($at) : null;

            if (!$at) {
                return $this->row($n, null, null, false, 'muted');
            }

            if ($kind === 'publication') {
                $left = $publication->remainingDays($publication->dueAt($at));

                return $this->row($n, $at, $left, false, $left < 0 ? 'red' : ($left === 0 ? 'amber' : 'green'));
            }

            $left = $measure->remainingDays($measure->dueAt($at));

            return $this->row($n, $at, $left, false, ['vencido' => 'red', 'vencendo' => 'amber', 'no_prazo' => 'green'][$measure->statusForRemainingDays($left)]);
        })->all();
    }

    /** @return array<string, mixed> */
    private function row(object $n, $informedAt, ?int $left, bool $delivered, string $tone): array
    {
        return [
            'note_id'     => (int) $n->note_id,
            'note'        => (string) $n->note,
            'type_note'   => (int) $n->type_note,
            'rubrica'     => $n->rubrica,
            'city'        => $n->city,
            'informed_at' => $informedAt ? Carbon::parse($informedAt) : null,
            'remaining'   => $left,
            'delivered'   => $delivered,
            'tone'        => $tone,
        ];
    }

    private function remainingLabel(?int $left, bool $delivered): string
    {
        return match (true) {
            $delivered     => 'ADS entregue',
            $left === null => '—',
            $left < 0      => 'vencido há ' . abs($left) . (abs($left) === 1 ? ' dia' : ' dias'),
            $left === 0    => 'vence hoje',
            default        => 'vence em ' . $left . ($left === 1 ? ' dia' : ' dias'),
        };
    }

    /** @param array<string, array<string, int>> $counts rótulo => série => total */
    private function dueSeries(array $counts): array
    {
        $order  = array_merge(['Vencido'], array_map('strval', range(0, 30)), ['30+', 'Sem prazo']);
        $labels = array_values(array_filter($order, fn ($l) => isset($counts[$l])));
        $series = [];

        foreach (self::DEADLINE_SERIES as $name) {
            $series[$name] = array_map(fn ($l) => $counts[$l][$name] ?? 0, $labels);
        }

        return ['labels' => $labels, 'series' => $series];
    }
}
