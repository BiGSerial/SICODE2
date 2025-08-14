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
            'Tipo',
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

        // Pega a medida “vigente” (medProtest)
        // A view usa sortBy('statusSist') e depois sortByDesc('med_id')
        $medProtest = optional($protest->medProtests)
            ->sortBy('statusSist')
            ->sortByDesc('med_id')
            ->first();

        // Pega o último andamento do usuário (Assignment)
        $assignmentUser = optional($medProtest?->assignments)->where('user', true)->last();

        // Coluna "M" (olho)
        $colM = (!$medProtest?->completed && $medProtest?->needsConfirmation) ? 'SIM' : '';

        // Coluna "Tipo"
        $tipoNota = $protest->tipoNota ?? '';

        // Coluna "Cod"
        $codMedida = $medProtest?->codMedida ?? '';

        // Coluna "TipoReclamacao"
        $tipoReclamacao = $medProtest?->txtCodCodificacao ?? '';

        // Coluna "CausaRaiz"
        $causaRaiz = $medProtest?->txtCodMedida ?? '';

        // Coluna "Origem" (com a lógica de extração corrigida)
        $origem = $protest->descricao ?? '';
        $parts = explode('Tipo de Solicitante: ', $origem);
        if (count($parts) > 1) {
            $origem = $parts[1];
        } else {
            $parts = explode('Nota de Atendimento ', $origem);
            if (count($parts) > 1) {
                $origem = $parts[1];
            }
        }

        // Coluna "Município"
        $municipio = $protest->cidade ?? '';

        // Coluna "Abertura"
        $dtAbertura = optional($protest->dtAberturaNota)->format('d/m/Y') ?? '';

        // Coluna "Desejada"
        $dtDesejada = optional($protest->dtConclusaoDesej)->format('d/m/Y') ?? '';

        // Coluna "Status" (Pendente / Em Andamento / Concluído)
        if (!$assignmentUser) {
            $statusAndamento = 'Pendente';
        } elseif ($assignmentUser->completed) {
            $statusAndamento = 'Concluído';
        } else {
            $statusAndamento = 'Em Andamento';
        }

        // Retorno do array na ordem exata da sua tabela
        return [
            $colM,
            $protest->nota ?? '',
            $tipoNota,
            $codMedida,
            $tipoReclamacao,
            $causaRaiz,
            mb_strtoupper($origem), // Converte para maiúsculas como na sua view
            $municipio,
            $dtAbertura,
            $dtDesejada,
            $statusAndamento,
            // Deixe este campo em branco ou adicione a lógica se houver mais colunas
            '',
        ];
    }



    public function registerEvents(): array
    {
        // A heurística de largura pode ser pesada para grandes exports.
        // Você pode desabilitá-la para mais de X registros para evitar timeouts.
        // Exemplo: if ($this->query()->count() > 10000) { return []; }

        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();

                // Estilo do cabeçalho
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

                // --- Heurística de largura das colunas ---
                $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                $headings = $this->headings(); // Use o método headings para obter os títulos

                for ($colIndex = 1; $colIndex <= $highestColumnIndex; $colIndex++) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);

                    // Pega o título da coluna
                    $headerTitle = $headings[$colIndex - 1] ?? '';

                    // Larguras padrão
                    $width = 20; // Largura padrão
                    $autoSize = true;

                    // Heurística de largura baseada nos títulos e tipo de dado
                    // Ajuste os valores conforme o necessário
                    switch ($headerTitle) {
                        case 'Nota':
                        case 'Tipo':
                        case 'Cod':
                        case 'Abertura':
                        case 'Desejada':
                        case 'Status':
                            $width = 15;
                            $autoSize = false;
                            break;
                        case 'Municipio':
                            $width = 25;
                            $autoSize = false;
                            break;
                        case 'Origem':
                        case 'TipoReclamacao':
                        case 'CausaRaiz':
                            $width = 35;
                            $autoSize = false;
                            break;
                        default:
                            $autoSize = true; // Use auto-size para outras colunas
                            break;
                    }

                    if ($autoSize) {
                        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                    } else {
                        $sheet->getColumnDimension($colLetter)->setWidth($width);
                    }
                }
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
