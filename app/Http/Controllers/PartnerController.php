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
}
