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

    public function hiringsurvey(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.levantamento.historicviab', [
            'service' => $service,
        ]);
    }

    public function waiting_return(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.oexterno.waiting-return', [
            'service' => $service,
        ]);
    }

    public function protocolNote(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.' . $service->folder . '.noteprotocol', [
            'service' => $service,
            'note' => $request->route('note'),
        ]);
    }

    // Reclamações
    public function protests_list(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.' . $service->folder . '.protests.list', [
            'service' => $service,
        ]);
    }

    public function protests_closed(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.' . $service->folder . '.protests.closed', [
            'service' => $service,
        ]);
    }

    public function protests_view(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.' . $service->folder . '.protests.view', [
            'service' => $service,
        ]);
    }
}
