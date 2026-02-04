<?php

namespace App\Http\Livewire\Dispatchs;

use App\Custom\RuleBuilder;
use App\Models\Note;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class Dashboard extends Component
{
    public $service;
    public $month;
    public $dt_ini;
    public $dt_fim;
    public $productionStats;
    public $ticketGeral;

    public $dadosGrafico = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $dadosGrafico1 = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $dadosGrafico2 = [
        'labels' => ['A', 'B', 'C'],
        'data1' => [10, 20, 70],
        'data2' => [10, 20, 70],
    ];

    public $dadosGrafico3 = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $dadosGrafico4 = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $dadosGrafico5 = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $dadosGrafico6 = [
        'labels' => ['A', 'B', 'C'],
        'data1' => [10, 20, 70],
        'data2' => [10, 20, 70],
    ];

    public $mixedChartData = [
        'chartId' => 'meuGraficoMisto',
        'labels' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
        'data' => [
            [
                'type' => 'line',
                'name' => 'Linha de Vendas',
                'data' => [30, 40, 35, 50, 49, 60],
            ],
            [
                'type' => 'bar',
                'name' => 'Barra de Lucro',
                'data' => [20, 30, 25, 40, 39, 50],
            ],
        ],
        'title' => 'Vendas vs Lucro',
        'height' => '350px',
    ];


    public string $chartId;
    public string $chartId1;
    public string $chartId2;
    public string $chartId3;
    public string $chartId4;
    public string $chartId5;
    public string $chartId6;

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->first();
        $this->chartId = 'chart-'  . Str::random(8);
        $this->chartId1 = 'chart-'  . Str::random(8);
        $this->chartId2 = 'chart-'  . Str::random(8);
        $this->chartId3 = 'chart-'  . Str::random(8);
        $this->chartId4 = 'chart-'  . Str::random(8);
        $this->chartId5 = 'chart-'  . Str::random(8);
        $this->chartId6 = 'chart-'  . Str::random(8);

        // Data inicial e final do mês
        $this->month = Carbon::today()->format('Y-m');
        $this->dt_ini = Carbon::today()->startOfMonth()->format('Y-m-d');
        $this->dt_fim = Carbon::today()->endOfMonth()->format('Y-m-d');

        $this->atualizarTicketMedioServico();
        $this->atualizarTicketMedioPorUsuario();
        $this->atualizarD5Proporcao();
        $this->atualizarProducaoDiaria();
        $this->atualizarDados();
        $this->atualizarDados1();
        $this->atualizarDados2();
        $this->atualizarProducaoAtivosDiario();


        $this->atualizarStackOv();
    }

    public function updatedMonth()
    {
        $this->dt_ini = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
        $this->dt_fim = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');

        $this->atualizarTicketMedioServico();
        $this->atualizarTicketMedioPorUsuario();
        $this->atualizarD5Proporcao();
        $this->atualizarProducaoDiaria();
        $this->atualizarDados1();
        $this->atualizarDados2();
        $this->atualizarProducaoAtivosDiario();

    }

    public function updatedDtIni()
    {
        $this->atualizarTicketMedioServico();
        $this->atualizarTicketMedioPorUsuario();
        $this->atualizarD5Proporcao();
        $this->atualizarProducaoDiaria();
        $this->atualizarDados1();
        $this->atualizarDados2();
        $this->atualizarProducaoAtivosDiario();
    }

    public function updatedDtFim()
    {
        $this->atualizarTicketMedioServico();
        $this->atualizarTicketMedioPorUsuario();
        $this->atualizarD5Proporcao();
        $this->atualizarProducaoDiaria();
        $this->atualizarDados1();
        $this->atualizarDados2();
        $this->atualizarProducaoAtivosDiario();
    }

    public function atualizarTicketMedioServico()
    {
        $this->ticketGeral = $this->getTicketMedioGeralProductionProperty();
    }


    public function atualizarTicketMedioPorUsuario()
    {
        $this->productionStats = $this->getProductionStatsByUserProperty();
    }


    public function atualizarD5Proporcao()
    {
        $datas = $this->getPercentualD5Property();

        $this->dadosGrafico = [
            'labels' => $datas->pluck('d5_status')->toArray(),
            'data' => $datas->pluck('total')->toArray(),
        ];

        $this->updateData($this->chartId, $this->dadosGrafico['labels'], $this->dadosGrafico['data']);
    }

    public function atualizarProducaoDiaria()
    {
        $datas = $this->getProductionDaylyProperty();

        $this->dadosGrafico1 = [
            'labels' => $datas->pluck('date')->toArray(),
            'data' => $datas->pluck('total')->toArray(),
        ];

        $this->updateData($this->chartId1, $this->dadosGrafico1['labels'], $this->dadosGrafico1['data']);
    }


    public function atualizarProducaoAtivosDiario()
    {
        $datas = $this->getProductionAssetsDaylyProperty();

        $this->dadosGrafico6 = [
            'labels' => $datas->pluck('date')->toArray(),
            'data' => $datas->pluck('total_postes')->toArray(),
        ];

        $this->updateData($this->chartId6, $this->dadosGrafico6['labels'], $this->dadosGrafico6['data']);
    }

    private function updateData(string $chartId = null, array $labels = [], array $data = [])
    {
        $this->dispatchBrowserEvent('updateGraph' . Str::studly($chartId), [
            'labels' => $labels,
            'data' => $data,
        ]);
    }


    public function atualizarStackOv()
    {
        $days = $this->getStackOvproperty();


        // dd($days);

        $novosDados = [
            'labels' => (function () {
                $labels = [];
                for ($i = 0; $i < 30; $i++) {
                    $labels[] = (string)$i;
                }
                $labels[] = '30+';
                return $labels;
            })(),
            'data2' => $days['withoutProduction'],
            'data1' => $days['withProduction'],
        ];

        // dd($novosDados);

        $this->dadosGrafico2 = $novosDados;

        // $this->updateData($this->chartId, $novosDados['labels'], $novosDados['data']);

        $this->emit('updateGraph1' . Str::studly($this->chartId2), [
            'labels' => $novosDados['labels'],
            'dataset1Data' => $novosDados['data1'],
            'dataset2Data' => $novosDados['data2']
        ]);
    }









    public function getTicketMedioGeralProductionProperty()
    {
        $query = Production::query()
            ->where('service_id', $this->service->uuid)
            ->when(auth()->user()->contract == true, function ($q) {
                return $q->where('company_id', auth()->user()->company_id);
            })
            ->when($this->dt_ini, function ($q) {
                return $q->where('dispatch_at', '>=', $this->dt_ini);
            })
            ->when($this->dt_fim, function ($q) {
                return $q->where('completed_at', '<=', $this->dt_fim);
            });

        return $query->selectRaw('
        AVG(TIMESTAMPDIFF(MINUTE, dispatch_at, att_at)) as avg_reaction_time,
        AVG(TIMESTAMPDIFF(MINUTE, att_at, completed_at)) as avg_execution_time
    ')->first();

    }

    public function getProductionStatsByUserProperty()
    {
        $query = Production::where('service_id', $this->service->uuid)
            ->when(auth()->user()->contract == true, function ($q) {
                return $q->where('company_id', auth()->user()->company_id);
            })
            ->join('users', 'productions.user_id', '=', 'users.id')
            ->when($this->dt_ini, function ($q) {
                return $q->where('att_at', '>=', $this->dt_ini);
            })
            ->when($this->dt_fim, function ($q) {
                return $q->where('completed_at', '<=', $this->dt_fim);
            });

        return $query->selectRaw('
            productions.user_id,
            users.name,
            COUNT(*) as total,
            AVG(CASE WHEN d5 = 1 THEN TIMESTAMPDIFF(MINUTE, att_at, completed_at) END) as avg_resolution_d5,
            AVG(CASE WHEN d5 = 0 THEN TIMESTAMPDIFF(MINUTE, att_at, completed_at) END) as avg_resolution_no_d5
        ')
            ->with('user.company')
            ->groupBy('productions.user_id', 'users.name')
            ->orderBy('total', 'desc')
            ->orderBy('avg_resolution_no_d5')
            ->orderBy('avg_resolution_d5')
            ->get();
    }

    public function getPercentualD5Property()
    {

        return Production::where('service_id', $this->service->uuid)
        ->when(auth()->user()->contract == true, function ($q) {
            return $q->where('company_id', auth()->user()->company_id);
        })
        ->when($this->dt_ini, function ($q) {
            return $q->where('dispatch_at', '>=', $this->dt_ini);
        })
        ->when($this->dt_fim, function ($q) {
            return $q->where('dispatch_at', '<=', $this->dt_fim);
        })
        ->selectRaw("
            CASE WHEN d5 = 1 THEN 'RI'
                ELSE 'Normal'
            END as d5_status,
            COUNT(*) as total
        ")
        ->groupBy('d5_status')
        ->get();

    }

    public function atualizarDados()
    {
        $reclaims = $this->getReclaimsProperty();

        $novosDados = [
            'labels' => $reclaims->pluck('category')->toArray(),
            'data' => $reclaims->pluck('total')->toArray(),
        ];

        $this->dadosGrafico3 = $novosDados;

        $this->updateData($this->chartId3, $novosDados['labels'], $novosDados['data']);
    }


    public function atualizarDados1()
    {
        $reclaims = $this->getReclaimsViabilityProperty();

        $novosDados = [
            'labels' => $reclaims->pluck('category')->toArray(),
            'data' => $reclaims->pluck('total')->toArray(),
        ];

        $this->dadosGrafico4 = $novosDados;

        $this->updateData($this->chartId4, $novosDados['labels'], $novosDados['data']);
    }

    public function atualizarDados2()
    {
        $reclaims = $this->getReclaimsAnaliseProperty();

        $novosDados = [
            'labels' => $reclaims->pluck('category')->toArray(),
            'data' => $reclaims->pluck('total')->toArray(),
        ];

        $this->dadosGrafico5 = $novosDados;

        $this->updateData($this->chartId5, $novosDados['labels'], $novosDados['data']);
    }

    public function getReclaimsProperty()
    {
        return Reclaim::whereDoesntHave('Viabilities')
            ->whereDoesntHave('Approvals')
            ->where('service_id', $this->service->uuid)
            ->when($this->dt_ini, function ($query) {
                return $query->where('created_at', '>=', $this->dt_ini);
            })
            ->when($this->dt_fim, function ($query) {
                return $query->where('created_at', '<=', $this->dt_fim);
            })
            ->select(DB::raw("COALESCE(category, 'SEM CATEGORIA') as category"), DB::raw('count(*) as total'))
            ->groupBy(DB::raw("COALESCE(category, 'SEM CATEGORIA')"))
            ->get();
    }

    public function getReclaimsViabilityProperty()
    {
        return Reclaim::whereHas('Viabilities')
        ->where('service_id', $this->service->uuid)
        ->when($this->dt_ini, function ($query) {
            return $query->where('created_at', '>=', $this->dt_ini);
        })
        ->when($this->dt_fim, function ($query) {
            return $query->where('created_at', '<=', $this->dt_fim);
        })
            ->select(DB::raw("COALESCE(category, 'SEM CATEGORIA') as category"), DB::raw('count(*) as total'))
            ->groupBy(DB::raw("COALESCE(category, 'SEM CATEGORIA')"))
            ->get();
    }

    public function getReclaimsAnaliseProperty()
    {
        return Reclaim::whereHas('Approvals')
        ->where('service_id', $this->service->uuid)
        ->when($this->dt_ini, function ($query) {
            return $query->where('created_at', '>=', $this->dt_ini);
        })
        ->when($this->dt_fim, function ($query) {
            return $query->where('created_at', '<=', $this->dt_fim);
        })
            ->select(DB::raw("COALESCE(category, 'SEM CATEGORIA') as category"), DB::raw('count(*) as total'))
            ->groupBy(DB::raw("COALESCE(category, 'SEM CATEGORIA')"))
            ->get();
    }

    public function getProductionDaylyProperty()
    {
        return Production::where('service_id', $this->service->uuid)
            ->when(auth()->user()->contract, function ($q) {
                return $q->where('company_id', auth()->user()->company_id);
            })
            ->when($this->dt_ini, function ($q) {
                return $q->where('completed_at', '>=', $this->dt_ini);
            })
            ->when($this->dt_fim, function ($q) {
                return $q->where('completed_at', '<=', $this->dt_fim);
            })
            ->selectRaw('
            DATE_FORMAT(completed_at, "%d/%m/%Y") as date,
            COUNT(*) as total
            ')
            ->groupBy('date')
            ->orderByRaw('YEAR(completed_at) ASC, MONTH(completed_at) ASC, DAY(completed_at) ASC')
            ->get();
    }


    public function getProductionAssetsDaylyProperty()
    {
        return Production::where('service_id', $this->service->uuid)
            ->when(auth()->user()->contract, function ($q) {
                return $q->where('company_id', auth()->user()->company_id);
            })
            ->when($this->dt_ini, function ($q) {
                return $q->where('completed_at', '>=', $this->dt_ini);
            })
            ->when($this->dt_fim, function ($q) {
                return $q->where('completed_at', '<=', $this->dt_fim);
            })
            ->selectRaw('
                DATE_FORMAT(completed_at, "%d/%m/%Y") as date,
                SUM(postes_u) as total_postes
            ')
            ->groupBy('date')
            ->orderByRaw('YEAR(completed_at) ASC, MONTH(completed_at) ASC, DAY(completed_at) ASC')
            ->get();
    }


    public function getStackOvproperty()
    {
        $query = Note::query()->excludeCanceledFullDone();
        RuleBuilder::applyRules($query, $this->service->Status);
        $query->where('type_note', 2);

        $today = Carbon::today();

        // Conjunto para notas que possuem Production com service_id igual a $this->service->uuid
        $withProd = (clone $query)
            ->whereHas('Productions', function ($q) {
                $q->where('service_id', $this->service->uuid);
            })
            ->selectRaw('
                CASE
                    WHEN DATEDIFF(?, dt_status) < 30 THEN CAST(DATEDIFF(?, dt_status) AS CHAR)
                    ELSE "30+"
                END as days_difference,
                COUNT(*) as total
            ', [$today, $today])
            ->groupBy('days_difference')
            ->get()
            ->pluck('total', 'days_difference')
            ->toArray();

        // Conjunto para notas que NÃO possuem Production com service_id igual a $this->service->uuid
        $withoutProd = (clone $query)
            ->whereDoesntHave('Productions', function ($q) {
                $q->where('service_id', $this->service->uuid);
            })
            ->selectRaw('
                CASE
                    WHEN DATEDIFF(?, dt_status) < 30 THEN CAST(DATEDIFF(?, dt_status) AS CHAR)
                    ELSE "30+"
                END as days_difference,
                COUNT(*) as total
            ', [$today, $today])
            ->groupBy('days_difference')
            ->get()
            ->pluck('total', 'days_difference')
            ->toArray();

        // Gera as labels de 0 a 29 + "30+"
        $labels = range(0, 29);
        $labels[] = '30+';

        // Monta arrays indexados de dados para "withProduction" e "withoutProduction"
        $withProductionData = [];
        $withoutProductionData = [];
        foreach ($labels as $label) {
            // Converte o label para string, pois as chaves de $withProd/$withoutProd são strings
            $labelKey = (string)$label;

            $withProductionData[] = $withProd[$labelKey] ?? 0;
            $withoutProductionData[] = $withoutProd[$labelKey] ?? 0;
        }

        // Retorna um array com índices numéricos para uso em gráficos
        return [
            'labels' => $labels,               // ex.: ["0","1","2",..., "29","30+"]
            'withProduction' => $withProductionData,   // ex.: [10, 5, 0, 2, ...]
            'withoutProduction' => $withoutProductionData
        ];
    }






    public function render()
    {
        return view('livewire.dispatchs.dashboard');
    }
}
