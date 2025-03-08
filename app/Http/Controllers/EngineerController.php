<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EngineerController extends Controller
{
    public function main()
    {
        return view('engineers.main');
    }

    public function viab_list()
    {
        return view('engineers.viablist');
    }

    public function viability_waiting()
    {
        return view('engineers.viability_waiting');
    }

    public function viab_reject()
    {
        return view('engineers.rejectedViabList');
    }

    public function justified_viab()
    {
        return view('engineers.justifyViab');
    }

    public function viab_hist()
    {
        return view('engineers.viahist');
    }

    public function inform_obra()
    {
        return view('engineers.workinform');
    }

    public function inform_list()
    {
        return view('engineers.workinformList');
    }

    public function intern_return()
    {
        return view('engineers.returnInternList');
    }

    public function viability_reports()
    {
        return view('engineers.viabilityreports');
    }

    public function waiting_parc()
    {
        return view('engineers.parcial_inform_waiting');
    }

    public function hist_parc()
    {
        return view('engineers.parcial_hist');
    }


    // Analises
    public function analises_dashboard()
    {
        return view('engineers.analises.approval_dashboard');
    }

    public function analises_toAnalise()
    {
        return view('engineers.analises.approval_list');
    }

    public function analises_inAnalise()
    {
        return view('engineers.analises.approval_control');
    }

    public function analises_analised()
    {
        return view('engineers.analises.approval_history');
    }
}
