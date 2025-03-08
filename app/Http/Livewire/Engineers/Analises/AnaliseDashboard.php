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
    public $chartId;
    public $chartId2;
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

    public function mount()
    {
        $this->chartId = 'chart-' . Str::random(8);
        $this->chartId2 = 'chart2-' . Str::random(8);

        // Data inicial e final do mês
        $this->month = Carbon::today()->format('Y-m');
        $this->dt_ini = Carbon::today()->startOfMonth()->format('Y-m-d');
        $this->dt_fim = Carbon::today()->endOfMonth()->format('Y-m-d');

        $this->atualizarDados();
        $this->atualizarDays();
        $this->atualizarTicketMedio();
        $this->atualizarTicketMedioReclaim();
        $this->atualizarTicketMedioResolution();
    }

    public function updatedMonth()
    {
        $this->dt_ini = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
        $this->dt_fim = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');

        $this->atualizarDados();
        $this->atualizarTicketMedio();
        $this->atualizarTicketMedioReclaim();
        $this->atualizarTicketMedioResolution();
    }

    public function updatedDtIni()
    {
        $this->atualizarDados();
        $this->atualizarTicketMedio();
        $this->atualizarTicketMedioReclaim();
        $this->atualizarTicketMedioResolution();
    }

    public function updatedDtFim()
    {
        $this->atualizarDados();
        $this->atualizarTicketMedio();
        $this->atualizarTicketMedioReclaim();
        $this->atualizarTicketMedioResolution();
    }

    public function atualizarDados()
    {
        $reclaims = $this->getReclaimsProperty();

        $novosDados = [
            'labels' => $reclaims->pluck('category')->toArray(),
            'data' => $reclaims->pluck('total')->toArray(),
        ];

        $this->dadosGrafico = $novosDados;

        $this->updateData($this->chartId, $novosDados['labels'], $novosDados['data']);
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

        $this->emit('updateGraph1' . Str::studly($this->chartId), [
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
        })
        ->where('type_note', 2);

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
        $withApprovalData = $withApprovalData->whereHas('Approval') // Adiciona o filtro para WITH Approval
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
        $usuarios =  ViabilityApproval::whereRelation('Note', 'type_note', 2)
        ->join('users', 'viability_approvals.user_id', '=', 'users.id')
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


    private function updateData(string $chartId = null, array $labels = [], array $data = [])
    {
        $this->dispatchBrowserEvent('updateGraph' . Str::studly($this->chartId), [
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    public function render()
    {
        return view('livewire.engineers.analises.analise-dashboard');
    }
}
