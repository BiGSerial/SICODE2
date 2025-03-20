<?php

namespace App\Http\Livewire\Partner;

use App\Models\ReturnWork;
use App\Models\Viability;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;

class Main extends Component
{
    public $pizza1;
    public $pizza2;

    public $month;
    public $dt_ini;
    public $dt_fim;

    public $dadospizza1 = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public $dadospizza2 = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];

    public function mount()
    {
        $this->pizza1 = 'chart-' . StR::random(8);
        $this->pizza2 = 'chart-' . StR::random(8);

        $this->month =  now()->format('Y-m');
        $this->dt_ini = now()->startOfMonth()->format('Y-m-d');
        $this->dt_fim = now()->endOfMonth()->format('Y-m-d');

        // Inicializar Graficos
        $this->atualizarViabilityCounts();
        $this->atualizaReturnWorkReports();

    }

    public function toUpdateGraphs()
    {
        $this->atualizarViabilityCounts();
        $this->atualizaReturnWorkReports();
    }

    public function updatedMonth()
    {
        $this->dt_ini = Carbon::parse($this->month)->startOfMonth()->format('Y-m-d');
        $this->dt_fim = Carbon::parse($this->month)->endOfMonth()->format('Y-m-d');

        $this->toUpdateGraphs();
    }

    public function updatedDtIni()
    {
        $this->month =  Carbon::parse($this->dt_ini)->format('Y-m');

        $this->toUpdateGraphs();
    }

    public function updatedDtFim()
    {


        $this->toUpdateGraphs();
    }

    public function getViabilityDueDate()
    {
        $today = Carbon::today();
        $threeDaysFromNow = Carbon::today()->addDays(3);

        $query = Viability::query()
            ->where('approved', false)
            ->where('rejected', false)
            ->where('completed', false)
            ->whereNotNull('sended_at');

        if (!auth()->user()->superadm) {
            if (Auth()->user()->Companies->isNotEmpty()) {
                $query->where(function ($q) {
                    $q->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                        ->orWhere('company_id', Auth()->user()->Company->id);
                });
            } else {
                $query->where('company_id', Auth()->user()->Company->id);
            }
        }

        $viabilities = $query->get();
        $dueSoon = [];

        foreach ($viabilities as $viability) {
            $dueDate = Carbon::parse($viability->sended_at)->addDays(7 + $viability->getDays());

            if ($dueDate->gte($today) && $dueDate->lte($threeDaysFromNow)) {
                $dueSoon[] = $viability;
            }
        }
        return $dueSoon;
    }


    public function getViabilityCounts()
    {
        $user = auth()->user();
        $companyIds = [];

        if (!$user->superadm) {
            if ($user->Companies->isNotEmpty()) {
                $companyIds = $user->Companies->pluck('id')->toArray();
                $companyIds[] = $user->Company->id; // Adiciona a própria empresa do usuário
                $companyIds = array_unique($companyIds); // Remove duplicatas
            } else {
                $companyIds = [$user->Company->id];
            }
        }

        $baseQuery = Viability::query();

        // Add date conditionals for sended_at if dt_ini and dt_fim exist
        if (!empty($this->dt_ini)) {
            $baseQuery->whereDate('sended_at', '>=', $this->dt_ini);
        }
        if (!empty($this->dt_fim)) {
            $baseQuery->whereDate('sended_at', '<=', $this->dt_fim);
        }


        if (!empty($companyIds)) {
            $baseQuery->where(function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        }

        $counts = [
            'inViability' => (clone $baseQuery)->where('approved', false)
                ->where('rejected', false)
                ->where('completed', false)
                ->where('tacit', false)
                ->count(),

            'realized' => (clone $baseQuery)->where(
                function ($q) {
                    $q->where('approved', true)
                        ->orWhere('rejected', true);
                }
            )
                ->where('tacit', false)
                ->count(),

            'notRealized' => (clone $baseQuery)->where('tacit', true)
                ->count(),
        ];

        return $counts;
    }


    public function atualizarViabilityCounts()
    {
        $counts = $this->getViabilityCounts();

        $this->dadospizza1 = [
            'labels' => ['Em Viabilidade', 'Realizadas', 'Não Realizadas'],
            'data' => [
                $counts['inViability'],
                $counts['realized'],
                $counts['notRealized'],
            ],
        ];

        $this->updateDataPizza($this->pizza1, ['Em Viabilidade', 'Realizadas', 'Não Realizadas'], [
            $counts['inViability'],
            $counts['realized'],
            $counts['notRealized'],
        ]);
    }


    public function getReturnWorkReports()
    {


        $user = auth()->user();
        $companyIds = [];

        if (!$user->superadm) {
            if ($user->Companies->isNotEmpty()) {
                $companyIds = $user->Companies->pluck('id')->toArray();
                $companyIds[] = $user->Company->id; // Adiciona a própria empresa do usuário
                $companyIds = array_unique($companyIds); // Remove duplicatas
            } else {
                $companyIds = [$user->Company->id];
            }
        }


        $baseQuery = ReturnWork::query();

        if ($companyIds) {
            $baseQuery->whereRelation('Workreport', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        }

        // Add date conditionals if provided. Assuming you have a 'created_at' column
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


    private function updateDataPizza(string $chartId = null, array $labels = [], array $data = [])
    {
        $this->dispatchBrowserEvent('updateGraph' . Str::studly($chartId), [
            'labels' => $labels,
            'data' => $data,
        ]);
    }



    public function render()
    {
        return view('livewire.partner.main', [
            'dueSoon' => $this->getViabilityDueDate(),
        ]);
    }
}
