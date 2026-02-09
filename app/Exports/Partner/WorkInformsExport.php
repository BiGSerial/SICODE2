<?php

namespace App\Exports\Partner;

use App\Custom\Viabilitiesstatus;
use App\Models\WorkReport;
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

class WorkInformsExport implements FromQuery, WithMapping, WithHeadings, WithProperties, WithEvents, WithChunkReading, ShouldQueue
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
        $this->baseQuery = $baseQuery ?: null;
    }

    /**
     * FromQuery: devolve o query builder já com filtros.
     */
    public function query()
    {
        return $this->baseQuery
        ->with(['Note', 'Orders', 'Equipment', 'Returnwork', 'Adsform', 'Company']);
    }

    /**
     * WithMapping: define como cada modelo vira linha simples de array.
     */
    public function map($row): array
    {



        return [
            $row->Note->note,
            // ordens concatenadas
            $row->Orders->pluck('ordem')->implode("\n"),
            $row->Note->rubrica,
            $row->Equipment?->count(),
            $row->changes ? 'SIM' : 'NÃO',
            $row->team,
            $row->responsible,
            $row->date?->format('d/m/Y'),
            $row->informed_at?->format('d/m/Y'),
            $row->informed_at?->format('H:i'),
            $row->adsform ? 'SIM' : 'NÃO',
            $row->adsform ? ($row->adsform->tacit ? 'TACITA' : 'NORMAL') : '',
            $row->adsform?->tacit_due_at?->format('d/m/Y'),
            $row->adsform?->tacit_due_at?->format('H:i'),
            ($row->adsform?->tacit ? $row->adsform?->tacit_delivered_at : $row->adsform?->created_at)?->format('d/m/Y'),
            ($row->adsform?->tacit ? $row->adsform?->tacit_delivered_at : $row->adsform?->created_at)?->format('H:i'),
            $row->company?->name,
            $row->rejected ? 'SIM' : 'NÃO',
            $row->rejected ? $row->Returnwork?->last()->Service?->service : '',
            $row->rejected ? $row->Returnwork?->last()->category : '',
            $row->rejected ? $row->Returnwork?->last()->User?->name : '',
            $row->rejected ? $row->Returnwork?->last()->text_obs : '',
            $row->rejected ? $row->Returnwork?->last()->created_at?->format('d/m/Y') : '',
            $row->rejected ? $row->Returnwork?->last()->created_at?->format('H:i') : '',

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
            'Equipamentos',
            'Mudanças',
            'Equipe',
            'Responsável',
            'Data da execução',
            'Data da informação',
            'Hora da informação',
            'Entrega ADS',
            'Tipo ADS',
            'Prazo ADS (Data)',
            'Prazo ADS (Hora)',
            'Data do ADS',
            'Hora do ADS',
            'Empresa',
            'Informe Rejeitado',
            'Serviço Rejeitado',
            'Motivo Rejeitada',
            'Usuário Rejeitado',
            'Observação Rejeitada',
            'Data da Rejeição',
            'Hora da Rejeição',
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

                $sheet->getStyle("H2:I{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yy');
                $sheet->getStyle("M2:M{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yy');
                $sheet->getStyle("O2:O{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yy');
                $sheet->getStyle("W2:W{$lastRow}")->getNumberFormat()->setFormatCode('dd/mm/yy');

                $sheet->getStyle("J1:J{$lastRow}")->getNumberFormat()->setFormatCode('hh:mm');
                $sheet->getStyle("N1:N{$lastRow}")->getNumberFormat()->setFormatCode('hh:mm');
                $sheet->getStyle("P1:P{$lastRow}")->getNumberFormat()->setFormatCode('hh:mm');
                $sheet->getStyle("X1:X{$lastRow}")->getNumberFormat()->setFormatCode('hh:mm');

                $sheet->getColumnDimension("V")->setWidth(50);
                $sheet->getStyle("V1:V{$lastRow}")->getAlignment()->setWrapText(true);

                $sheet->getColumnDimension("S")->setWidth(50);
                $sheet->getStyle("S1:S{$lastRow}")->getAlignment()->setWrapText(true);

                $sheet->getColumnDimension("F")->setWidth(50);
                $sheet->getColumnDimension("G")->setWidth(50);
                $sheet->getStyle("F1:G{$lastRow}")->getAlignment()->setWrapText(true);


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
