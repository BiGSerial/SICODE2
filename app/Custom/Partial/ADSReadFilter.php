<?php

namespace App\Custom\Partial;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ADSReadFilter implements IReadFilter
{
    /**
     * Mapa de planilhas para células permitidas.
     *
     * @var string[][]
     */
    private const ALLOWED_CELLS = [
        'Check-list' => [
            'G4', 'G5', 'G6', 'G7', 'G8',
            'W7',
            'Q13', 'R13', 'S13',
        ],
    ];

    /**
     * Decide se uma célula deve ser lida.
     *
     * @param string $columnAddress Coluna (ex: 'G')
     * @param int    $row           Linha (ex: 4)
     * @param string $worksheetName Nome da aba
     *
     * @return bool True para ler, False para pular
     */
    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        if (! isset(self::ALLOWED_CELLS[$worksheetName])) {
            return false;
        }

        $coord = $columnAddress . $row;
        return in_array($coord, self::ALLOWED_CELLS[$worksheetName], true);
    }
}
