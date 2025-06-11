<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ConstructionController extends Controller
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

    public function returned(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.' . $service->folder . '.returned', [
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

    public function waiting(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.' . $service->folder . '.waitinglist', [
            'service' => $service,
        ]);
    }

    public function lookatnotes(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.' . $service->folder . '.lookatnotes', [
            'service' => $service,
        ]);
    }


    // REDIRECIONADOR RESPONSER CONSTRUÇÃO
    public function responser_main()
    {
        return view('construction.responser.main');
    }



}
