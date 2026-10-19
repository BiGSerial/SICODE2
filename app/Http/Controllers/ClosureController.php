<?php

namespace App\Http\Controllers;

use App\Services\Closure\ClosureServiceLocator;
use Illuminate\Http\RedirectResponse;

class ClosureController extends Controller
{
    public function overview()
    {
        return view('closure.overview');
    }

    public function meta()
    {
        return view('closure.meta');
    }

    public function passive()
    {
        return view('closure.passive');
    }

    /*
     * Rotas legadas (`closure.dispatch`, `closure.work`...): o Encerramento agora usa as telas
     * padrão do Service (dispatch.* / services.*). Mantidas só como redirect 302 para não
     * quebrar links já enviados em notificações e documentos.
     */
    public function dispatch(): RedirectResponse
    {
        return $this->legacyRedirect('dispatch.main');
    }

    public function dispatchDashboard(): RedirectResponse
    {
        return $this->legacyRedirect('dispatch.dashboard');
    }

    public function availableWork(): RedirectResponse
    {
        return $this->legacyRedirect('services.main', ['aba' => 'disponiveis']);
    }

    public function myWork(): RedirectResponse
    {
        return $this->legacyRedirect('services.accompany');
    }

    private function legacyRedirect(string $route, array $query = []): RedirectResponse
    {
        $url = ClosureServiceLocator::url($route, $query);

        abort_unless($url, 404, 'Serviço de Encerramento não encontrado.');

        return redirect()->to($url);
    }

    public function workShow(int $note)
    {
        return view('closure.work-show', ['noteId' => $note]);
    }

    public function orderDetail(int $order)
    {
        return view('closure.order-detail', ['orderId' => $order]);
    }
}
