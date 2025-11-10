<?php

namespace App\Services\Design;

use App\Models\Note;
use App\Models\Service;
use App\Models\Production;

class BlockEvaluator
{
    public const FREE        = 0; // pode despachar / atribuir
    public const HOLD_BLUE   = 1; // aberto / em andamento
    public const HOLD_YELLOW = 2; // “guarda” (status==1) ou pendência leve
    public const HOLD_GREEN  = 3; // completed
    public const HOLD_RED    = 4; // confirmado e “não refletiu no SAP”

    public function evaluate(Note $note, Service $service): array
    {
        $prod = $this->latestProductionForService($note, $service->uuid);
        $prodCount = $this->countProductionsForService($note, $service->uuid);

        if (!$prod) {

            return $this->res(self::FREE, true, 'no_production');
        }



        // ===== 4) FALLBACK SAP (dt e status iguais => não refletiu)
        if (
            ($prod->dt_note ?? null) && ($note->dt_status ?? null) &&
            $prod->dt_note->equalTo($note->dt_status) &&
            ($prod->status_note ?? null) !== null &&
            ($note->nstats ?? null) !== null &&
            $prod->status_note == $note->nstats
            && $prod->confirmed
        ) {
            return $this->res(self::HOLD_RED, true, 'sap_not_reflected_same_dt_and_status', $prod, $prodCount);
        }

        // ===== Produção sem atribuição
        if (empty($prod->user_id)) {
            return $this->res(self::HOLD_YELLOW, false, 'production_without_assignment', $prod, $prodCount);
        }

        // ===== 5) Estados herdados
        // if ($prod->confirmed ?? false) {
        //     return $this->res(self::HOLD_RED, true, 'prod_confirmed', $prod, $prodCount);
        // }
        if ($prod->completed ?? false) {
            return $this->res(self::HOLD_GREEN, false, 'prod_completed', $prod, $prodCount);
        }
        if ((int) ($prod->status ?? 0) === 1) {
            return $this->res(self::HOLD_YELLOW, false, 'prod_status_guard', $prod, $prodCount);
        }

        return $this->res(self::HOLD_BLUE, false, 'prod_open_generic', $prod, $prodCount);
    }

    private function latestProductionForService(Note $note, string $serviceUuid): ?Production
    {
        if ($note->relationLoaded('Productions')) {
            return $note->Productions
                ->where('service_id', $serviceUuid)
                ->sortByDesc('created_at')
                ->first();
        }

        return Production::where('note_id', $note->id)
            ->where('service_id', $serviceUuid)
            ->orderByDesc('created_at')
            ->first();
    }

    private function countProductionsForService(Note $note, string $serviceUuid): int
    {
        if ($note->relationLoaded('Productions')) {
            return $note->Productions
                ->where('service_id', $serviceUuid)
                ->count();
        }

        return Production::where('note_id', $note->id)
            ->where('service_id', $serviceUuid)
            ->count();
    }

    private function res(int $block, bool $command, string $reason, ?Production $prod = null, int $count = 0): array
    {
        return [
            'block'      => $block,
            'command'    => $command,
            'color'      => $this->colorFor($block),
            'reason'     => $reason,
            'production' => $prod,
            'count'      => $count,
        ];
    }

    private function colorFor(int $block): string
    {
        return match ($block) {
            self::FREE        => '',
            self::HOLD_BLUE   => 'table-primary',
            self::HOLD_YELLOW => 'table-warning',
            self::HOLD_GREEN  => 'table-success',
            self::HOLD_RED    => 'table-danger',
            default           => '',
        };
    }
}
