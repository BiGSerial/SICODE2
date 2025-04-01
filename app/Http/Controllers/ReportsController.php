<?php

namespace App\Http\Controllers;

class ReportsController extends Controller
{
    public function productions()
    {
        return view('reports.productions');
    }

    public function viabilities()
    {
        return view('reports.viabilities');
    }

    public function search()
    {
        return view('reports.busca');
    }

    public function advancedsearch()
    {
        return view('reports.buscaavancada');
    }

    public function workreports()
    {
        return view('reports.workreports');
    }

    public function rejectedWorkReports()
    {
        return view('reports.rejectedworkedreports');
    }

    public function lookatnotes()
    {
        return view('reports.lookatnote');
    }
}
