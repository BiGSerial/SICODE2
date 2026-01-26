<?php

namespace App\Exports\Ads;

use App\Enum\AdsRequestStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;

class AdsRequestsHistoryExport implements FromQuery, WithHeadings, WithMapping, WithProperties, WithChunkReading
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
            'Nota',
            'Empresa',
            'Usuario',
            'Status',
            'Descricao',
            'Versao',
            'URL ADS',
            'Criado em',
            'Concluido em',
            'Cancelado em',
        ];
    }

    public function map($request): array
    {
        $status = $request->status instanceof AdsRequestStatus
            ? $request->status->label()
            : (string) $request->status;

        return [
            $request->note?->note ?? $request->note_id,
            $request->company?->name ?? '',
            $request->requestedBy?->name ?? '',
            $status,
            $request->description ?? '',
            $request->version ?? '',
            $request->url ?? '',
            optional($request->created_at)->format('d/m/Y H:i'),
            optional($request->completed_at)->format('d/m/Y H:i'),
            optional($request->canceled_at)->format('d/m/Y H:i'),
        ];
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE',
            'lastModifiedBy' => 'SICODE',
            'title'          => 'Historico de solicitacoes ADS',
            'description'    => 'Exportacao contendo o historico filtrado na tela.',
            'subject'        => 'AdsRequests',
            'keywords'       => 'ads, solicitacoes, export, excel',
            'category'       => 'Exports',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
