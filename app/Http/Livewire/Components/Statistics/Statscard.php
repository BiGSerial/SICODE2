<?php

namespace App\Http\Livewire\Components\Statistics;

use App\Custom\RuleBuilder;
use App\Models\{Note, Production, Service};
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Statscard extends Component
{
    public $service;

    public $title;

    public $company_s;

    public $user_s;

    public $update = false;

    protected $listeners = [
        'refreshMe'     => '$refresh',
        'searchUser'    => 'searchUser',
        'searchCompany' => 'searchCompany',
        'end_update'    => 'endUpdate',
    ];

    public function mount($service)
    {
        $this->service = Service::where('uuid', $service)->with('Status')->first();
        $this->title   = mb_strtoupper($this->service->service);
    }

    public function searchCompany($company)
    {
        $this->company_s = $company;
        $this->emit('refreshMe');
    }

    public function searchUser($user)
    {
        $this->user_s = $user;
        $this->emit('refreshMe');
    }

    public function endUpdate()
    {
        $this->update = false;

    }

    public function getCountserviceProperty()
    {
        $firstDayOfMonth = Carbon::now()->firstOfMonth();
        $lastDayOfMonth  = Carbon::now()->lastOfMonth();

        $query = Note::query();

        RuleBuilder::applyRules($query, $this->service->Status);

        $query->withCount([
            'productions' => function ($query) use ($firstDayOfMonth, $lastDayOfMonth) {
                $query->whereBetween('att_at', [$firstDayOfMonth, $lastDayOfMonth])
                    ->where('service_id', $this->service->uuid)
                    ->when($this->user_s, function ($q) {
                        return $q->where('user_id', $this->user_s);
                    })
                    ->when($this->company_s, function ($q) {
                        return $q->where('company_id', $this->company_s);
                    });
            },
        ]);

        // return Note::whereIn('nstats', $this->service->Status->pluck('status'))->withCount([
        //     'productions' => function ($query) use ($firstDayOfMonth, $lastDayOfMonth) {
        //         $query->whereBetween('att_at', [$firstDayOfMonth, $lastDayOfMonth])
        //             ->where('service_id', $this->service->uuid);
        //     },
        // ])->get();

        return $query->get();

    }

    public function getCounthourserviceProperty()
    {
        $firstDayOfMonth = Carbon::now()->firstOfMonth();
        $lastDayOfMonth  = Carbon::now()->lastOfMonth();

        $query = Production::where('service_id', $this->service->uuid)
            ->when($this->user_s, function ($q) {
                return $q->where('user_id', $this->user_s);
            })
            ->when($this->company_s, function ($q) {
                return $q->where('company_id', $this->company_s);
            })
            ->whereBetween('completed_at', [$firstDayOfMonth, $lastDayOfMonth])
            ->select(
                DB::raw('HOUR(completed_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('HOUR(completed_at)'))
            ->orderBy(DB::raw('HOUR(completed_at)'))
            ->get();

        // if ($this->update) {
        //     $this->emit('updateGraph');
        // }

        return $query;
    }

    public function getCountdayserviceProperty()
    {
        $firstDayOfMonth = now()->startOfMonth();
        $lastDayOfMonth  = now()->endOfMonth();

        return Production::where('service_id', $this->service->uuid)
            ->where('completed', true)
            ->where('rejected', false)

            ->whereBetween('completed_at', [$firstDayOfMonth, $lastDayOfMonth])
            ->when($this->user_s, function ($query) {
                $query->where('user_id', $this->user_s);
            })
            ->when($this->company_s, function ($query) {
                $query->where('company_id', $this->company_s);
            })
            ->groupByRaw('DATE(completed_at)')
            ->orderBy('date')
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as total, SUM(postes_u) as postes')
            ->get();
    }

    // public function getNotesdaysleftProperty()
    // {
    //     $query = Note::query();

    //     RuleBuilder::applyRules($query, $this->service->Status);

    //     $query->where('type_note', 2)
    //     ->selectRaw('(CASE
    //     WHEN (30 - days_left) >= 31 THEN 31
    //     ELSE (30 - days_left)
    //   END) AS days_remaining')
    //     ->selectRaw('COUNT(*) AS count')

    //     ->groupBy('days_remaining')
    //     ->orderBy('days_remaining');

    //     return $query->get();
    // }

    public function getNotesdaysleftProperty()
    {
        $query = Note::query();

        RuleBuilder::applyRules($query, $this->service->Status);

        $query->where('type_note', 2)
            ->selectRaw('(CASE
    WHEN notes.group2 LIKE "%MT%" AND notes.mmgd = true THEN (45 - days_left)
    ELSE
        (CASE
            WHEN (30 - days_left) >= 31 THEN 31
            ELSE (30 - days_left)
        END)
    END) AS days_remaining')
            ->selectRaw('COUNT(*) AS count')
            ->leftJoin('productions', function ($join) {
                $join->on('notes.id', '=', 'productions.note_id')
                    ->whereColumn('notes.dt_status', '=', 'productions.dt_note');
            })
            ->selectRaw('COUNT(productions.id) AS production_count')
            ->selectRaw('COUNT(*) - COUNT(productions.id) AS nota_att')
            ->groupBy('days_remaining')
            ->orderBy('days_remaining');

        return $query->get();
    }

    public function getWorkingDays($start, $end)
    {
        $start    = new DateTime($start);
        $end      = new DateTime($end);
        $interval = new DateInterval('P1D'); // Intervalo de 1 dia

        $period      = new DatePeriod($start, $interval, $end);
        $workingDays = 1;

        foreach ($period as $date) {

            if ($date->format('N') < 6) {
                $workingDays++;
            }

        }

        return $workingDays;
    }

    public function render()
    {
        return view('livewire.components.statistics.statscard', [
            'service_count' => $this->countservice,
            'hour_count'    => $this->counthourservice,
            'day_count'     => $this->countdayservice,
            'workdays'      => $this->getWorkingDays(date('Y-m-01'), date('Y-m-d')),
            'days_left'     => $this->notesdaysleft,
        ]);
    }
}
