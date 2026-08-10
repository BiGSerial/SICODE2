<?php

namespace App\Notifications\Channels;

use Illuminate\Database\QueryException;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

class IdempotentDatabaseChannel extends DatabaseChannel
{
    public function send($notifiable, Notification $notification)
    {
        $route = $notifiable->routeNotificationFor('database', $notification);
        $payload = $this->buildPayload($notifiable, $notification);

        try {
            return $route->create($payload);
        } catch (QueryException $exception) {
            if (! $this->isDuplicateKeyViolation($exception)) {
                throw $exception;
            }

            $existing = $route->whereKey($payload['id'])->first();

            if ($existing) {
                return $existing;
            }

            throw $exception;
        }
    }

    private function isDuplicateKeyViolation(QueryException $exception): bool
    {
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        if ($driverCode === 1062) {
            return true;
        }

        return str_contains(strtolower($exception->getMessage()), 'duplicate entry');
    }
}
