<?php

namespace App\Models;

use Carbon\Carbon;
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

    /**
     * Primeiro dia da competência — usado como referência de "aging" do passivo,
     * já que `frozen_at` marca quando o snapshot foi gravado, não quando a Ordem
     * ficou pendente (o backfill grava várias competências históricas no mesmo instante).
     */
    public function startDate(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1)->startOfDay();
    }

    public static function currentPeriodKey(): int
    {
        return ((int) now()->year * 100) + (int) now()->month;
    }
}
