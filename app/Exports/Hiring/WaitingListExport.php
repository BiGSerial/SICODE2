<?php

namespace App\Exports\Hiring;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class WaitingListExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading, ShouldAutoSize, WithStyles
{
    use Exportable;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function query()
    {
        return $this->data->with(['Note.Orders.Operations' => function ($q) {
            $q->where('operacao', '0010');
        }, 'Note.Files', 'Reclaim.Service', 'Reclaim.Production'])
            ->orderBy('created_at');
        ;
    }

    public function map($item): array
    {
        $note = $item->Note;
        $op   = optional(optional($note->Orders)->first())->Operations->first();
        $prod = optional($item->Reclaim)->Production;

        return [
            $note?->note,
            optional($note?->Orders?->first())->ordem,
            $op?->cenTrab ?: '---',
            optional($item->Reclaim?->Service)->service,
            $item->category,
            $note?->rubrica,
            $note?->lexp,
            optional($item->created_at)?->format('d/m/Y H:i'),
            optional($item->created_at)?->diffForHumans(now(), ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]),
            $prod
                ? \App\Custom\Notestatus::status($prod->status)->status
                : 'Aguardando Atribuição',
            $prod?->completed_at?->format('d/m/Y H:i') ?: '—',
            optional($prod->completed_at)?->diffForHumans(now(), ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]),
            $prod?->User?->name ?: '—',
        ];
    }

    public function headings(): array
    {
        return [
            'Nota',
            'OV',
            'CentroTrab',
            'Serviço',
            'Categoria',
            'Rubrica',
            'Município',
            'Data Envio',
            'Em Atividade (dif)',
            'Status',
            'Finalizado Em',
            'Temppo Finalizado',
            'Responsável',
        ];
    }


    public function chunkSize(): int
    {
        return 500;
    }


    public function styles($sheet)
    {
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);        
        $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode('0');

        return [];
    }


}
