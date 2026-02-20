<?php

namespace App\Http\Livewire\Engineers\Ads;

use App\Models\Adsform;
use App\Models\Company;
use App\Models\OldAdsInform;
use App\Models\ReturnWork;
use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $companies;
    public $company_id;
    public $dt_ini;
    public $dt_fim;
    public $month;

    // Id dos Gráficos
    public $returnInformChart1;
    public $dailyReceivedChartId;
    public $dailyADSChartId;
    public $totalAdsOriginChartId;


    public $dataReturnInform = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $dailyReceivedInform = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $dailyADSInform = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $totalAdsOriginData = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];


    public function mount()
    {
        // Definição do Valor do Id do Gráfico
        $this->returnInformChart1 = 'dataReturnInformChart-' . Str::random(8);
        $this->dailyReceivedChartId = 'dailyReceived-' . Str::random(8);
        $this->dailyADSChartId = 'dailyADS-' . Str::random(8);
        $this->totalAdsOriginChartId = 'totalAdsOrigin-' . Str::random(8);




        $this->companies = Company::whereNull('deleted_at')
            ->whereRelation('contracts', function ($q) {
                $q->where('construction', true)
                  ->where('service', false);
            })
            ->orderBy('name')
            ->get();

        $this->month = Carbon::now()->format('Y-m');
        $this->dt_ini = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
        $this->dt_fim = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');

        // Inicializa os Gráficos para exibição
        $this->toUpdateGraph();
    }

    public function updatedCompanyId()
    {
        $this->toUpdateGraph();
    }

    public function updatedMonth()
    {
        $this->dt_ini = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
        $this->dt_fim = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');
        $this->toUpdateGraph();

    }

    public function updatedDtIni()
    {
        $this->toUpdateGraph();
    }

    public function updatedDtFim()
    {
        $this->toUpdateGraph();
    }

    public function getBaseProperty()
    {
        return WorkReport::when($this->company_id, function ($q) {
            $q->where('company_id', $this->company_id);
        })->when($this->dt_ini, function ($q) {
            $q->where('date', '>=', $this->dt_ini);
        })->when($this->dt_fim, function ($q) {
            $q->where('date', '<=', $this->dt_fim);
        });
    }

    public function getWorkReportsRelation()
    {
        return $this->getBaseProperty()
            ->selectRaw("work_reports.*, COALESCE(
                (SELECT DATEDIFF(COALESCE(adsforms.tacit_delivered_at, adsforms.created_at), work_reports.informed_at)
                    FROM adsforms
                    WHERE adsforms.work_report_id = work_reports.id
                    AND COALESCE(adsforms.tacit_delivered_at, adsforms.created_at) > work_reports.informed_at
                    AND (adsforms.tacit = 0 OR adsforms.tacit_delivered_at IS NOT NULL)
                    LIMIT 1),
                (SELECT DATEDIFF(old_ads_informs.date, work_reports.informed_at)
                    FROM old_ads_informs
                    WHERE old_ads_informs.note_id = work_reports.note_id
                    AND old_ads_informs.date > work_reports.informed_at
                    LIMIT 1)
            ) as diff_days")
            ->where('rejected', false)
            ->where(function ($query) {
                $query->whereHas('Adsform', function ($q) {
                    $q->whereRaw('DATEDIFF(COALESCE(adsforms.tacit_delivered_at, adsforms.created_at), work_reports.informed_at) > ?', [6])
                        ->where(function ($sub) {
                            $sub->where('adsforms.tacit', false)
                                ->orWhereNotNull('adsforms.tacit_delivered_at');
                        });
                })
                ->orWhereHas('Note.OldAds', function ($q) {
                    $q->whereRaw('DATEDIFF(old_ads_informs.date, work_reports.informed_at) > ?', [6]);
                });
            })
            ->paginate(15, ['*'], 'workReportsPage');
    }

    public function getWorkReportsWithoutAdsRelation()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        return $this->getBaseProperty()
            ->where('rejected', false)
            ->whereDoesntHave('adsform')
            ->whereDoesntHave('note.oldAds')
            ->where(function ($query) use ($sevenDaysAgo) {
                $query->where('informed_at', '<=', $sevenDaysAgo);
            })
            ->paginate(15, ['*'], 'workReportsWithoutAdsPage');
    }

    public function getTacitOpenOverdueRelation()
    {
        return $this->getBaseProperty()
            ->where('rejected', false)
            ->whereHas('Adsform', function ($q) {
                $q->where('tacit', true)
                    ->whereNull('tacit_delivered_at')
                    ->whereNotNull('tacit_due_at')
                    ->where('tacit_due_at', '<', now());
            })
            ->with(['Note:id,note', 'Company:id,name', 'Adsform:id,work_report_id,tacit,tacit_due_at,tacit_delivered_at'])
            ->orderBy('informed_at', 'asc')
            ->paginate(10, ['*'], 'tacitOpenOverduePage');
    }

    public function getTacitDeliveredLateRelation()
    {
        return $this->getBaseProperty()
            ->where('rejected', false)
            ->whereHas('Adsform', function ($q) {
                $q->where('tacit', true)
                    ->whereNotNull('tacit_delivered_at')
                    ->whereNotNull('tacit_due_at')
                    ->whereRaw('tacit_delivered_at > tacit_due_at');
            })
            ->with(['Note:id,note', 'Company:id,name', 'Adsform:id,work_report_id,tacit,tacit_due_at,tacit_delivered_at'])
            ->orderBy('informed_at', 'asc')
            ->paginate(10, ['*'], 'tacitDeliveredLatePage');
    }

    public function getReturnbaseProperty()
    {
        return ReturnWork::when($this->company_id, function ($q) {
            $q->whereHas('workreport', function ($q) {
                $q->where('company_id', $this->company_id);
            });
        })->when($this->dt_ini, function ($q) {
            $q->where('created_at', '>=', $this->dt_ini);
        })->when($this->dt_fim, function ($q) {
            $q->where('created_at', '<=', $this->dt_fim);
        });
    }
    // Retorno de Informes
    public function getRejectionReason()
    {
        // ChartId $this->returnInformChart1


        $data = $this->getReturnbaseProperty()
            ->select('category', DB::Raw('COUNT(*) as total'))
            ->orderBy('category')
            ->groupBy('category')
            ->get();

        $this->dataReturnInform = [
            'labels' => $data->pluck('category')->toArray(),
            'data' => $data->pluck('total')->toArray(),
        ];

        $this->updateData($this->returnInformChart1, $this->dataReturnInform['labels'], $this->dataReturnInform['data']);
    }

    public function getDailyReceivedForms()
    {


        $data = $this->getBaseProperty()
        ->where('rejected', false)
        ->when($this->company_id, function ($q) {
            $q->where('company_id', $this->company_id);
        })
        ->selectRaw('
            DATE(informed_at) as raw_date,
            DATE_FORMAT(MIN(informed_at), "%d/%m/%y") as informed_at_formatted,
            COUNT(*) as total
        ')
        // ->select( // Alternativa usando DB::raw individualmente
        //     DB::raw('DATE(informed_at) as raw_date'),
        //     DB::raw('DATE_FORMAT(MIN(informed_at), "%d/%m/%Y") as informed_at_formatted'),
        //     DB::raw('COUNT(*) as total')
        // )
        ->groupBy('raw_date') // Agrupa apenas pela data
        ->orderBy('raw_date', 'asc')
        ->get();

        // Prepara os dados para o gráfico
        $this->dailyReceivedInform = [
            'labels' => $data->pluck('informed_at_formatted')->toArray(), // Use o alias correto
            'data'   => $data->pluck('total')->toArray(),
        ];

        // Atualiza os dados do gráfico
        $this->updateData(
            $this->dailyReceivedChartId,
            $this->dailyReceivedInform['labels'],
            $this->dailyReceivedInform['data']
        );
    }

    public function getDailyADSForms()
    {
        // Busca os dados novos
        $data1 = Adsform::whereHas('workReport')
            ->when($this->dt_ini, fn ($q) => $q->where('created_at', '>=', $this->dt_ini))
            ->when($this->dt_fim, fn ($q) => $q->where('created_at', '<=', $this->dt_fim))
            ->when($this->company_id, function ($q) {
                $q->whereHas('workReport', fn ($query) => $query->where('company_id', $this->company_id));
            })
            ->selectRaw('
                DATE(created_at) as raw_date,
                DATE_FORMAT(MIN(created_at), "%d/%m/%y") as informed_at_formatted,
                COUNT(*) as total
            ')
            ->groupBy('raw_date')
            ->orderBy('raw_date', 'asc')
            ->get()
            ->map(fn ($item) => [
                'raw_date' => $item->raw_date,
                'informed_at_formatted' => $item->informed_at_formatted,
                'total' => $item->total,
            ]);

        // Busca os dados antigos
        $data2 = OldAdsInform::whereHas('note', fn ($q) => $q->whereHas('WorkForm'))
            ->when($this->dt_ini, fn ($q) => $q->where('date', '>=', $this->dt_ini))
            ->when($this->dt_fim, fn ($q) => $q->where('date', '<=', $this->dt_fim))
            ->when($this->company_id, function ($q) {
                $q->whereHas(
                    'note',
                    fn ($query) =>
                    $query->whereHas(
                        'WorkForm',
                        fn ($wq) =>
                        $wq->where('company_id', $this->company_id)
                    )
                );
            })
            ->selectRaw('
                DATE(date) as raw_date,
                DATE_FORMAT(MIN(date), "%d/%m/%y") as informed_at_formatted,
                COUNT(*) as total
            ')
            ->groupBy('raw_date')
            ->orderBy('raw_date', 'asc')
            ->get()
            ->map(fn ($item) => [
                'raw_date' => $item->raw_date,
                'informed_at_formatted' => $item->informed_at_formatted,
                'total' => $item->total,
            ]);

        // Verifica se ambas estão vazias
        if ($data1->isEmpty() && $data2->isEmpty()) {
            $this->dailyADSInform = [
                'labels' => [],
                'data' => [],
            ];

        }

        // Une as duas coleções e agrupa por data
        if ($data1->isNotEmpty() && $data2->isEmpty()) {
            $data = $data1;
        } elseif ($data1->isEmpty() && $data2->isNotEmpty()) {
            $data = $data2;
        } else {
            $data = $data1->merge($data2)
            ->groupBy('raw_date')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'raw_date' => $first['raw_date'] ?? null,
                    'informed_at_formatted' => $first['informed_at_formatted'] ?? null,
                    'total' => collect($group)->sum('total'),
                ];
            })
            ->sortBy('raw_date')
            ->values();
        }

        // Prepara os dados para o gráfico
        $this->dailyADSInform = [
            'labels' => $data->map(fn ($item) => $item['informed_at_formatted'])->toArray(),
            'data'   => $data->map(fn ($item) => $item['total'])->toArray(),
        ];

        // Atualiza o gráfico
        $this->updateData(
            $this->dailyADSChartId,
            $this->dailyADSInform['labels'],
            $this->dailyADSInform['data']
        );
    }



    public function getTotalInformAdsOrigin()
    {
        // Count new ADS forms
        $totalAdsforms = Adsform::whereHas('workReport')
            ->when($this->dt_ini, fn ($q) => $q->where('created_at', '>=', $this->dt_ini))
            ->when($this->dt_fim, fn ($q) => $q->where('created_at', '<=', $this->dt_fim))
            ->when($this->company_id, function ($q) {
                $q->whereHas('workReport', fn ($query) => $query->where('company_id', $this->company_id));
            })
            ->count();

        // Count old ADS forms
        $totalOldAdsInforms = OldAdsInform::whereHas('note', fn ($q) => $q->whereHas('WorkForm'))
            ->when($this->dt_ini, fn ($q) => $q->where('date', '>=', $this->dt_ini))
            ->when($this->dt_fim, fn ($q) => $q->where('date', '<=', $this->dt_fim))
            ->when($this->company_id, function ($q) {
                $q->whereHas(
                    'note',
                    fn ($query) =>
                    $query->whereHas(
                        'WorkForm',
                        fn ($wq) =>
                $wq->where('company_id', $this->company_id)
                    )
                );
            })
            ->count();

        $this->totalAdsOriginData = [
            'labels' => ['BASE ANTIGA', 'BASE NOVA'], // Use o alias correto
            'data'   => [
                $totalOldAdsInforms,
                $totalAdsforms,
            ],
        ];

        // Atualiza os dados do gráfico
        $this->updateData(
            $this->totalAdsOriginChartId,
            $this->totalAdsOriginData['labels'],
            $this->totalAdsOriginData['data']
        );
    }



    private function updateData(string $chartId = null, array $labels = [], array $data = [])
    {
        $this->dispatchBrowserEvent('updateGraph' . Str::studly($chartId), [
            'labels' => $labels,
            'data' => $data,
        ]);
    }


    public function toUpdateGraph()
    {
        $this->getRejectionReason();
        $this->getDailyReceivedForms();
        $this->getDailyADSForms();
        $this->getTotalInformAdsOrigin();
    }

    public function render()
    {
        return view('livewire.engineers.ads.dashboard', [
            'workReportsVencidos' => $this->getWorkReportsWithoutAdsRelation(),
            'workReportsAdsVencidos' => $this->getWorkReportsRelation(),
            'tacitOpenOverdue' => $this->getTacitOpenOverdueRelation(),
            'tacitDeliveredLate' => $this->getTacitDeliveredLateRelation(),
        ]);
    }
}
