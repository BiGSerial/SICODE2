<?php

namespace App\Http\Livewire\Engineers\Ads;

use App\Models\Company;
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


    public $dataReturnInform = [
        'labels' => ['A', 'B', 'C'],
        'data' => [10, 20, 70],
    ];









    public function mount()
    {
        // Definição do Valor do Id do Gráfico
        $this->returnInformChart1 = 'dataReturnInformChart-' . Str::random(8);




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
                (SELECT DATEDIFF(adsforms.created_at, work_reports.informed_at)
                    FROM adsforms
                    WHERE adsforms.work_report_id = work_reports.id
                    AND adsforms.created_at > work_reports.informed_at
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
                    $q->whereRaw('DATEDIFF(adsforms.created_at, work_reports.informed_at) > ?', [6]);
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
            ->whereDoesntHave('note.adsform')
            ->whereDoesntHave('note.oldAds')
            ->where(function ($query) use ($sevenDaysAgo) {
                $query->where('informed_at', '<=', $sevenDaysAgo);
            })
            ->paginate(15, ['*'], 'workReportsWithoutAdsPage');
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
    }

    public function render()
    {
        return view('livewire.engineers.ads.dashboard', [
            'workReportsVencidos' => $this->getWorkReportsWithoutAdsRelation(),
            'workReportsAdsVencidos' => $this->getWorkReportsRelation(),
        ]);
    }
}
