<?php

namespace App\Http\Controllers;

class LegalController extends Controller
{
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

    public function fieldQueue()
    {
        return view('legal.field.assignments');
    }

    public function fieldResponse(int $assignment_id)
    {
        return view('legal.field.response', compact('assignment_id'));
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
