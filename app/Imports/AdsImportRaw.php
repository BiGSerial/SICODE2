<?php

namespace App\Imports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AdsImportRaw implements ToArray, WithHeadingRow
{
    /**
     * @param array $rows
     */
    public function array(array $rows)
    {
        foreach ($rows as &$row) { // Loop pelas linhas (usando & para modificar o array original)
            if (isset($row['hora_de_conclusao'])) {
                $excelSerialDate = $row['hora_de_conclusao'];

                // Converter a data serial do Excel para um timestamp Unix
                $unixTimestamp = ($excelSerialDate - 25569) * 86400;  // Fórmula para Excel (data base 1900)

                // Criar um objeto Carbon a partir do timestamp Unix
                if ($unixTimestamp > 0) {  //Verifica se a conversão é válida
                    $carbonDate = Carbon::createFromTimestamp($unixTimestamp);
                    $row['hora_de_conclusao'] = $carbonDate;  // Substitui o valor decimal pelo objeto Carbon
                } else {
                    $row['hora_de_conclusao'] = null; // Ou outro valor padrão, se a conversão falhar
                }
            }
        }
        return $rows;
    }

    public function headingRow(): int
    {
        return 1; // Retorna o número da linha que contém o cabeçalho (geralmente 1)
    }
}
