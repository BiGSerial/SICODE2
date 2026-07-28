<?php

namespace App\Exports\Reports;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\{Exportable, FromQuery, ShouldAutoSize, WithChunkReading, WithHeadings, WithMapping};

class CancellationListExport implements FromQuery, ShouldAutoSize, WithChunkReading, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(private Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nota',
            'Categoria',
            'Tipo',
            'Status',
            'Solicitante',
            'Executor',
            'Engenheiro',
            'Abertura',
            'Encerramento',
            'Tempo de execução',
            'Tempo de aprovação do engenheiro',
            'Tempo de encerramento',
            'Tempo de finalização',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->note_number ?: '-',
            $row->category_name ?: '-',
            $row->scope_label,
            $row->status_label,
            $row->requester_name ?: '-',
            $row->assignee_name ?: '-',
            $row->engineer_name ?: '-',
            $this->formatDate($row->opened_at),
            $this->formatDate($row->closed_at),
            $this->secondsToHuman($row->exec_seconds),
            $this->secondsToHuman($row->eng_seconds),
            $this->secondsToHuman($row->close_seconds),
            $this->secondsToHuman($row->final_seconds),
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    private function formatDate(?string $date): string
    {
        return $date ? Carbon::parse($date)->format('d/m/Y H:i') : '-';
    }

    private function secondsToHuman(?int $seconds): string
    {
        if (!$seconds || $seconds <= 0) {
            return '-';
        }

        $days    = intdiv($seconds, 86400);
        $hours   = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return collect([
            $days > 0 ? $days . 'd' : null,
            $hours > 0 ? $hours . 'h' : null,
            $minutes > 0 ? $minutes . 'min' : null,
        ])->filter()->implode(' ');
    }
}
