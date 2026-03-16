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

    public function informeAdsTacita()
    {
        return view('reports.informe-ads-tacita');
    }

    public function adsSolicitadas()
    {
        return view('reports.ads-solicitadas');
    }

    public function rejectedWorkReports()
    {
        return view('reports.rejectedworkedreports');
    }

    public function lookatnotes()
    {
        return view('reports.lookatnote');
    }

    public function equipments()
    {
        return view('reports.equipments_search');
    }

    public function historicRejectReports()
    {
        return view('reports.HistoricRejectReports');
    }

    public function return_intern_dashboard()
    {
        return view('reports.return-intern-dashboard');
    }

    public function return_intern_list()
    {
        return view('reports.return-intern-list');
    }

    public function consulta_d5()
    {
        return view('reports.consulta_d5');
    }

    public function returnWorkReports()
    {
        return view('reports.return-work-reports');
    }

    public function cancellationDashboard()
    {
        return view('reports.cancellation-dashboard');
    }

    public function cancellationList()
    {
        return view('reports.cancellation-list');
    }
}
