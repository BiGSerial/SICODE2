<?php

namespace App\Exports\Engineers;

use App\Models\Viability;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ResumeViabilityQueryExport implements FromQuery, WithMapping, WithHeadings, WithProperties, WithEvents, WithChunkReading, ShouldQueue
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
            $viab->Note->lexp,
            optional($viab->hired_at)->format('d/m/Y'),
            optional($viab->sended_at)->format('d/m/Y'),
            $viab->sended_at
                ? $viab->sended_at->addDays(7 + $viab->getDays())->format('d/m/Y')
                : '',
            optional($viab->tacit_at)->format('d/m/Y'),
            $viab->tacit_at
                ? $viab->tacit_at->addDays(7)->format('d/m/Y')
                : '',
            optional($viab->Justification?->created_at)->format('d/m/Y'),
            // status
            match (true) {
                !$viab->Justification => 'Não Justificado',
                $viab->Justification->granted && !$viab->Justification->dismissed => 'Deferido',
                !$viab->Justification->granted && $viab->Justification->dismissed => 'Indeferido',
                default => 'Pendente',
            },
            number_format($viab->value, 2, ',', '.'),
            number_format($viab->value * 0.01, 2, ',', '.'),
            $viab->Engineer?->name,
        ];
    }

    /**
     * WithHeadings: primeira linha de cabeçalhos.
     */
    public function headings(): array
    {
        return [
            'Note/OV','Ordem/DR','Rubrica','Município','Contratado Em','Enviado Em',
            'PrazoViabilidade','Vencido Em','Prazo Justificativa','Justificado Em',
            'Resultado','Valor MOA','Penalidade','Responsável',
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

                // cabeçalho azul
                $sheet->getStyle('A1:N1')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color'    => ['argb' => 'FF0000FF'],
                    ],
                    'font' => [
                        'color' => ['argb' => 'FFFFFFFF'],
                    ],
                ]);

                // formata colunas numéricas e moeda
                $sheet->getStyle('A')->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('B')->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('L')->getNumberFormat()->setFormatCode('R$ #.##0,00');
                $sheet->getStyle('M')->getNumberFormat()->setFormatCode('R$ #.##0,00');

                // alinhamento e autosize
                $sheet->getStyle('A1:Z1000')->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                      ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                foreach (range('A', 'Z') as $col) {
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
