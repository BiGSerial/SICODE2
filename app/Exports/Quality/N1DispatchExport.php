<?php

namespace App\Exports\Quality;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{FromCollection, ShouldAutoSize, WithHeadings};

class N1DispatchExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(private readonly Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn ($process) => [
            $process->Note?->note,
            $process->Note?->numPedido,
            $process->Note?->material,
            $process->Company?->name,
            $process->phase->label(),
            $process->dispatched_at?->format('d/m/Y H:i'),
        ]);
    }

    public function headings(): array
    {
        return ['Nota', 'Pedido', 'Material', 'Empresa', 'Ciclo', 'Disponível desde'];
    }
}
