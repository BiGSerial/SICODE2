<?php

namespace App\Exports\Reports;

use App\Models\City;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProductionFullExport implements FromArray, WithEvents, WithProperties, WithHeadings
{
    use Exportable;

    public $exports;
    protected $userName;


    public function __construct(array $data, $userName = 'Usuario Desconhecido')
    {
        $this->exports = $data;
        $this->userName = $userName ?? 'Usuario Desconhecido';
    }

    public function array(): array
    {
        return $this->exports;
    }

    public function headings(): array
    {
        return [
            'Nota', 'Rubrica', 'Municipio', 'Serviço', 'Empreiteira', 'DataStatus', 'HoraStatus', 'Despachado_Em',
            'Despachado_Hora', 'Finalizado_Em', 'Finalizado_Hora', 'Tempo_reacao', 'Tempo_Execucao', 'Status', 'Fiscalização por Fotos da Parceira'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
            // Define o estilo para a primeira linha
            $event->sheet->getStyle('A1:O1')->applyFromArray([
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
            $event->sheet->getStyle('A')->getNumberFormat()->setFormatCode('#,##0');

            // Formata as colunas F, H, J para data (d/m/Y)
            $event->sheet->getStyle('F')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            $event->sheet->getStyle('H')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            $event->sheet->getStyle('J')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

            // Formata as colunas G, I, K para hora (H:i:s)
            $event->sheet->getStyle('G')->getNumberFormat()->setFormatCode('HH:mm:ss');
            $event->sheet->getStyle('I')->getNumberFormat()->setFormatCode('HH:mm:ss');
            $event->sheet->getStyle('K')->getNumberFormat()->setFormatCode('HH:mm:ss');

            // Define o tamanho automático para as colunas
            $event->sheet->autoSize();
            },
        ];
    }


    public function properties(): array
    {
        return [
            'creator'        => $this->userName,
            'lastModifiedBy' => $this->userName,
            'title'          => 'Relatorio Automatico Sicode',
            'description'    => 'Arquivo gerado automaticamente via SICODE',
            'subject'        => 'Relatorios',
            'manager'        => 'Joao Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }

}
