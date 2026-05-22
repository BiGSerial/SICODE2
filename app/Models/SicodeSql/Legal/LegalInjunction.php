<?php

namespace App\Models\SicodeSql\Legal;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalInjunction extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv2';

    protected $table = 'dbo.subjus_liminares';

    protected $primaryKey = 'Número do Caso';

    public $incrementing = false;

    public $timestamps = false;

    public const SOURCE_TYPE = 'legal_injunction';

    protected $fillable = [
        'Número do Caso',
        'Número do Processo',
        'Status',
        'Empresa',
        'Gestor do Processo',
        'Escritório',
        'Área Atual Responsável',
        'Responsável Atual',
        'Área Solicitante',
        'Responsável Solicitante',
        'Descrição',
        'Data Inicial',
        'Modalidade Liminar',
        'Situação Liminar',
        'Status Liminar',
        'Prazo Fatal de Redirecionamento',
        'Data Alteração',
    ];

    protected $casts = [
        'Data Inicial' => 'datetime',
        'Prazo Fatal de Redirecionamento' => 'datetime',
        'Data Alteração' => 'datetime',

        // Casts usados quando a consulta vier com aliases normalizados
        'started_at' => 'datetime',
        'redirect_deadline_at' => 'datetime',
        'changed_at' => 'datetime',
    ];

    public const NORMALIZED_COLUMNS = [
        'Número do Caso as external_case_number',
        'Número do Processo as process_number',
        'Status as external_status',
        'Empresa as company_name',
        'Gestor do Processo as process_manager',
        'Escritório as law_firm',
        'Área Atual Responsável as current_responsible_area',
        'Responsável Atual as current_responsible_name',
        'Área Solicitante as requesting_area',
        'Responsável Solicitante as requesting_responsible_name',
        'Descrição as description',
        'Data Inicial as started_at',
        'Modalidade Liminar as injunction_modality',
        'Situação Liminar as injunction_situation',
        'Status Liminar as injunction_status',
        'Prazo Fatal de Redirecionamento as redirect_deadline_at',
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
            'company_name' => $this->company_name ?? $this->{'Empresa'} ?? null,
            'process_manager' => $this->process_manager ?? $this->{'Gestor do Processo'} ?? null,
            'law_firm' => $this->law_firm ?? $this->{'Escritório'} ?? null,
            'current_responsible_area' => $this->current_responsible_area ?? $this->{'Área Atual Responsável'} ?? null,
            'current_responsible_name' => $this->current_responsible_name ?? $this->{'Responsável Atual'} ?? null,
            'requesting_area' => $this->requesting_area ?? $this->{'Área Solicitante'} ?? null,
            'requesting_responsible_name' => $this->requesting_responsible_name ?? $this->{'Responsável Solicitante'} ?? null,
            'description' => $this->description ?? $this->{'Descrição'} ?? null,
            'started_at' => $this->started_at ?? $this->{'Data Inicial'} ?? null,
            'injunction_modality' => $this->injunction_modality ?? $this->{'Modalidade Liminar'} ?? null,
            'injunction_situation' => $this->injunction_situation ?? $this->{'Situação Liminar'} ?? null,
            'injunction_status' => $this->injunction_status ?? $this->{'Status Liminar'} ?? null,
            'redirect_deadline_at' => $this->redirect_deadline_at ?? $this->{'Prazo Fatal de Redirecionamento'} ?? null,
            'changed_at' => $this->changed_at ?? $this->{'Data Alteração'} ?? null,

            'raw_payload' => $this->getAttributes(),
        ];
    }
}