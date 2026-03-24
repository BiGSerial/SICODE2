<?php

namespace App\Http\Livewire\Reports;

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf8', 'pt_BR.UTF-8', 'pt_BR.iso88591', 'pt_BR@euro', 'pt_PT', 'portuguese');

use App\Custom\RuleBuilder;
use App\Exports\ProductionExport;
use App\Models\Note;
use App\Models\Production;
use App\Models\Service;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Custom\Notestatus;
use App\Exports\Reports\ProductionFullExport;
use App\Exports\Reports\ProductionsExportList;
use App\Helpers\TextFormatter;
use App\Jobs\ExportProductionListJob;
use App\Jobs\ExportProductionReportJob;
use App\Jobs\Reports\ExportProductionJob;
use Illuminate\Support\Facades\Auth;

class Productions extends Component
{
    use WithPagination;
    use TextFormatter;

    protected $paginationTheme = 'bootstrap';

    public $monthYear;
    public $company;
    public $search;

    public $advanceSearch;

    public $multisearch = [];

    public $service = [];

    public $dt_init;

    public $dt_end;

    public $complete = false;

    public $d5 = false;

    public $cities;


    protected $registers;

    protected $queryStrings = [
        'page'
    ];

    public function updatedCompany()
    {

        $this->resetPage();
    }

    public function updatedService()
    {
        $this->resetPage();
    }

    public function updatedMonthYear()
    {

        $this->dt_init = '';
        $this->dt_end = '';
        $this->resetPage();

    }

    public function updatedSearch()
    {
        if (trim($this->search)) {
            $this->multisearch = [];
            $this->resetPage();
        }
    }

    public function buscarMulti()
    {
        if ($this->advanceSearch) {
            $this->multisearch = $this->formatTextToArray($this->advanceSearch);
            $this->search = '';
            $this->advanceSearch = '';
            $this->resetPage();
            $this->dispatchBrowserEvent('hideModal');
        }
    }

    public function Export()
    {
        // if ($this->getListsProperty()->toBase()->count() > 30000) {
        //     $this->dispatchBrowserEvent('swal', [
        //         'position' => 'center',
        //         'icon'     => 'error',
        //         'title'    => "EXCESSO DE REGISTROS.",
        //         'html'    => "<div class='card'><div class='card-body'><p>Seu relatório possui mais de 30.000 registros, por favor, filtre melhor sua busca.</p></div></div>",
        //         'timer'    => 5000,
        //     ]);
        //     return;
        // }

        // return (new ProductionsExportList($this->getListsProperty()))->download(date('YmdHis-').'ProductionExportedList.xlsx');

        $params = [
            'complete'    => (bool) $this->complete,
            'monthYear'   => $this->monthYear,
            'd5'          => (bool) $this->d5,
            'service'     => $this->service,
            'dt_init'     => $this->dt_init,
            'dt_end'      => $this->dt_end,
            'company'     => $this->company,
            'search'      => $this->search,
            'multisearch' => $this->multisearch,
        ];

        ExportProductionJob::dispatch($params, auth()->user()->id);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => 'EXPORTAÇÃO EM ANDAMENTO.',
            'html'     => "<div class='card'><div class='card-body'><p>Seu relatório está sendo gerado. Você será notificado quando o arquivo estiver pronto para download.</p><p class='fw-bold'>Verifique sua Central de Notificação.</p></div></div>",
            'timer'    => 5000,
        ]);
    }

    public function Export2()
    {
        if (!count($this->service)) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => "Nenhum serviço selecionado.",
                'timer'    => 2500,
            ]);
            return;
        }

        $user = Auth::user();
        if (!$user) {
            $this->dispatchBrowserEvent('swal', [
                'position' => 'center',
                'icon'     => 'error',
                'title'    => "Erro: usuário não autenticado.",
                'timer'    => 2500,
            ]);
            return;
        }

        ExportProductionReportJob::dispatch($this->service, $this->monthYear, $this->dt_init, $this->dt_end, $user);

        $this->dispatchBrowserEvent('swal', [
            'position' => 'center',
            'icon'     => 'success',
            'title'    => "EXPORTAÇÃO EM ANDAMENTO.",
            'html'    => "<div class='card'><div class='card-body'><p>Seu relatório está sendo gerado, aguarde alguns instantes. Você será notificado quando o arquivo estiver pronto para download.</p> <p class='fw-bold'>Verifique sempre na sua Central de Notificação.</p></div></div>",
        ]);
    }

    // public function Search()
    // {
    //     $this->search = true;
    // }

    public function getListsProperty()
    {
        // if (!$this->monthYear && !$this->dt_init && !$this->dt_end) {
        //     $query = Production::query();
        //     $query->where('rejected', false);

        //     if (!$this->complete) {
        //         $query->where('completed', true);
        //     }

        //     if (auth()->user()->contract) {
        //         $query->where('company_id', auth()->user()->employee->contract->company_id);
        //     }

        //     $query->with('User', 'Company', 'Service', 'Note', 'Analise')
        //         ->orderBy('completed_at');

        //     return $query;
        // }

        $query = Production::query();
        $query->where('rejected', false);

        $column = 'completed_at';

        if (auth()->user()->contract) {
            $query->where('company_id', auth()->user()->employee->contract->company_id);
        }

        if ($this->monthYear) {
            $startDate = Carbon::parse($this->monthYear)->startOfMonth();
            $endDate = Carbon::parse($this->monthYear)->endOfMonth();
            $query->where(function ($q) use ($column, $startDate, $endDate) {
                $q->where(function ($done) use ($column, $startDate, $endDate) {
                    $done->where('completed', true)
                        ->whereBetween($column, [$startDate, $endDate]);
                });

                if ($this->complete) {
                    $q->orWhere('completed', false);
                }
            });

        }
        if (!$this->d5) {
            $query->where('d5', false);
        }

        if ($this->service) {
            $query->whereIn('service_id', $this->service);
        }

        if ($this->dt_init) {

            $query->where(function ($q) use ($column) {
                $q->where(function ($done) use ($column) {
                    $done->where('completed', true)
                        ->where($column, '>=', date('Y-m-d 0:00:00', strtotime($this->dt_init)));
                });

                if ($this->complete) {
                    $q->orWhere('completed', false);
                }
            });

        }

        if ($this->dt_end) {
            $query->where(function ($q) use ($column) {
                $q->where(function ($done) use ($column) {
                    $done->where('completed', true)
                        ->where($column, '<=', date('Y-m-d 23:59:59', strtotime($this->dt_end)));
                });

                if ($this->complete) {
                    $q->orWhere('completed', false);
                }
            });
        }

        if (!$this->monthYear && !$this->dt_init && !$this->dt_end) {
            if (!$this->complete) {
                $query->where('completed', true);
            }
        }

        if ($this->company) {
            $query->where('company_id', $this->company);
        }

        if (trim($this->search)) {
            $wildcard = str_contains($this->search, '*') || str_contains($this->search, '%')
               ? str_replace('*', '%', $this->search)
               : $this->search;

            if (str_contains($wildcard, '%')) {
                $type = 'like';
            } else {
                $type = '=';
            }


            $query->where(function ($q) use ($wildcard, $type) {
                $q->whereRelation('note', 'note', $type, $wildcard)
                    ->orWhereRelation('note.orders', 'ordem', $type, $wildcard)
                    ->orWhereRelation('note', 'material', $type, $wildcard);
            });
        }


        if (count($this->multisearch)) {
            $query->where(function ($q) {
                $q->whereRelation('Note', function ($qs) {
                    $qs->whereIn('note', $this->multisearch)
                        ->orWhereIn('material', $this->multisearch);

                })->orWhereRelation('Note.Orders', function ($qs) {
                    $qs->whereIn('ordem', $this->multisearch);
                });
            });
        }

        $query->with('User', 'Company', 'Service', 'Note', 'Analise')
            ->orderBy('completed_at');

        return $query;

    }

    public function getMonthYearList()
    {
        $oldestRecord = Production::selectRaw('MIN(completed_at) as MonthYear')->first();
        $newestRecord = Production::selectRaw('MAX(completed_at) as MonthYear')->first();



        if ($oldestRecord && $oldestRecord->MonthYear) {
            return (object) [
                'oldest' => Carbon::parse($oldestRecord->MonthYear)->format('Y-m'),
                'newest' => Carbon::parse($newestRecord->MonthYear)->format('Y-m'),
            ];
        }

        return [];
    }

    public function getCompanyList()
    {
        return Production::select('company_id')->with('Company')->groupBy('company_id')->get();
    }

    public function getServiceList()
    {
        return Production::select('service_id')->with('Service')->groupBy('Service_id')->get();
    }


    public function cleanAll()
    {
        $this->search = '';
        $this->multisearch = [];
        $this->advanceSearch = "";
        $this->dt_end = "";
        $this->dt_init = "";
        $this->monthYear = "";

    }

    public function render()
    {
        // Verifique se o botão Gerar foi clicado


        return view('livewire.reports.productions', [
            'month_list'   => $this->getMonthYearList(),
            'company_list' => $this->getCompanyList(),
            'service_list' => $this->getServiceList(),
            'lists' => $this->lists->paginate(100),
        ]);
    }
}
