<?php

namespace App\Http\Controllers;

class MonitorController extends Controller
{
    public function Services()
    {
        return view('monitor.services');
    }

    public function Inconsistency()
    {
        return view('monitor.inconsistency');
    }

    public function Analises()
    {
        return view('monitor.analiseactivity');
    }

    public function logger()
    {
        return view('monitor.logger');
    }
}
