<?php

namespace App\Http\Controllers;

use App\Models\Production;
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

    public function production(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->firstOrFail();
        $production = Production::query()
            ->where('id', $request->route('prod'))
            ->where('service_id', $service->uuid)
            ->firstOrFail();

        // Rota dedicada para abrir uma produção específica a partir de notificações.
        // Para Desenho, redireciona para a fila principal com abertura direta do chat/view.
        if ($service->folder === 'desenho') {
            return redirect()->route('services.main', [
                'service' => $service->uuid,
                'open_project_review' => 1,
                'production' => $production->id,
                'note' => $production->note_id,
            ]);
        }

        return redirect()->route('services.main', ['service' => $service->uuid]);
    }

    public function historic(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.' . $service->folder . '.historic', [
            'service' => $service,
        ]);
    }

    public function waiting_d5_create(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.pagamento.pending-d5-create', [
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

    public function adsRequests(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.' . $service->folder . '.ads_requests', [
            'service' => $service,
        ]);
    }

    public function cancellation_exec_queue(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (!$service || $service->folder !== 'pagamento') {
            abort(403, 'Recurso não implementado.');
        }

        return view('services.pagamento.cancellation-exec-queue', [
            'service' => $service,
        ]);
    }

    public function cancellation_exec_ongoing(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (!$service || $service->folder !== 'pagamento') {
            abort(403, 'Recurso não implementado.');
        }

        return view('services.pagamento.cancellation-exec-ongoing', [
            'service' => $service,
        ]);
    }

    public function cancellation_exec_history(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (!$service || $service->folder !== 'pagamento') {
            abort(403, 'Recurso não implementado.');
        }

        return view('services.pagamento.cancellation-exec-history', [
            'service' => $service,
        ]);
    }

    public function cancellation_exec_show(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (!$service || $service->folder !== 'pagamento') {
            abort(403, 'Recurso não implementado.');
        }

        return view('services.pagamento.cancellation-exec-show', [
            'service' => $service,
            'request' => $request->route('request'),
        ]);
    }

    public function cancellation_exec_bulk(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        if (!$service || $service->folder !== 'pagamento') {
            abort(403, 'Recurso não implementado.');
        }

        return view('services.pagamento.cancellation-exec-bulk', [
            'service' => $service,
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



    // Orgao Externo
    public function oexterno_undefined(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.oexterno.undefined', [
            'service' => $service,
        ]);
    }

    public function oexterno_waiting_payment(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.oexterno.waitingPayment', [
            'service' => $service,
        ]);
    }

    public function oexterno_waiting_orgao(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.oexterno.waitingOE', [
            'service' => $service,
        ]);
    }

    public function oexterno_waiting_taxa(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.oexterno.waitingTax', [
            'service' => $service,
        ]);
    }

    public function oexterno_dashboard(Request $request)
    {
        $service = Service::where('uuid', $request->route('service'))->first();

        return view('services.oexterno.dashboard', [
            'service' => $service,
        ]);
    }
}
