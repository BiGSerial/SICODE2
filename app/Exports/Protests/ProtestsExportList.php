<?php

namespace App\Exports\Protests;

use App\Enum\ProtestJobStatus;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithProperties;
use Carbon\Carbon;

class ProtestsExportList implements FromQuery, WithHeadings, WithMapping, WithProperties, WithEvents
{
    use Exportable;

    public Builder $query;

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
            'Medidas',
            'Nota',
            'Tipo',
            'Cod',
            'TipoReclamacao',
            'CausaRaiz',
            'Origem',
            'Municipio',
            'Abertura',
            'Tempo',
            'Desejada',
            'Status Resposta',
        ];
    }

    public function map($protest): array
    {
        $activeMed = $protest->medProtests->firstWhere('statusSist', 'MEDA') ?? $protest->medProtests->first();

        $startDate = $protest->tipoNota === 'NA'
            ? $protest->dtAberturaNota
            : optional($activeMed)->dtCriacaoMedida;

        $deadline = $protest->tipoNota === 'NA'
            ? $protest->dtConclusaoDesej
            : optional($activeMed)->dtFimMedidaDesej;

        $elapsed = $startDate
            ? Carbon::parse($startDate)->diffForHumans(now(), [
                'parts'  => 2,
                'short'  => true,
                'syntax' => Carbon::DIFF_ABSOLUTE,
            ])
            : '—';

        $deadlineDate = $deadline ? Carbon::parse($deadline)->format('d/m/Y') : '—';

        $latestJob = $activeMed?->ProtestJobs->first();
        $jobStatusLabel = 'Sem Job';

        if ($latestJob) {
            $enum = ProtestJobStatus::tryFrom($latestJob->status);
            $jobStatusLabel = $enum ? $enum->label() : Str::headline($latestJob->status);
        }

        $origem = $protest->descricao ?? '';
        $parts = explode('Tipo de Solicitante: ', $origem);
        if (count($parts) > 1) {
            $origem = $parts[1];
        } else {
            $parts = explode('Nota de Atendimento ', $origem);
            if (count($parts) > 1) {
                $origem = $parts[1];
            }
        }

        return [
            $protest->medProtests->count(),
            $protest->nota ?? '',
            $protest->tipoNota ?? '',
            $activeMed?->codMedida ?? '',
            $activeMed?->txtCodCodificacao ?? '',
            $activeMed?->txtCodMedida ?? '',
            Str::upper($origem),
            $protest->cidade ?? '',
            optional($startDate)->format('d/m/Y') ?? '—',
            $elapsed,
            $deadlineDate,
            $jobStatusLabel,
        ];
    }

    public function registerEvents(): array
    {
        return [];
    }

    public function properties(): array
    {
        return [
            'creator'        => 'SICODE',
            'lastModifiedBy' => 'SICODE',
            'title'          => 'Protests Export List',
            'description'    => 'Exportação das Reclamações em tela',
            'subject'        => 'Protests',
            'keywords'       => 'protests, export, data',
            'category'       => 'Exports',
        ];
    }
}

