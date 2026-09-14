<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkReportFlowProduction extends Model
{
    public const STAGE_FISCALIZATION = 'fiscalization';
    public const STAGE_PAYMENT = 'payment';
    public const STAGE_PUBLICATION = 'publication';
    public const SCOPE_GENERAL = 'general';
    public const SCOPE_NETWORK = 'network';
    public const SCOPE_CONNECTION = 'connection';

    protected $fillable = [
        'work_report_id',
        'production_id',
        'stage',
        'final_scope',
        'is_current',
        'linked_at',
        'linked_by',
        'reversed_at',
        'reversed_by',
        'reverse_reason',
        'source',
        'metadata',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'linked_at' => 'datetime',
        'reversed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function WorkReport()
    {
        return $this->belongsTo(WorkReport::class);
    }

    public function Production()
    {
        return $this->belongsTo(Production::class);
    }

    public function LinkedBy()
    {
        return $this->belongsTo(User::class, 'linked_by')->withTrashed();
    }

    public function ReversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by')->withTrashed();
    }

    public function stageLabel(): string
    {
        return match ($this->stage) {
            self::STAGE_FISCALIZATION => 'Fiscalização',
            self::STAGE_PAYMENT => 'Medição',
            self::STAGE_PUBLICATION => 'Publicação',
            default => '-',
        };
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            'retrofill_inference' => 'Carga histórica',
            'dispatch_supervision_main' => 'Despacho de Fiscalização',
            'dispatch_payment_main' => 'Despacho de Medição',
            'dispatch_payment_stack' => 'Pilha de Medição',
            'dispatch_fiscalization' => 'Despacho de Fiscalização',
            'dispatch_payment' => 'Despacho de Medição',
            'dispatch_publication' => 'Despacho de Publicação',
            'services_payment_self_assign' => 'Autoatribuição de Medição',
            'dispatch_workflow' => match ($this->stage) {
                self::STAGE_FISCALIZATION => 'Despacho de Fiscalização',
                self::STAGE_PAYMENT => 'Despacho de Medição',
                self::STAGE_PUBLICATION => 'Despacho de Publicação',
                default => 'Despacho operacional',
            },
            default => $this->source ? 'Origem operacional' : '-',
        };
    }

    public function stageBadgeClass(): string
    {
        return match ($this->stage) {
            self::STAGE_PAYMENT => 'text-bg-primary',
            self::STAGE_PUBLICATION => 'text-bg-info',
            self::STAGE_FISCALIZATION => 'text-bg-success',
            default => 'text-bg-secondary',
        };
    }
}
