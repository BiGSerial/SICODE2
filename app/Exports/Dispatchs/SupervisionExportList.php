<?php

namespace App\Exports\Dispatchs;

use App\Models\WorkReportFlowProduction;
use App\Support\SicodeRules;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\{Exportable, FromCollection, WithEvents, WithHeadings, WithMapping, WithProperties, WithTitle};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};

/**
 * Exportação da lista de despacho de Fiscalização.
 *
 * Cada linha representa exatamente o que a tela mostra: um informe de obra (com as ordens
 * DAQUELE informe), um informe parcial ou uma D5 sem informe (passiva).
 */
class SupervisionExportList implements FromCollection, WithEvents, WithProperties, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    private const HEADER_BG = '0B3C49';
    private const ZEBRA_BG  = 'F3F7F8';
    private const BORDER    = 'D5DEE1';

    private const TYPE_COLORS = [
        'FINAL'   => ['bg' => 'D1FAE5', 'fg' => '065F46'],
        'PARCIAL' => ['bg' => 'FEF3C7', 'fg' => '92400E'],
        'D5'      => ['bg' => 'DBEAFE', 'fg' => '1E40AF'],
    ];

    protected $exports;

    protected $service;

    protected $serviceUuid;

    protected array $selectedWorkReportIds;

    protected array $selectedPartialIds;

    protected array $selectedFiveNoteIds;

    public function __construct($data, $service, array $selectedWorkReportIds = [], array $selectedPartialIds = [], array $selectedFiveNoteIds = [])
    {
        $this->exports               = $data;
        $this->service               = $service;
        $this->serviceUuid           = $service;
        $this->selectedWorkReportIds = $this->ids($selectedWorkReportIds);
        $this->selectedPartialIds    = $this->ids($selectedPartialIds);
        $this->selectedFiveNoteIds   = $this->ids($selectedFiveNoteIds);
    }

    private function ids(array $ids): array
    {
        return collect($ids)->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function collection()
    {
        $notes = $this->exports instanceof Collection
            ? $this->exports
            : $this->exports->with([
                'orders'      => fn ($q) => $q->where('statusSist', 'not like', 'ENT%')->where('statusSist', 'not like', 'ENC%'),
                'WorkReports' => fn ($q) => $q->where('canceled', false),
                'WorkReports.Orders',
                'WorkReports.Adsform',
                'WorkReports.FiveNote',
                'WorkReports.Company',
                'Productions.User',
                'Productions.Company',
                'Productions.WorkReportFlowProductions',
                'Wpas',
                'Partials.Orders',
                'OldAds',
                'FiveNote',
                'FiveNotes',
            ])->get();

        $rows = $notes->map(function ($note) {
            // Cada linha da lista é um contexto específico (informe, parcial ou D5 passiva).
            // Nunca reanexamos todos os informes da nota: isso misturaria ordens de informes diferentes.
            $workReportId = (int) ($note->operational_work_report_id ?? $note->dispatch_work_report_id ?? 0);
            $partialId    = (int) ($note->row_partial_id ?? $note->dispatch_partial_id ?? 0);
            $fiveNoteId   = (int) ($note->passive_d5_id ?? $note->dispatch_five_note_id ?? 0);

            $row = clone $note;
            $row->setAttribute('export_work_report_id', $workReportId);
            $row->setAttribute('export_partial_id', $partialId);
            $row->setAttribute('export_five_note_id', $fiveNoteId);

            $workForm = $workReportId > 0
                ? ($note->WorkReports ?? collect())->firstWhere('id', $workReportId)
                : null;
            $row->setRelation('WorkForm', $workForm);

            if ($workForm) {
                $five = $workForm->FiveNote;
            } elseif ($fiveNoteId > 0) {
                $five = ($note->FiveNotes ?? collect())->firstWhere('id', $fiveNoteId);
            } elseif ($partialId > 0) {
                $five = null;
            } else {
                $five = $note->FiveNote;
            }
            $row->setRelation('FiveNote', $five);

            return $row;
        })->values();

        if (empty($this->selectedWorkReportIds) && empty($this->selectedPartialIds) && empty($this->selectedFiveNoteIds)) {
            return $rows;
        }

        return $rows->filter(function ($row): bool {
            $partialId = (int) $row->export_partial_id;

            if ($partialId <= 0 && (int) $row->export_work_report_id <= 0 && (int) $row->export_five_note_id <= 0) {
                $partialId = (int) ($this->legacyPartial($row)?->id ?? 0);
            }

            return in_array((int) $row->export_work_report_id, $this->selectedWorkReportIds, true)
                || in_array($partialId, $this->selectedPartialIds, true)
                || in_array((int) $row->export_five_note_id, $this->selectedFiveNoteIds, true);
        })->values();
    }

    public function title(): string
    {
        return 'Fiscalização';
    }

    public function headings(): array
    {
        return [
            'Tipo',
            'Nota',
            'Informe',
            'Escopo',
            'Ordens',
            'DD',
            'MMGD',
            'Postes',
            'Empresa do informe',
            'Informado em',
            'Dt ADS',
            'Nº Pedido',
            'Rubrica',
            'Município',
            'Custo (R$)',
            'Fiscalizações',
            'Status / Centro',
            'Dias D5',
            'Situação',
            'Despachado em',
            'Atribuído em',
            'Finalizado em',
        ];
    }

    private function legacyPartial($row)
    {
        return ($row->Partials ?? collect())
            ->where('allow', true)
            ->where('supervision', false)
            ->where('deny', false)
            ->sortByDesc('created_at')
            ->first();
    }

    private function partialFor($row)
    {
        if ($row->WorkForm) {
            return null;
        }

        $partialId = (int) ($row->export_partial_id ?? 0);

        if ($partialId > 0) {
            return ($row->Partials ?? collect())->firstWhere('id', $partialId);
        }

        // Linha de D5 passiva não tem parcial; só o formato antigo (sem contexto) cai no último parcial.
        return (int) ($row->export_five_note_id ?? 0) > 0 ? null : $this->legacyPartial($row);
    }

    public function map($row): array
    {
        $workForm    = $row->WorkForm;
        $partial     = $this->partialFor($row);
        $d5          = $row->FiveNote;
        $isPassiveD5 = !$workForm && !$partial && (int) ($row->export_five_note_id ?? 0) > 0;

        $orders = $workForm?->Orders
            ?? $partial?->Orders
            ?? ($isPassiveD5 ? ($row->orders ?? collect()) : collect());

        $production = ($row->Productions ?? collect())
            ->where('service_id', $this->serviceUuid)
            ->sortByDesc('created_at')
            ->first();

        [$type, $typeLabel, $reference] = match (true) {
            (bool) $partial  => ['PARCIAL', 'Parcial', '#' . $partial->id],
            $isPassiveD5     => ['D5', 'D5', $d5?->note_d5 ? 'D5 ' . $d5->note_d5 : 'D5 #' . $d5?->id],
            (bool) $workForm => ['FINAL', 'Final', '#' . $workForm->id],
            default          => ['FINAL', 'Final', '---'],
        };

        if ($workForm && $d5 && $d5->is_completed && !$d5->is_supervisioned) {
            $typeLabel = 'Final + D5';
        }

        $scope = $workForm
            ? collect($workForm->finalScopeBadges())->pluck('label')->implode(' / ')
            : '';

        $ads     = $workForm?->Adsform;
        $oldAds  = $row->OldAds?->last();
        $adsDate = $oldAds?->date ?? $ads?->created_at;
        $adsCell = $adsDate ? $this->excelDate($adsDate) : '---';
        $adsNote = $ads?->tacit ? 'TÁCITO' : '';

        $fiscalizations = ($row->Productions ?? collect())
            ->where('service_id', $this->serviceUuid)
            ->filter(function ($production) use ($workForm) {
                $workReportId = (int) ($workForm?->id ?? 0);

                return $workReportId === 0
                    ? true
                    : $production->WorkReportFlowProductions
                        ->where('work_report_id', $workReportId)
                        ->where('stage', WorkReportFlowProduction::STAGE_FISCALIZATION)
                        ->where('is_current', true)
                        ->isNotEmpty();
            });
        $fiscalizationValue = $fiscalizations->count()
            ? $fiscalizations->count() . ' - ' . ($fiscalizations->first()?->User?->name ?? '--')
            : '--';

        $d5Days = $d5?->completed_at
            ? Carbon::parse($d5->completed_at)->startOfDay()->diffInDays(Carbon::now())
            : '---';

        $informedAt = $workForm?->informed_at ?? $partial?->created_at;

        $this->typeByRow[] = $type;

        return [
            $typeLabel,
            $d5?->is_completed && !$d5?->is_supervisioned ? 'D5 ' . $row->note : $row->note,
            $reference,
            $scope !== '' ? $scope : '---',
            $orders->isNotEmpty() ? $orders->pluck('ordem')->filter()->implode("\n") : '---',
            SicodeRules::dispatchDdFor($row, $this->serviceUuid, $production) ?? '',
            $row->mmgd ? 'MMGD' : '---',
            $row->postes ?? '---',
            $workForm?->Company?->name ?? $partial?->company?->name ?? '---',
            $informedAt ? $this->excelDate($informedAt) : '---',
            $adsNote ? ($adsDate ? Carbon::parse($adsDate)->format('d/m/Y H:i') . ' (TÁCITO)' : 'TÁCITO') : $adsCell,
            mb_strtoupper((string) ($row->numPedido ?? '')),
            $row->rubrica ?? '---',
            $row->lexp ?? '---',
            (float) $orders->sum('service_cost'),
            $fiscalizationValue,
            ($row->nstats ?? '---') . ' / ' . ($row->centerjob ?? '---'),
            $d5Days,
            $row->pze_parecer ?? 'DESCONHECIDO',
            $this->productionDate($production?->dispatch_at),
            $this->productionDate($production?->att_at),
            $this->productionDate($production?->completed_at),
        ];
    }

    /** @var array<int, string> tipo (FINAL/PARCIAL/D5) por linha, na ordem de map() */
    private array $typeByRow = [];

    private function excelDate($date)
    {
        return ExcelDate::dateTimeToExcel(Carbon::parse($date));
    }

    private function productionDate($date)
    {
        return $date ? $this->excelDate($date) : '---';
    }

    public function properties(): array
    {
        return [
            'creator'        => 'Sicode',
            'lastModifiedBy' => 'Sicode',
            'title'          => 'Lista de despacho - Fiscalização',
            'description'    => 'Informes, parciais e D5 aguardando despacho de Fiscalização',
            'subject'        => 'Fiscalização',
            'keywords'       => 'fiscalização, despacho, sicode',
            'category'       => 'Fiscalização',
            'manager'        => 'Sicode',
            'company'        => 'Sicode',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();

                // Larguras fixas (autoSize com quebra de linha estoura em células com várias ordens).
                $widths = [
                    'A' => 13, 'B' => 17, 'C' => 15, 'D' => 14, 'E' => 17, 'F' => 13, 'G' => 9, 'H' => 9,
                    'I' => 30, 'J' => 13, 'K' => 19, 'L' => 24, 'M' => 16, 'N' => 22, 'O' => 14, 'P' => 26,
                    'Q' => 15, 'R' => 9, 'S' => 24, 'T' => 17, 'U' => 17, 'V' => 17,
                ];

                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                $range = 'A1:' . $lastCol . $lastRow;
                $sheet->getStyle($range)->applyFromArray([
                    'font'      => ['name' => 'Calibri', 'size' => 10],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::BORDER]]],
                ]);
                $sheet->getStyle('A2:' . $lastCol . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('P2:P' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Cabeçalho
                $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::HEADER_BG]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                if ($lastRow < 2) {
                    return;
                }

                // Formatos
                $sheet->getStyle('B2:B' . $lastRow)->getNumberFormat()->setFormatCode('@');
                $sheet->getStyle('E2:F' . $lastRow)->getNumberFormat()->setFormatCode('@');
                $sheet->getStyle('J2:J' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $sheet->getStyle('K2:K' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
                $sheet->getStyle('O2:O' . $lastRow)->getNumberFormat()->setFormatCode('"R$" #,##0.00');
                $sheet->getStyle('T2:V' . $lastRow)->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');

                // Zebra
                for ($r = 3; $r <= $lastRow; $r += 2) {
                    $sheet->getStyle('A' . $r . ':' . $lastCol . $r)->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::ZEBRA_BG);
                }

                // Etiqueta colorida do tipo (Final / Parcial / D5)
                foreach ($this->typeByRow as $i => $type) {
                    $colors = self::TYPE_COLORS[$type] ?? null;

                    if (!$colors) {
                        continue;
                    }
                    $cell = 'A' . ($i + 2);
                    $sheet->getStyle($cell)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => $colors['fg']]],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colors['bg']]],
                    ]);
                }

                $sheet->freezePane('C2');
                $sheet->setAutoFilter('A1:' . $lastCol . $lastRow);
                $sheet->getTabColor()->setRGB(self::HEADER_BG);
                $sheet->setShowGridlines(false);
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)->setFitToHeight(0);
                $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
            },
        ];
    }
}
