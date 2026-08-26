<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class SystemSettingVersion extends Model
{
    protected $fillable = [
        'key',
        'value',
        'changed_by',
    ];

    protected $casts = [
        'changed_by' => 'string',
    ];

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public static function recordFor(string $key, ?string $value): void
    {
        static::create([
            'key'        => $key,
            'value'      => $value,
            'changed_by' => auth()->id(),
        ]);
    }

    public static function historyFor(string $key, int $limit = 20): Collection
    {
        return static::query()
            ->where('key', $key)
            ->with('changedBy:id,name')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
