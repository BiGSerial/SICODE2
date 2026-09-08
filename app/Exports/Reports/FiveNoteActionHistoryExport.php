<?php

namespace App\Exports\Reports;

use App\Models\TimelineEvent;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FiveNoteActionHistoryExport implements FromQuery, WithHeadings, WithMapping, WithProperties, WithChunkReading, ShouldAutoSize, WithEvents
{
    use Exportable;

    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Nota D5',
            'Nota/OV',
            'Empresa',
            'Tipo de acao',
            'Etapa origem',
            'Etapa destino',
            'Responsavel',
            'Papel responsavel',
            'Acao por',
            'Papel acao',
            'Servico',
            'Producao',
            'Conclusao da producao',
            'Info da producao',
            'Data/Hora',
            'Motivo',
            'Comentario',
            'Inferido?',
            'Criado em',
        ];
    }

    public function map($event): array
    {
        /** @var TimelineEvent $event */
        return [
            $event->fiveNote?->note_d5 ?: '---',
            $event->note?->note ?: $event->fiveNote?->note?->note ?: '---',
            $event->fiveNote?->company?->name ?: '---',
            $this->eventLabel((string) $event->event_type),
            $this->stageLabel((string) ($event->from_stage ?? '')),
            $this->stageLabel((string) ($event->to_stage ?? '')),
            $event->owner?->name ?: '---',
            $event->owner_role ?: '---',
            $event->actor?->name ?: '---',
            $event->actor_role ?: '---',
            $event->service?->service ?: '---',
            $event->production_id ?: '---',
            $event->production?->Analise?->conclusion ?: '---',
            $event->production?->Analise?->info ?: '---',
            $this->formatDate($event->occurred_at),
            $event->reason ?: '---',
            $event->comment ?: '---',
            $event->inferred ? 'SIM' : 'NAO',
            $this->formatDate($event->created_at),
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE',
            'lastModifiedBy' => 'SICODE',
            'title'          => 'Historico de Acoes D5',
            'description'    => 'Exportacao contendo uma linha por evento registrado na timeline de D5.',
            'subject'        => 'FiveNotes timeline events',
            'keywords'       => 'five, d5, timeline, eventos, export, excel',
            'category'       => 'Exports',
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
                $highestColumn = $sheet->getHighestColumn();
                $highestRow = max(1, (int) $sheet->getHighestRow());

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$highestColumn}1");
                $sheet->getRowDimension(1)->setRowHeight(26);

                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0F766E'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                for ($row = 2; $row <= $highestRow; $row++) {
                    if ($row % 2 !== 0) {
                        $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F8FAFC'],
                            ],
                        ]);
                    }

                    $currentD5 = (string) $sheet->getCell("A{$row}")->getValue();
                    $previousD5 = $row > 2 ? (string) $sheet->getCell('A' . ($row - 1))->getValue() : null;

                    if ($row === 2 || $currentD5 !== $previousD5) {
                        $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                            'font' => ['bold' => true],
                            'borders' => [
                                'top' => [
                                    'borderStyle' => Border::BORDER_MEDIUM,
                                    'color' => ['rgb' => '0F766E'],
                                ],
                            ],
                        ]);
                    }
                }
            },
        ];
    }

    protected function formatDate($value): string
    {
        if (!$value) {
            return '---';
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('d/m/Y H:i');
        }

        return (string) $value;
    }

    protected function eventLabel(string $eventType): string
    {
        return match ($eventType) {
            'd5_created', 'd5_created_manual' => 'D5 criada',
            'd5_created_from_supervision' => 'D5 solicitada pela fiscalizacao',
            'd5_payment_updated' => 'Pagamento atualizado',
            'd5_released_to_partner' => 'Liberada para empreiteira',
            'd5_partner_completed' => 'Empreiteira concluiu',
            'd5_partner_recompleted' => 'Empreiteira concluiu novamente',
            'd5_sent_to_supervision_queue' => 'Enviada para fila da fiscalizacao',
            'd5_supervision_assigned' => 'Fiscalizacao atribuida',
            'd5_user_assigned' => 'Usuario atribuido',
            'd5_user_unassigned' => 'Usuario desatribuido',
            'd5_user_changed' => 'Usuario alterado',
            'd5_returned_with_pending' => 'Retornada com pendencia',
            'd5_supervision_approved' => 'Fiscalizacao aprovou',
            'd5_sent_to_payment_archive' => 'Enviada para arquivamento',
            'd5_archived' => 'D5 arquivada',
            default => $eventType ?: '---',
        };
    }

    protected function stageLabel(string $stage): string
    {
        return match ($stage) {
            'created' => 'Criada',
            'payment_review' => 'Em medição',
            'released_to_partner' => 'Com empreiteira',
            'partner_done' => 'Concluida pela empreiteira',
            'supervision_queue' => 'Fila fiscalizacao',
            'supervision_assigned' => 'Fiscal atribuido',
            'returned_to_partner' => 'Devolvida a empreiteira',
            'supervision_approved' => 'Aprovada na fiscalizacao',
            'archived' => 'Arquivada',
            default => $stage ?: '---',
        };
    }
}
