<?php

namespace App\Http\Livewire\Reports;

use App\Jobs\Reports\ExportHistoricRejectedListJob;
use App\Models\Company;
use App\Models\ReturnWork;
use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class HistoricRejectedReports extends Component
{
    use WithPagination;

    // Filtros
    public $dt_in;
    public $dt_out;
    public $searchNote;
    public $reason;

    /** @var array<int> */
    public array $companyIds = [];

    public $companies;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'dt_in'     => ['except' => '', 'as' => 'dtin'],
        'dt_out'    => ['except' => '', 'as' => 'dtout'],
        'searchNote' => ['except' => '', 'as' => 'nota'],
        'reason' => ['except' => '', 'as' => 'motivo'],
        'companyIds' => ['except' => [], 'as' => 'company'],
    ];

    public function mount()
    {
        $this->dt_in  = $this->dt_in ?: now()->startOfYear()->format('Y-m-d');
        $this->dt_out = $this->dt_out ?: now()->format('Y-m-d');


        if (Carbon::parse($this->dt_out)->greaterThan(now())) {
            $this->dt_out = now()->format('Y-m-d');
        }

        $this->companies = \App\Models\Company::select('id', 'name')->orderBy('name')->get();
    }

    public function exportToExcel()
    {
        $params = [
            'dt_in'      => $this->dt_in,
            'dt_out'     => $this->dt_out,
            'searchNote' => $this->searchNote,
            'reason' => $this->reason,
            'companyIds' => $this->companyIds,
        ];

        ExportHistoricRejectedListJob::dispatch($params, (string)auth()->id());

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'Exportação iniciada',
            'html'     => "<div class='card'><div class='card-body'>
            <p>Seu arquivo está sendo gerado.</p>
            <p class='mb-0'><strong>Quando concluir, o link aparecerá na sua Central de Notificações.</strong></p>
        </div></div>",
            'timer'    => 5000,
        ]);
    }

    /* ----------------------------------------------------------
     | Base query para reaproveitar filtros de data
     ---------------------------------------------------------- */
    protected function baseQuery()
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        return ReturnWork::query()
            ->with([
                'Workreport' => function ($q) {
                    // já traz Company e Note
                    $q->with(['Company:id,name', 'Note:id,note']);
                },
            ])
            ->whereBetween('created_at', [$start, $end])
            ->when($this->reason, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('category', 'like', '%' . $this->reason . '%')
                        ->orWhere('text_obs', 'like', '%' . $this->reason . '%');
                });
            })
            ->when(!empty($this->companyIds), function ($q) {
                $q->whereHas('Workreport', function ($wr) {
                    $wr->whereIn('company_id', $this->companyIds);
                });
            });
    }


    public function getMonthlyOpeningsProperty(): array
    {
        // --------- Preparação e "clamp" das datas ----------
        $dtOut = Carbon::parse($this->dt_out)->endOfDay();
        if ($dtOut->greaterThan(now())) {
            $dtOut = now()->endOfDay(); // não permitir futuro
        }

        // janela de 12 meses terminando em mês(dt_out), nunca no futuro
        $endMonth   = $dtOut->copy()->startOfMonth();
        $currentMon = now()->startOfMonth();
        if ($endMonth->greaterThan($currentMon)) {
            $endMonth = $currentMon;
        }
        $startMonth = $endMonth->copy()->subMonths(11)->startOfMonth();

        // gera sequência (12 labels)
        $months = collect();
        $cursor = $startMonth->copy();
        while ($cursor->lessThanOrEqualTo($endMonth)) {
            $months->push($cursor->copy());
            $cursor->addMonth();
        }
        if ($months->isEmpty()) {
            return [
                'type' => 'bar',
                'data' => ['labels' => [], 'datasets' => []],
                'options' => ['responsive' => true, 'maintainAspectRatio' => false],
            ];
        }

        // --------- Interseção com o entre-datas escolhido ----------
        $userStart = Carbon::parse($this->dt_in)->startOfDay();
        $userEnd   = Carbon::parse($this->dt_out)->endOfDay();
        if ($userEnd->greaterThan(now())) {
            $userEnd = now()->endOfDay(); // segurança extra
        }

        $rxStart = $months->first()->copy()->startOfMonth();
        $rxEnd   = $months->last()->copy()->endOfMonth();

        // interseção final = janela(12m) ∩ [dt_in, dt_out]
        $finalStart = $rxStart->greaterThan($userStart) ? $rxStart : $userStart;
        $finalEnd   = $rxEnd->lessThan($userEnd) ? $rxEnd : $userEnd;

        // --------- Busca agregada (considerando filtro de empreiteira) ----------
        $rows = collect();
        if ($finalStart->lessThanOrEqualTo($finalEnd)) {
            $rows = \App\Models\ReturnWork::query()
                ->join('work_reports as wr', 'wr.id', '=', 'return_works.work_report_id')
                ->when(!empty($this->companyIds), fn ($q) => $q->whereIn('wr.company_id', $this->companyIds))
                ->when($this->reason, function ($q) {
                    $q->where(function ($sq) {
                        $sq->where('return_works.category', 'like', '%' . $this->reason . '%')
                            ->orWhere('return_works.text_obs', 'like', '%' . $this->reason . '%');
                    });
                })
                ->whereBetween('return_works.created_at', [$finalStart, $finalEnd])
                ->selectRaw('YEAR(return_works.created_at) as y, MONTH(return_works.created_at) as m, COUNT(*) as total')
                ->groupBy('y', 'm')
                ->orderBy('y')->orderBy('m')
                ->get()
                ->keyBy(fn ($r) => sprintf('%04d-%02d', $r->y, $r->m));
        }

        // --------- Montagem dos dados (mantém 12 meses, zera fora da interseção) ----------
        $labels = [];
        $data   = [];
        foreach ($months as $m) {
            $labels[] = $m->translatedFormat('M/Y');

            // fora do range final? zera
            if ($m->lt($finalStart->copy()->startOfMonth()) || $m->gt($finalEnd->copy()->startOfMonth())) {
                $data[] = 0;
                continue;
            }

            $key = $m->format('Y-m');
            $data[] = (int) ($rows[$key]->total ?? 0);
        }

        // --------- Linha de média (inclui zeros para leitura consistente) ----------
        $avg = count($data) ? array_sum($data) / count($data) : 0;
        $avgLine = array_fill(0, count($labels), round($avg, 2));

        // --------- Config Chart.js ----------
        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'type' => 'bar',
                        'label' => 'Aberturas (Retornos) por mês',
                        'data'  => $data,
                        'backgroundColor' => 'rgba(0, 143, 251, 0.3)',
                        'borderColor'     => '#008FFB',
                        'borderWidth'     => 1,
                    ],
                    [
                        'type' => 'line',
                        'label' => 'Média mensal',
                        'data'  => $avgLine,
                        'borderColor'     => '#FBC02D',
                        'backgroundColor' => 'rgba(251,192,45,0.1)',
                        'borderDash'      => [5, 5],
                        'pointRadius'     => 0,
                        'tension'         => 0,
                        'fill'            => false,
                        'datalabels'      => ['display' => false],
                    ],
                ],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'top'],
                    'title'  => ['display' => true, 'text' => 'Aberturas mensais (últimos 12 meses)'],
                    'tooltip' => ['mode' => 'index', 'intersect' => false],
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title' => ['display' => true, 'text' => 'Qtd de Aberturas'],
                    ],
                    'x' => [
                        'title' => ['display' => true, 'text' => 'Mês'],
                    ],
                ],
            ],
        ];
    }



    /* ----------------------------------------------------------
     | 2) Volumetria por Empreiteira (company)
     |    Tipo: horizontal bar (indexAxis = 'y')
     ---------------------------------------------------------- */
    public function getByCompanyProperty(): array
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        $rows = ReturnWork::query()
            ->join('work_reports as wr', 'wr.id', '=', 'return_works.work_report_id')
            ->leftJoin('companies as c', 'c.id', '=', 'wr.company_id')
            ->whereBetween('return_works.created_at', [$start, $end])
            ->when($this->reason, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('return_works.category', 'like', '%' . $this->reason . '%')
                        ->orWhere('return_works.text_obs', 'like', '%' . $this->reason . '%');
                });
            })
            ->when(!empty($this->companyIds), fn ($q) => $q->whereIn('wr.company_id', $this->companyIds)) // 🔹 filtro
            ->selectRaw('COALESCE(c.name, "Sem empreiteira") as company_name, COUNT(*) as total')
            ->groupBy('company_name')
            ->orderByDesc('total')
            ->get();

        $labels = $rows->pluck('company_name')->toArray();
        $data   = $rows->pluck('total')->map(fn ($v) => (int)$v)->toArray();

        return [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Retornos por Empreiteira',
                    'data'  => $data,
                    'backgroundColor' => 'rgba(0, 227, 150, 0.25)',
                    'borderColor'     => '#00E396',
                    'borderWidth'     => 1,
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['display' => false],
                    'title'  => ['display' => true, 'text' => 'Volumetria por Empreiteira'],
                ],
                'scales' => [
                    'x' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Qtd de Retornos']],
                    'y' => ['title' => ['display' => true, 'text' => 'Empreiteira']],
                ],
            ],
        ];
    }

    /* ----------------------------------------------------------
     | 3) Distribuição por Categoria (ReturnWork.category)
     |    Tipo: doughnut
     ---------------------------------------------------------- */
    public function getByCategoryProperty(): array
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        $rows = ReturnWork::query()
            ->join('work_reports as wr', 'wr.id', '=', 'return_works.work_report_id')
            ->whereBetween('return_works.created_at', [$start, $end])
            ->when($this->reason, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('return_works.category', 'like', '%' . $this->reason . '%')
                        ->orWhere('return_works.text_obs', 'like', '%' . $this->reason . '%');
                });
            })
            ->when(!empty($this->companyIds), fn ($q) => $q->whereIn('wr.company_id', $this->companyIds)) // 🔹 filtro
            ->selectRaw('COALESCE(return_works.category, "Sem categoria") as cat, COUNT(*) as total')
            ->groupBy('cat')
            ->orderByDesc('total')
            ->get();

        $labels = $rows->pluck('cat')->toArray();
        $data   = $rows->pluck('total')->map(fn ($v) => (int)$v)->toArray();

        // Paleta simples (Chart.js aceita repetir)
        $bg = [
            'rgba(0, 143, 251, 0.3)',    // Azul
            'rgba(0, 227, 150, 0.3)',    // Verde
            'rgba(251, 192, 45, 0.3)',    // Amarelo
            'rgba(255, 69, 96, 0.3)',     // Vermelho
            'rgba(119, 93, 208, 0.3)',    // Roxo
            'rgba(255, 159, 64, 0.3)',    // Laranja
            'rgba(40, 199, 111, 0.3)',    // Verde esmeralda
            'rgba(83, 166, 250, 0.3)',    // Azul claro
            'rgba(32, 201, 151, 0.3)',    // Turquesa
            'rgba(206, 0, 255, 0.3)',     // Magenta
            'rgba(255, 69, 0, 0.3)',      // Vermelho-laranja
            'rgba(46, 204, 113, 0.3)',    // Verde-menta
            'rgba(142, 68, 173, 0.3)',    // Roxo escuro
            'rgba(52, 152, 219, 0.3)',    // Azul royal
            'rgba(241, 196, 15, 0.3)',    // Amarelo ouro
            'rgba(211, 84, 0, 0.3)',      // Laranja escuro
            'rgba(22, 160, 133, 0.3)',    // Verde azulado
            'rgba(192, 57, 43, 0.3)',     // Vermelho escuro
            'rgba(155, 89, 182, 0.3)',    // Lilás
            'rgba(243, 156, 18, 0.3)',    // Âmbar
        ];
        $border = [
            '#008FFB','#00E396','#FBC02D','#FF4560','#775DD0','#FF9F40','#28C76F'
        ];
        // expande se necessário
        while (count($bg) < count($labels)) {
            $bg = array_merge($bg, $bg);
            $border = array_merge($border, $border);
        }

        return [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Retornos por Categoria',
                    'data' => $data,
                    'backgroundColor' => array_slice($bg, 0, count($labels)),
                    'borderColor' => array_slice($border, 0, count($labels)),
                    'borderWidth' => 1,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => ['position' => 'right'],
                    'title' => ['display' => true, 'text' => 'Distribuição por Categoria'],
                ],
                'cutout' => '60%',
            ],
        ];
    }

    /* ----------------------------------------------------------
     | Lista paginada (com searchNote só na lista)
     ---------------------------------------------------------- */
    protected function listQuery()
    {
        $start = Carbon::parse($this->dt_in)->startOfDay();
        $end   = Carbon::parse($this->dt_out)->endOfDay();

        return $this->baseQuery()
            ->when($this->searchNote, function ($q) {
                $q->whereHas('Workreport.Note', function ($nq) {
                    $nq->where('note', 'like', '%' . $this->searchNote . '%');
                });
            })
            ->orderByDesc('created_at');
    }

    public function updatingSearchNote()
    {
        $this->resetPage();
    }
    public function updatingReason()
    {
        $this->resetPage();
    }
    public function updatingDtIn()
    {
        $this->resetPage();
    }
    public function updatingDtOut()
    {
        $this->resetPage();
    }

    public function updatingCompanyIds()
    {
        $this->resetPage();
    }

    /* ----------------------------------------------------------
    | Dispara eventos para atualizar os 3 gráficos
     ---------------------------------------------------------- */
    public function updatedDtIn()
    {
        $this->dispatchCharts();
    }

    public function updatedDtOut()
    {
        if (Carbon::parse($this->dt_out)->greaterThan(now())) {
            $this->dt_out = now()->format('Y-m-d');
        }
        $this->dispatchCharts();
    }

    public function updatedCompanyIds()
    {
        $this->dispatchCharts();
    }
    public function updatedReason()
    {
        $this->dispatchCharts();
    }

    protected function dispatchCharts()
    {
        $this->dispatchBrowserEvent('chart-monthly-openings', $this->monthlyOpenings);
        $this->dispatchBrowserEvent('chart-by-company', $this->byCompany);
        $this->dispatchBrowserEvent('chart-by-category', $this->byCategory);
    }

    public function render()
    {
        return view('livewire.reports.historic-rejected-reports', [
            'list' => $this->listQuery()->paginate(20),
            'monthly' => $this->monthlyOpenings,
            'company' => $this->byCompany,
            'category' => $this->byCategory,
        ]);
    }
}
