<?php

namespace App\Exports\Dispatchs;

use App\Custom\Notestatus;
use App\Helpers\DaysLeft;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\{
    Exportable,
    FromQuery,
    WithMapping,
    WithHeadings,
    WithProperties,
    WithEvents,
    WithChunkReading,
    ShouldAutoSize
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DispatchPaymentMain implements FromQuery, WithMapping, WithHeadings, WithProperties, WithEvents, WithChunkReading, ShouldAutoSize
{
    use Exportable;

    protected $data;
    protected $service;


    public function __construct($data, $service)
    {
        $this->data = $data;
        $this->service = $service;

    }

    /**
     * Define a query para exportação usando chunking.
     */
    public function query()
    {

        return $this->data
        ->with([
            'WorkForm.Orders.Operations',
            'WorkForm.Company',
            'Partials.Orders',
            'Partials.Company',
            'Productions' => function ($q) {
                $q->with(['User','Company']);
            },
        ]);

    }

    /**
     * Número de registros por chunk (ajuste conforme sua memória)
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Mapeia cada nota para uma linha de Excel.
     */
    public function map($list): array
    {


        if ($list->WorkForm) {
            $type = 'TOTAL';
            $order = $list->WorkForm?->Orders;
            $company = $list->WorkForm?->Company->name;
            $date_info = $list->WorkForm?->informed_at;
            $pagamento = Carbon::parse($list->fimLancado);
        } elseif ($list->Partials->count() > 0) {
            $type = 'PARCIAL';
            $order = $list->Partials?->last()->Orders;
            $company = $list->Partials?->last()->Company->name;
            $date_info = $list->Partials?->last()->created_at;
            $pagamento = $list->Partials?->last()->supervision_at->addDays(5);
        } else {
            $type = 'DESCONHECIDO';
            $order = null;
            $company = null;
            $date_info = null;
            $pagamento = null;
        }


        $ops = $order?->first()->Operations ?? collect();
        $lastProd = $list->Productions->where('service_id', $this->service)->last();

        return [
            $list->note,
            $type,
            $order ? implode("\n", $order->pluck('ordem')->toArray()) : '---',
            $order?->sum('moaberto') ?? 0,
            $ops->where('operacao', '0030')->first()?->status ? explode(' ', $ops->where('operacao', '0030')->first()->status)[0] : '---',
            $ops->where('operacao', '0040')->first()?->status ? explode(' ', $ops->where('operacao', '0040')->first()->status)[0] : '---',
            $ops->where('operacao', '0050')->first()?->status ? explode(' ', $ops->where('operacao', '0050')->first()->status)[0] : '---',
            $ops->where('operacao', '0010')->first()?->cenTrab ?? '---',
            $company,
            $list->lexp,
            optional($list->WorkForm)->date?->format('d/m/Y') ?? '---',
            $date_info,
            $list->nstats ?? $list->centerjob,
            optional($pagamento)->format('d/m/Y') ?? '---',
            (new DaysLeft($list))->getLastDate(),
            $lastProd?->User->name ?? '---',
            $lastProd ? Notestatus::status($lastProd?->status)->status : '---',
        ];
    }

    /**
     * Cabeçalhos da planilha.
     */
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
            'Status',
            'Prazo Pagamento',
            'Prazo Obra',
            'User Production',
            'Status Production',
        ];
    }

    /**
     * Propriedades do arquivo (mantém original).
     */
    public function properties(): array
    {
        return [
            'creator'        => auth()->user()->name,
            'lastModifiedBy' => auth()->user()->name,
            'title'          => 'Relatorio Automatico Sicode',
            'description'    => 'Arquivo gerado automaticamente via SICODE',
            'subject'        => 'Relatorios',
            'manager'        => 'Joao Paulo Mantovani',
            'company'        => 'EDP Energias do Brasil',
        ];
    }

    /**
     * Eventos para estilização (mantém original).
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:Q1')->applyFromArray([
                    'font' => ['bold' => true,'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID,'startColor' => ['rgb' => '0000FF']],

                ]);
                // estiliza só o header e colunas fixas
                $sheet->getStyle('A1:Q1')->applyFromArray([/* … */]);
                foreach (range('A', 'Q') as $col) {
                    $sheet->getColumnDimension($col)->setWidth(15);
                }
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A2:Q{$highestRow}")
                      ->getAlignment()
                      ->setWrapText(true);
            },
        ];
    }
}
