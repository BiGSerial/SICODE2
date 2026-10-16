<?php

namespace App\Services\Closure;

use App\Models\Closure\ClosureSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Configurações do despachante (closure_settings, chave/valor). Leitura com cache curto,
 * invalidado a cada gravação.
 */
class ClosureSettings
{
    public const KEY_ALLOW_FUTURE_COMPLETION = 'allow_future_completion';

    public const KEY_SELF_ASSIGN_ENABLED = 'self_assign_enabled';

    public const KEY_SELF_ASSIGN_TYPES = 'self_assign_types';

    public const CACHE_TTL_SECONDS = 60;

    private const DEFAULTS = [
        self::KEY_ALLOW_FUTURE_COMPLETION => false,
        self::KEY_SELF_ASSIGN_ENABLED     => false,
        self::KEY_SELF_ASSIGN_TYPES       => [ClosureMetaClassifier::TYPE_PASSIVE, ClosureMetaClassifier::TYPE_META],
    ];

    public function allowFutureCompletion(): bool
    {
        return (bool) $this->get(self::KEY_ALLOW_FUTURE_COMPLETION);
    }

    public function selfAssignEnabled(): bool
    {
        return (bool) $this->get(self::KEY_SELF_ASSIGN_ENABLED);
    }

    /** @return array<int, string> */
    public function selfAssignTypes(): array
    {
        return $this->normalizeTypes((array) $this->get(self::KEY_SELF_ASSIGN_TYPES));
    }

    public function setAllowFutureCompletion(bool $value, ?User $actor): void
    {
        $this->set(self::KEY_ALLOW_FUTURE_COMPLETION, $value, $actor);
    }

    public function setSelfAssignEnabled(bool $value, ?User $actor): void
    {
        $this->set(self::KEY_SELF_ASSIGN_ENABLED, $value, $actor);
    }

    /** @param array<int, string> $types */
    public function setSelfAssignTypes(array $types, ?User $actor): void
    {
        $this->set(self::KEY_SELF_ASSIGN_TYPES, $this->normalizeTypes($types), $actor);
    }

    public function forget(): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            Cache::forget($this->cacheKey($key));
        }
    }

    private function get(string $key): mixed
    {
        return Cache::remember($this->cacheKey($key), self::CACHE_TTL_SECONDS, function () use ($key) {
            $setting = ClosureSetting::query()->where('key', $key)->first();

            return $setting && $setting->value !== null ? $setting->value : self::DEFAULTS[$key];
        });
    }

    private function set(string $key, mixed $value, ?User $actor): void
    {
        ClosureSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_by' => $actor?->id]
        );

        Cache::forget($this->cacheKey($key));
    }

    /**
     * @param array<int, mixed> $types
     * @return array<int, string>
     */
    private function normalizeTypes(array $types): array
    {
        $valid = [ClosureMetaClassifier::TYPE_PASSIVE, ClosureMetaClassifier::TYPE_META, ClosureMetaClassifier::TYPE_FUTURE];

        return array_values(array_intersect($valid, array_map(fn ($type) => strtoupper((string) $type), $types)));
    }

    private function cacheKey(string $key): string
    {
        return "closure_settings.{$key}";
    }
}
