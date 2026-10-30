<?php

namespace App\Helpers;

use App\Models\Note;
use Carbon\Carbon;

final class DaysLeft
{
    public ?Note $note = null;

    public function __construct(?Note $note = null) // Make $note nullable and optional
    {
        $this->note = $note;
    }

    private function convertMensalizationToDate(?string $mesalization): ?string
    {
        if (empty($mesalization) || $mesalization === 'erro') {
            return null;
        }

        preg_match('/\d+\/\d+/', $mesalization, $matches);

        if (empty($matches)) {
            return null;
        }

        [$mes, $ano] = explode('/', $matches[0]);

        $mes = (int) $mes; // Convert to integer for validation

        if ($mes >= 1 && $mes <= 12) { // Validate month value
            return "{$ano}-{$mes}-28 0:00:00";
        } else {
            return "{$ano}-12-28 0:00:00"; // Default to December if month is invalid
        }
    }

    /**
     * Normaliza a data de vencimento vinda da origem (dtVencimento) para Y-m-d.
     * Vazio, não parseável ou ano anterior a 2000 (sentinelas do SQL Server) é inválido.
     */
    public static function parseDueDate($value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        try {
            $date = Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }

        return $date->year >= 2000 ? $date->format('Y-m-d') : null;
    }

    /**
     * Só OVs (type_note = 2) usam dt_vencimento. Se a coluna não veio no select do model,
     * consulta o banco para não sobrescrever uma data da origem com a calculada.
     */
    private function storedDueDate(): ?Carbon
    {
        if (!$this->note || $this->note->type_note != 2) {
            return null;
        }

        $value = array_key_exists('dt_vencimento', $this->note->getAttributes())
            ? $this->note->dt_vencimento
            : ($this->note->exists
                ? Note::whereKey($this->note->getKey())->value('dt_vencimento')
                : null);

        $date = DaysLeft::parseDueDate($value);

        return $date ? Carbon::parse($date)->startOfDay() : null;
    }

    /**
     * Vencimento da OV: usa dt_vencimento quando válido; senão calcula a partir de
     * days_left e grava (a próxima atualização da origem sobrescreve/zera o campo).
     */
    private function resolveOvDueDate(): ?Carbon
    {
        $stored = $this->storedDueDate();

        if ($stored) {
            return $stored;
        }

        $date = Carbon::today()->addDays((int) ($this->note->days_left ?? 0));

        // Só OVs persistem a data calculada; outros tipos mantêm o cálculo em memória.
        if ($this->note->type_note == 2 && $this->note->days_left !== null && $this->note->exists) {
            // Update direto: não dispara eventos nem mexe em updated_at.
            Note::whereKey($this->note->getKey())->update(['dt_vencimento' => $date->format('Y-m-d')]);
            $this->note->setAttribute('dt_vencimento', $date->format('Y-m-d'));
            $this->note->syncOriginalAttribute('dt_vencimento');
        }

        return $date;
    }

    public function getDaysLeft(): int
    {
        if (!$this->note) {
            return 0; // Or handle the case where $note is null appropriately
        }

        if ($this->note->type_note == 1) {
            $dataString = $this->convertMensalizationToDate($this->note->mesalization);

            if ($dataString === null) {
                return 0; // Or handle null date appropriately
            }

            try {
                $dataCarbon = Carbon::parse($dataString);
            } catch (\Exception $e) {
                // Handle parsing errors (e.g., invalid date format)
                return 0; // Or log the error and return a default value
            }

            $hoje      = Carbon::now();
            $days_left = $hoje->diffInDays($dataCarbon, false);

            return $days_left;
        } else {
            $due = $this->resolveOvDueDate();

            return $due ? (int) Carbon::today()->diffInDays($due, false) : 0;
        }
    }

    public function getLastDate(): string
    {
        if (!$this->note) {
            return '---'; // Or handle the case where $note is null appropriately
        }

        if ($this->note->type_note == 1) {
            $dataString = $this->convertMensalizationToDate($this->note->mesalization);

            if ($dataString === null) {
                return '---'; // Or handle null date appropriately
            }

            try {
                return Carbon::parse($dataString)->format('d/m/Y');
            } catch (\Exception $e) {
                return '---'; // Handle parsing errors
            }
        } else {
            return ($this->resolveOvDueDate() ?? Carbon::today())->format('d/m/Y');
        }
    }
}
