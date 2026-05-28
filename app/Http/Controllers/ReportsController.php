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
        return redirect()->route('ads.dashboard');
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

    public function fiveNotesReport()
    {
        return view('reports.five-notes-report');
    }

    public function complaintsMedeReport()
    {
        return view('reports.protest-mede');
    }

    public function projectReviewDashboard()
    {
        return view('reports.project-review-dashboard');
    }

    public function projectReviewHistory()
    {
        return view('reports.project-review-history');
    }

    public function productionWall()
    {
        return view('reports.production-wall');
    }

    public function productionWallV2(int $wall)
    {
        return view('reports.production-wall-v2', [
            'wallId' => $wall,
            'screenId' => null,
        ]);
    }

    public function productionWallV2Screen(int $wall, int $screen)
    {
        return view('reports.production-wall-v2', [
            'wallId' => $wall,
            'screenId' => $screen,
        ]);
    }

    public function productionWallV2Vue(int $wall)
    {
        return view('reports.production-wall-v2-vue', [
            'wallId' => $wall,
            'screenId' => null,
        ]);
    }

    public function productionWallV2VueScreen(int $wall, int $screen)
    {
        return view('reports.production-wall-v2-vue', [
            'wallId' => $wall,
            'screenId' => $screen,
        ]);
    }
}
