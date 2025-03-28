<?php

namespace App\Exports\Partner;

use App\Models\DeclaredEquipment;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DeclaredEquipmentListExport implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function query()
    {
        // Ajuste sua query conforme necessário.
        return $this->data;
    }

    public function headings(): array
    {
        // Defina os cabeçalhos das colunas.
        return [
            'Patrimônio',
            'Tipo',
            'Movimento',
            'Poste Referencia',
            'Nota/OV',
            'Ordem',
            'Rubrica',
            'Município',
            'Empreiteira',
            'Responsável',
            'Informado em',
            'Usuário',
        ];
    }

    public function map($row): array
    {
        if ($ordens = $row->WorkReport->Orders) {
            $ordens = $ordens->pluck('ordem')->implode("\n ");
        } else {
            $ordens = '';
        }


        return [
            $row->patrimony,
            $row->type,
            $row->installed ? 'INSTALAÇÃO' : 'RETIRADA',
            $row->pole,
            $row->WorkReport->Note->note,
            $ordens,
            $row->WorkReport->Note->rubrica,
            $row->WorkReport->Note->lexp,
            $row->WorkReport->Company->name,
            $row->WorkReport->responsible,
            $row->WorkReport->informed_at ? $row->WorkReport->informed_at->format('d/m/Y H:i') : '',
            $row->WorkReport->User?->name,
        ];
    }

    public function chunkSize(): int
    {
        // Define o tamanho dos chunks.
        return 1000;
    }

    public function registerEvents(): array
    {
        // Registre eventos, se necessário.
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Estilo do cabeçalho: azul com texto branco
                $event->sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FFFFFFFF'], // Texto branco
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'FF0000FF', // Fundo azul
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Alinhamento para todas as células
                $event->sheet->getStyle('A1:L' . $event->sheet->getHighestRow())->applyFromArray([
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true, // Permitir quebra de linha
                    ],
                ]);

                // Formatar colunas E e F como numéricas sem casas decimais
                $event->sheet->getStyle('E2:F' . $event->sheet->getHighestRow())->getNumberFormat()
                    ->setFormatCode('0');

                // Formatar coluna K como data/hora no padrão brasileiro
                $event->sheet->getStyle('K2:K' . $event->sheet->getHighestRow())->getNumberFormat()
                    ->setFormatCode('dd/mm/yyyy hh:mm');

                // Ajuste automático da largura das colunas
                foreach (range('A', 'L') as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }

    public function properties(): array
    {
        // Define as propriedades do Excel.
        return [
            'creator'        => 'Seu Nome',
            'lastModifiedBy' => 'Seu Nome',
            'title'          => 'Lista de Equipamentos Declarados',
            'description'    => 'Exportação dos equipamentos declarados',
            'subject'        => 'Equipamentos',
            'keywords'       => 'excel, export, equipamentos',
        ];
    }
}
