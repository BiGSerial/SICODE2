<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
