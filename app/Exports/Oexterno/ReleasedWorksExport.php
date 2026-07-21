<?php

namespace App\Exports\Oexterno;

use App\Models\ExternalOrganRelease;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;

class ReleasedWorksExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping, WithProperties
{
    /** @var array<int, int> */
    private array $releaseIds;

    /**
     * @param array<int, int> $releaseIds
     */
    public function __construct(array $releaseIds)
    {
        $this->releaseIds = $releaseIds;
    }

    public function query()
    {
        return ExternalOrganRelease::query()
            ->whereIn('id', $this->releaseIds)
            ->eligibleForExternalOrganList()
            ->with([
                'note',
                'production.User',
                'production.Company',
                'production.ProjectReviewCycles' => function ($q) {
                    $q->select(['id', 'production_id', 'round_number'])
                        ->orderByDesc('round_number')
                        ->with('Orders:id,cycle_id,sort_order,order_number,total_cost,company_cost,client_cost');
                },
            ])
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'Nota/OV',
            'Pedido',
            'Cliente',
            'Municipio',
            'Rubrica',
            'Status Atual',
            'Data Status Atual',
            'Status Detectado',
            'Data Status Detectado',
            'Custo Cliente?',
            'Custo Cliente',
            'Custo Empresa',
            'Custo Total',
            'Rodada Analise',
            'Ordens Analise',
            'Projetista',
            'Empresa Desenho',
            'Production ID',
            'Release ID',
        ];
    }

    public function map($release): array
    {
        $cost = $this->projectReviewCostSummary($release);

        return [
            $release->note?->note,
            $release->note?->numPedido,
            $release->note?->client,
            $release->note?->lexp,
            $release->note?->rubrica,
            $release->note?->nstats,
            optional($release->note?->dt_status)->format('d/m/Y H:i'),
            $release->detected_nstats,
            optional($release->detected_dt_status)->format('d/m/Y H:i'),
            $cost['has_cycle'] ? ($cost['has_client_cost'] ? 'Sim' : 'Nao') : 'Sem analise',
            $cost['client_cost'],
            $cost['company_cost'],
            $cost['total_cost'],
            $cost['round_number'],
            $cost['orders'],
            $release->production?->User?->name,
            $release->production?->Company?->name,
            $release->production_id,
            $release->id,
        ];
    }

    private function projectReviewCostSummary(ExternalOrganRelease $release): array
    {
        $cycle = $release->production?->ProjectReviewCycles?->sortByDesc('round_number')->first();

        if (!$cycle) {
            return [
                'has_cycle' => false,
                'has_client_cost' => false,
                'round_number' => null,
                'total_cost' => 0.0,
                'company_cost' => 0.0,
                'client_cost' => 0.0,
                'orders' => '',
            ];
        }

        $orders = collect($cycle->Orders ?? []);
        $clientCost = (float) $orders->sum(fn ($order) => (float) ($order->client_cost ?? 0));

        return [
            'has_cycle' => true,
            'has_client_cost' => $clientCost > 0,
            'round_number' => (int) $cycle->round_number,
            'total_cost' => (float) $orders->sum(fn ($order) => (float) ($order->total_cost ?? 0)),
            'company_cost' => (float) $orders->sum(fn ($order) => (float) ($order->company_cost ?? 0)),
            'client_cost' => $clientCost,
            'orders' => $orders
                ->pluck('order_number')
                ->map(fn ($orderNumber) => trim((string) $orderNumber))
                ->filter()
                ->implode(', '),
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function properties(): array
    {
        return [
            'creator' => 'SICODE',
            'lastModifiedBy' => 'SICODE',
            'title' => 'Obras Liberadas para Orgao Externo',
            'description' => 'Projetos dependentes de Orgao Externo pendentes de saida para status 20 ou 11.',
            'subject' => 'Orgao Externo',
            'keywords' => 'orgao externo, obras liberadas, sicode',
            'category' => 'Exports',
        ];
    }
}
