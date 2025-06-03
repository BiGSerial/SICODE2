<?php

namespace App\Exports\Viability;

use App\Custom\Notestatus;
use App\Custom\Viabilitiesstatus;
use App\Models\Viability;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;

class ViabilitiesRejectedExport implements FromQuery, WithMapping, WithHeadings, WithProperties, WithEvents, WithChunkReading, ShouldQueue
{
    use Exportable;

    /**
     * Se quiser filtrar pelo mesmo builder do seu método,
     * basta injetar um Builder no construtor.
     */
    protected $baseQuery;

    public function __construct($baseQuery = null)
    {
        // se não vier, usa todo o modelo Viability
        $this->baseQuery = $baseQuery ?: Viability::query();
    }

    /**
     * FromQuery: devolve o query builder já com filtros.
     */
    public function query()
    {
        return $this->baseQuery
        ->with(['Note', 'Orders', 'Justification', 'Engineer']);
    }

    /**
     * WithMapping: define como cada modelo vira linha simples de array.
     */
    public function map($viab): array
    {



        return [
            $viab->Note->note,
            // ordens concatenadas
            $viab->Orders->pluck('ordem')->implode("\n"),
            $viab->Note->rubrica,
            $viab->hired ? 'SIM' : 'NÃO',
            $viab->hired_at?->format('d/m/Y'),
            $viab->hired_at?->format('H:i'),
            $viab->returned_at?->format('d/m/Y'),
            $viab->returned_at?->format('H:i'),
            $viab->Form?->reason,
            $viab->Form?->description,
            $viab->Engineer?->name,
            $viab->Note->lexp,
            $viab->status ? Viabilitiesstatus::status($viab->status)->status : '',
            $viab->Company?->name,
            $viab?->updated_at?->format('d/m/Y'),
            $viab?->updated_at?->format('H:i'),

        ];
    }

    /**
     * WithHeadings: primeira linha de cabeçalhos.
     */
    public function headings(): array
    {
        return [
            'Nota',
            'Ordens',
            'Rubrica',
            'Contratada',
            'Data Contratação',
            'Hora Contratação',
            'Data Viabilidade',
            'Hora Viabilidade',
            'Motivo Rejeição',
            'Descrição Rejeição',
            'Responsável',
            'Cidade',
            'Status Viabilidade',
            'Empresa',
            'Ultima Atualização',
            'Hora Ultima Atualização',
        ];
    }

    /**
     * WithProperties: mantém seus metadados.
     */
    public function properties(): array
    {
        return [
            'creator'        => Auth::user()->name,
            'lastModifiedBy' => Auth::user()->name,
            'title'          => 'Engineers Not Realized',
            'description'    => 'SICODE - Relatório Automático',
            'subject'        => 'Engineers Not Realized',
            'keywords'       => 'Engineers, Not Realized',
            'category'       => 'Engineers',
            'manager'        => 'João Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }

    /**
     * WithEvents: reaproveita seu estilo de planilha.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastColumn = $sheet->getHighestDataColumn();
                // cabeçalho azul
                $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color'    => ['argb' => 'FF0000FF'],
                    ],
                    'font' => [
                        'color' => ['argb' => 'FFFFFFFF'],
                    ],
                ]);

                // formata colunas numéricas, data e hora
                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A1:B{$lastRow}")->getNumberFormat()->setFormatCode('0');

                $sheet->getStyle("C1:C{$lastRow}")->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle("E1:E{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yy');
                $sheet->getStyle("G1:G{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yy');
                $sheet->getStyle("O1:O{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yy');
                $sheet->getStyle("F1:F{$lastRow}")->getNumberFormat()->setFormatCode('hh:mm');
                $sheet->getStyle("H1:H{$lastRow}")->getNumberFormat()->setFormatCode('hh:mm');
                $sheet->getStyle("P1:P{$lastRow}")->getNumberFormat()->setFormatCode('hh:mm');


                $sheet->getStyle("B1:B{$lastRow}")->getAlignment()->setWrapText(true);

                // alinhamento e autosize
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                      ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                foreach (range('A', $lastColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }

    /**
     * WithChunkReading: define o tamanho de cada “fatia” (em linhas).
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
