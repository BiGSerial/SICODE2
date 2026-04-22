<?php

namespace App\Services\Wall\Support;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

trait CacheLockTrait
{
    /**
     * Resolve um valor via cache com lock distribuído opcional.
     *
     * - Verifica cache primário; retorna se presente.
     * - Verifica cache stale (30 min) para servir dados antigos enquanto recalcula.
     * - Usa lock para evitar dog-pile em drivers que suportam LockProvider.
     * - Fallback sem lock para drivers simples (ArrayStore, FileStore sem lock).
     */
    protected function rememberWithOptionalLock(string $key, int $ttlSeconds, callable $resolver): mixed
    {
        $ttl = now()->addSeconds($ttlSeconds);
        $staleKey = $key . ':stale';

        $cached = Cache::get($key);
        if (!is_null($cached)) {
            return $cached;
        }

        $stale = Cache::get($staleKey);
        if (!is_null($stale)) {
            return $stale;
        }

        $store = Cache::getStore();

        if ($store instanceof LockProvider) {
            $lock = null;
            try {
                $lock = Cache::lock($key . ':lock', 25);

                if ($lock->get()) {
                    $value = $resolver();
                    Cache::put($key, $value, $ttl);
                    Cache::put($staleKey, $value, now()->addMinutes(30));
                    return $value;
                }

                // Outro processo está computando; aguarda até 2 s antes de usar stale.
                for ($i = 0; $i < 10; $i++) {
                    usleep(200_000); // 200 ms
                    $current = Cache::get($key);
                    if (!is_null($current)) {
                        return $current;
                    }
                }

                $stale = Cache::get($staleKey);
                if (!is_null($stale)) {
                    return $stale;
                }
            } catch (Throwable $e) {
                $stale = Cache::get($key) ?? Cache::get($staleKey);
                if (!is_null($stale)) {
                    return $stale;
                }

                Log::warning('wall cache-lock fallback', [
                    'key'     => $key,
                    'error'   => $e->getMessage(),
                    'class'   => get_class($e),
                ]);
            } finally {
                if (!is_null($lock)) {
                    try {
                        $lock->release();
                    } catch (Throwable) {
                    }
                }
            }
        }

        $value = Cache::remember($key, $ttl, $resolver);
        Cache::put($staleKey, $value, now()->addMinutes(30));
        return $value;
    }
}
