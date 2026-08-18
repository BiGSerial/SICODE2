<?php

namespace App\Http\Controllers;

use App\Services\PartnerAccess\PartnerAccessGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function main(Request $request): RedirectResponse|View
    {
        foreach ($this->landingRoutes() as $routeName => $permissionKey) {
            if (PartnerAccessGate::allows($request->user(), $permissionKey)) {
                if ($routeName === 'partner.main.viability') {
                    return view('partner.main');
                }

                return redirect()->route($routeName);
            }
        }

        abort(403);
    }

    private function landingRoutes(): array
    {
        return [
            'partner.main.viability' => 'viability.dashboard',
            'partner.todo.viability' => 'viability.list',
            'partner.rejected.viability' => 'viability.rejected',
            'partner.tacit.viability' => 'viability.tacit',
            'partner.hist.viability' => 'viability.history',
            'partner.report.workreport' => 'conclusion_reports.create',
            'partner.report.rejectedWorked' => 'conclusion_reports.rejected',
            'partner.report.sendAdsForm' => 'conclusion_reports.ads_delivery',
            'partner.ads.requests' => 'conclusion_reports.ads_requests',
            'partner.report.workedlist' => 'conclusion_reports.list',
            'partner.declared.equipment' => 'conclusion_reports.equipment',
            'partner.report.partial' => 'partial_reports.create',
            'partner.report.partiallist' => 'partial_reports.list',
            'protests.partner.main' => 'complaints.index',
            'protests.partner.history' => 'complaints.history',
            'partner.note_d5.list' => 'd5_notes.list',
            'partner.note_d5.returned' => 'd5_notes.returned',
            'partner.note_d5.historic' => 'd5_notes.history',
            'partner.admin.users' => 'admin_users.view',
        ];
    }

    public function dashboard(): View
    {
        return view('partner.main');
    }

    public function searchNotes()
    {
        return view('partner.search_notes');
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

    public function reinformWorkreport(string $token)
    {
        return view('partner.reinform_workreport', ['token' => $token]);
    }

    public function rejectedViabList()
    {
        return view('partner.rejected_list');
    }

    public function tacitViabList()
    {
        return view('partner.tacit_list');
    }

    public function declaredEquipment()
    {
        return view('partner.workequipment');
    }

    public function partialreport()
    {
        return view('partner.partialform');
    }

    public function partialreportlist()
    {
        return view('partner.partial_list');
    }

    public function sendAdsForm()
    {
        return view('partner.adsform');
    }

    public function adsRequests()
    {
        return view('partner.ads_requests');
    }

    // D5
    public function partner_d5_list()
    {
        return view('partner.fiveNotes.list');
    }

    public function partner_d5_returned()
    {
        return view('partner.fiveNotes.returned');
    }

    public function partner_d5_historic()
    {
        return view('partner.fiveNotes.historic');
    }
}
