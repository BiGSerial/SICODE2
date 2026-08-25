<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class TrackAuthenticatedUserActivity
{
    private const SESSION_KEY = 'last_seen_at_synced_at';
    private const SYNC_INTERVAL_MINUTES = 15;
    private static ?bool $hasLastSeenColumn = null;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->user() || !$this->canSync($request)) {
            return $response;
        }

        $request->user()
            ->forceFill(['last_seen_at' => now()])
            ->saveQuietly();

        $request->session()->put(self::SESSION_KEY, now()->timestamp);

        return $response;
    }

    private function canSync(Request $request): bool
    {
        if (!$request->hasSession() || !$this->hasLastSeenColumn()) {
            return false;
        }

        $lastSyncedAt = (int) $request->session()->get(self::SESSION_KEY, 0);

        return $lastSyncedAt <= now()->subMinutes(self::SYNC_INTERVAL_MINUTES)->timestamp;
    }

    private function hasLastSeenColumn(): bool
    {
        if (self::$hasLastSeenColumn === true) {
            return true;
        }

        self::$hasLastSeenColumn = Schema::hasColumn('users', 'last_seen_at');

        return self::$hasLastSeenColumn === true;
    }
}
