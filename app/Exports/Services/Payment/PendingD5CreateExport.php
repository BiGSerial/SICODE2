<?php

namespace App\Exports\Services\Payment;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;

class PendingD5CreateExport implements FromQuery, WithHeadings, WithMapping, WithProperties
{
    use Exportable;

    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Nota',
            'Nota D5',
            'Empresa',
            'Local Instalacao',
            'Conjunto',
            'PEP',
            'E-PEP',
            'Codificacao',
            'Sintomas',
            'Motivo',
            'Descricao',
            'Despachado em',
            'Concluido em',
            'Fiscalizado em',
            'Pago em',
            'Criado em',
            'Atualizado em',
        ];
    }

    public function map($five): array
    {
        return [
            optional($five->note)->note,
            $five->note_d5,
            optional($five->company)->name,
            $five->loc_install,
            $five->conjunto,
            $five->pep,
            $five->e_pep,
            $five->codify,
            $five->sintoms,
            $five->reason,
            $five->description,
            optional($five->dispatch_at)->format('d/m/Y H:i'),
            optional($five->completed_at)->format('d/m/Y H:i'),
            optional($five->supervisioned_at)->format('d/m/Y H:i'),
            optional($five->payed_at)->format('d/m/Y H:i'),
            optional($five->created_at)->format('d/m/Y H:i'),
            optional($five->updated_at)->format('d/m/Y H:i'),
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE',
            'lastModifiedBy' => 'SICODE',
            'title'          => 'D5 pendentes para criacao',
            'description'    => 'Exportacao com notas D5 pendentes para criacao.',
            'subject'        => 'FiveNotes',
            'keywords'       => 'five, notas, export, excel',
            'category'       => 'Exports',
        ];
    }
}
