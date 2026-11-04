<?php

namespace App\Services\Closure;

use App\Models\{Production, Service, WorkReportFlowProduction};
use App\Services\Payment\PaymentAccompanyRowPresenter;

/**
 * O que o usuário de Pagamento precisa ver numa Ordem que está no status dele no Encerramento: a Nota na fila de Pagamento
 * (status e responsável), o informe (empresa, Final/Parcial, Valor ADS) e o prazo de medição. Reaproveita a regra do
 * Acompanhamento de Pagamento (`PaymentAccompanyRowPresenter`): uma regra de prazo só, sem cópia.
 */
class ClosureInboxPaymentContext
{
    public function __construct(private PaymentAccompanyRowPresenter $presenter)
    {
    }

    /**
     * @param array<int, int> $noteIds
     *
     * @return array<int, array<string, mixed>> note_id => linha do Pagamento + `owner` (responsável na fila); notas sem Production ficam de fora
     */
    public function forNotes(array $noteIds): array
    {
        $noteIds = array_values(array_unique(array_filter($noteIds)));

        if ($noteIds === []) {
            return [];
        }

        $serviceUuids = Service::query()->where('folder', ClosureArea::serviceFolder(ClosureArea::MEASUREMENT))->pluck('uuid')->all();

        if ($serviceUuids === []) {
            return [];
        }

        // Uma Production por Nota: a ainda aberta tem prioridade; entre iguais, a mais recente.
        $productions = Production::query()
            ->whereIn('note_id', $noteIds)
            ->whereIn('service_id', $serviceUuids)
            ->orderByDesc('id')
            ->get()
            ->sortBy(fn (Production $production) => $production->completed ? 1 : 0)
            ->unique('note_id')
            ->values();

        if ($productions->isEmpty()) {
            return [];
        }

        $productions->load([
            'User:id,name',
            'Note.Adsform:id,work_report_id,note_id,amount,tacit,tacit_due_at,tacit_delivered_at,created_at',
            'Note.WorkForm:id,note_id,company_id,informer,date,informed_at,created_at,rejected,selected_final_scopes',
            'Note.WorkForm.Company:id,name,deleted_at',
            'Note.WorkForm.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.pep', 'orders.moaberto']),
            'Note.WorkForm.Orders.Pep:objeto,disponivel',
            'Note.WorkForm.Orders.Operations' => fn ($q) => $q->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
            'Note.WorkForm.Adsform:id,work_report_id,note_id,amount,tacit,tacit_due_at,tacit_delivered_at,created_at',
            'fiveNotes:five_notes.id,note_id,work_report_id,note_d5,is_supervisioned,is_completed,is_archived,is_payed,completed_at',
            'measurementContext.fiveNote',
            'Note.Partials.Company:id,name,deleted_at',
            'Note.Partials.Orders' => fn ($q) => $q->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.pep', 'orders.moaberto']),
            'Note.Partials.Orders.Pep:objeto,disponivel',
            'Note.Partials.Orders.Operations' => fn ($q) => $q->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
            'WorkReportFlowProductions'       => fn ($q) => $q
                ->where('stage', WorkReportFlowProduction::STAGE_PAYMENT)
                ->where('is_current', true)
                ->with([
                    'WorkReport:id,note_id,company_id,informer,date,informed_at,created_at,rejected,selected_final_scopes',
                    'WorkReport.Company:id,name,deleted_at',
                    'WorkReport.Orders' => fn ($orders) => $orders->select(['orders.id', 'orders.note_id', 'orders.ordem', 'orders.pep', 'orders.moaberto']),
                    'WorkReport.Orders.Pep:objeto,disponivel',
                    'WorkReport.Orders.Operations' => fn ($orders) => $orders->select(['id', 'order_id', 'operacao', 'status', 'cenTrab', 'fimReal']),
                    'WorkReport.Adsform:id,work_report_id,note_id,amount,tacit,tacit_due_at,tacit_delivered_at,created_at',
                ]),
        ]);

        $result = [];

        foreach ($productions as $production) {
            $row          = $this->presenter->present($production);
            $row['owner'] = $production->User?->name;
            $row['done']  = (bool) $production->completed;

            // Nota já concluída em Pagamento: o prazo deixou de correr, então não vira alerta vermelho.
            if ($row['done']) {
                $row['dueTone'] = 'muted';
                $row['dueHint'] = 'Pagamento já concluído esta Nota. ' . $row['dueHint'];
            }

            $result[(int) $production->note_id] = $row;
        }

        return $result;
    }
}
