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
            ->with(['note', 'production.User', 'production.Company'])
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
            'Projetista',
            'Empresa Desenho',
            'Production ID',
            'Release ID',
        ];
    }

    public function map($release): array
    {
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
            $release->production?->User?->name,
            $release->production?->Company?->name,
            $release->production_id,
            $release->id,
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
