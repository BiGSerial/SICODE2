<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BtzeroController extends Controller
{
    public function main()
    {
        return view('btzero.main');
    }

    public function btzeroReport()
    {
        return view('btzero.btzeroreport');
    }

    public function SmcRejecteds()
    {
        return view('btzero.ramal_rejected_list');
    }

    public function histInform()
    {
        return view('btzero.btzero_inform_hist');
    }
}
