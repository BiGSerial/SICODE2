<?php

namespace App\Http\Livewire\Partner;

use App\Models\WorkReport;
use App\Models\ReturnWork;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class WorkedRejectedList extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage;
    public $search;


    protected $listeners = [
        'refresh_rejected' => '$refresh',
    ];


    protected $queryString = [
        'search'  => ['except' => '', 'as' => 'buscar'],
        'page'    => ['except' => 1, 'as' => 'p'],
        'perPage' => ['as' => 'pp'],
    ];



    public function getListsProperty()
    {
        return WorkReport::when(!Auth()->User()->superadm, function ($q) {
            $q->where(function ($query) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
            });
        })
        ->addSelect([
            'last_returned_at' => ReturnWork::select('created_at')
                ->whereColumn('work_report_id', 'work_reports.id')
                ->latest('created_at')
                ->limit(1),
        ])
        ->where('rejected', true)
         ->whereDoesntHave('Note', function ($q) {
             $q->whereIn('nstats', [55])
             ->orWhere(function ($q) {
                 $q->where('nstats', 99)
                   ->where('type_note', 1);
             });
         })
        ->orderByRaw('last_returned_at IS NULL')
        ->orderBy('last_returned_at')
        ->orderBy('work_reports.id')
        ->paginate($this->perPage);
    }

    public function reinform(int $workReportId)
    {
        $workReport = WorkReport::when(!Auth()->User()->superadm, function ($q) {
            $q->where(function ($query) {
                $query->whereIn('company_id', Auth()->user()->Companies->pluck('id')->toArray())
                    ->orWhere('company_id', Auth()->user()->Company->id);
            });
        })
            ->where('rejected', true)
            ->findOrFail($workReportId);

        $token = Str::random(48);

        session()->put("partner_reinform_work_report.{$token}", [
            'work_report_id' => $workReport->id,
            'created_at' => now()->timestamp,
        ]);

        return redirect()->route('partner.report.reinformWorkreport', ['token' => $token]);
    }

    public function render()
    {
        return view('livewire.partner.worked-rejected-list', [
            'lists' => $this->lists
        ]);
    }
}
