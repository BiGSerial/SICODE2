<?php

namespace App\Http\Livewire\Services\Oexterno\Helpers;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import simples: devolve as linhas como Collection com cabeçalhos.
 * O Livewire chama e consome em memória (não cria modelos).
 */
class PoolPaymentArrayImport implements ToCollection, WithHeadingRow
{
    /** @var \Illuminate\Support\Collection */
    public Collection $rows;

    public function collection(Collection $rows)
    {
        $this->rows = $rows;
    }
}
