<?php

namespace App\Exports\Engineers;

use App\Models\Viability;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
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
    protected string $exportBy;
    protected string $amountBasis;

    public function __construct($baseQuery = null, string $exportBy = 'note', string $amountBasis = 'moa')
    {
        // se não vier, usa todo o modelo Viability
        $this->baseQuery = $baseQuery ?: Viability::query();
        $this->exportBy = in_array($exportBy, ['note', 'order'], true) ? $exportBy : 'note';
        $this->amountBasis = in_array($amountBasis, ['moa', 'mop'], true) ? $amountBasis : 'moa';
    }

    /**
     * FromQuery: devolve o query builder já com filtros.
     */
    public function query()
    {
        // O export chama query() mais de uma vez (count/chunks). Clonar evita
        // acumular joins repetidos e colisão de alias (ex.: "ov").
        $query = clone $this->baseQuery;

        if ($this->exportBy === 'order') {
            return $query
                ->leftJoin('order_viability as ov', 'ov.viability_id', '=', 'viabilities.id')
                ->leftJoin('orders as o', 'o.id', '=', 'ov.order_id')
                ->select('viabilities.*', 'o.ordem as export_ordem', DB::raw('COALESCE(o.service_cost, 0) as export_service_cost'))
                ->with(['Note', 'Justification', 'Engineer']);
        }

        return $query->with(['Note', 'Orders', 'Justification', 'Engineer']);
    }

    /**
     * WithMapping: define como cada modelo vira linha simples de array.
     */
    public function map($viab): array
    {
        $orderRef = $this->exportBy === 'order'
            ? ($viab->export_ordem ?? optional($viab->Order)->ordem ?? '')
            : ($viab->Orders?->pluck('ordem')->implode("\n") ?? '');

        $serviceCostByOrder = $this->exportBy === 'order'
            ? (float) ($viab->export_service_cost ?? 0)
            : null;

        $baseAmount = $this->amountBasis === 'mop'
            ? ($this->exportBy === 'order'
                ? $serviceCostByOrder
                : (float) ($viab->Orders?->sum(fn ($order) => (float) ($order->service_cost ?? 0)) ?? 0))
            : (float) ($viab->value ?? 0);

        $row = [
            $viab->Note?->note,
            $orderRef,
        ];

        if ($this->exportBy === 'order') {
            $row[] = number_format($serviceCostByOrder ?? 0, 2, ',', '.');
        }

        $row = array_merge($row, [
            $viab->Note?->rubrica,
            $viab->Note?->lexp,
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
            $this->amountBasisLabel(),
            number_format($baseAmount, 2, ',', '.'),
            number_format($baseAmount * 0.01, 2, ',', '.'),
            $viab->Engineer?->name,
        ]);

        return $row;
    }

    /**
     * WithHeadings: primeira linha de cabeçalhos.
     */
    public function headings(): array
    {
        $headings = [
            'Note/OV','Ordem/DR','Rubrica','Município','Contratado Em','Enviado Em',
            'PrazoViabilidade','Vencido Em','Prazo Justificativa','Justificado Em',
            'Resultado','Base Monetária','Valor ' . strtoupper($this->amountBasis),'Penalidade','Responsável',
        ];

        if ($this->exportBy === 'order') {
            array_splice($headings, 2, 0, ['Service Cost Ordem']);
        }

        return $headings;
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
                $lastColumn = $this->exportBy === 'order' ? 'P' : 'O';
                $valueColumn = $this->exportBy === 'order' ? 'N' : 'M';
                $penaltyColumn = $this->exportBy === 'order' ? 'O' : 'N';

                // cabeçalho azul
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
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
                if ($this->exportBy === 'order') {
                    $sheet->getStyle('C')->getNumberFormat()->setFormatCode('R$ #.##0,00');
                }
                $sheet->getStyle($valueColumn)->getNumberFormat()->setFormatCode('R$ #.##0,00');
                $sheet->getStyle($penaltyColumn)->getNumberFormat()->setFormatCode('R$ #.##0,00');

                // quebra de linha na coluna B
                $sheet->getStyle('B')->getAlignment()->setWrapText(true);

                // alinhamento
                $sheet->getStyle("A1:{$lastColumn}1000")->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                      ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // autosize apenas para as colunas necessárias
                $autoSizeColumns = $this->exportBy === 'order'
                    ? ['A', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P']
                    : ['A', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];

                foreach ($autoSizeColumns as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // largura fixa para coluna B devido ao wrap text
                $sheet->getColumnDimension('B')->setWidth(20);
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

    private function amountBasisLabel(): string
    {
        return $this->amountBasis === 'mop'
            ? 'MOP - Mão de Obra Prevista'
            : 'MOA - Mão de Obra em Aberto';
    }
}
