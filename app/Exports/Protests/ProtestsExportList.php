<?php

namespace App\Exports\Protests;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithStyles;

class ProtestsExportList implements FromQuery, WithHeadings, WithMapping, WithProperties, WithEvents
{
    use Exportable;

    public $data;

    public function __construct($data)
    {

        $this->data = $data;
    }

    public function query()
    {

        return $this->data->orderBy('dtConclusaoDesej');
    }

    public function headings(): array
    {
        // Mesmas colunas da view
        return [
            'M',                 // Acompanhamento (olhinho na view)
            'Nota',
            'Cod',               // codMedida do medProtest selecionado
            'TipoReclamacao',    // txtCodCodificacao
            'CausaRaiz',         // txtCodMedida
            'Origem',
            'Municipio',
            'Abertura',
            'Desejada',
            'Status',            // Pendente / Em Andamento / Concluído (coluna “Status” da view)
        ];
    }

    public function map($protest): array
    {
        // === LÓGICA IGUAL À VIEW ===

        // pega a medida “vigente” (mesma ordem da view)
        $medProtest = optional($protest->medProtests)
            ->sortBy('statusSist')
            ->sortByDesc('med_id')
            ->first();

        $assignmentUser = optional($medProtest?->assignments)->where('user', true)->last();

        // Coluna M (olho): mostra SIM quando existe needsConfirmation e ainda não foi concluído
        $colM = (!$medProtest?->completed && $medProtest?->needsConfirmation) ? 'SIM' : '';

        // Cod
        $codMedida = $medProtest?->codMedida;

        // TipoReclamacao / CausaRaiz
        $tipoReclamacao = $medProtest?->txtCodCodificacao;
        $causaRaiz      = $medProtest?->txtCodMedida;

        // Origem (mesma extração da view)
        $origem = $protest->descricao ?? '';
        $parts  = explode('Tipo de Solicitante: ', $origem);
        if (!(count($parts) > 1)) {
            $parts = explode('Nota de Atendimento ', $origem);
        }
        $origem = count($parts) > 1 ? $parts[1] : ($protest->descricao ?? '');

        // Datas
        $dtAbertura = optional($protest->dtAberturaNota)?->format('d/m/Y');
        $dtDesejada = optional($protest->dtConclusaoDesej)?->format('d/m/Y');

        // Status (coluna da direita na view — andamento do assignment)
        if (!$assignmentUser) {
            $statusAndamento = 'Pendente';
        } elseif ($assignmentUser->completed) {
            $statusAndamento = 'Concluído';
        } else {
            $statusAndamento = 'Em Andamento';
        }

        return [
            $colM,
            $protest->nota,
            $codMedida,
            $tipoReclamacao,
            $causaRaiz,
            strtoupper($origem),
            $protest->cidade,
            $dtAbertura,
            $dtDesejada,
            $statusAndamento,
        ];
    }



    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                // Auto-size all columns
                for ($i = 1; $i <= $highestColumnIndex; $i++) {
                    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Set minimum width for text columns (assuming last two columns are description fields)
                if ($highestColumnIndex >= 2) {
                    $descColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($highestColumnIndex - 1);
                    $resumeColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($highestColumnIndex);
                    $sheet->getColumnDimension($descColumn)->setWidth(30); // descricao
                    $sheet->getColumnDimension($resumeColumn)->setWidth(30); // resume
                }

                // Style the header row (row 1) - blue background, white bold text
                $headerRange = 'A1:' . $highestColumn . '1';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0066CC'],
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                ]);
            },
        ];
    }

    public function properties(): array
    {
        return [
            'creator' => 'SICODE',
            'lastModifiedBy' => 'SICODE',
            'title' => 'Protests Export List',
            'description' => 'Export of protests data',
            'subject' => 'Protests Data',
            'keywords' => 'protests, export, data',
            'category' => 'Exports',
        ];
    }
}
