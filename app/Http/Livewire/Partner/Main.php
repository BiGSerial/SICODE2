<?php

namespace App\Http\Livewire\Partner;

use App\Models\FiveNote;
use App\Models\Reclaim;
use App\Models\ReturnWork;
use App\Models\Viability;
use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class Main extends Component
{
    private const ADS_TACIT_DAYS = 6;

    public $pizza1;
    public $pizza2;
    public $backlogChart;
    public $dailyViabilityChart;

    public $month;
    public $dt_ini;
    public $dt_fim;

    public int $daysAhead = 3;
    public array $kpis = [];

    public $dadospizza1 = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $dadospizza2 = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $dadosBacklog = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $dadosDailyViability = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public function mount()
    {
        $this->pizza1 = 'chart-' . Str::random(8);
        $this->pizza2 = 'chart-' . Str::random(8);
        $this->backlogChart = 'chart-' . Str::random(8);
        $this->dailyViabilityChart = 'chart-' . Str::random(8);

        $this->month = now()->format('Y-m');
        $this->dt_ini = now()->startOfMonth()->format('Y-m-d');
        $this->dt_fim = now()->endOfMonth()->format('Y-m-d');

        $this->toUpdateGraphs();
    }

    public function toUpdateGraphs()
    {
        $this->atualizarViabilityCounts();
        $this->atualizaReturnWorkReports();
        $this->kpis = $this->getDashboardKpis();
        $this->atualizaBacklogChart();
        $this->atualizaDailyViability();
    }

    public function updatedMonth()
    {
        $this->dt_ini = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
        $this->dt_fim = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');

        $this->toUpdateGraphs();
    }

    public function updatedDtIni()
    {
        $this->month = Carbon::parse($this->dt_ini)->format('Y-m');

        $this->toUpdateGraphs();
    }

    public function updatedDtFim()
    {
        $this->toUpdateGraphs();
    }

    protected function companyIds(): array
    {
        $user = auth()->user();

        if ($user->superadm) {
            return [];
        }

        $ids = [];
        if ($user->Companies->isNotEmpty()) {
            $ids = $user->Companies->pluck('id')->toArray();
        }

        if ($user->Company?->id) {
            $ids[] = $user->Company->id;
        }

        return array_values(array_unique($ids));
    }

    protected function scopeByCompany(Builder $query, string $column = 'company_id'): Builder
    {
        if (auth()->user()->superadm) {
            return $query;
        }

        $companyIds = $this->companyIds();

        if (!empty($companyIds)) {
            $query->whereIn($column, $companyIds);
        }

        return $query;
    }

    public function getViabilityDueDate(): Collection
    {
        $today = Carbon::today();
        $dueLimit = Carbon::today()->addDays($this->daysAhead);

        $query = Viability::query()
            ->where('approved', false)
            ->where('rejected', false)
            ->where('completed', false)
            ->where('canceled', false)
            ->whereNotNull('sended_at')
            ->with(['Note:id,note', 'Company:id,name']);

        $this->scopeByCompany($query);

        return $query->get()
            ->filter(function ($viability) use ($today, $dueLimit) {
                $dueDate = Carbon::parse($viability->sended_at)->addDays(7 + $viability->getDays());
                return $dueDate->betweenIncluded($today, $dueLimit);
            })
            ->sortBy(function ($viability) {
                return Carbon::parse($viability->sended_at)->addDays(7 + $viability->getDays())->timestamp;
            })
            ->values();
    }

    public function getWorkReportsWithoutAdsDueSoon(): Collection
    {
        $today = Carbon::today();
        $dueLimit = Carbon::today()->addDays($this->daysAhead);

        $from = $today->copy()->subDays(self::ADS_TACIT_DAYS)->startOfDay();
        $to = $dueLimit->copy()->subDays(self::ADS_TACIT_DAYS)->endOfDay();

        $query = WorkReport::query()->active()
            ->where('rejected', false)
            ->whereNotNull('informed_at')
            ->whereBetween('informed_at', [$from, $to])
            ->whereDoesntHave('Note.Adsform')
            ->whereDoesntHave('Note.OldAds')
            ->with(['Note:id,note', 'Company:id,name'])
            ->orderBy('informed_at');

        $this->scopeByCompany($query);

        return $query->get();
    }

    public function getTacitAdsOverdueWithoutDelivery(): Collection
    {
        $query = WorkReport::query()->active()
            ->whereHas('Adsform', function ($q) {
                $q->where('tacit', true)
                    ->whereNull('tacit_delivered_at')
                    ->whereNotNull('tacit_due_at')
                    ->where('tacit_due_at', '<', now());
            })
            ->join('adsforms', 'adsforms.work_report_id', '=', 'work_reports.id')
            ->with([
                'Note:id,note',
                'Adsform:id,work_report_id,tacit_due_at,tacit_delivered_at',
            ])
            ->orderBy('adsforms.tacit_due_at')
            ->select('work_reports.*');

        $this->scopeByCompany($query);

        return $query->get();
    }

    public function getDashboardKpis(): array
    {
        $baseViability = Viability::query()->where('canceled', false);
        $this->scopeByCompany($baseViability);

        $baseWorkReport = WorkReport::query()->active();
        $this->scopeByCompany($baseWorkReport);

        $rejectedViabilityBase = Viability::query()
            ->where('rejected', true)
            ->where('completed', false)
            ->where('status', 5);
        $this->scopeByCompany($rejectedViabilityBase);

        $d5PendingBase = FiveNote::query()
            ->where('visible_partner', true)
            ->where('is_completed', false)
            ->where('returned', false);
        $this->scopeByCompany($d5PendingBase);

        $d5ReturnedBase = FiveNote::query()
            ->where('visible_partner', true)
            ->where('is_completed', false)
            ->where('returned', true);
        $this->scopeByCompany($d5ReturnedBase);

        $reclaimsBase = Reclaim::query()
            ->where('completed', false)
            ->whereHas('Viabilities', function ($q) {
                $this->scopeByCompany($q);
            });

        $workReportsWithoutAdsOverdueBase = WorkReport::query()->active()
            ->where('rejected', false)
            ->whereNotNull('informed_at')
            ->where('informed_at', '<', $this->getAdsTacitOverdueThreshold())
            ->whereDoesntHave('Note.Adsform')
            ->whereDoesntHave('Note.OldAds');
        $this->scopeByCompany($workReportsWithoutAdsOverdueBase);

        return [
            'pending_viability' => (clone $baseViability)
                ->where('completed', false)
                ->where('status', 1)
                ->count(),
            'viability_due_soon' => $this->getViabilityDueDate()->count(),
            'work_without_ads_due_soon' => $this->getWorkReportsWithoutAdsDueSoon()->count(),
            'work_without_ads_overdue' => $workReportsWithoutAdsOverdueBase->count(),
            'd5_pending' => $d5PendingBase->count(),
            'd5_returned' => $d5ReturnedBase->count(),
            'viability_rejected_waiting' => $rejectedViabilityBase->count(),
            'reclaims_pending' => $reclaimsBase->distinct('reclaims.id')->count('reclaims.id'),
            'informs_rejected' => (clone $baseWorkReport)->where('rejected', true)->count(),
        ];
    }

    public function getViabilityCounts()
    {
        $baseQuery = Viability::query()->where('canceled', false);

        if (!empty($this->dt_ini)) {
            $baseQuery->whereDate('sended_at', '>=', $this->dt_ini);
        }

        if (!empty($this->dt_fim)) {
            $baseQuery->whereDate('sended_at', '<=', $this->dt_fim);
        }

        $this->scopeByCompany($baseQuery);

        return [
            'inViability' => (clone $baseQuery)->where('approved', false)
                ->where('rejected', false)
                ->where('completed', false)
                ->where('tacit', false)
                ->count(),

            'realized' => (clone $baseQuery)->where(function ($q) {
                $q->where('approved', true)
                    ->orWhere('rejected', true);
            })
                ->where('tacit', false)
                ->count(),

            'notRealized' => (clone $baseQuery)->where('tacit', true)
                ->count(),
        ];
    }

    public function atualizarViabilityCounts()
    {
        $counts = $this->getViabilityCounts();

        $labels = ['Em Viabilidade', 'Realizadas', 'Não Realizadas'];
        $data = [
            $counts['inViability'],
            $counts['realized'],
            $counts['notRealized'],
        ];

        $this->dadospizza1 = [
            'labels' => $labels,
            'data' => $data,
        ];

        $this->updateDataPizza($this->pizza1, $labels, $data);
    }

    public function getReturnWorkReports()
    {
        $baseQuery = ReturnWork::query();

        $companyIds = $this->companyIds();
        if (!empty($companyIds)) {
            $baseQuery->whereRelation('Workreport', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        }

        if ($this->dt_ini) {
            $baseQuery->whereDate('created_at', '>=', $this->dt_ini);
        }

        if ($this->dt_fim) {
            $baseQuery->whereDate('created_at', '<=', $this->dt_fim);
        }

        return $baseQuery->selectRaw('count(*) as total, category')
            ->groupBy('category')
            ->get();
    }

    public function atualizaReturnWorkReports()
    {
        $returnWorkReports = $this->getReturnWorkReports();

        if ($returnWorkReports->isEmpty()) {
            $this->dadospizza2 = [
                'labels' => [],
                'data' => [],
            ];

            $this->updateDataPizza($this->pizza2, [], []);
            return;
        }

        $labels = $returnWorkReports->pluck('category')->toArray();
        $data = $returnWorkReports->pluck('total')->toArray();

        $this->dadospizza2 = [
            'labels' => $labels,
            'data' => $data,
        ];

        $this->updateDataPizza($this->pizza2, $labels, $data);
    }

    public function atualizaBacklogChart(): void
    {
        $kpis = $this->kpis ?: $this->getDashboardKpis();

        $labels = [
            'Viabilidade pendente',
            'Viabilidade a vencer',
            'ADS tacita a vencer',
            'D5 pendente',
            'Viabilidade rejeitada',
            'Reclamações pendentes',
            'Informes rejeitados',
        ];

        $data = [
            (int) ($kpis['pending_viability'] ?? 0),
            (int) ($kpis['viability_due_soon'] ?? 0),
            (int) ($kpis['work_without_ads_due_soon'] ?? 0),
            (int) ($kpis['d5_pending'] ?? 0),
            (int) ($kpis['viability_rejected_waiting'] ?? 0),
            (int) ($kpis['reclaims_pending'] ?? 0),
            (int) ($kpis['informs_rejected'] ?? 0),
        ];

        $this->dadosBacklog = [
            'labels' => $labels,
            'data' => $data,
        ];

        $this->updateDataPizza($this->backlogChart, $labels, $data);
    }

    public function atualizaDailyViability(): void
    {
        $baseQuery = Viability::query()
            ->where('canceled', false)
            ->whereNotNull('sended_at');

        if (!empty($this->dt_ini)) {
            $baseQuery->whereDate('sended_at', '>=', $this->dt_ini);
        }

        if (!empty($this->dt_fim)) {
            $baseQuery->whereDate('sended_at', '<=', $this->dt_fim);
        }

        $this->scopeByCompany($baseQuery);

        $data = $baseQuery
            ->selectRaw('DATE(sended_at) as raw_date, DATE_FORMAT(MIN(sended_at), "%d/%m") as ref_date, COUNT(*) as total')
            ->groupBy('raw_date')
            ->orderBy('raw_date')
            ->get();

        $labels = $data->pluck('ref_date')->toArray();
        $series = $data->pluck('total')->toArray();

        $this->dadosDailyViability = [
            'labels' => $labels,
            'data' => $series,
        ];

        $this->updateDataPizza($this->dailyViabilityChart, $labels, $series);
    }

    public function exportSummaryCsv()
    {
        $kpis = $this->kpis ?: $this->getDashboardKpis();
        $fileName = now()->format('YmdHis') . '-partner-dashboard-resumo.csv';

        return response()->streamDownload(function () use ($kpis) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Periodo inicial', $this->dt_ini]);
            fputcsv($out, ['Periodo final', $this->dt_fim]);
            fputcsv($out, []);
            fputcsv($out, ['Indicador', 'Quantidade']);
            fputcsv($out, ['Viabilidade pendente', $kpis['pending_viability'] ?? 0]);
            fputcsv($out, ['Viabilidades a vencer', $kpis['viability_due_soon'] ?? 0]);
            fputcsv($out, ['ADS tacita a vencer', $kpis['work_without_ads_due_soon'] ?? 0]);
            fputcsv($out, ['ADS tacita vencida', $kpis['work_without_ads_overdue'] ?? 0]);
            fputcsv($out, ['D5 pendentes', $kpis['d5_pending'] ?? 0]);
            fputcsv($out, ['D5 devolvidos', $kpis['d5_returned'] ?? 0]);
            fputcsv($out, ['Viabilidades rejeitadas aguardando resposta', $kpis['viability_rejected_waiting'] ?? 0]);
            fputcsv($out, ['Reclamacoes pendentes', $kpis['reclaims_pending'] ?? 0]);
            fputcsv($out, ['Informes rejeitados', $kpis['informs_rejected'] ?? 0]);

            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPendenciesCsv()
    {
        $fileName = now()->format('YmdHis') . '-partner-dashboard-pendencias.csv';

        $viabilityDueSoon = $this->getViabilityDueDate();
        $workWithoutAdsDueSoon = $this->getWorkReportsWithoutAdsDueSoon();

        return response()->streamDownload(function () use ($viabilityDueSoon, $workWithoutAdsDueSoon) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Tipo', 'Nota', 'Empresa', 'Recebido em', 'Vence em', 'Dias restantes']);

            foreach ($viabilityDueSoon as $item) {
                $dueDate = $item->sended_at?->copy()->addDays(7 + $item->getDays());
                $daysLeft = $dueDate
                    ? now()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false)
                    : null;

                fputcsv($out, [
                    'Viabilidade',
                    $item->note->note ?? '',
                    $item->company->name ?? '',
                    optional($item->sended_at)->format('d/m/Y H:i'),
                    optional($dueDate)->format('d/m/Y H:i'),
                    $daysLeft,
                ]);
            }

            foreach ($workWithoutAdsDueSoon as $item) {
                $dueDate = $this->getAdsTacitDueAt($item->informed_at);
                $daysLeft = $dueDate
                    ? now()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false)
                    : null;

                fputcsv($out, [
                    'ADS tacita',
                    $item->note->note ?? '',
                    $item->company->name ?? '',
                    optional($item->informed_at)->format('d/m/Y H:i'),
                    optional($dueDate)->format('d/m/Y H:i'),
                    $daysLeft,
                ]);
            }

            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function updateDataPizza(string $chartId = null, array $labels = [], array $data = [])
    {
        $this->dispatchBrowserEvent('updateGraph' . Str::studly($chartId), [
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    private function getAdsTacitDueAt(?Carbon $informedAt): ?Carbon
    {
        return $informedAt?->copy()->addDays(self::ADS_TACIT_DAYS)->endOfDay();
    }

    private function getAdsTacitOverdueThreshold(): Carbon
    {
        // Vence no fim do 6o dia; a partir de 00:00 do dia seguinte ja esta vencido.
        return now()->subDays(self::ADS_TACIT_DAYS)->startOfDay();
    }

    public function render()
    {
        return view('livewire.partner.main', [
            'dueSoon' => $this->getViabilityDueDate(),
            'workReportsWithoutAdsDueSoon' => $this->getWorkReportsWithoutAdsDueSoon(),
            'tacitAdsOverdueWithoutDelivery' => $this->getTacitAdsOverdueWithoutDelivery(),
        ]);
    }
}
