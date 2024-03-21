<?php

namespace App\Http\Controllers;

class ReportsController extends Controller
{
    public function productions()
    {
        return view('reports.productions');
    }

    public function search()
    {
        return view('reports.busca');
    }

    public function advancedsearch()
    {
        return view('reports.buscaavancada');
    }
}
