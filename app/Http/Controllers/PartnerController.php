<?php

namespace App\Http\Controllers;

class PartnerController extends Controller
{
    public function viability()
    {
        return view('partner.viability');
    }

    public function historic_viab()
    {
        return view('partner.hist_viability');
    }
}
