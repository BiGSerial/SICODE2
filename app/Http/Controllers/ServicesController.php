<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    public function main(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.' . $service->folder . '.main', [
            'service' => $service,
        ]);
    }

    public function accompany(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.' . $service->folder . '.accompany', [
            'service' => $service,
        ]);
    }

    public function historic(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.' . $service->folder . '.historic', [
            'service' => $service,
        ]);
    }

    public function waiting_list(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.waitinglist', [
            'service' => $service,
        ]);
    }
}
