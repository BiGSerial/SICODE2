<?php

namespace App\Exports\Protests;

use App\Models\MedProtest;
use App\Models\ProtestJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DispatcherMeasuresExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading, ShouldAutoSize, WithEvents
{
    use Exportable;

    public function __construct(
        protected array $filters
    ) {
    }

    public function query(): Builder
    {
        $start = Carbon::parse($this->filters['start'])->startOfDay();
        $end   = Carbon::parse($this->filters['end'])->endOfDay();
        $userId = $this->filters['userId'] ?? null;
        $types = $this->filters['protestTypes'] ?? [];

        $firstJobs = ProtestJob::selectRaw('med_protest_id, MIN(id) as job_id')
            ->whereNotNull('created_by')
            ->groupBy('med_protest_id');

        $query = MedProtest::query()
            ->leftJoinSub($firstJobs, 'first_jobs', 'first_jobs.med_protest_id', '=', 'med_protests.id')
            ->leftJoin('protest_jobs as first_job', 'first_job.id', '=', 'first_jobs.job_id')
            ->leftJoin('users as dispatcher', 'dispatcher.id', '=', 'first_job.created_by')
            ->leftJoin('protests', 'protests.id', '=', 'med_protests.protest_id')
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($sub) use ($start, $end) {
                    $sub->where('protests.tipoNota', 'NA')
                        ->whereBetween('protests.dtConclusaoDesej', [$start, $end]);
                })
                ->orWhere(function ($sub) use ($start, $end) {
                    $sub->where(function ($tipo) {
                        $tipo->where('protests.tipoNota', '!=', 'NA')
                            ->orWhereNull('protests.tipoNota');
                    })
                    ->whereBetween('med_protests.dtFimMedidaDesej', [$start, $end]);
                });
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('med_protests as mp2')
                    ->whereColumn('mp2.protest_id', 'med_protests.protest_id')
                    ->where('mp2.statusSist', 'MEDA');
            })
            ->when(!empty($types), fn ($q) => $q->whereIn('med_protests.protest_type', $types))
            ->when($userId, fn ($q) => $q->where('first_job.created_by', $userId))
            ->select([
                'med_protests.id',
                'med_protests.med_id',
                'med_protests.statusSist',
                'med_protests.statMedida',
                'med_protests.dtCriacaoMedida',
                'med_protests.protest_id',
                'med_protests.protest_type',
                'med_protests.dtFimMedidaDesej',
                'med_protests.dtFimMedida',
                'med_protests.result',
                'protests.nota as protest_nota',
                'protests.tipoNota as protest_tipo_nota',
                'protests.dtAberturaNota as protest_dt_abertura_nota',
                'protests.dtConclusaoDesej as protest_dt_conclusao_desej',
                'protests.type',
                'protests.txtGrpCodificacao as protest_txt_grp_codificacao',
                'protests.descCausa as protest_desc_causa',
                'protests.descSubCausa as protest_desc_sub_causa',
                'protests.statUsuar as protest_stat_usuar',
                'first_job.id as job_id',
                'first_job.sent_at as job_sent_at',
                'first_job.created_by as dispatcher_id',
                'dispatcher.name as dispatcher_name',

            ])
            ->orderByDesc('med_protests.dtFimMedidaDesej');

        return $query;
    }

    public function map($row): array
    {
        $isOnTime = $this->isOnTime($row);
        $dueBase = $row->protest_tipo_nota === 'NA'
            ? $row->protest_dt_conclusao_desej
            : $row->dtFimMedidaDesej;
        $protestType = $row->protest_type;
        if ($protestType instanceof \App\Enum\ProtestType) {
            $protestType = $protestType->label();
        }

        $lastJobMedProtest = $row->ProtestJobs()?->where('status', 'done')->orderByDesc('id')->first();

        return [
            $row->med_id,
            $row->protest_nota,
            $row->protest_tipo_nota,
            $protestType,
            $row->type,
            $row->protest_txt_grp_codificacao,
            $row->protest_desc_causa,
            $row->protest_desc_sub_causa,
            $row->statusSist,
            $row->protest_stat_usuar,
            $row->statMedida,
            $this->formatDate($row->protest_dt_abertura_nota),
            $this->formatDate($row->dtCriacaoMedida),
            $this->formatDate($row->protest_dt_conclusao_desej),
            $this->formatDate($row->dtFimMedidaDesej),            
            $this->formatDate($dueBase),
            $this->formatDate($row->dtFimMedida),
            $isOnTime ? 'Sim' : 'Nao',
            $row->result,
            $row->job_id,
            $row->dispatcher_name,
            $this->formatDate($row->job_sent_at),
            $lastJobMedProtest?->Owner?->name,
            $lastJobMedProtest?->Owner?->Company?->name,

        ];
    }

    public function headings(): array
    {
        return [
            'Medida ID',
            'Reclamacao (Nota)',
            'Tipo Nota',
            'Tipo Reclamacão',
            'Categoria Reclamação',
            'Grupo Codificação',
            'Descrição de Causa',
            'Descrição Sub Causa',
            'Status Medida',
            'Conclusao Nota',
            'Conclusao Medida',
            'Abertura Reclamação',
            'Criacao Medida',
            'Conclusao desejada (Nota)',
            'Fim medida desejado',                       
            'SLA base considerado',
            'Fim medida', 
            'Dentro do prazo',
            'Conclusion',
            'Job ID',
            'Despachante',
            'Job enviado em',
            'responsavel_conclusao',
            'empresa_responsavel_conclusao',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $columnsCount = max(1, count($this->headings()));
                $lastColumn = $sheet->getCellByColumnAndRow($columnsCount, 1)->getColumn();
                $lastRow = max(1, $sheet->getHighestRow());

                $headerRange = "A1:{$lastColumn}1";
                $allRange = "A1:{$lastColumn}{$lastRow}";
                $dataRange = $lastRow >= 2 ? "A2:{$lastColumn}{$lastRow}" : null;

                $sheet->freezePane('A2');
                $sheet->getRowDimension(1)->setRowHeight(26);

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0F4C81'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                ]);

                $sheet->getStyle($allRange)->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);

                if ($dataRange) {
                    for ($row = 2; $row <= $lastRow; $row++) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8FAFC'],
                                ],
                            ]);
                        }

                        $onTimeCell = $sheet->getCell("R{$row}")->getValue();
                        if ($onTimeCell === 'Sim') {
                            $sheet->getStyle("R{$row}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => '047857']],
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'DCFCE7'],
                                ],
                            ]);
                        } elseif ($onTimeCell === 'Nao') {
                            $sheet->getStyle("R{$row}")->applyFromArray([
                                'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FEE2E2'],
                                ],
                            ]);
                        }
                    }
                }
            },
        ];
    }

    protected function isOnTime($row): bool
    {
        if ($row->protest_tipo_nota === 'NA') {
            if (! $row->protest_dt_conclusao_desej || ! $row->dtFimMedida) {
                return false;
            }

            return Carbon::parse($row->dtFimMedida)->toDateString()
                <= Carbon::parse($row->protest_dt_conclusao_desej)->toDateString();
        }

        if (! $row->dtFimMedidaDesej || ! $row->dtFimMedida) {
            return false;
        }

        return Carbon::parse($row->dtFimMedida)->toDateString()
            <= Carbon::parse($row->dtFimMedidaDesej)->toDateString();
    }

    protected function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
