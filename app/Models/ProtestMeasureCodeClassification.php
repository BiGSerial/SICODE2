<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ProtestMeasureCodeClassification extends Model
{
    public const CLASSIFICATION_CIP = 'cip';
    public const CLASSIFICATION_CONSTRUCTION = 'construction';
    private const CONSTRUCTION_CACHE_KEY = 'protest_measure_code_classifications.construction';

    protected $fillable = [
        'code',
        'classification',
        'label',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public static function constructionCodes(): array
    {
        return Cache::remember(self::CONSTRUCTION_CACHE_KEY, now()->addMinutes(10), function () {
            return self::query()
                ->where('active', true)
                ->where('classification', self::CLASSIFICATION_CONSTRUCTION)
                ->pluck('code')
                ->map(fn ($code) => self::normalizeCode($code))
                ->filter()
                ->unique()
                ->values()
                ->all();
        });
    }

    public static function normalizeCode(mixed $code): string
    {
        return mb_strtoupper(trim((string) $code));
    }

    public function setCodeAttribute(mixed $value): void
    {
        $this->attributes['code'] = self::normalizeCode($value);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CONSTRUCTION_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CONSTRUCTION_CACHE_KEY));
    }
}
