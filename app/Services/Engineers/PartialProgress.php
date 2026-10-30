<?php

namespace App\Services\Engineers;

use App\Models\Partial;

/**
 * Posição de um informe parcial no caminho Atividade → Aprovação → Fiscalização → Medição → Finalizado.
 *
 * Regras (as mesmas dos status da lista e do histórico):
 * - Atividade: sempre concluída (o envio da empreiteira).
 * - Aprovação: concluída com `allow`; atual enquanto não houve decisão.
 * - Fiscalização: atual com `allow` sem `supervision`; concluída com `supervision`.
 * - Medição: atual com `supervision` sem `payment`; concluída com `payment`.
 * - Finalizado: concluído com `complete`; atual com `payment` ainda sem `complete`.
 * - Rejeitada (`deny`): a etapa onde parou fica `rejected` e as seguintes `todo`.
 */
class PartialProgress
{
    public const DONE = 'done';

    public const CURRENT = 'current';

    public const TODO = 'todo';

    public const REJECTED = 'rejected';

    /**
     * @return array<int, array{key: string, label: string, state: string, at: ?\Carbon\Carbon, by: ?string}>
     */
    public static function steps(Partial $partial): array
    {
        $rejectedAt = self::rejectedStage($partial);
        $denied     = (bool) $partial->deny;

        $state = function (string $stage, bool $done, bool $current) use ($rejectedAt, $denied): string {
            return match (true) {
                $rejectedAt === $stage => self::REJECTED,
                $denied                => $done ? self::DONE : self::TODO,
                $done                  => self::DONE,
                $current               => self::CURRENT,
                default                => self::TODO,
            };
        };

        $allow       = (bool) $partial->allow;
        $supervision = (bool) $partial->supervision;
        $payment     = (bool) $partial->payment;

        return [
            ['key' => 'activity', 'label' => 'Atividade', 'state' => self::DONE, 'at' => $partial->created_at, 'by' => $partial->user?->name],
            ['key' => 'approval', 'label' => 'Aprovação', 'state' => $state('approval', $allow, !$allow), 'at' => $partial->decision_at, 'by' => $partial->engineer?->name],
            ['key' => 'supervision', 'label' => 'Fiscalização', 'state' => $state('supervision', $supervision, $allow && !$supervision), 'at' => $partial->supervision_at, 'by' => $partial->supervisor?->name],
            ['key' => 'payment', 'label' => 'Medição', 'state' => $state('payment', $payment, $supervision && !$payment), 'at' => $partial->payment_at, 'by' => $partial->payer?->name],
            ['key' => 'finalized', 'label' => 'Finalizado', 'state' => $state('finalized', (bool) $partial->complete && $payment, $payment && !$partial->complete), 'at' => null, 'by' => null],
        ];
    }

    /** Etapa em que a parcial foi rejeitada (`approval`, `supervision`, `payment`), ou null. */
    public static function rejectedStage(Partial $partial): ?string
    {
        if (!$partial->deny) {
            return null;
        }

        return match (true) {
            (bool) ($partial->payment || $partial->payment_at)         => 'payment',
            (bool) ($partial->supervision || $partial->supervision_at) => 'supervision',
            default                                                    => 'approval',
        };
    }

    /** Rótulo da etapa atual ("Aguardando aprovação", "Em fiscalização"…), ou o motivo do encerramento. */
    public static function headline(Partial $partial): string
    {
        foreach (self::steps($partial) as $step) {
            if ($step['state'] === self::REJECTED) {
                return 'Rejeitada na ' . mb_strtolower($step['label']);
            }
        }

        foreach (self::steps($partial) as $step) {
            if ($step['state'] === self::CURRENT) {
                return match ($step['key']) {
                    'approval'    => 'Aguardando aprovação',
                    'supervision' => 'Em fiscalização',
                    'payment'     => 'Em medição',
                    default       => 'Aguardando finalização',
                };
            }
        }

        return 'Finalizada';
    }
}
