<?php

namespace App\Exports\Workreports;

use App\Models\WorkReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\{Exportable, FromQuery, RegistersEventListeners, WithChunkReading, WithEvents, WithHeadings, WithMapping, WithProperties};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Conditional, Fill};

class WorkreportsListExport implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;
    use RegistersEventListeners;

    private const HEADER_COLOR = '0B3C49';
    private const BORDER_COLOR = 'D8E2E7';
    private const ZEBRA_COLOR  = 'F4F8F9';

    /** @var array<string,mixed> */
    protected array $filters;

    /** @var array<int, string> Auditoria completa do aceite, indexada pela linha do Excel. */
    private array $acceptanceAuditByRow = [];

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = WorkReport::query()->with([
            'Note.Files',
            'Note.Adsform',
            'Note.OldAds',
            'Orders',
            'Equipment',
            'Company',
            'User:id,name,email',
        ]);

        $dateBy  = $this->filters['dateBy'] ?? 'first_informed';
        $dateIn  = $this->filters['date_in'] ?? null;
        $dateOut = $this->filters['date_out'] ?? null;

        if ($dateIn || $dateOut) {
            $dateColumn = $this->resolveDateColumn($dateBy);

            if ($dateIn) {
                $query->whereDate($dateColumn, '>=', $dateIn);
            }

            if ($dateOut) {
                $query->whereDate($dateColumn, '<=', $dateOut);
            }
        }

        $search = trim((string) ($this->filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereRelation('Note', 'note', 'like', "%{$search}%")
                    ->orWhereRelation('Orders', 'ordem', 'like', "%{$search}%");
            });
        }

        $multiSearch = $this->filters['multiSearch'] ?? [];

        if (!empty($multiSearch)) {
            $query->where(function ($q) use ($multiSearch) {
                $q->whereRelation('Note', function ($sq) use ($multiSearch) {
                    $sq->whereIn('note', $multiSearch);
                })->orWhereRelation('Orders', function ($sq) use ($multiSearch) {
                    $sq->whereIn('ordem', $multiSearch);
                });
            });
        }

        $filters = $this->filters['filters'] ?? [];

        if (!empty($filters['company'])) {
            $query->whereIn('company_id', $filters['company']);
        }

        if (!empty($filters['city'])) {
            $query->whereRelation('Note', function ($q) use ($filters) {
                $q->whereIn('lexp', $filters['city']);
            });
        }

        if (!empty($filters['rubrica'])) {
            $query->whereRelation('Note', function ($q) use ($filters) {
                $q->whereIn('rubrica', $filters['rubrica']);
            });
        }

        return $query->orderByDesc('created_at');
    }

    public function map($row): array
    {
        $ads  = null;
        $note = $row->Note;

        if ($note?->Adsform) {
            $adsForm = $note->Adsform;
            $ads     = ($adsForm->tacit ? $adsForm->tacit_delivered_at : $adsForm->created_at)?->format('d/m/Y');
        } elseif ($note?->OldAds?->isNotEmpty()) {
            $ads = $note->OldAds->last()->date->format('d/m/Y');
        }

        [$acceptanceSummary, $acceptanceAudit] = $this->acceptanceExportValues($row);
        $this->acceptanceAuditByRow[]          = $acceptanceAudit;

        return [
            $note?->note,
            $row->Orders ? implode("\n ", $row->Orders->pluck('ordem')->toArray()) : '',
            $note?->rubrica,
            $row->canceled ? 'CANCELADO' : 'NORMAL',
            $row->Equipment ? $row->Equipment->count() : '',
            $row->changes ? 'SIM' : 'NAO',
            $row->team,
            $row->date ? $row->date->format('d/m/Y') : '',
            $row->informed_at ? $row->informed_at->format('d/m/Y') : $row->created_at->format('d/m/Y'),
            $ads,
            $row->responsible,
            $row->observation,
            $row->Company?->name,
            $acceptanceSummary,
        ];
    }

    public function headings(): array
    {
        return [
            'Note',
            'Ordens',
            'Rubrica',
            'Status Informe',
            'Equipamentos',
            'Alteracoes',
            'Equipe WPA',
            'Data da Execucao',
            'Data da Entrega',
            'Data entrega ADS',
            'Responsavel',
            'Observacoes',
            'Empreiteira',
            'Aceite do informe',
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE',
            'lastModifiedBy' => 'SICODE',
            'title'          => 'Relatorio Automatico Sicode',
            'description'    => 'Arquivo gerado automaticamente via SICODE',
            'subject'        => 'Relatorios',
            'manager'        => 'Joao Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet     = $event->sheet->getDelegate();
                $lastRow   = max(1, $sheet->getHighestDataRow());
                $dataRange = 'A2:N' . $lastRow;

                $sheet->getStyle('A1:N1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 10,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::HEADER_COLOR],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                ]);

                $sheet->getStyle($dataRange)->applyFromArray([
                    'font'      => ['name' => 'Calibri', 'size' => 10, 'color' => ['rgb' => '203040']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => self::BORDER_COLOR]]],
                ]);
                $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C2:J' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B2:B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
                $sheet->getStyle('K2:N' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('L2:L' . $lastRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('H:H')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $sheet->getStyle('I:I')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $sheet->getStyle('J:J')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                $widths = [
                    'A' => 16, 'B' => 20, 'C' => 17, 'D' => 17, 'E' => 13, 'F' => 13, 'G' => 18,
                    'H' => 15, 'I' => 15, 'J' => 15, 'K' => 24, 'L' => 38, 'M' => 30, 'N' => 42,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:N' . $lastRow);
                $sheet->setShowGridlines(false);
                $sheet->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

                if ($lastRow >= 2) {
                    $zebra = new Conditional();
                    $zebra->setConditionType(Conditional::CONDITION_EXPRESSION)
                        ->addCondition('MOD(ROW(),2)=0');
                    $zebra->getStyle()->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB(self::ZEBRA_COLOR);
                    $sheet->getStyle($dataRange)->setConditionalStyles([$zebra]);
                }

                // A planilha mostra um resumo de uma linha; os metadados e o histórico
                // completos ficam disponíveis no comentário da célula, sem aumentar a altura da lista.
                $sheet->getStyle('N:N')->getAlignment()->setWrapText(false)->setShrinkToFit(false);

                foreach ($this->acceptanceAuditByRow as $index => $audit) {
                    if ($audit === '') {
                        continue;
                    }

                    $sheet->getComment('N' . ($index + 2))
                        ->getText()
                        ->createText($audit);
                }
            },
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    private function resolveDateColumn(string $dateBy)
    {
        if ($dateBy === 'informed_at' || $dateBy === 'created_at') {
            return $dateBy;
        }

        return DB::raw('COALESCE(informed_at, created_at)');
    }

    /**
     * Retorna um resumo compacto para a célula e a auditoria completa para o comentário.
     * O comentário conserva também o histórico de reaceites em acceptance_meta.history.
     */
    private function acceptanceExportValues(WorkReport $workReport): array
    {
        $meta = is_array($workReport->acceptance_meta) ? $workReport->acceptance_meta : [];

        if (!$workReport->acceptance_accepted && empty($meta)) {
            return ['NÃO REGISTRADO', ''];
        }

        $current     = is_array($meta['current'] ?? null) ? $meta['current'] : $meta;
        $signature   = is_array($current['signature'] ?? null) ? $current['signature'] : [];
        $signedName  = $signature['signed_name'] ?? $current['acceptance_name'] ?? $workReport->acceptance_name ?? '---';
        $userName    = $current['app_user']['name'] ?? $workReport->User?->name ?? '---';
        $signedAt    = $signature['signed_at'] ?? $current['acceptance_at'] ?? $workReport->acceptance_at;
        $hash        = (string) ($signature['hash'] ?? '---');
        $date        = $signedAt ? Carbon::parse($signedAt)->format('d/m/Y H:i:s') : '---';
        $hashPreview = $hash !== '---' && strlen($hash) > 16 ? substr($hash, 0, 16) . '…' : $hash;
        $status      = $workReport->acceptance_accepted ? 'ACEITO' : 'NÃO ACEITO';

        $summary = sprintf(
            '%s | Assinado: %s | Usuário: %s | Em: %s | Hash: %s',
            $status,
            $signedName,
            $userName,
            $date,
            $hashPreview,
        );

        $audit = "Aceite do informe\n"
            . "Nome assinado: {$signedName}\n"
            . "Usuário SICODE: {$userName}\n"
            . "Data e hora: {$date}\n"
            . "Hash da assinatura: {$hash}\n\n"
            . "Metadados e histórico completos:\n"
            . json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [$summary, $audit];
    }
}
