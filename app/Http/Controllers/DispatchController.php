<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    public function survey_main(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (view()->exists('dispatchs.' . $service->folder . '.main')) {

            if (Auth()->User()->contract) {
                return view('dispatchs.' . $service->folder . '.main', [
                    'service' => $service,
                ]);
            } else {
                return view('dispatchs.' . $service->folder . '.main', [
                    'service' => $service,
                ]);
            }
        } else {

            if (Auth()->User()->contract) {
                return view('dispatchs.default.main', [
                    'service' => $service,
                ]);
            } else {
                return view('dispatchs.default.main', [
                    'service' => $service,
                ]);
            }
        }
    }

    public function survey_stack(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (view()->exists('dispatchs.' . $service->folder . '.stack')) {
            return view('dispatchs.' . $service->folder . '.stack', [
                'service' => $service,
            ]);
        } else {
            // Se a view 'dispatchs.survey.stack' não existir, use uma view alternativa
            return view('dispatchs.default.stack', [
                'service' => $service,
            ]);
        }
    }

    public function survey_transfer(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (view()->exists('dispatchs.' . $service->folder . '.transprod')) {
            return view('dispatchs.' . $service->folder . '.transprod', [
                'service' => $service,
            ]);
        } else {
            // Se a view 'dispatchs.survey.stack' não existir, use uma view alternativa
            return view('dispatchs.default.stack', [
                'service' => $service,
            ]);
        }
    }

    public function returnD5(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('dispatchs.returned', [
            'service' => $service,
        ]);
    }

    public function survey_map(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('dispatchs.levantamento.map_info', [
            'service' => $service,
        ]);
    }
}
