<?php

namespace App\Http\Livewire\Engineers\Analises;

use App\Models\Note;
use App\Models\Production;
use App\Models\Reclaim;
use App\Models\User;
use App\Models\ViabilityApproval;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class AnaliseDashboard extends Component
{
    public $chartId1;
    public $chartId2;
    public $chartId3;
    public $chartId4;
    public $chartId5;
    public $chartId6;
    public $chartId7;

    public $usuariosStats;
    public $ticketMedio;
    public $reclaimsGeral;
    public $productionsStats;
    public $month;
    public $dt_ini;
    public $dt_fim;


    protected $cast = [
        'month' => 'month',
        'dt_ini' => 'date',
        'dt_fim' => 'date',
    ];


    public $dadosGrafico = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $dadosGrafico1 = [
        'labels' => ['A', 'B', 'C'],
        'data1' => [10, 20, 70],
        'data2' => [10, 20, 70],
    ];

    public $dadosGrafico2 = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],

    ];

    public $dadosGrafico3 = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $pizzaReturnInternData = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];


    public $multStackData = [
        'labels' => ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5'],
        'datasets' => [
            [
                'name' => 'Atribuidos',
                'data' => [10, 20, 15, 25, 30],
            ],
            [
                'name' => 'Sem Atribuição',
                'data' => [5, 10, 7, 12, 15],
            ],
            [
                'name' => 'Resolvidos',
                'data' => [2, 5, 3, 6, 8],
            ],
        ],
    ];


    public $dadosGrafico4 = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public function mount()
    {
        $this->chartId1 = 'chart1-' . Str::random(8);
        $this->chartId2 = 'chart2-' . Str::random(8);
        $this->chartId3 = 'chart3-' . Str::random(8);
        $this->chartId4 = 'chart4-' . Str::random(8);
        $this->chartId5 = 'chart5-' . Str::random(8);
        $this->chartId6 = 'chart6-' . Str::random(8);
        $this->chartId7 = 'chart7-' . Str::random(8);


        // Data inicial e final do mês
        $this->month = Carbon::today()->format('Y-m');
        $this->dt_ini = Carbon::today()->startOfMonth()->format('Y-m-d');
        $this->dt_fim = Carbon::today()->endOfMonth()->format('Y-m-d');

        $this->atualizarDados();
        $this->atualizarDays();
        $this->atualizarTicketMedio();
        $this->atualizarTicketMedioReclaim();
        $this->atualizarTicketMedioResolution();
        $this->atualizarAprovedCategory();
        $this->atualizarReclaimType();
        $this->atualizarDaysReclaimType();
        $this->atualizarDados2();

    }

    public function updatedMonth()
    {
        $this->dt_ini = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
        $this->dt_fim = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');

        $this->atualizarDados();
        $this->atualizarTicketMedio();
        $this->atualizarTicketMedioReclaim();
        $this->atualizarTicketMedioResolution();
        $this->atualizarAprovedCategory();
        $this->atualizarReclaimType();
        $this->atualizarDados2();
    }

    public function updatedDtIni()
    {
        $this->atualizarDados();
        $this->atualizarTicketMedio();
        $this->atualizarTicketMedioReclaim();
        $this->atualizarTicketMedioResolution();
        $this->atualizarAprovedCategory();
        $this->atualizarReclaimType();
        $this->atualizarDados2();
    }

    public function updatedDtFim()
    {
        $this->atualizarDados();
        $this->atualizarTicketMedio();
        $this->atualizarTicketMedioReclaim();
        $this->atualizarTicketMedioResolution();
        $this->atualizarAprovedCategory();
        $this->atualizarReclaimType();
        $this->atualizarDados2();
    }

    public function atualizarDados()
    {
        $reclaims = $this->getReclaimsProperty();

        $novosDados = [
            'labels' => $reclaims->pluck('category')->toArray(),
            'data' => $reclaims->pluck('total')->toArray(),
        ];

        $this->dadosGrafico = $novosDados;

        $this->updateData($this->chartId1, $this->dadosGrafico['labels'], $this->dadosGrafico['data']);
    }

    public function atualizarDados2()
    {
        $reclaims = $this->getReclaimsActualProperty();

        $novosDados = [
            'labels' => $reclaims->pluck('category')->toArray(),
            'data' => $reclaims->pluck('total')->toArray(),
        ];

        $this->dadosGrafico4 = $novosDados;

        $this->updateData($this->chartId7, $novosDados['labels'], $novosDados['data']);
    }


    public function atualizarReclaimType()
    {
        $reclaims = $this->getReclaimsTypesProperty();

        $novosDados = [
            'labels' => ['Aanalise', 'Contratação',  'Viabilidade'],
            'data' => [
                $reclaims['Approvals'],
                $reclaims['Waiting'],
                $reclaims['Viabilities'],
            ],
        ];




        $this->pizzaReturnInternData = $novosDados;

        $this->updateData($this->chartId5, $novosDados['labels'], $novosDados['data']);
    }


    public function atualizarDaysReclaimType()
    {
        $reclaims = $this->getReclaimsDaysProperty();



        $labels = [];

        for ($i = 0; $i < 10 ; $i++) {
            $labels[] = (string)$i;
        }

        $labels[] = '10+';

        $this->multStackData = [
            'labels' => $labels,
            'datasets' => [
                [
                    'name' => 'Analises',
                    'data' => $reclaims['Approvals'],
                ],
                [
                    'name' => 'Contratação',
                    'data' => $reclaims['Waiting'],
                ],
                [
                    'name' => 'Viabilidade',
                    'data' => $reclaims['Viabilities'],
                ],
            ],
        ];

        // dd($this->multStackData);




        $this->emit('updateGraph2' . Str::studly($this->chartId6), [
            'labels' => $this->multStackData['labels'],
            'datasets' => $this->multStackData['datasets'],
        ]);
    }

    public function atualizarAprovedCategory()
    {
        $datas = $this->getApprovedStats();

        $novosDados = [
            'labels' => ['Liberado com RI', 'Liberado sem RI'],
            'data' => [$datas['withReclaims'], $datas['withoutReclaims']],
        ];

        $this->dadosGrafico2 = $novosDados;

        $this->updateData($this->chartId3, $novosDados['labels'], $novosDados['data']);
    }

    public function atualizarDays()
    {
        $days = $this->getTimestackProperty();


        // dd($days);

        $novosDados = [
            'labels' => [0, 1, 2, 3, 4, 5, 6, 7, 8, '9+'],
            'data2' => $days['noApproval'],
            'data1' => $days['withApproval'],
        ];

        $this->dadosGrafico1 = $novosDados;

        // $this->updateData($this->chartId, $novosDados['labels'], $novosDados['data']);

        $this->emit('updateGraph1' . Str::studly($this->chartId2), [
            'labels' => $novosDados['labels'],
            'dataset1Data' => $novosDados['data1'],
            'dataset2Data' => $novosDados['data2']
        ]);
    }

    /**
     * Ticket Médio de Analise
     *
     * @return void
     */
    public function atualizarTicketMedio()
    {
        $this->usuariosStats = $this->getAverageReactionsProperty();
    }

    public function getReclaimsProperty()
    {
        return Reclaim::whereHas('Approvals')
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

    public function getReclaimsActualProperty()
    {
        return Reclaim::whereHas('Approvals')
        ->where('completed', false)
        // ->when($this->dt_ini, function ($query) {
        //     return $query->where('created_at', '>=', $this->dt_ini);
        // })
        // ->when($this->dt_fim, function ($query) {
        //     return $query->where('created_at', '<=', $this->dt_fim);
        // })
            ->select(DB::raw("COALESCE(category, 'SEM CATEGORIA') as category"), DB::raw('count(*) as total'))
            ->groupBy(DB::raw("COALESCE(category, 'SEM CATEGORIA')"))
            ->get();
    }


    public function getReclaimsTypesProperty()
    {
        $query = Reclaim::query();

        // Apply date filters
        if ($this->dt_ini) {
            $query->where('created_at', '>=', $this->dt_ini);
        }
        if ($this->dt_fim) {
            $query->where('created_at', '<=', $this->dt_fim);
        }



        $results = [];

        // Approvals
        $approvalsQuery = clone $query;
        $results['Approvals'] = $approvalsQuery->whereHas('Approvals')->count();

        // Waiting
        $waitingQuery = clone $query;
        $results['Waiting'] = $waitingQuery->whereHas('Waiting')->count();

        // Viabilities
        $viabilitiesQuery = clone $query;
        $results['Viabilities'] = $viabilitiesQuery->whereHas('Viabilities')->count();



        return $results;
    }


    public function atualizarTicketMedioReclaim()
    {
        $this->reclaimsGeral = $this->getReclaimTicketProperty();
    }

    public function atualizarTicketMedioResolution()
    {
        $this->productionsStats  = $this->getAverageResolutionProperty();
    }


    /**
     * Pega o tempo das OVS pela data do STATUS, e então definir o tempo que está Disponpivel para análise.
     *
     * @return void
     */
    public function getTimestackProperty()
    {
        $today = Carbon::today();

        // Consulta base (a mesma que você forneceu)
        $baseQuery = Note::query();

        $baseQuery->where(function ($query) {
            $query->where(function ($qq) {
                $qq->whereIn('nstats', [46, 47, 48, 49, 50])
                   ->whereNotIn('rubrica', ['Incoporação'])
                   ->where('type_note', 2);
            }) ->orWhere(function ($qq) {
                $qq->where(function ($qs) {
                    $qs->where('type_note', 1)
                    ->where('centerjob', 'like', 'VIAB%');
                })
                ->orWhere(function ($qq) {
                    $qq->orWhereNull('centerjob')
                    ->where('type_note', 1);
                });
            });

        })
        ->whereHas('Orders', function ($q) {
            $q->where('statusSist', 'not like', 'ENTE%')
                  ->where('statusSist', 'not like', 'ENCE%')
                  ->whereHas('Operations', function ($sq) {
                      $sq->where('operacao', '0010')
                         ->where('status', 'like', 'ABER%');
                  });
        })
        ->where(function ($q) {
            $q->whereDoesntHave('Approval', function ($q) {
                $q->where('approved', true);
            })
              ->whereDoesntHave('Viabilities')
              ->whereDoesntHave('Waitings');
        })
        ->where(function ($q) {
            $q->where('txpriority', '!=', 'Emergente')
              ->orWhereNull('txpriority');
        });

        // Agrupamento para notas sem Approval
        $noApprovalData = clone $baseQuery; // Importante clonar para não modificar a consulta original
        $noApprovalData = $noApprovalData->whereDoesntHave('Approval')

            ->selectRaw('CASE
            WHEN DATEDIFF(?, dt_status) BETWEEN 0 AND 8 THEN DATEDIFF(?, dt_status)
            ELSE 9
            END AS days_difference, COUNT(*) as count', [$today, $today])
            ->groupBy('days_difference')
            ->orderBy('days_difference')
            ->get()
            ->pluck('count', 'days_difference')
            ->toArray();

        // Agrupamento para notas COM Approval
        $withApprovalData = clone $baseQuery;
        $withApprovalData = $withApprovalData->whereHas('Approval', function ($q) {
            $q->whereDoesntHave('Reclaims');

        })

            ->selectRaw('CASE
                WHEN DATEDIFF(?, dt_status) BETWEEN 0 AND 8 THEN DATEDIFF(?, dt_status)
                ELSE 9
                END AS days_difference, COUNT(*) as count', [$today, $today])
            ->groupBy('days_difference')
            ->orderBy('days_difference')
            ->get()
            ->pluck('count', 'days_difference')
            ->toArray();

        // Preencher com zeros os dias faltantes
        for ($i = 0; $i <= 9; $i++) {
            if (!isset($noApprovalData[$i])) {
                $noApprovalData[$i] = 0;
            }
            if (!isset($withApprovalData[$i])) {
                $withApprovalData[$i] = 0;
            }
        }

        ksort($noApprovalData); // Garante a ordem correta das chaves
        ksort($withApprovalData);

        return [
            'noApproval' => $noApprovalData,
            'withApproval' => $withApprovalData,
        ];
    }

    public function getAverageReactionsProperty()
    {
        $usuarios =  ViabilityApproval::join('users', 'viability_approvals.user_id', '=', 'users.id')
        ->leftJoin(DB::raw('(
            SELECT var.viability_approval_id, MIN(r.created_at) as first_reclaim_created_at
            FROM viability_approval_reclaim as var
            JOIN reclaims r ON var.reclaim_id = r.id
            GROUP BY var.viability_approval_id
        ) as first_reclaim'), function ($join) {
            $join->on('viability_approvals.id', '=', 'first_reclaim.viability_approval_id');
        })
        ->when($this->dt_ini, function ($query) {
            return $query->where('viability_approvals.created_at', '>=', $this->dt_ini);
        })
        ->when($this->dt_fim, function ($query) {
            return $query->where('viability_approvals.created_at', '<=', $this->dt_fim);
        })
        ->selectRaw('
            users.id as user_id,
            users.name,
            AVG(TIMESTAMPDIFF(MINUTE, viability_approvals.dt_status, viability_approvals.created_at)) as avg_reaction_time,
            AVG(
                CASE
                    WHEN first_reclaim.first_reclaim_created_at IS NOT NULL
                        THEN TIMESTAMPDIFF(MINUTE, viability_approvals.created_at, first_reclaim.first_reclaim_created_at)
                    ELSE TIMESTAMPDIFF(MINUTE, viability_approvals.created_at, viability_approvals.approved_at)
                END
            ) as avg_execution_time
        ')
        ->groupBy('users.id', 'users.name')
        ->orderBy('avg_execution_time', 'asc')
        ->get();

        return $usuarios;
    }


    /**
     * Tempo médio de resolução Interna
     *
     * @return void
     */
    public function getReclaimTicketProperty()
    {


        $reclaimsGeral = Reclaim::join('productions', 'reclaims.production_id', '=', 'productions.id')
            ->join('viability_approval_reclaim', 'reclaims.id', '=', 'viability_approval_reclaim.reclaim_id')
            ->when($this->dt_ini, function ($query) {
                return $query->where('reclaims.created_at', '>=', $this->dt_ini);
            })
            ->when($this->dt_fim, function ($query) {
                return $query->where('reclaims.created_at', '<=', $this->dt_fim);
            })
            ->where('reclaims.completed', true)
            ->selectRaw('
            AVG(TIMESTAMPDIFF(MINUTE, reclaims.created_at, reclaims.completed_at)) as avg_resolution,
            AVG(TIMESTAMPDIFF(MINUTE, reclaims.created_at, productions.dispatch_at)) as avg_reaction,
            AVG(TIMESTAMPDIFF(MINUTE, productions.att_at, productions.completed_at)) as avg_execution
        ')
            ->first();



        return $reclaimsGeral;

    }

    /**
     * Pega o tempo Médio em produção da resolução interna
     */
    public function getAverageResolutionProperty()
    {
        $productionsStats = Production::join('reclaims', 'productions.id', '=', 'reclaims.production_id')
            ->join('viability_approval_reclaim', 'reclaims.id', '=', 'viability_approval_reclaim.reclaim_id')
            ->join('users', 'productions.user_id', '=', 'users.id')
            ->where('reclaims.completed', true)
            ->when($this->dt_ini, function ($query) {
                return $query->where('productions.att_at', '>=', $this->dt_ini);
            })
            ->when($this->dt_fim, function ($query) {
                return $query->where('productions.att_at', '<=', $this->dt_fim);
            })
            ->selectRaw('
                productions.user_id,
                users.name,
                AVG(TIMESTAMPDIFF(MINUTE, productions.att_at, productions.completed_at)) as avg_resolution_production
            ')
            ->groupBy('productions.user_id', 'users.name')
            ->orderBy('avg_resolution_production', 'asc')
            ->get();



        return $productionsStats;
    }


    public function getApprovedStats()
    {
        // Contagem de ViabilityApprovals aprovados que possuam Reclaims
        $countApprovedWithReclaims = ViabilityApproval::where('approved', true)
            ->when($this->dt_ini, function ($query) {
                return $query->where('created_at', '>=', $this->dt_ini);
            })
            ->when($this->dt_fim, function ($query) {
                return $query->where('created_at', '<=', $this->dt_fim);
            })
            ->whereHas('Reclaims')
            ->count();

        // Contagem de ViabilityApprovals aprovados que NÃO possuam Reclaims
        $countApprovedWithoutReclaims = ViabilityApproval::where('approved', true)
            ->when($this->dt_ini, function ($query) {
                return $query->where('created_at', '>=', $this->dt_ini);
            })
            ->when($this->dt_fim, function ($query) {
                return $query->where('created_at', '<=', $this->dt_fim);
            })
            ->whereDoesntHave('Reclaims')
            ->count();

        return [
            'withReclaims' => $countApprovedWithReclaims,
            'withoutReclaims' => $countApprovedWithoutReclaims,
        ];
    }

    public function getReclaimsDaysProperty()
    {
        $query = Reclaim::query()->where('completed', false);

        $results = [];
        $days = [];

        // Initialize $days array. Important!
        for ($i = 0; $i <= 9; $i++) { // Changed to 29
            $days[$i] = 0;
        }
        $days[10] = 0; // Add a 31st element at index 30 for 30+ days


        // Approvals
        $approvalsData = $this->groupReclaimsByDay($query, 'Approvals');
        $results['Approvals'] = array_replace($days, $approvalsData); // Merge with zeros

        // Waiting
        $waitingData = $this->groupReclaimsByDay($query, 'Waiting');
        $results['Waiting'] = array_replace($days, $waitingData); // Merge with zeros

        // Viabilities
        $viabilitiesData = $this->groupReclaimsByDay($query, 'Viabilities');
        $results['Viabilities'] = array_replace($days, $viabilitiesData); // Merge with zeros

        return $results;
    }

    private function groupReclaimsByDay($baseQuery, $type)
    {
        $data = [];

        for ($i = 0; $i <= 9; $i++) { // Changed to 29
            $startDate = now()->subDays($i)->startOfDay();
            $endDate = now()->subDays($i)->endOfDay();

            $query = clone $baseQuery; // Important: clone to avoid modifying the original

            // Apply date filter for the specific day
            $query->whereBetween('created_at', [$startDate, $endDate]);

            switch ($type) {
                case 'Approvals':
                    $count = $query->whereHas('Approvals')->count();
                    break;
                case 'Waiting':
                    $count = $query->whereHas('Waiting')->count();
                    break;
                case 'Viabilities':
                    $count = $query->whereHas('Viabilities')->count();
                    break;
                default:
                    $count = 0;
            }

            $data[$i] = $count;
        }


        // Count for 30+ days
        $startDate = now()->subDays(10)->startOfDay();
        $query = clone $baseQuery;
        $query->where('created_at', '<', $startDate);

        switch ($type) {
            case 'Approvals':
                $count = $query->whereHas('Approvals')->count();
                break;
            case 'Waiting':
                $count = $query->whereHas('Waiting')->count();
                break;
            case 'Viabilities':
                $count = $query->whereHas('Viabilities')->count();
                break;
            default:
                $count = 0;
        }

        $data[10] = $count; // Assign 30+ day count to index 30

        return $data;
    }

    private function updateData(string $chartId = null, array $labels = [], array $data = [])
    {
        $this->dispatchBrowserEvent('updateGraph' . Str::studly($chartId), [
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    public function render()
    {
        return view('livewire.engineers.analises.analise-dashboard');
    }
}
