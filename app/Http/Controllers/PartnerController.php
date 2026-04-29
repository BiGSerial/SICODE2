<?php

namespace App\Http\Controllers;

class PartnerController extends Controller
{
    public function main()
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
