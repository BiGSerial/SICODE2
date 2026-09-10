<?php

namespace App\Custom\Partial;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ADSReadFilter implements IReadFilter
{
    private const ALLOWED_CELLS = [
        'Check-list' => [
            'G4',
            'G5',
            'G6',
            'G7',
            'G8',
            'W7',

            /*
             * Fallback do valor
             */
            'O12',
            'Q12',
            'Q13',
        ],
    ];

    public function readCell(
        $columnAddress,
        $row,
        $worksheetName = ''
    ): bool {
        $allowed = self::ALLOWED_CELLS[$worksheetName] ?? null;

        if ($allowed === null) {
            return false;
        }

        return in_array(
            $columnAddress . $row,
            $allowed,
            true
        );
    }
}
