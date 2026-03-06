<?php

namespace App\Exports\Workreports;

use App\Models\WorkReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class WorkreportsListExport implements FromQuery, WithEvents, WithProperties, WithHeadings, WithChunkReading, WithMapping
{
    use Exportable;
    use RegistersEventListeners;

    /** @var array<string,mixed> */
    protected array $filters;

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
        ]);

        $dateBy = $this->filters['dateBy'] ?? 'first_informed';
        $dateIn = $this->filters['date_in'] ?? null;
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
        $ads = null;
        $note = $row->Note;

        if ($note?->Adsform) {
            $adsForm = $note->Adsform;
            $ads = ($adsForm->tacit ? $adsForm->tacit_delivered_at : $adsForm->created_at)?->format('d/m/Y');
        } elseif ($note?->OldAds?->isNotEmpty()) {
            $ads = $note->OldAds->last()->date->format('d/m/Y');
        }

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
                $event->sheet->getStyle('A1:M1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'],
                    ],
                ]);

                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('B:M')->getAlignment()->setWrapText(true);
                $sheet->getColumnDimension('F')->setWidth(30);
                $sheet->getColumnDimension('J')->setWidth(30);
                $sheet->getColumnDimension('K')->setWidth(30);
                $sheet->getStyle('A:M')->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('H:H')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $sheet->getStyle('I:I')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                $sheet->getStyle('J:J')->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                $event->sheet->autoSize();
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
}
