<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];

    protected $casts = [
        'updated_by' => 'string',
    ];

    /** Valores já lidos nesta requisição (`SicodeRules` consulta a mesma regra por linha da lista). Zerado ao salvar/excluir. */
    private static array $requestMemo = [];

    protected static function booted(): void
    {
        static::saved(fn () => self::$requestMemo = []);
        static::deleted(fn () => self::$requestMemo = []);
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        // Console (testes, filas, tinker): sem memo. O banco é revertido entre testes e workers vivem muito tempo; só a requisição web memoiza.
        if (app()->runningInConsole()) {
            return static::query()->where('key', $key)->value('value') ?? $default;
        }

        if (!array_key_exists($key, self::$requestMemo)) {
            self::$requestMemo[$key] = static::query()->where('key', $key)->value('value');
        }

        return self::$requestMemo[$key] ?? $default;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = static::getValue($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value'      => $value,
                'updated_by' => auth()->id(),
            ]
        );

        SystemSettingVersion::recordFor($key, $value);
    }
}
