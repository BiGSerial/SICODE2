<?php

namespace App\Exports\Partner;

use App\Helpers\DaysLeft;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportViabilityToExcel implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles,
    WithEvents
{
    /** @var \Illuminate\Support\Collection */
    protected $rows;

    public function __construct(Collection $rows)
    {
        // garante relações mínimas pro mapeamento sem N+1
        $this->rows = $rows->loadMissing([
            'Note',
            'Note.Orders',
            'Note.Files',
            'Note.City',
            'Company',
        ]);
    }

    /**
     * Dados crus.
     */
    public function collection()
    {
        return $this->rows;
    }

    /**
     * Cabeçalhos da planilha.
     */
    public function headings(): array
    {
        return [
            'Nota',
            'Ordens',
            'Criticidade',
            'Cliente',
            // 'Qtd Arquivos',
            'Contratado',
            'Recebido',
            'Prazo Viabilidade',
            'Prazo Obra',
            'Rubrica',
            'Material',
            'Região',
            'Município',
            'Status',
            'Empreiteira',
        ];
    }

    /**
     * Mapeia cada linha (espelha a tabela da view).
     */
    public function map($viab): array
    {
        // ORDENS filtradas (ignora ENT*/ENC*)
        $ordersValidas = $viab->Orders?->pluck('ordem')
                        ->implode("\n ");

        // Prazo Viabilidade = sended_at + getDays() + 7
        $prazoViab = null;
        if ($viab->sended_at) {
            $prazoViab = $viab->sended_at
                ->copy()
                ->addDays($viab->getDays() + 7)
                ->format('d/m/Y');
        }

        // Prazo Obra (helper DaysLeft)
        $prazoObra = null;
        if ($viab->Note) {
            $lastDateHelper = new DaysLeft($viab->Note);
            $prazoObra = $lastDateHelper->getLastDate();
        }

        // Status bonitinho
        $statusInfo = \App\Custom\Viabilitiesstatus::status($viab->status);
        $statusTxt  = $statusInfo->status ?? '';

        return [
            // Nota
            $viab->Note?->note,

            // Ordens válidas
            $ordersValidas ?: '---',

            // Criticidade
            $viab->Note->txpriority ?? 'Normal',

             // Cliente
            $viab->Note->client ?? 'Normal',

            // Qtd Arquivos (da nota)
            // $viab->Note->Files->count(),

            // Contratado
            $viab->hired ? 'SIM' : 'NÃO',

            // Recebido
            optional($viab->sended_at)->format('d/m/Y'),

            // Prazo Viabilidade
            $prazoViab,

            // Prazo Obra
            $prazoObra,

            // Rubrica
            $viab->Note->rubrica ?? '',

            // Material
            $viab->Note->material ?? '',

            // Região
            $viab->note->City?->regiao ?? '',

            // Município
            $viab->Note->lexp ?? $viab->Note->City?->cidade ?? '',

            // Status
            $statusTxt,

            // Empreiteira
            $viab->Company?->name ?? '',
        ];
    }

    /**
     * Negrito no header via WithStyles.
     * Cor de fundo vamos aplicar em AfterSheet (limitação da interface).
     */
    public function styles(Worksheet $sheet)
    {
        // Linha 1 inteira em bold + texto branco:
        $sheet->getStyle('1:1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');

        return [
            // Podemos devolver array vazio porque já configuramos direto.
        ];
    }

    /**
     * Eventos extras (AfterSheet) pra:
     * - Pintar fundo azul só na primeira linha
     * - Ajustar alinhamentos básicos se quiser
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $lastColumnIndex = count($this->headings());
                $lastColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumnIndex);

                // === Estilos em batch para melhor performance ===
                $sheet->getStyle("A1:{$lastColumnLetter}1")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F4E79'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                ]);

                // Coluna B (Ordens) com wrap text
                $sheet->getStyle("B2:B{$highestRow}")->applyFromArray([
                    'numberFormat' => ['formatCode' => '0'],
                    'alignment' => [
                        'wrapText' => true,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    ],
                ]);

                // Coluna D (Qtd Arquivos) como inteiro centralizado
                $sheet->getStyle("D2:D{$highestRow}")->applyFromArray([
                    'numberFormat' => ['formatCode' => '0'],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Altura da linha do cabeçalho
                $sheet->getRowDimension(1)->setRowHeight(22);
            },
        ];
    }

}
