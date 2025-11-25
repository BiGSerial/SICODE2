<?php

namespace App\Services\Design;

use App\Models\Note;
use App\Models\Service;
use App\Models\Production;
use Carbon\Carbon;

class BlockEvaluator
{
    public const FREE        = 0;
    public const HOLD_BLUE   = 1;
    public const HOLD_YELLOW = 2;
    public const HOLD_GREEN  = 3;
    public const HOLD_RED    = 4;
    public const HOLD_INCONST  = 5;

    private function dateEq(mixed $a, mixed $b): bool
    {
        if ($a === null && $b === null) {
            return true;
        }
        if ($a === null || $b === null) {
            return false;
        }

        try {
            $ca = $a instanceof Carbon ? $a : Carbon::parse($a);
            $cb = $b instanceof Carbon ? $b : Carbon::parse($b);
            return $ca->equalTo($cb);
        } catch (\Throwable $e) {
            // fallback por string (último caso)
            return (string)$a === (string)$b;
        }
    }

    /**
     * Se ambos forem “numéricos”, compara como int.
     * Caso contrário, normaliza para string trimada e compara.
     */
    private function statusEq(mixed $a, mixed $b): bool
    {
        if ($a === null && $b === null) {
            return true;
        }
        if ($a === null || $b === null) {
            return false;
        }

        $aIsNum = is_numeric($a);
        $bIsNum = is_numeric($b);

        if ($aIsNum && $bIsNum) {
            return (int)$a === (int)$b;
        }

        return trim((string)$a) === trim((string)$b);
    }


    public function evaluate(Note $note, Service $service): array
    {
        $prod      = $this->latestProductionForService($note, $service->uuid);
        $prodCount = $this->countProductionsForService($note, $service->uuid);

        // 1) n sem p => FREE
        if (!$prod) {
            return $this->res(self::FREE, true, 'no_production');
        }

        // 2) Comparações conforme regra
        $dtEqual      = $this->dateEq($prod->dt_note ?? null, $note->dt_status ?? null);
        $dtDifferent  = !$dtEqual;

        [$noteStatus, $prodStatus] = $this->resolveStatuses($note, $prod);
        $statusEqual     = $this->statusEq($prodStatus, $noteStatus);
        $statusDifferent = !$statusEqual;


        $completed = (bool)($prod->completed ?? false);
        $confirmed = (bool)($prod->confirmed ?? false);

        // 3) DIFERENTES + completed + confirmed => FREE
        if (($dtDifferent || $statusDifferent) && $completed && $confirmed) {
            return $this->res(self::FREE, true, 'different_and_completed_confirmed', $prod, $prodCount);
        }

        // 4) IGUAIS + completed + confirmed => RED
        if ($dtEqual && $statusEqual && $completed && $confirmed) {
            return $this->res(self::HOLD_RED, true, 'same_and_completed_confirmed', $prod, $prodCount);
        }

        // 5) IGUAIS + completed + !confirmed => GREEN (produção finalizada)
        if ($dtEqual && $statusEqual && $completed && !$confirmed) {
            return $this->res(self::HOLD_GREEN, false, 'same_completed_not_confirmed', $prod, $prodCount);
        }

        // 6) Sem usuário atribuído => YELLOW
        if (empty($prod->user_id)) {
            return $this->res(self::HOLD_YELLOW, false, 'production_without_assignment', $prod, $prodCount);
        }

        // 7) status==2 e !completed => BLUE
        if ((int)($prod->status ?? 0) === 2 && !$completed) {
            return $this->res(self::HOLD_BLUE, false, 'production_status_2_not_completed', $prod, $prodCount);
        }

        if ($dtDifferent && $completed && !$confirmed) {
            return $this->res(self::HOLD_INCONST, false, 'production_completed_waiting_confirmation', $prod, $prodCount);
        }

        // 8) Aberto genérico => BLUE
        return $this->res(self::HOLD_INCONST, false, 'prod_inconst_open_generic', $prod, $prodCount);
    }

    private function latestProductionForService(Note $note, string $serviceUuid): ?Production
    {
        if ($note->relationLoaded('Productions')) {
            return $note->Productions
                ->where('service_id', $serviceUuid)
                ->sortByDesc(fn ($p) => $p->updated_at ?? $p->created_at)
                ->first();
        }

        return Production::where('note_id', $note->id)
            ->where('service_id', $serviceUuid)
            ->orderByDesc('updated_at')
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

    /**
     * Comparação por type_note:
     *  - type_note == 1: n.centerjob  vs p.centerTrab
     *  - type_note == 2: n.nstats     vs p.status_note
     *  - fallback:       n.nstats     vs p.status_note
     */
    private function resolveStatuses(Note $note, Production $prod): array
    {
        $type = (int)($note->type_note ?? 0);

        if ($type === 1) {
            $noteStatus = $note->centerjob   ?? null;
            $prodStatus = $prod->centerTrab  ?? null;
        } elseif ($type === 2) {
            $noteStatus = $note->nstats      ?? null;
            $prodStatus = $prod->status_note ?? null;
        } else {
            $noteStatus = $note->nstats      ?? null;
            $prodStatus = $prod->status_note ?? null;
        }

        return [$noteStatus, $prodStatus];
    }

    /**
     * Igualdade tolerante:
     * - null == null => true
     * - null != valor => false
     * - compara por === quando ambos não nulos (evita coerção indesejada)
     */
    private function eq(mixed $a, mixed $b): bool
    {
        if ($a === null && $b === null) {
            return true;
        }
        if ($a === null || $b === null) {
            return false;
        }
        return $a === $b;
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
            self::HOLD_INCONST => 'table-secondary',
            default           => '',
        };
    }
}
