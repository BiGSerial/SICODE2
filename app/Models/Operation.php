<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    use HasFactory;

    public const IGNORED_STATUS_PREFIX = 'IMPR LIB';

    protected static function booted(): void
    {
        // Esse status é um registro intermediário do SAP e não pode participar
        // das decisões operacionais do SICODE.
        static::addGlobalScope('without_impr_lib', function ($query) {
            $query->where(function ($statusQuery) {
                $statusQuery->whereNull('status')
                    ->orWhereRaw("UPPER(TRIM(status)) NOT LIKE 'IMPR LIB%'");
            });
        });

        static::saving(function (self $operation) {
            return !self::isIgnoredStatus($operation->status);
        });
    }

    public static function isIgnoredStatus(?string $status): bool
    {
        return str_starts_with(strtoupper(trim((string) $status)), self::IGNORED_STATUS_PREFIX);
    }

    protected $fillable = [
        'order_id',
        'operacao',
        'descOperacao',
        'inicioPlanejado',
        'fimPlanejado',
        'inicioReal',
        'fimReal',
        'status',
        'notaOv',
        'cenPlan',
        'cenTrab',
        'txtCenTrab',
    ];

    protected $casts = [
        'inicioPlanejado' => 'date',
        'fimPlanejado' => 'date',
        'inicioReal' => 'date',
        'fimReal' => 'date',
    ];

    public function Order()
    {
        return $this->belongsTo(Order::class);
    }
}
