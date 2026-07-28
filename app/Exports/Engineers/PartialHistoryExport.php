<?php

namespace App\Exports\Engineers;

use App\Models\Partial;
use App\Services\Engineers\PartialHistoryService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;

class PartialHistoryExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading, ShouldAutoSize, WithProperties
{
    use Exportable;

    public function __construct(private readonly Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * @param Partial $partial
     */
    public function map($partial): array
    {
        $orders = $partial->Orders->pluck('ordem')
            ->merge($partial->Note?->Orders?->pluck('ordem') ?? collect())
            ->filter()
            ->unique()
            ->implode(', ');

        return [
            $partial->Note?->note ?? '---',
            $orders !== '' ? $orders : '---',
            $partial->Note?->numPedido ?? '---',
            $partial->Note?->rubrica ?? '---',
            $partial->Company?->name ?? 'Desconhecido',
            $partial->created_at?->format('d/m/Y H:i:s') ?? '---',
            $partial->engineer?->name ?? '---',
            $partial->decision_at?->format('d/m/Y H:i:s') ?? '---',
            $partial->supervisor?->name ?? '---',
            $partial->supervision_at?->format('d/m/Y H:i:s') ?? '---',
            $partial->payer?->name ?? '---',
            $partial->payment_at?->format('d/m/Y H:i:s') ?? '---',
            (float) ($partial->value ?? 0),
            app(PartialHistoryService::class)->statusLabel($partial),
            $partial->complete ? 'SIM' : 'NÃO',
        ];
    }

    public function headings(): array
    {
        return [
            'Nota/OV',
            'Ordem',
            'DR/Pedido',
            'Rubrica',
            'Empreiteira',
            'Data envio',
            'Engenheiro aprovador',
            'Quando aprovou',
            'Fiscalizado por',
            'Quando fiscalizou',
            'Pago por',
            'Quando pagou',
            'Valor ADS',
            'Status',
            'Finalizado',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function properties(): array
    {
        return [
            'creator' => config('app.name', 'SICODE'),
            'lastModifiedBy' => config('app.name', 'SICODE'),
            'title' => 'Historico de Informes Parciais',
            'description' => 'Arquivo gerado automaticamente via SICODE',
            'subject' => 'Engenharia',
            'company' => config('app.name', 'SICODE'),
        ];
    }
}
