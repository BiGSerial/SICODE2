<?php

namespace App\Services\Payment;

use App\Custom\Notestatus;
use App\Models\{Production, WorkReportFlowProduction};
use App\Services\D5\ProductionFiveNoteResolver;
use App\Support\Ops\{Deadline, Tone};
use App\Support\SicodeRules;
use Carbon\Carbon;

/**
 * Linha do Acompanhamento de Pagamento (Serviços): tudo que a tela mostra, já com tom semântico (padrão:
 * docs/PADRAO_LISTAS_OPERACIONAIS.md). A view só desenha.
 *
 * Prazo de medição, contado em dias inteiros (a hora é ignorada). Quantidade e tipo de dia (úteis ou corridos) vêm da região
 * (`SicodeRules::paymentAccompanyDeadline`, config/sicode.php); hoje 8 dias úteis em ES e SP:
 *  - Parcial: conta da aprovação do engenheiro (`partials.decision_at`);
 *  - Final: conta da entrega da ADS do informe (`tacit_delivered_at` na ADS tácita; senão a criação da ADS).
 * Verde com mais de 1 dia, âmbar de 1 a 0 dias (até o dia do vencimento), vermelho vencido (mesma faixa do Controle de Pagamento).
 */
class PaymentAccompanyRowPresenter
{
    /** Dias restantes até onde a cor passa a âmbar. */
    public const WARN_UNTIL = 1;

    public function __construct(
        private readonly MeasurementDeadlinePolicy $policy,
        private readonly ProductionFiveNoteResolver $fiveNotes,
    ) {
    }

    public function present(Production $list, array $legalTags = [], bool $blockedByWaiting = false): array
    {
        $note    = $list->Note;
        $partial = $list->partial ? $note?->Partials?->last() : null;

        $flowReport = $list->WorkReportFlowProductions
            ->firstWhere('stage', WorkReportFlowProduction::STAGE_PAYMENT)
            ?->WorkReport;
        $workForm = $flowReport ?? $note?->WorkForm;

        $orders = $workForm && !$partial
            ? ($workForm->Orders ?? collect())
            : ($partial ? ($partial->Orders ?? collect()) : collect());

        $ads      = $workForm?->Adsform ?? $note?->Adsform;
        $tacit    = (bool) ($ads?->tacit ?? false);
        $adsAt    = $ads ? ($tacit ? $ads->tacit_delivered_at : $ads->created_at) : null;
        $five     = $this->fiveNotes->resolve($list);
        $deadline = $this->deadline($partial, $adsAt, $tacit);
        $adsDate  = $partial ? $partial->created_at : $adsAt;
        $adsDays  = $adsDate ? (int) Carbon::parse($adsDate)->startOfDay()->diffInDays(Carbon::today(), true) : null;

        $informant = trim((string) ($partial ? $partial->responsible : $workForm?->informer));
        $parts     = $informant !== '' ? preg_split('/\s+/', $informant) : [];

        $isConnection = $workForm
            ? collect($workForm->finalScopeBadges())->pluck('scope')->contains('connection')
            : false;

        return [
            'id'        => (int) $list->id,
            'noteId'    => (int) $list->note_id,
            'note'      => $note?->note,
            'priority'  => (bool) $list->priority,
            'isPartial' => (bool) $list->partial,
            'scopeTone' => $list->partial ? 'amber' : ($isConnection ? 'amber' : 'green'),
            'scopeText' => $list->partial ? 'Parcial' : 'Final',
            'scopeHint' => $list->partial ? 'Informe parcial' : ($isConnection ? 'Informe final de ligação' : 'Informe final'),
            'detail'    => $partial
                ? ['kind' => 'partial', 'id' => $partial->id]
                : ($workForm ? ['kind' => 'workform', 'id' => $workForm->id] : null),
            'd5'          => $this->d5($five),
            'five'        => $five?->id,
            'files'       => $note?->Files,
            'legal'       => $legalTags,
            'tacit'       => $tacit,
            'tacitSent'   => $tacit && (bool) $ads?->tacit_delivered_at,
            'orders'      => $orders->map(fn ($order) => $this->order($order))->values()->all(),
            'adsAmount'   => $partial ? $partial->value : $ads?->amount,
            'company'     => $workForm ? $workForm->Company?->name : $partial?->Company?->name,
            'informant'   => count($parts) > 1 ? $parts[0] . ' ' . end($parts) : ($parts[0] ?? null),
            'city'        => $note?->lexp,
            'executedAt'  => $workForm?->date ? Carbon::parse($workForm->date) : null,
            'informedAt'  => $workForm ? $workForm->informed_at : $partial?->supervision_at,
            'adsAt'       => $adsDate,
            'adsLabel'    => $adsDays === null ? null : $adsDays . ($adsDays === 1 ? ' dia' : ' dias'),
            'dueAt'       => $deadline['dueAt'],
            'dueDays'     => $deadline['days'],
            'dueTone'     => $deadline['tone'],
            'dueLabel'    => $this->dueLabel($deadline['days'], $deadline['unit']),
            'dueHint'     => $deadline['hint'],
            'status'      => (int) $list->status,
            'statusLabel' => Notestatus::status($list->status)->status,
            'statusTone'  => Tone::forProductionStatus((int) $list->status),
            'blocked'     => (bool) $list->block,
            'canAct'      => !$list->block && !$blockedByWaiting && !$list->completed,
            'canReturn'   => (bool) ($list->partial && !$workForm),
        ];
    }

    /**
     * Prazo de medição (também usado pela Lista para Medição): Parcial conta da aprovação do engenheiro, Final da entrega da ADS.
     *
     * @return array{dueAt: ?Carbon, days: ?int, tone: string, hint: string, unit: string}
     */
    public function deadline($partial, $adsAt, bool $tacit): array
    {
        $config   = SicodeRules::paymentAccompanyDeadline($partial ? 'partial' : 'final');
        $business = $config['business_days'];
        $unit     = $business ? 'úteis' : 'corridos';
        $rule     = $config['days'] . ' dias ' . $unit;

        if ($partial) {
            $base  = $partial->decision_at;
            $basis = 'aprovação do engenheiro';
        } else {
            $base  = $adsAt;
            $basis = 'entrega da ADS do informe';
        }

        if (!$base) {
            return [
                'dueAt' => null,
                'days'  => null,
                'tone'  => 'muted',
                'unit'  => $unit,
                'hint'  => !$partial && $tacit
                    ? 'ADS tácita ainda não entregue: o prazo (' . $rule . ' após a entrega da ADS) começa quando ela chegar'
                    : 'Sem data de ' . $basis . ': o prazo (' . $rule . ') não começou',
            ];
        }

        $dueAt = $this->policy->dueAtIn(Carbon::parse($base), $config['days'], $business);
        $days  = $this->policy->remainingIn($dueAt, $business);

        return [
            'dueAt' => $dueAt,
            'days'  => $days,
            'tone'  => Deadline::remaining($days, self::WARN_UNTIL),
            'unit'  => $unit,
            'hint'  => $this->remainingText($days, $business) . ' · ' . $rule . ' após a ' . $basis . ' (' . Carbon::parse($base)->format('d/m/Y') . '); verde acima de ' . self::WARN_UNTIL . ' dias, âmbar nos últimos ' . self::WARN_UNTIL . ', vermelho vencido',
        ];
    }

    private function d5($five): ?array
    {
        if (!$five) {
            return null;
        }

        return $five->is_supervisioned
            ? ['tone' => 'green', 'hint' => 'D5 fiscalizada: liberar carta']
            : ['tone' => 'blue', 'hint' => 'Gerar D5 e reter carta'];
    }

    private function order($order): array
    {
        $operations = $order->Operations ?? collect();
        $status     = fn (string $op) => ($match = $operations->firstWhere('operacao', $op)) && !empty($match->status)
            ? explode(' ', $match->status)[0]
            : null;
        $pep = $order->Pep?->disponivel;

        return [
            'ordem' => $order->ordem,
            'op30'  => $status('0030'),
            'op40'  => $status('0040'),
            'op50'  => $status('0050'),
            'pep'   => $order->pep,
            'saldo' => $pep === null ? null : (float) $pep,
        ];
    }

    public function dueLabel(?int $days, string $unit): ?string
    {
        $business = $unit === 'úteis';

        return match (true) {
            $days === null => null,
            $days < 0      => abs($days) . ($business ? (abs($days) === 1 ? ' dia útil vencido' : ' dias úteis vencidos') : (abs($days) === 1 ? ' dia vencido' : ' dias vencidos')),
            default        => $days . ($business ? ' d úteis' : ' d'),
        };
    }

    private function remainingText(int $days, bool $business): string
    {
        $abs  = abs($days);
        $unit = $business ? ($abs === 1 ? 'dia útil' : 'dias úteis') : ($abs === 1 ? 'dia' : 'dias');

        return match (true) {
            $days === 0 => 'Vence hoje',
            $days > 0   => 'Vence em ' . $abs . ' ' . $unit,
            default     => 'Venceu há ' . $abs . ' ' . $unit,
        };
    }
}
