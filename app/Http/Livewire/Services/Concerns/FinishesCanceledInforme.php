<?php

namespace App\Http\Livewire\Services\Concerns;

use App\Models\Production;
use App\Services\Production\CanceledInformeService;

/**
 * Fila do usuário (Serviços) com produção cujo informe foi cancelado: o único comando é FINALIZAR. Pede a confirmação (SweetAlert) dizendo
 * o que vai acontecer e, ao confirmar, o `CanceledInformeService` encerra a produção e registra FINALIZADO POR CANCELAMENTO na análise.
 *
 * O componente precisa de `$service` (Service), do listener `'confirm_finish_canceled' => 'finishCanceled'` e do evento `refresh_accomany`.
 */
trait FinishesCanceledInforme
{
    /** Produção que o usuário pediu para finalizar por cancelamento (confirmada no SweetAlert). */
    public $pendingCanceledProductionId = null;

    /** A produção é da fila do usuário, neste serviço? */
    private function ownsCanceledProduction($productionId): bool
    {
        return Production::query()
            ->whereKey($productionId)
            ->where('service_id', $this->service->uuid)
            ->where('user_id', auth()->id())
            ->exists();
    }

    public function askFinishCanceled($productionId): void
    {
        $production = Production::query()->with('Note:id,note')->find($productionId);
        $canceled   = $production && $this->ownsCanceledProduction($production->id) && !$production->completed
            ? app(CanceledInformeService::class)->forProduction($production)
            : null;

        if (!$canceled) {
            $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'info', 'title' => 'Nada a finalizar', 'html' => 'Esta atividade não tem informe cancelado ou já foi encerrada.']);

            return;
        }

        $this->pendingCanceledProductionId = $production->id;

        $by   = $canceled['canceled_by'] ? ' por <strong>' . e($canceled['canceled_by']) . '</strong>' : '';
        $when = $canceled['canceled_at'] ? ' em <strong>' . e($canceled['canceled_at']) . '</strong>' : '';

        $this->dispatchBrowserEvent('alertar', [
            'title' => 'FINALIZAR POR CANCELAMENTO',
            'msg'   => 'O informe #' . (int) $canceled['id'] . ' da Nota/OV <strong>' . e($production->Note?->note) . '</strong> foi cancelado' . $by . $when . '.<br><br>
                Ao confirmar, o sistema <strong>encerra esta atividade</strong>, registra na análise
                <strong>' . CanceledInformeService::CONCLUSION . '</strong> e descreve nela quem cancelou o informe e quando.
                Nada mais precisa ser executado e esta operação não poderá ser desfeita.
                <h4 class="text-center mt-3">DESEJA FINALIZAR?</h4>',
            'icon'          => 'warning',
            'btnOktxt'      => 'Sim, finalizar',
            'btnCanceltxt'  => 'Não, cancele',
            'action'        => 'confirm_finish_canceled',
            'cancel_titulo' => 'Cancelado!',
            'cancel_msg'    => 'Nada foi alterado.',
        ]);
    }

    public function finishCanceled(): void
    {
        $production                        = $this->pendingCanceledProductionId ? Production::find($this->pendingCanceledProductionId) : null;
        $this->pendingCanceledProductionId = null;

        if (!$production || !$this->ownsCanceledProduction($production->id)) {
            return;
        }

        try {
            app(CanceledInformeService::class)->finish($production, auth()->user());
        } catch (\DomainException $e) {
            $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'info', 'title' => 'Nada a finalizar', 'html' => e($e->getMessage())]);

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'error', 'title' => 'NÃO FINALIZADO', 'html' => 'Não conseguimos finalizar a atividade. Tente novamente ou fale com o suporte.']);

            return;
        }

        $this->dispatchBrowserEvent('swal', ['position' => 'center', 'icon' => 'success', 'title' => 'Atividade finalizada', 'html' => 'Registrado na análise: ' . CanceledInformeService::CONCLUSION . '.', 'timer' => 4000]);
        $this->emit('refresh_accomany');
    }
}
