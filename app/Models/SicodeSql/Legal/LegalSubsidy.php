<?php

namespace App\Models\SicodeSql\Legal;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalSubsidy extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv2';

    protected $table = 'dbo.subjus_subsidios';

    protected $primaryKey = 'Número do Caso';

    public $incrementing = false;

    public $timestamps = false;

    public const SOURCE_TYPE = 'legal_subsidy';

    protected $fillable = [
        'Número do Caso',
        'Número do Processo',
        'Status',
        'Nome Empresa',
        'Gestor do Processo',
        'Escritório',
        'Área Atual Responsável',
        'Responsável Atual',
        'Área Solicitante',
        'Responsável Solicitante',
        'Tipo Subsídio',
        'Data Limite',
        'Rejeição',
        'Status Subsídio',
        'Data Alteração',
    ];

    protected $casts = [
        'Data Limite' => 'datetime',
        'Data Alteração' => 'datetime',

        // Casts usados quando a consulta vier com aliases normalizados
        'deadline_at' => 'datetime',
        'changed_at' => 'datetime',
    ];

    public const NORMALIZED_COLUMNS = [
        'Número do Caso as external_case_number',
        'Número do Processo as process_number',
        'Status as external_status',
        'Nome Empresa as company_name',
        'Gestor do Processo as process_manager',
        'Escritório as law_firm',
        'Área Atual Responsável as current_responsible_area',
        'Responsável Atual as current_responsible_name',
        'Área Solicitante as requesting_area',
        'Responsável Solicitante as requesting_responsible_name',
        'Tipo Subsídio as information_request_type',
        'Data Limite as deadline_at',
        'Rejeição as rejection',
        'Status Subsídio as information_request_status',
        'Data Alteração as changed_at',
    ];

    public function scopeNormalized(Builder $query): Builder
    {
        return $query->select(self::NORMALIZED_COLUMNS);
    }

    public function toNormalizedArray(): array
    {
        return [
            'source_type' => self::SOURCE_TYPE,

            'external_case_number' => $this->external_case_number ?? $this->{'Número do Caso'} ?? null,
            'process_number' => $this->process_number ?? $this->{'Número do Processo'} ?? null,
            'external_status' => $this->external_status ?? $this->{'Status'} ?? null,
            'company_name' => $this->company_name ?? $this->{'Nome Empresa'} ?? null,
            'process_manager' => $this->process_manager ?? $this->{'Gestor do Processo'} ?? null,
            'law_firm' => $this->law_firm ?? $this->{'Escritório'} ?? null,
            'current_responsible_area' => $this->current_responsible_area ?? $this->{'Área Atual Responsável'} ?? null,
            'current_responsible_name' => $this->current_responsible_name ?? $this->{'Responsável Atual'} ?? null,
            'requesting_area' => $this->requesting_area ?? $this->{'Área Solicitante'} ?? null,
            'requesting_responsible_name' => $this->requesting_responsible_name ?? $this->{'Responsável Solicitante'} ?? null,
            'information_request_type' => $this->information_request_type ?? $this->{'Tipo Subsídio'} ?? null,
            'deadline_at' => $this->deadline_at ?? $this->{'Data Limite'} ?? null,
            'rejection' => $this->rejection ?? $this->{'Rejeição'} ?? null,
            'information_request_status' => $this->information_request_status ?? $this->{'Status Subsídio'} ?? null,
            'changed_at' => $this->changed_at ?? $this->{'Data Alteração'} ?? null,

            'raw_payload' => $this->getAttributes(),
        ];
    }
}