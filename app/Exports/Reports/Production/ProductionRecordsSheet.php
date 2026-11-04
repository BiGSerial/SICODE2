<?php

namespace App\Exports\Reports\Production;

use App\Exports\Closure\Dashboard\DashboardTheme;
use App\Models\Production;
use App\Services\Reports\Production\ProductionReportAnalytics;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, WithChunkReading, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill, NumberFormat};

/**
 * Aba "Registros" do Excel do Relatório de Produção: uma linha por produção do filtro, da mais recente para a mais antiga
 * (a mesma consulta da lista da tela). Lida em blocos para não carregar o resultado inteiro na memória.
 */
class ProductionRecordsSheet implements FromQuery, WithChunkReading, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithStrictNullComparison, WithTitle
{
    private const DATE = 'dd/mm/yyyy';

    private const TIME = 'hh:mm';

    /** Cada data vem em duas colunas (data e hora) para filtrar e agrupar no Excel; `Data do status` é o `dt_note` gravado na produção. */
    private const HEADINGS = [
        'Fim (data)', 'Fim (hora)', 'Início (data)', 'Início (hora)', 'Data do status', 'Hora do status', 'Usuário', 'Empresa', 'Atividade', 'Nota', 'Material', 'Grupo 2', 'Grupo 5',
        'Depende de órgão externo', 'Meio ambiente', 'RI', 'Parado (dias)', 'Postes/ativos', 'Situação', 'Conclusão',
    ];

    private const WIDTHS = [13, 10, 13, 10, 14, 10, 28, 14, 24, 14, 28, 22, 14, 14, 12, 8, 12, 12, 18, 38];

    public function __construct(private readonly Builder $query)
    {
    }

    public function title(): string
    {
        return 'Registros';
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function chunkSize(): int
    {
        return 2000;
    }

    public function headings(): array
    {
        return self::HEADINGS;
    }

    /** @param Production $row */
    public function map($row): array
    {
        $note = $row->Note;

        return [
            ...$this->dateAndTime($row->completed_at),
            ...$this->dateAndTime($row->att_at),
            ...$this->dateAndTime($row->dt_note),
            $row->User->name ?? 'Desconhecido',
            $row->Company->name ?? '',
            $row->Service->service ?? '',
            $note->note ?? '',
            $note->material ?? '',
            $note->group2 ?? '',
            $note->group5 ?? '',
            $note?->doe ? 'SIM' : 'NÃO',
            $row->ma ? 'SIM' : 'NÃO',
            $row->d5 ? 'SIM' : 'NÃO',
            $row->stopped ? round($row->stopped / 86400, 2) : null,
            $row->postes_u ? ProductionReportAnalytics::postes($row) : null,
            $row->confirmed ? 'Contabilizado' : 'Não contabilizado',
            $row->Analise->conclusion ?? '',
        ];
    }

    /**
     * Data (dia, sem hora) e hora (fração do dia) como números do Excel, cada um no seu formato de coluna.
     *
     * @return array{0: float|null, 1: float|null}
     */
    private function dateAndTime(?CarbonInterface $at): array
    {
        if (!$at) {
            return [null, null];
        }

        return [
            Date::dateTimeToExcel($at->copy()->startOfDay()),
            ($at->hour * 3600 + $at->minute * 60 + $at->second) / 86400,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => self::DATE, 'B' => self::TIME, 'C' => self::DATE, 'D' => self::TIME, 'E' => self::DATE, 'F' => self::TIME,
            'J' => NumberFormat::FORMAT_TEXT, 'Q' => '#,##0.00', 'R' => '#,##0',
        ];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $last  = Coordinate::stringFromColumnIndex(count(self::HEADINGS));

            $sheet->getTabColor()->setRGB(DashboardTheme::BLUE);
            $sheet->getRowDimension(1)->setRowHeight(30);
            $sheet->getStyle("A1:{$last}1")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => DashboardTheme::INK]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);

            foreach (self::WIDTHS as $i => $width) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($width);
            }

            $sheet->getStyle("A2:F{$sheet->getHighestRow()}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N2:P{$sheet->getHighestRow()}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->freezePane('A2');
            $sheet->setAutoFilter("A1:{$last}1");
        }];
    }
}
