<?php

namespace App\Http\Controllers;

use App\Models\{Manualnote, Production, User};
use Carbon\{Carbon, CarbonImmutable};
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->middleware('auth');

    }

    public function getProductionProperty()
    {
        return Production::Where('user_id', Auth()->User()->id)
                ->where('rejected', false)->Where('confirmed', true)
                ->where('d5', false)
                ->Select('completed_at', 'count(*) as total')
                ->groupBy('completed_at')
                ->get();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        if (Auth()->user()->first_pass) {

            return view('auth.change');

        } elseif (Auth()->User()->onlyparner) {

            return redirect()->route('partner.main.viability');

        } else {

            $userId         = auth()->user()->id;
            $currentMonth   = Carbon::now()->startOfMonth();
            $lastDayOfMonth = Carbon::now()->endOfMonth()->endOfDay();

            $prod = Production::where('user_id', $userId)
                ->where('confirmed', true)
                ->where('rejected', false)
                ->where('d5', false)
                ->whereBetween('completed_at', [$currentMonth, $lastDayOfMonth])
                ->select(DB::raw('DATE(completed_at) as date'), DB::raw('COUNT(*) as total'), DB::raw('SUM(postes_u) as postes'))
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get();

            $mes = Production::where('user_id', $userId)
                ->where('confirmed', true)
                ->where('rejected', false)
                ->where('d5', false)
                ->where('completed_at', '>=', Carbon::now()->subMonths(15))
                ->select(
                    DB::raw('YEAR(completed_at) as year'),
                    DB::raw('MONTH(completed_at) as month'),
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(postes_u) as postes')
                )
                ->groupBy('year', 'month')
                ->orderBy('year', 'ASC')
                ->orderBy('month', 'ASC')
                ->get();

            Carbon::setLocale('pt_BR');
            CarbonImmutable::setLocale('pt_BR');

            $formattedMes = $mes->map(function ($item) {
                $monthName = Carbon::createFromDate($item->year, $item->month)->format('F Y');

                return [
                    'label'  => $monthName,
                    'total'  => $item->total,
                    'postes' => $item->postes,
                ];
            });

            $waitingList = Manualnote::where('user_id', $userId)
                        // ->where('completed', true)
                        ->where('confirmed', false)
                        ->with('service')
                        ->get();

            $inconsistencies = Production::where('user_id', $userId)
                ->where('confirmed', false)->where('tries', '>=', 2)
                ->where('d5', false)
                ->with('Note', 'service')
                ->orderBy('completed_at')
                ->get();

            $statusCounts = DB::table('productions')
                ->where('user_id', $userId)
                ->where('rejected', false)
                ->where('d5', false)
                ->whereBetween('completed_at', [$currentMonth, $lastDayOfMonth])
                ->selectRaw('
                                    COUNT(*) as total_notes,
                                    SUM(IFNULL(postes_u, 0) + IFNULL(postes_c, 0)) as total_postes,
                                    SUM(CASE WHEN completed = 1 THEN (IFNULL(postes_u, 0) + IFNULL(postes_c, 0)) ELSE 0 END) as completed_postes,
                                    SUM(CASE WHEN confirmed = 1 THEN (IFNULL(postes_u, 0) + IFNULL(postes_c, 0)) ELSE 0 END) as confirmed_postes,
                                    COUNT(CASE WHEN completed = 1 THEN 1 END) as completed_count,
                                    COUNT(CASE WHEN confirmed = 1 THEN 1 END) as confirmed_count,
                                    COUNT(CASE WHEN DATE(completed_at) = ? THEN 1 END) as completed_today_count,
                                    SUM(CASE WHEN DATE(completed_at) = ? THEN IFNULL(postes_u, 0) + IFNULL(postes_c, 0) ELSE 0 END) as postes_today
                                ', [Carbon::today(), Carbon::today()])
                ->first();

            return view('home', [
                'prod_dia'        => $prod,
                'prod_mes'        => $formattedMes,
                'waiting_list'    => $waitingList,
                'inconsistencies' => $inconsistencies,
                'status_count'    => $statusCounts,
            ]);
        }

    }

    public function company()
    {

        $userId         = auth()->user()->id;
        $currentMonth   = Carbon::now()->startOfMonth();
        $lastDayOfMonth = Carbon::now()->endOfMonth()->endOfDay();

        $prod = Production::where('user_id', $userId)
            ->where('confirmed', true)
            ->where('rejected', false)
            ->where('d5', false)
            ->whereBetween('completed_at', [$currentMonth, $lastDayOfMonth])
            ->select(DB::raw('DATE(completed_at) as date'), DB::raw('COUNT(*) as total'), DB::raw('SUM(postes_u) as postes'))
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        $mes = Production::where('user_id', $userId)
            ->where('confirmed', true)
            ->where('rejected', false)
            ->where('d5', false)
            ->select(
                DB::raw('YEAR(completed_at) as year'),
                DB::raw('MONTH(completed_at) as month'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(postes_u) as postes'),
            )
            ->groupBy('year', 'month')
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'DESC')
            ->get();

        Carbon::setLocale('pt_BR');
        CarbonImmutable::setLocale('pt_BR');

        $formattedMes = $mes->map(function ($item) {
            $monthName = Carbon::createFromDate($item->year, $item->month)->format('F Y');

            return [
                'label'  => $monthName,
                'total'  => $item->total,
                'postes' => $item->postes,
            ];
        });

        $waitingList = Manualnote::where('user_id', $userId)
                    // ->where('completed', true)
                    // ->where('confirmed', false)
            ->with('service')
            ->get();

        $inconsistencies = Production::where('user_id', $userId)
            ->where('confirmed', false)->where('tries', '>=', 2)
            ->where('d5', false)
            ->with('Note', 'service')
            ->orderBy('completed_at')
            ->get();

        $statusCounts = DB::table('productions')
            ->where('user_id', $userId)
            ->where('rejected', false)
            ->where('d5', false)
            ->whereBetween('completed_at', [$currentMonth, $lastDayOfMonth])
            ->selectRaw('
                                    COUNT(*) as total_notes,
                                    SUM(IFNULL(postes_u, 0) + IFNULL(postes_c, 0)) as total_postes,
                                    SUM(CASE WHEN completed = 1 THEN (IFNULL(postes_u, 0) + IFNULL(postes_c, 0)) ELSE 0 END) as completed_postes,
                                    SUM(CASE WHEN confirmed = 1 THEN (IFNULL(postes_u, 0) + IFNULL(postes_c, 0)) ELSE 0 END) as confirmed_postes,
                                    COUNT(CASE WHEN completed = 1 THEN 1 END) as completed_count,
                                    COUNT(CASE WHEN confirmed = 1 THEN 1 END) as confirmed_count,
                                    COUNT(CASE WHEN DATE(completed_at) = ? THEN 1 END) as completed_today_count,
                                    SUM(CASE WHEN DATE(completed_at) = ? THEN IFNULL(postes_u, 0) + IFNULL(postes_c, 0) ELSE 0 END) as postes_today
                                ', [Carbon::today(), Carbon::today()])
            ->first();

        return view('company', [
            'prod_dia'        => $prod,
            'prod_mes'        => $formattedMes,
            'waiting_list'    => $waitingList,
            'inconsistencies' => $inconsistencies,
            'status_count'    => $statusCounts,
        ]);
    }

    public function profile($id)
    {
        if (Auth()->user()->id != $id) {
            abort(403);
        }

        if (!$user = User::with('company.address')->findOrFail($id)) {
            abort(404);
        }

        return view('profile', [
            'user' => $user,
        ]);
    }
}
