<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

    public function survey_stack2(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (view()->exists('dispatchs.' . $service->folder . '.stack2')) {
            return view('dispatchs.' . $service->folder . '.stack2', [
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

    public function dashboard(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (view()->exists('dispatchs.' . $service->folder . '.dashboard')) {

            return view('dispatchs.' . $service->folder . '.dashboard', [
                'service' => $service,
            ]);
        } else {
            abort(403, 'Recurso não implementado.');
        }

    }

    public function waitingFiveNote(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (view()->exists('dispatchs.' . $service->folder . '.waitingFiveNote')) {

            return view('dispatchs.' . $service->folder . '.waitingFiveNote', [
                'service' => $service,
            ]);
        } else {
            abort(403, 'Recurso não implementado.');
        }

    }

    public function adsRequests(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('dispatchs.ads_requests', [
            'service' => $service,
        ]);
    }

    public function cancellationQueue(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (view()->exists('dispatchs.' . $service->folder . '.cancellation-queue')) {
            return view('dispatchs.' . $service->folder . '.cancellation-queue', [
                'service' => $service,
            ]);
        }

        abort(403, 'Recurso não implementado.');
    }

    public function cancellationShow(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (view()->exists('dispatchs.' . $service->folder . '.cancellation-show')) {
            return view('dispatchs.' . $service->folder . '.cancellation-show', [
                'service' => $service,
                'request' => $request->route('request'),
            ]);
        }

        abort(403, 'Recurso não implementado.');
    }

    public function cancellationHistory(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (view()->exists('dispatchs.' . $service->folder . '.cancellation-history')) {
            return view('dispatchs.' . $service->folder . '.cancellation-history', [
                'service' => $service,
            ]);
        }

        abort(403, 'Recurso não implementado.');
    }

    public function cancellationCategories(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (view()->exists('dispatchs.' . $service->folder . '.cancellation-categories')) {
            return view('dispatchs.' . $service->folder . '.cancellation-categories', [
                'service' => $service,
            ]);
        }

        abort(403, 'Recurso não implementado.');
    }
}
