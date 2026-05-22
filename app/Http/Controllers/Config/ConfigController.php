<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;

class ConfigController extends Controller
{
    public function systemStatus()
    {
        return view('config.system_status');
    }

    public function systemHistory()
    {
        return view('config.system_history');
    }

    public function systemSchedule()
    {
        return view('config.system_schedule');
    }

    public function main()
    {
        return redirect()->route('config.system.status');
    }

    public function services()
    {
        return view('config.services.main');
    }

    public function jobs_view()
    {
        return view('config.jobs');
    }

    public function adsRequestRecipients()
    {
        return view('config.ads_request_recipients');
    }
}
