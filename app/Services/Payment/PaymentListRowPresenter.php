<?php

namespace App\Services\Payment;

use App\Helpers\DaysLeft;
use App\Models\Note;
use App\Support\Ops\{Deadline, RowState};
use Carbon\Carbon;

/**
 * Monta, para a Lista para Pagamento do despacho, tudo o que uma linha mostra, já com tom semântico (padrão:
 * docs/PADRAO_LISTAS_OPERACIONAIS.md). A view só desenha; regra de prazo/cor mora aqui e em `App\Support\Ops`.
 *
 * `$state` é o resultado de `Main::rowDispatchState()` (avaliação de bloqueio + pilha da empresa).
 */
class PaymentListRowPresenter
{
    public function __construct(private readonly PaymentAccompanyRowPresenter $accompany)
    {
    }

    /** Prazo para entrega da fiscalização de uma Parcial (dias após `supervision_at`). */
    public const PARTIAL_SUPERVISION_DAYS = 5;

    /**
     * @param string $profile `dispatch` (Despacho): D5 por ciclo e payload com a D5.
     *                        `partner` (parceira): valor é o MOA aberto, D5 só azul/verde e payload sem a D5.
     *                        `service` (Serviços, auto-atribuição): valor da ADS e D5 só azul/verde.
     */
    public function present(Note $list, array $state, string $profile = 'dispatch'): array
    {
        $partner  = $profile === 'partner';
        $simpleD5 = $profile !== 'dispatch';
        $eval     = $state['eval'];
        $stack    = $state['stack'];

        $workForms = $list->relationLoaded('WorkForms') ? $list->WorkForms : collect([$list->WorkForm])->filter();
        $wf        = $workForms->first() ?? $list->WorkForm;

        $workReportId = (int) ($list->payment_work_report_id ?? ($profile === 'service' ? $wf?->id : 0) ?? 0);
        $fiveNoteId   = (int) ($list->dispatch_five_note_id ?? 0);
        $partialId    = (int) ($list->dispatch_partial_id ?? 0);

        $partial = !$wf
            ? ($partialId > 0 ? ($list->Partials->firstWhere('id', $partialId) ?? $list->Partials->first()) : $list->Partials->first())
            : null;

        $orders = $wf
            ? $workForms->flatMap(fn ($workForm) => $workForm->Orders ?? collect())
            : ($partial ? $partial->Orders ?? collect() : collect());

        $production = $stack ?: ($eval['production'] ?? null);
        $rowState   = $stack ? RowState::FREE : RowState::fromBlock((int) $eval['block']);
        $reason     = $stack ? 'Disponível na pilha da empresa para atribuição individual.' : ($eval['reason'] ?? null);

        $ads    = $wf?->Adsform;
        $adsAt  = $ads?->created_at;
        $tacit  = (bool) ($ads?->tacit ?? false);
        $adsAge = $adsAt
            ? (int) $adsAt->diffInDays(Carbon::now(), true)
            : ($partial ? (int) $partial->created_at->diffInDays(Carbon::now(), true) : null);

        $fiscalAt = $partial ? $partial->supervision_at?->copy()->addDays(self::PARTIAL_SUPERVISION_DAYS) : ($list->fimLancado ? Carbon::parse($list->fimLancado) : null);

        $dueDays   = new DaysLeft($list);
        $dueLabel  = $dueDays->getLastDate();
        $dueDate   = $dueLabel === '---' ? null : Carbon::createFromFormat('d/m/Y', $dueLabel)->startOfDay();
        $dueRemain = $dueDate ? $dueDays->getDaysLeft() : null;
        $dueTone   = Deadline::remaining($dueRemain);
        $dueHint   = Deadline::remainingText($dueRemain) . ' (verde acima de 15 dias, âmbar até 15, vermelho vencido)';

        // Medição (Serviços): o mesmo prazo do Acompanhamento. Parcial: 8 dias úteis da aprovação do engenheiro; Final: da entrega da ADS.
        if ($profile === 'service') {
            $adsAt     = $partial ? $partial->created_at : ($ads ? ($tacit ? $ads->tacit_delivered_at : $ads->created_at) : null);
            $adsAge    = $adsAt ? (int) Carbon::parse($adsAt)->diffInDays(Carbon::now(), true) : null;
            $deadline  = $this->accompany->deadline($partial, $partial ? null : $adsAt, $tacit);
            $dueDate   = $deadline['dueAt'];
            $dueRemain = $deadline['days'];
            $dueTone   = $deadline['tone'];
            $dueHint   = $deadline['hint'];
        }

        $informedAt = $wf
            ? ($wf->informed_at ?: $wf->created_at)
            : $partial?->created_at;

        $assignee = $production?->User?->name ?: $production?->Company?->name;
        $parts    = $assignee ? explode(' ', $assignee) : [];
        $assignee = $parts ? (count($parts) > 1 ? $parts[0] . ' ' . end($parts) : $parts[0]) : null;

        return [
            'key'       => (string) ($list->payment_context_key ?? $list->id),
            'noteId'    => (int) $list->id,
            'note'      => $list->note,
            'isOv'      => (int) $list->type_note === 2,
            'isPartial' => (bool) $partial,
            'scopes'    => (!$partial && $wf) ? collect($wf->finalScopeBadges())->map(fn ($b) => [
                'label' => $b['label'],
                'tone'  => match ($b['class']) {
                    'text-bg-primary' => 'blue',
                    'text-bg-warning' => 'amber',
                    default           => 'muted',
                },
            ])->all() : [],
            'd5'           => $this->d5($partial ? null : $list->FiveNote),
            'workReportId' => $workReportId ?: null,
            'partialId'    => $partialId ?: null,
            'orders'       => $orders->map(fn ($order) => $this->order($order))->values()->all(),
            'adsAmount'    => $partial ? $partial->value : $ads?->amount,
            'company'      => $wf ? $wf->Company?->name : $partial?->Company?->name,
            'city'         => $list->lexp,
            'finalOp20'    => $this->finalOp20($orders),
            'informedAt'   => $informedAt,
            'adsAt'        => $adsAt,
            'adsAge'       => $adsAge,
            'adsTone'      => $adsAt || $partial ? Deadline::age($adsAge) : 'muted',
            'fiscalAt'     => $fiscalAt,
            // Só a Parcial tem prazo de fiscalização (5 dias); o Informe Final mostra a data sem cor.
            'fiscalTone'   => $partial && $fiscalAt ? Deadline::remaining(Deadline::daysFromNow($fiscalAt), self::PARTIAL_SUPERVISION_DAYS - 1) : 'muted',
            'dueAt'        => $dueDate,
            'dueDays'      => $dueRemain,
            'dueTone'      => $dueTone,
            'dueHint'      => $dueHint,
            'productionId' => $production?->id,
            'state'        => $rowState,
            'reason'       => $reason,
            'command'      => (bool) ($eval['command'] ?? false),
            'canDispatch'  => (bool) $state['canDispatch'],
            'stackOpen'    => (bool) $stack,
            'assignee'     => $assignee,
            'payload'      => $workReportId
                ? [['note_id' => (int) $list->id, 'work_report_id' => $workReportId] + ($partner ? [] : ['five_note_id' => $fiveNoteId ?: null])]
                : [(int) $list->id],
        ];
    }

    private function d5($five, bool $partner = false): ?array
    {
        if (!$five || (!$partner && $five->is_archived)) {
            return null;
        }

        if ($partner) {
            return ($five->is_supervisioned ?? false)
                ? ['tone' => 'green', 'hint' => 'D5 fiscalizada – liberar carta']
                : ['tone' => 'blue', 'hint' => 'Gerar D5 e reter carta'];
        }

        return match (true) {
            (bool) ($five->is_supervisioned ?? false) => ['tone' => 'green', 'hint' => 'D5 fiscalizada – liberar carta'],
            $five->isAwaitingPaymentCreation()        => ['tone' => 'blue', 'hint' => 'Gerar D5 e reter carta'],
            default                                   => ['tone' => 'muted', 'hint' => 'D5 já despachada – aguardando fiscalização'],
        };
    }

    private function order($order): array
    {
        $operations = $order->Operations ?? collect();
        $status     = fn (string $op) => ($match = $operations->firstWhere('operacao', $op)) && isset($match->status)
            ? explode(' ', $match->status)[0]
            : null;
        $center = $operations->firstWhere('operacao', '0010');
        $pep    = $order->Pep?->disponivel;

        return [
            'ordem'  => $order->ordem,
            'op30'   => $status('0030'),
            'op40'   => $status('0040'),
            'op50'   => $status('0050'),
            'center' => $center && isset($center->cenTrab) ? explode(' ', $center->cenTrab)[0] : null,
            'pep'    => $order->pep,
            'saldo'  => $pep === null ? null : (float) $pep,
        ];
    }

    private function finalOp20($orders): ?Carbon
    {
        $date = $orders
            ->flatMap(fn ($order) => $order->Operations ?? collect())
            ->where('operacao', '0020')
            ->pluck('fimReal')
            ->filter()
            ->sort()
            ->first();

        return $date ? Carbon::parse($date) : null;
    }
}
