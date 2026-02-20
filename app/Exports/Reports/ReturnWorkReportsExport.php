<?php

namespace App\Exports\Reports;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReturnWorkReportsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithChunkReading
{
    use Exportable;

    protected BaseBuilder $builder;

    public function __construct(BaseBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function query()
    {
        return $this->builder;
    }

    public function headings(): array
    {
        return [
            'Data da rejeição',
            'Informe (WorkReport)',
            'Nota',
            'Empreiteira',
            'Serviço que rejeitou',
            'Categoria da rejeição',
            'Quem rejeitou',
            'Usuário que informou',
            'Criado por (usuário logado)',
            'Empresa do criador',
            'Data de criação do informe',
            'Observação da rejeição',
        ];
    }

    public function map($row): array
    {
        return [
            $row->rejected_at ? Carbon::parse($row->rejected_at)->format('d/m/Y H:i') : '—',
            $row->workreport_id ?? '—',
            $row->note_number ?? '—',
            $row->contractor ?? '—',
            $row->service_name ?? '—',
            $row->reject_category ?? '—',
            $row->rejector_name ?? '—',
            $row->informer_name ?? '—',
            $row->creator_name ?? '—',
            $row->creator_company ?? '—',
            $row->workreport_created_at ? Carbon::parse($row->workreport_created_at)->format('d/m/Y H:i') : '—',
            $row->reject_observation ?? '—',
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
