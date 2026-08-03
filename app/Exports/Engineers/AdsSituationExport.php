<?php

namespace App\Exports\Engineers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithProperties;

class AdsSituationExport implements FromCollection, WithHeadings, ShouldAutoSize, WithProperties
{
    use Exportable;

    public function __construct(private Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows->map(function (array $row) {
            return [
                $row['work_report_id'],
                $row['note_number'],
                $row['company_name'],
                $row['state'] ?? '',
                $this->formatDateTime($row['informed_at']),
                $this->formatDateTime($row['due_at']),
                $this->formatDateTime($row['delivered_at']),
                $row['status_label'],
                $row['delay_days'],
                $row['days_to_due'] ?? '',
                ($row['penalty_percentage'] ?? 0) . '%',
                str_replace('_', ' ', strtoupper($row['penalty_band'] ?? '')),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'WorkReport ID',
            'Nota',
            'Empreiteira',
            'UF',
            'Informe',
            'Data limite ADS',
            'Entrega ADS',
            'Status',
            'Dias vencidos',
            'Dias para vencer',
            'Percentual de penalidade',
            'Faixa de penalidade',
        ];
    }

    public function properties(): array
    {
        return [
            'creator' => 'SICODE',
            'lastModifiedBy' => 'SICODE',
            'title' => 'Situação de ADS',
            'description' => 'Arquivo gerado automaticamente via SICODE',
            'subject' => 'Engenharia',
            'company' => 'EDP Energias do Brasil',
        ];
    }

    private function formatDateTime(?Carbon $date): string
    {
        return $date ? $date->format('d/m/Y H:i') : '—';
    }
}
