<?php

namespace App\Exports\Reports;

use App\Custom\Viabilitiesstatus;
use Carbon\Carbon;
use DateTimeInterface;
use Maatwebsite\Excel\Concerns\{Exportable, FromQuery, WithChunkReading, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithProperties};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class viabilityQueryExport implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping, WithColumnWidths
{
    use Exportable;

    public $exports;

    public function __construct($data)
    {
        $this->exports = $data;
    }

    public function query()
    {
        return $this->exports;
    }

    public function headings(): array
    {
        return [
            'CONTRATANTE',
            'EMPRESA',
            'ORDEM',
            'NOTA/OV',
            'RESPONSÁVEL',
            'CONTRATADO',
            'TÁCITO',
            'ENVIADO EM',
            'CONTRATADO EM',
            'VENCIDO EM',
            'EMPREITERA',
            'VIABILIZADO EM',
            'COMPLETADO EM',
            'STATUS',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Define o estilo para a primeira linha
                $event->sheet->getStyle('A1:N1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'], // Cor do texto (branco)
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'], // Cor de fundo (azul)
                    ],
                ]);

                // Formata a coluna A para número sem casas decimais
                $event->sheet->getStyle('C')->getAlignment()->setWrapText(true);
                $event->sheet->getStyle('C')->getNumberFormat()->setFormatCode('0');
                $event->sheet->getStyle('D')->getNumberFormat()->setFormatCode('0');

                // Formata as colunas F, H, J para data (d/m/Y)
                $event->sheet->getStyle('G')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('H')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('I')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('L')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $event->sheet->getStyle('M')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                // Define o alinhamento horizontal e vertical para todas as células
                $event->sheet->getStyle('A1:N' . $event->sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $event->sheet->getStyle('A1:N' . $event->sheet->getHighestRow())->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            },

        ];
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 22,
            'C' => 18,
            'D' => 16,
            'E' => 28,
            'F' => 14,
            'G' => 12,
            'H' => 14,
            'I' => 14,
            'J' => 14,
            'K' => 22,
            'L' => 14,
            'M' => 14,
            'N' => 18,
        ];
    }

    public function map($row): array
    {
        $orders = $row->Orders?->isNotEmpty()
            ? $row->Orders->pluck('ordem')->filter()->implode("\n")
            : ($row->Order?->ordem ?? '');

        $contractCompany = $row->User?->Employee?->Contract?->company?->name
            ?? $row->User?->Company?->name
            ?? '---';

        return [
            $row->User?->name ?? '---',
            $contractCompany,
            $orders ?? '---',
            $row->Note?->note ?? '---',
            $row->Engineer?->name ?? '---',
            $row->hired ? 'SIM' : 'NÃO',
            $row->tacit ? 'SIM' : 'NÃO',
            $this->formatDate($row->sended_at),
            $this->formatDate($row->hired_at),
            $this->formatDate($row->tacit_at),
            $row->Company?->name ?? '---',
            $this->formatDate($row->returned_at),
            $this->formatDate($row->completed_at),
            Viabilitiesstatus::status($row->status)->status,
        ];
    }

    private function formatDate($date): string
    {
        if (!$date) {
            return '---';
        }

        return ($date instanceof DateTimeInterface ? $date : Carbon::parse($date))->format('d/m/Y');
    }

    public function properties(): array
    {
        return [
            'creator'        => "SICODE",
            'lastModifiedBy' => "SICODE",
            'title'          => 'Relatorio Automatico Sicode',
            'description'    => 'Arquivo gerado automaticamente via SICODE',
            'subject'        => 'Relatorios',
            'manager'        => 'Joao Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }
}
