<?php

namespace App\Exports\Home;

use App\Custom\Notestatus;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PersonalProductionsExport implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;
    use RegistersEventListeners;

    public function __construct(
        private readonly mixed $query,
        private readonly int $rowEstimate = 0
    ) {
    }

    public function query()
    {
        return $this->query;
    }

    public function chunkSize(): int
    {
        return 3000;
    }

    public function headings(): array
    {
        return [
            'Número da nota',
            'Serviço',
            'Data de despacho',
            'Data de atribuição',
            'Data de conclusão',
            'Tempo para concluir',
            'Tempo parado',
            'Postes utilizados',
            'Situação da produção',
            'Conclusão',
        ];
    }

    public function map($row): array
    {
        return [
            $row->numero_nota ?? '',
            $row->servico ?? '',
            $this->formatarData($row->data_despacho ?? null),
            $this->formatarData($row->data_atribuicao ?? null),
            $this->formatarData($row->data_conclusao ?? null),
            $this->formatarDuracaoEntre($row->data_atribuicao ?? null, $row->data_conclusao ?? null),
            $this->formatarDuracao((int) ($row->tempo_parado_segundos ?? 0)),
            $row->postes_utilizados ?? '',
            $this->situacaoProducao($row->situacao_producao ?? null),
            $row->conclusao ?? '',
        ];
    }

    public function properties(): array
    {
        return [
            'creator' => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title' => 'Histórico de Produções do Usuário',
            'description' => 'Arquivo gerado automaticamente pelo SICODE',
            'subject' => 'Histórico de Produções',
            'company' => config('app.name', 'SICODE'),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $headerRange = "A1:{$highestColumn}1";

                $sheet->freezePane('A2');
                $sheet->setAutoFilter($headerRange);
                $sheet->getRowDimension(1)->setRowHeight(26);

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E293B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $letter = Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($letter)->setWidth(match ($letter) {
                        'A' => 18,
                        'B' => 24,
                        'C', 'D', 'E' => 20,
                        'F', 'G' => 18,
                        'H' => 16,
                        'I' => 22,
                        'J' => 48,
                        default => 16,
                    });
                }

                if ($this->rowEstimate > 0) {
                    $sheet->setCellValue('L1', 'Registros estimados');
                    $sheet->setCellValue('M1', $this->rowEstimate);
                    $sheet->getStyle('L1:M1')->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E0F2FE'],
                        ],
                    ]);
                }
            },
        ];
    }

    private function formatarData(mixed $value): string
    {
        if (!$value) {
            return '';
        }

        return Carbon::parse($value)->format('d/m/Y H:i:s');
    }

    private function formatarDuracaoEntre(mixed $inicio, mixed $fim): string
    {
        if (!$inicio || !$fim) {
            return '';
        }

        return $this->formatarDuracao(Carbon::parse($inicio)->diffInSeconds(Carbon::parse($fim)));
    }

    private function formatarDuracao(int $segundos): string
    {
        if ($segundos <= 0) {
            return '';
        }

        $dias = intdiv($segundos, 86400);
        $segundos %= 86400;
        $horas = intdiv($segundos, 3600);
        $segundos %= 3600;
        $minutos = intdiv($segundos, 60);

        $partes = [];
        if ($dias > 0) {
            $partes[] = "{$dias}d";
        }
        if ($horas > 0) {
            $partes[] = "{$horas}h";
        }
        if ($minutos > 0 || $partes === []) {
            $partes[] = "{$minutos}min";
        }

        return implode(' ', $partes);
    }

    private function situacaoProducao(mixed $status): string
    {
        if ($status === null || $status === '') {
            return '';
        }

        $label = Notestatus::status($status)->status ?? '';

        return strtr($label, [
            'Nao' => 'Não',
            'Atribuido' => 'Atribuído',
        ]);
    }
}
