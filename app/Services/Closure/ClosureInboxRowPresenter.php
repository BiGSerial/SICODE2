<?php

namespace App\Services\Closure;

use App\Enum\ClosureOperationalStatus;
use App\Models\Closure\ClosureIssue;
use App\Support\Ops\Tone;
use Carbon\Carbon;

/**
 * Linha da caixa "Em acompanhamento" (Medição/Fiscalização) já pronta para a tela: textos em PT-BR e tons semânticos
 * (padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md). A view só desenha; a cor da linha continua sendo o `ClosureSignal` do Encerramento.
 */
class ClosureInboxRowPresenter
{
    /**
     * @param object      $order linha da `ClosureDispatchListQuery`
     * @param object|null $track `closure_order_states` da Ordem (data em que entrou no status usuário), quando já despachada
     *
     * @return array<string, mixed>
     */
    public function present(object $order, ?object $track = null): array
    {
        $letter = ClosureMetaClassifier::letter($order->meta_type);
        $signal = ClosureSignal::forRow($order);
        $since  = $track?->status_user_changed_at ? Carbon::parse($track->status_user_changed_at) : null;

        $holder = $order->assignee_name ?: ($order->holder_company_name ? 'Pilha de ' . $order->holder_company_name : null);

        return [
            'id'         => (int) $order->id,
            'noteId'     => (int) $order->note_id,
            'note'       => $order->note_number,
            'partialId'  => $order->partial_id ?: null,
            'ordem'      => $order->ordem,
            'competence' => ClosureMetaClassifier::competenceLabel($order->competence),
            'typeLetter' => $letter,
            'typeTone'   => Tone::forClosureType($letter),
            'typeLabel'  => ClosureMetaClassifier::label($order->meta_type),
            'work'       => $order->descricao ?: null,
            'city'       => $order->city_name ?: null,
            'planned'    => $order->custPlanejado,
            'actual'     => $order->custRealizado,
            'statusSap'  => $order->statusSist ?: null,
            'statusUser' => ClosureEligibleOrders::statusUserPrefix($order->statusUser) ?: null,
            'since'      => $since,
            'estimated'  => (bool) ($track?->status_user_estimated),
            'dispatched' => (bool) $order->state_id,
            'situation'  => $order->state_id ? [
                'label' => ClosureOperationalStatus::labelFor($order->operational_status),
                'tone'  => Tone::forClosureStatus($order->operational_status),
            ] : null,
            'holder'     => $holder,
            'signalKey'  => $signal['key'] ?? null,
            'signalHint' => $signal ? $signal['label'] . ': ' . $signal['hint'] : null,
        ];
    }

    /**
     * Linha da aba "Despachos para retorno": pendência que o Encerramento enviou e espera resposta da área.
     *
     * @return array<string, mixed>
     */
    public function issue(ClosureIssue $issue): array
    {
        [$label, $tone, $hint] = match ($issue->status) {
            ClosureIssue::STATUS_OPEN       => ['Aberta', 'blue', 'Aberta pelo Encerramento, ainda não enviada.'],
            ClosureIssue::STATUS_REQUESTED  => ['Aguardando resposta', 'amber', 'O Encerramento enviou e espera a resposta da área.'],
            ClosureIssue::STATUS_RESPONDED  => ['Respondida', 'teal', 'A área respondeu; o Encerramento valida.'],
            ClosureIssue::STATUS_VALIDATING => ['Em validação', 'blue', 'O Encerramento está conferindo a resposta.'],
            ClosureIssue::STATUS_RETURNED   => ['Devolvida', 'red', 'O Encerramento devolveu: a área precisa responder de novo.'],
            ClosureIssue::STATUS_RESOLVED   => ['Resolvida', 'green', 'Pendência resolvida.'],
            default                         => [(string) $issue->status, 'muted', ''],
        };

        $sentAt = $issue->requested_at ?? $issue->opened_at;

        return [
            'id'         => (int) $issue->id,
            'note'       => $issue->Order?->Note?->note,
            'ordem'      => $issue->Order?->ordem,
            'subject'    => $issue->Category?->name,
            'detail'     => $issue->Subcategory?->name,
            'statusText' => $label,
            'statusTone' => $tone,
            'statusHint' => $hint,
            'status'     => $issue->status,
            'sentAt'     => $sentAt,
            'sentDays'   => $sentAt ? (int) Carbon::parse($sentAt)->startOfDay()->diffInDays(Carbon::today(), true) : null,
        ];
    }
}
