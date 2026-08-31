<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClosureCycle extends Model
{
    use HasFactory;

    public const STATUS_OPEN     = 'OPEN';
    public const STATUS_FROZEN   = 'FROZEN';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    protected $fillable = [
        'year',
        'month',
        'label',
        'status',
        'frozen_at',
        'frozen_by',
    ];

    protected $casts = [
        'frozen_at' => 'datetime',
    ];

    public function Targets()
    {
        return $this->hasMany(ClosureTarget::class);
    }

    public function FrozenBy()
    {
        return $this->belongsTo(User::class, 'frozen_by')->withTrashed();
    }

    public function periodKey(): int
    {
        return ($this->year * 100) + $this->month;
    }

    public static function currentPeriodKey(): int
    {
        return ((int) now()->year * 100) + (int) now()->month;
    }
}
