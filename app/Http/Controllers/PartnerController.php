<?php

namespace App\Http\Controllers;

class PartnerController extends Controller
{
    public function main()
    {
        return view('partner.main');
    }

    public function viability()
    {
        return view('partner.viability');
    }

    public function hired_viability()
    {
        return view('partner.hired_viability');
    }

    public function historic_viab()
    {
        return view('partner.hist_viability');
    }

    public function workreport()
    {
        return view('partner.workreport');
    }

    public function workedlist()
    {
        return view('partner.worksList');
    }

    public function rejectedWorked()
    {
        return view('partner.workedRejectedList');
    }

    public function rejectedViabList()
    {
        return view('partner.RejectList');
    }

    public function tacitViabList()
    {
        return view('partner.tacit_list');
    }
}
