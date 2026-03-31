<?php

namespace App\Exports\Dispatchs;

use App\Custom\Notestatus;
use App\Helpers\DaysLeft;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{
    Exportable, FromQuery, WithMapping, WithHeadings, WithProperties,
    WithEvents, WithChunkReading, ShouldAutoSize
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DispatchPaymentMain implements FromQuery, WithMapping, WithHeadings, WithProperties, WithEvents, WithChunkReading, ShouldAutoSize
{
    use Exportable;

    /** @var \Illuminate\Database\Eloquent\Builder */
    protected Builder $queryBuilder;
    protected string $serviceUuid;

    public function __construct(Builder $queryBuilder, string $serviceUuid)
    {
        $this->queryBuilder = $queryBuilder;
        $this->serviceUuid  = $serviceUuid;
    }

    public function query()
    {
        return $this->queryBuilder->with([
            'WorkForm.Orders.Operations',
            'WorkForm.Company',
            'WorkFormAny.Orders.Operations',
            'WorkFormAny.Company',
            'Partials.Orders',
            'Partials.Company',
            'Productions' => fn ($q) => $q->with(['User','Company']),
            'FiveNote', // necessário para coluna de D5
        ]);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function map($list): array
    {
        $workForm = $list->WorkForm ?: $list->WorkFormAny;
        $workFormCanceled = (bool) ($workForm?->canceled);

        if ($workForm) {
            $type      = 'TOTAL';
            $order     = $workForm?->Orders;
            $company   = $workForm?->Company->name;
            $date_info = $workForm?->informed_at;
            $pagamento = $list->fimLancado ? Carbon::parse($list->fimLancado) : null;
            $dt_ads    = $workForm?->Adsform?->created_at ?? null;

        } elseif ($list->Partials->count() > 0) {
            $type      = 'PARCIAL';
            $order     = $list->Partials?->last()->Orders;
            $company   = $list->Partials?->last()->Company->name;
            $date_info = $list->Partials?->last()->created_at;
            $pagamento = $list->fimLancado ? Carbon::parse($list->fimLancado) : null;
            $dt_ads    = $date_info;
        } else {
            $type      = 'DESCONHECIDO';
            $order     = null;
            $company   = null;
            $date_info = null;
            $pagamento = null;
            $dt_ads    = null;
        }

        // Última produção do serviço atual
        $lastProd = $list->Productions
            ->where('service_id', $this->serviceUuid)
            ->sortBy('created_at') // garante última
            ->last();

        if ($lastProd) {
            if ($type === 'TOTAL' && $lastProd->partial) {
                $lastProd = null;
            } elseif ($type === 'PARCIAL' && $workForm) {
                $lastProd = null;
            }
        }

        $ops = $order?->first()->Operations ?? collect();

        // --- Colunas de D5 ---
        $fn        = $list->FiveNote;
        $hasD5     = $fn ? 'SIM' : 'NÃO';
        $numberD5  = (string) $fn?->note_d5;

        if ($fn && $hasD5) {
            if (!$fn->is_supervisioned) {
                $statusD5 = 'Gerar D5';
            } else {
                $statusD5 = 'Finalizar D5';
            }
        } else {
            $statusD5 = '---';
        }

        if (!$fn) {
            $numberD5 = '-';
        }

        return [
            $list->note,
            $type.($workFormCanceled ? ' (CANCELADO)' : ''),
            $order ? implode("\n", $order->pluck('ordem')->toArray()) : '---',
            $order?->sum('moaberto') ?? 0,
            $ops->where('operacao', '0030')->first()?->status ? explode(' ', $ops->where('operacao', '0030')->first()->status)[0] : '---',
            $ops->where('operacao', '0040')->first()?->status ? explode(' ', $ops->where('operacao', '0040')->first()->status)[0] : '---',
            $ops->where('operacao', '0050')->first()?->status ? explode(' ', $ops->where('operacao', '0050')->first()->status)[0] : '---',
            $ops->where('operacao', '0010')->first()?->cenTrab ?? '---',
            $company ? $company.($workFormCanceled ? ' (CANCELADO)' : '') : $company,
            $list->lexp,
            optional($workForm)->earliest_fim_real?->format('d/m/Y') ?? '---',
            $date_info ? Carbon::parse($date_info)->format('d/m/Y') : '---',
            $dt_ads ? Carbon::parse($dt_ads)->format('d/m/Y') : '---',
            $list->type_note == 2 ? $list->nstats : ($list->centerjob ?? '---'),
            $pagamento ? $pagamento->format('d/m/Y') : '---',
            (new DaysLeft($list))->getLastDate(),
            $lastProd?->User->name ?? '---',
            $lastProd ? Notestatus::status($lastProd?->status)->status : '---',
            $hasD5,
            $numberD5,
            $statusD5,
        ];
    }

    public function headings(): array
    {
        return [
            'Nota',
            'Tipo',
            'Ordem',
            'MOA',
            'OP30',
            'OP40',
            'OP50',
            'CentroTrab',
            'Empresa',
            'Município',
            'Data Execução',
            'Data Informe',
            'Data Ads',
            'Status',
            'Dt Final OP20',
            'Prazo Obra',
            'User Production',
            'Status Production',
            'Possui D5',
            'Número D5',
            'Status D5',
        ];
    }

    public function properties(): array
    {
        $user = auth()->user();
        $name = $user?->name ?? 'SICODE';
        return [
            'creator'        => $name,
            'lastModifiedBy' => $name,
            'title'          => 'Relatorio Automatico SICODE',
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
                $sheet         = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();
                $highestRow    = $sheet->getHighestRow();

                // Header bold + fundo
                $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Larguras dinâmicas
                for ($col = 'A'; $col !== $highestColumn; $col = chr(ord($col) + 1)) {
                    $sheet->getColumnDimension($col)->setWidth(15);
                }
                $sheet->getColumnDimension($highestColumn)->setWidth(15);

                // Quebra de linha nas células (linhas de dados) e centralização
                if ($highestRow > 1) {
                    $sheet->getStyle("A2:{$highestColumn}{$highestRow}")
                        ->getAlignment()
                        ->setWrapText(true)
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }

                // Formatação de números inteiros para Nota (A) e Ordem (C)
                if ($highestRow > 1) {
                    $sheet->getStyle("A2:A{$highestRow}")
                        ->getNumberFormat()->setFormatCode('#');
                    $sheet->getStyle("C2:C{$highestRow}")
                        ->getNumberFormat()->setFormatCode('#');
                    $sheet->getStyle("T2:T{$highestRow}")
                        ->getNumberFormat()->setFormatCode('0');
                }
            },
        ];
    }
}
