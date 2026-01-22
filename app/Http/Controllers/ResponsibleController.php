<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ResponsibleController extends Controller
{
    public function main()
    {
        return view('responsible.main');
    }

    public function viab_list()
    {
        return view('responsible.viablist');
    }

    public function viability_waiting()
    {
        return view('responsible.viability_waiting');
    }

    public function viab_reject()
    {
        return view('responsible.rejectedViabList');
    }

    public function justified_viab()
    {
        return view('responsible.justifyViab');
    }

    public function viab_hist()
    {
        return view('responsible.viahist');
    }

    public function inform_obra()
    {
        return view('responsible.workinform');
    }

    public function inform_list()
    {
        return view('responsible.workinformList');
    }

    public function intern_return()
    {
        return view('responsible.returnInternList');
    }

    public function approve_list()
    {
        return view('responsible.approval_list');
    }

    public function approve_control()
    {
        return view('responsible.approval_control');
    }

    public function approve_hist()
    {
        return view('responsible.approval_history');
    }

    public function partial_hist()
    {
        return view('responsible.parcial_hist');
    }

    // D5 Aguardando Resolução
    public function waiting_dfive()
    {
        return view('responsible.waitingFiveNotes');
    }

    public function adsRequests()
    {
        return view('responsible.ads_requests');
    }
}
