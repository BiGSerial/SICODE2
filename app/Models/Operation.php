<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    use HasFactory;

    public const SAP_PRINT_PREFIX = 'IMPR ';

    protected static function booted(): void
    {
        static::saving(function (self $operation) {
            $operation->status = self::normalizeStatus($operation->status);
            return true;
        });
    }

    public static function normalizeStatus(?string $status): ?string
    {
        $status = trim((string) $status);

        if (str_starts_with(strtoupper($status), self::SAP_PRINT_PREFIX)) {
            return trim(substr($status, strlen(self::SAP_PRINT_PREFIX)));
        }

        return $status !== '' ? $status : null;
    }

    public function getStatusAttribute($value): ?string
    {
        return self::normalizeStatus($value);
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
