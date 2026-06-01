<?php

namespace App\Http\Controllers;

class LegalController extends Controller
{
    private function ensureSubdemandsFeatureEnabled(): void
    {
        abort_unless(config('features.legal_subdemands', true), 404);
    }

    public function queue()
    {
        return view('legal.controller.queue');
    }

    public function triage()
    {
        return view('legal.controller.triage');
    }

    public function demandDetail(string $uuid)
    {
        return view('legal.controller.detail', compact('uuid'));
    }

    public function subdemandMonitor()
    {
        $this->ensureSubdemandsFeatureEnabled();
        return view('legal.controller.subdemand-monitor');
    }

    public function subdemandDetail(int $id)
    {
        $this->ensureSubdemandsFeatureEnabled();
        return view('legal.controller.subdemand-detail', compact('id'));
    }

    public function fieldQueue()
    {
        return view('legal.field.assignments');
    }

    public function fieldResponse(int $assignment_id)
    {
        return view('legal.field.response', compact('assignment_id'));
    }

    public function fieldResponseExternal(int $assignment_id)
    {
        return view('legal.field.response_external', compact('assignment_id'));
    }

    public function externalExpired()
    {
        return view('legal.field.response_external_expired');
    }

    public function subdemandResponseExternal(string $token)
    {
        $this->ensureSubdemandsFeatureEnabled();
        return view('legal.field.subdemand_external_response', compact('token'));
    }

    public function dashboard()
    {
        return view('legal.management.dashboard');
    }

    public function cases()
    {
        return view('legal.management.cases');
    }

    public function reports()
    {
        return view('legal.management.reports');
    }
}
