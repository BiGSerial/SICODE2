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

            $hoje = Carbon::now();
            $days_left = $hoje->diffInDays($dataCarbon, false);

            return $days_left;
        } else {
            return $this->note->days_left ?? 0; // Use null coalescing operator
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
            return Carbon::now()->addDays($this->note->days_left ?? 0)->format('d/m/Y'); // Use null coalescing operator
        }
    }
}
