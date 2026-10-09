<?php

namespace App\Exports\Dispatchs;

use App\Custom\Notestatus;
use Maatwebsite\Excel\Concerns\{
    Exportable,
    FromQuery,
    ShouldAutoSize,
    WithChunkReading,
    WithEvents,
    WithHeadings,
    WithMapping,
    WithProperties
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DispatchPaymentStack implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithProperties,
    WithEvents,
    WithChunkReading,
    ShouldAutoSize
{
    use Exportable;

    protected $data;

    protected $service;

    public function __construct($data, $service)
    {
        $this->data    = $data;
        $this->service = $service;
    }

    /**
     * 1️⃣ Fonte de dados: query que retorna os dispatches para o serviço
     */
    public function query()
    {
        return $this->data
            ->with([
                'Note.WorkForm.Orders.Operations',
                'Note.WorkForm.Orders.Pep:objeto,disponivel',
                'Note.WorkFormAny.Orders.Operations',
                'Note.WorkFormAny.Orders.Pep:objeto,disponivel',
                'Note.Partials.Orders.Operations',
                'Note.Partials.Orders.Pep:objeto,disponivel',
                'Company',
                'User',
                'Notetimelines',
            ])
            ->where('service_id', $this->service)
            ->orderBy('dispatch_at', 'asc');
    }

    /**
     * 2️⃣ Mapeamento de cada linha: aqui você monta o array de valores
     */
    public function map($row): array
    {
        // dd($row);

        if ($row->partial && $row->note->partials->isNotEmpty()) {
            $orderModels = $row->note->partials?->last()->orders ?? collect();
            $orders      = $orderModels->pluck('ordem')->implode("\n ");
            $soma        = $row->note->partials->last()?->value;
            $company     = $row->note->partials->last()->company->name;
            $lastPaydate = $row->note->partials->last()->supervision_at;
        } else {
            $workForm = $row->note->WorkForm ?: $row->note->WorkFormAny;

            if ($workForm) {
                $orderModels = $workForm->orders ?? collect();
                $orders      = $orderModels->pluck('ordem')->implode("\n ");
                $soma        = $orderModels->sum('moaberto');
                $company     = $workForm->company->name;
                $lastPaydate = $workForm->informed_at;

                if ($workForm->canceled) {
                    $company .= ' (CANCELADO)';
                }
            } else {
                $orderModels = collect();
                $orders      = '';
                $soma        = 0;
                $company     = "";
                $lastPaydate = null;
            }
        }

        $pepCodes    = $orderModels->pluck('pep')->filter()->implode("\n") ?: '---';
        $pepBalances = $orderModels
            ->map(fn ($order) => $order->Pep?->disponivel === null
                ? '---'
                : number_format((float) $order->Pep->disponivel, 2, ',', '.'))
            ->implode("\n") ?: '---';

        // dd([
        //     $row->note->note,
        //     $row->partial ? "PARCIAL" : "TOTAL",
        //     $orders,
        //     $soma,
        //     $company,
        //     $row->note->rubrica,
        //     $row->note->lexp,
        //     $row->company?->name,
        //     $row->user?->name,
        //     $row->dispatch_at?->format('Y-m-d'),
        //     $row->att_at?->format('Y-m-d'),
        //     $lastPaydate?->addDays(5)->format('Y-m-d'),
        //     Notestatus::status($row->status)->status,

        // ]);

        return [
            $row->partial ? "PARCIAL" : "TOTAL",
            $row->note->note,
            $orders,
            $pepCodes,
            $pepBalances,
            $soma,
            $company,
            $row->note->rubrica,
            $row->note->lexp,
            $row->company?->name,
            $row->user?->name,
            $row->dispatch_at?->format('Y-m-d'),
            $row->att_at?->format('Y-m-d'),
            $lastPaydate?->format('Y-m-d'),
            $lastPaydate?->addDays(5)->format('Y-m-d'),
            Notestatus::status($row->status)->status,
            $row->notetimelines?->where('status', $row->status)?->last()?->info,

        ];
    }

    /**
     * 3️⃣ Cabeçalhos da planilha
     */
    public function headings(): array
    {
        return [
            'Tipo',
            'Nota',
            'Ordem',
            'PEP',
            'Saldo disponível PEP',
            'MOA',
            'Empreiteira',
            'Rubrica',
            'Municipio',
            'Empresa',
            'Usuario',
            'Data de Despacho',
            'Data de Atribuicao',
            'Data do Informe',
            'Prazo Medição',
            'Status',
            'Info Status',
        ];
    }

    /**
     * 4️⃣ Metadados do arquivo
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
     * 5️⃣ Eventos para estilização: cabeçalho e formatação numérica
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // estiliza a linha de cabeçalho (A1:M1)
                $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->headings()));
                $event->sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0000FF'],
                    ],
                ]);

                // formata colunas numéricas
                $event->sheet->getStyle('F:F')->getNumberFormat()
                      ->setFormatCode('#,##0.00');
                $event->sheet->getStyle('B:C')->getNumberFormat()
                      ->setFormatCode('0');

                // permite quebra de linha na coluna B
                $event->sheet->getStyle('C:C')->getAlignment()
                      ->setWrapText(true);
            },
        ];
    }

    /**
     * 6️⃣ Tamanho do chunk para leituras grandes
     */
    public function chunkSize(): int
    {
        return 500;
    }
}
