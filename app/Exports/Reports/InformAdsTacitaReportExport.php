<?php

namespace App\Exports\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;

class InformAdsTacitaReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithProperties
{
    use Exportable;

    public function __construct(private Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows->map(function (array $row) {
            return [
                $row['mode_label'],
                $row['note_number'],
                $row['company_name'],
                $row['order_numbers'],
                $this->formatDateTime($row['informed_delivery_at']),
                $this->formatDateTime($row['tacit_due_at']),
                $this->formatDateTime($row['tacit_delivered_at']),
                $row['fine_status'] === 'EM ABERTO'
                    ? 'EM ABERTO (Ref.: ' . $this->formatDateTime($row['fine_reference_at']) . ')'
                    : 'ENTREGUE',
                $row['delay_days'],
                $row['applied_percentage'] . '%',
                $row['base_amount'],
                $row['daily_fine_amount'],
                $row['total_fine_amount'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Modo de listagem',
            'Número da NOTA',
            'Empreiteira',
            'Número(s) da ORDEM',
            'Data de entrega do informe',
            'Data de vencimento tácito da ADS',
            'Data de envio da ADS de forma tácita',
            'Status ADS',
            'Dias para multa',
            'Percentual aplicado',
            'Custo de serviço / valor da obra',
            'Valor de multa diária prevista',
            'Valor de multa total prevista',
        ];
    }

    public function properties(): array
    {
        return [
            'creator' => 'SICODE',
            'lastModifiedBy' => 'SICODE',
            'title' => 'Relatório Informe x ADS Tácita',
            'description' => 'Arquivo gerado automaticamente via SICODE',
            'subject' => 'Relatórios',
            'company' => 'EDP Energias do Brasil',
        ];
    }

    private function formatDateTime(?Carbon $date): string
    {
        return $date ? $date->format('d/m/Y H:i') : '—';
    }
}
