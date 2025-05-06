<?php

namespace App\Exports\Dispatchs;

use App\Custom\Notestatus;
use Illuminate\Contracts\View\View;
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
use Carbon\Carbon;

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
        $this->data     = $data;
        $this->service = $service;
    }

    /**
     * 1️⃣ Fonte de dados: query que retorna os dispatches para o serviço
     */
    public function query()
    {
        return $this->data
            ->with(['Note.WorkForm.Orders.Operations', 'Note.Partials.Orders.Operations', 'Company', 'User'])
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
            $orders = implode("\n ", $row->note->partials?->last()->orders?->pluck('ordem')->toArray());
            $soma = $row->note->partials->last()?->value;
            $company = $row->note->partials->last()->company->name;
            $lastPaydate = $row->note->partials->last()->supervision_at;
        } else {
            if ($row->note->WorkForm) {
                $orders = implode("\n ", $row->note->WorkForm->orders?->pluck('ordem')->toArray());
                $soma = $row->note->WorkForm?->orders?->sum('moaberto');
                $company = $row->note->WorkForm->company->name;
                $lastPaydate = $row->note->WorkForm->informed_at;
            } else {
                $orders = '';
                $soma = 0;
                $company = "";
                $lastPaydate = null;
            }
        }

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
            'MOA',
            'Empreiteira',
            'Rubrica',
            'Municipio',
            'Empresa',
            'Usuario',
            'Data de Despacho',
            'Data de Atribuicao',
            'Data do Informe',
            'Prazo Pagamento',
            'Status',
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
                $event->sheet->getStyle('A1:N1')->applyFromArray([
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
                $event->sheet->getStyle('D:D')->getNumberFormat()
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
