<?php

namespace App\Services\Supervision;

use App\Custom\{Notestatus, WpaStatus};
use App\Models\{Production, WorkReportFlowProduction};
use App\Support\Ops\{Deadline, Tone};
use Carbon\Carbon;

/**
 * Monta, para o Controle de Fiscalização, tudo o que uma linha mostra, já com tom semântico (padrão:
 * docs/PADRAO_LISTAS_OPERACIONAIS.md §10 e §13). A view só desenha; regra de prazo/cor mora aqui e em `App\Support\Ops`.
 */
class SupervisionControlRowPresenter
{
    /** Dias desde o informe: verde até 5, âmbar de 6 a 8, vermelho acima de 8. */
    public const INFORMED_OK_UNTIL = 5;

    public const INFORMED_WARN_UNTIL = 8;

    /** Dias desde o despacho: verde até 21 (70% do limite), âmbar até 30, vermelho acima. */
    public const DISPATCH_OK_UNTIL = 21;

    public const DISPATCH_WARN_UNTIL = 30;

    /** Dias desde a atribuição: verde até 7 (70% do limite), âmbar até 9, vermelho acima. */
    public const ATT_OK_UNTIL = 7;

    public const ATT_WARN_UNTIL = 9;

    public function present(Production $item, array $legalTags = []): array
    {
        $note        = $item->note;
        $currentFlow = $item->WorkReportFlowProductions?->first();
        $workReport  = $currentFlow?->work_report_id;
        $adsForm     = $note?->Adsform ?? $note?->workform?->adsform;

        // Valor e data da ADS vêm do informe da atividade, não da Nota.
        if ($workReport) {
            $adsAt          = $item->dt_ads ? Carbon::parse($item->dt_ads) : null;
            $adsAmount      = $item->ads_amount;
            $isTacit        = (bool) $item->ads_tacit;
            $tacitDelivered = (bool) $item->ads_tacit_delivered_at;
        } else {
            $adsAt          = $adsForm?->created_at;
            $adsAmount      = $adsForm?->amount;
            $isTacit        = (bool) ($adsForm?->tacit ?? false);
            $tacitDelivered = (bool) ($adsForm?->tacit_delivered_at ?? false);
        }

        $informedAt = $item->dt_inform ? Carbon::parse($item->dt_inform) : null;
        $informed   = $this->days($informedAt);
        $dispatched = $this->days($item->dispatch_at);
        $assigned   = $this->days($item->att_at);

        $wpa = $this->wpa($item);

        // D5 (2º ciclo do informe): informe e ADS não são cobrados; ficam em cinza (`secondary`) com "D5" no lugar dos dias.
        $cycleTwo = !$item->d5 && (bool) $item->dfive;

        return [
            'id'           => (int) $item->id,
            'noteId'       => (int) $item->note_id,
            'note'         => $note?->note,
            'workReportId' => $workReport ?: null,
            'isPartial'    => (bool) $item->partial,
            'isConnection' => !$item->partial && $currentFlow?->final_scope === WorkReportFlowProduction::SCOPE_CONNECTION,
            'ri'           => (bool) $item->d5,
            'd5'           => !$item->d5 && (bool) $item->dfive,
            'priority'     => (bool) $item->priority,
            'legal'        => $legalTags[$item->note_id] ?? [],
            'dispatcher'   => $this->person($item->dispatcher, null),
            'user'         => $this->person($item->user, $item->company),
            'wpa'          => $wpa,
            'rubrica'      => $note?->rubrica,
            'city'         => $note?->lexp,
            'group2'       => $note?->group2,
            'postes'       => $note?->postes,
            'adsAt'        => $adsAt ? Carbon::parse($adsAt) : null,
            'adsDays'      => $this->days($adsAt),
            'adsTacit'     => $isTacit,
            'adsDelivered' => $tacitDelivered,
            'adsAmount'    => $adsAmount === null ? null : (float) $adsAmount,
            'informedAt'   => $informedAt,
            'informedDays' => $informed,
            'informedTone' => $cycleTwo ? ($informedAt ? 'secondary' : 'muted') : Deadline::age($informed, self::INFORMED_OK_UNTIL, self::INFORMED_WARN_UNTIL),
            'cycleTwo'     => $cycleTwo,
            'dispatchAt'   => $item->dispatch_at,
            'dispatchDays' => $dispatched,
            'dispatchTone' => Deadline::age($dispatched, self::DISPATCH_OK_UNTIL, self::DISPATCH_WARN_UNTIL),
            'attAt'        => $item->att_at,
            'attDays'      => $assigned,
            'attTone'      => Deadline::age($assigned, self::ATT_OK_UNTIL, self::ATT_WARN_UNTIL),
            'status'       => (int) $item->status,
            'statusLabel'  => Notestatus::status($item->status)->status,
            'statusTone'   => Tone::forProductionStatus($item->status),
            'block'        => (bool) $item->block,
            'completed'    => (bool) $item->completed,
            'hasUser'      => (bool) $item->user_id,
            'canRemoveAll' => !auth()->user()?->contract,
        ];
    }

    /** Dias corridos desde uma data (zero hoje); null sem data. */
    private function days($date): ?int
    {
        return $date ? (int) Carbon::parse($date)->startOfDay()->diffInDays(Carbon::now()->startOfDay()) : null;
    }

    /** @return array{name: string, company: ?string} */
    private function person($user, $fallbackCompany): array
    {
        $name  = is_string($user) ? $user : $user?->name;
        $parts = collect(explode(' ', trim((string) $name)))->filter()->values();
        $short = $parts->isEmpty() ? 'Desconhecido' : ($parts->count() > 1 ? $parts->first() . ' ' . $parts->last() : $parts->first());

        return ['name' => $short, 'company' => $fallbackCompany?->name ?? $user?->Company?->name];
    }

    /** Situação da DD no WPA (ícone, tom e duas linhas de texto). */
    private function wpa(Production $item): array
    {
        $last   = $item->wpas?->last();
        $status = WpaStatus::status($last?->dd, $last?->execstats, $last?->completed_at);
        $lines  = preg_split('/\R/', trim(strip_tags(str_replace('</p>', "\n", (string) $status->info)))) ?: [];

        return [
            'icon' => $status->icon,
            'tone' => Tone::fromBootstrap(str_replace('text-bg-', '', (string) $status->bg_color)),
            'dd'   => $lines[0] ?? null,
            'sub'  => $lines[1] ?? null,
        ];
    }
}
