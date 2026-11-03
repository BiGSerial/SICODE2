<?php

namespace App\Services\Partner;

use App\Custom\Viabilitiesstatus;
use App\Helpers\DaysLeft;
use App\Models\Viability;
use App\Support\Ops\{Deadline, Tone};
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Transforma a Viability da lista do portal da parceira em valores prontos para a view (tons, datas, ordens),
 * para que o Blade só desenhe (docs/PADRAO_LISTAS_OPERACIONAIS.md §4). Sem consulta: as listas carregam
 * `Orders`, `Note.Orders` e `withSum('Days as days_sum', 'days')` antes de chamar.
 */
final class ViabilityListRowPresenter
{
    /** Dias de tolerância somados ao prazo da viabilidade (regra antiga das listas). */
    private const GRACE_DAYS = 7;

    /** Faltando até este número de dias o prazo vira âmbar (antes: "vencendo" abaixo de 3 dias). */
    private const WARN_DAYS = 2;

    /** Números de ordem a mostrar: as da viabilidade, ou (se não houver) as da nota que ainda não foram encerradas. */
    public static function orders(Viability $viability, bool $viabilityFirst = true): Collection
    {
        if ($viabilityFirst && $viability->relationLoaded('Orders') && $viability->Orders->isNotEmpty()) {
            return $viability->Orders->pluck('ordem')->values();
        }

        return $viability->Note->Orders
            ->reject(fn ($order) => str_starts_with((string) $order->statusSist, 'ENT') || str_starts_with((string) $order->statusSist, 'ENC'))
            ->pluck('ordem')
            ->values();
    }

    /** Prazo da viabilidade (recebida + dias concedidos + tolerância), com o tom do prazo e a explicação. */
    public static function due(Viability $viability): array
    {
        if (!$viability->sended_at) {
            return ['date' => null, 'tone' => 'muted', 'hint' => null];
        }

        $granted = array_key_exists('days_sum', $viability->getAttributes()) ? (int) $viability->days_sum : $viability->getDays();
        $date    = $viability->sended_at->copy()->addDays($granted + self::GRACE_DAYS);
        $left    = Deadline::daysFromNow($date);

        return [
            'date' => $date,
            'tone' => Deadline::remaining($left, self::WARN_DAYS),
            'hint' => 'Prazo da viabilidade (recebida + dias concedidos + ' . self::GRACE_DAYS . ' dias). ' . Deadline::remainingText($left),
        ];
    }

    /** Data limite da obra (mensalização da nota ou vencimento da OV); null quando a nota não tem. */
    public static function workDate(Viability $viability): ?Carbon
    {
        $text = (new DaysLeft($viability->Note))->getLastDate();

        if (!preg_match('#^\d{2}/\d{2}/\d{4}$#', $text)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $text)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Selo de status da viabilidade (tom vem da cor legada de `Viabilitiesstatus`). */
    public static function status(Viability $viability): array
    {
        $status = Viabilitiesstatus::status($viability->status);

        return ['label' => $status->status, 'tone' => Tone::fromBootstrap($status->color ?? null)];
    }

    /** Selo da justificativa de ADS tácita (histórico); null quando não há o que mostrar. */
    public static function tacit(Viability $viability): ?array
    {
        $justification = $viability->relationLoaded('Justification') ? $viability->Justification : null;

        if (!$justification) {
            return $viability->tacit
                ? ['label' => 'Sem justificativa', 'tone' => 'muted', 'open' => false, 'hint' => 'Venceu tacitamente e a parceira não justificou']
                : null;
        }

        $granted   = (bool) $justification->granted;
        $dismissed = (bool) $justification->dismissed;

        return match (true) {
            $granted && !$dismissed  => ['label' => 'Deferido', 'tone' => 'green', 'open' => true, 'hint' => 'Justificativa aceita. Clique para ver'],
            $dismissed && !$granted  => ['label' => 'Indeferido', 'tone' => 'red', 'open' => true, 'hint' => 'Justificativa recusada. Clique para ver'],
            !$dismissed && !$granted => ['label' => 'Em avaliação', 'tone' => 'blue', 'open' => true, 'hint' => 'Justificativa aguardando avaliação. Clique para ver'],
            default                  => ['label' => 'Inconsistência', 'tone' => 'amber', 'open' => true, 'hint' => 'Justificativa com dados inconsistentes. Clique para ver'],
        };
    }
}
